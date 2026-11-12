@extends('layouts.panel')
@section('title', 'Dashboard Pengelola')
@section('page-title', 'Dashboard Pengelola')

@section('content')
<!-- 1. KARTU RINGKASAN HARI INI -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">groups</span>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Total Booking Hari Ini</p>
            <p class="text-xl font-bold text-gray-800">{{ $stats['total_hari_ini'] }} Orang</p>
        </div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">verified</span>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Sudah Terverifikasi</p>
            <p class="text-xl font-bold text-gray-800">{{ $stats['sudah_verifikasi'] }} Orang</p>
        </div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">pending_actions</span>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Perlu Verifikasi</p>
            <p class="text-xl font-bold text-gray-800">{{ $stats['belum_verifikasi'] }} Orang</p>
        </div>
    </div>
</div>

<!-- 2. AREA GRAFIK PENGELOLA -->
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-indigo-600">show_chart</span> Tren Jumlah Pengunjung (7 Hari Terakhir)
    </h3>
    <div class="relative h-80">
        <canvas id="visitorChart"></canvas>
    </div>
</div>

<!-- Script Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = @json($chartLabels);
    const visitorData = @json($chartVisitors);

    const ctxVisitor = document.getElementById('visitorChart').getContext('2d');
    
    // Buat gradien warna agar terlihat lebih premium
    const gradient = ctxVisitor.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.5)'); // Indigo
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    new Chart(ctxVisitor, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Pengunjung',
                data: visitorData,
                backgroundColor: gradient,
                borderColor: '#6366f1',
                borderWidth: 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4 // Membuat garis melengkung halus
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { stepSize: 1 } }, 
                x: { grid: { display: false } } 
            }
        }
    });
</script>
@endsection