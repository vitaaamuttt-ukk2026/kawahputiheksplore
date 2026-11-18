<?php

namespace App\Exports;

use App\Models\Booking;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LaporanExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $periode;
    protected $bulan;
    protected $tahun;

    public function __construct($periode, $bulan, $tahun)
    {
        $this->periode = $periode;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function collection()
    {
        $rows = [];

        $buatBaris = function (string $label, $start, $end) use (&$rows) {
            $base = Booking::whereDate('tanggal_kunjungan', '>=', $start)
                           ->whereDate('tanggal_kunjungan', '<=', $end)
                           ->where('status', '!=', 'cancelled');

            $pendapatan = (int) Booking::whereDate('tanggal_kunjungan', '>=', $start)
                ->whereDate('tanggal_kunjungan', '<=', $end)
                ->where('status', 'paid')
                ->sum('total_bayar');

            $rows[] = [
                $label,
                (clone $base)->count(),
                (int) (clone $base)->sum('jumlah_pengunjung'),
                $pendapatan,
                (int) (clone $base)->where('status_kunjungan', 'selesai')->count(),
            ];
        };

        switch ($this->periode) {
            case 'harian':
                $awalBulan = Carbon::parse($this->bulan . '-01');
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
                $minggu = Carbon::create($this->tahun, 1, 1)->startOfWeek();
                $batas = Carbon::create($this->tahun, 12, 31)->endOfWeek();
                $nomor = 1;
                while ($minggu->lte($batas)) {
                    $awalMinggu = $minggu->copy();
                    $akhirMinggu = $minggu->copy()->endOfWeek();
                    if ($awalMinggu->year > $this->tahun) {
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
                    $awalBln = Carbon::create($this->tahun, $bln, 1);
                    $buatBaris(
                        $awalBln->translatedFormat('F Y'),
                        $awalBln->copy()->startOfDay(),
                        $awalBln->copy()->endOfMonth()
                    );
                }
                break;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Periode',
            'Booking',
            'Pengunjung',
            'Pendapatan',
            'Kunjungan Selesai',
        ];
    }

    public function title(): string
    {
        return 'Laporan ' . ucfirst($this->periode);
    }
}
