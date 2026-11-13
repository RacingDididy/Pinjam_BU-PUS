@extends('layouts.app')

@section('title', 'PINJAM BU-PUS - ' . __('Beranda'))

@section('content')
<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-3.5 sm:py-7 space-y-6 sm:space-y-10">

    <!-- Hero pencarian dan kategori -->
    <section class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-8 lg:p-10 shadow-sm space-y-4 sm:space-y-5">
        <div class="flex flex-wrap items-center gap-2 text-[11px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 font-bold">
                <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo SMK Negeri 5 Surakarta" width="16" height="16" class="w-4 h-4 object-contain">
                <span>PINJAM BU-PUS</span>
            </span>
            <span>&bull;</span>
            <span>{{ $totalKoleksi }} {{ __('judul buku tersedia dalam katalog') }}</span>
        </div>

        <div class="max-w-3xl space-y-2">
            <h1 class="text-xl sm:text-3xl lg:text-[40px] font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight">
                {{ __('Pinjam Buku Perpustakaan dengan Nyaman & Cepat') }}
            </h1>
            <p class="text-xs sm:text-sm lg:text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                {{ __('Temukan koleksi buku cetak pilihan, pinjam langsung dalam satu klik, atau kumpulkan ke keranjang checkout sesuai kebutuhan belajar dan literasimu.') }}
            </p>
        </div>

        <!-- Hero Search Form -->
        <form action="{{ route('perpustakaan.index') }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <div class="relative flex-1">
                <i class="ph ph-magnifying-glass text-slate-400 text-lg absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true"></i>
                <input 
                    type="text"
                    name="search"
                    placeholder="{{ __('Cari judul buku, penulis, atau ISBN...') }}"
                    aria-label="{{ __('Cari judul buku, penulis, atau ISBN...') }}"
                    class="w-full min-h-[46px] rounded-2xl bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 pl-11 pr-4 py-3 outline-none transition-colors"
                >
            </div>
            <button 
                type="submit"
                class="min-h-[46px] px-6 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm transition-colors shrink-0"
            >
                <i class="ph-bold ph-magnifying-glass text-base" aria-hidden="true"></i>
                <span>{{ __('Cari') }}</span>
            </button>
        </form>

        <!-- Quick Action Pills -->
        <div class="flex flex-wrap items-center gap-2 pt-0.5">
            <a 
                href="{{ route('perpustakaan.index') }}"
                class="min-h-[38px] px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-sm transition-colors"
            >
                <i class="ph-bold ph-compass text-sm" aria-hidden="true"></i>
                <span>{{ __('Jelajahi Perpustakaan') }}</span>
            </a>
            <a 
                href="{{ route('profil.index') }}"
                class="min-h-[38px] px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 hover:border-blue-400 text-slate-700 dark:text-slate-200 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors"
            >
                <i class="ph-bold ph-book-bookmark text-sm text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                <span>{{ __('Pinjaman Saya') }}</span>
            </a>
            <a 
                href="#buku-trending"
                class="min-h-[38px] px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 hover:border-blue-400 text-slate-700 dark:text-slate-200 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors"
            >
                <i class="ph-bold ph-trend-up text-sm text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                <span>{{ __('Buku Terpopuler') }}</span>
            </a>
        </div>

        <!-- Category Pills List inside Hero -->
        @if($kategoris->isNotEmpty())
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 overflow-x-auto sm:flex-wrap no-scrollbar pb-1">
                    <a 
                        href="{{ route('perpustakaan.index') }}"
                        class="shrink-0 px-3.5 py-1.5 rounded-full bg-slate-900 dark:bg-blue-600 text-white text-xs font-bold transition-colors"
                    >
                        {{ __('Semua') }}
                    </a>
                    @foreach($kategoris as $kat)
                        <a 
                            href="{{ route('perpustakaan.index', ['kategori' => $kat->id]) }}"
                            class="shrink-0 px-3.5 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/70 hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400 text-slate-700 dark:text-slate-300 text-xs font-medium transition-colors"
                        >
                            {{ $kat->localized_name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <!-- Banner informasi -->
    <section class="rounded-3xl bg-gradient-to-r from-blue-700 via-blue-600 to-sky-600 text-white p-5 sm:p-8 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
        <div class="max-w-2xl space-y-1.5">
            <h2 class="text-lg sm:text-2xl font-extrabold tracking-tight">
                {{ __('Semua kemudahan meminjam buku dalam satu tempat') }}
            </h2>
            <p class="text-xs sm:text-sm text-blue-100 leading-relaxed">
                {{ __('Pantau status pinjaman aktif, riwayat pengembalian, serta simpan buku incaran ke dalam wishlist akun pembaca.') }}
            </p>
        </div>
        <a 
            href="{{ route('profil.index') }}"
            class="min-h-[44px] px-5 py-2.5 rounded-2xl bg-white text-blue-700 hover:bg-blue-50 font-extrabold text-xs sm:text-sm inline-flex items-center gap-2 shadow-sm transition-colors shrink-0"
        >
            <span>{{ __('Buka Pinjaman Saya') }}</span>
            <i class="ph-bold ph-arrow-right text-sm" aria-hidden="true"></i>
        </a>
    </section>

    <!-- Buku pilihan -->
    @if($pilihanBooks->isNotEmpty())
        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="ph-fill ph-bookmark-simple text-blue-600 dark:text-blue-400 text-xl" aria-hidden="true"></i>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {{ __('Buku Pilihan') }}
                    </h2>
                </div>
                <a href="{{ route('perpustakaan.index', ['sort' => 'tersedia']) }}" class="text-xs sm:text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                    <span>{{ __('Lihat semua') }}</span>
                    <i class="ph-bold ph-caret-right text-xs" aria-hidden="true"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                @foreach($pilihanBooks as $idx => $book)
                    @include('partials.book-card', ['book' => $book, 'eager' => $idx < 4])
                @endforeach
            </div>
        </section>
    @endif

    <!-- Koleksi terbaru -->
    @if($terbaruBooks->isNotEmpty())
        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="ph-fill ph-books text-blue-600 dark:text-blue-400 text-xl" aria-hidden="true"></i>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {{ __('Koleksi Terbaru') }}
                    </h2>
                </div>
                <a href="{{ route('perpustakaan.index', ['sort' => 'terbaru']) }}" class="text-xs sm:text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                    <span>{{ __('Lihat semua') }}</span>
                    <i class="ph-bold ph-caret-right text-xs" aria-hidden="true"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-5">
                @foreach($terbaruBooks as $book)
                    @include('partials.book-card', ['book' => $book])
                @endforeach
            </div>
        </section>
    @endif

    <!-- Buku trending -->
    @if($trendingBooks->isNotEmpty())
        <section id="buku-trending" class="space-y-4 scroll-mt-24">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="ph-bold ph-trend-up text-amber-500 text-xl" aria-hidden="true"></i>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {{ __('Buku Trending') }}
                    </h2>
                </div>
                <a href="{{ route('perpustakaan.index', ['sort' => 'populer']) }}" class="text-xs sm:text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                    <span>{{ __('Lihat semua') }}</span>
                    <i class="ph-bold ph-caret-right text-xs" aria-hidden="true"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                @foreach($trendingBooks as $index => $trendBook)
                    <a 
                        href="{{ route('buku.show', $trendBook->id) }}"
                        class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-500/70 p-3 sm:p-4 flex items-center gap-3.5 sm:gap-4 transition-all hover:shadow-sm"
                    >
                        <div class="w-8 sm:w-9 text-center font-black text-lg sm:text-xl {{ $index < 3 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }} shrink-0">
                            {{ $index + 1 }}
                        </div>

                        <img 
                            src="{{ $trendBook->cover_url }}" 
                            alt="{{ $trendBook->judul }}" 
                            width="48"
                            height="68"
                            loading="lazy"
                            class="w-11 h-16 sm:w-12 sm:h-[68px] object-cover rounded-xl bg-slate-100 dark:bg-slate-800 shrink-0"
                        >

                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate transition-colors">
                                {{ $trendBook->judul }}
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                {{ $trendBook->pengarang }}
                            </p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2.5 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="inline-flex items-center gap-1 font-semibold text-amber-600 dark:text-amber-400">
                                    <i class="ph-fill ph-star text-amber-400" aria-hidden="true"></i>
                                    {{ $trendBook->total_ratings_count > 0 ? number_format($trendBook->average_rating, 1) : '-' }}
                                </span>
                                <span>&bull;</span>
                                <span>{{ number_format($trendBook->views_count) }} {{ __('dilihat') }}</span>
                                <span>&bull;</span>
                                <span class="font-semibold {{ $trendBook->stok > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                                    {{ $trendBook->stok > 0 ? __('Stok') . ' ' . $trendBook->stok : __('Habis') }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Keunggulan layanan -->
    <section class="space-y-5 pt-2">
        <h2 class="text-center text-lg sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            {{ __('Kenapa PINJAM BU-PUS?') }}
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 space-y-2">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                    <i class="ph-bold ph-hand-tap" aria-hidden="true"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Pinjam Langsung 1-Klik') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ __('Pinjam buku lebih ringkas langsung dari halaman detail tanpa harus membuka keranjang.') }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 space-y-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                    <i class="ph-bold ph-shopping-cart" aria-hidden="true"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Checkout Multi-Buku') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ __('Kumpulkan beberapa judul buku ke keranjang pinjaman dan ajukan sekaligus dalam satu langkah.') }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 space-y-2">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl">
                    <i class="ph-bold ph-books" aria-hidden="true"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Katalog Terstruktur') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ __('Telusuri buku berdasarkan kategori, ketersediaan stok, popularitas, dan ulasan pembaca.') }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 space-y-2">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl">
                    <i class="ph-bold ph-moon-stars" aria-hidden="true"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Nyaman Siang & Malam') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ __('Tampilan responsif di ponsel maupun desktop dengan dukungan mode terang, gelap, dan dua bahasa.') }}</p>
            </div>
        </div>
    </section>

</div>
@endsection
