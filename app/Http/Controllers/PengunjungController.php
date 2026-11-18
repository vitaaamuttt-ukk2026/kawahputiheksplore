<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Paket;
use App\Models\OperasionalSetting;
use App\Models\InfoContent;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use App\Models\Review;

class PengunjungController extends Controller
{
    /**
     * Ambil cuaca otomatis dari Open-Meteo (tanpa API key).
     * Di-cache 30 menit; jika gagal, fallback ke setting manual pengelola.
     */
    private function getCuacaOtomatis(): array
    {
        return Cache::remember('cuaca_kawah_putih', 1800, function () {
            try {
                $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude'  => -7.1247,
                    'longitude' => 107.3986,
                    'current'   => 'temperature_2m,weather_code',
                    'timezone'  => 'Asia/Jakarta',
                ]);

                if (!$response->successful()) {
                    throw new \Exception('API cuaca tidak tersedia');
                }

                $data = $response->json('current', []);

                return [
                    'suhu'    => round($data['temperature_2m'] ?? 28) . '°C',
                    'kondisi' => $this->deskripsiCuaca((int) ($data['weather_code'] ?? 0)),
                ];
            } catch (\Exception $e) {
                $setting = OperasionalSetting::getSetting();

                return [
                    'suhu'    => $setting->suhu ?? '28°C',
                    'kondisi' => $setting->kondisi_cuaca ?? 'Cerah',
                ];
            }
        });
    }

    /**
     * Konversi kode cuaca WMO (Open-Meteo) ke deskripsi Bahasa Indonesia.
     */
    private function deskripsiCuaca(int $kode): string
    {
        return match (true) {
            $kode === 0                  => 'Cerah',
            in_array($kode, [1, 2])      => 'Cerah Berawan',
            $kode === 3                  => 'Berawan',
            in_array($kode, [45, 48])    => 'Berkabut',
            in_array($kode, [51, 53, 55, 56, 57]) => 'Gerimis',
            in_array($kode, [61, 63, 65, 66, 67, 80, 81, 82]) => 'Hujan',
            in_array($kode, [71, 73, 75, 77, 85, 86]) => 'Salju',
            in_array($kode, [95, 96, 99]) => 'Badai Petir',
            default                      => 'Cerah',
        };
    }

    public function dashboard(): View
    {
        $setting = OperasionalSetting::getSetting();
        $cuaca = $this->getCuacaOtomatis();
        
        $heroImage = InfoContent::get('hero_image', '');
        $photoSpots = json_decode(InfoContent::get('photo_spots', '[]'), true) ?: [];
        $moodRecommendations = json_decode(InfoContent::get('mood_recommendations', '[]'), true) ?: [];
        
        if (empty($moodRecommendations)) {
            $moodRecommendations = [
                ['title' => 'Santai', 'subtitle' => 'Tenang & Sejuk', 'icon' => 'park', 'color' => 'bg-emerald-50'],
                ['title' => 'Petualangan', 'subtitle' => 'Trekking & Sunrise', 'icon' => 'hiking', 'color' => 'bg-orange-50'],
                ['title' => 'Romantis', 'subtitle' => 'Spot Foto Pasangan', 'icon' => 'favorite', 'color' => 'bg-pink-50'],
                ['title' => 'Keluarga', 'subtitle' => 'Aman untuk Anak', 'icon' => 'family_restroom', 'color' => 'bg-blue-50'],
            ];
        }
        
        // 1. AMBIL REVIEW: Urutkan rating tertinggi (desc), lalu terbaru
        $daftarReviews = Review::with('user')
            ->orderBy('rating', 'desc')
            ->latest()
            ->get();

        $hasReviewed = false;
        $canReview = false;

        if (Auth::check()) {
            $userId = Auth::id();
            
            $hasReviewed = Review::where('user_id', $userId)->exists();

            $validBooking = Booking::where('user_id', $userId)
                        ->where(function ($query) {
                            $query->where('status_kunjungan', 'selesai')
                                  ->orWhere('status', 'verified');
                        })
                        ->exists();

            // User HANYA boleh review jika: Belum pernah review DAN tiket verified / kunjungan sudah selesai
            if (!$hasReviewed && $validBooking) {
                $canReview = true;
            }
        }

        $totalReviews = $daftarReviews->count();
        $averageRating = $totalReviews > 0 ? $daftarReviews->avg('rating') : 0;
        $destinasiId = 1; 

        return view('pengunjung.dashboard', compact(
            'setting', 'heroImage', 'moodRecommendations', 'photoSpots',
            'daftarReviews', 'totalReviews', 'averageRating', 'destinasiId',
            'hasReviewed', 'canReview', 'cuaca'
        ));
    }

    public function info(): View
    {
        $info = [
            'sejarah' => InfoContent::get('sejarah', 'Kawah Putih adalah tempat wisata...'),
            'lokasi' => InfoContent::get('lokasi', 'Jl. Raya Soreang-Ciwidey...'),
            'jam_operasional' => InfoContent::get('jam_operasional', '07:00 - 17:00'),
            'fasilitas' => InfoContent::get('fasilitas', 'Toilet umum, Mushola, Warung makan & restoran, Area parkir luas, Angkutan Ontang-Anting, Guide Lokal, Shelter / tempat Berteduh, Dermaga Ponton, Sunan Ibu, Spot foto instagramable, Tim medis'),
            'tiket_domestik' => InfoContent::get('tiket_domestik', '30000'),
            'tiket_mancanegara' => InfoContent::get('tiket_mancanegara', '80000'),
            'shuttle_price' => InfoContent::get('shuttle_price', '25000'),
            'parkir_motor' => InfoContent::get('parkir_motor', '5000'),
            'parkir_mobil' => InfoContent::get('parkir_mobil', '20000'),
            'tips_1' => InfoContent::get('tips_1', 'Bawa jaket.'),
            'tips_2' => InfoContent::get('tips_2', 'Gunakan masker.'),
            'tips_3' => InfoContent::get('tips_3', 'Siapkan uang tunai.'),
            'tips_4' => InfoContent::get('tips_4', 'Sepatu nyaman.'),
            'hero_image' => InfoContent::get('hero_image', ''),
            'photo_spots' => json_decode(InfoContent::get('photo_spots', '[]'), true) ?: [],
            'hotels' => json_decode(InfoContent::get('hotels', '[]'), true) ?: [],
            'gallery' => json_decode(InfoContent::get('gallery', '[]'), true) ?: [],
        ];
        
        return view('pengunjung.info', compact('info'));
    }

    public function detail(): View
    {
        return view('pengunjung.detail');
    }

    public function wisata(): View
    {
        $pakets = Paket::where('is_active', true)
                        ->orderBy('kategori')
                        ->orderBy('urutan')
                        ->get();
        $groupedPakets = $pakets->groupBy('kategori');
        $averageRating = Review::avg('rating') ?: 0;
        $totalReviews = Review::count();
        $monthlyVisitors = Booking::where('status', 'verified')
            ->whereMonth('tanggal_kunjungan', now()->month)
            ->whereYear('tanggal_kunjungan', now()->year)
            ->sum('jumlah_pengunjung') ?: 0;
        return view('pengunjung.wisata', compact('groupedPakets', 'averageRating', 'totalReviews', 'monthlyVisitors'));
    }

    public function booking(): View
    {
        return view('pengunjung.booking');
    }

    public function prosesPembayaran(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('pengunjung.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $validated = $request->validate([
            'nama_pengunjung'   => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'no_hp'             => 'required|string|max:20',
            'tanggal_kunjungan' => 'required|date',
            'jumlah_tiket'      => 'required|integer|min:1',
            'metode_pembayaran' => 'required|in:qris,va,transfer',
            'cart_data'         => 'required|string',
            'bukti_pembayaran'  => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $cartItems = json_decode($validated['cart_data'], true);

        if (empty($cartItems) || !is_array($cartItems)) {
            return back()->with('error', 'Data keranjang tidak valid.');
        }

        $totalBayar = 0;
        $namaPaketArray = [];
        $detailPaketArray = [];
        
        foreach ($cartItems as $item) {
            $subtotal = ($item['price'] ?? 0) * ($item['qty'] ?? 0);
            $totalBayar += $subtotal;
            $namaPaketArray[] = $item['name'] . ' (x' . $item['qty'] . ')';
            $detailPaketArray[] = [
                'nama' => $item['name'],
                'qty' => $item['qty'],
                'harga' => $item['price'],
                'subtotal' => $subtotal
            ];
        }

        $buktiPath = null;
        if ($request->hasFile('bukti_pembayaran')) {
            $buktiPath = $request->file('bukti_pembayaran')->store('bukti_pembayaran', 'public');
        }

        $bookingCode = 'KP-' . date('Ymd') . '-' . strtoupper(substr(md5(time() . rand()), 0, 4));

        Booking::create([
            'user_id'           => Auth::id(),
            'booking_code'      => $bookingCode,
            'nama_lengkap'      => $validated['nama_pengunjung'],
            'email'             => $validated['email'],
            'no_hp'             => $validated['no_hp'],
            'jumlah_pengunjung' => $validated['jumlah_tiket'],
            'tanggal_kunjungan' => $validated['tanggal_kunjungan'],
            'paket'             => json_encode($detailPaketArray, JSON_UNESCAPED_UNICODE),
            'total_bayar'       => $totalBayar,
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'bukti_pembayaran'  => $buktiPath,
            'status'            => 'pending',
            'paid_at'           => null,
        ]);

        return redirect()->route('pengunjung.tiket')->with('success', 'Pesanan berhasil diajukan! Kode: ' . $bookingCode);
    }

    public function tiket()
    {
        if (!Auth::check()) {
            return redirect()->route('pengunjung.login')->with('error', 'Silakan login.');
        }

        $userId = Auth::id();
        $bookings = Booking::where('user_id', $userId)->latest()->get();
        
        return view('pengunjung.tiket', compact('bookings'));
    }

    public function checkStatus(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([]);
        }

        $bookings = Booking::where('user_id', Auth::id())
            ->select('id', 'status', 'status_kunjungan')
            ->get()
            ->pluck('status', 'id')
            ->toArray();

        return response()->json($bookings);
    }

    public function requestRefund(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

    if ($booking->user_id !== Auth::id()) {
        return back()->with('error', 'Anda tidak memiliki akses ke tiket ini.');
    }

    $booking->update([
        'status' => 'refund_requested',
        'refund_reason' => $request->refund_reason,
    ]);

    return back()->with('success', 'Permintaan refund berhasil diajukan. Menunggu persetujuan admin.');
    }
}