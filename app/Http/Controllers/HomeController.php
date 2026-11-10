<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Wishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('search') || $request->filled('kategori')) {
            return redirect()->route('perpustakaan.index', $request->only(['search', 'kategori']));
        }

        $kategoris = Kategori::orderBy('nama_kategori')->get();
        $totalKoleksi = Buku::count();

        $pilihanBooks = Buku::with('kategori')
            ->withAvg('ratings', 'bintang')
            ->withCount('ratings')
            ->orderByDesc('ratings_avg_bintang')
            ->orderByDesc('views_count')
            ->orderByDesc('stok')
            ->take(6)
            ->get();

        $terbaruBooks = Buku::with('kategori')
            ->withAvg('ratings', 'bintang')
            ->withCount('ratings')
            ->latest()
            ->take(10)
            ->get();

        $trendingBooks = Buku::with('kategori')
            ->withAvg('ratings', 'bintang')
            ->withCount('ratings')
            ->orderByDesc('views_count')
            ->orderByDesc('ratings_count')
            ->latest()
            ->take(10)
            ->get();

        $wishlistIds = auth()->check()
            ? Wishlist::where('user_id', auth()->id())->pluck('buku_id')->all()
            : [];

        $cartIds = array_keys(BukuController::normalizeCart(session('cart_pinjam', [])));

        return view('home', compact(
            'kategoris',
            'totalKoleksi',
            'pilihanBooks',
            'terbaruBooks',
            'trendingBooks',
            'wishlistIds',
            'cartIds'
        ));
    }

    public function switchLocale(string $locale): RedirectResponse
    {
        if (in_array($locale, ['id', 'en'], true)) {
            session()->put('locale', $locale);
        }

        return back();
    }

    public function aturanPeminjaman(): View
    {
        return view('informasi.aturan-peminjaman');
    }

    public function panduanPengembalian(): View
    {
        return view('informasi.panduan-pengembalian');
    }
}
