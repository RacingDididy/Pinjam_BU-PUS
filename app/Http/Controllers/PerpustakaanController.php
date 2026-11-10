<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Wishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PerpustakaanController extends Controller
{
    public function index(Request $request): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        $totalKoleksi = Buku::count();

        $query = Buku::with('kategori')
            ->withAvg('ratings', 'bintang')
            ->withCount('ratings');

        $activeKategori = null;
        if ($request->filled('kategori')) {
            $kategoriId = (int) $request->input('kategori');
            $query->where('kategori_id', $kategoriId);
            $activeKategori = $kategoris->firstWhere('id', $kategoriId);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%'.$search.'%')
                    ->orWhere('pengarang', 'like', '%'.$search.'%')
                    ->orWhere('penerbit', 'like', '%'.$search.'%')
                    ->orWhere('isbn', 'like', '%'.$search.'%');
            });
        }

        $sort = $request->string('sort', 'terbaru')->toString();
        if (! in_array($sort, ['terbaru', 'populer', 'rating', 'tersedia'], true)) {
            $sort = 'terbaru';
        }

        if ($sort === 'populer') {
            $query->orderByDesc('views_count')->orderByDesc('ratings_count')->latest();
        } elseif ($sort === 'rating') {
            $query->orderByDesc('ratings_avg_bintang')->orderByDesc('ratings_count')->latest();
        } elseif ($sort === 'tersedia') {
            $query->where('stok', '>', 0)->orderByDesc('stok')->latest();
        } else {
            $query->latest();
        }

        $bukus = $query->paginate(20)->withQueryString();

        $wishlistIds = auth()->check()
            ? Wishlist::where('user_id', auth()->id())->pluck('buku_id')->all()
            : [];

        $cartIds = array_keys(BukuController::normalizeCart(session('cart_pinjam', [])));

        return view('perpustakaan', compact(
            'bukus',
            'kategoris',
            'totalKoleksi',
            'activeKategori',
            'sort',
            'wishlistIds',
            'cartIds'
        ));
    }
}
