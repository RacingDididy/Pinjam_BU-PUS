<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Rating;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    public function index(): View
    {
        $pinjamanAktif = collect();
        $riwayatPinjaman = collect();
        $groupedTransactions = collect();
        $ulasanUser = collect();
        $wishlistBooks = collect();
        $wishlistIds = [];
        $cartIds = array_keys(BukuController::normalizeCart(session('cart_pinjam', [])));
        $stats = [
            'aktif' => 0,
            'total_pinjaman' => 0,
            'selesai' => 0,
            'ulasan' => 0,
            'wishlist' => 0,
        ];

        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();

            $riwayatPinjaman = Peminjaman::with(['buku.kategori', 'approver'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            $pinjamanAktif = $riwayatPinjaman->whereIn('status', [
                'menunggu_approval',
                'sedang_dipinjam',
                'terlambat',
            ])->values();

            // Kelompokkan pinjaman berdasarkan kode transaksi
            $groupedTransactions = $riwayatPinjaman->groupBy(function (Peminjaman $loan) {
                return $loan->kode_transaksi ?: ('TRX-'.$loan->id);
            })->map(function ($items, $trxCode) {
                /** @var Peminjaman $first */
                $first = $items->first();
                $totalBuku = (int) $items->sum('jumlah');

                $statuses = $items->pluck('status')->unique()->all();
                if (in_array('terlambat', $statuses, true)) {
                    $groupStatus = 'terlambat';
                } elseif (in_array('sedang_dipinjam', $statuses, true)) {
                    $groupStatus = 'sedang_dipinjam';
                } elseif (in_array('menunggu_approval', $statuses, true)) {
                    $groupStatus = 'menunggu_approval';
                } elseif (in_array('ditolak', $statuses, true) && count($statuses) === 1) {
                    $groupStatus = 'ditolak';
                } else {
                    $groupStatus = 'selesai';
                }

                $step = match ($groupStatus) {
                    'menunggu_approval' => 2,
                    'sedang_dipinjam', 'terlambat' => 3,
                    'selesai' => 4,
                    'ditolak' => 0,
                    default => 2,
                };

                $percent = match ($groupStatus) {
                    'menunggu_approval' => 42,
                    'sedang_dipinjam', 'terlambat' => 75,
                    'selesai' => 100,
                    'ditolak' => 100,
                    default => 42,
                };

                $statusLabel = match ($groupStatus) {
                    'menunggu_approval' => 'Menunggu Persetujuan',
                    'sedang_dipinjam' => 'Sedang Dipinjam',
                    'terlambat' => 'Terlambat Kembali',
                    'selesai' => 'Telah Dikembalikan',
                    'ditolak' => 'Ditolak',
                    default => 'Diajukan',
                };

                return [
                    'kode_transaksi' => $trxCode,
                    'status' => $groupStatus,
                    'status_label' => $statusLabel,
                    'prioritas' => $first->prioritas ?? 'normal',
                    'keperluan' => $first->keperluan,
                    'catatan_peminjam' => $first->catatan_peminjam,
                    'catatan_pustakawan' => $first->catatan_pustakawan,
                    'step' => $step,
                    'percent' => $percent,
                    'progress_pct' => $percent,
                    'total_buku' => $totalBuku,
                    'total_books' => $totalBuku,
                    'total_judul' => $items->count(),
                    'tanggal_pinjam' => $first->tanggal_pinjam?->translatedFormat('d M Y') ?? '-',
                    'tanggal_pengajuan' => $first->tanggal_pinjam?->translatedFormat('d M Y') ?? '-',
                    'tenggat_kembali' => $first->tenggat_kembali?->translatedFormat('d M Y') ?? '-',
                    'tanggal_kembali' => $first->tanggal_kembali?->translatedFormat('d M Y'),
                    'is_active' => in_array($groupStatus, ['menunggu_approval', 'sedang_dipinjam', 'terlambat'], true),
                    'items' => $items->map(fn (Peminjaman $p) => [
                        'id' => $p->id,
                        'buku_id' => $p->buku_id,
                        'judul' => $p->buku->judul ?? 'Buku Terhapus',
                        'pengarang' => $p->buku->pengarang ?? '-',
                        'kategori' => $p->buku?->kategori?->nama_kategori ?? 'Umum',
                        'cover_url' => $p->buku->cover_url ?? 'https://placehold.co/120x170/1e293b/ffffff?text=Buku',
                        'url' => $p->buku ? route('buku.show', $p->buku->id) : '#',
                        'jumlah' => $p->jumlah ?? 1,
                        'status' => $p->status,
                    ])->values()->all(),
                ];
            })->values();

            $ulasanUser = Rating::with('buku')
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            $wishlistRecords = Wishlist::with([
                'buku' => function ($q) {
                    $q->with('kategori')
                        ->withAvg('ratings', 'bintang')
                        ->withCount('ratings');
                },
            ])
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            $wishlistBooks = $wishlistRecords->pluck('buku')->filter()->values();
            $wishlistIds = $wishlistBooks->pluck('id')->all();

            $stats = [
                'aktif' => (int) $pinjamanAktif->sum('jumlah'),
                'total_pinjaman' => (int) $riwayatPinjaman->sum('jumlah'),
                'selesai' => (int) $riwayatPinjaman->where('status', 'selesai')->sum('jumlah'),
                'ulasan' => $ulasanUser->count(),
                'wishlist' => $wishlistBooks->count(),
            ];
        }

        $profileColors = User::PROFILE_COLORS;

        return view('profil.index', compact(
            'pinjamanAktif',
            'riwayatPinjaman',
            'groupedTransactions',
            'ulasanUser',
            'wishlistBooks',
            'wishlistIds',
            'cartIds',
            'stats',
            'profileColors'
        ));
    }

    public function liveLoansStatus(): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['success' => false], 401);
        }

        $loans = Peminjaman::with('buku.kategori')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        $grouped = $loans->groupBy(fn (Peminjaman $l) => $l->kode_transaksi ?: ('TRX-'.$l->id))
            ->map(function ($items, $trxCode) {
                $first = $items->first();
                $statuses = $items->pluck('status')->unique()->all();
                if (in_array('terlambat', $statuses, true)) {
                    $groupStatus = 'terlambat';
                } elseif (in_array('sedang_dipinjam', $statuses, true)) {
                    $groupStatus = 'sedang_dipinjam';
                } elseif (in_array('menunggu_approval', $statuses, true)) {
                    $groupStatus = 'menunggu_approval';
                } elseif (in_array('ditolak', $statuses, true) && count($statuses) === 1) {
                    $groupStatus = 'ditolak';
                } else {
                    $groupStatus = 'selesai';
                }

                $step = match ($groupStatus) {
                    'menunggu_approval' => 2,
                    'sedang_dipinjam', 'terlambat' => 3,
                    'selesai' => 4,
                    'ditolak' => 0,
                    default => 2,
                };

                $percent = match ($groupStatus) {
                    'menunggu_approval' => 42,
                    'sedang_dipinjam', 'terlambat' => 75,
                    'selesai' => 100,
                    'ditolak' => 100,
                    default => 42,
                };

                $totalBuku = (int) $items->sum('jumlah');
                $statusLabel = match ($groupStatus) {
                    'menunggu_approval' => 'Menunggu Persetujuan',
                    'sedang_dipinjam' => 'Sedang Dipinjam',
                    'terlambat' => 'Terlambat Kembali',
                    'selesai' => 'Telah Dikembalikan',
                    'ditolak' => 'Ditolak',
                    default => 'Diajukan',
                };

                return [
                    'kode_transaksi' => $trxCode,
                    'status' => $groupStatus,
                    'status_label' => $statusLabel,
                    'prioritas' => $first->prioritas ?? 'normal',
                    'keperluan' => $first->keperluan,
                    'catatan_peminjam' => $first->catatan_peminjam,
                    'catatan_pustakawan' => $first->catatan_pustakawan,
                    'step' => $step,
                    'percent' => $percent,
                    'progress_pct' => $percent,
                    'total_buku' => $totalBuku,
                    'total_books' => $totalBuku,
                    'total_judul' => $items->count(),
                    'tanggal_pinjam' => $first->tanggal_pinjam?->translatedFormat('d M Y') ?? '-',
                    'tanggal_pengajuan' => $first->tanggal_pinjam?->translatedFormat('d M Y') ?? '-',
                    'tenggat_kembali' => $first->tenggat_kembali?->translatedFormat('d M Y') ?? '-',
                    'tanggal_kembali' => $first->tanggal_kembali?->translatedFormat('d M Y'),
                    'is_active' => in_array($groupStatus, ['menunggu_approval', 'sedang_dipinjam', 'terlambat'], true),
                    'items' => $items->map(fn (Peminjaman $p) => [
                        'id' => $p->id,
                        'buku_id' => $p->buku_id,
                        'judul' => $p->buku->judul ?? 'Buku Terhapus',
                        'pengarang' => $p->buku->pengarang ?? '-',
                        'kategori' => $p->buku?->kategori?->nama_kategori ?? 'Umum',
                        'cover_url' => $p->buku->cover_url ?? '',
                        'url' => $p->buku ? route('buku.show', $p->buku->id) : '#',
                        'jumlah' => $p->jumlah ?? 1,
                        'status' => $p->status,
                    ])->values()->all(),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'transactions' => $grouped,
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:40',
                'regex:/^[a-zA-Z0-9._]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'profile_color' => ['required', 'string', Rule::in(array_keys(User::PROFILE_COLORS))],
            'avatar_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik (.), atau garis bawah (_).',
            'username.unique' => 'Username tersebut sudah digunakan oleh pembaca lain.',
        ]);

        $avatarPath = $user->avatar;

        if ($request->hasFile('avatar_file')) {
            File::ensureDirectoryExists(public_path('avatars'));

            if (! empty($user->avatar) && Str::startsWith($user->avatar, '/avatars/')) {
                $oldFile = public_path(ltrim($user->avatar, '/'));
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            $file = $request->file('avatar_file');
            $filename = 'avatar_'.$user->id.'_'.time().'_'.Str::random(6).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('avatars'), $filename);
            $avatarPath = '/avatars/'.$filename;
        } elseif ($request->boolean('remove_avatar')) {
            if (! empty($user->avatar) && Str::startsWith($user->avatar, '/avatars/')) {
                $oldFile = public_path(ltrim($user->avatar, '/'));
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }
            $avatarPath = null;
        }

        $user->update([
            'username' => Str::lower(trim($validated['username'])),
            'name' => trim($validated['name']),
            'profile_color' => $validated['profile_color'],
            'avatar' => $avatarPath,
        ]);

        Rating::where('user_id', $user->id)->update([
            'nama_penilai' => $user->name,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profil pembaca berhasil diperbarui!',
            ]);
        }

        return redirect()->route('profil.index')->with('success', 'Profil pembaca berhasil diperbarui!');
    }

    public function updateIdentity(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'no_telepon' => ['required', 'string', 'min:8', 'max:25', 'regex:/^[0-9+\-\s]+$/'],
            'no_whatsapp' => ['required', 'string', 'min:8', 'max:25', 'regex:/^[0-9+\-\s]+$/'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'nisn' => [
                'required',
                'string',
                'regex:/^\d{10}$/',
                Rule::unique('users', 'nisn')->ignore($user->id),
            ],
            'nis' => ['required', 'string', 'min:1', 'max:5', 'regex:/^[0-9A-Za-z.\-\/]+$/'],
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'tahun_ajaran' => ['required', 'string', 'max:30'],
            'nama_kelas' => ['required', 'string', 'max:60'],
            'alamat' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'nisn.regex' => 'NISN wajib terdiri dari tepat 10 digit angka (0-9).',
            'nisn.unique' => 'NISN tersebut sudah digunakan oleh akun siswa lain.',
            'nis.max' => 'NIS maksimal terdiri dari 5 karakter sesuai format umum sekolah.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh berisi angka.',
            'no_whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
        ]);

        $user->update([
            'name' => ! empty($validated['name']) ? trim($validated['name']) : $user->name,
            'no_telepon' => trim($validated['no_telepon']),
            'no_whatsapp' => trim($validated['no_whatsapp']),
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'nisn' => trim($validated['nisn']),
            'nis' => trim($validated['nis']),
            'nama_sekolah' => trim($validated['nama_sekolah']),
            'tahun_ajaran' => trim($validated['tahun_ajaran']),
            'nama_kelas' => trim($validated['nama_kelas']),
            'alamat' => trim($validated['alamat']),
        ]);

        $msg = 'Data identitas warga sekolah berhasil disimpan! Anda kini dapat meminjam buku.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'is_complete' => $user->isSchoolIdentityComplete(),
                'completion' => $user->identity_completion_percentage,
                'completion_percent' => $user->identity_completion_percentage,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $verifyMode = $request->input('verify_mode', 'current_password');

        $validated = $request->validate([
            'current_password' => ['nullable', 'string'],
            'verify_nisn' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/\d/', 'confirmed'],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal terdiri dari 8 karakter.',
            'password.regex' => 'Kata sandi baru wajib mengandung huruf dan minimal 1 angka.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if ($verifyMode === 'nisn') {
            $nisnClean = preg_replace('/\D/', '', (string) ($validated['verify_nisn'] ?? ''));
            if (empty($user->nisn)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum mengisi NISN di Data Warga Sekolah. Silakan lengkapi NISN terlebih dahulu atau gunakan kata sandi saat ini.',
                ], 422);
            }
            if ($user->nisn !== $nisnClean) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor NISN yang Anda masukkan tidak sesuai dengan data akun Anda.',
                ], 422);
            }
        } else {
            if (empty($validated['current_password']) || ! Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kata sandi saat ini tidak sesuai. Jika lupa, pilih opsi verifikasi menggunakan NISN.',
                ], 422);
            }
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi akun Anda berhasil diperbarui!',
        ]);
    }

    public function returnLoanItem(Peminjaman $peminjaman): JsonResponse
    {
        if ($peminjaman->user_id !== auth()->id() && ! auth()->user()?->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses tidak diizinkan.',
            ], 403);
        }

        if (in_array($peminjaman->status, ['sedang_dipinjam', 'terlambat'], true)) {
            if ($peminjaman->buku) {
                $peminjaman->buku->increment('stok', max(1, (int) ($peminjaman->jumlah ?? 1)));
            }
            $peminjaman->update([
                'status' => 'selesai',
                'tanggal_kembali' => now()->toDateString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Buku telah ditandai dikembalikan. Terima kasih!',
        ]);
    }
}
