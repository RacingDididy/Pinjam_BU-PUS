@extends('layouts.app')

@section('title', 'Form Peminjaman Buku - PINJAM BU-PUS')

@section('content')
<div 
    x-data="{
        selectedIds: @js(collect($cartPayload['items'] ?? [])->pluck('id')->all()),
        form: {
            name: @js($user->name ?? ''),
            no_telepon: @js($user->no_telepon ?? ''),
            no_whatsapp: @js($user->no_whatsapp ?? ''),
            tanggal_lahir: @js($user->tanggal_lahir?->format('Y-m-d') ?? ''),
            nisn: @js($user->nisn ?? ''),
            nis: @js($user->nis ?? ''),
            nama_sekolah: @js($user->nama_sekolah ?: 'SMK Negeri 5 Surakarta'),
            tahun_ajaran: @js($user->tahun_ajaran ?: '2025/2026'),
            nama_kelas: @js($user->nama_kelas ?? ''),
            alamat: @js($user->alamat ?? ''),
            tanggal_pinjam: @js(now()->format('Y-m-d')),
            catatan_peminjam: ''
        },
        submitting: false,
        submitProgress: 0,
        errorList: [],
        get items() {
            return this.cartPayload?.items || [];
        },
        get allSelected() {
            return this.items.length > 0 && this.selectedIds.length === this.items.length;
        },
        toggleSelectAll() {
            if (this.allSelected) {
                this.selectedIds = [];
            } else {
                this.selectedIds = this.items.map(i => i.id);
            }
        },
        toggleItem(id) {
            if (this.selectedIds.includes(id)) {
                this.selectedIds = this.selectedIds.filter(x => x !== id);
            } else {
                this.selectedIds.push(id);
            }
        },
        get selectedItems() {
            return this.items.filter(i => this.selectedIds.includes(i.id));
        },
        get selectedTotalQty() {
            return this.selectedItems.reduce((sum, item) => sum + Number(item.qty || 1), 0);
        },
        get nisnValid() {
            return /^\d{10}$/.test((this.form.nisn || '').trim());
        },
        get nisValid() {
            const val = (this.form.nis || '').trim();
            return val.length >= 1 && val.length <= 5;
        },
        get identityProgress() {
            const checks = [
                (this.form.name || '').trim().length > 1,
                (this.form.no_telepon || '').trim().length >= 8,
                (this.form.no_whatsapp || '').trim().length >= 8,
                (this.form.tanggal_lahir || '').trim().length > 0,
                this.nisnValid,
                this.nisValid,
                (this.form.nama_sekolah || '').trim().length > 2,
                (this.form.tahun_ajaran || '').trim().length > 3,
                (this.form.nama_kelas || '').trim().length > 1,
                (this.form.alamat || '').trim().length >= 5,
            ];
            const passed = checks.filter(Boolean).length;
            return Math.round((passed / checks.length) * 100);
        },
        async submitCheckout() {
            if (this.submitting) return;
            this.errorList = [];

            if (this.selectedItems.length === 0) {
                this.errorList = ['Pilih minimal 1 buku dari daftar keranjang untuk dipinjam.'];
                this.showToast(this.errorList[0], 'error');
                return;
            }

            if (this.identityProgress < 100) {
                this.errorList = ['Data identitas warga sekolah belum lengkap atau format belum sesuai (NISN 10 digit & NIS maks 5 karakter). Silakan ubah data di Halaman Profil terlebih dahulu.'];
                this.showToast(this.errorList[0], 'error');
                return;
            }

            this.submitting = true;
            this.submitProgress = 25;
            const progInterval = setInterval(() => {
                if (this.submitProgress < 90) this.submitProgress += 15;
            }, 220);

            try {
                const res = await fetch('{{ route('buku.cart.checkout') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        tanggal_pinjam: this.form.tanggal_pinjam,
                        catatan_peminjam: this.form.catatan_peminjam,
                        selected_ids: this.selectedIds
                    })
                });
                const data = await res.json();
                clearInterval(progInterval);
                this.submitProgress = 100;

                if (res.ok && data.success) {
                    if (data.cart) {
                        this.cartPayload = data.cart;
                    }
                    this.showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '{{ route('profil.index') }}';
                    }, 450);
                } else {
                    if (data.errors) {
                        this.errorList = Object.values(data.errors).flat();
                    } else {
                        this.errorList = [data.message || 'Gagal mengajukan peminjaman. Periksa kembali isian Anda.'];
                    }
                    this.showToast(this.errorList[0], 'error');
                    this.submitting = false;
                    this.submitProgress = 0;
                }
            } catch (e) {
                clearInterval(progInterval);
                this.errorList = ['Koneksi internet tidak stabil. Silakan coba tekan Konfirmasi Peminjaman kembali.'];
                this.showToast(this.errorList[0], 'error');
                this.submitting = false;
                this.submitProgress = 0;
            }
        }
    }"
    class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-7 space-y-5 sm:space-y-6"
>

    <!-- Breadcrumb -->
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
            class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-600 font-semibold shrink-0 transition-colors"
        >
            {{ __('Perpustakaan') }}
        </a>
        <i class="ph-bold ph-caret-right text-[10px] text-slate-400 shrink-0" aria-hidden="true"></i>
        <span class="px-3.5 py-1.5 rounded-full bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 font-bold shrink-0">
            Form Peminjaman Buku
        </span>
    </nav>

    <!-- Top Page Banner -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl border border-slate-200/90 dark:border-slate-800 px-4 sm:px-6 py-4 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-xl shadow-sm shrink-0">
            <i class="ph-bold ph-shopping-bag-open" aria-hidden="true"></i>
        </div>
        <div>
            <h1 class="text-lg sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Form Peminjaman Buku
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Periksa daftar buku yang dipilih dan pastikan identitas warga sekolah Anda telah sesuai sebelum konfirmasi.
            </p>
        </div>
    </div>

    <!-- Validation Error Alert -->
    <div x-cloak x-show="errorList.length > 0" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs space-y-1">
        <div class="font-extrabold flex items-center gap-1.5">
            <i class="ph-fill ph-warning-circle text-base text-rose-500"></i>
            <span>Mohon periksa kembali data berikut:</span>
        </div>
        <template x-for="(err, i) in errorList" :key="i">
            <p class="pl-5 font-medium" x-text="'• ' + err"></p>
        </template>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-start">

        <!-- Daftar buku dan identitas peminjam -->
        <div class="lg:col-span-8 space-y-5 sm:space-y-6">

            <!-- Daftar buku di keranjang -->
            <section class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
                <!-- Pilih semua buku -->
                <div class="px-4 sm:px-6 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-2 flex-wrap">
                    <label class="inline-flex items-center gap-2.5 text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white select-none">
                        <input 
                            type="checkbox"
                            :checked="allSelected"
                            @change="toggleSelectAll()"
                            :disabled="items.length === 0"
                            class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span>Pilih Semua (<span x-text="items.length"></span> Judul / <span x-text="cartPayload.total_qty"></span> Buku)</span>
                    </label>

                    <a 
                        href="{{ route('perpustakaan.index') }}"
                        class="px-3 py-1.5 rounded-xl border border-blue-200 dark:border-blue-800/80 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 text-[11px] sm:text-xs font-extrabold inline-flex items-center gap-1.5 transition-colors"
                    >
                        <i class="ph-bold ph-plus text-xs" aria-hidden="true"></i>
                        <span>Tambah Buku Lain</span>
                    </a>
                </div>

                <!-- Book Items List -->
                <div class="p-4 sm:p-6 space-y-3">
                    <template x-for="item in items" :key="item.id">
                        <div 
                            class="p-3 sm:p-4 rounded-2xl border transition-all flex items-start sm:items-center gap-3 sm:gap-4"
                            :class="selectedIds.includes(item.id) 
                                ? 'bg-blue-50/30 dark:bg-blue-950/20 border-blue-300 dark:border-blue-800/80' 
                                : 'bg-slate-50/60 dark:bg-slate-800/40 border-slate-200/80 dark:border-slate-800 opacity-75'"
                        >
                            <input 
                                type="checkbox"
                                :checked="selectedIds.includes(item.id)"
                                @change="toggleItem(item.id)"
                                aria-label="Pilih buku"
                                class="mt-2 sm:mt-0 w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 shrink-0"
                            >

                            <a :href="item.url" class="shrink-0">
                                <img :src="item.cover_url" :alt="item.judul" width="60" height="84" class="w-14 h-20 sm:w-16 sm:h-22 object-cover rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800">
                            </a>

                            <div class="flex-1 min-w-0 space-y-1">
                                <a :href="item.url" class="text-xs sm:text-base font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 line-clamp-1" x-text="item.judul"></a>
                                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">
                                    <span class="font-semibold text-blue-600 dark:text-blue-400" x-text="item.kategori || 'Umum'"></span>
                                    <span>&bull;</span>
                                    <span x-text="item.pengarang"></span>
                                </p>

                                <div class="pt-1.5 flex flex-wrap items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200/70 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-[10px] sm:text-[11px] font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Stok Tersedia (<span x-text="item.stok"></span>)</span>
                                    </span>

                                    <!-- +/- Quantity Stepper -->
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
                                        <span class="min-w-[32px] text-center text-xs font-extrabold text-slate-900 dark:text-white select-none" x-text="item.qty"></span>
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
                                </div>
                            </div>

                            <button 
                                type="button"
                                @click="removeCartItem(item.id)"
                                :disabled="cartBusy"
                                aria-label="Hapus buku"
                                class="w-9 h-9 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center justify-center transition-colors shrink-0"
                            >
                                <i class="ph-bold ph-trash text-base" aria-hidden="true"></i>
                            </button>
                        </div>
                    </template>

                    <!-- Empty Cart State -->
                    <div x-show="items.length === 0" class="py-12 text-center space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                            <i class="ph ph-shopping-bag" aria-hidden="true"></i>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">Belum ada buku di Form Peminjaman</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                                Pilih buku dari katalog perpustakaan terlebih dahulu untuk mulai mengajukan peminjaman.
                            </p>
                        </div>
                        <a 
                            href="{{ route('perpustakaan.index') }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-colors"
                        >
                            <i class="ph-bold ph-books text-sm" aria-hidden="true"></i>
                            <span>Pilih Buku di Perpustakaan</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Identitas peminjam -->
            <section class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-4 sm:p-6 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg shrink-0">
                            <i class="ph-bold ph-identification-card" aria-hidden="true"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm sm:text-lg font-extrabold text-slate-900 dark:text-white">
                                    Detail Peminjam &amp; Identitas Warga Sekolah
                                </h2>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-bold inline-flex items-center gap-1 border border-slate-200 dark:border-slate-700">
                                    <i class="ph-fill ph-lock-key text-xs" aria-hidden="true"></i>
                                    Terkunci
                                </span>
                            </div>
                            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                                Data identitas peminjam tidak dapat diubah di halaman ini. Untuk mengubah data, silakan melalui Halaman Profil.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="w-20 h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div 
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="identityProgress === 100 ? 'bg-emerald-500' : 'bg-amber-500'"
                                    :style="`width: ${identityProgress}%`"
                                ></div>
                            </div>
                            <span 
                                class="text-xs font-extrabold"
                                :class="identityProgress === 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                x-text="identityProgress + '%'"
                            ></span>
                        </div>

                        <a 
                            href="{{ route('profil.index', ['lengkap_identitas' => 1]) }}"
                            class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold inline-flex items-center gap-1.5 shadow-sm transition-colors"
                        >
                            <i class="ph-bold ph-pencil-simple text-xs" aria-hidden="true"></i>
                            <span>Ubah di Profil</span>
                        </a>
                    </div>
                </div>

                <!-- Incomplete Identity Warning Box -->
                <div 
                    x-cloak
                    x-show="identityProgress < 100"
                    class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                >
                    <div class="flex items-start gap-2.5 text-amber-800 dark:text-amber-200">
                        <i class="ph-fill ph-warning-circle text-base text-amber-500 shrink-0 mt-0.5" aria-hidden="true"></i>
                        <span>Data identitas warga sekolah Anda belum lengkap atau NIS lebih dari 5 karakter. Silakan lengkapi di Halaman Profil terlebih dahulu.</span>
                    </div>
                    <a 
                        href="{{ route('profil.index', ['lengkap_identitas' => 1]) }}"
                        class="self-start sm:self-center shrink-0 px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition-colors"
                    >
                        Lengkapi di Profil
                    </a>
                </div>

                <!-- Sub-group 1: Data Pribadi & Nomor Induk (Readonly) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                    <div>
                        <label for="co-name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nama Lengkap Siswa / Warga Sekolah
                        </label>
                        <input 
                            id="co-name"
                            type="text"
                            :value="form.name || '-'"
                            readonly
                            tabindex="-1"
                            class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                        >
                    </div>

                    <div>
                        <label for="co-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Email Terverifikasi
                        </label>
                        <div class="relative">
                            <input 
                                id="co-email"
                                type="email"
                                value="{{ $user->email }}"
                                readonly
                                tabindex="-1"
                                class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 pr-24 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                            >
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold inline-flex items-center gap-1">
                                <i class="ph-fill ph-check-circle"></i> Terverifikasi
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="co-nisn" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                NISN (10 Digit Angka)
                            </label>
                            <span 
                                class="text-[11px] font-extrabold"
                                :class="nisnValid ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                x-text="(form.nisn || '').length + '/10 digit'"
                            ></span>
                        </div>
                        <div class="relative">
                            <input 
                                id="co-nisn"
                                type="text"
                                :value="form.nisn || '-'"
                                readonly
                                tabindex="-1"
                                class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-mono font-bold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                            >
                            <i 
                                x-show="nisnValid" 
                                class="ph-fill ph-check-circle text-emerald-500 text-base absolute right-3.5 top-1/2 -translate-y-1/2"
                            ></i>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="co-nis" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                NIS Sekolah (Maks. 5 Karakter)
                            </label>
                            <span 
                                class="text-[11px] font-extrabold"
                                :class="nisValid ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                x-text="(form.nis || '').length + '/5 maks'"
                            ></span>
                        </div>
                        <input 
                            id="co-nis"
                            type="text"
                            maxlength="5"
                            :value="form.nis || '-'"
                            readonly
                            tabindex="-1"
                            class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-mono font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                        >
                    </div>
                </div>

                <!-- Sub-group 2: Detail Sekolah (Readonly) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 pt-1">
                    <div class="sm:col-span-2">
                        <label for="co-sekolah" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nama Sekolah
                        </label>
                        <input 
                            id="co-sekolah"
                            type="text"
                            :value="form.nama_sekolah || '-'"
                            readonly
                            tabindex="-1"
                            class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                        >
                    </div>

                    <div>
                        <label for="co-kelas" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nama Kelas / Jurusan
                        </label>
                        <input 
                            id="co-kelas"
                            type="text"
                            :value="form.nama_kelas || '-'"
                            readonly
                            tabindex="-1"
                            class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                        >
                    </div>

                    <div>
                        <label for="co-tahun-ajaran" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Tahun Ajaran
                        </label>
                        <input 
                            id="co-tahun-ajaran"
                            type="text"
                            :value="form.tahun_ajaran || '-'"
                            readonly
                            tabindex="-1"
                            class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                        >
                    </div>
                </div>

                <!-- Sub-group 3: Kontak (Readonly) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4 pt-1">
                    <div>
                        <label for="co-telp" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nomor Telepon
                        </label>
                        <input 
                            id="co-telp"
                            type="text"
                            :value="form.no_telepon || '-'"
                            readonly
                            tabindex="-1"
                            class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                        >
                    </div>

                    <div>
                        <label for="co-wa" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nomor WhatsApp Aktif
                        </label>
                        <input 
                            id="co-wa"
                            type="text"
                            :value="form.no_whatsapp || '-'"
                            readonly
                            tabindex="-1"
                            class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                        >
                    </div>

                    <div>
                        <label for="co-dob" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Tanggal Lahir
                        </label>
                        <input 
                            id="co-dob"
                            type="date"
                            :value="form.tanggal_lahir"
                            readonly
                            tabindex="-1"
                            class="w-full min-h-[42px] rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none cursor-not-allowed select-none"
                        >
                    </div>
                </div>

                <!-- Sub-group 4: Lokasi / Alamat Lengkap (Readonly) -->
                <div>
                    <label for="co-alamat" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Lokasi / Alamat Tempat Tinggal Lengkap
                    </label>
                    <textarea 
                        id="co-alamat"
                        rows="2"
                        :value="form.alamat || '-'"
                        readonly
                        tabindex="-1"
                        class="w-full rounded-xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 p-3 text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-300 outline-none resize-none cursor-not-allowed select-none"
                    ></textarea>
                </div>

                <!-- Sub-group 5: Tanggal Pinjam & Catatan Tambahan (No Tingkat Kepentingan Dropdown) -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3.5">
                    <div>
                        <label for="co-tgl-pinjam" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Tanggal Pinjam
                        </label>
                        <input 
                            id="co-tgl-pinjam"
                            type="date"
                            x-model="form.tanggal_pinjam"
                            class="w-full sm:max-w-xs min-h-[42px] rounded-xl bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 focus:border-blue-500 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white outline-none"
                        >
                    </div>

                    <div>
                        <label for="co-catatan" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Catatan Tambahan untuk Pustakawan (Opsional)
                        </label>
                        <textarea 
                            id="co-catatan"
                            rows="2"
                            x-model="form.catatan_peminjam"
                            placeholder="Contoh: Untuk keperluan tugas mata pelajaran Produktif RPL..."
                            class="w-full rounded-xl bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 p-3 text-xs sm:text-sm font-medium text-slate-900 dark:text-white outline-none resize-none transition-colors"
                        ></textarea>
                    </div>
                </div>
            </section>

        </div>

        <!-- Ringkasan peminjaman -->
        <aside class="lg:col-span-4 lg:sticky lg:top-24">
            <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-5 sm:p-6 space-y-5">
                <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800">
                    Ringkasan Peminjaman
                </h2>

                <!-- Selected Books Mini Summary -->
                <div class="space-y-2 max-h-48 overflow-y-auto pr-1" x-show="selectedItems.length > 0">
                    <template x-for="sItem in selectedItems" :key="sItem.id">
                        <div class="flex items-center justify-between gap-2 text-xs py-1">
                            <span class="text-slate-700 dark:text-slate-300 font-medium truncate" x-text="sItem.judul"></span>
                            <span class="font-extrabold text-slate-900 dark:text-white shrink-0" x-text="sItem.qty + ' Buku'"></span>
                        </div>
                    </template>
                </div>

                <div class="space-y-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs sm:text-sm">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Total Judul Dipilih</span>
                        <span class="font-bold text-slate-900 dark:text-white"><span x-text="selectedItems.length"></span> Judul</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Total Buku Dipilih</span>
                        <span class="font-extrabold text-blue-600 dark:text-blue-400 text-sm sm:text-base"><span x-text="selectedTotalQty"></span> Buku</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Durasi Peminjaman Maksimal</span>
                        <span class="font-bold text-slate-900 dark:text-white">7 Hari</span>
                    </div>

                    <!-- Peraturan Denda Peminjaman Buku Sekolah (Below Durasi Peminjaman Maksimal) -->
                    <div class="p-3 rounded-xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/70 space-y-1 text-[11px] leading-relaxed text-amber-900 dark:text-amber-200">
                        <div class="font-extrabold flex items-center gap-1.5 text-amber-800 dark:text-amber-300">
                            <i class="ph-fill ph-warning-octagon text-sm shrink-0" aria-hidden="true"></i>
                            <span>Peraturan Denda Keterlambatan Siswa</span>
                        </div>
                        <p class="text-amber-800/90 dark:text-amber-200/90">
                            Keterlambatan pengembalian buku melewati batas waktu <strong>7 hari</strong> dikenakan denda <strong>Rp 1.000 / hari / buku</strong> sesuai tata tertib perpustakaan sekolah, serta penangguhan hak pinjam sementara hingga buku dikembalikan.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400 pt-1">
                        <span>Status Identitas Siswa</span>
                        <span 
                            class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                            :class="identityProgress === 100 
                                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' 
                                : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300'"
                            x-text="identityProgress === 100 ? 'Lengkap (100%)' : ('Belum Lengkap (' + identityProgress + '%)')"
                        ></span>
                    </div>
                </div>

                <!-- Animated Loading Progress Bar during Submission -->
                <div x-cloak x-show="submitting" class="space-y-1.5">
                    <div class="flex items-center justify-between text-[11px] font-bold text-blue-600 dark:text-blue-400">
                        <span>Mengirim pengajuan ke Pustakawan...</span>
                        <span x-text="submitProgress + '%'"></span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-blue-100 dark:bg-blue-950 overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full transition-all duration-200" :style="`width: ${submitProgress}%`"></div>
                    </div>
                </div>

                <!-- Confirm Button -->
                <button 
                    type="button"
                    @click="submitCheckout()"
                    :disabled="submitting || selectedItems.length === 0"
                    class="w-full min-h-[48px] rounded-2xl bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-extrabold text-xs sm:text-sm py-3.5 px-5 shadow-md shadow-blue-600/20 flex items-center justify-center gap-2 transition-all disabled:opacity-50"
                >
                    <i class="ph-bold text-lg" :class="submitting ? 'ph-spinner animate-spin' : 'ph-check-square-offset'" aria-hidden="true"></i>
                    <span x-text="submitting ? 'Memproses Peminjaman...' : 'Konfirmasi Peminjaman'"></span>
                </button>

                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-800 text-center text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                    Dengan mengonfirmasi, Anda menyetujui tata tertib perpustakaan <strong>PINJAM BU-PUS SMK Negeri 5 Surakarta</strong>.
                </div>
            </div>
        </aside>

    </div>

</div>
@endsection
