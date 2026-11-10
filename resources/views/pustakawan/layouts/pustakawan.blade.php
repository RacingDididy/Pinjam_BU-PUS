<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sekolah.png') }}">
    <title>@yield('title', 'Dashboard Pustakawan') - PINJAM BU-PUS</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes shimmerSlide {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }
        .animate-shimmer-bar {
            animation: shimmerSlide 1.5s infinite ease-in-out;
        }
    </style>
</head>
<body 
    x-data="{
        sidebarOpen: false,
        networkBusy: false,
        toastMessage: '',
        toastType: 'success',
        toastTimer: null,
        showToast(msg, type = 'success') {
            this.toastMessage = msg;
            this.toastType = type;
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toastMessage = ''; }, 3600);
        },
        initNet() {
            const origFetch = window.fetch;
            const self = this;
            window.fetch = async function(...args) {
                self.networkBusy = true;
                try {
                    return await origFetch.apply(this, args);
                } finally {
                    self.networkBusy = false;
                }
            };
        }
    }"
    x-init="initNet()"
    @notify-toast.window="showToast($event.detail?.message, $event.detail?.type || 'success')"
    class="bg-[#F8FAFC] text-slate-800 antialiased min-h-screen flex pb-16 lg:pb-0"
>

    <!-- Global Top Loading Bar -->
    <div x-cloak x-show="networkBusy" class="fixed top-0 inset-x-0 z-[70] h-1 bg-emerald-100 overflow-hidden pointer-events-none">
        <div class="h-full w-2/5 bg-gradient-to-r from-emerald-500 via-blue-500 to-teal-400 rounded-full animate-shimmer-bar"></div>
    </div>

    <!-- Floating Toast -->
    <div 
        x-cloak
        x-show="toastMessage"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        class="fixed top-16 sm:top-20 right-3 sm:right-6 left-3 sm:left-auto z-[100] sm:max-w-sm rounded-2xl p-3 sm:p-3.5 shadow-2xl border text-xs sm:text-sm font-bold flex items-center gap-2.5"
        :class="toastType === 'success' ? 'bg-slate-900 text-white border-slate-700/80' : 'bg-rose-950 text-white border-rose-800'"
    >
        <i class="text-lg shrink-0" :class="toastType === 'success' ? 'ph-fill ph-check-circle text-emerald-400' : 'ph-fill ph-warning-circle text-rose-400'"></i>
        <span class="flex-1 leading-snug" x-text="toastMessage"></span>
        <button type="button" @click="toastMessage = ''" class="text-slate-400 hover:text-white p-1 shrink-0"><i class="ph-bold ph-x"></i></button>
    </div>

    <!-- Mobile Backdrop -->
    <div 
        x-cloak
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-40 lg:hidden"
    ></div>

    <!-- Sidebar -->
    <aside 
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed lg:sticky top-0 left-0 z-50 w-64 bg-[#09090b] text-white flex flex-col justify-between p-5 shrink-0 h-screen transition-transform duration-200 border-r border-zinc-800"
    >
        <div>
            <div class="flex items-center justify-between gap-2 px-1 py-2">
                <a href="{{ route('pustakawan.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                    <img 
                        src="{{ asset('images/logo-sekolah.png') }}" 
                        alt="Logo SMK Negeri 5 Surakarta" 
                        width="38" 
                        height="38" 
                        class="w-9 h-9 object-contain shrink-0 drop-shadow-sm"
                    >
                    <div class="min-w-0">
                        <span class="font-extrabold text-sm tracking-tight text-white block truncate">
                            PINJAM <span class="text-emerald-400">BU-PUS</span>
                        </span>
                        <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block">Panel Pustakawan</span>
                    </div>
                </a>
                <button type="button" @click="sidebarOpen = false" class="lg:hidden text-zinc-400 hover:text-white p-1">
                    <i class="ph-bold ph-x text-lg"></i>
                </button>
            </div>

            <!-- Menu navigasi -->
            <div class="mt-6">
                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest px-3 block mb-2.5">
                    MENU UTAMA PUSTAKAWAN
                </span>
                <nav class="space-y-1.5">
                    <a 
                        href="{{ route('pustakawan.dashboard') }}" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all {{ request()->routeIs('pustakawan.dashboard') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-zinc-300 hover:text-white hover:bg-zinc-800/80' }}"
                    >
                        <i class="ph-bold ph-squares-four text-lg"></i>
                        <span>Ringkasan &amp; Statistik</span>
                    </a>

                    <a 
                        href="{{ route('pustakawan.laporan') }}" 
                        class="flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all {{ request()->routeIs('pustakawan.laporan') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-zinc-300 hover:text-white hover:bg-zinc-800/80' }}"
                    >
                        <span class="flex items-center gap-3 min-w-0">
                            <i class="ph-bold ph-clipboard-text text-lg shrink-0"></i>
                            <span class="truncate">Laporan &amp; Persetujuan</span>
                        </span>
                    </a>

                    <a 
                        href="{{ route('pustakawan.buku.index') }}" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all {{ request()->routeIs('pustakawan.buku.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-zinc-300 hover:text-white hover:bg-zinc-800/80' }}"
                    >
                        <i class="ph-bold ph-books text-lg"></i>
                        <span>Kelola Katalog Buku</span>
                    </a>

                    <a 
                        href="{{ route('pustakawan.kategori.index') }}" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all {{ request()->routeIs('pustakawan.kategori.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-zinc-300 hover:text-white hover:bg-zinc-800/80' }}"
                    >
                        <i class="ph-bold ph-folders text-lg"></i>
                        <span>Kelola Kategori Buku</span>
                    </a>

                    <a 
                        href="{{ route('pustakawan.warga.index') }}" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all {{ request()->routeIs('pustakawan.warga.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-zinc-300 hover:text-white hover:bg-zinc-800/80' }}"
                    >
                        <i class="ph-bold ph-identification-badge text-lg"></i>
                        <span>Pantau Warga Sekolah</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Kembali ke beranda -->
        <div class="pt-4 border-t border-zinc-800">
            <a 
                href="{{ route('home') }}" 
                class="flex items-center justify-center gap-2 w-full bg-zinc-900 hover:bg-zinc-800 text-zinc-200 hover:text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-all border border-zinc-800"
            >
                <i class="ph-bold ph-arrow-square-out text-base"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </aside>

    <!-- Konten utama -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Topbar -->
        <header class="bg-white border-b border-slate-200/80 px-3.5 sm:px-7 py-3 sm:py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <button type="button" @click="sidebarOpen = true" class="lg:hidden w-9 h-9 rounded-xl border border-slate-200 flex items-center justify-center text-slate-700 shrink-0">
                    <i class="ph-bold ph-list text-lg"></i>
                </button>
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-lg font-extrabold text-slate-900 tracking-tight truncate">
                        @yield('page-title', 'Dashboard Pustakawan')
                    </h1>
                    <p class="text-[11px] text-slate-500 hidden sm:block truncate">
                        Perpustakaan SMK Negeri 5 Surakarta &bull; Panel Operasional Pustakawan
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span class="hidden sm:inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-extrabold px-3 py-1 rounded-full tracking-wider">
                    <i class="ph-fill ph-book-open-text text-xs"></i>
                    PUSTAKAWAN
                </span>
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-emerald-700 text-white font-extrabold text-xs flex items-center justify-center shadow-sm">
                    {{ auth()->user()?->initial ?? 'P' }}
                </div>
            </div>
        </header>

        @if(session('success'))
            <div x-init="showToast('{{ addslashes(session('success')) }}', 'success')"></div>
        @endif
        @if(session('error'))
            <div x-init="showToast('{{ addslashes(session('error')) }}', 'error')"></div>
        @endif
        @if(session('info'))
            <div x-init="showToast('{{ addslashes(session('info')) }}', 'success')"></div>
        @endif

        <main class="p-3 sm:p-6 lg:p-7 flex-1 max-w-7xl w-full mx-auto">
            @yield('content')
        </main>

    </div>

    <!-- Navigasi bawah mobile -->
    <nav aria-label="Menu Mobile Pustakawan" class="lg:hidden fixed bottom-0 inset-x-0 z-40 h-16 bg-white/95 backdrop-blur-md border-t border-slate-200 grid grid-cols-5 items-center px-1">
        <a 
            href="{{ route('pustakawan.dashboard') }}" 
            class="flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[10px] font-bold transition-colors {{ request()->routeIs('pustakawan.dashboard') ? 'text-emerald-600' : 'text-slate-500' }}"
        >
            <i class="text-lg {{ request()->routeIs('pustakawan.dashboard') ? 'ph-fill ph-squares-four' : 'ph-bold ph-squares-four' }}"></i>
            <span>Ringkasan</span>
        </a>
        <a 
            href="{{ route('pustakawan.laporan') }}" 
            class="flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[10px] font-bold transition-colors {{ request()->routeIs('pustakawan.laporan') ? 'text-emerald-600' : 'text-slate-500' }}"
        >
            <i class="text-lg {{ request()->routeIs('pustakawan.laporan') ? 'ph-fill ph-clipboard-text' : 'ph-bold ph-clipboard-text' }}"></i>
            <span>Laporan</span>
        </a>
        <a 
            href="{{ route('pustakawan.buku.index') }}" 
            class="flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[10px] font-bold transition-colors {{ request()->routeIs('pustakawan.buku.*') ? 'text-emerald-600' : 'text-slate-500' }}"
        >
            <i class="text-lg {{ request()->routeIs('pustakawan.buku.*') ? 'ph-fill ph-books' : 'ph-bold ph-books' }}"></i>
            <span>Buku</span>
        </a>
        <a 
            href="{{ route('pustakawan.kategori.index') }}" 
            class="flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[10px] font-bold transition-colors {{ request()->routeIs('pustakawan.kategori.*') ? 'text-emerald-600' : 'text-slate-500' }}"
        >
            <i class="text-lg {{ request()->routeIs('pustakawan.kategori.*') ? 'ph-fill ph-folders' : 'ph-bold ph-folders' }}"></i>
            <span>Kategori</span>
        </a>
        <a 
            href="{{ route('pustakawan.warga.index') }}" 
            class="flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[10px] font-bold transition-colors {{ request()->routeIs('pustakawan.warga.*') ? 'text-emerald-600' : 'text-slate-500' }}"
        >
            <i class="text-lg {{ request()->routeIs('pustakawan.warga.*') ? 'ph-fill ph-identification-badge' : 'ph-bold ph-identification-badge' }}"></i>
            <span>Warga</span>
        </a>
    </nav>

</body>
</html>
