<?php

namespace App\Http\Controllers\Pustakawan;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\Rating;
use App\Models\User;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $bukus = $this->loadBooksWithStats();
        $kategoris = $this->loadCategoriesWithStats($bukus);
        $studentsPayload = $this->loadStudentsPayload();
        $loanTransactions = $this->buildPriorityLoanQueue();
        $reportMetrics = $this->buildReportMetrics($bukus, $kategoris, $loanTransactions, $studentsPayload);
        $topBorrowedBooks = $this->buildTopBorrowedBooks($bukus);
        $popularBooks = $this->buildPopularBooks($bukus);
        $topBorrowers = collect($studentsPayload)
            ->filter(fn (array $s) => $s['total_loans_count'] > 0)
            ->sortByDesc('total_loans_count')
            ->take(5)
            ->values()
            ->all();

        return view('pustakawan.dashboard', compact(
            'bukus',
            'kategoris',
            'studentsPayload',
            'loanTransactions',
            'reportMetrics',
            'topBorrowedBooks',
            'popularBooks',
            'topBorrowers'
        ));
    }

    public function laporan(Request $request): View
    {
        $bukus = $this->loadBooksWithStats();
        $kategoris = $this->loadCategoriesWithStats($bukus);
        $studentsPayload = $this->loadStudentsPayload();
        $loanTransactions = $this->buildPriorityLoanQueue();
        $reportMetrics = $this->buildReportMetrics($bukus, $kategoris, $loanTransactions, $studentsPayload);
        $initialTab = $request->query('tab', 'all');

        return view('pustakawan.laporan', compact(
            'loanTransactions',
            'reportMetrics',
            'initialTab'
        ));
    }

    public function kategoriIndex(): View
    {
        $bukus = $this->loadBooksWithStats();
        $kategoris = $this->loadCategoriesWithStats($bukus);
        $loanTransactions = $this->buildPriorityLoanQueue();
        $studentsPayload = $this->loadStudentsPayload();
        $reportMetrics = $this->buildReportMetrics($bukus, $kategoris, $loanTransactions, $studentsPayload);

        return view('pustakawan.kategori', compact(
            'kategoris',
            'reportMetrics'
        ));
    }

    public function wargaIndex(): View
    {
        $bukus = $this->loadBooksWithStats();
        $kategoris = $this->loadCategoriesWithStats($bukus);
        $studentsPayload = $this->loadStudentsPayload();
        $loanTransactions = $this->buildPriorityLoanQueue();
        $reportMetrics = $this->buildReportMetrics($bukus, $kategoris, $loanTransactions, $studentsPayload);

        return view('pustakawan.warga', compact(
            'studentsPayload',
            'reportMetrics'
        ));
    }

    public function approveLoan(Request $request, string $kodeTransaksi): JsonResponse
    {
        $loans = $this->findLoansByCode($kodeTransaksi);

        if ($loans->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan peminjaman tidak ditemukan.',
            ], 404);
        }

        $catatan = $request->input('catatan_pustakawan');

        DB::transaction(function () use ($loans, $catatan) {
            foreach ($loans as $loan) {
                if ($loan->status === 'menunggu_approval') {
                    $loan->update([
                        'status' => 'sedang_dipinjam',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'catatan_pustakawan' => $catatan ?: $loan->catatan_pustakawan,
                    ]);
                }
            }
        });

        $bukus = $this->loadBooksWithStats();
        $kategoris = $this->loadCategoriesWithStats($bukus);
        $transactions = $this->buildPriorityLoanQueue();
        $studentsPayload = $this->loadStudentsPayload();

        return response()->json([
            'success' => true,
            'message' => "Peminjaman #{$kodeTransaksi} berhasil diterima/disetujui. Status kini Sedang Dipinjam.",
            'transactions' => $transactions,
            'reportMetrics' => $this->buildReportMetrics($bukus, $kategoris, $transactions, $studentsPayload),
        ]);
    }

    public function rejectLoan(Request $request, string $kodeTransaksi): JsonResponse
    {
        $loans = $this->findLoansByCode($kodeTransaksi);

        if ($loans->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan peminjaman tidak ditemukan.',
            ], 404);
        }

        $catatan = $request->input('catatan_pustakawan', 'Pengajuan belum memenuhi ketentuan perpustakaan.');

        DB::transaction(function () use ($loans, $catatan) {
            foreach ($loans as $loan) {
                if ($loan->status === 'menunggu_approval') {
                    if ($loan->buku) {
                        $loan->buku->increment('stok', max(1, (int) ($loan->jumlah ?? 1)));
                    }
                    $loan->update([
                        'status' => 'ditolak',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'catatan_pustakawan' => $catatan,
                    ]);
                }
            }
        });

        $bukus = $this->loadBooksWithStats();
        $kategoris = $this->loadCategoriesWithStats($bukus);
        $transactions = $this->buildPriorityLoanQueue();
        $studentsPayload = $this->loadStudentsPayload();

        return response()->json([
            'success' => true,
            'message' => "Pengajuan #{$kodeTransaksi} ditolak dan stok buku telah dikembalikan.",
            'transactions' => $transactions,
            'reportMetrics' => $this->buildReportMetrics($bukus, $kategoris, $transactions, $studentsPayload),
        ]);
    }

    public function completeLoan(Request $request, string $kodeTransaksi): JsonResponse
    {
        $loans = $this->findLoansByCode($kodeTransaksi);

        if ($loans->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data peminjaman tidak ditemukan.',
            ], 404);
        }

        DB::transaction(function () use ($loans) {
            foreach ($loans as $loan) {
                if (in_array($loan->status, ['sedang_dipinjam', 'terlambat'], true)) {
                    if ($loan->buku) {
                        $loan->buku->increment('stok', max(1, (int) ($loan->jumlah ?? 1)));
                    }
                    $loan->update([
                        'status' => 'selesai',
                        'tanggal_kembali' => now()->toDateString(),
                    ]);
                }
            }
        });

        $bukus = $this->loadBooksWithStats();
        $kategoris = $this->loadCategoriesWithStats($bukus);
        $transactions = $this->buildPriorityLoanQueue();
        $studentsPayload = $this->loadStudentsPayload();

        return response()->json([
            'success' => true,
            'message' => "Buku pada transaksi #{$kodeTransaksi} telah ditandai sudah dikembalikan.",
            'transactions' => $transactions,
            'reportMetrics' => $this->buildReportMetrics($bukus, $kategoris, $transactions, $studentsPayload),
        ]);
    }

    public function storeKategori(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:80', 'unique:kategoris,nama_kategori'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ], [
            'nama_kategori.unique' => 'Nama kategori tersebut sudah terdaftar.',
        ]);

        $kategori = Kategori::create([
            'nama_kategori' => $validated['nama_kategori'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Kategori '{$kategori->nama_kategori}' berhasil ditambahkan.",
            'kategori' => [
                'id' => $kategori->id,
                'nama_kategori' => $kategori->nama_kategori,
                'deskripsi' => $kategori->deskripsi ?? '',
                'bukus_count' => 0,
                'total_stok' => 0,
                'total_dipinjam' => 0,
            ],
        ]);
    }

    public function updateKategori(Request $request, Kategori $kategori): JsonResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:80', 'unique:kategoris,nama_kategori,'.$kategori->id],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ]);

        $kategori->update($validated);
        $kategori->loadCount('bukus');

        return response()->json([
            'success' => true,
            'message' => "Kategori '{$kategori->nama_kategori}' berhasil diperbarui.",
            'kategori' => [
                'id' => $kategori->id,
                'nama_kategori' => $kategori->nama_kategori,
                'deskripsi' => $kategori->deskripsi ?? '',
                'bukus_count' => (int) $kategori->bukus_count,
            ],
        ]);
    }

    public function destroyKategori(Kategori $kategori): JsonResponse
    {
        if ($kategori->bukus()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori ini masih memiliki koleksi buku dan tidak dapat dihapus.',
            ], 422);
        }

        $nama = $kategori->nama_kategori;
        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => "Kategori '{$nama}' berhasil dihapus.",
        ]);
    }

    private function loadBooksWithStats()
    {
        return Buku::with('kategori')
            ->withCount([
                'peminjamans as kali_dipinjam_count' => function ($q) {
                    $q->where('status', '!=', 'ditolak');
                },
                'ratings',
                'wishlists',
            ])
            ->withSum([
                'peminjamans as total_eksemplar_dipinjam' => function ($q) {
                    $q->where('status', '!=', 'ditolak');
                },
            ], 'jumlah')
            ->withAvg('ratings', 'bintang')
            ->latest()
            ->get();
    }

    private function loadCategoriesWithStats($bukus)
    {
        $kategoris = Kategori::withCount('bukus')->orderBy('nama_kategori')->get();

        return $kategoris->map(function (Kategori $kat) use ($bukus) {
            $booksInKat = $bukus->where('kategori_id', $kat->id);
            $kat->setAttribute('total_stok', (int) $booksInKat->sum('stok'));
            $kat->setAttribute('total_dipinjam', (int) $booksInKat->sum('total_eksemplar_dipinjam'));
            $kat->setAttribute('total_views', (int) $booksInKat->sum('views_count'));

            return $kat;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadStudentsPayload(): array
    {
        $students = User::where('role', 'user')
            ->withCount([
                'peminjamans as active_loans_count' => function ($q) {
                    $q->whereIn('status', ['menunggu_approval', 'sedang_dipinjam', 'terlambat']);
                },
                'peminjamans as completed_loans_count' => function ($q) {
                    $q->where('status', 'selesai');
                },
                'peminjamans as overdue_loans_count' => function ($q) {
                    $q->where('status', 'terlambat');
                },
                'peminjamans as total_loans_count',
            ])
            ->latest()
            ->get();

        return $students->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'username' => $u->display_username,
            'email' => $u->email,
            'email_verified' => $u->isEmailVerified(),
            'identity_complete' => $u->isSchoolIdentityComplete(),
            'identity_pct' => $u->identity_completion_percentage,
            'nisn' => $u->nisn ?: '-',
            'nis' => $u->nis ?: '-',
            'nama_sekolah' => $u->nama_sekolah ?: 'SMK Negeri 5 Surakarta',
            'nama_kelas' => $u->nama_kelas ?: '-',
            'tahun_ajaran' => $u->tahun_ajaran ?: '-',
            'no_telepon' => $u->no_telepon ?: '-',
            'no_whatsapp' => $u->no_whatsapp ?: '-',
            'tanggal_lahir' => $u->tanggal_lahir ? $u->tanggal_lahir->format('d M Y') : '-',
            'alamat' => $u->alamat ?: '-',
            'initial' => $u->initial,
            'palette' => $u->color_palette,
            'active_loans_count' => (int) ($u->active_loans_count ?? 0),
            'completed_loans_count' => (int) ($u->completed_loans_count ?? 0),
            'overdue_loans_count' => (int) ($u->overdue_loans_count ?? 0),
            'total_loans_count' => (int) ($u->total_loans_count ?? 0),
        ])->values()->all();
    }

    private function findLoansByCode(string $kodeTransaksi)
    {
        if (str_starts_with($kodeTransaksi, 'TRX-SINGLE-')) {
            $id = (int) str_replace('TRX-SINGLE-', '', $kodeTransaksi);

            return Peminjaman::with(['buku', 'user'])->where('id', $id)->get();
        }

        return Peminjaman::with(['buku', 'user'])->where('kode_transaksi', $kodeTransaksi)->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPriorityLoanQueue(): array
    {
        // Update status otomatis jika melewati tenggat
        Peminjaman::where('status', 'sedang_dipinjam')
            ->whereDate('tenggat_kembali', '<', now()->toDateString())
            ->update(['status' => 'terlambat']);

        $allLoans = Peminjaman::with(['buku.kategori', 'user', 'approver'])
            ->latest()
            ->get();

        $grouped = $allLoans->groupBy(function (Peminjaman $item) {
            return $item->kode_transaksi ?: ('TRX-SINGLE-'.$item->id);
        });

        $today = Carbon::today();

        $transactions = $grouped->map(function ($items, $kode) use ($today) {
            /** @var Peminjaman $first */
            $first = $items->first();
            $borrower = $first->user;

            $statuses = $items->pluck('status')->unique();
            if ($statuses->contains('menunggu_approval')) {
                $overallStatus = 'menunggu_approval';
            } elseif ($statuses->contains('terlambat')) {
                $overallStatus = 'terlambat';
            } elseif ($statuses->contains('sedang_dipinjam')) {
                $overallStatus = 'sedang_dipinjam';
            } elseif ($statuses->contains('ditolak') && $statuses->count() === 1) {
                $overallStatus = 'ditolak';
            } else {
                $overallStatus = 'selesai';
            }

            $prioritas = $items->contains(fn ($i) => $i->prioritas === 'penting') ? 'penting' : 'normal';
            $totalBooks = (int) $items->sum(fn ($i) => max(1, (int) ($i->jumlah ?? 1)));

            // Hitung keterlambatan dan denda (Rp 1.000/hari/buku)
            $overdueDays = 0;
            if ($first->tenggat_kembali) {
                $dueDate = Carbon::parse($first->tenggat_kembali)->startOfDay();
                if ($overallStatus === 'terlambat' && $today->gt($dueDate)) {
                    $overdueDays = (int) $dueDate->diffInDays($today);
                } elseif ($overallStatus === 'selesai' && $first->tanggal_kembali) {
                    $returnDate = Carbon::parse($first->tanggal_kembali)->startOfDay();
                    if ($returnDate->gt($dueDate)) {
                        $overdueDays = (int) $dueDate->diffInDays($returnDate);
                    }
                }
            }
            $dendaRp = $overdueDays * 1000 * $totalBooks;

            $sortRank = match ($overallStatus) {
                'menunggu_approval' => $prioritas === 'penting' ? 0 : 1,
                'terlambat' => 2,
                'sedang_dipinjam' => 3,
                default => 4,
            };

            $step = match ($overallStatus) {
                'menunggu_approval' => 2,
                'sedang_dipinjam', 'terlambat' => 3,
                'selesai' => 4,
                default => 1,
            };

            $progressPct = match ($overallStatus) {
                'menunggu_approval' => 42,
                'sedang_dipinjam', 'terlambat' => 75,
                'selesai' => 100,
                default => 15,
            };

            return [
                'kode_transaksi' => $kode,
                'sort_rank' => $sortRank,
                'created_timestamp' => $first->created_at?->timestamp ?? 0,
                'status' => $overallStatus,
                'status_label' => match ($overallStatus) {
                    'menunggu_approval' => 'Butuh Diterima',
                    'sedang_dipinjam' => 'Sedang Dipinjam',
                    'terlambat' => 'Terlambat Kembali',
                    'selesai' => 'Sudah Dikembalikan',
                    'ditolak' => 'Ditolak',
                    default => 'Diajukan',
                },
                'prioritas' => $prioritas,
                'keperluan' => $first->keperluan ?: 'Peminjaman Buku Perpustakaan',
                'catatan_peminjam' => $first->catatan_peminjam,
                'catatan_pustakawan' => $first->catatan_pustakawan,
                'tanggal_pengajuan' => $first->tanggal_pinjam?->translatedFormat('d M Y') ?? '-',
                'tenggat_kembali' => $first->tenggat_kembali?->translatedFormat('d M Y') ?? '-',
                'tanggal_kembali' => $first->tanggal_kembali?->translatedFormat('d M Y'),
                'approved_at' => $first->approved_at?->translatedFormat('d M Y H:i'),
                'approver_name' => $first->approver?->name,
                'overdue_days' => $overdueDays,
                'denda_rp' => $dendaRp,
                'denda_formatted' => 'Rp '.number_format($dendaRp, 0, ',', '.'),
                'step' => $step,
                'progress_pct' => $progressPct,
                'total_books' => $totalBooks,
                'total_titles' => $items->count(),
                'borrower' => [
                    'id' => $borrower?->id,
                    'name' => $borrower?->name ?? ($first->nama_peminjam ?: 'Siswa'),
                    'email' => $borrower?->email ?? '-',
                    'nisn' => $borrower?->nisn ?: '-',
                    'nis' => $borrower?->nis ?: '-',
                    'nama_sekolah' => $borrower?->nama_sekolah ?: 'SMK Negeri 5 Surakarta',
                    'nama_kelas' => $borrower?->nama_kelas ?: '-',
                    'tahun_ajaran' => $borrower?->tahun_ajaran ?: '-',
                    'no_telepon' => $borrower?->no_telepon ?: '-',
                    'no_whatsapp' => $borrower?->no_whatsapp ?: '-',
                    'tanggal_lahir' => $borrower?->tanggal_lahir ? $borrower->tanggal_lahir->format('d M Y') : '-',
                    'alamat' => $borrower?->alamat ?: '-',
                    'initial' => $borrower?->initial ?? 'S',
                    'palette' => $borrower?->color_palette ?? ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
                ],
                'books' => $items->map(fn (Peminjaman $loan) => [
                    'loan_id' => $loan->id,
                    'buku_id' => $loan->buku_id,
                    'judul' => $loan->buku?->judul ?? 'Buku Terhapus',
                    'pengarang' => $loan->buku?->pengarang ?? '-',
                    'kategori' => $loan->buku?->kategori?->nama_kategori ?? 'Umum',
                    'isbn' => $loan->buku?->isbn ?? '-',
                    'cover_url' => $loan->buku?->cover_url ?? asset('images/logo-sekolah.png'),
                    'jumlah' => max(1, (int) ($loan->jumlah ?? 1)),
                ])->values()->all(),
            ];
        })
            ->sort(function ($a, $b) {
                if ($a['sort_rank'] !== $b['sort_rank']) {
                    return $a['sort_rank'] <=> $b['sort_rank'];
                }

                return $b['created_timestamp'] <=> $a['created_timestamp'];
            })
            ->values()
            ->all();

        return $transactions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTopBorrowedBooks($bukus): array
    {
        $maxBorrowed = max(1, (int) $bukus->max('total_eksemplar_dipinjam'));

        return $bukus
            ->sortByDesc(fn (Buku $b) => ((int) $b->total_eksemplar_dipinjam * 1000) + (int) $b->views_count)
            ->take(6)
            ->values()
            ->map(fn (Buku $b, int $idx) => [
                'rank' => $idx + 1,
                'id' => $b->id,
                'judul' => $b->judul,
                'pengarang' => $b->pengarang,
                'kategori' => $b->kategori?->nama_kategori ?? 'Umum',
                'cover_url' => $b->cover_url,
                'stok' => (int) $b->stok,
                'kali_dipinjam' => (int) ($b->kali_dipinjam_count ?? 0),
                'total_eksemplar' => (int) ($b->total_eksemplar_dipinjam ?? 0),
                'views_count' => (int) ($b->views_count ?? 0),
                'rating_avg' => round((float) ($b->ratings_avg_bintang ?? 0), 1),
                'pct' => (int) round((((int) ($b->total_eksemplar_dipinjam ?? 0)) / $maxBorrowed) * 100),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPopularBooks($bukus): array
    {
        $maxViews = max(1, (int) $bukus->max('views_count'));

        return $bukus
            ->sortByDesc(fn (Buku $b) => ((int) $b->views_count * 100) + ((int) $b->wishlists_count * 50) + (float) ($b->ratings_avg_bintang ?? 0) * 10)
            ->take(6)
            ->values()
            ->map(fn (Buku $b, int $idx) => [
                'rank' => $idx + 1,
                'id' => $b->id,
                'judul' => $b->judul,
                'pengarang' => $b->pengarang,
                'kategori' => $b->kategori?->nama_kategori ?? 'Umum',
                'cover_url' => $b->cover_url,
                'stok' => (int) $b->stok,
                'views_count' => (int) ($b->views_count ?? 0),
                'wishlists_count' => (int) ($b->wishlists_count ?? 0),
                'ratings_count' => (int) ($b->ratings_count ?? 0),
                'rating_avg' => round((float) ($b->ratings_avg_bintang ?? 0), 1),
                'total_eksemplar' => (int) ($b->total_eksemplar_dipinjam ?? 0),
                'pct' => (int) round((((int) ($b->views_count ?? 0)) / $maxViews) * 100),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportMetrics($bukus, $kategoris, array $transactions = [], array $studentsPayload = []): array
    {
        $trxCollection = collect($transactions);

        $totalTrx = $trxCollection->count();
        $pendingTrxCount = $trxCollection->where('status', 'menunggu_approval')->count();
        $urgentTrxCount = $trxCollection->where('prioritas', 'penting')->count();
        $urgentPendingTrxCount = $trxCollection->where('status', 'menunggu_approval')->where('prioritas', 'penting')->count();
        $activeTrxCount = $trxCollection->where('status', 'sedang_dipinjam')->count();
        $overdueTrxCount = $trxCollection->where('status', 'terlambat')->count();
        $completedTrxCount = $trxCollection->where('status', 'selesai')->count();
        $rejectedTrxCount = $trxCollection->where('status', 'ditolak')->count();
        $totalDendaRp = (int) $trxCollection->sum('denda_rp');

        $totalLoansItems = Peminjaman::count();
        $pendingCount = Peminjaman::where('status', 'menunggu_approval')->count();
        $urgentPendingCount = Peminjaman::where('status', 'menunggu_approval')
            ->where('prioritas', 'penting')
            ->count();
        $activeCount = Peminjaman::where('status', 'sedang_dipinjam')->count();
        $overdueCount = Peminjaman::where('status', 'terlambat')->count();
        $completedCount = Peminjaman::where('status', 'selesai')->count();
        $rejectedCount = Peminjaman::where('status', 'ditolak')->count();

        $processedTrxCount = $activeTrxCount + $overdueTrxCount + $completedTrxCount + $rejectedTrxCount;
        $approvalRatePct = $totalTrx > 0 ? (int) round(($processedTrxCount / $totalTrx) * 100) : 100;

        $processedUrgent = max(0, $urgentTrxCount - $urgentPendingTrxCount);
        $urgentHandledPct = $urgentTrxCount > 0 ? (int) round(($processedUrgent / $urgentTrxCount) * 100) : 100;

        $totalAvailableStock = (int) $bukus->sum('stok');
        $currentlyBorrowedQty = (int) Peminjaman::whereIn('status', ['menunggu_approval', 'sedang_dipinjam', 'terlambat'])
            ->sum('jumlah');
        $totalReturnedQty = (int) Peminjaman::where('status', 'selesai')->sum('jumlah');
        $totalLibraryCapacity = max(1, $totalAvailableStock + $currentlyBorrowedQty);
        $stockAvailablePct = (int) round(($totalAvailableStock / $totalLibraryCapacity) * 100);

        $activeAndCompleted = $activeTrxCount + $completedTrxCount + $overdueTrxCount;
        $onTimePct = $activeAndCompleted > 0
            ? (int) round((($activeTrxCount + $completedTrxCount) / $activeAndCompleted) * 100)
            : 100;

        $totalStudents = count($studentsPayload);
        $completeStudents = collect($studentsPayload)->where('identity_complete', true)->count();
        $studentIdentityPct = $totalStudents > 0 ? (int) round(($completeStudents / $totalStudents) * 100) : 100;

        $totalViews = (int) $bukus->sum('views_count');
        $totalReviews = Rating::count();
        $totalWishlists = Wishlist::count();
        $outOfStockBooks = $bukus->where('stok', '<=', 0)->count();

        $totalJudul = max(1, $bukus->count());
        $categoryBars = $kategoris->map(fn ($kat) => [
            'id' => $kat->id,
            'nama' => $kat->nama_kategori,
            'deskripsi' => $kat->deskripsi ?? '',
            'count' => (int) $kat->bukus_count,
            'total_stok' => (int) ($kat->total_stok ?? 0),
            'total_dipinjam' => (int) ($kat->total_dipinjam ?? 0),
            'total_views' => (int) ($kat->total_views ?? 0),
            'pct' => (int) round((($kat->bukus_count ?? 0) / $totalJudul) * 100),
        ])->sortByDesc('count')->values()->all();

        return [
            'total_trx' => $totalTrx,
            'pending_trx_count' => $pendingTrxCount,
            'urgent_trx_count' => $urgentTrxCount,
            'urgent_pending_trx_count' => $urgentPendingTrxCount,
            'active_trx_count' => $activeTrxCount,
            'overdue_trx_count' => $overdueTrxCount,
            'completed_trx_count' => $completedTrxCount,
            'rejected_trx_count' => $rejectedTrxCount,
            'total_denda_rp' => $totalDendaRp,
            'total_denda_formatted' => 'Rp '.number_format($totalDendaRp, 0, ',', '.'),
            'total_loans' => $totalLoansItems,
            'pending_count' => $pendingTrxCount,
            'urgent_pending_count' => $urgentPendingTrxCount,
            'active_count' => $activeTrxCount,
            'overdue_count' => $overdueTrxCount,
            'completed_count' => $completedTrxCount,
            'rejected_count' => $rejectedTrxCount,
            'total_judul' => $bukus->count(),
            'total_kategori' => $kategoris->count(),
            'total_stok' => $totalAvailableStock,
            'total_kapasitas' => $totalAvailableStock + $currentlyBorrowedQty,
            'borrowed_qty' => $currentlyBorrowedQty,
            'returned_qty' => $totalReturnedQty,
            'out_of_stock_count' => $outOfStockBooks,
            'total_views' => $totalViews,
            'total_reviews' => $totalReviews,
            'total_wishlists' => $totalWishlists,
            'total_students' => $totalStudents,
            'complete_students' => $completeStudents,
            'student_identity_pct' => $studentIdentityPct,
            'approval_rate_pct' => $approvalRatePct,
            'urgent_handled_pct' => $urgentHandledPct,
            'stock_available_pct' => $stockAvailablePct,
            'on_time_pct' => $onTimePct,
            'category_bars' => $categoryBars,
        ];
    }
}
