@extends('layouts.pengunjung')
@section('title', 'Info Kawah Putih')

@section('content')
<section class="w-full max-w-6xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-3xl p-8 mb-8 border border-emerald-100">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-emerald-600 text-3xl">info</span>
            <h1 class="text-3xl font-bold text-emerald-900">Panduan Lengkap Destinasi</h1>
        </div>
        <p class="text-emerald-700">Kenali lebih dalam keindahan Kawah Putih, dari sejarah, fasilitas, hingga tips perjalanan agar liburan Anda sempurna.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Sejarah -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">history</span>
                Sejarah & Deskripsi
            </h2>
            <div class="prose prose-emerald max-w-none text-gray-600 leading-relaxed">
                {!! nl2br(e($info['sejarah'])) !!}
            </div>
        </div>

        <!-- Gambar Hero (Dinamis dari Admin) -->
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
            @if(!empty($info['hero_image']))
                <img src="{{ $info['hero_image'] }}" alt="Kawah Putih" class="w-full h-64 object-cover rounded-xl">
            @else
                <img src="https://images.unsplash.com/photo-1596401057633-54a8fe8ef3b0?w=600" alt="Kawah Putih" class="w-full h-64 object-cover rounded-xl">
            @endif
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Lokasi -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-emerald-600 text-2xl">location_on</span>
                <h3 class="font-bold text-gray-800">Lokasi</h3>
            </div>
            <p class="text-sm text-gray-600 mb-3 leading-relaxed">{{ $info['lokasi'] }}</p>
            <a href="https://maps.app.goo.gl/Nsc8MD5rVsDFAVkUA" target="_blank" class="inline-flex items-center gap-1 text-sm text-emerald-600 font-semibold hover:underline">
                <span class="material-symbols-outlined text-sm">map</span> Buka di Google Maps
            </a>
        </div>

        <!-- Jam Operasional -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-emerald-600 text-2xl">schedule</span>
                <h3 class="font-bold text-gray-800">Jam Operasional</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-1">
                {!! nl2br(e($info['jam_operasional'])) !!}
            </div>
        </div>

        <!-- Fasilitas -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-emerald-600 text-2xl">local_offer</span>
                <h3 class="font-bold text-gray-800">Fasilitas</h3>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach(explode(',', $info['fasilitas']) as $fasilitas)
                <span class="px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg border border-emerald-100">
                    {{ trim($fasilitas) }}
                </span>
                @endforeach
            </div>
        </div>
        <!-- Kontak -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-emerald-600 text-2xl">contact_phone</span>
                <h3 class="font-bold text-gray-800">Informasi Kontak</h3>
            </div>
            <div class="text-sm text-gray-600 space-y-1">
                <p><strong>Telepon:</strong> (62) 895350248136</p>
                <p><strong>Email:</strong> jo.adventureinfo@gmail.com</p>
                <p><strong>Website:</strong> www.ciwideyoutbound.com</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600">payments</span>
            Harga Tiket & Wahana
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-4 border border-emerald-100">
                <div class="text-sm font-semibold text-emerald-900 mb-1">Tiket Masuk (WNI)</div>
                <div class="text-xs text-emerald-700 mb-2">Domestik</div>
                <div class="text-2xl font-bold text-emerald-600">Rp {{ number_format($info['tiket_domestik'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                <div class="text-sm font-semibold text-blue-900 mb-1">Tiket Masuk (WNA)</div>
                <div class="text-xs text-blue-700 mb-2">Mancanegara</div>
                <div class="text-2xl font-bold text-blue-600">Rp {{ number_format($info['tiket_mancanegara'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border border-purple-100">
                <div class="text-sm font-semibold text-purple-900 mb-1">Shuttle Ontang-anting</div>
                <div class="text-xs text-purple-700 mb-2">Transportasi PP</div>
                <div class="text-2xl font-bold text-purple-600">Rp {{ number_format($info['shuttle_price'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-100">
                <div class="text-sm font-semibold text-amber-900 mb-1">Area Parkir</div>
                <div class="text-xs text-amber-700 mb-2">Motor / Mobil</div>
                <div class="text-lg font-bold text-amber-600">Rp {{ number_format($info['parkir_motor'], 0, ',', '.') }} / Rp {{ number_format($info['parkir_mobil'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Tips -->
    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl border border-amber-200 p-6 mb-8">
        <h2 class="text-xl font-bold text-amber-900 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-600">lightbulb</span>
            Tips & Saran Penting
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @for($i = 1; $i <= 4; $i++)
                @if(!empty($info['tips_' . $i]))
                <div class="flex items-start gap-3 bg-white/70 rounded-xl p-4">
                    <span class="material-symbols-outlined text-amber-600 flex-shrink-0">check_circle</span>
                    <div class="text-sm text-amber-900">{{ $info['tips_' . $i] }}</div>
                </div>
                @endif
            @endfor
        </div>
    </div>

    <!-- Rekomendasi Hotel (BARU) -->
    @if(count($info['hotels']) > 0)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600">hotel</span>
            Rekomendasi Penginapan
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($info['hotels'] as $hotel)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                <img src="{{ $hotel['image'] }}" class="w-full h-40 object-cover">
                <div class="p-4">
                    <h3 class="font-bold text-gray-800">{{ $hotel['name'] }}</h3>
                    <p class="text-emerald-600 font-semibold text-sm mt-1">{{ $hotel['price'] }}</p>
                    @if(!empty($hotel['desc']))
                        <p class="text-gray-500 text-xs mt-2 line-clamp-2">{{ $hotel['desc'] }}</p>
                    @endif
                    @if(!empty($hotel['link']))
                        <a href="{{ $hotel['link'] }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1 mt-3 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">
                            <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                            Lihat Detail / Booking
                        </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Spot Foto Favorit -->
    @if(count($info['photo_spots']) > 0)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600">photo_camera</span>
            Spot Foto Favorit
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($info['photo_spots'] as $spot)
            <div class="relative rounded-xl overflow-hidden aspect-square group cursor-pointer border border-gray-200">
                <img src="{{ $spot['image'] }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex flex-col justify-end p-3">
                    <div class="font-bold text-white text-sm">{{ $spot['name'] }}</div>
                    <div class="text-xs text-white/80">{{ $spot['count'] }} Foto</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Galeri Foto (Album) -->
    @if(count($info['gallery']) > 0)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600">photo_library</span>
            Galeri Foto Kawah Putih
        </h2>
        <div class="relative rounded-2xl overflow-hidden cursor-pointer group max-w-xs" onclick="openAlbum()">
            <img src="{{ $info['gallery'][0] }}" class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-500">
            <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
                <div class="text-white text-center">
                    <span class="material-symbols-outlined text-5xl">photo_library</span>
                    <div class="font-bold text-lg">{{ count($info['gallery']) }} Foto</div>
                    <div class="text-sm">Klik untuk buka album</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Album -->
    <div id="albumModal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeAlbum()">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg">Album Galeri</h3>
                <button onclick="closeAlbum()" class="text-2xl leading-none">&times;</button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($info['gallery'] as $img)
                <img src="{{ $img }}" class="w-full aspect-square object-cover rounded-lg cursor-pointer hover:opacity-80" onclick="viewPhoto(this.src)">
                @endforeach
            </div>
        </div>
    </div>
    <div id="photoModal" class="fixed inset-0 bg-black/90 z-[60] hidden flex items-center justify-center p-4" onclick="closePhoto()">
        <button onclick="event.stopPropagation();prevPhoto()" class="absolute left-4 text-white text-4xl">&lsaquo;</button>
        <img id="photoView" src="" class="max-w-full max-h-full rounded-lg">
        <button onclick="event.stopPropagation();nextPhoto()" class="absolute right-4 text-white text-4xl">&rsaquo;</button>
    </div>
    <script>
        var galleryImages = [@foreach($info['gallery'] as $img)"{{ $img }}",@endforeach];
        var photoIndex = 0;
        function openAlbum(){document.getElementById('albumModal').classList.remove('hidden');document.getElementById('albumModal').classList.add('flex');}
        function closeAlbum(){document.getElementById('albumModal').classList.add('hidden');document.getElementById('albumModal').classList.remove('flex');}
        function viewPhoto(src){photoIndex=galleryImages.indexOf(src);if(photoIndex<0)photoIndex=0;showPhoto();document.getElementById('photoModal').classList.remove('hidden');document.getElementById('photoModal').classList.add('flex');}
        function showPhoto(){document.getElementById('photoView').src=galleryImages[photoIndex];}
        function prevPhoto(){photoIndex=(photoIndex-1+galleryImages.length)%galleryImages.length;showPhoto();}
        function nextPhoto(){photoIndex=(photoIndex+1)%galleryImages.length;showPhoto();}
        function closePhoto(){document.getElementById('photoModal').classList.add('hidden');document.getElementById('photoModal').classList.remove('flex');}
    </script>
    @endif

</section>
@endsection