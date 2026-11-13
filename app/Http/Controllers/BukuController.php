<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\Rating;
use App\Models\User;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BukuController extends Controller
{
    /**
     * @param  array<int|string, int>  $raw
     * @return array<int, int>
     */
    public static function normalizeCart(array $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $normalized = [];

        if (array_is_list($raw)) {
            foreach ($raw as $id) {
                $bookId = (int) $id;
                if ($bookId > 0) {
                    $normalized[$bookId] = ($normalized[$bookId] ?? 0) + 1;
                }
            }

            return $normalized;
        }

        foreach ($raw as $bookId => $qty) {
            $id = (int) $bookId;
            $quantity = (int) $qty;
            if ($id > 0 && $quantity > 0) {
                $normalized[$id] = $quantity;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<int, int>  $cartMap
     * @return array{items: array<int, array<string, mixed>>, cart_map: array<int, int>, total_titles: int, total_qty: int}
     */
    public static function buildCartPayload(array $cartMap): array
    {
        $cartMap = self::normalizeCart($cartMap);

        if (empty($cartMap)) {
            return [
                'items' => [],
                'cart_map' => (object) [],
                'total_titles' => 0,
                'total_qty' => 0,
            ];
        }

        $books = Buku::with('kategori')->whereIn('id', array_keys($cartMap))->get()->keyBy('id');
        $items = [];
        $cleanMap = [];
        $totalQty = 0;

        foreach ($cartMap as $bookId => $qty) {
            $book = $books->get($bookId);
            if (! $book || $book->stok <= 0) {
                continue;
            }

            $validQty = min(max(1, $qty), $book->stok);
            $cleanMap[$book->id] = $validQty;
            $totalQty += $validQty;

            $items[] = [
                'id' => $book->id,
                'judul' => $book->judul,
                'pengarang' => $book->pengarang,
                'kategori' => $book->kategori?->localized_name ?? __('Umum'),
                'isbn' => $book->isbn,
                'cover_url' => $book->cover_url,
                'url' => route('buku.show', $book->id),
                'stok' => $book->stok,
                'qty' => $validQty,
            ];
        }

        session()->put('cart_pinjam', $cleanMap);

        return [
            'items' => $items,
            'cart_map' => empty($cleanMap) ? (object) [] : $cleanMap,
            'total_titles' => count($cleanMap),
            'total_qty' => $totalQty,
        ];
    }

    public function show(Buku $buku): View
    {
        $buku->increment('views_count');

        $buku->load([
            'kategori',
            'ratings' => function ($q) {
                $q->with('user')->latest();
            },
        ])->loadAvg('ratings', 'bintang')->loadCount('ratings');

        $relatedBooks = Buku::with('kategori')
            ->withAvg('ratings', 'bintang')
            ->withCount('ratings')
            ->where('kategori_id', $buku->kategori_id)
            ->where('id', '!=', $buku->id)
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        $cartMap = self::normalizeCart(session()->get('cart_pinjam', []));
        $cartIds = array_keys($cartMap);
        $inCart = array_key_exists($buku->id, $cartMap);

        $isFavorite = auth()->check()
            ? Wishlist::where('user_id', auth()->id())->where('buku_id', $buku->id)->exists()
            : false;

        $hasActiveLoan = auth()->check()
            ? Peminjaman::where('user_id', auth()->id())
                ->where('buku_id', $buku->id)
                ->whereIn('status', ['menunggu_approval', 'sedang_dipinjam', 'terlambat'])
                ->exists()
            : false;

        $wishlistIds = auth()->check()
            ? Wishlist::where('user_id', auth()->id())->pluck('buku_id')->all()
            : [];

        return view('buku.show', compact(
            'buku',
            'relatedBooks',
            'inCart',
            'isFavorite',
            'hasActiveLoan',
            'wishlistIds',
            'cartIds'
        ));
    }

    public function showCheckout(): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->isEmailVerified()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Silakan verifikasi kode 6 digit email Anda terlebih dahulu sebelum meminjam buku.');
        }

        $cartMap = self::normalizeCart(session()->get('cart_pinjam', []));
        $cartPayload = self::buildCartPayload($cartMap);

        return view('buku.checkout', [
            'user' => $user,
            'cartPayload' => $cartPayload,
        ]);
    }

    public function borrowDirect(Request $request, Buku $buku): RedirectResponse|JsonResponse
    {
        if (! auth()->check()) {
            $msg = 'Silakan masuk ke akun Anda terlebih dahulu untuk meminjam buku.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'auth_required' => true,
                    'message' => $msg,
                ], 401);
            }

            return redirect()->route('login')->with('error', $msg);
        }

        /** @var User $user */
        $user = auth()->user();

        if (! $user->isEmailVerified()) {
            $msg = 'Silakan verifikasi kode email Anda terlebih dahulu sebelum meminjam buku.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'redirect_url' => route('verification.notice'),
                    'message' => $msg,
                ], 403);
            }

            return redirect()->route('verification.notice')->with('error', $msg);
        }

        $requestedQty = max(1, (int) $request->input('qty', 1));

        // Arahkan ke halaman profil jika data identitas belum lengkap
        if (! $user->isSchoolIdentityComplete()) {
            $cartMap = self::normalizeCart(session()->get('cart_pinjam', []));
            $cartMap[$buku->id] = min(max(1, $requestedQty), max(1, $buku->stok));
            $payload = self::buildCartPayload($cartMap);

            $msg = 'Silakan lengkapi identitas warga sekolah (NISN 10 digit, NIS maks 5 karakter, Kelas, & Kontak) di Halaman Profil terlebih dahulu.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'identity_required' => true,
                    'redirect_url' => route('profil.index', ['lengkap_identitas' => 1]),
                    'message' => $msg,
                    'cart' => $payload,
                ]);
            }

            return redirect()->route('profil.index', ['lengkap_identitas' => 1])->with('info', $msg);
        }

        $kodeTransaksi = $this->generateTransactionCode();

        $borrowed = DB::transaction(function () use ($buku, $requestedQty, $user, $kodeTransaksi): int {
            $lockedBuku = Buku::where('id', $buku->id)->lockForUpdate()->firstOrFail();

            if ($lockedBuku->stok <= 0) {
                return 0;
            }

            $actualQty = min($requestedQty, $lockedBuku->stok);
            $lockedBuku->decrement('stok', $actualQty);

            Peminjaman::create([
                'kode_transaksi' => $kodeTransaksi,
                'buku_id' => $lockedBuku->id,
                'user_id' => $user->id,
                'nama_peminjam' => $user->name,
                'jumlah' => $actualQty,
                'tanggal_pinjam' => now()->toDateString(),
                'tenggat_kembali' => now()->addDays(7)->toDateString(),
                'status' => 'menunggu_approval',
                'prioritas' => 'normal',
                'keperluan' => 'Peminjaman Langsung',
            ]);

            return $actualQty;
        });

        if ($borrowed <= 0) {
            $msg = 'Maaf, stok buku ini sedang habis dan belum dapat dipinjam.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $cartMap = self::normalizeCart(session()->get('cart_pinjam', []));
        unset($cartMap[$buku->id]);
        $payload = self::buildCartPayload($cartMap);

        $buku->refresh();
        $msg = 'Pengajuan pinjaman #'.$kodeTransaksi.' ('.$borrowed.' buku "'.$buku->judul.'") berhasil dikirim! Pantau progresnya di Pinjaman Saya.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'kode_transaksi' => $kodeTransaksi,
                'new_stok' => $buku->stok,
                'cart' => $payload,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function storeRating(Request $request, Buku $buku): RedirectResponse|JsonResponse
    {
        if (! auth()->check()) {
            $msg = 'Silakan masuk ke akun Anda terlebih dahulu untuk memberikan rating dan komentar.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'auth_required' => true,
                    'message' => $msg,
                ], 401);
            }

            return redirect()->route('login')->with('error', $msg);
        }

        $validated = $request->validate([
            'bintang' => ['required', 'integer', 'min:1', 'max:5'],
            'ulasan' => ['required', 'string', 'max:500'],
        ]);

        $rating = Rating::create([
            'buku_id' => $buku->id,
            'user_id' => auth()->id(),
            'nama_penilai' => auth()->user()->name,
            'bintang' => $validated['bintang'],
            'ulasan' => trim($validated['ulasan']),
        ]);

        if ($request->wantsJson()) {
            /** @var User $authUser */
            $authUser = auth()->user();

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Komentar dan rating berhasil dikirim.',
                'rating' => [
                    'id' => $rating->id,
                    'nama_penilai' => $authUser->name,
                    'bintang' => (int) $rating->bintang,
                    'ulasan' => $rating->ulasan,
                    'waktu' => 'Baru saja',
                    'avatar_url' => $authUser->avatar_url,
                    'initial' => $authUser->initial,
                    'palette' => $authUser->color_palette,
                ],
            ]);
        }

        return back()->with('success', 'Terima kasih! Komentar dan rating berhasil dikirim.');
    }

    public function addToCart(Request $request, Buku $buku): RedirectResponse|JsonResponse
    {
        if (! auth()->check()) {
            $msg = 'Silakan masuk ke akun Anda terlebih dahulu untuk meminjam buku.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'auth_required' => true,
                    'message' => $msg,
                ], 401);
            }

            return redirect()->route('login')->with('error', $msg);
        }

        if ($buku->stok <= 0) {
            $msg = 'Maaf, stok buku ini sedang habis dan belum dapat dipinjam.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $cartMap = self::normalizeCart(session()->get('cart_pinjam', []));
        $currentQty = $cartMap[$buku->id] ?? 0;

        if ($currentQty >= $buku->stok) {
            $payload = self::buildCartPayload($cartMap);
            $msg = 'Jumlah di keranjang sudah mencapai batas stok tersedia ('.$buku->stok.' buku).';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'warning',
                    'message' => $msg,
                    'cart' => $payload,
                ], 422);
            }

            return back()->with('info', $msg);
        }

        $cartMap[$buku->id] = $currentQty + 1;
        $payload = self::buildCartPayload($cartMap);

        $msg = $currentQty > 0
            ? 'Jumlah pinjaman "'.$buku->judul.'" ditambah menjadi '.$cartMap[$buku->id].' buku.'
            : 'Buku "'.$buku->judul.'" ditambahkan ke keranjang pinjaman!';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'cart' => $payload,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function updateCartQuantity(Request $request, Buku $buku): JsonResponse|RedirectResponse
    {
        if (! auth()->check()) {
            return response()->json([
                'success' => false,
                'auth_required' => true,
                'message' => 'Silakan masuk terlebih dahulu.',
            ], 401);
        }

        $action = $request->string('action', 'increment')->toString();
        $cartMap = self::normalizeCart(session()->get('cart_pinjam', []));
        $currentQty = $cartMap[$buku->id] ?? 0;

        if ($action === 'increment') {
            if ($currentQty >= $buku->stok) {
                $payload = self::buildCartPayload($cartMap);

                return response()->json([
                    'success' => false,
                    'status' => 'warning',
                    'message' => 'Maksimal peminjaman sesuai stok tersedia ('.$buku->stok.' buku).',
                    'cart' => $payload,
                ], 422);
            }

            $cartMap[$buku->id] = $currentQty + 1;
            $msg = 'Jumlah "'.$buku->judul.'" menjadi '.$cartMap[$buku->id].' buku.';
        } elseif ($action === 'decrement') {
            if ($currentQty <= 1) {
                unset($cartMap[$buku->id]);
                $msg = 'Buku "'.$buku->judul.'" dihapus dari keranjang pinjaman.';
            } else {
                $cartMap[$buku->id] = $currentQty - 1;
                $msg = 'Jumlah "'.$buku->judul.'" dikurangi menjadi '.$cartMap[$buku->id].' buku.';
            }
        } else {
            $targetQty = max(0, (int) $request->input('qty', 1));
            if ($targetQty === 0) {
                unset($cartMap[$buku->id]);
                $msg = 'Buku dihapus dari keranjang pinjaman.';
            } else {
                $cartMap[$buku->id] = min($targetQty, max(1, $buku->stok));
                $msg = 'Jumlah pinjaman diperbarui.';
            }
        }

        $payload = self::buildCartPayload($cartMap);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'cart' => $payload,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function removeFromCart(Request $request, Buku $buku): RedirectResponse|JsonResponse
    {
        $cartMap = self::normalizeCart(session()->get('cart_pinjam', []));
        unset($cartMap[$buku->id]);
        $payload = self::buildCartPayload($cartMap);

        $msg = 'Buku telah dihapus dari keranjang pinjaman.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'cart' => $payload,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function checkoutCart(Request $request): RedirectResponse|JsonResponse
    {
        if (! auth()->check()) {
            $msg = 'Silakan masuk ke akun Anda terlebih dahulu untuk mengajukan peminjaman.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'auth_required' => true, 'message' => $msg], 401);
            }

            return redirect()->route('login')->with('error', $msg);
        }

        /** @var User $user */
        $user = auth()->user();

        if (! $user->isEmailVerified()) {
            $msg = 'Silakan verifikasi kode email Anda terlebih dahulu.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'redirect_url' => route('verification.notice'),
                    'message' => $msg,
                ], 403);
            }

            return redirect()->route('verification.notice')->with('error', $msg);
        }

        if (! $user->isSchoolIdentityComplete()) {
            $msg = 'Data identitas warga sekolah Anda belum lengkap atau belum sesuai format (NISN 10 digit, NIS maks 5 karakter). Silakan lengkapi atau ubah data di Halaman Profil terlebih dahulu.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'redirect_url' => route('profil.index', ['lengkap_identitas' => 1]),
                    'message' => $msg,
                ], 422);
            }

            return redirect()->route('profil.index', ['lengkap_identitas' => 1])->with('error', $msg);
        }

        $validated = $request->validate([
            'tanggal_pinjam' => ['nullable', 'date'],
            'prioritas' => ['nullable', 'string', Rule::in(['penting', 'normal'])],
            'keperluan' => ['nullable', 'string', 'max:100'],
            'catatan_peminjam' => ['nullable', 'string', 'max:500'],
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer'],
        ]);

        $cartMap = self::normalizeCart(session()->get('cart_pinjam', []));

        if (empty($cartMap)) {
            $msg = 'Keranjang pinjaman Anda masih kosong.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $selectedIds = ! empty($validated['selected_ids'])
            ? array_map('intval', $validated['selected_ids'])
            : array_keys($cartMap);

        $checkoutMap = array_intersect_key($cartMap, array_flip($selectedIds));

        if (empty($checkoutMap)) {
            $msg = 'Pilih minimal 1 buku untuk diajukan peminjamannya.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $tanggalPinjam = ! empty($validated['tanggal_pinjam'])
            ? Carbon::parse($validated['tanggal_pinjam'])->toDateString()
            : now()->toDateString();
        $tenggatKembali = Carbon::parse($tanggalPinjam)->addDays(7)->toDateString();
        $kodeTransaksi = $this->generateTransactionCode();

        $totalBorrowedBooks = 0;

        DB::transaction(function () use (
            $checkoutMap,
            &$cartMap,
            $user,
            $kodeTransaksi,
            $tanggalPinjam,
            $tenggatKembali,
            $validated,
            &$totalBorrowedBooks
        ) {
            $books = Buku::whereIn('id', array_keys($checkoutMap))->lockForUpdate()->get();

            foreach ($books as $book) {
                if ($book->stok <= 0) {
                    continue;
                }

                $requestedQty = max(1, (int) ($checkoutMap[$book->id] ?? 1));
                $actualQty = min($requestedQty, $book->stok);

                $book->decrement('stok', $actualQty);

                Peminjaman::create([
                    'kode_transaksi' => $kodeTransaksi,
                    'buku_id' => $book->id,
                    'user_id' => $user->id,
                    'nama_peminjam' => $user->name,
                    'jumlah' => $actualQty,
                    'tanggal_pinjam' => $tanggalPinjam,
                    'tenggat_kembali' => $tenggatKembali,
                    'status' => 'menunggu_approval',
                    'prioritas' => $validated['prioritas'] ?? 'normal',
                    'keperluan' => $validated['keperluan'] ?? 'Peminjaman Buku Perpustakaan',
                    'catatan_peminjam' => ! empty($validated['catatan_peminjam']) ? trim($validated['catatan_peminjam']) : null,
                ]);

                $totalBorrowedBooks += $actualQty;
                unset($cartMap[$book->id]);
            }
        });

        $remainingPayload = self::buildCartPayload($cartMap);

        if ($totalBorrowedBooks === 0) {
            $msg = 'Stok buku yang dipilih sedang habis.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'cart' => $remainingPayload], 422);
            }

            return back()->with('error', $msg);
        }

        $msg = 'Peminjaman #'.$kodeTransaksi.' ('.$totalBorrowedBooks.' buku) berhasil diajukan! Pantau progres persetujuannya di halaman Pinjaman Saya.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'kode_transaksi' => $kodeTransaksi,
                'cart' => $remainingPayload,
                'redirect_url' => route('profil.index'),
            ]);
        }

        return redirect()->route('profil.index')->with('success', $msg);
    }

    public function toggleFavorite(Request $request, Buku $buku): JsonResponse|RedirectResponse
    {
        if (! auth()->check()) {
            $msg = 'Silakan masuk ke akun Anda terlebih dahulu untuk menyimpan buku ke wishlist.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'auth_required' => true,
                    'message' => $msg,
                ], 401);
            }

            return redirect()->route('login')->with('error', $msg);
        }

        $existing = Wishlist::where('user_id', auth()->id())
            ->where('buku_id', $buku->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $msg = 'Buku dihapus dari daftar wishlist Anda.';
            $nowFav = false;
        } else {
            Wishlist::create([
                'user_id' => auth()->id(),
                'buku_id' => $buku->id,
            ]);
            $msg = 'Buku disimpan ke daftar wishlist Anda!';
            $nowFav = true;
        }

        $count = Wishlist::where('user_id', auth()->id())->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_favorite' => $nowFav,
                'message' => $msg,
                'count' => $count,
            ]);
        }

        return back()->with('success', $msg);
    }

    protected function generateTransactionCode(): string
    {
        $datePrefix = 'TRX-'.now()->format('Ymd').'-';
        $countToday = Peminjaman::where('kode_transaksi', 'like', $datePrefix.'%')
            ->distinct('kode_transaksi')
            ->count('kode_transaksi');

        $seq = str_pad((string) ($countToday + 1), 3, '0', STR_PAD_LEFT);
        $candidate = $datePrefix.$seq;

        if (Peminjaman::where('kode_transaksi', $candidate)->exists()) {
            $candidate = $datePrefix.$seq.strtoupper(Str::random(2));
        }

        return $candidate;
    }
}
