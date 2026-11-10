@extends('pustakawan.layouts.pustakawan')

@section('title', 'Ringkasan & Statistik Perpustakaan')
@section('page-title', 'Ringkasan Statistik, Top Buku & Hasil Laporan')

@section('content')
<div 
    x-data="{
        metrics: {{ Js::from($reportMetrics) }},
        topBorrowed: {{ Js::from($topBorrowedBooks) }},
        popularBooks: {{ Js::from($popularBooks) }},
        topBorrowers: {{ Js::from($topBorrowers) }},
        transactions: {{ Js::from($loanTransactions) }},
        busyTrx: null,
        get pendingTransactions() {
            return (this.transactions || []).filter(t => t.status === 'menunggu_approval').slice(0, 4);
        },
        async quickApprove(kode) {
            if (this.busyTrx) return;
            this.busyTrx = kode;
            try {
                const res = await fetch(`/pustakawan/peminjaman/${encodeURIComponent(kode)}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.transactions = data.transactions;
                    this.metrics = data.reportMetrics;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyTrx = null;
            }
        }
    }"
    class="space-y-5 sm:space-y-7"
>

    <!-- Ringkasan utama -->
    <section class="space-y-3.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div>
                <h2 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight">
                    Ringkasan Kalkulasi Perpustakaan &amp; Hasil Laporan
                </h2>
                <p class="text-xs text-slate-500">
                    Seluruh metrik dihitung otomatis secara real-time dari katalog buku, transaksi peminjaman, dan aktivitas pembaca.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="{{ route('pustakawan.laporan', ['tab' => 'menunggu_approval']) }}"
                    class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors"
                >
                    <i class="ph-bold ph-clipboard-text text-sm"></i>
                    <span>Buka Persetujuan (<span x-text="metrics.pending_trx_count"></span>)</span>
                </a>
                <a 
                    href="{{ route('pustakawan.buku.create') }}"
                    class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors"
                >
                    <i class="ph-bold ph-plus-circle text-sm"></i>
                    <span>Tambah Buku</span>
                </a>
            </div>
        </div>

        <!-- Kartu statistik -->
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-2.5 sm:gap-3.5">
            <!-- Jumlah Judul & Kategori -->
            <a href="{{ route('pustakawan.buku.index') }}" class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 hover:border-emerald-400 shadow-sm transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-500">Jumlah Buku</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base shrink-0">
                        <i class="ph-bold ph-books"></i>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-slate-900 block" x-text="metrics.total_judul + ' Judul'"></span>
                    <span class="text-[11px] font-semibold text-slate-500">
                        <span x-text="metrics.total_stok"></span> stok &bull; <span x-text="metrics.total_kategori"></span> kategori
                    </span>
                </div>
            </a>

            <!-- Butuh Diterima -->
            <a href="{{ route('pustakawan.laporan', ['tab' => 'menunggu_approval']) }}" class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 hover:border-amber-400 shadow-sm transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-500">Butuh Diterima</span>
                    <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base shrink-0">
                        <i class="ph-bold ph-hourglass-high"></i>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-amber-600 block" x-text="metrics.pending_trx_count"></span>
                    <span class="text-[11px] font-semibold text-slate-500">
                        <span x-text="metrics.urgent_pending_trx_count"></span> prioritas penting
                    </span>
                </div>
            </a>

            <!-- Sedang Dipinjam -->
            <a href="{{ route('pustakawan.laporan', ['tab' => 'sedang_dipinjam']) }}" class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 hover:border-blue-400 shadow-sm transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-500">Sedang Dipinjam</span>
                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base shrink-0">
                        <i class="ph-bold ph-book-open-text"></i>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-blue-600 block" x-text="metrics.active_trx_count"></span>
                    <span class="text-[11px] font-semibold text-slate-500">
                        <span x-text="metrics.borrowed_qty"></span> eksemplar keluar
                    </span>
                </div>
            </a>

            <!-- Terlambat & Denda -->
            <a href="{{ route('pustakawan.laporan', ['tab' => 'terlambat']) }}" class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 hover:border-rose-400 shadow-sm transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-500">Terlambat &amp; Denda</span>
                    <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-base shrink-0">
                        <i class="ph-bold ph-warning-octagon"></i>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-rose-600 block" x-text="metrics.overdue_trx_count"></span>
                    <span class="text-[11px] font-bold text-rose-600 truncate block" x-text="metrics.total_denda_formatted"></span>
                </div>
            </a>

            <!-- Sudah Dikembalikan -->
            <a href="{{ route('pustakawan.laporan', ['tab' => 'selesai']) }}" class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 hover:border-emerald-400 shadow-sm transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-500">Sudah Dikembalikan</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base shrink-0">
                        <i class="ph-bold ph-check-circle"></i>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-emerald-600 block" x-text="metrics.completed_trx_count"></span>
                    <span class="text-[11px] font-semibold text-slate-500">
                        <span x-text="metrics.returned_qty"></span> buku kembali
                    </span>
                </div>
            </a>

            <!-- Total Dilihat & Ulasan -->
            <div class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-500">Interaksi Pembaca</span>
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base shrink-0">
                        <i class="ph-bold ph-eye"></i>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-slate-900 block" x-text="metrics.total_views + 'x'"></span>
                    <span class="text-[11px] font-semibold text-slate-500">
                        <span x-text="metrics.total_reviews"></span> ulasan &bull; <span x-text="metrics.total_wishlists"></span> simpan
                    </span>
                </div>
            </div>
        </div>

        <!-- 4 Progress Bar Cards (Hasil Laporan Progres) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
            <!-- Progres 1: Rasio Persetujuan -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm space-y-2.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-600">Progres Laporan Diproses</span>
                    <span class="font-extrabold text-blue-600" x-text="metrics.approval_rate_pct + '%'"></span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-blue-600 rounded-full transition-all duration-500 relative overflow-hidden" :style="`width: ${metrics.approval_rate_pct}%`">
                        <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">
                    Total <strong class="text-slate-700" x-text="metrics.total_trx"></strong> transaksi (<span x-text="metrics.rejected_trx_count"></span> ditolak)
                </p>
            </div>

            <!-- Progres 2: Ketersediaan Stok Buku -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm space-y-2.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-600">Rasio Stok Tersedia di Rak</span>
                    <span class="font-extrabold text-emerald-600" x-text="metrics.stock_available_pct + '%'"></span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-500 relative overflow-hidden" :style="`width: ${metrics.stock_available_pct}%`">
                        <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">
                    <strong class="text-slate-700" x-text="metrics.total_stok"></strong> tersedia dari <strong class="text-slate-700" x-text="metrics.total_kapasitas"></strong> kapasitas eksemplar
                </p>
            </div>

            <!-- Progres 3: Ketepatan Pengembalian -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm space-y-2.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-600">Kepatuhan Tenggat 7 Hari</span>
                    <span class="font-extrabold text-indigo-600" x-text="metrics.on_time_pct + '%'"></span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-indigo-600 rounded-full transition-all duration-500 relative overflow-hidden" :style="`width: ${metrics.on_time_pct}%`">
                        <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">
                    <strong class="text-slate-700" x-text="metrics.completed_trx_count"></strong> dikembalikan &bull; <strong class="text-rose-600" x-text="metrics.overdue_trx_count"></strong> terlambat
                </p>
            </div>

            <!-- Progres 4: Kelengkapan Identitas Siswa -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm space-y-2.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-600">Identitas Warga Sekolah</span>
                    <span class="font-extrabold text-amber-600" x-text="metrics.student_identity_pct + '%'"></span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-amber-500 rounded-full transition-all duration-500 relative overflow-hidden" :style="`width: ${metrics.student_identity_pct}%`">
                        <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">
                    <strong class="text-slate-700" x-text="metrics.complete_students"></strong> dari <strong class="text-slate-700" x-text="metrics.total_students"></strong> siswa lengkap
                </p>
            </div>
        </div>
    </section>

    <!-- Top buku dan buku populer -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full">
                        Peringkat Peminjaman
                    </span>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 mt-1">
                        Top Buku Paling Banyak Dipinjam
                    </h3>
                </div>
                <a href="{{ route('pustakawan.buku.index') }}" class="text-xs font-bold text-blue-600 hover:underline">
                    Lihat Katalog &rarr;
                </a>
            </div>

            <div class="space-y-3">
                <template x-for="book in topBorrowed" :key="'top-' + book.id">
                    <div class="p-2.5 sm:p-3 rounded-2xl bg-slate-50/80 border border-slate-200/70 flex items-center gap-3">
                        <div 
                            class="w-7 h-7 rounded-lg font-black text-xs flex items-center justify-center shrink-0"
                            :class="book.rank === 1 ? 'bg-amber-400 text-slate-950' : (book.rank === 2 ? 'bg-slate-300 text-slate-900' : (book.rank === 3 ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-600'))"
                            x-text="'#' + book.rank"
                        ></div>
                        <img :src="book.cover_url" :alt="book.judul" class="w-10 h-14 object-cover rounded-lg bg-slate-200 shrink-0">
                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="text-xs sm:text-sm font-extrabold text-slate-900 truncate" x-text="book.judul"></p>
                            <p class="text-[11px] text-slate-500 truncate">
                                <span class="font-bold text-blue-600" x-text="book.kategori"></span> &bull; <span x-text="book.pengarang"></span>
                            </p>
                            <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full" :style="`width: ${Math.max(8, book.pct)}%`"></div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 font-extrabold text-xs block">
                                <span x-text="book.total_eksemplar"></span> dipinjam
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 mt-0.5 block">
                                Stok: <span x-text="book.stok"></span>
                            </span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">
                        Minat Pembaca Real-Time
                    </span>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 mt-1">
                        Buku Populer (Dilihat, Rating &amp; Disimpan)
                    </h3>
                </div>
                <span class="text-xs font-bold text-slate-500">
                    Total <strong class="text-slate-900" x-text="metrics.total_views"></strong> dilihat
                </span>
            </div>

            <div class="space-y-3">
                <template x-for="book in popularBooks" :key="'pop-' + book.id">
                    <div class="p-2.5 sm:p-3 rounded-2xl bg-slate-50/80 border border-slate-200/70 flex items-center gap-3">
                        <div 
                            class="w-7 h-7 rounded-lg font-black text-xs flex items-center justify-center shrink-0"
                            :class="book.rank <= 3 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'"
                            x-text="'#' + book.rank"
                        ></div>
                        <img :src="book.cover_url" :alt="book.judul" class="w-10 h-14 object-cover rounded-lg bg-slate-200 shrink-0">
                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="text-xs sm:text-sm font-extrabold text-slate-900 truncate" x-text="book.judul"></p>
                            <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                <span class="inline-flex items-center gap-1 font-bold text-slate-700">
                                    <i class="ph-bold ph-eye text-emerald-600"></i>
                                    <span x-text="book.views_count"></span> dilihat
                                </span>
                                <span class="inline-flex items-center gap-1 font-bold text-amber-600">
                                    <i class="ph-fill ph-star"></i>
                                    <span x-text="book.rating_avg"></span> (<span x-text="book.ratings_count"></span>)
                                </span>
                                <span class="inline-flex items-center gap-1 font-bold text-rose-600">
                                    <i class="ph-fill ph-heart"></i>
                                    <span x-text="book.wishlists_count"></span>
                                </span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" :style="`width: ${Math.max(8, book.pct)}%`"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </section>

    <!-- Kategori dan antrean terbaru -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
        
        <!-- Distribusi Kategori (5 Cols) -->
        <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                        Kalkulasi Koleksi per Kategori
                    </h3>
                    <p class="text-[11px] text-slate-500">Jumlah judul, stok eksemplar, dan total dipinjam</p>
                </div>
                <a href="{{ route('pustakawan.kategori.index') }}" class="text-xs font-bold text-emerald-600 hover:underline">
                    Kelola &rarr;
                </a>
            </div>

            <div class="space-y-3">
                <template x-for="cat in metrics.category_bars" :key="cat.id">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800 truncate" x-text="cat.nama"></span>
                            <span class="text-[11px] font-bold text-slate-500 shrink-0">
                                <strong class="text-slate-900" x-text="cat.count"></strong> judul &bull;
                                <span x-text="cat.total_stok"></span> stok &bull;
                                <span class="text-blue-600" x-text="cat.total_dipinjam + ' dipinjam'"></span>
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" :style="`width: ${Math.max(6, cat.pct)}%`"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Antrean Butuh Diterima Terbaru (7 Cols) -->
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                        Pengajuan Peminjaman Butuh Diterima
                    </h3>
                    <p class="text-[11px] text-slate-500">Daftar cepat pengajuan terbaru yang menunggu persetujuan pustakawan</p>
                </div>
                <a href="{{ route('pustakawan.laporan') }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold text-xs transition-colors">
                    Semua Tab Laporan &rarr;
                </a>
            </div>

            <div x-show="pendingTransactions.length === 0" class="py-10 text-center space-y-2">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-xl">
                    <i class="ph-bold ph-check-circle"></i>
                </div>
                <p class="text-xs sm:text-sm font-bold text-slate-800">Semua pengajuan peminjaman sudah ditangani!</p>
                <p class="text-[11px] text-slate-500">Buka halaman Laporan &amp; Persetujuan untuk melihat tab Sedang Dipinjam atau Sudah Dikembalikan.</p>
            </div>

            <div class="space-y-2.5" x-show="pendingTransactions.length > 0">
                <template x-for="trx in pendingTransactions" :key="trx.kode_transaksi">
                    <div class="p-3 rounded-2xl border border-slate-200/80 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="font-mono font-extrabold text-xs text-blue-600" x-text="'#' + trx.kode_transaksi"></span>
                                <span class="text-xs font-extrabold text-slate-900" x-text="trx.borrower.name"></span>
                                <span class="px-2 py-0.5 rounded bg-slate-200/80 text-slate-700 text-[10px] font-bold" x-text="trx.borrower.nama_kelas"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 truncate">
                                <strong class="text-slate-700" x-text="trx.total_books + ' Buku'"></strong> &bull;
                                Pinjam: <span x-text="trx.tanggal_pengajuan"></span> &bull;
                                NISN: <span class="font-mono" x-text="trx.borrower.nisn"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button 
                                type="button"
                                @click="quickApprove(trx.kode_transaksi)"
                                :disabled="busyTrx === trx.kode_transaksi"
                                class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs inline-flex items-center gap-1 transition-colors"
                            >
                                <i class="ph-bold ph-check-circle"></i>
                                <span>Terima</span>
                            </button>
                            <a 
                                :href="`{{ route('pustakawan.laporan') }}?tab=menunggu_approval`"
                                class="px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs"
                            >
                                Detail
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </section>

</div>
@endsection
