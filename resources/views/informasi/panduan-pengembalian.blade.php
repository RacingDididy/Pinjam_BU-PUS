@extends('layouts.app')

@section('title', 'Panduan Pengembalian Buku - PINJAM BU-PUS')

@section('content')
<div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-5 sm:space-y-7">

    <!-- Navigation Switcher antara 2 Halaman Informasi Layanan -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-blue-600 dark:hover:text-blue-400 inline-flex items-center gap-1">
                <i class="ph-bold ph-house"></i>
                <span>Beranda</span>
            </a>
            <i class="ph-bold ph-caret-right text-[10px]"></i>
            <span class="text-slate-900 dark:text-white font-bold">Panduan Pengembalian Buku</span>
        </nav>

        <div class="inline-flex p-1 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm text-xs font-extrabold">
            <a 
                href="{{ route('informasi.aturan-peminjaman') }}" 
                class="px-3.5 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 inline-flex items-center gap-1.5 transition-colors"
            >
                <i class="ph-bold ph-scroll"></i>
                <span>Aturan Peminjaman</span>
            </a>
            <a 
                href="{{ route('informasi.panduan-pengembalian') }}" 
                class="px-3.5 py-2 rounded-xl bg-blue-600 text-white shadow-sm inline-flex items-center gap-1.5"
            >
                <i class="ph-bold ph-key-return"></i>
                <span>Panduan Pengembalian</span>
            </a>
        </div>
    </div>

    <!-- Hero Header Card -->
    <section class="bg-gradient-to-br from-emerald-600 via-teal-700 to-slate-900 rounded-3xl p-5 sm:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm text-[11px] font-extrabold uppercase tracking-wider">
                    <i class="ph-fill ph-check-circle text-emerald-300"></i>
                    <span>Prosedur Pengembalian &amp; Verifikasi</span>
                </div>
                <h1 class="text-xl sm:text-3xl font-black tracking-tight leading-tight">
                    Panduan Pengembalian Buku Perpustakaan
                </h1>
                <p class="text-xs sm:text-sm text-emerald-50 leading-relaxed">
                    Petunjuk lengkap mengembalikan buku pinjaman tepat waktu, cara memantau <em>progress bar</em> 4 tahap, serta prosedur penyelesaian keterlambatan di <strong>PINJAM BU-PUS SMK Negeri 5 Surakarta</strong>.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2.5 shrink-0">
                <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/15 text-center">
                    <span class="text-[10px] font-bold text-emerald-200 uppercase block">Alur Progres</span>
                    <span class="text-xl sm:text-2xl font-black text-white mt-0.5 block">4 Tahap</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/15 text-center">
                    <span class="text-[10px] font-bold text-emerald-200 uppercase block">Update Stok</span>
                    <span class="text-base sm:text-lg font-black text-emerald-300 mt-1 block">Otomatis</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Tahapan progres peminjaman -->
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-7 shadow-sm space-y-5">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="ph-fill ph-chart-line-up text-emerald-600 dark:text-emerald-400 text-xl"></i>
                <span>Memahami 4 Tahap Progres Peminjaman di Halaman Pinjaman Saya</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Setiap transaksi peminjaman dilengkapi <em>progress bar</em> interaktif yang diperbarui secara langsung tanpa perlu memuat ulang halaman.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-extrabold">Tahap 1</span>
                    <i class="ph-bold ph-paper-plane-tilt text-blue-600"></i>
                </div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">1. Diajukan</h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Form peminjaman buku berhasil dikirim oleh siswa dan stok buku telah diamankan sementara untuk transaksi Anda.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 text-[10px] font-extrabold">Tahap 2 (42%)</span>
                    <i class="ph-bold ph-hourglass-medium text-amber-500"></i>
                </div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">2. Diproses</h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Laporan peminjaman masuk ke antrean <strong>Butuh Diterima</strong> di Dashboard Pustakawan untuk diverifikasi.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[10px] font-extrabold">Tahap 3 (75%)</span>
                    <i class="ph-bold ph-book-open-text text-indigo-600"></i>
                </div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">3. Dipinjam</h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Pustakawan menyetujui pinjaman. Buku sedang dipinjam siswa dengan batas waktu maksimal <strong>7 hari</strong>.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] font-extrabold">Tahap 4 (100%)</span>
                    <i class="ph-fill ph-check-circle text-emerald-600"></i>
                </div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">4. Dikembalikan</h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Buku telah dikembalikan secara utuh, stok katalog bertambah otomatis, dan transaksi dinyatakan tuntas.
                </p>
            </div>
        </div>
    </section>

    <!-- Prosedur pengembalian dan denda -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        
        <!-- Left: Langkah Pengembalian Buku -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shrink-0">
                    <i class="ph-bold ph-key-return"></i>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                        Prosedur Mengembalikan Buku
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Langkah pengembalian fisik buku &amp; pembaruan status di sistem.
                    </p>
                </div>
            </div>

            <ol class="space-y-3 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">1</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white block">Periksa Kelengkapan &amp; Kondisi Buku</strong>
                        Pastikan seluruh judul dan jumlah eksemplar buku yang dipinjam sesuai dengan yang tertera pada <strong>Kode Transaksi (#TRX-...)</strong> serta dalam keadaan rapi.
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">2</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white block">Serahkan Buku ke Meja Layanan Pustakawan</strong>
                        Datang ke Perpustakaan SMK Negeri 5 Surakarta dan tunjukkan halaman <strong>Pinjaman Saya</strong> atau sebutkan <strong>Kode Transaksi / NISN</strong> Anda kepada Pustakawan.
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">3</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white block">Konfirmasi Pengembalian di Sistem</strong>
                        Pustakawan akan memeriksa kondisi fisik buku lalu menekan tombol <strong>Tandai Sudah Dikembalikan</strong> pada tab Laporan, atau Anda dapat menekan tombol pengembalian di halaman <strong>Pinjaman Saya</strong>.
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">4</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white block">Berikan Rating &amp; Ulasan Buku</strong>
                        Setelah buku dikembalikan, Anda dapat memberikan rating bintang dan ulasan pada halaman detail buku untuk membantu pembaca lainnya.
                    </div>
                </li>
            </ol>
        </div>

        <!-- Right: Simulasi Perhitungan Denda Keterlambatan -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl shrink-0">
                    <i class="ph-bold ph-calculator"></i>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                        Perhitungan Denda Keterlambatan
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Rumus otomatis yang digunakan sistem pada tab Terlambat &amp; Denda.
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/90 dark:border-amber-800/60 space-y-2">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 block">
                    Rumus Denda Resmi Sekolah
                </span>
                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                    Total Denda = Hari Terlambat &times; Rp 1.000 &times; Jumlah Buku
                </p>
                <p class="text-[11px] text-amber-900/90 dark:text-amber-200/90 leading-relaxed">
                    Dihitung mulai hari ke-8 (1 hari setelah tanggal tenggat pengembalian 7 hari terlewati).
                </p>
            </div>

            <div class="space-y-2.5 text-xs">
                <span class="font-extrabold text-slate-800 dark:text-slate-200 block">Contoh Simulasi di Sistem:</span>
                
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between gap-2">
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Pinjam 1 Buku, Terlambat 2 Hari</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">2 hari &times; Rp 1.000 &times; 1 buku</span>
                    </div>
                    <span class="font-black text-rose-600 dark:text-rose-400 text-sm">Rp 2.000</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between gap-2">
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Pinjam 2 Buku, Terlambat 3 Hari</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">3 hari &times; Rp 1.000 &times; 2 buku</span>
                    </div>
                    <span class="font-black text-rose-600 dark:text-rose-400 text-sm">Rp 6.000</span>
                </div>
            </div>
        </div>

    </section>

    <!-- Bottom CTA -->
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                Cek Status Pinjaman &amp; Tenggat Pengembalian Anda
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pantau progres persetujuan dan batas tanggal kembali buku Anda di halaman Pinjaman Saya.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a 
                href="{{ route('informasi.aturan-peminjaman') }}" 
                class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-400 text-slate-700 dark:text-slate-200 font-bold text-xs inline-flex items-center gap-1.5 transition-colors"
            >
                <i class="ph-bold ph-scroll"></i>
                <span>Lihat Aturan Peminjaman</span>
            </a>
            <a 
                href="{{ route('profil.index') }}" 
                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors"
            >
                <i class="ph-bold ph-clock-counter-clockwise"></i>
                <span>Buka Pinjaman Saya</span>
            </a>
        </div>
    </section>

</div>
@endsection

