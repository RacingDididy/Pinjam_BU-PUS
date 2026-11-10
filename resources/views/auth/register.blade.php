<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sekolah.png') }}">
    <title>{{ __('Daftar Akun') }} - PINJAM BU-PUS</title>

    <!-- Prevent Flash of Incorrect Theme -->
    <script>
        (function () {
            try {
                if (localStorage.getItem('pinjam_theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        a[href], button:not(:disabled) { cursor: pointer; }
        :focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
    </style>
</head>
<body 
    x-data="{
        darkMode: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('pinjam_theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('pinjam_theme', 'light');
            }
        },
        showPassword: false,
        submitting: false
    }"
    class="bg-[#f6f8fc] dark:bg-[#0b1120] text-slate-800 dark:text-slate-100 antialiased min-h-screen flex flex-col justify-between p-3 sm:p-6 transition-colors duration-200 selection:bg-blue-600 selection:text-white"
>

    <!-- Top Compact Navigation Bar -->
    <header class="w-full max-w-md mx-auto pt-1 pb-2 flex items-center justify-between gap-2">
        <a 
            href="{{ route('home') }}" 
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-blue-400 transition-colors shadow-sm"
        >
            <i class="ph-bold ph-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Beranda') }}</span>
        </a>

        <div class="flex items-center gap-1.5">
            <a 
                href="{{ route('locale.switch', app()->getLocale() === 'id' ? 'en' : 'id') }}"
                class="h-8 px-2.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 inline-flex items-center gap-1 text-[11px] font-bold transition-colors shadow-sm"
                title="{{ __('Bahasa') }}"
            >
                <i class="ph-bold ph-globe text-xs" aria-hidden="true"></i>
                <span class="uppercase">{{ app()->getLocale() }}</span>
            </a>

            <button 
                type="button"
                @click="toggleTheme()"
                :aria-label="darkMode ? '{{ __('Tema Terang') }}' : '{{ __('Tema Gelap') }}'"
                class="w-8 h-8 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 flex items-center justify-center transition-colors shadow-sm"
            >
                <i class="ph-bold text-sm" :class="darkMode ? 'ph-sun text-amber-400' : 'ph-moon'" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <!-- Main Centered Compact Register Card -->
    <main class="w-full max-w-md mx-auto my-auto py-2">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl p-5 sm:p-7 space-y-4">

            <!-- Brand Header with School Logo -->
            <div class="text-center space-y-1.5">
                <a href="{{ route('home') }}" class="inline-flex flex-col items-center gap-1.5 group">
                    <img 
                        src="{{ asset('images/logo-sekolah.png') }}" 
                        alt="Logo SMK Negeri 5 Surakarta" 
                        width="52" 
                        height="52" 
                        class="w-12 h-12 sm:w-14 sm:h-14 object-contain drop-shadow-sm transition-transform duration-200 group-hover:scale-105"
                    >
                    <span class="font-extrabold text-base sm:text-lg tracking-tight text-slate-900 dark:text-white">
                        PINJAM <span class="text-blue-600 dark:text-blue-400">BU-PUS</span>
                    </span>
                </a>
                <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ __('Buat Akun Pembaca Baru') }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    {{ __('Daftar menggunakan email aktif untuk menerima 6 digit kode verifikasi pendaftaran.') }}
                </p>
            </div>

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-medium space-y-1">
                    @foreach ($errors->all() as $error)
                        <p class="flex items-start gap-1.5">
                            <i class="ph-fill ph-warning-circle text-sm text-rose-500 shrink-0 mt-0.5" aria-hidden="true"></i>
                            <span>{{ $error }}</span>
                        </p>
                    @endforeach
                </div>
            @endif

            <!-- Register Form -->
            <form action="{{ route('register') }}" method="POST" @submit="submitting = true" class="space-y-3">
                @csrf

                <!-- Nama Lengkap -->
                <div>
                    <label for="reg-name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('Nama Lengkap') }}
                    </label>
                    <div class="relative">
                        <i class="ph ph-user text-slate-400 text-base absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true"></i>
                        <input 
                            id="reg-name"
                            type="text" 
                            name="name" 
                            value="{{ old('name') }}" 
                            required 
                            autocomplete="name"
                            placeholder="{{ __('Nama Lengkap Anda') }}" 
                            class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 rounded-xl pl-10 pr-3.5 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 outline-none transition-colors"
                        >
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="reg-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Email
                    </label>
                    <div class="relative">
                        <i class="ph ph-envelope-simple text-slate-400 text-base absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true"></i>
                        <input 
                            id="reg-email"
                            type="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required 
                            autocomplete="email"
                            placeholder="nama@email.com" 
                            class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 rounded-xl pl-10 pr-3.5 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 outline-none transition-colors"
                        >
                    </div>
                </div>

                <!-- Kata Sandi & Konfirmasi (Compact Grid on sm+, Stacked on xs) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="reg-password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Kata Sandi') }}
                        </label>
                        <div class="relative">
                            <i class="ph ph-lock-key text-slate-400 text-base absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true"></i>
                            <input 
                                id="reg-password"
                                :type="showPassword ? 'text' : 'password'" 
                                name="password" 
                                required 
                                autocomplete="new-password"
                                placeholder="{{ __('Min. 6 karakter') }}" 
                                class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 rounded-xl pl-10 pr-9 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 outline-none transition-colors"
                            >
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                aria-label="Toggle password"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                            >
                                <i :class="showPassword ? 'ph-bold ph-eye-slash' : 'ph-bold ph-eye'" class="text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="reg-password-confirm" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Ulangi Sandi') }}
                        </label>
                        <div class="relative">
                            <i class="ph ph-check-circle text-slate-400 text-base absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true"></i>
                            <input 
                                id="reg-password-confirm"
                                :type="showPassword ? 'text' : 'password'" 
                                name="password_confirmation" 
                                required 
                                autocomplete="new-password"
                                placeholder="{{ __('Ulangi Kata Sandi') }}" 
                                class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 rounded-xl pl-10 pr-3.5 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 outline-none transition-colors"
                            >
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-1">
                    <button 
                        type="submit" 
                        :disabled="submitting"
                        class="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold py-2.5 rounded-xl shadow-sm transition-all text-xs sm:text-sm flex items-center justify-center gap-2 disabled:opacity-60"
                    >
                        <i class="ph-bold text-base" :class="submitting ? 'ph-spinner animate-spin' : 'ph-user-plus'" aria-hidden="true"></i>
                        <span x-text="submitting ? '{{ __('Memproses...') }}' : '{{ __('Daftar Sekarang') }}'"></span>
                    </button>
                </div>
            </form>

            <!-- Switch to Login -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-center text-xs text-slate-500 dark:text-slate-400">
                {{ __('Sudah punya akun?') }}
                <a href="{{ route('login') }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                    {{ __('Masuk di sini') }}
                </a>
            </div>

        </div>
    </main>

    <!-- Compact Footer -->
    <footer class="w-full max-w-md mx-auto text-center py-2 text-[11px] text-slate-400 dark:text-slate-500">
        &copy; {{ date('Y') }} PINJAM BU-PUS &bull; SMK Negeri 5 Surakarta
    </footer>

</body>
</html>
