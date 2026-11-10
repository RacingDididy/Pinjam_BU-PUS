<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sekolah.png') }}">
    <title>{{ __('Verifikasi Email') }} - PINJAM BU-PUS</title>

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">

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
        digits: ['', '', '', '', '', ''],
        devCode: '{{ $isMailerLogOrLocal ? $user->verification_code : '' }}',
        submitting: false,
        resending: false,
        cooldown: 0,
        errorMsg: '',
        successMsg: '',
        get fullCode() {
            return this.digits.join('');
        },
        handleInput(idx, event) {
            const val = event.target.value.replace(/\D/g, '');
            this.digits[idx] = val ? val.slice(-1) : '';
            this.errorMsg = '';
            if (this.digits[idx] && idx < 5) {
                this.$refs['otp' + (idx + 1)]?.focus();
            }
            if (this.fullCode.length === 6) {
                this.submitOtp();
            }
        },
        handleKeydown(idx, event) {
            if (event.key === 'Backspace' && !this.digits[idx] && idx > 0) {
                this.$refs['otp' + (idx - 1)]?.focus();
            }
        },
        handlePaste(event) {
            const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (!pasted) return;
            event.preventDefault();
            for (let i = 0; i < 6; i++) {
                this.digits[i] = pasted[i] || '';
            }
            if (this.fullCode.length === 6) {
                this.submitOtp();
            }
        },
        fillDevCode() {
            if (!this.devCode) return;
            for (let i = 0; i < 6; i++) {
                this.digits[i] = this.devCode[i] || '';
            }
            this.submitOtp();
        },
        async submitOtp() {
            if (this.fullCode.length !== 6 || this.submitting) return;
            this.submitting = true;
            this.errorMsg = '';
            try {
                const res = await fetch('{{ route('verification.verify') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ code: this.fullCode })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.successMsg = data.message || 'Email berhasil diverifikasi!';
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '{{ route('profil.index') }}';
                    }, 450);
                } else {
                    this.errorMsg = data.message || 'Kode verifikasi tidak sesuai.';
                }
            } catch (e) {
                this.errorMsg = 'Koneksi internet tidak stabil. Silakan coba kembali.';
            } finally {
                this.submitting = false;
            }
        },
        async resendCode() {
            if (this.resending || this.cooldown > 0) return;
            this.resending = true;
            this.errorMsg = '';
            try {
                const res = await fetch('{{ route('verification.resend') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.successMsg = data.message;
                    if (data.dev_code) {
                        this.devCode = data.dev_code;
                    }
                    this.cooldown = 30;
                    const timer = setInterval(() => {
                        this.cooldown--;
                        if (this.cooldown <= 0) clearInterval(timer);
                    }, 1000);
                }
            } catch (e) {
                this.errorMsg = 'Gagal mengirim ulang kode. Periksa koneksi internet Anda.';
            } finally {
                this.resending = false;
            }
        }
    }"
    class="bg-[#f6f8fc] dark:bg-[#0b1120] text-slate-800 dark:text-slate-100 antialiased min-h-screen flex flex-col justify-between p-3 sm:p-6 transition-colors duration-200"
>

    <header class="w-full max-w-md mx-auto pt-1 pb-2 flex items-center justify-between gap-2">
        <a 
            href="{{ route('home') }}" 
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-blue-400 transition-colors shadow-sm"
        >
            <i class="ph-bold ph-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Beranda') }}</span>
        </a>

        <div class="flex items-center gap-1.5">
            <button 
                type="button"
                @click="toggleTheme()"
                class="w-8 h-8 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 flex items-center justify-center transition-colors shadow-sm"
            >
                <i class="ph-bold text-sm" :class="darkMode ? 'ph-sun text-amber-400' : 'ph-moon'" aria-hidden="true"></i>
            </button>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-rose-600 dark:text-rose-400 hover:border-rose-300 transition-colors shadow-sm">
                    {{ __('Keluar') }}
                </button>
            </form>
        </div>
    </header>

    <main class="w-full max-w-md mx-auto my-auto py-2">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl p-5 sm:p-7 space-y-5">

            <div class="text-center space-y-1.5">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/80 border border-blue-100 dark:border-blue-900 text-blue-600 dark:text-blue-400 flex items-center justify-center text-2xl mx-auto mb-2">
                    <i class="ph-fill ph-envelope-open" aria-hidden="true"></i>
                </div>
                <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Verifikasi Kode Email
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Masukkan 6 digit kode verifikasi yang telah kami kirim ke alamat email:
                    <strong class="block text-slate-800 dark:text-slate-200 mt-0.5">{{ $user->email }}</strong>
                </p>
            </div>

            <!-- Dev / Local Mailer Helper Banner -->
            <template x-if="devCode">
                <div class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/80 text-amber-900 dark:text-amber-200 text-xs space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold flex items-center gap-1.5">
                            <i class="ph-fill ph-info text-amber-500 text-base"></i>
                            <span>Mode Pengujian (MAIL_MAILER=log)</span>
                        </span>
                        <button 
                            type="button"
                            @click="fillDevCode()"
                            class="px-2.5 py-1 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] transition-colors"
                        >
                            Isi Otomatis
                        </button>
                    </div>
                    <p class="text-[11px] leading-relaxed">
                        Kode OTP Anda saat ini adalah <strong class="font-mono text-sm px-1.5 py-0.5 rounded bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700 tracking-widest" x-text="devCode"></strong>. Untuk mengirim langsung ke kotak masuk Gmail, isi <code class="font-mono">MAIL_MAILER=smtp</code>, <code class="font-mono">MAIL_USERNAME</code>, dan <code class="font-mono">MAIL_PASSWORD</code> (App Password Gmail) di file <code class="font-mono">.env</code>.
                    </p>
                </div>
            </template>

            <div x-cloak x-show="errorMsg" class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
                <i class="ph-fill ph-warning-circle text-base text-rose-500 shrink-0"></i>
                <span x-text="errorMsg"></span>
            </div>

            <div x-cloak x-show="successMsg" class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2">
                <i class="ph-fill ph-check-circle text-base text-emerald-500 shrink-0"></i>
                <span x-text="successMsg"></span>
            </div>

            <!-- 6-Digit OTP Input Form -->
            <form @submit.prevent="submitOtp()" class="space-y-4">
                <div class="grid grid-cols-6 gap-2 sm:gap-2.5" @paste="handlePaste($event)">
                    <template x-for="(digit, idx) in digits" :key="idx">
                        <input 
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            :x-ref="'otp' + idx"
                            :value="digits[idx]"
                            @input="handleInput(idx, $event)"
                            @keydown="handleKeydown(idx, $event)"
                            aria-label="Digit kode verifikasi"
                            class="w-full h-12 sm:h-14 text-center text-lg sm:text-xl font-extrabold rounded-2xl bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 focus:border-blue-600 focus:bg-white dark:focus:bg-slate-900 text-slate-900 dark:text-white outline-none transition-all"
                        >
                    </template>
                </div>

                <button 
                    type="submit"
                    :disabled="submitting || fullCode.length !== 6"
                    class="w-full min-h-[46px] rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-3 shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                >
                    <i class="ph-bold text-base" :class="submitting ? 'ph-spinner animate-spin' : 'ph-shield-check'" aria-hidden="true"></i>
                    <span x-text="submitting ? 'Memverifikasi Kode...' : 'Verifikasi & Lanjutkan'"></span>
                </button>
            </form>

            <div class="text-center pt-1 border-t border-slate-100 dark:border-slate-800 space-y-2">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Belum menerima kode verifikasi di Gmail?
                </p>
                <button 
                    type="button"
                    @click="resendCode()"
                    :disabled="resending || cooldown > 0"
                    class="text-xs font-extrabold text-blue-600 dark:text-blue-400 hover:underline disabled:opacity-50 inline-flex items-center gap-1.5"
                >
                    <i class="ph-bold" :class="resending ? 'ph-spinner animate-spin' : 'ph-arrows-clockwise'"></i>
                    <span x-text="cooldown > 0 ? ('Kirim Ulang dalam ' + cooldown + 'd') : 'Kirim Ulang Kode Verifikasi'"></span>
                </button>
            </div>

        </div>
    </main>

    <footer class="w-full max-w-md mx-auto text-center py-2 text-[11px] text-slate-400 dark:text-slate-500">
        &copy; {{ date('Y') }} PINJAM BU-PUS &bull; SMK Negeri 5 Surakarta
    </footer>

</body>
</html>

