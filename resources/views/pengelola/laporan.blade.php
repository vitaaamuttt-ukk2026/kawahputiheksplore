@extends('layouts.panel')
@section('title', 'Laporan')
@section('page-title', 'Laporan Kunjungan')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@php
    $periodeList = [
        'harian'   => 'Harian',
        'mingguan' => 'Mingguan',
        'bulanan'  => 'Bulanan',
        'tahunan'  => 'Tahunan',
    ];
    $rupiah = fn ($nilai) => 'Rp ' . number_format($nilai, 0, ',', '.');
@endphp

<!-- Tabs Periode -->
<div class="flex flex-wrap gap-2 mb-6">
    @foreach($periodeList as $key => $label)
        <a href="{{ route('pengelola.laporan', array_filter(['periode' => $key, 'bulan' => $bulan, 'tahun' => $tahun])) }}"
           class="px-4 py-2 rounded-xl text-sm font-semibold border transition {{ $periode === $key ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<!-- Filter Periode Data -->
<form method="GET" action="{{ route('pengelola.laporan') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-6 flex flex-wrap items-end gap-4">
    <input type="hidden" name="periode" value="{{ $periode }}">

    @if($periode === 'harian')
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Bulan</label>
            <input type="month" name="bulan" value="{{ $bulan }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-emerald-500 focus:outline-none">
        </div>
    @elseif($periode === 'mingguan' || $periode === 'bulanan')
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tahun</label>
            <input type="number" name="tahun" value="{{ $tahun }}" min="2000" max="2100" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
        </div>
    @endif

    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition flex items-center gap-2">
        <span class="material-symbols-outlined text-lg">filter_alt</span>
        Tampilkan
    </button>

    <details class="relative ml-auto">
        <summary class="cursor-pointer list-none bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition flex items-center gap-2 shadow-lg shadow-emerald-200">
            <span class="material-symbols-outlined text-lg">download</span> Unduh Laporan
            <span class="material-symbols-outlined text-lg">expand_more</span>
        </summary>
        <div class="absolute right-0 z-20 mt-2 w-48 rounded-xl border border-gray-200 bg-white p-1 shadow-xl">
            <a href="{{ route('pengelola.laporan.excel', array_filter(['periode' => $periode, 'bulan' => $bulan, 'tahun' => $tahun])) }}" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-emerald-50">Unduh Excel (.xlsx)</a>
            <a href="{{ route('pengelola.laporan.pdf', array_filter(['periode' => $periode, 'bulan' => $bulan, 'tahun' => $tahun])) }}" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-emerald-50">Unduh PDF</a>
        </div>
    </details>
</form>

<!-- Tab Navigation -->
<div class="flex gap-2 mb-6">
    <button type="button" id="btn-tab-ringkasan" onclick="switchTab('ringkasan')"
            class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition bg-emerald-600 text-white border-emerald-600 shadow-sm flex items-center gap-2">
        <span class="material-symbols-outlined text-lg">summarize</span> Ringkasan
    </button>
    <button type="button" id="btn-tab-riwayat" onclick="switchTab('riwayat')"
            class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition bg-white text-gray-600 border-gray-200 hover:bg-gray-50 flex items-center gap-2">
        <span class="material-symbols-outlined text-lg">history</span> Riwayat Pengunjung
    </button>
</div>

<div id="tab-ringkasan">
<!-- Ringkasan -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-blue-500">shopping_cart</span>
            <span class="text-xs font-semibold text-gray-500 uppercase">Total Booking</span>
        </div>
        <div class="text-3xl font-bold text-gray-800">{{ number_format($summary['booking'], 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-amber-500">groups</span>
            <span class="text-xs font-semibold text-gray-500 uppercase">Total Pengunjung</span>
        </div>
        <div class="text-3xl font-bold text-gray-800">{{ number_format($summary['pengunjung'], 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-emerald-500">payments</span>
            <span class="text-xs font-semibold text-gray-500 uppercase">Total Pendapatan</span>
        </div>
        <div class="text-2xl font-bold text-emerald-600">{{ $rupiah($summary['pendapatan']) }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-purple-500">check_circle</span>
            <span class="text-xs font-semibold text-gray-500 uppercase">Kunjungan Selesai</span>
        </div>
        <div class="text-3xl font-bold text-purple-600">{{ number_format($summary['selesai'], 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-pink-500">comment</span>
            <span class="text-xs font-semibold text-gray-500 uppercase">Total Ulasan</span>
        </div>
        <div class="text-3xl font-bold text-pink-600">{{ number_format($summary['ulasan'], 0, ',', '.') }}</div>
    </div>
</div>

<!-- Grafik -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-emerald-500">bar_chart</span>
        Grafik Pengunjung per {{ $periodeList[$periode] }}
    </h3>
    <div style="height: 280px;">
        <canvas id="laporanChart"></canvas>
    </div>
</div>

<!-- Tabel Rincian -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-bold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-500">table_chart</span>
            Rincian {{ $periodeList[$periode] }}
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-right">Booking</th>
                    <th class="px-4 py-3 text-right">Pengunjung</th>
                    <th class="px-4 py-3 text-right">Pendapatan</th>
                    <th class="px-4 py-3 text-right">Kunjungan Selesai</th>
                    <th class="px-4 py-3 text-right">Ulasan</th>
                    <th class="px-4 py-3 text-center">Unduh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $row['label'] }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row['booking'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row['pengunjung'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-medium text-emerald-600">{{ $rupiah($row['pendapatan']) }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row['selesai'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row['ulasan'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center">
                        <details class="relative inline-block text-left">
                            <summary class="cursor-pointer list-none text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-medium px-2 py-1 rounded border border-emerald-200 transition">Unduh ▾</summary>
                            <div class="absolute right-0 z-10 mt-1 w-36 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                                <a class="block rounded px-2 py-1.5 text-left hover:bg-emerald-50" href="{{ route('pengelola.laporan.excel', ['start' => $row['start_date'], 'end' => $row['end_date'], 'periode' => $periode, 'bulan' => $bulan, 'tahun' => $tahun]) }}">Excel (.xlsx)</a>
                                <a class="block rounded px-2 py-1.5 text-left hover:bg-emerald-50" href="{{ route('pengelola.laporan.pdf', ['start' => $row['start_date'], 'end' => $row['end_date'], 'periode' => $periode, 'bulan' => $bulan, 'tahun' => $tahun]) }}">PDF</a>
                            </div>
                        </details>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-400">Belum ada data untuk periode ini.</td>
                </tr>
                @endforelse
            </tbody>
            @if(count($rows) > 0)
            <tfoot>
                <tr class="bg-gray-50 font-bold text-gray-800">
                    <td class="px-4 py-3">Total</td>
                    <td class="px-4 py-3 text-right">{{ number_format($summary['booking'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($summary['pengunjung'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-emerald-600">{{ $rupiah($summary['pendapatan']) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($summary['selesai'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($summary['ulasan'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
</div>

<div id="tab-riwayat" class="hidden">
<!-- Riwayat Pengunjung -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mt-6">
    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="font-bold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-pink-500">history</span>
            Riwayat Pengunjung
        </h3>
        <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">{{ $detailBookings->total() }} data</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Nama Pengunjung</th>
                    <th class="px-4 py-3 text-left">Kode Booking</th>
                    <th class="px-4 py-3 text-left">Tanggal Kunjungan</th>
                    <th class="px-4 py-3 text-right">Jumlah</th>
                    <th class="px-4 py-3 text-right">Total Bayar</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Bukti Transfer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detailBookings as $index => $booking)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-800">{{ $booking->nama_lengkap }}</div>
                        <div class="text-[11px] text-gray-500">{{ $booking->email }}</div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-emerald-700">{{ $booking->booking_code }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($booking->tanggal_kunjungan)->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ $booking->jumlah_pengunjung }} org</td>
                    <td class="px-4 py-3 text-right font-medium text-emerald-600">{{ $rupiah($booking->total_bayar) }}</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $statusMap = [
                                'pending'          => ['Menunggu', 'bg-amber-100 text-amber-700'],
                                'paid'             => ['Lunas', 'bg-green-100 text-green-700'],
                                'verified'         => ['Terverifikasi', 'bg-green-100 text-green-700'],
                                'cancelled'        => ['Dibatalkan', 'bg-red-100 text-red-700'],
                                'refund_requested' => ['Refund Diajukan', 'bg-orange-100 text-orange-700'],
                                'refunded'         => ['Refund Selesai', 'bg-gray-100 text-gray-700'],
                            ];
                            [$statusText, $statusClass] = $statusMap[$booking->status] ?? [ucfirst($booking->status), 'bg-gray-100 text-gray-700'];
                        @endphp
                        <span class="px-2 py-1 text-[11px] font-bold rounded-full {{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($booking->bukti_pembayaran)
                            @php
                                $paths = [
                                    'storage/' . $booking->bukti_pembayaran,
                                    $booking->bukti_pembayaran,
                                    'bukti_pembayaran/' . basename($booking->bukti_pembayaran),
                                ];
                                $foundPath = null;
                                foreach ($paths as $path) {
                                    if (file_exists(public_path($path))) {
                                        $foundPath = asset($path);
                                        break;
                                    }
                                }
                            @endphp
                            @if($foundPath)
                                <a href="{{ $foundPath }}" target="_blank" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-600 font-medium px-2 py-1 rounded border border-blue-200 transition">Lihat</a>
                            @else
                                <span class="text-xs text-red-500">File hilang</span>
                            @endif
                        @else
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-400">Belum ada riwayat pengunjung untuk periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($detailBookings->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $detailBookings->links() }}
    </div>
    @endif
</div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('tab-ringkasan').classList.toggle('hidden', tab !== 'ringkasan');
    document.getElementById('tab-riwayat').classList.toggle('hidden', tab !== 'riwayat');

    const btnRingkasan = document.getElementById('btn-tab-ringkasan');
    const btnRiwayat = document.getElementById('btn-tab-riwayat');
    const active = ['bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-sm'];
    const inactive = ['bg-white', 'text-gray-600', 'border-gray-200', 'hover:bg-gray-50'];

    if (tab === 'ringkasan') {
        btnRingkasan.classList.remove(...inactive); btnRingkasan.classList.add(...active);
        btnRiwayat.classList.add(...inactive); btnRiwayat.classList.remove(...active);
    } else {
        btnRiwayat.classList.remove(...inactive); btnRiwayat.classList.add(...active);
        btnRingkasan.classList.add(...inactive); btnRingkasan.classList.remove(...active);
    }
}

const chartLabels = @json($chartLabels);
const chartData = @json($chartData);

new Chart(document.getElementById('laporanChart'), {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Pengunjung',
            data: chartData,
            backgroundColor: '#10b981',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

if (new URLSearchParams(window.location.search).has('page')) {
    switchTab('riwayat');
}
</script>
@endsection
