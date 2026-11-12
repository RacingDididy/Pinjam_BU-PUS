<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sekolah.png') }}">
    <title>@yield('title', 'PINJAM BU-PUS - ' . __('Perpustakaan Buku'))</title>

    <!-- Prevent Flash of Incorrect Theme -->
    <script>
        (function () {
            try {
                const savedTheme = localStorage.getItem('pinjam_theme');
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Phosphor Icons CSS (Direct stylesheets for zero layout shift & instant rendering) -->
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">

    <!-- Tailwind CDN -->
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
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        a[href], button:not(:disabled), summary, select, label[for], [role="button"] { cursor: pointer; }
        button:disabled, input:disabled, textarea:disabled { cursor: not-allowed; }
        :focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
        @keyframes shimmerSlide {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }
        .animate-shimmer-bar {
            animation: shimmerSlide 1.5s infinite ease-in-out;
        }
        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
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
        networkBusy: false,
        activeRequests: 0,
        startNetworkIndicator() {
            this.activeRequests++;
            this.networkBusy = true;
        },
        stopNetworkIndicator() {
            this.activeRequests = Math.max(0, this.activeRequests - 1);
            if (this.activeRequests === 0) {
                this.networkBusy = false;
            }
        },
        reloadPreservingScroll(targetUrl = null) {
            try {
                sessionStorage.setItem('pinjam_scroll_restore', JSON.stringify({
                    path: targetUrl ? new URL(targetUrl, window.location.origin).pathname : window.location.pathname,
                    y: window.scrollY || 0,
                    ts: Date.now()
                }));
            } catch (e) {}
            if (targetUrl) {
                window.location.href = targetUrl;
            } else {
                window.location.reload();
            }
        },
        initNetworkMonitor() {
            try {
                const rawScroll = sessionStorage.getItem('pinjam_scroll_restore');
                if (rawScroll) {
                    sessionStorage.removeItem('pinjam_scroll_restore');
                    const parsed = JSON.parse(rawScroll);
                    if (parsed && parsed.path === window.location.pathname && (Date.now() - parsed.ts) < 15000 && parsed.y > 0) {
                        requestAnimationFrame(() => window.scrollTo({ top: parsed.y, behavior: 'instant' }));
                    }
                }
            } catch (e) {}
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form && form.method && form.method.toUpperCase() === 'POST' && !form.hasAttribute('data-ajax-catalog-form')) {
                    try {
                        sessionStorage.setItem('pinjam_scroll_restore', JSON.stringify({
                            path: window.location.pathname,
                            y: window.scrollY || 0,
                            ts: Date.now()
                        }));
                    } catch (err) {}
                }
            });
            const origFetch = window.fetch;
            const self = this;
            window.fetch = async function(...args) {
                self.startNetworkIndicator();
                try {
                    return await origFetch.apply(this, args);
                } finally {
                    self.stopNetworkIndicator();
                }
            };
            window.addEventListener('beforeunload', () => {
                this.startNetworkIndicator();
            });
            window.addEventListener('pageshow', () => {
                this.activeRequests = 0;
                this.stopNetworkIndicator();
            });
        },
        cartOpen: false,
        cartBusy: false,
        cartPayload: @js($layoutCartPayload ?? ['items' => [], 'cart_map' => (object) [], 'total_titles' => 0, 'total_qty' => 0]),
        cartQty(bookId) {
            if (!this.cartPayload || !this.cartPayload.cart_map) return 0;
            return Number(this.cartPayload.cart_map[bookId] || 0);
        },
        async addToCart(bookId) {
            if (this.cartBusy) return;
            this.cartBusy = true;
            try {
                const res = await fetch(`/buku/${bookId}/cart`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ qty: 1 })
                });
                if (res.status === 401) {
                    this.openAuthModal('{{ __('meminjam buku') }}');
                    return;
                }
                const data = await res.json();
                if (data.cart) {
                    this.cartPayload = data.cart;
                }
                this.showToast(
                    data.message || '{{ __('Buku ditambahkan ke keranjang.') }}',
                    (data.status === 'error' || data.status === 'warning') ? 'error' : 'success'
                );
            } catch (err) {
                this.showToast('{{ __('Gagal memperbarui keranjang.') }}', 'error');
            } finally {
                this.cartBusy = false;
            }
        },
        async updateCartQty(bookId, action, qty = null) {
            if (this.cartBusy) return;
            this.cartBusy = true;
            try {
                const payload = { action };
                if (action === 'set' && qty !== null) {
                    const parsed = parseInt(qty, 10);
                    payload.qty = isNaN(parsed) ? 1 : Math.max(0, parsed);
                }
                const res = await fetch(`/buku/${bookId}/cart`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(payload)
                });
                if (res.status === 401) {
                    this.openAuthModal('{{ __('meminjam buku') }}');
                    return;
                }
                const data = await res.json();
                if (data.cart) {
                    this.cartPayload = data.cart;
                }
                if (data.status === 'warning' || data.status === 'error') {
                    this.showToast(data.message, 'error');
                }
            } catch (err) {
                this.showToast('{{ __('Gagal memperbarui jumlah buku.') }}', 'error');
            } finally {
                this.cartBusy = false;
            }
        },
        async removeCartItem(bookId) {
            if (this.cartBusy) return;
            this.cartBusy = true;
            try {
                const res = await fetch(`/buku/${bookId}/cart`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (data.cart) {
                    this.cartPayload = data.cart;
                }
                if (data.message) {
                    this.showToast(data.message, 'success');
                }
            } catch (err) {
                this.showToast('{{ __('Gagal menghapus buku dari keranjang.') }}', 'error');
            } finally {
                this.cartBusy = false;
            }
        },
        mobileSearchOpen: false,
        langOpen: false,
        authModalOpen: {{ (request()->boolean('setup_password') || session('needs_password_setup') || request()->filled('waiting_verify')) ? 'true' : 'false' }},
        authStep: '{{ (request()->boolean('setup_password') || session('needs_password_setup')) ? 'set_password' : (request()->filled('waiting_verify') ? 'waiting_verification' : 'login') }}',
        authModalAction: '{{ __('melanjutkan') }}',
        authEmail: '{{ addslashes(session('setup_password_email') ?? request('waiting_verify', '')) }}',
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
        otpCodeInput: '',
        generatedOtpCode: '',
        verifyPollTimer: null,
        openAuthModal(action = '{{ __('melanjutkan') }}', step = 'login') {
            this.authModalAction = action || '{{ __('melanjutkan') }}';
            this.authError = '';
            this.authSuccessMsg = '';
            this.authStep = step;
            this.authModalOpen = true;
            if (step === 'waiting_verification') {
                this.startVerificationPolling();
            }
        },
        switchAuthStep(step) {
            this.authError = '';
            this.authSuccessMsg = '';
            this.authStep = step;
            if (step === 'waiting_verification') {
                this.startVerificationPolling();
            } else {
                this.stopVerificationPolling();
            }
        },
        closeAuthModal() {
            this.authModalOpen = false;
            this.stopVerificationPolling();
        },
        startVerificationPolling() {
            this.stopVerificationPolling();
            this.verifyPollTimer = setInterval(async () => {
                if (!this.authModalOpen || this.authStep !== 'waiting_verification') {
                    this.stopVerificationPolling();
                    return;
                }
                try {
                    const res = await fetch(`{{ route('auth.check.verification') }}?email=${encodeURIComponent(this.authEmail)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (res.ok && data.verified) {
                        this.stopVerificationPolling();
                        this.authPassword = '';
                        this.authPasswordConfirm = '';
                        this.authError = '';
                        this.authSuccessMsg = data.message || 'Verifikasi email berhasil! Silakan masukkan kata sandi Anda.';
                        this.authStep = 'set_password';
                        this.showToast('Email terverifikasi! Silakan buat kata sandi.', 'success');
                    }
                } catch (e) {}
            }, 2500);
        },
        stopVerificationPolling() {
            if (this.verifyPollTimer) {
                clearInterval(this.verifyPollTimer);
                this.verifyPollTimer = null;
            }
        },
        async submitQuickLogin() {
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
                    this.authModalOpen = false;
                    if (data.user && (data.user.role === 'admin' || data.user.role === 'pustakawan') && data.redirect) {
                        window.location.href = data.redirect;
                    } else if (data.redirect && window.location.pathname === '/login') {
                        window.location.href = data.redirect;
                    } else {
                        this.reloadPreservingScroll();
                    }
                } else if (data.needs_verification) {
                    this.generatedOtpCode = data.verification_code || '';
                    this.otpCodeInput = '';
                    this.authSuccessMsg = data.message;
                    this.switchAuthStep('waiting_verification');
                } else {
                    this.authError = data.message || 'Email atau kata sandi tidak sesuai.';
                }
            } catch (err) {
                this.authError = 'Terjadi gangguan jaringan. Silakan coba kembali.';
            } finally {
                this.authLoading = false;
            }
        },
        async submitEmailRegister() {
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
                    body: JSON.stringify({
                        email: this.authEmail
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.generatedOtpCode = data.verification_code || '';
                    this.otpCodeInput = '';
                    this.authSuccessMsg = data.message || 'Kode verifikasi telah dikirim.';
                    this.switchAuthStep('waiting_verification');
                } else if (data.already_registered) {
                    this.authError = data.message;
                    this.authStep = 'login';
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                    this.authError = firstErr || data.message || 'Gagal mengirim email verifikasi.';
                }
            } catch (err) {
                this.authError = 'Terjadi gangguan jaringan saat mengirim email verifikasi.';
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
                    this.switchAuthStep('set_password');
                } else if (res.ok && data.success) {
                    this.generatedOtpCode = data.verification_code || '';
                    this.otpCodeInput = '';
                    this.authSuccessMsg = data.message || 'Kode pemulihan kata sandi telah dikirim ke email Anda.';
                    this.switchAuthStep('waiting_verification');
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                    this.authError = firstErr || data.message || 'Gagal memproses pemulihan kata sandi.';
                }
            } catch (err) {
                this.authError = 'Terjadi gangguan jaringan saat memproses permintaan.';
            } finally {
                this.authLoading = false;
            }
        },
        async resendVerifyEmail() {
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
                if (res.ok && data.verified) {
                    this.switchAuthStep('set_password');
                } else if (res.ok && data.success) {
                    this.generatedOtpCode = data.verification_code || this.generatedOtpCode;
                    this.authSuccessMsg = data.message;
                    this.showToast(data.message, 'success');
                } else {
                    this.authError = data.message || 'Gagal mengirim ulang kode verifikasi.';
                }
            } catch (e) {
                this.authError = 'Gagal menghubungi server.';
            } finally {
                this.authLoading = false;
            }
        },
        async submitOtpCodeVerify() {
            const cleanCode = (this.otpCodeInput || '').replace(/\D/g, '');
            if (cleanCode.length !== 6) {
                this.authError = 'Masukkan 6 digit angka kode verifikasi.';
                return;
            }
            if (this.authLoading) return;
            this.authLoading = true;
            this.authError = '';
            try {
                const res = await fetch('{{ route('verification.verify') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        email: this.authEmail,
                        code: cleanCode
                    })
                });
                const data = await res.json();
                if (res.ok && data.verified) {
                    this.stopVerificationPolling();
                    this.authPassword = '';
                    this.authPasswordConfirm = '';
                    this.authError = '';
                    this.authSuccessMsg = data.message || 'Kode verifikasi berhasil! Silakan buat kata sandi Anda.';
                    this.authStep = 'set_password';
                    this.showToast('Kode terverifikasi! Silakan buat kata sandi.', 'success');
                } else {
                    this.authError = data.message || 'Kode verifikasi tidak sesuai.';
                }
            } catch (e) {
                this.authError = 'Terjadi gangguan jaringan saat memverifikasi kode.';
            } finally {
                this.authLoading = false;
            }
        },
        async submitNewPasswordSetup() {
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
                    this.authModalOpen = false;
                    const cleanUrl = window.location.pathname;
                    this.reloadPreservingScroll(cleanUrl);
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                    this.authError = firstErr || data.message || 'Gagal menyimpan kata sandi.';
                }
            } catch (e) {
                this.authError = 'Terjadi gangguan jaringan.';
            } finally {
                this.authLoading = false;
            }
        },
        toastMessage: '',
        toastType: 'success',
        toastTimer: null,
        showToast(msg, type = 'success') {
            this.toastMessage = msg;
            this.toastType = type;
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toastMessage = ''; }, 4200);
        }
    }"
    x-init="initNetworkMonitor()"
    @open-auth-modal.window="openAuthModal($event.detail?.action)"
    @notify-toast.window="showToast($event.detail?.message, $event.detail?.type || 'success')"
    @cart-add.window="addToCart($event.detail?.id)"
    @cart-update.window="updateCartQty($event.detail?.id, $event.detail?.action)"
    @keydown.escape.window="cartOpen = false; authModalOpen = false; langOpen = false; mobileSearchOpen = false"
    class="bg-[#f6f8fc] dark:bg-[#08080a] text-slate-800 dark:text-slate-100 antialiased min-h-screen flex flex-col justify-between pb-20 md:pb-0 transition-colors duration-200 selection:bg-blue-600 selection:text-white"
>

    <!-- Indikator jaringan -->
    <div x-cloak x-show="networkBusy" class="fixed top-0 inset-x-0 z-[110] h-1 bg-blue-100/70 dark:bg-slate-800 overflow-hidden pointer-events-none">
        <div class="h-full w-2/5 bg-gradient-to-r from-blue-500 via-indigo-500 to-cyan-400 rounded-full animate-shimmer-bar"></div>
    </div>

    <!-- Notifikasi mengambang (selalu di atas elemen & modal) -->
    <div 
        x-cloak
        x-show="toastMessage"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        class="fixed top-16 sm:top-20 right-3 sm:right-6 left-3 sm:left-auto z-[100] sm:max-w-sm shadow-2xl rounded-2xl p-3 sm:p-3.5 flex items-center gap-2.5 border text-xs sm:text-sm font-semibold"
        :class="toastType === 'success' 
            ? 'bg-slate-900 dark:bg-slate-800 text-white border-slate-700/80' 
            : 'bg-rose-950 text-white border-rose-800'"
        role="status"
        aria-live="polite"
    >
        <i class="text-lg shrink-0" :class="toastType === 'success' ? 'ph-fill ph-check-circle text-emerald-400' : 'ph-fill ph-warning-circle text-rose-300'" aria-hidden="true"></i>
        <span class="flex-1 leading-snug" x-text="toastMessage"></span>
        <button type="button" @click="toastMessage = ''" aria-label="Close" class="text-slate-400 hover:text-white p-1 shrink-0">
            <i class="ph-bold ph-x" aria-hidden="true"></i>
        </button>
    </div>

    @if(session('success'))
        <div x-init="showToast('{{ addslashes(session('success')) }}', 'success')"></div>
    @endif
    @if(session('error'))
        <div x-init="showToast('{{ addslashes(session('error')) }}', 'error')"></div>
    @endif
    @if(session('info'))
        <div x-init="showToast('{{ addslashes(session('info')) }}', 'success')"></div>
    @endif

    <!-- Navbar atas -->
    <header class="sticky top-0 z-40 pt-2 sm:pt-3.5 px-2.5 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <nav aria-label="Main Navigation" class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-200/90 dark:border-slate-800 rounded-2xl sm:rounded-full px-2.5 sm:px-5 h-13 sm:h-16 py-1.5 sm:py-0 shadow-sm flex items-center justify-between gap-1.5 sm:gap-4">
                
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2 sm:gap-2.5 shrink-0 group min-w-0">
                    <img 
                        src="{{ asset('images/logo-sekolah.png') }}" 
                        alt="Logo SMK Negeri 5 Surakarta" 
                        width="36" 
                        height="36" 
                        class="w-8 h-8 sm:w-9 sm:h-9 object-contain shrink-0 drop-shadow-sm transition-transform duration-200 group-hover:scale-105"
                    >
                    <span class="font-extrabold text-xs sm:text-lg tracking-tight text-slate-900 dark:text-white truncate">
                        PINJAM <span class="text-blue-600 dark:text-blue-400">BU-PUS</span>
                    </span>
                </a>

                <!-- Menu desktop -->
                <div class="hidden md:flex items-center gap-1 p-1 rounded-full border border-slate-200/80 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-950/60">
                    <a 
                        href="{{ route('home') }}"
                        class="px-4 py-1.5 rounded-full text-xs lg:text-sm font-semibold transition-all {{ request()->routeIs('home') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' }}"
                    >
                        {{ __('Beranda') }}
                    </a>
                    <a 
                        href="{{ route('perpustakaan.index') }}"
                        class="px-4 py-1.5 rounded-full text-xs lg:text-sm font-semibold transition-all {{ request()->routeIs('perpustakaan.*', 'buku.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' }}"
                    >
                        {{ __('Perpustakaan') }}
                    </a>
                    <a 
                        href="{{ route('profil.index') }}"
                        class="px-4 py-1.5 rounded-full text-xs lg:text-sm font-semibold transition-all {{ request()->routeIs('profil.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' }}"
                    >
                        {{ __('Pinjaman Saya') }}
                    </a>
                </div>

                <!-- Right Controls: Search, Checkout Cart, Profile, Language, Dark/Light -->
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    
                    <!-- Compact Inline Search (Desktop lg+) -->
                    <form action="{{ route('perpustakaan.index') }}" method="GET" class="hidden lg:flex items-center relative w-48 xl:w-60">
                        <i class="ph ph-magnifying-glass text-slate-400 text-sm absolute left-3.5 pointer-events-none" aria-hidden="true"></i>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="{{ __('Cari buku...') }}"
                            aria-label="{{ __('Cari buku...') }}"
                            class="w-full rounded-full bg-slate-100/90 dark:bg-slate-800/90 border border-slate-200/70 dark:border-slate-700/80 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 pl-9 pr-3 py-2 transition-colors outline-none"
                        >
                    </form>

                    <!-- Mobile / Tablet Search Toggle -->
                    <button 
                        type="button"
                        @click="mobileSearchOpen = !mobileSearchOpen"
                        aria-label="{{ __('Cari buku...') }}"
                        class="lg:hidden w-9 h-9 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-blue-400 flex items-center justify-center transition-colors"
                    >
                        <i class="ph-bold text-sm" :class="mobileSearchOpen ? 'ph-x text-blue-600 dark:text-blue-400' : 'ph-magnifying-glass'" aria-hidden="true"></i>
                    </button>

                    <!-- Checkout / Keranjang Pinjaman Drawer Trigger -->
                    <button 
                        type="button"
                        @click="cartOpen = true"
                        aria-label="{{ __('Keranjang Pinjaman') }}"
                        title="{{ __('Keranjang Pinjaman') }}"
                        class="relative w-9 h-9 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400 flex items-center justify-center transition-colors"
                    >
                        <i class="ph-bold ph-shopping-cart text-sm sm:text-base" aria-hidden="true"></i>
                        <span 
                            x-cloak
                            x-show="cartPayload.total_qty > 0"
                            x-text="cartPayload.total_qty"
                            class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-white text-[10px] font-extrabold flex items-center justify-center border-2 border-white dark:border-slate-900"
                        ></span>
                    </button>

                    <!-- Admin & Pustakawan Quick Link -->
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a 
                                href="{{ route('admin.dashboard') }}" 
                                title="{{ __('Panel Admin') }}"
                                class="hidden sm:inline-flex items-center gap-1 px-2.5 h-9 rounded-full bg-slate-900 dark:bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors"
                            >
                                <i class="ph-bold ph-shield-check text-sm" aria-hidden="true"></i>
                                <span class="hidden xl:inline">Admin</span>
                            </a>
                        @elseif(auth()->user()->isPustakawan())
                            <a 
                                href="{{ route('pustakawan.dashboard') }}" 
                                title="{{ __('Panel Pustakawan') }}"
                                class="hidden sm:inline-flex items-center gap-1 px-2.5 h-9 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors"
                            >
                                <i class="ph-bold ph-book-open-text text-sm" aria-hidden="true"></i>
                                <span class="hidden xl:inline">Pustakawan</span>
                            </a>
                        @endif
                    @endauth

                    <!-- Profil Pill Button -->
                    @auth
                        @php
                            $navUser = auth()->user();
                            $navPalette = $navUser->color_palette;
                        @endphp
                        <a 
                            href="{{ route('profil.index') }}"
                            class="h-9 pl-1.5 pr-2.5 sm:pr-3 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 flex items-center gap-1.5 text-xs font-bold text-slate-800 dark:text-slate-100 transition-colors"
                        >
                            @if($navUser->avatar_url)
                                <img src="{{ $navUser->avatar_url }}" alt="{{ $navUser->name }}" width="24" height="24" class="w-6 h-6 rounded-full object-cover">
                            @else
                                <span 
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-extrabold shrink-0"
                                    style="background-color: {{ $navPalette['bg'] }}; color: {{ $navPalette['text'] }};"
                                >
                                    {{ $navUser->initial }}
                                </span>
                            @endif
                            <span class="hidden sm:inline max-w-[84px] truncate">{{ Str::words($navUser->name, 1, '') }}</span>
                        </a>
                    @else
                        <a 
                            href="{{ route('profil.index') }}"
                            class="h-9 px-2.5 sm:px-3 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors"
                        >
                            <i class="ph-bold ph-user-circle text-base" aria-hidden="true"></i>
                            <span class="hidden sm:inline">{{ __('Profil') }}</span>
                        </a>
                    @endguest

                    <!-- Language Switcher (ID / EN) -->
                    <div class="relative">
                        <button 
                            type="button"
                            @click="langOpen = !langOpen"
                            aria-label="{{ __('Bahasa') }}"
                            title="{{ __('Bahasa') }}"
                            class="h-9 px-2.5 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 flex items-center justify-center gap-1 text-xs font-bold transition-colors"
                        >
                            <i class="ph-bold ph-globe text-sm sm:text-base" aria-hidden="true"></i>
                            <span class="uppercase text-[11px]">{{ app()->getLocale() }}</span>
                        </button>

                        <div 
                            x-cloak
                            x-show="langOpen"
                            @click.away="langOpen = false"
                            x-transition
                            class="absolute right-0 mt-2 w-44 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl p-1.5 z-50 text-xs"
                        >
                            <a 
                                href="{{ route('locale.switch', 'id') }}"
                                class="flex items-center justify-between px-3 py-2 rounded-xl font-semibold transition-colors {{ app()->getLocale() === 'id' ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}"
                            >
                                <span>Bahasa Indonesia</span>
                                <span class="text-[10px] font-extrabold uppercase">ID</span>
                            </a>
                            <a 
                                href="{{ route('locale.switch', 'en') }}"
                                class="flex items-center justify-between px-3 py-2 rounded-xl font-semibold transition-colors mt-0.5 {{ app()->getLocale() === 'en' ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}"
                            >
                                <span>English</span>
                                <span class="text-[10px] font-extrabold uppercase">EN</span>
                            </a>
                        </div>
                    </div>

                    <!-- Light / Dark Theme Toggle -->
                    <button 
                        type="button"
                        @click="toggleTheme()"
                        :aria-label="darkMode ? '{{ __('Tema Terang') }}' : '{{ __('Tema Gelap') }}'"
                        :title="darkMode ? '{{ __('Tema Terang') }}' : '{{ __('Tema Gelap') }}'"
                        class="w-9 h-9 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 flex items-center justify-center transition-colors"
                    >
                        <i class="ph-bold text-sm sm:text-base" :class="darkMode ? 'ph-sun text-amber-400' : 'ph-moon'" aria-hidden="true"></i>
                    </button>

                </div>
            </nav>

            <!-- Collapsible Search Bar for Mobile & Tablet -->
            <div 
                x-cloak
                x-show="mobileSearchOpen"
                x-transition
                class="lg:hidden mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-2.5 shadow-md"
            >
                <form action="{{ route('perpustakaan.index') }}" method="GET" class="relative flex items-center">
                    <i class="ph ph-magnifying-glass text-slate-400 text-base absolute left-3.5 pointer-events-none" aria-hidden="true"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="{{ __('Cari judul buku, penulis, atau ISBN...') }}"
                        class="w-full bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs rounded-xl pl-9 pr-20 py-2.5 border border-slate-200 dark:border-slate-700 focus:border-blue-500 outline-none"
                    >
                    <button type="submit" class="absolute right-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg">
                        {{ __('Cari') }}
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Navigasi bawah mobile -->
    <nav aria-label="Mobile Navigation" class="md:hidden fixed bottom-0 inset-x-0 z-40 h-16 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 grid grid-cols-4 items-center px-2">
        <a 
            href="{{ route('home') }}"
            class="flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[11px] font-semibold transition-colors {{ request()->routeIs('home') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400' }}"
        >
            <i class="text-lg {{ request()->routeIs('home') ? 'ph-fill ph-house' : 'ph-bold ph-house' }}" aria-hidden="true"></i>
            <span>{{ __('Beranda') }}</span>
        </a>

        <a 
            href="{{ route('perpustakaan.index') }}"
            class="flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[11px] font-semibold transition-colors {{ request()->routeIs('perpustakaan.*', 'buku.*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400' }}"
        >
            <i class="text-lg {{ request()->routeIs('perpustakaan.*', 'buku.*') ? 'ph-fill ph-books' : 'ph-bold ph-books' }}" aria-hidden="true"></i>
            <span>{{ __('Perpustakaan') }}</span>
        </a>

        <button 
            type="button"
            @click="cartOpen = true"
            class="relative flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400"
        >
            <span class="relative">
                <i class="ph-bold ph-shopping-cart text-lg" aria-hidden="true"></i>
                <span 
                    x-cloak
                    x-show="cartPayload.total_qty > 0"
                    x-text="cartPayload.total_qty"
                    class="absolute -top-1.5 -right-2.5 min-w-[16px] h-[16px] px-1 rounded-full bg-blue-600 text-white text-[9px] font-extrabold flex items-center justify-center"
                ></span>
            </span>
            <span>{{ __('Keranjang') }}</span>
        </button>

        <a 
            href="{{ route('profil.index') }}"
            class="flex flex-col items-center justify-center min-h-[48px] gap-0.5 text-[11px] font-semibold transition-colors {{ request()->routeIs('profil.*') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400' }}"
        >
            <i class="text-lg {{ request()->routeIs('profil.*') ? 'ph-fill ph-user-circle' : 'ph-bold ph-user-circle' }}" aria-hidden="true"></i>
            <span class="truncate max-w-[76px]">{{ __('Pinjaman Saya') }}</span>
        </a>
    </nav>

    <!-- Drawer keranjang pinjaman -->
    <div 
        x-cloak
        x-show="cartOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('Keranjang Pinjaman') }}"
    >
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="cartOpen = false"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex">
            <div 
                x-show="cartOpen"
                x-transition:enter="transform transition ease-in-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="w-screen max-w-md bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col justify-between"
            >
                <!-- Drawer Header -->
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                            <i class="ph-bold ph-shopping-cart" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white">{{ __('Keranjang Pinjaman') }}</h2>
                            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                                <span class="font-bold text-slate-700 dark:text-slate-200" x-text="cartPayload.total_qty"></span> {{ __('buku dipilih') }}
                                <span x-show="cartPayload.total_titles > 0">
                                    (<span x-text="cartPayload.total_titles"></span> {{ __('judul') }})
                                </span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="cartOpen = false" aria-label="Close" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center">
                        <i class="ph-bold ph-x text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Drawer Items (Reactive Alpine List with +/- Stepper) -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3">
                    <template x-for="item in cartPayload.items" :key="item.id">
                        <div class="flex items-start justify-between gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-700/80">
                            <a :href="item.url" class="shrink-0">
                                <img :src="item.cover_url" :alt="item.judul" width="56" height="80" class="w-13 h-18 sm:w-14 sm:h-20 object-cover rounded-xl bg-slate-200 dark:bg-slate-700">
                            </a>

                            <div class="flex-1 min-w-0">
                                <a :href="item.url" class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 hover:text-blue-600 dark:hover:text-blue-400 truncate block" x-text="item.judul"></a>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate" x-text="item.pengarang"></p>

                                <div class="mt-2.5 flex items-center justify-between gap-2 flex-wrap">
                                    <!-- +/- Quantity Stepper (Typable) -->
                                    <div class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-0.5 shadow-sm">
                                        <button 
                                            type="button"
                                            @click="updateCartQty(item.id, 'decrement')"
                                            :disabled="cartBusy"
                                            aria-label="Kurangi jumlah"
                                            class="w-7 h-7 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors disabled:opacity-40"
                                        >
                                            <i class="ph-bold ph-minus text-xs" aria-hidden="true"></i>
                                        </button>
                                        <input
                                            type="number"
                                            min="1"
                                            :max="item.stok"
                                            :value="item.qty"
                                            @change="updateCartQty(item.id, 'set', $event.target.value)"
                                            @keydown.enter.prevent="$event.target.blur()"
                                            aria-label="Ketik jumlah buku"
                                            class="w-11 h-7 text-center text-xs font-extrabold text-slate-900 dark:text-white bg-transparent border-x border-slate-100 dark:border-slate-800 outline-none focus:bg-blue-50/60 dark:focus:bg-blue-950/40 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        >
                                        <button 
                                            type="button"
                                            @click="updateCartQty(item.id, 'increment')"
                                            :disabled="cartBusy || item.qty >= item.stok"
                                            aria-label="Tambah jumlah"
                                            class="w-7 h-7 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors disabled:opacity-40"
                                        >
                                            <i class="ph-bold ph-plus text-xs" aria-hidden="true"></i>
                                        </button>
                                    </div>

                                    <span class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md">
                                        {{ __('Stok') }}: <span x-text="item.stok"></span>
                                    </span>
                                </div>
                            </div>

                            <button 
                                type="button"
                                @click="removeCartItem(item.id)"
                                :disabled="cartBusy"
                                aria-label="Hapus dari keranjang"
                                class="w-8 h-8 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 flex items-center justify-center transition-colors shrink-0"
                            >
                                <i class="ph-bold ph-trash text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div x-show="!cartPayload.items || cartPayload.items.length === 0" class="text-center py-14 px-4">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                            <i class="ph ph-shopping-bag" aria-hidden="true"></i>
                        </div>
                        <h3 class="font-bold text-slate-800 dark:text-slate-200 text-sm mb-1">{{ __('Keranjang Masih Kosong') }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto leading-relaxed">
                            {{ __('Tambahkan buku dari katalog atau halaman detail untuk meminjam beberapa buku sekaligus.') }}
                        </p>
                    </div>
                </div>

                <!-- Drawer Footer -->
                <div x-show="cartPayload.total_qty > 0" class="p-4 sm:p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900 space-y-2.5">
                    <div class="text-xs text-slate-600 dark:text-slate-400 flex justify-between">
                        <span>{{ __('Total Buku Dipinjam') }}</span>
                        <span class="font-extrabold text-slate-900 dark:text-white">
                            <span x-text="cartPayload.total_qty"></span> {{ __('Buku') }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-600 dark:text-slate-400 flex justify-between">
                        <span>{{ __('Durasi Pinjam') }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ __('7 Hari') }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/70 text-[11px] text-amber-800 dark:text-amber-200 leading-relaxed">
                        <strong>Ketentuan Denda Siswa:</strong> Terlambat melewati batas 7 hari dikenakan denda <strong>Rp 1.000/hari/buku</strong> sesuai peraturan perpustakaan sekolah.
                    </div>

                    @guest
                        <div class="pt-1">
                            <button 
                                type="button"
                                @click="cartOpen = false; openAuthModal('{{ __('Checkout Peminjaman') }}')"
                                class="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-3 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2"
                            >
                                <i class="ph-bold ph-sign-in" aria-hidden="true"></i>
                                <span>{{ __('Masuk untuk Meminjam') }}</span>
                            </button>
                        </div>
                    @else
                        <div class="pt-1">
                            <a 
                                href="{{ route('buku.checkout.show') }}"
                                class="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-3 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2"
                            >
                                <i class="ph-bold ph-clipboard-text text-base" aria-hidden="true"></i>
                                <span>{{ __('Lanjut ke Form Peminjaman') }}</span>
                            </a>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>

    <!-- Modal login dan daftar -->
    <div 
        x-cloak
        x-show="authModalOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('Masuk atau Daftar ke PINJAM BU-PUS') }}"
    >
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-md" @click="closeAuthModal()"></div>

        <div 
            class="relative w-full max-w-sm sm:max-w-md bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-7 z-10 space-y-4 my-auto"
            @click.stop
        >
            <button 
                type="button"
                @click="closeAuthModal()"
                aria-label="Close"
                class="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-300 flex items-center justify-center transition-colors"
            >
                <i class="ph-bold ph-x text-base" aria-hidden="true"></i>
            </button>

            <!-- Dynamic Header -->
            <div class="text-center pt-1">
                <img 
                    src="{{ asset('images/logo-sekolah.png') }}" 
                    alt="Logo SMK Negeri 5 Surakarta" 
                    width="48" 
                    height="48" 
                    class="w-12 h-12 object-contain mx-auto mb-2.5 drop-shadow-sm"
                >
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    <span x-show="authStep === 'login'">{{ __('Masuk ke PINJAM BU-PUS') }}</span>
                    <span x-show="authStep === 'register'">{{ __('Daftar ke PINJAM BU-PUS') }}</span>
                    <span x-show="authStep === 'forgot_password'">{{ __('Pemulihan Kata Sandi') }}</span>
                    <span x-show="authStep === 'waiting_verification'">{{ __('Verifikasi Email Anda') }}</span>
                    <span x-show="authStep === 'set_password'">{{ __('Buat Kata Sandi Akun') }}</span>
                </h2>
                <p x-show="authStep === 'login'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    {{ __('Silakan masuk terlebih dahulu untuk') }} <span class="font-bold text-blue-600 dark:text-blue-400" x-text="authModalAction"></span>.
                </p>
                <p x-show="authStep === 'register'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Masukkan email aktif Anda. Sistem akan otomatis mengirimkan tombol verifikasi ke Gmail Anda.
                </p>
                <p x-show="authStep === 'forgot_password'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Lupa kata sandi akun? Pilih metode verifikasi via Email Gmail atau NISN warga sekolah untuk mereset kata sandi.
                </p>
                <p x-show="authStep === 'waiting_verification'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Kami telah mengirimkan email verifikasi ke <strong class="text-blue-600 dark:text-blue-400" x-text="authEmail"></strong>.
                </p>
                <p x-show="authStep === 'set_password'" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                    Akun <strong class="text-emerald-600 dark:text-emerald-400" x-text="authEmail"></strong> berhasil diverifikasi! Silakan buat kata sandi baru Anda.
                </p>
            </div>

            <!-- Error Banner -->
            <div 
                x-show="authError"
                x-transition
                class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2"
            >
                <i class="ph-fill ph-warning-circle text-base text-rose-500 shrink-0" aria-hidden="true"></i>
                <span x-text="authError"></span>
            </div>

            <!-- Success Banner -->
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
                <form @submit.prevent="submitQuickLogin()" class="space-y-3 text-left">
                    <div>
                        <label for="quick-auth-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                        <input 
                            id="quick-auth-email"
                            type="email"
                            x-model="authEmail"
                            required
                            placeholder="email@example.com"
                            class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                        >
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="quick-auth-password" class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Kata Sandi') }}</label>
                            <button type="button" @click="switchAuthStep('forgot_password')" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline font-semibold">{{ __('Lupa sandi?') }}</button>
                        </div>
                        <div class="relative">
                            <input 
                                id="quick-auth-password"
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
                    <button type="button" @click="switchAuthStep('register')" class="font-bold text-blue-600 dark:text-blue-400 hover:underline ml-0.5">
                        {{ __('Daftar & Verifikasi Email') }}
                    </button>
                </div>
            </div>

            <!-- Form daftar -->
            <div x-show="authStep === 'register'" class="space-y-3" style="display: none;">
                <form @submit.prevent="submitEmailRegister()" class="space-y-3 text-left">
                    <div>
                        <label for="quick-register-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email Aktif (Gmail)</label>
                        <input 
                            id="quick-register-email"
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
                    <button type="button" @click="switchAuthStep('login')" class="font-bold text-blue-600 dark:text-blue-400 hover:underline ml-0.5">
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
                        <label for="quick-forgot-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email Akun Terdaftar</label>
                        <input 
                            id="quick-forgot-email"
                            type="email"
                            x-model="authEmail"
                            required
                            placeholder="nama@gmail.com"
                            class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                        >
                    </div>

                    <div x-show="forgotMethod === 'nisn'" x-transition class="space-y-1.5">
                        <label for="quick-forgot-nisn" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Nomor NISN (10 Digit Angka)</label>
                        <input 
                            id="quick-forgot-nisn"
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
                    <button type="button" @click="switchAuthStep('login')" class="font-bold text-blue-600 dark:text-blue-400 hover:underline ml-0.5">
                        Kembali ke Login
                    </button>
                </div>
            </div>

            <!-- Menunggu verifikasi email -->
            <div x-show="authStep === 'waiting_verification'" class="space-y-3.5 text-center" style="display: none;">
                <div class="p-4 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/80 space-y-2">
                    <div class="w-11 h-11 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto text-xl shadow-sm">
                        <i class="ph-bold ph-shield-check" aria-hidden="true"></i>
                    </div>
                    <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed">
                        Masukkan <strong>6 digit kode verifikasi</strong> yang dikirim ke <strong x-text="authEmail"></strong>, atau tekan tombol konfirmasi di kotak masuk Gmail Anda.
                    </p>
                    <div x-show="generatedOtpCode" class="pt-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-blue-200 dark:border-blue-800 shadow-xs">
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Kode Anda:</span>
                            <strong class="font-mono text-sm font-extrabold tracking-widest text-blue-600 dark:text-blue-400" x-text="generatedOtpCode"></strong>
                            <button
                                type="button"
                                @click="otpCodeInput = generatedOtpCode; submitOtpCodeVerify()"
                                class="px-2 py-0.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] transition-colors"
                            >
                                Pakai Kode
                            </button>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitOtpCodeVerify()" class="space-y-2.5 text-left">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 text-center">Kode Verifikasi (6 Digit)</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            x-model="otpCodeInput"
                            @input="otpCodeInput = otpCodeInput.replace(/\D/g, '').slice(0, 6); if (otpCodeInput.length === 6) submitOtpCodeVerify();"
                            placeholder="• • • • • •"
                            class="w-full text-center font-mono text-lg sm:text-xl font-extrabold tracking-[0.45em] bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-600 transition-colors"
                        >
                    </div>
                    <button
                        type="submit"
                        :disabled="authLoading || (otpCodeInput || '').length !== 6"
                        class="w-full min-h-[42px] bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-2.5 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <i class="ph-bold text-base" :class="authLoading ? 'ph-spinner animate-spin' : 'ph-check-circle'" aria-hidden="true"></i>
                        <span x-text="authLoading ? 'Memverifikasi...' : 'Verifikasi Kode Sekarang'"></span>
                    </button>
                </form>

                <div class="flex items-center justify-between gap-2 pt-1 text-xs">
                    <button 
                        type="button"
                        @click="resendVerifyEmail()"
                        :disabled="authLoading"
                        class="font-bold text-blue-600 dark:text-blue-400 hover:underline"
                    >
                        Kirim Ulang Kode
                    </button>
                    <button 
                        type="button"
                        @click="switchAuthStep('register')"
                        class="font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                    >
                        Ganti Alamat Email
                    </button>
                </div>
            </div>

            <!-- Form buat password -->
            <div x-show="authStep === 'set_password'" class="space-y-3" style="display: none;">
                <form @submit.prevent="submitNewPasswordSetup()" class="space-y-3 text-left">
                    <div>
                        <label for="setup-new-password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Baru</label>
                        <div class="relative">
                            <input 
                                id="setup-new-password"
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
                        <label for="setup-confirm-password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Konfirmasi Kata Sandi</label>
                        <div class="relative">
                            <input 
                                id="setup-confirm-password"
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
    </div>

    <!-- Konten utama -->
    <main class="grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="max-w-7xl w-full mx-auto px-3 sm:px-6 lg:px-8 pt-8 pb-6 sm:pb-10">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 sm:p-10 shadow-sm space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-8">
                
                <!-- Brand Description -->
                <div class="lg:col-span-5 space-y-3">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                        <img 
                            src="{{ asset('images/logo-sekolah.png') }}" 
                            alt="Logo SMK Negeri 5 Surakarta" 
                            width="36" 
                            height="36" 
                            class="w-9 h-9 object-contain shrink-0 drop-shadow-sm"
                        >
                        <span class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-white tracking-tight">
                            PINJAM <span class="text-blue-600 dark:text-blue-400">BU-PUS</span>
                        </span>
                    </a>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-sm">
                        {{ __('Layanan peminjaman buku perpustakaan modern dengan katalog terstruktur, pinjam cepat satu klik, dan pengelolaan pinjaman yang praktis.') }}
                    </p>
                </div>

                <!-- Quick Links (No Blog, All Real Routes) -->
                <div class="lg:col-span-3 space-y-2.5">
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                        {{ __('Tautan Cepat') }}
                    </h3>
                    <ul class="space-y-2 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        <li><a href="{{ route('home') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ __('Beranda') }}</a></li>
                        <li><a href="{{ route('perpustakaan.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ __('Perpustakaan') }}</a></li>
                        <li><a href="{{ route('profil.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ __('Pinjaman Saya') }}</a></li>
                        @guest
                            <li><button type="button" @click="openAuthModal('Masuk Akun', 'login')" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ __('Masuk') }}</button></li>
                            <li><button type="button" @click="openAuthModal('Daftar Akun', 'register')" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ __('Daftar') }}</button></li>
                        @endguest
                    </ul>
                </div>

                <!-- Library Service Info -->
                <div class="lg:col-span-4 space-y-2.5">
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                        {{ __('Informasi Layanan') }}
                    </h3>
                    <ul class="space-y-2 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        <li><a href="{{ route('informasi.aturan-peminjaman') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ __('Aturan Peminjaman (7 Hari)') }}</a></li>
                        <li><a href="{{ route('informasi.panduan-pengembalian') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ __('Panduan Pengembalian Buku') }}</a></li>
                    </ul>
                </div>

            </div>

            <!-- Categories Pills Row -->
            @if(isset($footerKategoris) && $footerKategoris->isNotEmpty())
                <div class="space-y-3 pt-2">
                    <h3 class="text-xs font-extrabold text-slate-900 dark:text-white">
                        {{ __('Kategori') }}
                    </h3>
                    <div class="flex flex-wrap gap-1.5 sm:gap-2">
                        @foreach($footerKategoris as $kat)
                            <a 
                                href="{{ route('perpustakaan.index', ['kategori' => $kat->id]) }}"
                                class="px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400 text-[11px] sm:text-xs font-medium text-slate-600 dark:text-slate-300 transition-colors"
                            >
                                {{ $kat->nama_kategori }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="pt-5 border-t border-slate-100 dark:border-slate-800 text-center text-[11px] sm:text-xs text-slate-400 dark:text-slate-500">
                &copy; {{ date('Y') }} PINJAM BU-PUS. {{ __('Hak cipta dilindungi.') }}
            </div>
        </div>
    </footer>

</body>
</html>
