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

        $totals = [0, 0, 0, 0, 0];
        foreach ($rows as $row) {
            for ($i = 1; $i <= 5; $i++) {
                $totals[$i - 1] += (int) $row[$i];
            }
        }
        $xmlEscape = fn ($value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $inlineCell = fn ($ref, $value, $style = 0) => '<c r="' . $ref . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t>' . $xmlEscape($value) . '</t></is></c>';
        $numberCell = fn ($ref, $value, $style = 0) => '<c r="' . $ref . '"' . ($style ? ' s="' . $style . '"' : '') . '><v>' . (int) $value . '</v></c>';
        $lastDataRow = 6 + count($rows);
        $totalRow = $lastDataRow + 1;
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <dimension ref="A1:F' . $totalRow . '"/>
  <sheetViews><sheetView workbookViewId="0"><pane ySplit="6" topLeftCell="A7" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>
  <cols><col min="1" max="1" width="30" customWidth="1"/><col min="2" max="3" width="16" customWidth="1"/><col min="4" max="4" width="20" customWidth="1"/><col min="5" max="6" width="22" customWidth="1"/></cols>
  <sheetData>';
        $xml .= '<row r="1" ht="30" customHeight="1">' . $inlineCell('A1', 'LAPORAN KUNJUNGAN WISATA', 1) . '</row>';
        $periodeLabel = ucfirst($periode) . ($periode === 'bulanan' ? ' - ' . $tahun : ($periode === 'harian' ? ' - ' . Carbon::parse($bulan . '-01')->translatedFormat('F Y') : ' - ' . $tahun));
        $xml .= '<row r="2" ht="22" customHeight="1">' . $inlineCell('A2', 'Periode laporan: ' . $periodeLabel . ' | Dicetak: ' . now()->format('d-m-Y H:i'), 2) . '</row>';
        $xml .= '<row r="4" ht="22" customHeight="1">' . $inlineCell('A4', 'TOTAL BOOKING', 3) . $numberCell('B4', $totals[0], 6)
            . $inlineCell('C4', 'TOTAL PENGUNJUNG', 3) . $numberCell('D4', $totals[1], 6)
            . $inlineCell('E4', 'TOTAL PENDAPATAN', 3) . $numberCell('F4', $totals[2], 7) . '</row>';
        $xml .= '<row r="6" ht="28" customHeight="1">';
        foreach (['Periode', 'Booking', 'Pengunjung', 'Pendapatan (Rp)', 'Kunjungan Selesai', 'Ulasan'] as $index => $heading) {
            $xml .= $inlineCell(chr(65 + $index) . '6', $heading, 4);
        }
        $xml .= '</row>';

        $rowNum = 7;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '" ht="21" customHeight="1">';
            $xml .= $inlineCell('A' . $rowNum, $row[0], 5);
            $xml .= $numberCell('B' . $rowNum, $row[1], 6) . $numberCell('C' . $rowNum, $row[2], 6);
            $xml .= $numberCell('D' . $rowNum, $row[3], 7) . $numberCell('E' . $rowNum, $row[4], 6) . $numberCell('F' . $rowNum, $row[5], 6);
            $xml .= '</row>';
            $rowNum++;
        }
        $xml .= '<row r="' . $totalRow . '" ht="24" customHeight="1">' . $inlineCell('A' . $totalRow, 'TOTAL', 8);
        foreach ($totals as $index => $value) {
            $xml .= $numberCell(chr(66 + $index) . $totalRow, $value, $index === 2 ? 9 : 8);
        }
        $xml .= '</row></sheetData><mergeCells count="2"><mergeCell ref="A1:F1"/><mergeCell ref="A2:F2"/></mergeCells>';
        if (count($rows) > 0) {
            $xml .= '<autoFilter ref="A6:F' . $lastDataRow . '"/>';
        }
        $xml .= '</worksheet>';

        $rels = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
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
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="&quot;Rp &quot;#,##0"/></numFmts><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="16"/><name val="Calibri"/></font><font><color rgb="FF64748B"/><sz val="10"/><name val="Calibri"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF047857"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE2F3EC"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="10"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0"/><xf numFmtId="0" fontId="0" fillId="3" borderId="1" applyFill="1"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="1"/><xf numFmtId="3" fontId="0" fillId="0" borderId="1" applyNumberFormat="1"/><xf numFmtId="165" fontId="0" fillId="0" borderId="1" applyNumberFormat="1"/><xf numFmtId="0" fontId="1" fillId="3" borderId="1" applyFont="1" applyFill="1"/><xf numFmtId="165" fontId="1" fillId="3" borderId="1" applyFont="1" applyFill="1" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->close();

        $contents = file_get_contents($zipPath);
        @unlink($zipPath);

        return response($contents, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length'      => strlen($contents),
        ]);
    }

    public function laporanPdf(Request $request)
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

        $rows = $this->buildLaporanRows($periode, $bulan, $tahun);
        $start = $request->input('start');
        $end = $request->input('end');
        if ($start && $end) {
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = Carbon::parse($end)->endOfDay();
            $rows = array_values(array_filter($rows, function ($row) use ($startDate, $endDate) {
                return Carbon::parse($row[6])->gte($startDate) && Carbon::parse($row[7])->lte($endDate);
            }));
        }

        $lines = ['LAPORAN KUNJUNGAN WISATA', 'Periode: ' . ucfirst($periode) . ($periode === 'bulanan' ? ' - ' . $tahun : ''), '',
            str_pad('Periode', 28) . str_pad('Booking', 10) . str_pad('Pengunjung', 12) . str_pad('Pendapatan', 16) . str_pad('Selesai', 10) . 'Ulasan',
            str_repeat('-', 84)];
        foreach ($rows as $row) {
            $lines[] = str_pad(substr($row[0], 0, 27), 28) . str_pad((string) $row[1], 10) . str_pad((string) $row[2], 12)
                . str_pad(number_format($row[3], 0, ',', '.'), 16) . str_pad((string) $row[4], 10) . (string) $row[5];
        }
        if (count($rows) === 0) {
            $lines[] = 'Tidak ada data untuk periode ini.';
        }

        $pageRows = array_chunk($rows, 20);
        if (count($pageRows) === 0) {
            $pageRows = [[]];
        }
        $totals = [0, 0, 0, 0, 0];
        foreach ($rows as $row) {
            for ($i = 1; $i <= 5; $i++) {
                $totals[$i - 1] += (int) $row[$i];
            }
        }
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
        $pageRefs = [];
        foreach ($pageRows as $index => $pageRowsList) {
            $pageId = 5 + ($index * 2);
            $streamId = $pageId + 1;
            $pageRefs[] = $pageId . ' 0 R';
            $stream = '';
            $drawText = function (float $x, float $y, string $value, int $size = 9, bool $bold = false, string $color = '0.18 0.25 0.33') use (&$stream) {
                $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
                $value = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
                $stream .= $color . " rg BT /F" . ($bold ? '2' : '1') . ' ' . $size . ' Tf ' . $x . ' ' . $y . ' Td (' . $value . ") Tj ET\n";
            };
            $drawRect = function (float $x, float $y, float $width, float $height, string $color) use (&$stream) {
                $stream .= $color . " rg " . $x . ' ' . $y . ' ' . $width . ' ' . $height . " re f\n";
            };

            $drawRect(0, 0, 842, 595, '1 1 1');
            $drawRect(0, 500, 842, 95, '0.02 0.36 0.28');
            $drawRect(40, 523, 44, 44, '0.08 0.63 0.45');
            $drawText(49, 539, 'KP', 14, true, '1 1 1');
            $drawText(102, 555, 'LAPORAN KUNJUNGAN WISATA', 18, true, '1 1 1');
            $drawText(102, 536, 'Ringkasan kunjungan dan operasional', 9, false, '0.84 0.94 0.90');
            $periodeLabel = $start && $end
                ? Carbon::parse($start)->format('d M Y') . ' - ' . Carbon::parse($end)->format('d M Y')
                : ucfirst($periode) . ($periode === 'bulanan' ? ' ' . $tahun : ($periode === 'harian' ? ' ' . Carbon::parse($bulan . '-01')->translatedFormat('F Y') : ' ' . $tahun));
            $drawText(570, 555, 'PERIODE', 8, true, '0.72 0.88 0.81');
            $drawText(570, 536, $periodeLabel, 12, true, '1 1 1');
            $drawText(710, 555, 'TANGGAL CETAK', 8, true, '0.72 0.88 0.81');
            $drawText(710, 536, now()->format('d-m-Y'), 11, true, '1 1 1');

            if ($index === 0) {
                $cards = [
                    ['TOTAL BOOKING', number_format($totals[0], 0, ',', '.')],
                    ['PENGUNJUNG', number_format($totals[1], 0, ',', '.')],
                    ['PENDAPATAN', 'Rp ' . number_format($totals[2], 0, ',', '.')],
                    ['SELESAI / ULASAN', number_format($totals[3], 0, ',', '.') . ' / ' . number_format($totals[4], 0, ',', '.')],
                ];
                foreach ($cards as $cardIndex => [$label, $value]) {
                    $x = 40 + ($cardIndex * 193);
                    $drawRect($x, 445, 180, 43, '0.94 0.97 0.95');
                    $drawText($x + 12, 471, $label, 7, true, '0.35 0.45 0.40');
                    $drawText($x + 12, 454, $value, 12, true, '0.02 0.36 0.28');
                }
            }

            $hasMonthlyChart = $index === 0 && $periode === 'bulanan' && count($rows) > 0;
            if ($hasMonthlyChart) {
                $drawRect(40, 300, 762, 132, '0.98 0.99 0.98');
                $drawText(54, 414, 'TREN PENGUNJUNG PER BULAN', 8, true, '0.18 0.31 0.25');
                $maxVisitors = max(1, max(array_column($rows, 2)));
                $chartBaseY = 326;
                $drawRect(66, $chartBaseY, 706, 1, '0.75 0.82 0.78');
                foreach ($rows as $chartIndex => $chartRow) {
                    $barX = 78 + ($chartIndex * 57);
                    $barHeight = max(2, ((int) $chartRow[2] / $maxVisitors) * 66);
                    $drawRect($barX, $chartBaseY + 1, 25, $barHeight, '0.08 0.63 0.45');
                    $drawText($barX - 2, 311, Carbon::parse($chartRow[6])->translatedFormat('M'), 7, false, '0.35 0.45 0.40');
                    if ($chartRow[2] > 0) {
                        $drawText($barX + 4, $chartBaseY + $barHeight + 5, (string) $chartRow[2], 6, false, '0.18 0.31 0.25');
                    }
                }
            }

            $tableTop = $index === 0 ? ($hasMonthlyChart ? 286 : 428) : 478;
            $drawRect(40, $tableTop - 24, 762, 24, '0.02 0.36 0.28');
            $drawText(50, $tableTop - 16, 'PERIODE', 8, true, '1 1 1');
            $drawText(320, $tableTop - 16, 'BOOKING', 8, true, '1 1 1');
            $drawText(400, $tableTop - 16, 'PENGUNJUNG', 8, true, '1 1 1');
            $drawText(505, $tableTop - 16, 'PENDAPATAN', 8, true, '1 1 1');
            $drawText(645, $tableTop - 16, 'SELESAI', 8, true, '1 1 1');
            $drawText(730, $tableTop - 16, 'ULASAN', 8, true, '1 1 1');
            $y = $tableTop - 42;
            foreach ($pageRowsList as $rowIndex => $row) {
                if ($rowIndex % 2 === 1) {
                    $drawRect(40, $y - 6, 762, 20, '0.96 0.98 0.97');
                }
                $drawText(50, $y, mb_substr($row[0], 0, 38), 8);
                $drawText(327, $y, (string) $row[1], 8);
                $drawText(412, $y, (string) $row[2], 8);
                $drawText(505, $y, 'Rp ' . number_format($row[3], 0, ',', '.'), 8);
                $drawText(660, $y, (string) $row[4], 8);
                $drawText(743, $y, (string) $row[5], 8);
                $y -= 17;
            }
            if (count($rows) === 0) {
                $drawText(50, $y, 'Tidak ada data untuk periode ini.', 9);
            }
            $drawRect(40, 28, 762, 1, '0.82 0.87 0.84');
            $drawText(40, 14, 'Laporan internal | Kawah Putih', 8, false, '0.42 0.49 0.45');
            $drawText(690, 14, 'Halaman ' . ($index + 1) . ' dari ' . count($pageRows), 8, false, '0.42 0.49 0.45');

            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
            $objects[$streamId] = '<< /Length ' . strlen($stream) . ">>\nstream\n" . $stream . "\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id]) . "\n";
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        $fileName = 'Laporan-' . ucfirst($periode) . '-' . now()->format('YmdHis') . '.pdf';
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdf),
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
