<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $hasValidTicket = Booking::where('user_id', Auth::id())
                            ->where(function ($query) {
                                $query->where('status_kunjungan', 'selesai')
                                      ->orWhere('status', 'verified');
                            })
                            ->exists();

        if (!$hasValidTicket) {
            return back()->with('error', 'Yuk selesaikan kunjungan terlebih dahulu sebelum memberikan ulasan.');
        }

        
        $alreadyReviewed = Review::where('user_id', Auth::id())->exists();

        if ($alreadyReviewed) {
            return back()->with('error', 'Anda hanya dapat memberikan 1 ulasan.');
        }

        
        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10',
        ], [
            'rating.required'  => 'Rating wajib diisi',
            'rating.min'       => 'Rating minimal 1',
            'rating.max'       => 'Rating maksimal 5',
            'comment.required' => 'Komentar wajib diisi',
            'comment.min'      => 'Komentar minimal 10 karakter',
        ]);

        
        Review::create([
            'user_id'      => Auth::id(),
            'destinasi_id' => $request->destinasi_id ?? 1,
            'rating'       => $validated['rating'],
            'comment'      => $validated['comment'],
        ]);

        return back()->with('success', 'Ulasan berhasil dikirim dan disimpan!');
    }
}