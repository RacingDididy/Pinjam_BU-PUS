<?php

namespace App\Http\Controllers\Pustakawan;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\Rating;
use App\Models\User;
use App\Models\Wishlist;
use App\Support\ExcelExportBuilder;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

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

    public function exportBuku(Request $request): Response
    {
        $mode = strtolower((string) $request->query('mode', 'both'));
        $search = trim((string) $request->query('search', ''));

        $query = Buku::with('kategori')
            ->withCount([
                'peminjamans as kali_dipinjam_count' => fn ($q) => $q->where('status', '!=', 'ditolak'),
                'ratings',
                'wishlists',
            ])
            ->withSum([
                'peminjamans as total_eksemplar_dipinjam' => fn ($q) => $q->where('status', '!=', 'ditolak'),
            ], 'jumlah')
            ->withAvg('ratings', 'bintang');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%'.$search.'%')
                    ->orWhere('pengarang', 'like', '%'.$search.'%')
                    ->orWhere('isbn', 'like', '%'.$search.'%');
            });
        }

        $bukus = $query->orderBy('kategori_id')->orderBy('judul')->get();
        $totalJudul = $bukus->count();
        $totalEksemplar = (int) $bukus->sum('stok');
        $totalDipinjam = (int) $bukus->sum('total_eksemplar_dipinjam');
        $printedAt = now()->translatedFormat('d F Y H:i').' WIB';

        // 1. Sheet Data Sudah Jadi (Formatted Report)
        $formattedRows = [
            [['value' => 'LAPORAN DATA INDUK KOLEKSI BUKU PERPUSTAKAAN SMK NEGERI 5 SURAKARTA', 'style' => 1]],
            [['value' => 'Dicetak pada: '.$printedAt.' | Total Judul: '.$totalJudul.' Buku | Total Stok Tersedia: '.$totalEksemplar.' Eksemplar | Total Pernah Dipinjam: '.$totalDipinjam.' Eksemplar', 'style' => 2]],
            [],
            [
                ['value' => 'NO', 'style' => 3],
                ['value' => 'KODE / ISBN', 'style' => 3],
                ['value' => 'JUDUL BUKU', 'style' => 3],
                ['value' => 'PENGARANG', 'style' => 3],
                ['value' => 'PENERBIT', 'style' => 3],
                ['value' => 'TANGGAL TERBIT', 'style' => 3],
                ['value' => 'KATEGORI KOLEKSI', 'style' => 3],
                ['value' => 'STOK TERSEDIA', 'style' => 3],
                ['value' => 'STATUS STOK', 'style' => 3],
                ['value' => 'DIPINJAM (EKS)', 'style' => 3],
                ['value' => 'RATING', 'style' => 3],
            ],
        ];

        foreach ($bukus as $idx => $b) {
            $stok = (int) $b->stok;
            $dipinjam = (int) ($b->total_eksemplar_dipinjam ?? 0);
            $rating = round((float) ($b->ratings_avg_bintang ?? 0), 1);
            $formattedRows[] = [
                ['value' => $idx + 1, 'style' => 6, 'type' => 'number'],
                ['value' => $b->isbn ?: '-', 'style' => 6],
                ['value' => $b->judul, 'style' => 5],
                ['value' => $b->pengarang ?: '-', 'style' => 5],
                ['value' => $b->penerbit ?: '-', 'style' => 5],
                ['value' => $b->tanggal_terbit_formatted, 'style' => 6],
                ['value' => $b->kategori?->nama_kategori ?? 'Umum', 'style' => 6],
                ['value' => $stok, 'style' => 7, 'type' => 'number'],
                ['value' => $stok > 0 ? 'Tersedia ('.$stok.' Eks)' : 'Stok Habis', 'style' => $stok > 0 ? 9 : 11],
                ['value' => $dipinjam, 'style' => 7, 'type' => 'number'],
                ['value' => $rating > 0 ? number_format($rating, 1) : '-', 'style' => 6],
            ];
        }

        $formattedRows[] = [
            ['value' => '', 'style' => 8],
            ['value' => 'TOTAL KESELURUHAN', 'style' => 8],
            ['value' => $totalJudul.' Judul Buku', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => $totalEksemplar, 'style' => 8, 'type' => 'number'],
            ['value' => 'Eksemplar Aktif', 'style' => 8],
            ['value' => $totalDipinjam, 'style' => 8, 'type' => 'number'],
            ['value' => '', 'style' => 8],
        ];

        $formattedSheet = [
            'name' => 'Laporan Buku (Sudah Jadi)',
            'rows' => $formattedRows,
            'widths' => [6, 18, 42, 24, 24, 20, 22, 15, 18, 15, 10],
            'merges' => ['A1:K1', 'A2:K2'],
        ];

        // 2. Sheet Data Raw / Mentah
        $rawRows = [
            [
                ['value' => 'id', 'style' => 4],
                ['value' => 'isbn', 'style' => 4],
                ['value' => 'judul', 'style' => 4],
                ['value' => 'pengarang', 'style' => 4],
                ['value' => 'penerbit', 'style' => 4],
                ['value' => 'tanggal_terbit', 'style' => 4],
                ['value' => 'tahun_terbit', 'style' => 4],
                ['value' => 'kategori_id', 'style' => 4],
                ['value' => 'nama_kategori', 'style' => 4],
                ['value' => 'stok', 'style' => 4],
                ['value' => 'tebal_halaman', 'style' => 4],
                ['value' => 'bahasa', 'style' => 4],
                ['value' => 'views_count', 'style' => 4],
                ['value' => 'kali_dipinjam_count', 'style' => 4],
                ['value' => 'total_eksemplar_dipinjam', 'style' => 4],
                ['value' => 'rating_avg', 'style' => 4],
                ['value' => 'ratings_count', 'style' => 4],
                ['value' => 'wishlists_count', 'style' => 4],
                ['value' => 'cover_image', 'style' => 4],
                ['value' => 'created_at', 'style' => 4],
            ],
        ];

        foreach ($bukus as $b) {
            $rawRows[] = [
                ['value' => (int) $b->id, 'style' => 5, 'type' => 'number'],
                ['value' => (string) ($b->isbn ?? ''), 'style' => 5],
                ['value' => (string) $b->judul, 'style' => 5],
                ['value' => (string) ($b->pengarang ?? ''), 'style' => 5],
                ['value' => (string) ($b->penerbit ?? ''), 'style' => 5],
                ['value' => $b->tanggal_terbit ? $b->tanggal_terbit->format('Y-m-d') : ($b->tahun_terbit ? $b->tahun_terbit.'-01-15' : ''), 'style' => 5],
                ['value' => (int) ($b->tahun_terbit ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (int) ($b->kategori_id ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (string) ($b->kategori?->nama_kategori ?? 'Umum'), 'style' => 5],
                ['value' => (int) $b->stok, 'style' => 5, 'type' => 'number'],
                ['value' => (int) ($b->tebal_halaman ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (string) ($b->bahasa ?? 'Indonesia'), 'style' => 5],
                ['value' => (int) ($b->views_count ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (int) ($b->kali_dipinjam_count ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (int) ($b->total_eksemplar_dipinjam ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => round((float) ($b->ratings_avg_bintang ?? 0), 2), 'style' => 5, 'type' => 'number'],
                ['value' => (int) ($b->ratings_count ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (int) ($b->wishlists_count ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (string) ($b->cover_image ?? ''), 'style' => 5],
                ['value' => $b->created_at?->format('Y-m-d H:i:s') ?? '', 'style' => 5],
            ];
        }

        $rawSheet = [
            'name' => 'Data Raw Buku (Mentah)',
            'rows' => $rawRows,
            'widths' => [8, 18, 40, 24, 24, 15, 14, 12, 22, 10, 14, 14, 12, 16, 20, 12, 14, 14, 36, 20],
        ];

        $sheets = match ($mode) {
            'formatted' => [$formattedSheet],
            'raw' => [$rawSheet],
            default => [$formattedSheet, $rawSheet],
        };

        $suffix = match ($mode) {
            'formatted' => 'Sudah_Jadi',
            'raw' => 'Data_Raw',
            default => 'Lengkap_Jadi_dan_Raw',
        };

        $filename = 'Laporan_Koleksi_Buku_SMKN5_'.$suffix.'_'.now()->format('Ymd_His').'.xlsx';

        return ExcelExportBuilder::download($filename, $sheets);
    }

    public function exportPeminjaman(Request $request): Response
    {
        $mode = strtolower((string) $request->query('mode', 'both'));
        $statusFilter = strtolower((string) $request->query('status', 'all'));

        $transactions = $this->buildPriorityLoanQueue();
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $transactions = array_values(array_filter(
                $transactions,
                fn (array $trx) => $trx['status'] === $statusFilter
            ));
        }

        $allLoansQuery = Peminjaman::with(['buku.kategori', 'user', 'approver'])->latest();
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $allLoansQuery->where('status', $statusFilter);
        }
        $rawLoans = $allLoansQuery->get();

        $totalTrx = count($transactions);
        $totalBooksBorrowed = (int) collect($transactions)->sum('total_books');
        $totalDendaRp = (int) collect($transactions)->sum('denda_rp');
        $printedAt = now()->translatedFormat('d F Y H:i').' WIB';

        // 1. Sheet Data Sudah Jadi (Formatted Report)
        $formattedRows = [
            [['value' => 'LAPORAN SIRKULASI & PEMINJAMAN BUKU PERPUSTAKAAN SMK NEGERI 5 SURAKARTA', 'style' => 1]],
            [['value' => 'Dicetak pada: '.$printedAt.' | Total Transaksi: '.$totalTrx.' | Total Buku Dipinjam: '.$totalBooksBorrowed.' Eksemplar | Total Akumulasi Denda: Rp '.number_format($totalDendaRp, 0, ',', '.'), 'style' => 2]],
            [],
            [
                ['value' => 'NO', 'style' => 3],
                ['value' => 'KODE TRANSAKSI', 'style' => 3],
                ['value' => 'NAMA PEMINJAM', 'style' => 3],
                ['value' => 'KELAS', 'style' => 3],
                ['value' => 'NISN / NIS', 'style' => 3],
                ['value' => 'NO. WHATSAPP', 'style' => 3],
                ['value' => 'DAFTAR BUKU DIPINJAM', 'style' => 3],
                ['value' => 'TOTAL BUKU', 'style' => 3],
                ['value' => 'TGL PINJAM', 'style' => 3],
                ['value' => 'TENGGAT KEMBALI', 'style' => 3],
                ['value' => 'TGL DIKEMBALIKAN', 'style' => 3],
                ['value' => 'STATUS SIRKULASI', 'style' => 3],
                ['value' => 'TERLAMBAT (HARI)', 'style' => 3],
                ['value' => 'DENDA (RP)', 'style' => 3],
            ],
        ];

        foreach ($transactions as $idx => $trx) {
            $bookTitles = collect($trx['books'])
                ->map(fn (array $b) => $b['judul'].' ('.$b['jumlah'].' Eks)')
                ->implode('; ');

            $statusStyle = match ($trx['status']) {
                'selesai' => 9,
                'terlambat', 'ditolak' => 11,
                default => 10,
            };

            $formattedRows[] = [
                ['value' => $idx + 1, 'style' => 6, 'type' => 'number'],
                ['value' => $trx['kode_transaksi'], 'style' => 6],
                ['value' => $trx['borrower']['name'], 'style' => 5],
                ['value' => $trx['borrower']['nama_kelas'], 'style' => 6],
                ['value' => $trx['borrower']['nisn'].' / '.$trx['borrower']['nis'], 'style' => 6],
                ['value' => $trx['borrower']['no_whatsapp'], 'style' => 6],
                ['value' => $bookTitles, 'style' => 5],
                ['value' => (int) $trx['total_books'], 'style' => 7, 'type' => 'number'],
                ['value' => $trx['tanggal_pengajuan'], 'style' => 6],
                ['value' => $trx['tenggat_kembali'], 'style' => 6],
                ['value' => $trx['tanggal_kembali'] ?: '-', 'style' => 6],
                ['value' => $trx['status_label'], 'style' => $statusStyle],
                ['value' => (int) $trx['overdue_days'], 'style' => 7, 'type' => 'number'],
                ['value' => $trx['denda_formatted'], 'style' => ((int) $trx['denda_rp'] > 0) ? 11 : 6],
            ];
        }

        $formattedRows[] = [
            ['value' => '', 'style' => 8],
            ['value' => 'TOTAL RINGKASAN', 'style' => 8],
            ['value' => $totalTrx.' Transaksi', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => $totalBooksBorrowed, 'style' => 8, 'type' => 'number'],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => '', 'style' => 8],
            ['value' => 'Rp '.number_format($totalDendaRp, 0, ',', '.'), 'style' => 8],
        ];

        $formattedSheet = [
            'name' => 'Laporan Peminjaman (Jadi)',
            'rows' => $formattedRows,
            'widths' => [6, 20, 26, 15, 22, 18, 44, 13, 15, 16, 16, 20, 16, 18],
            'merges' => ['A1:N1', 'A2:N2'],
        ];

        // 2. Sheet Data Raw / Mentah (Baris per Item Peminjaman)
        $rawRows = [
            [
                ['value' => 'loan_id', 'style' => 4],
                ['value' => 'kode_transaksi', 'style' => 4],
                ['value' => 'status', 'style' => 4],
                ['value' => 'prioritas', 'style' => 4],
                ['value' => 'user_id', 'style' => 4],
                ['value' => 'nama_peminjam', 'style' => 4],
                ['value' => 'email_peminjam', 'style' => 4],
                ['value' => 'nisn', 'style' => 4],
                ['value' => 'nis', 'style' => 4],
                ['value' => 'nama_kelas', 'style' => 4],
                ['value' => 'no_whatsapp', 'style' => 4],
                ['value' => 'buku_id', 'style' => 4],
                ['value' => 'isbn_buku', 'style' => 4],
                ['value' => 'judul_buku', 'style' => 4],
                ['value' => 'kategori_buku', 'style' => 4],
                ['value' => 'jumlah_eksemplar', 'style' => 4],
                ['value' => 'tanggal_pinjam', 'style' => 4],
                ['value' => 'tenggat_kembali', 'style' => 4],
                ['value' => 'tanggal_kembali', 'style' => 4],
                ['value' => 'hari_terlambat', 'style' => 4],
                ['value' => 'denda_rp', 'style' => 4],
                ['value' => 'keperluan', 'style' => 4],
                ['value' => 'catatan_peminjam', 'style' => 4],
                ['value' => 'catatan_pustakawan', 'style' => 4],
                ['value' => 'disetujui_oleh', 'style' => 4],
                ['value' => 'approved_at', 'style' => 4],
                ['value' => 'created_at', 'style' => 4],
            ],
        ];

        $today = Carbon::today();
        foreach ($rawLoans as $loan) {
            $borrower = $loan->user;
            $qty = max(1, (int) ($loan->jumlah ?? 1));
            $overdueDays = 0;
            if ($loan->tenggat_kembali) {
                $dueDate = Carbon::parse($loan->tenggat_kembali)->startOfDay();
                if ($loan->status === 'terlambat' && $today->gt($dueDate)) {
                    $overdueDays = (int) $dueDate->diffInDays($today);
                } elseif ($loan->status === 'selesai' && $loan->tanggal_kembali) {
                    $retDate = Carbon::parse($loan->tanggal_kembali)->startOfDay();
                    if ($retDate->gt($dueDate)) {
                        $overdueDays = (int) $dueDate->diffInDays($retDate);
                    }
                }
            }
            $dendaRp = $overdueDays * 1000 * $qty;

            $rawRows[] = [
                ['value' => (int) $loan->id, 'style' => 5, 'type' => 'number'],
                ['value' => (string) ($loan->kode_transaksi ?: ('TRX-SINGLE-'.$loan->id)), 'style' => 5],
                ['value' => (string) $loan->status, 'style' => 5],
                ['value' => (string) ($loan->prioritas ?: 'normal'), 'style' => 5],
                ['value' => (int) ($loan->user_id ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (string) ($borrower?->name ?? $loan->nama_peminjam ?? ''), 'style' => 5],
                ['value' => (string) ($borrower?->email ?? ''), 'style' => 5],
                ['value' => (string) ($borrower?->nisn ?? ''), 'style' => 5],
                ['value' => (string) ($borrower?->nis ?? ''), 'style' => 5],
                ['value' => (string) ($borrower?->nama_kelas ?? ''), 'style' => 5],
                ['value' => (string) ($borrower?->no_whatsapp ?? ''), 'style' => 5],
                ['value' => (int) ($loan->buku_id ?? 0), 'style' => 5, 'type' => 'number'],
                ['value' => (string) ($loan->buku?->isbn ?? ''), 'style' => 5],
                ['value' => (string) ($loan->buku?->judul ?? 'Buku Terhapus'), 'style' => 5],
                ['value' => (string) ($loan->buku?->kategori?->nama_kategori ?? 'Umum'), 'style' => 5],
                ['value' => $qty, 'style' => 5, 'type' => 'number'],
                ['value' => $loan->tanggal_pinjam?->format('Y-m-d') ?? '', 'style' => 5],
                ['value' => $loan->tenggat_kembali?->format('Y-m-d') ?? '', 'style' => 5],
                ['value' => $loan->tanggal_kembali?->format('Y-m-d') ?? '', 'style' => 5],
                ['value' => $overdueDays, 'style' => 5, 'type' => 'number'],
                ['value' => $dendaRp, 'style' => 5, 'type' => 'number'],
                ['value' => (string) ($loan->keperluan ?? ''), 'style' => 5],
                ['value' => (string) ($loan->catatan_peminjam ?? ''), 'style' => 5],
                ['value' => (string) ($loan->catatan_pustakawan ?? ''), 'style' => 5],
                ['value' => (string) ($loan->approver?->name ?? ''), 'style' => 5],
                ['value' => $loan->approved_at?->format('Y-m-d H:i:s') ?? '', 'style' => 5],
                ['value' => $loan->created_at?->format('Y-m-d H:i:s') ?? '', 'style' => 5],
            ];
        }

        $rawSheet = [
            'name' => 'Data Raw Peminjaman (Mentah)',
            'rows' => $rawRows,
            'widths' => [10, 20, 18, 12, 10, 26, 26, 15, 12, 15, 18, 10, 18, 36, 22, 16, 15, 15, 15, 14, 14, 24, 24, 24, 20, 20, 20],
        ];

        $sheets = match ($mode) {
            'formatted' => [$formattedSheet],
            'raw' => [$rawSheet],
            default => [$formattedSheet, $rawSheet],
        };

        $suffix = match ($mode) {
            'formatted' => 'Sudah_Jadi',
            'raw' => 'Data_Raw',
            default => 'Lengkap_Jadi_dan_Raw',
        };

        $filename = 'Laporan_Peminjaman_SMKN5_'.$suffix.'_'.now()->format('Ymd_His').'.xlsx';

        return ExcelExportBuilder::download($filename, $sheets);
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

    public function bulkDestroyKategori(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:kategoris,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $kategoris = Kategori::withCount('bukus')->whereIn('id', $ids)->get();

        $deletedIds = [];
        $skippedNames = [];

        foreach ($kategoris as $kat) {
            if ($kat->bukus_count > 0) {
                $skippedNames[] = $kat->nama_kategori;
            } else {
                $deletedIds[] = $kat->id;
                $kat->delete();
            }
        }

        if (empty($deletedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori yang dipilih masih memiliki koleksi buku dan tidak dapat dihapus.',
            ], 422);
        }

        $msg = count($deletedIds).' kategori berhasil dihapus.';
        if (! empty($skippedNames)) {
            $msg .= ' ('.count($skippedNames).' kategori dilewati karena masih memiliki buku).';
        }

        return response()->json([
            'success' => true,
            'deleted_ids' => $deletedIds,
            'message' => $msg,
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
