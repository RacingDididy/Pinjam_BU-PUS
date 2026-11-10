@extends('layouts.app')

@section('title', __('Aktivitas Pinjaman') . ' - PINJAM BU-PUS')

@section('content')
@php
    $authUser = auth()->user();
    $initialColorKey = old('profile_color', $authUser?->profile_color ?? 'blue');
    $initialUsername = old('username', $authUser?->display_username ?? '');
    $initialDisplayName = old('name', $authUser?->name ?? '');
    $existingAvatarUrl = $authUser?->avatar_url;
    $activeTransactions = collect($groupedTransactions ?? [])->where('is_active', true)->values();
@endphp

<div 
    x-data="{
        activeTab: 'aktif',
        profileModalOpen: {{ $errors->any() ? 'true' : 'false' }},
        identityModalOpen: {{ request()->boolean('lengkap_identitas') ? 'true' : 'false' }},
        passwordModalOpen: false,
        passForm: {
            verify_mode: 'current_password',
            current_password: '',
            verify_nisn: '',
            password: '',
            password_confirmation: '',
            showCurrent: false,
            showNew: false,
            showConfirm: false,
            saving: false,
            error: ''
        },
        colors: {{ Js::from($profileColors) }},
        selectedColor: '{{ $initialColorKey }}',
        username: '{{ addslashes($initialUsername) }}',
        displayName: '{{ addslashes($initialDisplayName) }}',
        previewUrl: {{ $existingAvatarUrl ? Js::from($existingAvatarUrl) : 'null' }},
        removeAvatar: false,
        identitySaving: false,
        identityCompletion: {{ $authUser ? $authUser->identity_completion_percentage : 0 }},
        identityComplete: {{ $authUser && $authUser->isSchoolIdentityComplete() ? 'true' : 'false' }},
        identityForm: {
            name: '{{ addslashes($authUser?->name ?? '') }}',
            no_telepon: '{{ addslashes($authUser?->no_telepon ?? '') }}',
            no_whatsapp: '{{ addslashes($authUser?->no_whatsapp ?? '') }}',
            tanggal_lahir: '{{ $authUser?->tanggal_lahir ? $authUser->tanggal_lahir->format('Y-m-d') : '' }}',
            nisn: '{{ addslashes($authUser?->nisn ?? '') }}',
            nis: '{{ addslashes($authUser?->nis ?? '') }}',
            nama_sekolah: '{{ addslashes($authUser?->nama_sekolah ?: 'SMK Negeri 5 Surakarta') }}',
            tahun_ajaran: '{{ addslashes($authUser?->tahun_ajaran ?: '2025/2026') }}',
            nama_kelas: '{{ addslashes($authUser?->nama_kelas ?? '') }}',
            alamat: '{{ addslashes($authUser?->alamat ?? '') }}'
        },
        liveTransactions: {{ Js::from($groupedTransactions ?? []) }},
        refreshingLoans: false,
        returningLoanId: null,
        get activeLiveTransactions() {
            return (this.liveTransactions || []).filter(t => t.is_active);
        },
        sanitizeNisn() {
            this.identityForm.nisn = (this.identityForm.nisn || '').replace(/\D/g, '').slice(0, 10);
        },
        sanitizeNis() {
            this.identityForm.nis = (this.identityForm.nis || '').slice(0, 5);
        },
        get liveIdentityPct() {
            const nisVal = String(this.identityForm.nis || '').trim();
            const fields = [
                this.identityForm.no_telepon,
                this.identityForm.no_whatsapp,
                this.identityForm.tanggal_lahir,
                (this.identityForm.nisn || '').length === 10 ? this.identityForm.nisn : '',
                (nisVal.length >= 1 && nisVal.length <= 5) ? nisVal : '',
                this.identityForm.nama_sekolah,
                this.identityForm.tahun_ajaran,
                this.identityForm.nama_kelas,
                this.identityForm.alamat
            ];
            const filled = fields.filter(v => String(v || '').trim().length > 0).length;
            return Math.round((filled / fields.length) * 100);
        },
        async saveSchoolIdentity() {
            if (this.identitySaving) return;
            if ((this.identityForm.nisn || '').length !== 10) {
                $dispatch('notify-toast', { message: 'NISN wajib tepat 10 digit angka sesuai format resmi.', type: 'error' });
                return;
            }
            const nisClean = (this.identityForm.nis || '').trim();
            if (nisClean.length === 0 || nisClean.length > 5) {
                $dispatch('notify-toast', { message: 'NIS wajib diisi maksimal 5 karakter sesuai format umum sekolah.', type: 'error' });
                return;
            }
            this.identitySaving = true;
            try {
                const res = await fetch('{{ route('profil.identity.update') }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.identityForm)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.identityCompletion = data.completion || 100;
                    this.identityComplete = true;
                    if (this.identityForm.name) {
                        this.displayName = this.identityForm.name;
                    }
                    this.identityModalOpen = false;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal menyimpan identitas.');
                    $dispatch('notify-toast', { message: firstErr, type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Terjadi gangguan jaringan saat menyimpan identitas.', type: 'error' });
            } finally {
                this.identitySaving = false;
            }
        },
        async refreshLoansLive() {
            if (this.refreshingLoans) return;
            this.refreshingLoans = true;
            try {
                const res = await fetch('{{ route('profil.loans.live') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (res.ok && data.success && data.transactions) {
                    this.liveTransactions = data.transactions;
                    $dispatch('notify-toast', { message: 'Progres peminjaman berhasil diperbarui.', type: 'success' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal memuat status terbaru.', type: 'error' });
            } finally {
                this.refreshingLoans = false;
            }
        },
        async returnBookItem(loanId) {
            if (this.returningLoanId) return;
            this.returningLoanId = loanId;
            try {
                const res = await fetch(`/peminjaman/${loanId}/return`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                    await this.refreshLoansLive();
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal mengembalikan buku.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal menghubungi server.', type: 'error' });
            } finally {
                this.returningLoanId = null;
            }
        },
        get activePalette() {
            return this.colors[this.selectedColor] || this.colors['blue'];
        },
        get liveInitial() {
            const cleaned = (this.displayName || '').trim();
            return cleaned ? cleaned.charAt(0).toUpperCase() : 'P';
        },
        handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.removeAvatar = false;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
            };
            reader.readAsDataURL(file);
        },
        selectColor(colorKey) {
            this.selectedColor = colorKey;
            this.previewUrl = null;
            this.removeAvatar = true;
            if (this.$refs.avatarInput) {
                this.$refs.avatarInput.value = '';
            }
        },
        async saveNewPassword() {
            if (this.passForm.saving) return;
            const pwd = this.passForm.password || '';
            if (pwd.length < 8) {
                this.passForm.error = 'Kata sandi baru minimal 8 karakter.';
                return;
            }
            if (!/[a-zA-Z]/.test(pwd) || !/\d/.test(pwd)) {
                this.passForm.error = 'Kata sandi baru wajib mengandung huruf dan minimal 1 angka.';
                return;
            }
            if (pwd !== this.passForm.password_confirmation) {
                this.passForm.error = 'Konfirmasi kata sandi baru tidak cocok.';
                return;
            }
            this.passForm.saving = true;
            this.passForm.error = '';
            try {
                const res = await fetch('{{ route('profil.password.update') }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        verify_mode: this.passForm.verify_mode,
                        current_password: this.passForm.current_password,
                        verify_nisn: this.passForm.verify_nisn,
                        password: this.passForm.password,
                        password_confirmation: this.passForm.password_confirmation
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.passwordModalOpen = false;
                    this.passForm.current_password = '';
                    this.passForm.verify_nisn = '';
                    this.passForm.password = '';
                    this.passForm.password_confirmation = '';
                    this.passForm.error = '';
                    $dispatch('notify-toast', { message: data.message || 'Kata sandi berhasil diperbarui!', type: 'success' });
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                    this.passForm.error = firstErr || data.message || 'Gagal memperbarui kata sandi.';
                }
            } catch (e) {
                this.passForm.error = 'Terjadi gangguan jaringan saat menyimpan kata sandi.';
            } finally {
                this.passForm.saving = false;
            }
        }
    }"
    @keydown.escape.window="profileModalOpen = false; identityModalOpen = false; passwordModalOpen = false"
    class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-7 space-y-5 sm:space-y-7"
>

    <!-- Breadcrumb Pills -->
    <nav aria-label="Breadcrumb" class="flex items-center justify-between gap-2 flex-wrap text-xs">
        <div class="flex items-center gap-1.5">
            <a 
                href="{{ route('home') }}"
                aria-label="{{ __('Beranda') }}"
                class="w-8 h-8 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-600 flex items-center justify-center transition-colors"
            >
                <i class="ph-bold ph-house text-sm" aria-hidden="true"></i>
            </a>
            <i class="ph-bold ph-caret-right text-[10px] text-slate-400" aria-hidden="true"></i>
            <span class="px-3.5 py-1.5 rounded-full bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 font-bold">
                {{ __('Riwayat & Progres Peminjaman') }}
            </span>
        </div>

        @auth
            <button 
                type="button"
                @click="refreshLoansLive()"
                :disabled="refreshingLoans"
                class="min-h-[34px] px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 font-bold text-[11px] inline-flex items-center gap-1.5 transition-colors"
            >
                <i class="ph-bold ph-arrows-clockwise text-xs text-blue-600 dark:text-blue-400" :class="refreshingLoans ? 'animate-spin' : ''" aria-hidden="true"></i>
                <span x-text="refreshingLoans ? 'Memperbarui...' : 'Segarkan Status'"></span>
            </button>
        @endauth
    </nav>

    <!-- Banner profil -->
    @guest
        <section class="rounded-3xl overflow-hidden bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm grid grid-cols-1 md:grid-cols-12">
            <div class="md:col-span-8 bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-600 p-6 sm:p-9 text-white space-y-2.5">
                <div class="w-11 h-11 rounded-full bg-white/15 flex items-center justify-center text-2xl">
                    <i class="ph-bold ph-user-circle" aria-hidden="true"></i>
                </div>
                <h1 class="text-xl sm:text-3xl font-extrabold tracking-tight">
                    {{ __('Profil pembaca') }}
                </h1>
                <p class="text-xs sm:text-sm text-blue-100 max-w-lg leading-relaxed">
                    {{ __('Login untuk mengelola profil, identitas warga sekolah, progres alur peminjaman buku, dan wishlist di PINJAM BU-PUS.') }}
                </p>
            </div>
            <div class="md:col-span-4 p-5 sm:p-8 flex items-center justify-center bg-white dark:bg-slate-900">
                <button 
                    type="button"
                    @click="openAuthModal('{{ __('Profil pembaca') }}')"
                    class="w-full min-h-[46px] px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm transition-colors"
                >
                    <i class="ph-bold ph-sign-in text-base" aria-hidden="true"></i>
                    <span>{{ __('Login akun pembaca') }}</span>
                </button>
            </div>
        </section>
    @else
        @php
            $userPalette = $authUser->color_palette;
        @endphp
        <section class="rounded-3xl overflow-hidden bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm grid grid-cols-1 lg:grid-cols-12">
            <div 
                class="lg:col-span-7 p-5 sm:p-7 text-white flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-5 transition-all duration-300"
                style="background: {{ $userPalette['banner'] }};"
                :style="`background: ${activePalette.banner};`"
            >
                @if($authUser->avatar_url)
                    <img 
                        src="{{ $authUser->avatar_url }}" 
                        alt="{{ $authUser->name }}" 
                        width="84"
                        height="84"
                        class="w-18 h-18 sm:w-22 sm:h-22 rounded-full object-cover border-4 border-white/25 shadow-md shrink-0"
                    >
                @else
                    <div 
                        class="w-18 h-18 sm:w-22 sm:h-22 rounded-full font-black text-2xl sm:text-3xl flex items-center justify-center border-4 border-white/30 shadow-md shrink-0 transition-colors duration-300"
                        style="background-color: {{ $userPalette['bg'] }}; color: {{ $userPalette['text'] }};"
                        :style="`background-color: ${activePalette.bg}; color: ${activePalette.text};`"
                    >
                        <span x-text="liveInitial">{{ $authUser->initial }}</span>
                    </div>
                @endif

                <div class="space-y-1 min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-lg sm:text-2xl font-extrabold tracking-tight truncate" x-text="displayName || '{{ addslashes($authUser->name) }}'">
                            {{ $authUser->name }}
                        </h1>
                        @if($authUser->isAdmin())
                            <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-extrabold uppercase tracking-wider">
                                Admin
                            </span>
                        @elseif($authUser->isPustakawan())
                            <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-extrabold uppercase tracking-wider">
                                Pustakawan
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-bold">
                                Warga Sekolah
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-white/90 font-semibold">
                        <span x-text="'@' + (username || '{{ addslashes($authUser->display_username) }}')">{{ '@' . $authUser->display_username }}</span> &bull; {{ $authUser->email }}
                    </p>

                    <!-- Compact School Identity Badges -->
                    <div class="pt-1 flex flex-wrap items-center gap-1.5 text-[11px]">
                        @if($authUser->nisn)
                            <span class="px-2.5 py-0.5 rounded-lg bg-white/15 text-white font-bold">
                                NISN: {{ $authUser->nisn }}
                            </span>
                        @endif
                        @if($authUser->nama_kelas)
                            <span class="px-2.5 py-0.5 rounded-lg bg-white/15 text-white font-bold">
                                Kelas: {{ $authUser->nama_kelas }}
                            </span>
                        @endif
                        <span 
                            class="px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1"
                            :class="identityComplete ? 'bg-emerald-400/25 text-emerald-100' : 'bg-amber-400/25 text-amber-100'"
                        >
                            <i class="ph-fill" :class="identityComplete ? 'ph-SealCheck' : 'ph-warning-circle'" aria-hidden="true"></i>
                            <span x-text="identityComplete ? 'Identitas Sekolah Lengkap' : ('Identitas ' + identityCompletion + '%')"></span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 p-4 sm:p-6 flex flex-col justify-center gap-2.5 bg-white dark:bg-slate-900">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <button 
                        type="button"
                        @click="identityModalOpen = true"
                        class="min-h-[42px] px-3.5 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition-colors"
                    >
                        <i class="ph-bold ph-identification-card text-base" aria-hidden="true"></i>
                        <span>{{ __('Data Sekolah') }}</span>
                    </button>

                    <button 
                        type="button"
                        @click="profileModalOpen = true"
                        class="min-h-[42px] px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 text-slate-800 dark:text-slate-100 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <i class="ph-bold ph-pencil-simple text-base text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                        <span>{{ __('Edit Profil') }}</span>
                    </button>

                    <button 
                        type="button"
                        @click="passwordModalOpen = true; passForm.error = ''"
                        class="min-h-[42px] px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 text-slate-800 dark:text-slate-100 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <i class="ph-bold ph-key text-base text-amber-500" aria-hidden="true"></i>
                        <span>{{ __('Ubah Sandi') }}</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    @if($authUser->isAdmin())
                        <a 
                            href="{{ route('admin.dashboard') }}"
                            class="flex-1 min-h-[40px] px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <i class="ph-bold ph-shield-check text-sm" aria-hidden="true"></i>
                            <span>{{ __('Panel Admin') }}</span>
                        </a>
                    @elseif($authUser->isPustakawan())
                        <a 
                            href="{{ route('pustakawan.dashboard') }}"
                            class="flex-1 min-h-[40px] px-3 py-2 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/50 hover:border-emerald-400 text-emerald-700 dark:text-emerald-300 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <i class="ph-bold ph-book-open-text text-sm" aria-hidden="true"></i>
                            <span>{{ __('Panel Pustakawan') }}</span>
                        </a>
                    @else
                        <a 
                            href="{{ route('buku.checkout.show') }}"
                            class="flex-1 min-h-[40px] px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-900 bg-blue-50/70 dark:bg-blue-950/50 hover:border-blue-400 text-blue-700 dark:text-blue-300 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <i class="ph-bold ph-clipboard-text text-sm" aria-hidden="true"></i>
                            <span>{{ __('Form Peminjaman') }}</span>
                        </a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST" class="flex-1">
                        @csrf
                        <button 
                            type="submit"
                            class="w-full min-h-[40px] px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-800 bg-slate-50 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <i class="ph-bold ph-sign-out text-sm" aria-hidden="true"></i>
                            <span>{{ __('Keluar') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Peringatan kelengkapan identitas -->
        <div 
            x-cloak
            x-show="!identityComplete"
            class="rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        >
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 mt-0.5">
                    <i class="ph-bold ph-identification-badge text-lg" aria-hidden="true"></i>
                </div>
                <div class="space-y-1">
                    <h2 class="text-xs sm:text-sm font-extrabold text-amber-950 dark:text-amber-200">
                        Lengkapi Identitas Warga Sekolah Sebelum Meminjam Buku (<span x-text="identityCompletion"></span>%)
                    </h2>
                    <p class="text-[11px] sm:text-xs text-amber-800 dark:text-amber-300/90 leading-relaxed">
                        Wajib mengisi Nomor Telepon, WhatsApp, Tanggal Lahir, NISN, NIS, Kelas, Tahun Ajaran, dan Alamat agar pustakawan dapat memverifikasi peminjaman Anda.
                    </p>
                </div>
            </div>
            <button 
                type="button"
                @click="identityModalOpen = true"
                class="self-start sm:self-center shrink-0 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition-colors"
            >
                Lengkapi Sekarang
            </button>
        </div>
    @endguest

    <!-- Riwayat dan tab aktivitas -->
    <section class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <i class="ph-bold ph-clock-counter-clockwise text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                    <span>{{ __('Riwayat Peminjaman') }}</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl">
                    {{ __('Pantau status persetujuan dan waktu pengembalian buku Anda di sini.') }}
                </p>
            </div>
            <a 
                href="{{ route('perpustakaan.index') }}"
                class="self-start sm:self-auto min-h-[40px] px-4 py-2 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 text-slate-800 dark:text-slate-100 text-xs font-bold inline-flex items-center gap-1.5 transition-colors shrink-0"
            >
                <span>{{ __('Cari Buku Lain') }}</span>
                <i class="ph-bold ph-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>

        <!-- Tab navigasi -->
        <div role="tablist" aria-label="{{ __('Aktivitas Pinjaman') }}" class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
            <!-- Box 1: Pinjaman Aktif -->
            <button 
                type="button"
                role="tab"
                :aria-selected="activeTab === 'aktif'"
                @click="activeTab = 'aktif'"
                class="relative text-left p-3 sm:p-4 rounded-2xl border transition-all flex items-center gap-3 overflow-hidden"
                :class="activeTab === 'aktif' 
                    ? 'bg-white dark:bg-slate-900 border-blue-500 ring-2 ring-blue-500/15 shadow-sm' 
                    : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-blue-300'"
            >
                <div 
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-base sm:text-lg shrink-0 transition-colors"
                    :class="activeTab === 'aktif' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                >
                    <i class="ph-bold ph-book-open" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <span class="block text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate">{{ __('Pinjaman Aktif') }}</span>
                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 truncate">
                        <span x-text="activeLiveTransactions.length"></span> {{ __('transaksi berjalan') }}
                    </span>
                </div>
            </button>

            <!-- Box 2: Riwayat -->
            <button 
                type="button"
                role="tab"
                :aria-selected="activeTab === 'riwayat'"
                @click="activeTab = 'riwayat'"
                class="relative text-left p-3 sm:p-4 rounded-2xl border transition-all flex items-center gap-3 overflow-hidden"
                :class="activeTab === 'riwayat' 
                    ? 'bg-white dark:bg-slate-900 border-blue-500 ring-2 ring-blue-500/15 shadow-sm' 
                    : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-blue-300'"
            >
                <div 
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-base sm:text-lg shrink-0 transition-colors"
                    :class="activeTab === 'riwayat' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                >
                    <i class="ph-bold ph-clock-counter-clockwise" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <span class="block text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate">{{ __('Riwayat') }}</span>
                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 truncate">
                        <span x-text="liveTransactions.length"></span> {{ __('semua transaksi') }}
                    </span>
                </div>
            </button>

            <!-- Box 3: Statistik -->
            <button 
                type="button"
                role="tab"
                :aria-selected="activeTab === 'statistik'"
                @click="activeTab = 'statistik'"
                class="relative text-left p-3 sm:p-4 rounded-2xl border transition-all flex items-center gap-3 overflow-hidden"
                :class="activeTab === 'statistik' 
                    ? 'bg-white dark:bg-slate-900 border-blue-500 ring-2 ring-blue-500/15 shadow-sm' 
                    : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-blue-300'"
            >
                <div 
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-base sm:text-lg shrink-0 transition-colors"
                    :class="activeTab === 'statistik' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                >
                    <i class="ph-bold ph-chart-line-up" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <span class="block text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate">{{ __('Statistik') }}</span>
                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $stats['ulasan'] }} {{ __('ulasan dikirim') }}</span>
                </div>
            </button>

            <!-- Box 4: Wishlist -->
            <button 
                type="button"
                role="tab"
                :aria-selected="activeTab === 'wishlist'"
                @click="activeTab = 'wishlist'"
                class="relative text-left p-3 sm:p-4 rounded-2xl border transition-all flex items-center gap-3 overflow-hidden"
                :class="activeTab === 'wishlist' 
                    ? 'bg-white dark:bg-slate-900 border-blue-500 ring-2 ring-blue-500/15 shadow-sm' 
                    : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-blue-300'"
            >
                <div 
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-base sm:text-lg shrink-0 transition-colors"
                    :class="activeTab === 'wishlist' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                >
                    <i class="ph-bold ph-heart" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <span class="block text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate">{{ __('Wishlist') }}</span>
                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $stats['wishlist'] }} {{ __('buku disimpan') }}</span>
                </div>
            </button>
        </div>

        <!-- Daftar pinjaman aktif dan riwayat -->
        <div x-show="activeTab === 'aktif' || activeTab === 'riwayat'" class="pt-2 space-y-4">
            <!-- Empty State -->
            <div 
                x-show="(activeTab === 'aktif' ? activeLiveTransactions : liveTransactions).length === 0"
                class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 py-14 px-4 text-center space-y-3"
            >
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                    <i class="ph ph-book-open" aria-hidden="true"></i>
                </div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white" x-text="activeTab === 'aktif' ? '{{ __('Belum ada pinjaman aktif') }}' : '{{ __('Belum ada riwayat peminjaman') }}'"></h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                    {{ __('Pilih buku dari perpustakaan lalu ajukan peminjaman untuk memantau alur persetujuan pustakawan secara real-time.') }}
                </p>
                <div class="pt-1">
                    <a href="{{ route('perpustakaan.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-colors">
                        <i class="ph-bold ph-books" aria-hidden="true"></i>
                        <span>{{ __('Jelajahi Perpustakaan') }}</span>
                    </a>
                </div>
            </div>

            <!-- Kartu transaksi -->
            <template x-for="trx in (activeTab === 'aktif' ? activeLiveTransactions : liveTransactions)" :key="trx.kode_transaksi">
                <article class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden transition-all">
                    
                    <!-- Top Section: Transaction Code, Book Count, Date & Status Pill -->
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/70 px-2.5 py-0.5 rounded-md border border-blue-200/60 dark:border-blue-800/60">
                                    KODE TRX: #<span x-text="trx.kode_transaksi"></span>
                                </span>
                                <template x-if="trx.prioritas === 'penting'">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/70 px-2.5 py-0.5 rounded-md border border-rose-200 dark:border-rose-800 inline-flex items-center gap-1">
                                        <i class="ph-fill ph-lightning text-xs" aria-hidden="true"></i>
                                        Prioritas Penting
                                    </span>
                                </template>
                            </div>
                            <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
                                Peminjaman <span x-text="trx.total_books"></span> Buku
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                <i class="ph-bold ph-calendar-blank text-slate-400" aria-hidden="true"></i>
                                <span>Tanggal Pengajuan: <strong class="text-slate-700 dark:text-slate-300" x-text="trx.tanggal_pengajuan"></strong></span>
                            </p>
                        </div>

                        <div class="flex flex-col sm:items-end gap-1.5">
                            <!-- Status Pill -->
                            <span 
                                class="self-start sm:self-end inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-extrabold border"
                                :class="{
                                    'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800': trx.status === 'menunggu_approval',
                                    'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800': trx.status === 'sedang_dipinjam',
                                    'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800': trx.status === 'terlambat' || trx.status === 'ditolak',
                                    'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800': trx.status === 'selesai'
                                }"
                            >
                                <i 
                                    class="ph-bold text-sm"
                                    :class="{
                                        'ph-spinner animate-spin': trx.status === 'menunggu_approval',
                                        'ph-book-open-text': trx.status === 'sedang_dipinjam',
                                        'ph-warning-circle': trx.status === 'terlambat' || trx.status === 'ditolak',
                                        'ph-check-circle': trx.status === 'selesai'
                                    }"
                                    aria-hidden="true"
                                ></i>
                                <span x-text="trx.status_label"></span>
                            </span>

                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                <template x-if="trx.tanggal_kembali">
                                    <span>Dikembalikan pada: <strong class="text-emerald-600 dark:text-emerald-400" x-text="trx.tanggal_kembali"></strong></span>
                                </template>
                                <template x-if="!trx.tanggal_kembali">
                                    <span>Batas Pengembalian: <strong class="text-slate-700 dark:text-slate-200" x-text="trx.tenggat_kembali"></strong></span>
                                </template>
                            </p>
                        </div>
                    </div>

                    <!-- Middle Section: 4-Step Animated Progress Tracker (Diajukan -> Diproses -> Dipinjam -> Dikembalikan) -->
                    <div class="px-4 sm:px-8 py-6 sm:py-8 bg-slate-50/60 dark:bg-slate-950/40 border-b border-slate-100 dark:border-slate-800">
                        <template x-if="trx.status === 'ditolak'">
                            <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2.5">
                                <i class="ph-fill ph-x-circle text-lg text-rose-500 shrink-0" aria-hidden="true"></i>
                                <div>
                                    <span class="font-extrabold">Pengajuan peminjaman ditolak oleh Pustakawan.</span>
                                    <span x-show="trx.catatan_pustakawan" class="block mt-0.5 text-[11px]" x-text="'Catatan: ' + trx.catatan_pustakawan"></span>
                                </div>
                            </div>
                        </template>

                        <template x-if="trx.status !== 'ditolak'">
                            <div class="max-w-3xl mx-auto">
                                <!-- Animated Progress Bar Track -->
                                <div class="relative">
                                    <!-- Background Line -->
                                    <div class="absolute top-5 left-6 right-6 h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                                        <!-- Active Colored Line with Shimmer Animation -->
                                        <div 
                                            class="h-full rounded-full transition-all duration-700 ease-out relative overflow-hidden"
                                            :class="trx.status === 'selesai' ? 'bg-emerald-500' : (trx.status === 'terlambat' ? 'bg-rose-500' : 'bg-blue-600')"
                                            :style="`width: ${trx.progress_pct}%`"
                                        >
                                            <div x-show="trx.is_active" class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/45 to-transparent animate-shimmer-bar"></div>
                                        </div>
                                    </div>

                                    <!-- 4 Step Circles -->
                                    <div class="relative z-10 grid grid-cols-4 gap-2 text-center">
                                        
                                        <!-- Step 1: Diajukan -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div 
                                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-extrabold border-2 transition-all shadow-sm"
                                                :class="trx.step >= 1 
                                                    ? 'bg-blue-600 border-blue-600 text-white' 
                                                    : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-400'"
                                            >
                                                <i class="ph-bold ph-check text-base" aria-hidden="true"></i>
                                            </div>
                                            <div>
                                                <span class="block text-[11px] sm:text-xs font-extrabold text-slate-900 dark:text-white">Diajukan</span>
                                                <span class="hidden sm:block text-[10px] text-slate-500 dark:text-slate-400">Form terkirim</span>
                                            </div>
                                        </div>

                                        <!-- Step 2: Diproses -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div 
                                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-extrabold border-2 transition-all shadow-sm"
                                                :class="trx.step > 2 
                                                    ? 'bg-blue-600 border-blue-600 text-white' 
                                                    : (trx.step === 2 
                                                        ? 'bg-amber-500 border-amber-500 text-white ring-4 ring-amber-500/20' 
                                                        : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-400')"
                                            >
                                                <template x-if="trx.step > 2">
                                                    <i class="ph-bold ph-check text-base" aria-hidden="true"></i>
                                                </template>
                                                <template x-if="trx.step === 2">
                                                    <i class="ph-bold ph-spinner animate-spin text-base" aria-hidden="true"></i>
                                                </template>
                                                <template x-if="trx.step < 2">
                                                    <span>2</span>
                                                </template>
                                            </div>
                                            <div>
                                                <span class="block text-[11px] sm:text-xs font-extrabold" :class="trx.step >= 2 ? 'text-slate-900 dark:text-white' : 'text-slate-400'">Diproses</span>
                                                <span class="hidden sm:block text-[10px] text-slate-500 dark:text-slate-400">Verifikasi pustakawan</span>
                                            </div>
                                        </div>

                                        <!-- Step 3: Dipinjam -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div 
                                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-extrabold border-2 transition-all shadow-sm"
                                                :class="trx.step > 3 
                                                    ? 'bg-blue-600 border-emerald-600 text-white' 
                                                    : (trx.step === 3 
                                                        ? (trx.status === 'terlambat' ? 'bg-rose-600 border-rose-600 text-white ring-4 ring-rose-500/20' : 'bg-blue-600 border-blue-600 text-white ring-4 ring-blue-500/20') 
                                                        : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-400')"
                                            >
                                                <template x-if="trx.step > 3">
                                                    <i class="ph-bold ph-check text-base" aria-hidden="true"></i>
                                                </template>
                                                <template x-if="trx.step === 3">
                                                    <i class="ph-bold ph-book-open-text text-base animate-pulse" aria-hidden="true"></i>
                                                </template>
                                                <template x-if="trx.step < 3">
                                                    <span>3</span>
                                                </template>
                                            </div>
                                            <div>
                                                <span class="block text-[11px] sm:text-xs font-extrabold" :class="trx.step >= 3 ? 'text-slate-900 dark:text-white' : 'text-slate-400'">Dipinjam</span>
                                                <span class="hidden sm:block text-[10px] text-slate-500 dark:text-slate-400">Buku dibawa siswa</span>
                                            </div>
                                        </div>

                                        <!-- Step 4: Dikembalikan -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div 
                                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-extrabold border-2 transition-all shadow-sm"
                                                :class="trx.step === 4 
                                                    ? 'bg-blue-600 border-emerald-600 text-white ring-4 ring-emerald-500/20' 
                                                    : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-400'"
                                            >
                                                <template x-if="trx.step === 4">
                                                    <i class="ph-bold ph-check text-base" aria-hidden="true"></i>
                                                </template>
                                                <template x-if="trx.step < 4">
                                                    <span>4</span>
                                                </template>
                                            </div>
                                            <div>
                                                <span class="block text-[11px] sm:text-xs font-extrabold" :class="trx.step === 4 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'">Dikembalikan</span>
                                                <span class="hidden sm:block text-[10px] text-slate-500 dark:text-slate-400">Selesai tepat waktu</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Buku yang dipinjam -->
                    <div class="p-4 sm:p-6 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                BUKU YANG DIAJUKAN
                            </h4>
                            <template x-if="trx.keperluan">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Keperluan: <strong class="text-slate-700 dark:text-slate-200" x-text="trx.keperluan"></strong>
                                </span>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <template x-for="book in trx.items" :key="book.id">
                                <div class="p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <a :href="book.url" class="shrink-0">
                                            <img :src="book.cover_url" :alt="book.judul" class="w-12 h-16 object-cover rounded-xl bg-slate-200 dark:bg-slate-700 shadow-sm">
                                        </a>
                                        <div class="min-w-0">
                                            <a :href="book.url" class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate block" x-text="book.judul"></a>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="book.pengarang"></p>
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded-md bg-blue-100/80 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                                                <span x-text="book.jumlah"></span> Buku
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Quick Return Action if Currently Borrowed -->
                                    <template x-if="book.status === 'sedang_dipinjam' || book.status === 'terlambat'">
                                        <button 
                                            type="button"
                                            @click="returnBookItem(book.id)"
                                            :disabled="returningLoanId === book.id"
                                            class="shrink-0 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] inline-flex items-center gap-1 transition-colors disabled:opacity-50"
                                        >
                                            <i class="ph-bold" :class="returningLoanId === book.id ? 'ph-spinner animate-spin' : 'ph-arrow-u-up-left'" aria-hidden="true"></i>
                                            <span>Kembalikan</span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                </article>
            </template>
        </div>

        <!-- Tab statistik -->
        <div x-show="activeTab === 'statistik'" style="display: none;" class="pt-2 space-y-5">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">{{ __('Pinjaman Aktif') }}</span>
                    <span class="text-2xl sm:text-3xl font-black text-blue-600 dark:text-blue-400 mt-1 block">{{ $stats['aktif'] }}</span>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">{{ __('Total Pinjaman') }}</span>
                    <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1 block">{{ $stats['total_pinjaman'] }}</span>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">{{ __('Sudah Dikembalikan') }}</span>
                    <span class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1 block">{{ $stats['selesai'] }}</span>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">{{ __('Ulasan Ditulis') }}</span>
                    <span class="text-2xl sm:text-3xl font-black text-amber-500 mt-1 block">{{ $stats['ulasan'] }}</span>
                </div>
            </div>

            @if($ulasanUser->isNotEmpty())
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-6 space-y-3">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Ulasan Ditulis') }}</h3>
                    <div class="space-y-2.5">
                        @foreach($ulasanUser as $rev)
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/70 space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <a href="{{ $rev->buku ? route('buku.show', $rev->buku->id) : '#' }}" class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white hover:text-blue-600 truncate">
                                        {{ $rev->buku->judul ?? 'Buku' }}
                                    </a>
                                    <span class="text-xs font-bold text-amber-500 shrink-0">★ {{ $rev->bintang }}/5</span>
                                </div>
                                @if($rev->ulasan)
                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $rev->ulasan }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Tab wishlist -->
        <div x-show="activeTab === 'wishlist'" style="display: none;" class="pt-2">
            @if($wishlistBooks->isEmpty())
                <div class="py-16 px-4 text-center space-y-2">
                    <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-2 text-2xl">
                        <i class="ph ph-heart" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">{{ __('Belum ada buku di wishlist') }}</h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                        {{ __('Tekan ikon hati pada kartu atau detail buku untuk menyimpan bacaan incaranmu.') }}
                    </p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-5">
                    @foreach($wishlistBooks as $book)
                        @include('partials.book-card', ['book' => $book])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- Modal identitas siswa -->
    @auth
        <div 
            x-cloak
            x-show="identityModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 !mt-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-label="Identitas Warga Sekolah"
        >
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="identityModalOpen = false"></div>

            <div 
                class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-7 z-10 space-y-5 my-auto"
                @click.stop
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                            <i class="ph-bold ph-identification-card" aria-hidden="true"></i>
                            Wajib Untuk Peminjaman Buku
                        </span>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-0.5">
                            Identitas Lengkap Warga Sekolah
                        </h2>
                    </div>
                    <button 
                        type="button"
                        @click="identityModalOpen = false"
                        aria-label="Close"
                        class="w-9 h-9 rounded-full text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors shrink-0"
                    >
                        <i class="ph-bold ph-x text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Live Completion Progress Bar -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-700/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Kelengkapan Data Peminjam</span>
                        <span class="font-extrabold text-blue-600 dark:text-blue-400" x-text="liveIdentityPct + '%'"></span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div 
                            class="h-full rounded-full transition-all duration-300"
                            :class="liveIdentityPct === 100 ? 'bg-emerald-500' : 'bg-blue-600'"
                            :style="`width: ${liveIdentityPct}%`"
                        ></div>
                    </div>
                </div>

                <form @submit.prevent="saveSchoolIdentity()" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- Nama Lengkap Siswa -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Nama Lengkap Siswa / Warga Sekolah <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text"
                                x-model="identityForm.name"
                                required
                                maxlength="120"
                                placeholder="Nama lengkap sesuai identitas sekolah"
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none"
                            >
                        </div>

                        <!-- NISN (Strictly 10 digits) -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    NISN (Wajib 10 Digit) <span class="text-rose-500">*</span>
                                </label>
                                <span 
                                    class="text-[10px] font-extrabold px-1.5 py-0.5 rounded"
                                    :class="(identityForm.nisn || '').length === 10 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'"
                                    x-text="(identityForm.nisn || '').length + '/10 digit'"
                                ></span>
                            </div>
                            <input 
                                type="text"
                                x-model="identityForm.nisn"
                                @input="sanitizeNisn()"
                                inputmode="numeric"
                                maxlength="10"
                                required
                                placeholder="Contoh: 0061234567"
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white outline-none font-mono tracking-wider"
                            >
                        </div>

                        <!-- NIS (Max 5 characters) -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    NIS Sekolah (Maks. 5 Karakter) <span class="text-rose-500">*</span>
                                </label>
                                <span 
                                    class="text-[10px] font-extrabold px-1.5 py-0.5 rounded"
                                    :class="(identityForm.nis || '').trim().length >= 1 && (identityForm.nis || '').trim().length <= 5 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'"
                                    x-text="(identityForm.nis || '').length + '/5 maks'"
                                ></span>
                            </div>
                            <input 
                                type="text"
                                x-model="identityForm.nis"
                                @input="sanitizeNis()"
                                required
                                maxlength="5"
                                placeholder="Contoh: 23013"
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white outline-none font-mono tracking-wider"
                            >
                        </div>

                        <!-- Nama Sekolah -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Nama Sekolah <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text"
                                x-model="identityForm.nama_sekolah"
                                required
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none"
                            >
                        </div>

                        <!-- Nama Kelas / Jurusan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Kelas / Jurusan <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text"
                                x-model="identityForm.nama_kelas"
                                required
                                placeholder="Contoh: XII RPL B"
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none"
                            >
                        </div>

                        <!-- Tahun Ajaran -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Tahun Ajaran <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                x-model="identityForm.tahun_ajaran"
                                required
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none"
                            >
                                <option value="2024/2025">2024/2025</option>
                                <option value="2025/2026">2025/2026</option>
                                <option value="2026/2027">2026/2027</option>
                            </select>
                        </div>

                        <!-- Tanggal Lahir -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Tanggal Lahir <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="date"
                                x-model="identityForm.tanggal_lahir"
                                required
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none"
                            >
                        </div>

                        <!-- Nomor Telepon -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Nomor Telepon <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="tel"
                                x-model="identityForm.no_telepon"
                                required
                                placeholder="0812xxxxxxxx"
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none"
                            >
                        </div>

                        <!-- Nomor WhatsApp -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Nomor WhatsApp Aktif <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="tel"
                                x-model="identityForm.no_whatsapp"
                                required
                                placeholder="0812xxxxxxxx"
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none"
                            >
                        </div>

                        <!-- Alamat / Lokasi -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Lokasi / Alamat Tempat Tinggal <span class="text-rose-500">*</span>
                            </label>
                            <textarea 
                                rows="2"
                                x-model="identityForm.alamat"
                                required
                                placeholder="Nama jalan, RT/RW, Kelurahan, Kecamatan, Kota..."
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <button 
                        type="submit"
                        :disabled="identitySaving"
                        class="w-full min-h-[46px] rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs sm:text-sm py-3 shadow-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-60"
                    >
                        <i class="ph-bold text-base" :class="identitySaving ? 'ph-spinner animate-spin' : 'ph-floppy-disk'" aria-hidden="true"></i>
                        <span x-text="identitySaving ? 'Menyimpan Identitas...' : 'Simpan Identitas Warga Sekolah'"></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal edit profil -->
        <div 
            x-show="profileModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 !mt-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
            style="display: none;"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Profil pembaca') }}"
        >
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="profileModalOpen = false"></div>

            <div 
                class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-8 z-10 space-y-5 my-auto"
                @click.stop
            >
                <!-- Modal Header -->
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            {{ __('Profil pembaca') }}
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ __('Atur username unik, nama tampilan, unggah foto profil, atau gunakan inisial abjad nama dengan warna pilihanmu.') }}
                        </p>
                    </div>
                    <button 
                        type="button"
                        @click="profileModalOpen = false"
                        aria-label="Close"
                        class="w-9 h-9 rounded-full text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors shrink-0"
                    >
                        <i class="ph-bold ph-x text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                @if($errors->any())
                    <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs space-y-1">
                        @foreach($errors->all() as $error)
                            <p class="font-semibold">&bull; {{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('profil.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="profile_color" :value="selectedColor">
                    <input type="hidden" name="remove_avatar" :value="removeAvatar ? '1' : '0'">

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-stretch">
                        
                        <!-- Left Live Preview Card (Matches User Selected Profile Banner Color) -->
                        <div 
                            class="sm:col-span-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-5 flex flex-col items-center justify-center text-center text-white shadow-sm transition-all duration-300"
                            :style="`background: ${activePalette.banner};`"
                        >
                            <div 
                                class="w-28 h-28 sm:w-32 sm:h-32 rounded-full flex items-center justify-center overflow-hidden border-4 border-white/30 shadow-md transition-colors"
                                :style="`background-color: ${activePalette.bg}; color: ${activePalette.text};`"
                            >
                                <template x-if="previewUrl">
                                    <img :src="previewUrl" alt="Avatar Preview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!previewUrl">
                                    <span class="text-4xl sm:text-5xl font-black select-none" x-text="liveInitial"></span>
                                </template>
                            </div>

                            <p class="mt-4 text-base sm:text-lg font-extrabold text-white truncate max-w-full px-2" x-text="displayName || '{{ __('Pembaca') }}'"></p>
                            <p class="text-xs sm:text-sm font-semibold text-white/85 truncate max-w-full px-2" x-text="'@' + (username || 'pembaca')"></p>
                        </div>

                        <!-- Right Form Fields -->
                        <div class="sm:col-span-7 space-y-4">
                            <div>
                                <label for="profile-username" class="block text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                                    {{ __('Username') }}
                                </label>
                                <input 
                                    id="profile-username"
                                    type="text"
                                    name="username"
                                    x-model="username"
                                    required
                                    maxlength="40"
                                    class="w-full min-h-[44px] rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 focus:bg-white dark:focus:bg-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white outline-none transition-colors"
                                >
                            </div>

                            <div>
                                <label for="profile-display-name" class="block text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                                    {{ __('Nama tampilan') }}
                                </label>
                                <input 
                                    id="profile-display-name"
                                    type="text"
                                    name="name"
                                    x-model="displayName"
                                    required
                                    maxlength="100"
                                    class="w-full min-h-[44px] rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-blue-600 focus:bg-white dark:focus:bg-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white outline-none transition-colors"
                                >
                            </div>

                            <!-- 6 Profile Color Circles -->
                            <div>
                                <span class="block text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200 mb-2">
                                    {{ __('Warna profil') }}
                                </span>
                                <div class="flex flex-wrap items-center gap-2.5">
                                    @foreach($profileColors as $colorKey => $palette)
                                        <button 
                                            type="button"
                                            @click="selectColor('{{ $colorKey }}')"
                                            aria-label="{{ $palette['label'] }}"
                                            title="{{ $palette['label'] }}"
                                            class="w-10 h-10 rounded-full p-1 transition-all flex items-center justify-center"
                                            :class="selectedColor === '{{ $colorKey }}' ? 'ring-2 ring-blue-600 scale-105' : 'ring-1 ring-slate-200 dark:ring-slate-700 hover:scale-105'"
                                        >
                                            <span class="w-full h-full rounded-full block" style="background-color: {{ $palette['bg'] }};"></span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Upload Photo -->
                            <div class="pt-1 space-y-2">
                                <span class="block text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">
                                    {{ __('Foto profil') }}
                                </span>

                                <input 
                                    type="file"
                                    name="avatar_file"
                                    x-ref="avatarInput"
                                    accept="image/jpeg,image/png,image/webp"
                                    @change="handleFileChange($event)"
                                    class="hidden"
                                >

                                <div class="flex flex-wrap items-center gap-2">
                                    <button 
                                        type="button"
                                        @click="$refs.avatarInput.click()"
                                        class="min-h-[40px] px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-500 text-slate-800 dark:text-slate-100 text-xs font-bold inline-flex items-center gap-1.5 transition-colors"
                                    >
                                        <i class="ph-bold ph-upload-simple text-sm text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                                        <span>{{ __('Unggah Foto') }}</span>
                                    </button>
                                </div>

                                <p class="text-[11px] text-slate-400 dark:text-slate-500 leading-relaxed">
                                    {{ __('Format JPG, PNG, atau WebP (maks. 2MB). Jika dikosongkan, otomatis menampilkan abjad nama.') }}
                                </p>
                            </div>
                        </div>

                    </div>

                    <button 
                        type="submit"
                        class="w-full min-h-[48px] rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm py-3 shadow-sm transition-colors"
                    >
                        {{ __('Simpan profil') }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal ubah kata sandi -->
        <div 
            x-cloak
            x-show="passwordModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 !mt-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
            style="display: none;"
            role="dialog"
            aria-modal="true"
            aria-label="Ubah Kata Sandi Akun"
        >
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="passwordModalOpen = false"></div>

            <div 
                class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-7 z-10 space-y-4 my-auto"
                @click.stop
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                            <i class="ph-bold ph-key" aria-hidden="true"></i>
                            Keamanan Akun
                        </span>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-0.5">
                            Ubah / Reset Kata Sandi
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Ganti kata sandi akun Anda menggunakan kata sandi lama atau verifikasi NISN jika lupa.
                        </p>
                    </div>
                    <button 
                        type="button"
                        @click="passwordModalOpen = false"
                        aria-label="Close"
                        class="w-9 h-9 rounded-full text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors shrink-0"
                    >
                        <i class="ph-bold ph-x text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Pilihan metode verifikasi -->
                <div class="grid grid-cols-2 gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80">
                    <button 
                        type="button"
                        @click="passForm.verify_mode = 'current_password'; passForm.error = ''"
                        class="py-2 px-2.5 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5"
                        :class="passForm.verify_mode === 'current_password' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'"
                    >
                        <i class="ph-bold ph-lock-key text-sm"></i>
                        <span>Pakai Sandi Lama</span>
                    </button>
                    <button 
                        type="button"
                        @click="passForm.verify_mode = 'nisn'; passForm.error = ''"
                        class="py-2 px-2.5 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5"
                        :class="passForm.verify_mode === 'nisn' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'"
                    >
                        <i class="ph-bold ph-identification-card text-sm"></i>
                        <span>Lupa? Pakai NISN</span>
                    </button>
                </div>

                <!-- Pesan error -->
                <div 
                    x-show="passForm.error"
                    x-transition
                    class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2"
                >
                    <i class="ph-fill ph-warning-circle text-base text-rose-500 shrink-0" aria-hidden="true"></i>
                    <span x-text="passForm.error"></span>
                </div>

                <form @submit.prevent="saveNewPassword()" class="space-y-3.5">
                    <!-- Verifikasi Kata Sandi Saat Ini -->
                    <div x-show="passForm.verify_mode === 'current_password'">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                :type="passForm.showCurrent ? 'text' : 'password'"
                                x-model="passForm.current_password"
                                :required="passForm.verify_mode === 'current_password'"
                                placeholder="Masukkan kata sandi saat ini"
                                class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-3.5 pr-10 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                            >
                            <button 
                                type="button"
                                @click="passForm.showCurrent = !passForm.showCurrent"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center transition-colors"
                            >
                                <i class="ph-bold text-base" :class="passForm.showCurrent ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Verifikasi via NISN jika lupa kata sandi lama -->
                    <div x-show="passForm.verify_mode === 'nisn'" style="display: none;">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Verifikasi NISN Anda (10 Digit) <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            x-model="passForm.verify_nisn"
                            @input="passForm.verify_nisn = (passForm.verify_nisn || '').replace(/\D/g, '').slice(0, 10)"
                            :required="passForm.verify_mode === 'nisn'"
                            placeholder="Masukkan 10 digit NISN akun Anda"
                            class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors font-mono"
                        >
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Jika Anda lupa kata sandi saat ini, masukkan NISN yang terdaftar di profil Anda untuk meresetnya.
                        </p>
                    </div>

                    <!-- Kata Sandi Baru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                :type="passForm.showNew ? 'text' : 'password'"
                                x-model="passForm.password"
                                required
                                minlength="8"
                                placeholder="Minimal 8 karakter & minimal 1 angka"
                                class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-3.5 pr-10 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                            >
                            <button 
                                type="button"
                                @click="passForm.showNew = !passForm.showNew"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center transition-colors"
                            >
                                <i class="ph-bold text-base" :class="passForm.showNew ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>

                        <!-- Indikator syarat kata sandi -->
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
                            <span class="inline-flex items-center gap-1 font-semibold transition-colors" :class="(passForm.password || '').length >= 8 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'">
                                <i class="ph-fill" :class="(passForm.password || '').length >= 8 ? 'ph-check-circle' : 'ph-circle'"></i>
                                Minimal 8 karakter
                            </span>
                            <span class="inline-flex items-center gap-1 font-semibold transition-colors" :class="/[a-zA-Z]/.test(passForm.password || '') && /\d/.test(passForm.password || '') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'">
                                <i class="ph-fill" :class="/[a-zA-Z]/.test(passForm.password || '') && /\d/.test(passForm.password || '') ? 'ph-check-circle' : 'ph-circle'"></i>
                                Mengandung huruf &amp; minimal 1 angka
                            </span>
                        </div>
                    </div>

                    <!-- Konfirmasi Kata Sandi Baru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                :type="passForm.showConfirm ? 'text' : 'password'"
                                x-model="passForm.password_confirmation"
                                required
                                minlength="8"
                                placeholder="Ulangi kata sandi baru"
                                class="w-full text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-3.5 pr-10 py-2.5 text-slate-900 dark:text-white outline-none focus:border-blue-500 transition-colors"
                            >
                            <button 
                                type="button"
                                @click="passForm.showConfirm = !passForm.showConfirm"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center transition-colors"
                            >
                                <i class="ph-bold text-base" :class="passForm.showConfirm ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-1">
                        <button 
                            type="submit"
                            :disabled="passForm.saving"
                            class="w-full min-h-[46px] rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs sm:text-sm py-3 shadow-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <i class="ph-bold text-base" :class="passForm.saving ? 'ph-spinner animate-spin' : 'ph-check-circle'" aria-hidden="true"></i>
                            <span x-text="passForm.saving ? 'Menyimpan Kata Sandi...' : 'Simpan Kata Sandi Baru'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endauth

</div>
@endsection
