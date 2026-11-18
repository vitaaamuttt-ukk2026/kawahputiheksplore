<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        Review::truncate();
        Booking::where('email', 'like', '%@dummy-review.com')->delete();
        User::where('email', 'like', '%@dummy-review.com')->delete();

        $namaLengkap = [
            'Devi Fauziah', 'Rizky Pratama', 'Siti Nurhaliza', 'Budi Santoso',
            'Anisa Rahma', 'Dimas Aditya', 'Putri Wulandari', 'Fajar Nugroho',
            'Dewi Lestari', 'Andi Wijaya', 'Maya Sari', 'Irfan Hakim',
            'Rina Amelia', 'Yoga Permana', 'Lina Marlina', 'Hendra Gunawan',
            'Fitri Handayani', 'Agus Setiawan', 'Nurul Hidayah', 'Bayu Aji',
            'Tina Kusuma', 'Reza Fahlevi', 'Indah Permata', 'Dedi Kurniawan',
            'Wulan Dari', 'Arief Rahman', 'Sari Dewi', 'Joko Susilo',
            'Mega Putri', 'Rudi Hartono'
        ];

        $comments = [
            "Tempatnya sangat indah dan sejuk sekali, recommended banget buat healing!",
            "Pemandangannya luar biasa, air danau nya warna tosca cantik banget.",
            "Agak ramai di weekend, tapi tetap worth it untuk dikunjungi.",
            "Fasilitasnya cukup lengkap, toilet dan warung ada di sekitar lokasi.",
            "Shuttle bus nya membantu banget karena jalanan menuju kawah cukup menanjak.",
            "Bawa jaket tebal ya guys, di bawah sana anginnya lumayan dingin.",
            "Spot foto nya banyak banget, cocok buat konten Instagram atau TikTok.",
            "Harga tiketnya sangat sesuai dengan pemandangan yang didapat.",
            "Parkirannya luas dan tertata, aman buat motor maupun mobil.",
            "Pemesanan tiket online jadi gak perlu antri panjang di loket.",
            "Sungai belerang nya unik, tapi hati-hati ya baunya agak menyengat.",
            "Jembatan gantung nya seru, tapi agak takut buat yang gak suka ketinggian.",
            "Sunrise di sini juara! Wajib banget datang pagi-pagi buta.",
            "Makanan di warung sekitarnya enak-enak dan harganya masih wajar.",
            "Toiletnya bersih, ada yang berbayar tapi sepadan dengan fasilitasnya.",
            "Cocok banget buat liburan bareng keluarga atau pasangan.",
            "Jalanan menuju ke sana agak berkelok, tapi aspalnya mulus kok.",
            "Sempet hujan gerimis, tapi malah makin asik suasananya berkabut.",
            "Staff di loket ramah dan membantu banget arahin jalannya.",
            "Bawa masker ya, karena bau belerang nya lumayan kuat di area kawah.",
            "View nya 10/10, gak bakal bosen liatin danau tosca ini seharian.",
            "Sayang banget masih ada yang buang sampah sembarangan, tolong dijaga ya.",
            "Area pikniknya nyaman, banyak pohon pinus yang rindang buat duduk-duduk.",
            "Bisa sewa baju hangat di sekitar lokasi, harganya murah meriah.",
            "Cocok buat lari pagi, udaranya seger banget masuk ke paru-paru.",
            "Foto di sini gak perlu filter-filter, udah cakep alami dari sononya.",
            "Datang pas weekday sepi banget, bisa foto sepuasnya tanpa antre.",
            "Parkir motor 5rb, mobil 20rb, sangat terjangkau buat kantong pelajar.",
            "Shuttle nya rutin, gak perlu nunggu lama untuk naik ke atas.",
            "Pengalaman yang tak terlupakan, pasti bakal kesini lagi bareng temen-temen!"
        ];

        for ($i = 0; $i < 30; $i++) {
            $uniqueEmail = strtolower(str_replace(' ', '', $namaLengkap[$i])) . rand(100, 999) . '@dummy-review.com';

            $user = User::create([
                'name'     => $namaLengkap[$i],
                'email'    => $uniqueEmail,
                'password' => Hash::make('password123'),
                'role'     => 'pengunjung',
            ]);

            $tanggalKunjungan = now()->subDays(rand(1, 60))->format('Y-m-d');
            $jumlahTiket      = rand(1, 4);
            $hargaPerTiket    = 31000;
            $totalBayar       = $hargaPerTiket * $jumlahTiket;

            Booking::create([
                'user_id'           => $user->id,
                'booking_code'      => 'KP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'nama_lengkap'      => $namaLengkap[$i],
                'email'             => $uniqueEmail,
                'no_hp'             => '08' . rand(100000000, 999999999),
                'jumlah_pengunjung' => $jumlahTiket,
                'tanggal_kunjungan' => $tanggalKunjungan,
                'paket'             => json_encode([['nama' => 'Tiket Masuk Kawah Putih', 'qty' => $jumlahTiket, 'harga' => $hargaPerTiket, 'subtotal' => $totalBayar]], JSON_UNESCAPED_UNICODE),
                'total_bayar'       => $totalBayar,
                'metode_pembayaran' => 'qris',
                'bukti_pembayaran'  => null,
                'status'            => 'verified',
                'status_kunjungan'  => 'selesai',
                'paid_at'           => now()->subDays(rand(1, 60)),
            ]);

            Review::create([
                'user_id'      => $user->id,
                'destinasi_id' => 1,
                'rating'       => rand(3, 5),
                'comment'      => $comments[$i],
                'created_at'   => now()->subDays(rand(1, 60)),
                'updated_at'   => now(),
            ]);
        }
    }
}
