@extends('layouts.app')

@section('title', __('Perpustakaan Buku') . ' - PINJAM BU-PUS')

@section('content')
<div 
    id="perpustakaan-dynamic-area"
    x-data="{
        loadingCatalog: false,
        async loadCatalogUrl(url, pushHistory = true) {
            if (!url || this.loadingCatalog) return;
            this.loadingCatalog = true;
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextArea = doc.getElementById('perpustakaan-dynamic-content');
                const currentArea = document.getElementById('perpustakaan-dynamic-content');
                if (nextArea && currentArea) {
                    currentArea.innerHTML = nextArea.innerHTML;
                    if (window.Alpine) {
                        window.Alpine.initTree(currentArea);
                    }
                    if (pushHistory) {
                        window.history.pushState({ catalogUrl: url }, '', url);
                    }
                } else {
                    window.location.href = url;
                }
            } catch (e) {
                window.location.href = url;
            } finally {
                this.loadingCatalog = false;
            }
        },
        handleCatalogClick(e) {
            const link = e.target.closest('a[data-ajax-catalog], .pagination a, nav[role=navigation] a');
            if (!link) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            e.preventDefault();
            this.loadCatalogUrl(href, true);
        },
        handleCatalogSearch(e) {
            const form = e.target.closest('form[data-ajax-catalog-form]');
            if (!form) return;
            e.preventDefault();
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (const [k, v] of formData.entries()) {
                if (String(v).trim() !== '') params.set(k, String(v).trim());
            }
            const base = form.getAttribute('action') || window.location.pathname;
            const qs = params.toString();
            this.loadCatalogUrl(qs ? `${base}?${qs}` : base, true);
        }
    }"
    @click="handleCatalogClick($event)"
    @submit="handleCatalogSearch($event)"
    @popstate.window="loadCatalogUrl(window.location.href, false)"
    class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-7"
>
<div id="perpustakaan-dynamic-content" class="space-y-5 sm:space-y-6 transition-opacity duration-150" :class="loadingCatalog ? 'opacity-60 pointer-events-none' : 'opacity-100'">

    <!-- Breadcrumb Pills -->
    <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs overflow-x-auto no-scrollbar">
        <a 
            href="{{ route('home') }}"
            class="w-8 h-8 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-600 flex items-center justify-center shrink-0 transition-colors"
            aria-label="{{ __('Beranda') }}"
        >
            <i class="ph-bold ph-house text-sm" aria-hidden="true"></i>
        </a>
        <i class="ph-bold ph-caret-right text-[10px] text-slate-400 shrink-0" aria-hidden="true"></i>
        <a 
            href="{{ route('perpustakaan.index') }}"
            data-ajax-catalog="1"
            class="px-3 py-1.5 rounded-full font-bold shrink-0 transition-colors {{ !$activeKategori ? 'bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-600' }}"
        >
            {{ __('Perpustakaan') }}
        </a>
        @if($activeKategori)
            <i class="ph-bold ph-caret-right text-[10px] text-slate-400 shrink-0" aria-hidden="true"></i>
            <span class="px-3 py-1.5 rounded-full bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 font-bold shrink-0">
                {{ $activeKategori->localized_name }}
            </span>
        @endif
    </nav>

    <!-- Catalog Header + Inline Search -->
    <section class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="space-y-1">
            <span class="inline-block px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 text-[10px] font-extrabold tracking-wider uppercase">
                {{ __('KOLEKSI PERPUSTAKAAN') }} &bull; {{ $totalKoleksi }} {{ __('Buku') }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                @if($activeKategori)
                    {{ $activeKategori->localized_name }}
                @else
                    {{ __('Perpustakaan Buku') }}
                @endif
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                @if(request('search'))
                    "{{ request('search') }}" ({{ $bukus->total() }} {{ __('Buku') }})
                @else
                    {{ $bukus->total() }} {{ __('judul buku tersedia dalam katalog') }}
                @endif
            </p>
        </div>

        <!-- Inline Catalog Search -->
        <form action="{{ route('perpustakaan.index') }}" method="GET" data-ajax-catalog-form="1" class="w-full md:w-80 lg:w-96 flex items-center relative">
            @if(request('kategori'))
                <input type="hidden" name="kategori" value="{{ request('kategori') }}">
            @endif
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            <i class="ph ph-magnifying-glass text-slate-400 text-base absolute left-3.5 pointer-events-none" aria-hidden="true"></i>
            <input 
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="{{ __('Cari judul buku, penulis, atau ISBN...') }}"
                aria-label="{{ __('Cari judul buku, penulis, atau ISBN...') }}"
                class="w-full min-h-[42px] rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 focus:border-blue-500 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 pl-10 pr-20 py-2.5 outline-none transition-colors shadow-sm"
            >
            <button type="submit" class="absolute right-1.5 px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors">
                {{ __('Cari') }}
            </button>
        </form>
    </section>

    <!-- Category Pills Filter Card -->
    <section aria-label="{{ __('Kategori') }}" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl border border-slate-200/90 dark:border-slate-800 p-3.5 sm:p-5 shadow-sm">
        <div class="flex items-center sm:flex-wrap gap-2 overflow-x-auto sm:overflow-visible no-scrollbar pb-0.5">
            <a 
                href="{{ route('perpustakaan.index', array_filter(['search' => request('search'), 'sort' => request('sort') !== 'terbaru' ? request('sort') : null])) }}"
                data-ajax-catalog="1"
                class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-colors {{ !request('kategori') ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-blue-400' }}"
            >
                {{ __('Semua') }}
            </a>
            @foreach($kategoris as $kat)
                <a 
                    href="{{ route('perpustakaan.index', array_filter(['kategori' => $kat->id, 'search' => request('search'), 'sort' => request('sort') !== 'terbaru' ? request('sort') : null])) }}"
                    data-ajax-catalog="1"
                    class="shrink-0 px-3.5 py-1.5 rounded-full text-xs transition-colors {{ ($activeKategori && $activeKategori->id === $kat->id) ? 'bg-blue-600 text-white font-bold shadow-sm' : 'bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-blue-400 font-medium' }}"
                >
                    {{ $kat->localized_name }}
                </a>
            @endforeach
        </div>
    </section>

    <!-- Sorting Pills Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 mr-1 shrink-0">{{ __('Urutkan:') }}</span>

            @php
                $sortOptions = [
                    'terbaru' => __('Terbaru'),
                    'populer' => __('Terpopuler'),
                    'rating' => __('Rating'),
                    'tersedia' => __('Stok Tersedia'),
                ];
            @endphp

            @foreach($sortOptions as $sortKey => $sortLabel)
                <a 
                    href="{{ route('perpustakaan.index', array_filter(['kategori' => request('kategori'), 'search' => request('search'), 'sort' => $sortKey])) }}"
                    data-ajax-catalog="1"
                    class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors {{ $sort === $sortKey ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-blue-400' }}"
                >
                    {{ $sortLabel }}
                </a>
            @endforeach
        </div>

        @if(request('search') || request('kategori') || $sort !== 'terbaru')
            <a 
                href="{{ route('perpustakaan.index') }}"
                data-ajax-catalog="1"
                class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 inline-flex items-center gap-1"
            >
                <i class="ph-bold ph-arrow-counter-clockwise" aria-hidden="true"></i>
                <span>{{ __('Reset Filter') }}</span>
            </a>
        @endif
    </div>

    <!-- Daftar buku katalog -->
    @if($bukus->isNotEmpty())
        <section class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-5">
            @foreach($bukus as $idx => $book)
                @include('partials.book-card', ['book' => $book, 'eager' => $idx < 5])
            @endforeach
        </section>

        @if($bukus->hasPages())
            <div class="pt-6">
                {{ $bukus->links() }}
            </div>
        @endif
    @else
        <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 py-14 px-6 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto text-2xl">
                <i class="ph ph-books" aria-hidden="true"></i>
            </div>
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
                {{ __('Tidak ada buku yang ditemukan') }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                {{ __('Coba gunakan kata kunci pencarian lain atau pilih kategori buku yang berbeda.') }}
            </p>
            <div class="pt-2">
                <a 
                    href="{{ route('perpustakaan.index') }}"
                    data-ajax-catalog="1"
                    class="min-h-[42px] px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold inline-flex items-center gap-2 transition-colors"
                >
                    <span>{{ __('Lihat Semua Koleksi') }}</span>
                </a>
            </div>
        </section>
    @endif

</div>
</div>
@endsection
