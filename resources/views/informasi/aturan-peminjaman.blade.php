@extends('layouts.app')

@section('title', 'Aturan Peminjaman Buku (7 Hari) - PINJAM BU-PUS')

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
            <span class="text-slate-900 dark:text-white font-bold">Aturan Peminjaman (7 Hari)</span>
        </nav>

        <div class="inline-flex p-1 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm text-xs font-extrabold">
            <a 
                href="{{ route('informasi.aturan-peminjaman') }}" 
                class="px-3.5 py-2 rounded-xl bg-blue-600 text-white shadow-sm inline-flex items-center gap-1.5"
            >
                <i class="ph-bold ph-scroll"></i>
                <span>Aturan Peminjaman</span>
            </a>
            <a 
                href="{{ route('informasi.panduan-pengembalian') }}" 
                class="px-3.5 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 inline-flex items-center gap-1.5 transition-colors"
            >
                <i class="ph-bold ph-key-return"></i>
                <span>Panduan Pengembalian</span>
            </a>
        </div>
    </div>

    <!-- Hero Header Card -->
    <section class="bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 rounded-3xl p-5 sm:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm text-[11px] font-extrabold uppercase tracking-wider">
                    <i class="ph-fill ph-shield-check text-amber-300"></i>
                    <span>Tata Tertib Resmi Perpustakaan</span>
                </div>
                <h1 class="text-xl sm:text-3xl font-black tracking-tight leading-tight">
                    Aturan &amp; Panduan Peminjaman Buku (7 Hari)
                </h1>
                <p class="text-xs sm:text-sm text-blue-100 leading-relaxed">
                    Ketentuan resmi peminjaman koleksi buku perpustakaan <strong>PINJAM BU-PUS SMK Negeri 5 Surakarta</strong> agar sirkulasi buku berjalan tertib, adil, dan transparan bagi seluruh warga sekolah.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2.5 shrink-0">
                <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/15 text-center">
                    <span class="text-[10px] font-bold text-blue-200 uppercase block">Durasi Maksimal</span>
                    <span class="text-xl sm:text-2xl font-black text-white mt-0.5 block">7 Hari</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/15 text-center">
                    <span class="text-[10px] font-bold text-blue-200 uppercase block">Denda Terlambat</span>
                    <span class="text-base sm:text-lg font-black text-amber-300 mt-1 block">Rp 1.000/hr</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Alur peminjaman -->
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-7 shadow-sm space-y-5">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="ph-fill ph-footprints text-blue-600 dark:text-blue-400 text-xl"></i>
                <span>Panduan Langkah-Langkah Meminjam Buku</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Ikuti 4 langkah berikut untuk mengajukan peminjaman buku melalui sistem PINJAM BU-PUS.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            <!-- Step 1 -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-xl bg-blue-600 text-white font-black text-xs flex items-center justify-center">01</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">Verifikasi &amp; Profil</span>
                </div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                    Lengkapi Identitas Siswa
                </h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Daftar dengan email aktif, klik tombol verifikasi di Gmail, buat sandi, lalu lengkapi data sekolah (<strong>NISN 10 digit</strong> &amp; <strong>NIS maks. 5 karakter</strong>) di halaman <strong>Profil</strong>.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-xl bg-blue-600 text-white font-black text-xs flex items-center justify-center">02</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">Pilih Koleksi</span>
                </div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                    Pilih Buku &amp; Jumlah (+ / -)
                </h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Pilih buku di katalog, atur jumlah eksemplar menggunakan tombol <strong>+ / -</strong> sesuai stok tersedia, atau gunakan tombol <strong>+ Tambah Buku Lain</strong> di halaman Checkout.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-xl bg-blue-600 text-white font-black text-xs flex items-center justify-center">03</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">Checkout</span>
                </div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                    Konfirmasi Form Peminjaman
                </h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Periksa ringkasan buku dan identitas peminjam yang terisi otomatis, kemudian tekan <strong>Konfirmasi Peminjaman</strong> untuk memperoleh <strong>Kode Transaksi (#TRX-...)</strong>.
                </p>
            </div>

            <!-- Step 4 -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white font-black text-xs flex items-center justify-center">04</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Persetujuan</span>
                </div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                    Persetujuan Pustakawan
                </h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Pantau *progress bar* di halaman <strong>Pinjaman Saya</strong>. Setelah status berubah menjadi <strong>Sedang Dipinjam</strong>, buku siap diambil di perpustakaan.
                </p>
            </div>
        </div>
    </section>

    <!-- Peraturan peminjaman -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        
        <!-- Card A: Peraturan Identitas & Keabsahan Data -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl shrink-0">
                    <i class="ph-bold ph-identification-card"></i>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                        1. Ketentuan Identitas &amp; Format Nomor Induk
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Wajib dipenuhi sebelum mengonfirmasi peminjaman buku.
                    </p>
                </div>
            </div>

            <ul class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                <li class="flex items-start gap-2.5">
                    <i class="ph-fill ph-check-circle text-blue-600 dark:text-blue-400 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Format NISN Wajib 10 Digit Angka:</strong> Sesuai standar nasional sekolah, kolom NISN hanya menerima tepat 10 digit angka (contoh: <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono">0092971900</code>).</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <i class="ph-fill ph-check-circle text-blue-600 dark:text-blue-400 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Format NIS Maksimal 5 Karakter:</strong> Nomor Induk Siswa (NIS) sekolah dibatasi maksimal 5 karakter sesuai format umum SMK Negeri 5 Surakarta (contoh: <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono">23013</code>).</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <i class="ph-fill ph-check-circle text-blue-600 dark:text-blue-400 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Data Peminjam Terkunci Saat Checkout:</strong> Pada halaman Form Peminjaman Buku, kolom identitas tidak dapat diubah langsung agar tidak terjadi pemalsuan data. Perubahan identitas hanya dapat dilakukan di halaman <strong>Profil Pembaca</strong>.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <i class="ph-fill ph-check-circle text-blue-600 dark:text-blue-400 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Kontak WhatsApp Aktif:</strong> Nomor WhatsApp dan telepon yang dicantumkan wajib aktif untuk keperluan konfirmasi persetujuan atau pengingat jatuh tempo oleh Pustakawan.</span>
                </li>
            </ul>
        </div>

        <!-- Card B: Peraturan Durasi 7 Hari & Denda Keterlambatan -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl shrink-0">
                    <i class="ph-bold ph-warning-octagon"></i>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                        2. Peraturan Durasi 7 Hari &amp; Denda Sekolah
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Ketentuan batas waktu pinjam dan sanksi keterlambatan.
                    </p>
                </div>
            </div>

            <ul class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                <li class="flex items-start gap-2.5">
                    <i class="ph-fill ph-clock-countdown text-amber-500 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Batas Waktu Peminjaman 7 Hari:</strong> Setiap peminjaman buku memiliki durasi maksimal <strong>7 (tujuh) hari kalender</strong> sejak tanggal pinjam yang tercatat pada transaksi.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <i class="ph-fill ph-coins text-rose-500 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Denda Keterlambatan Rp 1.000 / Hari / Buku:</strong> Apabila buku belum dikembalikan melewati tanggal tenggat kembali, sistem otomatis menandai status <strong>Terlambat</strong> dan menghitung denda kas perpustakaan sebesar <strong>Rp 1.000 per hari untuk setiap eksemplar buku</strong>.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <i class="ph-fill ph-book-open text-amber-500 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Tanggung Jawab Kerusakan / Kehilangan:</strong> Peminjam wajib menjaga keutuhan buku. Buku yang rusak berat, dicoret, halaman robek, atau hilang wajib diganti dengan buku yang sama atau mengganti sesuai nilai buku.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <i class="ph-fill ph-prohibit text-rose-500 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Pembatasan Pinjaman Berikutnya:</strong> Siswa yang masih memiliki buku berstatus <em>Terlambat</em> wajib menyelesaikan pengembalian dan administrasi denda sebelum mengajukan peminjaman baru.</span>
                </li>
            </ul>
        </div>

    </section>

    <!-- Bottom CTA -->
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                Siap Meminjam Buku Koleksi Perpustakaan?
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Jelajahi katalog buku atau pelajari panduan pengembalian buku secara lengkap.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a 
                href="{{ route('informasi.panduan-pengembalian') }}" 
                class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-400 text-slate-700 dark:text-slate-200 font-bold text-xs inline-flex items-center gap-1.5 transition-colors"
            >
                <i class="ph-bold ph-key-return"></i>
                <span>Baca Panduan Pengembalian</span>
            </a>
            <a 
                href="{{ route('perpustakaan.index') }}" 
                class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors"
            >
                <i class="ph-bold ph-books"></i>
                <span>Buka Katalog Perpustakaan</span>
            </a>
        </div>
    </section>

</div>
@endsection

