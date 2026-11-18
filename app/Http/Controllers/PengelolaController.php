<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\OperasionalSetting;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use ZipArchive;

class PengelolaController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total_hari_ini'     => Booking::whereDate('created_at', today())->count(),
            'sudah_verifikasi'   => Booking::where('status', 'paid')->count(),
            'belum_verifikasi'   => Booking::where('status', 'pending')->count(),
        ];

        $chartLabels = [];
        $chartVisitors = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartLabels[] = $date->format('d M');
            $chartVisitors[] = Booking::whereDate('created_at', $date)->count();
        }

        $recentBookings = Booking::latest()->take(10)->get();

        return view('pengelola.dashboard', compact('stats', 'chartLabels', 'chartVisitors', 'recentBookings'));
    }

    public function reservasi(): View
    {
        $search = request('search', ''); // Menambahkan variabel search agar tidak error
        $reservasi = Booking::latest()->get();
        
        return view('pengelola.reservasi', compact('reservasi', 'search'));
    }

    public function operasional(): View
    {
        $setting = OperasionalSetting::getSetting();
        return view('pengelola.operasional', compact('setting'));
    }

    public function updateOperasional(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'kuota_maksimal' => 'nullable|integer|min:0',
                'pengunjung_aktif' => 'nullable|integer|min:0',
                'status_gerbang' => 'nullable|string|max:100',
                'status_dermaga' => 'nullable|string|max:100',
                'info_dermaga' => 'nullable|string',
            ]);

            $setting = OperasionalSetting::first();
            if (!$setting) {
                OperasionalSetting::create($validated);
            } else {
                $setting->update($validated);
            }

            return back()->with('success', 'Data operasional berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function laporan(Request $request): View
    {
        $periode = $request->input('periode', 'bulanan');
        if (!in_array($periode, ['harian', 'mingguan', 'bulanan', 'tahunan'], true)) {
            $periode = 'bulanan';
        }

        $bulan = $request->input('bulan', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $bulan)) {
            $bulan = now()->format('Y-m');
        }

        $tahun = (int) $request->input('tahun', now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) now()->year;
        }

        $summary = ['booking' => 0, 'pengunjung' => 0, 'pendapatan' => 0, 'selesai' => 0, 'ulasan' => 0];

        $buatBaris = function (string $label, $start, $end) use (&$summary): array {
            $base = Booking::whereDate('tanggal_kunjungan', '>=', $start)
                           ->whereDate('tanggal_kunjungan', '<=', $end)
                           ->where('status', '!=', 'cancelled');

            $pendapatan = (int) Booking::whereDate('tanggal_kunjungan', '>=', $start)
                ->whereDate('tanggal_kunjungan', '<=', $end)
                ->whereIn('status', ['paid', 'verified'])
                ->sum('total_bayar');

            $ulasan = Review::whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)
                ->count();

            $baris = [
                'label'       => $label,
                'booking'     => (clone $base)->count(),
                'pengunjung'  => (int) (clone $base)->sum('jumlah_pengunjung'),
                'pendapatan'  => $pendapatan,
                'selesai'     => (int) (clone $base)->where('status_kunjungan', 'selesai')->count(),
                'ulasan'      => $ulasan,
                'start_date'  => $start->format('Y-m-d'),
                'end_date'    => $end->format('Y-m-d'),
            ];

            foreach (['booking', 'pengunjung', 'pendapatan', 'selesai', 'ulasan'] as $kunci) {
                $summary[$kunci] += $baris[$kunci];
            }

            return $baris;
        };

        $rows = [];

        switch ($periode) {
            case 'harian':
                $awalBulan = Carbon::parse($bulan . '-01');
                $akhirBulan = $awalBulan->copy()->endOfMonth();
                for ($tanggal = $awalBulan->copy(); $tanggal->lte($akhirBulan) && $tanggal->lte(today()); $tanggal->addDay()) {
                    $rows[] = $buatBaris(
                        $tanggal->format('d M Y'),
                        $tanggal->copy()->startOfDay(),
                        $tanggal->copy()->endOfDay()
                    );
                }
                break;

            case 'mingguan':
                $minggu = Carbon::create($tahun, 1, 1)->startOfWeek();
                $batas = Carbon::create($tahun, 12, 31)->endOfWeek();
                $nomor = 1;
                while ($minggu->lte($batas)) {
                    $awalMinggu = $minggu->copy();
                    $akhirMinggu = $minggu->copy()->endOfWeek();
                    if ($awalMinggu->year > $tahun) {
                        break;
                    }
                    $rows[] = $buatBaris(
                        'Minggu ' . $nomor . ' (' . $awalMinggu->format('d M') . ' - ' . $akhirMinggu->format('d M') . ')',
                        $awalMinggu->copy()->startOfDay(),
                        $akhirMinggu->copy()->endOfDay()
                    );
                    $nomor++;
                    $minggu->addWeek();
                }
                break;

            case 'tahunan':
                for ($thn = (int) now()->year - 4; $thn <= (int) now()->year; $thn++) {
                    $awalTahun = Carbon::create($thn, 1, 1);
                    $akhirTahun = $thn === (int) now()->year ? today() : Carbon::create($thn, 12, 31);
                    $rows[] = $buatBaris((string) $thn, $awalTahun->startOfDay(), $akhirTahun->copy()->endOfDay());
                }
                break;

            case 'bulanan':
            default:
                for ($bln = 1; $bln <= 12; $bln++) {
                    $awalBln = Carbon::create($tahun, $bln, 1);
                    $rows[] = $buatBaris(
                        $awalBln->translatedFormat('F Y'),
                        $awalBln->copy()->startOfDay(),
                        $awalBln->copy()->endOfMonth()
                    );
                }
                break;
        }

        $chartLabels = array_column($rows, 'label');
        $chartData = array_column($rows, 'pengunjung');

        $detailBookings = new \Illuminate\Pagination\LengthAwarePaginator(
            [],
            0,
            10,
            1,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        if (!empty($rows)) {
            $rangeStart = $rows[0]['start_date'];
            $rangeEnd   = $rows[count($rows) - 1]['end_date'];

            $detailBookings = Booking::whereDate('tanggal_kunjungan', '>=', $rangeStart)
                ->whereDate('tanggal_kunjungan', '<=', $rangeEnd)
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('tanggal_kunjungan')
                ->paginate(10)
                ->appends($request->query());
        }

        return view('pengelola.laporan', compact('periode', 'bulan', 'tahun', 'rows', 'summary', 'chartLabels', 'chartData', 'detailBookings'));
    }

    public function laporanExcel(Request $request)
    {
        $start = $request->input('start');
        $end   = $request->input('end');

        $periode = $request->input('periode', 'bulanan');
        if (!in_array($periode, ['harian', 'mingguan', 'bulanan', 'tahunan'], true)) {
            $periode = 'bulanan';
        }

        $bulan = $request->input('bulan', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $bulan)) {
            $bulan = now()->format('Y-m');
        }

        $tahun = (int) $request->input('tahun', now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) now()->year;
        }

        $rows = $this->buildLaporanRows($periode, $bulan, $tahun);

        if ($start && $end) {
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate   = Carbon::parse($end)->endOfDay();
            $rows = array_filter($rows, function ($row) use ($startDate, $endDate) {
                $rStart = Carbon::parse($row[6]);
                $rEnd   = Carbon::parse($row[7]);
                return $rStart->gte($startDate) && $rEnd->lte($endDate);
            });
            $fileName = 'Laporan-' . Carbon::parse($start)->format('d-m-Y') . '_sd_' . Carbon::parse($end)->format('d-m-Y') . '.xlsx';
        } else {
            $fileName = 'Laporan-' . ucfirst($periode) . '-' . now()->format('YmdHis') . '.xlsx';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>Periode</t></is></c>
      <c r="B1" t="inlineStr"><is><t>Booking</t></is></c>
      <c r="C1" t="inlineStr"><is><t>Pengunjung</t></is></c>
      <c r="D1" t="inlineStr"><is><t>Pendapatan</t></is></c>
      <c r="E1" t="inlineStr"><is><t>Kunjungan Selesai</t></is></c>
      <c r="F1" t="inlineStr"><is><t>Ulasan</t></is></c>
    </row>';

        $rowNum = 2;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '">';
            $xml .= '<c r="A' . $rowNum . '" t="inlineStr"><is><t>' . htmlspecialchars($row[0]) . '</t></is></c>';
            $xml .= '<c r="B' . $rowNum . '" t="n"><v>' . (int) $row[1] . '</v></c>';
            $xml .= '<c r="C' . $rowNum . '" t="n"><v>' . (int) $row[2] . '</v></c>';
            $xml .= '<c r="D' . $rowNum . '" t="n"><v>' . (int) $row[3] . '</v></c>';
            $xml .= '<c r="E' . $rowNum . '" t="n"><v>' . (int) $row[4] . '</v></c>';
            $xml .= '<c r="F' . $rowNum . '" t="n"><v>' . (int) $row[5] . '</v></c>';
            $xml .= '</row>';
            $rowNum++;
        }

        $xml .= '</sheetData></worksheet>';

        $rels = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';

        $workbook = '<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Laporan" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';

        $zip = new ZipArchive();
        $zipPath = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();

        $contents = file_get_contents($zipPath);
        @unlink($zipPath);

        return response($contents, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length'      => strlen($contents),
        ]);
    }

    private function buildLaporanRows(string $periode, string $bulan, int $tahun): array
    {
        $rows = [];

        $buatBaris = function (string $label, $start, $end) use (&$rows) {
            $base = Booking::whereDate('tanggal_kunjungan', '>=', $start)
                           ->whereDate('tanggal_kunjungan', '<=', $end)
                           ->where('status', '!=', 'cancelled');

            $pendapatan = (int) Booking::whereDate('tanggal_kunjungan', '>=', $start)
                ->whereDate('tanggal_kunjungan', '<=', $end)
                ->whereIn('status', ['paid', 'verified'])
                ->sum('total_bayar');

            $rows[] = [
                $label,
                (clone $base)->count(),
                (int) (clone $base)->sum('jumlah_pengunjung'),
                $pendapatan,
                (int) (clone $base)->where('status_kunjungan', 'selesai')->count(),
                Review::whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end)
                    ->count(),
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
            ];
        };

        switch ($periode) {
            case 'harian':
                $awalBulan = Carbon::parse($bulan . '-01');
                $akhirBulan = $awalBulan->copy()->endOfMonth();
                for ($tanggal = $awalBulan->copy(); $tanggal->lte($akhirBulan) && $tanggal->lte(today()); $tanggal->addDay()) {
                    $buatBaris(
                        $tanggal->format('d M Y'),
                        $tanggal->copy()->startOfDay(),
                        $tanggal->copy()->endOfDay()
                    );
                }
                break;

            case 'mingguan':
                $minggu = Carbon::create($tahun, 1, 1)->startOfWeek();
                $batas = Carbon::create($tahun, 12, 31)->endOfWeek();
                $nomor = 1;
                while ($minggu->lte($batas)) {
                    $awalMinggu = $minggu->copy();
                    $akhirMinggu = $minggu->copy()->endOfWeek();
                    if ($awalMinggu->year > $tahun) {
                        break;
                    }
                    $buatBaris(
                        'Minggu ' . $nomor . ' (' . $awalMinggu->format('d M') . ' - ' . $akhirMinggu->format('d M') . ')',
                        $awalMinggu->copy()->startOfDay(),
                        $akhirMinggu->copy()->endOfDay()
                    );
                    $nomor++;
                    $minggu->addWeek();
                }
                break;

            case 'tahunan':
                for ($thn = (int) now()->year - 4; $thn <= (int) now()->year; $thn++) {
                    $awalTahun = Carbon::create($thn, 1, 1);
                    $akhirTahun = $thn === (int) now()->year ? today() : Carbon::create($thn, 12, 31);
                    $buatBaris((string) $thn, $awalTahun->startOfDay(), $akhirTahun->copy()->endOfDay());
                }
                break;

            case 'bulanan':
            default:
                for ($bln = 1; $bln <= 12; $bln++) {
                    $awalBln = Carbon::create($tahun, $bln, 1);
                    $buatBaris(
                        $awalBln->translatedFormat('F Y'),
                        $awalBln->copy()->startOfDay(),
                        $awalBln->copy()->endOfMonth()
                    );
                }
                break;
        }

        return $rows;
    }

    public function pengaturan(): View
    {
        $user = Auth::user();
        return view('pengelola.pengaturan', compact('user'));
    }

    public function updatePengaturan(Request $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8|confirmed',
            ]);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();

            return back()->with('success', 'Profil berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function verifyReservasi($id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($id) {
                $booking = Booking::where('id', $id)->where('status', 'pending')->firstOrFail();
                $booking->update(['status' => 'paid', 'paid_at' => now()]);
            });
            return back()->with('success', 'Reservasi berhasil diverifikasi!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function cancelReservasi($id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($id) {
                $booking = Booking::where('id', $id)->whereIn('status', ['pending', 'paid'])->firstOrFail();
                $booking->update(['status' => 'cancelled']);
            });
            return back()->with('success', 'Reservasi dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function storeReservasi(Request $request): RedirectResponse
    {
        return back()->with('success', 'Reservasi ditambahkan!');
    }

    public function destroyReservasi($reservasi): RedirectResponse
    {
        $reservasi->delete();
        return back()->with('success', 'Reservasi dihapus.');
    }

    public function cekKode(): View
    {
        return view('pengelola.cek-kode');
    }

    public function tandaiDipakai($id): RedirectResponse
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status_kunjungan' => 'sedang']);
        return back()->with('success', 'Tiket sedang digunakan.');
    }

    public function tandaiSelesai($id): RedirectResponse
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status_kunjungan' => 'selesai']);
        return back()->with('success', 'Kunjungan selesai.');
    }

    public function tandaiSedang($id): RedirectResponse
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status_kunjungan' => 'sedang']);
        return back()->with('success', 'Kunjungan sedang berlangsung.');
    }
}