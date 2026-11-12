<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sekolah.png') }}">
    <title>{{ __('Masuk / Daftar ke PINJAM BU-PUS') }}</title>

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
                    },
                    colors: {
                        slate: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#71717a',
                            600: '#52525b',
                            700: '#27272a',
                            800: '#18181b',
                            900: '#111113',
                            950: '#08080a',
                        }
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
        authStep: '{{ $initialMode ?? 'login' }}',
        authEmail: '{{ old('email', '') }}',
        authPassword: '',
        authPasswordConfirm: '',
        forgotMethod: 'email',
        forgotNisn: '',
        showLoginPass: false,
        showNewPass: false,
        showConfirmPass: false,
        authLoading: false,
        authError: '',
        authSuccessMsg: '',
        devVerifyUrl: '',
        verifyPollTimer: null,
        switchStep(step) {
            this.authError = '';
            this.authSuccessMsg = '';
            this.authStep = step;
            if (step === 'waiting_verification') {
                this.startPolling();
            } else {
                this.stopPolling();
            }
        },
        startPolling() {
            this.stopPolling();
            this.verifyPollTimer = setInterval(async () => {
                if (this.authStep !== 'waiting_verification') {
                    this.stopPolling();
                    return;
                }
                try {
                    const res = await fetch(`{{ route('auth.check.verification') }}?email=${encodeURIComponent(this.authEmail)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (res.ok && data.verified) {
                        this.stopPolling();
                        this.authPassword = '';
                        this.authPasswordConfirm = '';
                        this.authError = '';
                        this.authSuccessMsg = data.message || 'Verifikasi email berhasil! Silakan masukkan kata sandi Anda.';
                        this.authStep = 'set_password';
                    }
                } catch (e) {}
            }, 2500);
        },
        stopPolling() {
            if (this.verifyPollTimer) {
                clearInterval(this.verifyPollTimer);
                this.verifyPollTimer = null;
            }
        },
        async submitLogin() {
            this.authLoading = true;
            this.authError = '';
            this.authSuccessMsg = '';
            try {
                const res = await fetch('{{ route('login') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        email: this.authEmail,
                        password: this.authPassword,
                        remember: true
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.href = data.redirect || '{{ route('home') }}';
                } else if (data.needs_verification) {
                    this.devVerifyUrl = data.dev_verification_url || '';
                    this.authSuccessMsg = data.message;
                    this.switchStep('waiting_verification');
                } else {
                    this.authError = data.message || 'Email atau kata sandi tidak sesuai.';
                }
            } catch (e) {
                this.authError = 'Terjadi gangguan jaringan. Silakan coba kembali.';
            } finally {
                this.authLoading = false;
            }
        },
        async submitRegisterEmail() {
            if (this.authLoading) return;
            this.authLoading = true;
            this.authError = '';
            this.authSuccessMsg = '';
            try {
                const res = await fetch('{{ route('register') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ email: this.authEmail })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.devVerifyUrl = data.dev_verification_url || '';
                    this.authSuccessMsg = data.message;
                    this.switchStep('waiting_verification');
                } else if (data.already_registered) {
                    this.authError = data.message;
                    this.authStep = 'login';
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                    this.authError = firstErr || data.message || 'Gagal mengirim email verifikasi.';
                }
            } catch (e) {
                this.authError = 'Terjadi gangguan jaringan.';
            } finally {
                this.authLoading = false;
            }
        },
        async submitForgotPassword() {
            if (this.authLoading) return;
            this.authLoading = true;
            this.authError = '';
            this.authSuccessMsg = '';
            try {
                const res = await fetch('{{ route('auth.password.forgot') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        email: this.authEmail,
                        method: this.forgotMethod,
                        nisn: this.forgotNisn
                    })
                });
                const data = await res.json();
                if (res.ok && data.verified) {
                    this.authPassword = '';
                    this.authPasswordConfirm = '';
                    this.authSuccessMsg = data.message || 'Identitas terverifikasi! Silakan buat kata sandi baru.';
                    this.switchStep('set_password');
                } else if (res.ok && data.success) {
                    this.devVerifyUrl = data.dev_verification_url || '';
                    this.authSuccessMsg = data.message || 'Tautan pemulihan kata sandi telah dikirim ke email Anda.';
                    this.switchStep('waiting_verification');
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                    this.authError = firstErr || data.message || 'Gagal memproses pemulihan kata sandi.';
                }
            } catch (e) {
                this.authError = 'Terjadi gangguan jaringan.';
            } finally {
                this.authLoading = false;
            }
        },
        async triggerDirectVerify() {
            if (!this.devVerifyUrl || this.authLoading) return;
            this.authLoading = true;
            try {
                const res = await fetch(this.devVerifyUrl, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (res.ok && data.verified) {
                    this.stopPolling();
                    this.authPassword = '';
                    this.authPasswordConfirm = '';
                    this.authError = '';
                    this.authSuccessMsg = data.message;
                    this.authStep = 'set_password';
                }
            } catch (e) {
                window.location.href = this.devVerifyUrl;
            } finally {
                this.authLoading = false;
            }
        },
        async resendEmail() {
            if (this.authLoading) return;
            this.authLoading = true;
            this.authError = '';
            try {
                const res = await fetch('{{ route('verification.resend') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ email: this.authEmail })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.devVerifyUrl = data.dev_verification_url || this.devVerifyUrl;
                    this.authSuccessMsg = data.message || 'Tautan verifikasi baru berhasil dikirim.';
                } else {
                    this.authError = data.message || 'Gagal mengirim ulang email.';
                }
            } catch (e) {
                this.authError = 'Terjadi gangguan jaringan.';
            } finally {
                this.authLoading = false;
            }
        },
        async submitPasswordSetup() {
            if (this.authLoading) return;
            const pwd = this.authPassword || '';
            if (pwd.length < 8) {
                this.authError = 'Kata sandi minimal 8 karakter.';
                return;
            }
            if (!/[a-zA-Z]/.test(pwd) || !/\d/.test(pwd)) {
                this.authError = 'Kata sandi wajib mengandung huruf dan minimal 1 angka.';
                return;
            }
            if (pwd !== this.authPasswordConfirm) {
                this.authError = 'Konfirmasi kata sandi tidak cocok.';
                return;
            }
            this.authLoading = true;
            this.authError = '';
            try {
                const res = await fetch('{{ route('auth.password.setup') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        password: this.authPassword,
                        password_confirmation: this.authPasswordConfirm
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.href = data.redirect_url || '{{ route('home') }}';
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                    this.authError = firstErr || data.message || 'Gagal menyimpan kata sandi.';
                }
            } catch (e) {
                this.authError = 'Terjadi gangguan jaringan.';
            } finally {
                this.authLoading = false;
            }
        }
    }"
    class="bg-[#f6f8fc] dark:bg-[#08080a] text-slate-800 dark:text-slate-100 antialiased min-h-screen flex flex-col justify-between p-3 sm:p-6 transition-colors duration-200 selection:bg-blue-600 selection:text-white"
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
            >
                <i class="ph-bold ph-globe text-xs" aria-hidden="true"></i>
                <span class="uppercase">{{ app()->getLocale() }}</span>
            </a>

            <button 
                type="button"
                @click="toggleTheme()"
                class="w-8 h-8 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 flex items-center justify-center transition-colors shadow-sm"
            >
                <i class="ph-bold text-sm" :class="darkMode ? 'ph-sun text-amber-400' : 'ph-moon'" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <!-- Main Centered Card (Matches Modal Design) -->
    <main class="w-full max-w-md mx-auto my-auto py-2">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl p-5 sm:p-7 space-y-4">

            <div class="text-center pt-1">
                <img 
                    src="{{ asset('images/logo-sekolah.png') }}" 
                    alt="Logo SMK Negeri 5 Surakarta" 
                    width="48" 
                    height="48" 
                    class="w-12 h-12 object-contain mx-auto mb-2.5 drop-shadow-sm"
                >
                <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    <span x-show="authStep === 'login'">{{ __('Masuk ke PINJAM BU-PUS') }}</span>
                    <span x-show="authStep === 'register'">{{ __('Daftar ke PINJAM BU-PUS') }}</span>
                    <span x-show="authStep === 'forgot_password'">{{ __('Pemulihan Kata Sandi') }}</span>
                    <span x-show="authStep === 'waiting_verification'">{{ __('Verifikasi Email Anda') }}</span>
                    <span x-show="authStep === 'set_password'">{{ __('Buat Kata Sandi Akun') }}</span>
                </h1>
                <p x-show="authStep === 'login'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Silakan masuk terlebih dahulu untuk mengakses layanan perpustakaan.
                </p>
                <p x-show="authStep === 'register'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Masukkan email aktif Anda. Sistem otomatis mengirimkan tombol verifikasi ke Gmail Anda.
                </p>
                <p x-show="authStep === 'forgot_password'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Lupa kata sandi akun? Pilih metode verifikasi via Email Gmail atau NISN warga sekolah untuk mereset kata sandi.
                </p>
                <p x-show="authStep === 'waiting_verification'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Tautan verifikasi telah dikirim ke <strong class="text-blue-600 dark:text-blue-400" x-text="authEmail"></strong>.
                </p>
                <p x-show="authStep === 'set_password'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Akun <strong class="text-emerald-600 dark:text-emerald-400" x-text="authEmail"></strong> berhasil diverifikasi! Silakan buat kata sandi baru Anda.
                </p>
            </div>

            <div 
                x-show="authError"
                x-transition
                class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2"
            >
                <i class="ph-fill ph-warning-circle text-base text-rose-500 shrink-0" aria-hidden="true"></i>
                <span x-text="authError"></span>
            </div>

            <div 
                x-show="authSuccessMsg && !authError"
                x-transition
                class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2"
            >
                <i class="ph-fill ph-check-circle text-base text-emerald-500 shrink-0" aria-hidden="true"></i>
                <span x-text="authSuccessMsg"></span>
            </div>

            <!-- Form login -->
            <div x-show="authStep === 'login'" class="space-y-3">
                <form @submit.prevent="submitLogin()" class="space-y-3 text-left">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                        <input 
                            type="email"
                            x-model="authEmail"
                            required
                            placeholder="email@example.com"
                            class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                        >
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Kata Sandi') }}</label>
                            <button type="button" @click="switchStep('forgot_password')" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline font-semibold">{{ __('Lupa sandi?') }}</button>
                        </div>
                        <div class="relative">
                            <input 
                                :type="showLoginPass ? 'text' : 'password'"
                                x-model="authPassword"
                                required
                                placeholder="••••••••"
                                class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-3.5 pr-10 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                            >
                            <button 
                                type="button" 
                                @click="showLoginPass = !showLoginPass"
                                :aria-label="showLoginPass ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center transition-colors"
                            >
                                <i class="ph-bold text-base" :class="showLoginPass ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-1">
                        <button 
                            type="submit"
                            :disabled="authLoading"
                            class="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-2.5 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <i class="ph-bold text-base" :class="authLoading ? 'ph-spinner animate-spin' : 'ph-sign-in'" aria-hidden="true"></i>
                            <span x-text="authLoading ? '{{ __('Memverifikasi...') }}' : '{{ __('Masuk Sekarang') }}'"></span>
                        </button>
                    </div>
                </form>

                <div class="text-center pt-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                    {{ __('Belum punya akun warga sekolah?') }}
                    <button type="button" @click="switchStep('register')" class="font-bold text-blue-600 dark:text-blue-400 hover:underline ml-0.5">
                        {{ __('Daftar & Verifikasi Email') }}
                    </button>
                </div>
            </div>

            <!-- Form daftar -->
            <div x-show="authStep === 'register'" class="space-y-3" style="display: none;">
                <form @submit.prevent="submitRegisterEmail()" class="space-y-3 text-left">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email Aktif (Gmail)</label>
                        <input 
                            type="email"
                            x-model="authEmail"
                            required
                            placeholder="nama@gmail.com"
                            class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                        >
                    </div>

                    <div class="pt-1">
                        <button 
                            type="submit"
                            :disabled="authLoading"
                            class="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-2.5 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <i class="ph-bold text-base" :class="authLoading ? 'ph-spinner animate-spin' : 'ph-paper-plane-tilt'" aria-hidden="true"></i>
                            <span x-text="authLoading ? 'Mengirim Verifikasi...' : 'Kirim Verifikasi ke Email'"></span>
                        </button>
                    </div>
                </form>

                <div class="text-center pt-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                    Sudah punya akun warga sekolah?
                    <button type="button" @click="switchStep('login')" class="font-bold text-blue-600 dark:text-blue-400 hover:underline ml-0.5">
                        Masuk Sekarang
                    </button>
                </div>
            </div>

            <!-- Form lupa kata sandi -->
            <div x-show="authStep === 'forgot_password'" class="space-y-3" style="display: none;">
                <!-- Tab metode pemulihan -->
                <div class="grid grid-cols-2 gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80">
                    <button 
                        type="button" 
                        @click="forgotMethod = 'email'; authError = ''"
                        class="py-2 px-2.5 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5"
                        :class="forgotMethod === 'email' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'"
                    >
                        <i class="ph-bold ph-envelope-simple text-sm"></i>
                        <span>Via Email Gmail</span>
                    </button>
                    <button 
                        type="button" 
                        @click="forgotMethod = 'nisn'; authError = ''"
                        class="py-2 px-2.5 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5"
                        :class="forgotMethod === 'nisn' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'"
                    >
                        <i class="ph-bold ph-identification-card text-sm"></i>
                        <span>Via NISN Sekolah</span>
                    </button>
                </div>

                <form @submit.prevent="submitForgotPassword()" class="space-y-3 text-left">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email Akun Terdaftar</label>
                        <input 
                            type="email"
                            x-model="authEmail"
                            required
                            placeholder="nama@gmail.com"
                            class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                        >
                    </div>

                    <div x-show="forgotMethod === 'nisn'" x-transition class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Nomor NISN (10 Digit Angka)</label>
                        <input 
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            x-model="forgotNisn"
                            @input="forgotNisn = forgotNisn.replace(/\D/g, '').slice(0, 10)"
                            :required="forgotMethod === 'nisn'"
                            placeholder="Contoh: 0078123456"
                            class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors font-mono"
                        >
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Cocokkan dengan NISN yang sudah tersimpan di profil akun Anda untuk langsung membuat kata sandi baru.
                        </p>
                    </div>

                    <div x-show="forgotMethod === 'email'" class="p-2.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/70 dark:border-blue-800/60 text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                        Sistem akan mengirimkan tautan pemulihan ke kotak masuk <strong>Gmail</strong> Anda untuk membuat kata sandi baru.
                    </div>

                    <div class="pt-1">
                        <button 
                            type="submit"
                            :disabled="authLoading"
                            class="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-2.5 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <i class="ph-bold text-base" :class="authLoading ? 'ph-spinner animate-spin' : (forgotMethod === 'nisn' ? 'ph-shield-check' : 'ph-paper-plane-tilt')" aria-hidden="true"></i>
                            <span x-text="authLoading ? 'Memproses...' : (forgotMethod === 'nisn' ? 'Verifikasi NISN & Reset Sandi' : 'Kirim Tautan Reset ke Email')"></span>
                        </button>
                    </div>
                </form>

                <div class="text-center pt-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                    Ingat kata sandi Anda?
                    <button type="button" @click="switchStep('login')" class="font-bold text-blue-600 dark:text-blue-400 hover:underline ml-0.5">
                        Kembali ke Login
                    </button>
                </div>
            </div>

            <!-- Menunggu verifikasi email -->
            <div x-show="authStep === 'waiting_verification'" class="space-y-3.5 text-center" style="display: none;">
                <div class="p-4 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/80 space-y-2.5">
                    <div class="w-11 h-11 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto text-xl shadow-sm">
                        <i class="ph-bold ph-envelope-open animate-bounce" aria-hidden="true"></i>
                    </div>
                    <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed">
                        Silakan buka kotak masuk <strong>Gmail</strong> Anda lalu tekan tombol <strong>"Konfirmasi Email Saya"</strong>.
                    </p>
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-bold text-blue-600 dark:text-blue-400">
                        <i class="ph-bold ph-spinner animate-spin" aria-hidden="true"></i>
                        <span>Menunggu Anda menekan tombol verifikasi di email...</span>
                    </div>
                </div>

                <div x-show="devVerifyUrl" class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 space-y-2">
                    <p class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                        Mode Simulasi / Demo: Klik tombol di bawah untuk langsung memverifikasi email tanpa membuka Gmail:
                    </p>
                    <button 
                        type="button"
                        @click="triggerDirectVerify()"
                        :disabled="authLoading"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs inline-flex items-center justify-center gap-1.5 shadow-sm transition-colors"
                    >
                        <i class="ph-bold ph-check-circle text-sm" aria-hidden="true"></i>
                        <span>Verifikasi Email Sekarang (Klik Langsung)</span>
                    </button>
                </div>

                <div class="flex items-center justify-between gap-2 pt-1 text-xs">
                    <button type="button" @click="resendEmail()" :disabled="authLoading" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                        Kirim Ulang Email
                    </button>
                    <button type="button" @click="switchStep('register')" class="font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        Ganti Alamat Email
                    </button>
                </div>
            </div>

            <!-- Form buat password -->
            <div x-show="authStep === 'set_password'" class="space-y-3" style="display: none;">
                <form @submit.prevent="submitPasswordSetup()" class="space-y-3 text-left">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Baru</label>
                        <div class="relative">
                            <input 
                                :type="showNewPass ? 'text' : 'password'"
                                x-model="authPassword"
                                required
                                minlength="8"
                                placeholder="Minimal 8 karakter & terdapat 1 angka"
                                class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-3.5 pr-10 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                            >
                            <button 
                                type="button" 
                                @click="showNewPass = !showNewPass"
                                :aria-label="showNewPass ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center transition-colors"
                            >
                                <i class="ph-bold text-base" :class="showNewPass ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>

                        <!-- Indikator Ketentuan Password -->
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold transition-colors"
                                :class="(authPassword || '').length >= 8 ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                            >
                                <i class="ph-bold" :class="(authPassword || '').length >= 8 ? 'ph-check-circle' : 'ph-circle'"></i>
                                <span>Minimal 8 huruf/karakter</span>
                            </span>
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold transition-colors"
                                :class="/[a-zA-Z]/.test(authPassword || '') && /\d/.test(authPassword || '') ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                            >
                                <i class="ph-bold" :class="/[a-zA-Z]/.test(authPassword || '') && /\d/.test(authPassword || '') ? 'ph-check-circle' : 'ph-circle'"></i>
                                <span>Terdapat minimal 1 angka</span>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Konfirmasi Kata Sandi</label>
                        <div class="relative">
                            <input 
                                :type="showConfirmPass ? 'text' : 'password'"
                                x-model="authPasswordConfirm"
                                required
                                minlength="8"
                                placeholder="Ulangi kata sandi baru"
                                class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-3.5 pr-10 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                            >
                            <button 
                                type="button" 
                                @click="showConfirmPass = !showConfirmPass"
                                :aria-label="showConfirmPass ? 'Sembunyikan konfirmasi sandi' : 'Lihat konfirmasi sandi'"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center transition-colors"
                            >
                                <i class="ph-bold text-base" :class="showConfirmPass ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-1">
                        <button 
                            type="submit"
                            :disabled="authLoading"
                            class="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-2.5 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <i class="ph-bold text-base" :class="authLoading ? 'ph-spinner animate-spin' : 'ph-check-circle'" aria-hidden="true"></i>
                            <span x-text="authLoading ? 'Menyimpan Kata Sandi...' : 'Simpan Kata Sandi & Masuk'"></span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>

    <footer class="w-full max-w-md mx-auto text-center py-2 text-[11px] text-slate-400 dark:text-slate-500">
        &copy; {{ date('Y') }} PINJAM BU-PUS &bull; SMK Negeri 5 Surakarta
    </footer>

</body>
</html>
