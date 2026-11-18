@extends('layouts.pengunjung')
@section('title', 'Dashboard')

@section('content')
@php
    $heroImage = \App\Models\InfoContent::get('hero_image', '');
    $photoSpots = json_decode(\App\Models\InfoContent::get('photo_spots', '[]'), true) ?: [];
    $moodRecommendations = json_decode(\App\Models\InfoContent::get('mood_recommendations', '[]'), true) ?: [];
    
    if (empty($moodRecommendations)) {
        $moodRecommendations = [
            ['title' => 'Santai', 'subtitle' => 'Tenang & Sejuk', 'icon' => 'park', 'color' => 'bg-emerald-50'],
            ['title' => 'Petualangan', 'subtitle' => 'Trekking & Sunrise', 'icon' => 'hiking', 'color' => 'bg-orange-50'],
            ['title' => 'Romantis', 'subtitle' => 'Spot Foto Pasangan', 'icon' => 'favorite', 'color' => 'bg-pink-50'],
            ['title' => 'Keluarga', 'subtitle' => 'Aman untuk Anak', 'icon' => 'family_restroom', 'color' => 'bg-blue-50'],
        ];
    }

    // FALLBACK BIAR GAK ERROR UNDEFINED VARIABLE
    $daftarReviews = isset($daftarReviews) ? $daftarReviews : collect();
    $totalReviews = isset($totalReviews) ? $totalReviews : $daftarReviews->count();
    $averageRating = isset($averageRating) ? $averageRating : ($totalReviews > 0 ? $daftarReviews->avg('rating') : 0);
    $destinasiId = isset($destinasi) ? $destinasi->id : 1;
@endphp

<section class="w-full">
    <!-- Hero dengan Gambar Dinamis -->
    <div class="relative h-80 md:h-96 overflow-hidden rounded-b-3xl">
        @if(!empty($heroImage) && $heroImage !== '/storage/hero/default.jpg')
            <img src="{{ $heroImage }}" class="w-full h-full object-cover" alt="Kawah Putih">
        @else
            <img src="https://images.unsplash.com/photo-1596401057633-54a8fe8ef3b0?q=80&w=1600" class="w-full h-full object-cover" alt="Kawah Putih">
        @endif
        
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
        <div class="absolute bottom-0 left-0 p-6 md:p-10 text-white max-w-3xl">
            <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold mb-3 border border-white/30">
                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                Buka 07:00 - 17:00 WIB
            </div>
            <h1 class="text-3xl md:text-5xl font-bold mb-2 leading-tight">Selamat Datang di Kawah Putih</h1>
            <p class="text-sm md:text-base text-white/90 mb-4">Jelajahi keindahan kawah vulkanik belerang tosca alami.</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('pengunjung.wisata') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">confirmation_number</span>
                    Pesan Tiket
                </a>
                <a href="{{ route('pengunjung.info') }}" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 border border-white/30">
                    <span class="material-symbols-outlined text-lg">info</span>
                    Detail & Info
                </a>
            </div>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 -mt-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4 md:p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-amber-500 text-xl">wb_sunny</span>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cuaca</span>
                </div>
                <div class="text-2xl md:text-3xl font-bold text-gray-800" id="live-suhu">{{ $cuaca['suhu'] }}</div>
                <div class="text-sm text-gray-600 mt-1" id="live-cuaca">{{ $cuaca['kondisi'] }}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4 md:p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-emerald-500 text-xl">groups</span>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Kepadatan</span>
                </div>
                <div class="text-2xl md:text-3xl font-bold text-gray-800" id="live-kepadatan">Sedang</div>
                <div class="text-sm text-gray-600 mt-1" id="live-kuota">0 / 1200</div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4 md:p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-blue-500 text-xl">payments</span>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tarif</span>
                </div>
                <div class="text-lg md:text-xl font-bold text-gray-800">Rp 31rb <span class="text-xs font-normal text-gray-500">/ WNI</span></div>
                <div class="text-sm text-gray-600 mt-1">Rp 90rb <span class="text-xs font-normal text-gray-500">/ WNA</span></div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4 md:p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-purple-500 text-xl">attractions</span>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Wahana</span>
                </div>
                <div class="text-2xl md:text-3xl font-bold text-emerald-600">100%</div>
                <div class="text-sm text-gray-600 mt-1">Aktif</div>
            </div>
        </div>

        <!-- Status Gerbang Banner -->
        <div id="live-gerbang-banner" class="mt-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-600 text-2xl">door_front</span>
                <div>
                    <div class="text-sm font-semibold text-emerald-900">Status Gerbang</div>
                    <div class="text-xs text-emerald-700" id="live-gerbang">{{ $setting->status_gerbang ?? 'Buka Normal' }}</div>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-2 text-xs text-emerald-700">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                Live Update
            </div>
        </div>

        <!-- Rekomendasi Berdasarkan Mood -->
        <div class="mt-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">psychology</span>
                Rekomendasi Berdasarkan Mood
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($moodRecommendations as $mood)
                <div class="{{ $mood['color'] ?? 'bg-white' }} border border-gray-200 rounded-xl p-4 hover:shadow-md transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-3xl text-gray-700 mb-2">{{ $mood['icon'] }}</span>
                    <div class="font-bold text-gray-800 text-sm">{{ $mood['title'] }}</div>
                    <div class="text-xs text-gray-500">{{ $mood['subtitle'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Spot Foto Favorit -->
        <div class="mt-8 mb-10">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">photo_camera</span>
                Spot Foto Favorit
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @forelse($photoSpots as $spot)
                <div class="relative rounded-xl overflow-hidden aspect-square group cursor-pointer">
                    <img src="{{ $spot['image'] }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                    <div class="absolute bottom-3 left-3 text-white">
                        <div class="font-bold text-sm">{{ $spot['name'] }}</div>
                        <div class="text-xs text-white/80">{{ $spot['count'] }} Foto</div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                    <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">photo_library</span>
                    <p class="text-gray-500 text-sm">Belum ada spot foto yang ditambahkan oleh admin.</p>
                </div>
                @endforelse
            </div>
        </div>

                <!-- ============================================ -->
        <!-- RATING & KOMENTAR -->
        <!-- ============================================ -->
        <div class="mt-8 mb-10">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Rating & Komentar</h2>

            <!-- 1. RATA-RATA RATING (TERLIHAT OLEH SEMUA ORANG / PUBLIC) -->
            @if($totalReviews > 0)
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="text-4xl font-bold text-yellow-600">
                        {{ number_format($averageRating, 1) }}
                    </div>
                    <div>
                        <div class="text-yellow-500 text-xl">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= round($averageRating))
                                    ★
                                @else
                                    ☆
                                @endif
                            @endfor
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Berdasarkan {{ $totalReviews }} ulasan</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- ALERT SUKSES/ERROR -->
            @if(session('success'))
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- 2. FORM INPUT (HANYA MUNCUL JIKA SUDAH LOGIN) -->
            @auth
                @if($hasReviewed)
                    <!-- KONDISI A: Sudah pernah review -->
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 mb-6 text-center">
                        <span class="material-symbols-outlined text-emerald-600 text-4xl mb-2">check_circle</span>
                        <h3 class="text-emerald-800 font-bold text-lg">Terima Kasih!</h3>
                        <p class="text-emerald-700 text-sm mt-1">Anda sudah memberikan ulasan untuk kunjungan ini.</p>
                    </div>

                @elseif(!$canReview)
                    <!-- KONDISI B: Sudah login, tapi belum punya tiket verified -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6 text-center">
                        <span class="material-symbols-outlined text-amber-600 text-4xl mb-2">info</span>
                        <h3 class="text-amber-800 font-bold text-lg">Belum Bisa Memberikan Ulasan</h3>
                        <p class="text-amber-700 text-sm mt-1">
                            Fitur ulasan hanya tersedia untuk pengunjung yang telah memiliki tiket valid (Verified) 
                            atau telah menyelesaikan kunjungan. 
                            Silakan pesan tiket terlebih dahulu di halaman <a href="{{ route('pengunjung.wisata') }}" class="underline font-bold">Wisata</a>.
                        </p>
                    </div>

                @else
                    <!-- KONDISI C: Sudah login DAN punya tiket verified (TAMPILKAN FORM) -->
                    <form action="{{ route('reviews.store') }}" method="POST" id="formReview" class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
                        @csrf
                        <input type="hidden" name="destinasi_id" value="{{ $destinasiId }}">

                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rating <span class="text-red-500">*</span></label>
                            <div class="flex gap-1 text-2xl text-gray-300 cursor-pointer select-none" id="bintangContainer">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="star hover:text-yellow-400 transition" data-value="{{ $i }}" onclick="pilihRating({{ $i }})">★</span>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" id="inputRating" value="">
                            @error('rating') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Komentar <span class="text-red-500">*</span></label>
                            <textarea name="comment" id="inputKomentar" rows="2" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-1 focus:ring-emerald-500 focus:outline-none resize-none" placeholder="Tulis pengalaman Anda (min. 10 karakter)...">{{ old('comment') }}</textarea>
                            <p id="hitungKarakter" class="text-xs text-gray-500 mt-1">0 / 10 karakter</p>
                            @error('comment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition">
                            Kirim Ulasan
                        </button>
                    </form>
                @endif
            @else
                <!-- 3. PESAN UNTUK PENGUNJUNG PUBLIC (BELUM LOGIN) -->
                <p class="text-sm text-gray-600 mb-6 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">login</span>
                    Silakan <a href="{{ route('pengunjung.login') }}" class="text-emerald-600 font-semibold underline">login</a> untuk memberikan ulasan.
                </p>
            @endauth

            <!-- 4. DAFTAR KOMENTAR (TERLIHAT OLEH SEMUA ORANG / PUBLIC) -->
            <div class="space-y-4">
                @foreach($daftarReviews as $review)
                <div class="border-b border-gray-200 pb-3 last:border-0">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-semibold text-gray-800 text-sm">{{ $review->user->name ?? 'Pengunjung' }}</span>
                        <div class="text-yellow-400 text-sm tracking-wide">
                            @for($i = 1; $i <= 5; $i++)
                                {{ $i <= $review->rating ? '★' : '☆' }}
                            @endfor
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm leading-relaxed">{{ $review->comment }}</p>
                    <p class="text-gray-400 text-xs mt-1">{{ $review->created_at->diffForHumans() }}</p>
                </div>
                @endforeach
            </div>
        </div>
        
        

    </div>
</section>

<script>
async function loadOperasional() {
    try {
        const response = await fetch("{{ route('api.operasional') }}");
        const data = await response.json();
        
        if (data.pengunjung_aktif && data.kuota_pengunjung) {
            const persen = (data.pengunjung_aktif / data.kuota_pengunjung) * 100;
            let kepadatan = 'Sepi';
            if (persen > 70) kepadatan = 'Padat';
            else if (persen > 40) kepadatan = 'Sedang';
            document.getElementById('live-kepadatan').innerText = kepadatan;
            document.getElementById('live-kuota').innerText = data.pengunjung_aktif + ' / ' + data.kuota_pengunjung;
        }
        if (data.status_gerbang) {
            document.getElementById('live-gerbang').innerText = data.status_gerbang;
            const banner = document.getElementById('live-gerbang-banner');
            if (data.status_gerbang === 'Tutup Sementara') {
                banner.className = 'mt-4 bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center justify-between';
                banner.querySelector('.text-emerald-900').className = 'text-sm font-semibold text-red-900';
                banner.querySelector('.text-emerald-700').className = 'text-xs text-red-700';
            } else if (data.status_gerbang === 'Kuota Hampir Penuh') {
                banner.className = 'mt-4 bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center justify-between';
            }
        }
    } catch (error) {
        console.log('Gagal memuat data operasional');
    }
}

loadOperasional();
setInterval(loadOperasional, 10000);

// Validasi Frontend Rating & Komentar
function pilihRating(nilai) {
    document.getElementById('inputRating').value = nilai;
    const bintang = document.querySelectorAll('.star');
    bintang.forEach((b, index) => {
        if (index < nilai) {
            b.classList.remove('text-gray-300');
            b.classList.add('text-yellow-400');
        } else {
            b.classList.remove('text-yellow-400');
            b.classList.add('text-gray-300');
        }
    });
}

const komentar = document.getElementById('inputKomentar');
const hitung = document.getElementById('hitungKarakter');

if (komentar && hitung) {
    komentar.addEventListener('input', function() {
        const panjang = this.value.trim().length;
        hitung.textContent = `${panjang} / 10 karakter minimum`;
        if (panjang >= 10) {
            hitung.classList.remove('text-red-500');
            hitung.classList.add('text-emerald-600');
        } else {
            hitung.classList.remove('text-emerald-600');
            hitung.classList.add('text-red-500');
        }
    });
}

const formReview = document.getElementById('formReview');
if (formReview) {
    formReview.addEventListener('submit', function(e) {
        let errors = [];
        const rating = document.getElementById('inputRating').value;
        const komentarVal = document.getElementById('inputKomentar').value.trim();

        if (!rating || rating < 1 || rating > 5) {
            errors.push('• Rating wajib diisi (pilih 1-5 bintang)');
        }
        if (komentarVal.length < 10) {
            errors.push('• Komentar minimal 10 karakter');
        }

        if (errors.length > 0) {
            e.preventDefault();
            alert('Mohon lengkapi form berikut:\n\n' + errors.join('\n'));
        }
    });
}
</script>
@endsection