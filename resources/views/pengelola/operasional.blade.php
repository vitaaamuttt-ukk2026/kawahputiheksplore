@extends('layouts.panel')
@section('title', 'Operasional')
@section('page-title', 'Operasional Lapangan')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success'))
<script>
    Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', timer: 2500, showConfirmButton: false, toast: true, position: 'top-end' });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({ icon: 'error', title: 'Gagal!', text: '{{ session('error') }}', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
</script>
@endif

<form method="POST" action="{{ route('pengelola.operasional.update') }}" class="space-y-6">
    @csrf

    <div class="grid grid-cols-1 gap-6">
        <!-- Kuota Pengunjung -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-500">groups</span>
                    Kuota Pengunjung
                </h3>
                <span class="bg-gray-800 text-white text-xs px-2 py-1 rounded-full">
                    {{ $setting->pengunjung_aktif ?? 0 }} / {{ $setting->kuota_maksimal ?? 1200 }}
                </span>
            </div>

            <div class="mb-4">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Batas Wilayah Suaka</span>
                    <span class="font-semibold">{{ number_format((($setting->pengunjung_aktif ?? 0) / ($setting->kuota_maksimal ?? 1200)) * 100, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ min(100, (($setting->pengunjung_aktif ?? 0) / ($setting->kuota_maksimal ?? 1200)) * 100) }}%"></div>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kuota Maksimal</label>
                    <input type="number" name="kuota_maksimal" value="{{ $setting->kuota_maksimal ?? 1200 }}" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pengunjung Aktif Saat Ini</label>
                    <input type="number" name="pengunjung_aktif" value="{{ $setting->pengunjung_aktif ?? 0 }}" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Gerbang</label>
                    <select name="status_gerbang" class="w-full border border-gray-300 rounded-lg p-2.5">
                        <option value="Buka Normal" {{ ($setting->status_gerbang ?? '') == 'Buka Normal' ? 'selected' : '' }}>Buka Normal</option>
                        <option value="Kuota Hampir Penuh" {{ ($setting->status_gerbang ?? '') == 'Kuota Hampir Penuh' ? 'selected' : '' }}>Kuota Hampir Penuh</option>
                        <option value="Tutup Sementara" {{ ($setting->status_gerbang ?? '') == 'Tutup Sementara' ? 'selected' : '' }}>Tutup Sementara</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Kunjungan -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-500">hiking</span>
                Update Kunjungan (Pembaharuan Arus Pengunjung Langsung)
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full">Siaran Masuk Langsung</span>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Pintu Masuk & Dermaga -->
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-500">anchor</span>
                        Pintu Masuk & Dermaga
                    </h4>
                    <span class="text-xs text-gray-500">Pembaruan Live</span>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Dermaga</label>
                        <select name="status_dermaga" class="w-full border border-gray-300 rounded-lg p-2.5">
                            <option value="Aktif - Selesai Boarding" {{ ($setting->status_dermaga ?? '') == 'Aktif - Selesai Boarding' ? 'selected' : '' }}>Aktif - Selesai Boarding</option>
                            <option value="Boarding Berlangsung" {{ ($setting->status_dermaga ?? '') == 'Boarding Berlangsung' ? 'selected' : '' }}>Boarding Berlangsung</option>
                            <option value="Tutup" {{ ($setting->status_dermaga ?? '') == 'Tutup' ? 'selected' : '' }}>Tutup</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Info Dermaga</label>
                        <input type="text" name="info_dermaga" value="{{ $setting->info_dermaga ?? '' }}" class="w-full border border-gray-300 rounded-lg p-2.5" placeholder="Contoh: Kapal Ferry Sentosa (142 penumpang tiba)">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-8 rounded-xl flex items-center gap-2">
            <span class="material-symbols-outlined">campaign</span>
            Perbarui & Siarkan Sekarang
        </button>
    </div>
</form>
@endsection