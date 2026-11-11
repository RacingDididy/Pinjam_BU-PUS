@extends('layouts.app')

@section('title', $buku->judul . ' - PINJAM BU-PUS')

@section('content')
<div 
    x-data="{
        previews: {{ Js::from($buku->preview_images) }},
        activePreviewIndex: 0,
        get currentPreview() {
            return this.previews[this.activePreviewIndex] || this.previews[0];
        },
        selectPreview(idx) {
            this.activePreviewIndex = idx;
        },
        synopsisExpanded: false,
        selectedRating: 5,
        ratingHover: 5,
        commentText: '',
        commentSubmitting: false,
        newComments: [],
        totalCommentsCount: {{ $buku->ratings->count() }},
        isFavorite: {{ $isFavorite ? 'true' : 'false' }},
        favoriteLoading: false,
        hasLoan: {{ $hasActiveLoan ? 'true' : 'false' }},
        liveStok: {{ (int) $buku->stok }},
        borrowBusy: false,
        async submitComment() {
            if (this.commentSubmitting || this.commentText.trim().length === 0) return;
            this.commentSubmitting = true;
            try {
                const res = await fetch('{{ route('buku.rating', $buku->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        bintang: this.selectedRating,
                        ulasan: this.commentText
                    })
                });
                const data = await res.json();
                if (res.status === 401 || data.auth_required) {
                    this.openAuthModal('{{ __('Komentar') }}');
                    return;
                }
                if (res.ok && data.success && data.rating) {
                    this.newComments.unshift(data.rating);
                    this.totalCommentsCount++;
                    this.commentText = '';
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                    $dispatch('notify-toast', { message: firstErr || data.message || 'Gagal mengirim komentar.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Terjadi gangguan jaringan saat mengirim komentar.', type: 'error' });
            } finally {
                this.commentSubmitting = false;
            }
        },
        async submitDirectBorrow() {
            if (this.borrowBusy || this.liveStok <= 0) return;
            this.borrowBusy = true;
            try {
                const res = await fetch('{{ route('buku.borrow.direct', $buku->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ qty: Math.max(1, this.cartQty({{ $buku->id }}) || 1) })
                });
                const data = await res.json();
                if (res.status === 401) {
                    this.openAuthModal('{{ __('Langsung Pinjam') }}');
                    return;
                }
                if (data.cart) {
                    this.cartPayload = data.cart;
                }
                if (data.redirect_url && (data.identity_required || data.verification_required)) {
                    $dispatch('notify-toast', { message: data.message, type: 'error' });
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 600);
                    return;
                }
                if (typeof data.new_stok === 'number') {
                    this.liveStok = data.new_stok;
                }
                if (data.success) {
                    this.hasLoan = true;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal memproses peminjaman.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal memproses peminjaman.', type: 'error' });
            } finally {
                this.borrowBusy = false;
            }
        },
        async toggleFav() {
            if (this.favoriteLoading) return;
            this.favoriteLoading = true;
            try {
                const res = await fetch('{{ route('buku.favorite.toggle', $buku->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.status === 401 || data.auth_required) {
                    $dispatch('open-auth-modal', { action: '{{ __('Wishlist') }}' });
                } else if (data.success) {
                    this.isFavorite = data.is_favorite;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.favoriteLoading = false;
            }
        },
        copyShareLink() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href);
                $dispatch('notify-toast', { message: 'Tautan buku berhasil disalin!', type: 'success' });
            }
        }
    }"
    class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-7 space-y-6 sm:space-y-8"
>

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs overflow-x-auto no-scrollbar py-0.5">
        <a 
            href="{{ route('home') }}"
            aria-label="{{ __('Beranda') }}"
            class="w-8 h-8 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-600 flex items-center justify-center shrink-0 transition-colors"
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
        @if($buku->kategori)
            <i class="ph-bold ph-caret-right text-[10px] text-slate-400 shrink-0" aria-hidden="true"></i>
            <a 
                href="{{ route('perpustakaan.index', ['kategori' => $buku->kategori_id]) }}"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-600 font-semibold shrink-0 transition-colors"
            >
                {{ $buku->kategori->nama_kategori }}
            </a>
        @endif
        <i class="ph-bold ph-caret-right text-[10px] text-slate-400 shrink-0" aria-hidden="true"></i>
        <span class="px-3 py-1.5 rounded-full bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 font-bold truncate max-w-[190px] sm:max-w-xs">
            {{ $buku->judul }}
        </span>
    </nav>

    <!-- Detail buku -->
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-4 sm:p-8 lg:p-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-start">

            <!-- Cover dan pratinjau halaman -->
            <div class="lg:col-span-5 flex flex-col items-center">
                <div class="relative w-48 sm:w-60 lg:w-full max-w-[230px] sm:max-w-xs lg:max-w-sm aspect-[3/4.2] rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-md">
                    <img 
                        :src="currentPreview.url"
                        :alt="currentPreview.title"
                        width="380"
                        height="532"
                        fetchpriority="high"
                        onerror="this.onerror=null;this.src='https://placehold.co/400x600/1e293b/ffffff?text={{ urlencode($buku->judul) }}';"
                        class="w-full h-full object-cover transition-all duration-300"
                    >
                    <div x-show="previews.length > 1" class="absolute bottom-2.5 inset-x-2.5 flex items-center justify-between pointer-events-none">
                        <span class="bg-slate-900/80 backdrop-blur-sm text-white text-[10px] sm:text-[11px] font-medium px-2.5 py-1 rounded-full flex items-center gap-1.5">
                            <i class="ph-bold ph-image text-blue-400 text-xs" aria-hidden="true"></i>
                            <span x-text="currentPreview.title"></span>
                        </span>
                        <span class="bg-black/65 text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
                            <span x-text="activePreviewIndex + 1"></span>/<span x-text="previews.length"></span>
                        </span>
                    </div>
                </div>

                <!-- Pilihan tampilan depan, samping, belakang (tampil jika buku memiliki > 1 gambar) -->
                <template x-if="previews.length > 1">
                    <div class="w-48 sm:w-60 lg:w-full max-w-[230px] sm:max-w-xs lg:max-w-sm">
                        <div 
                            class="mt-3.5 sm:mt-4 grid gap-2"
                            :class="previews.length === 2 ? 'grid-cols-2' : 'grid-cols-3'"
                        >
                            <template x-for="(prev, idx) in previews" :key="idx">
                                <button 
                                    type="button"
                                    @click="selectPreview(idx)"
                                    class="min-h-[46px] py-2 px-1.5 rounded-xl text-center font-extrabold text-xs sm:text-sm border transition-all flex flex-col items-center justify-center gap-0.5"
                                    :class="activePreviewIndex === idx 
                                        ? 'bg-blue-50 dark:bg-blue-950/80 border-blue-600 text-blue-700 dark:text-blue-300 ring-2 ring-blue-500/20' 
                                        : 'bg-white dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-blue-400'"
                                    :title="prev.title"
                                >
                                    <span x-text="prev.label"></span>
                                    <span class="text-[9px] sm:text-[10px] font-medium text-slate-500 dark:text-slate-400 truncate w-full px-0.5" x-text="prev.title"></span>
                                </button>
                            </template>
                        </div>

                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2 text-center">
                            {{ __('Klik tombol di atas untuk melihat sudut gambar buku lainnya.') }}
                        </p>
                    </div>
                </template>
            </div>

            <!-- Informasi buku dan tombol pinjam -->
            <div class="lg:col-span-7 flex flex-col justify-between space-y-5">

                <!-- Title, Author, Rating & Stock -->
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <h1 class="text-xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-snug">
                            {{ $buku->judul }}
                        </h1>

                        <button 
                            type="button"
                            @click="copyShareLink()"
                            aria-label="{{ __('Bagikan') }}"
                            title="{{ __('Bagikan') }}"
                            class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 flex items-center justify-center shrink-0 transition-colors"
                        >
                            <i class="ph-bold ph-share-network text-lg" aria-hidden="true"></i>
                        </button>
                    </div>

                    <!-- Meta Sub-row: Author, Category, Views -->
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs sm:text-sm text-slate-600 dark:text-slate-400">
                        <a href="{{ route('perpustakaan.index', ['search' => $buku->pengarang]) }}" class="inline-flex items-center gap-1.5 font-semibold hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <i class="ph-bold ph-user text-slate-400" aria-hidden="true"></i>
                            <span>{{ $buku->pengarang }}</span>
                        </a>
                        @if($buku->kategori)
                            <a href="{{ route('perpustakaan.index', ['kategori' => $buku->kategori_id]) }}" class="inline-flex items-center gap-1.5 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                <i class="ph-bold ph-tag text-slate-400" aria-hidden="true"></i>
                                <span>{{ $buku->kategori->nama_kategori }}</span>
                            </a>
                        @endif
                        <span class="inline-flex items-center gap-1.5">
                            <i class="ph-bold ph-eye text-slate-400" aria-hidden="true"></i>
                            <span>{{ number_format($buku->views_count) }} {{ __('dilihat') }}</span>
                        </span>
                    </div>

                    <!-- Stars & Stock Pill Row -->
                    <div class="flex flex-wrap items-center gap-3 pt-0.5 text-xs sm:text-sm">
                        <a href="#komentar-section" class="inline-flex items-center gap-1.5">
                            <span class="flex items-center gap-0.5 text-amber-400 text-base">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="{{ $i <= round($buku->average_rating) ? 'ph-fill ph-star' : 'ph ph-star text-slate-300 dark:text-slate-600' }}" aria-hidden="true"></i>
                                @endfor
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">
                                {{ $buku->total_ratings_count > 0 ? number_format($buku->average_rating, 1) : '0.0' }}
                            </span>
                            <span class="text-slate-500 dark:text-slate-400">({{ $buku->total_ratings_count }})</span>
                        </a>

                        <span class="text-slate-300 dark:text-slate-700">|</span>

                        <span 
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-bold text-xs border"
                            :class="liveStok > 0 
                                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/70' 
                                : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/70'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="liveStok > 0 ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                            <template x-if="liveStok > 0">
                                <span>{{ __('Tersedia:') }} <strong x-text="liveStok + ' {{ __('Buku') }}'">{{ $buku->stok }} {{ __('Buku') }}</strong></span>
                            </template>
                            <template x-if="liveStok <= 0">
                                <span>{{ __('Stok Habis') }}</span>
                            </template>
                        </span>
                    </div>
                </div>

                <!-- Tombol aksi peminjaman -->
                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                    <!-- Langsung pinjam -->
                    @guest
                        <button 
                            type="button"
                            @click="openAuthModal('{{ __('Langsung Pinjam') }}')"
                            class="min-h-[44px] px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-2 shadow-sm transition-colors flex-1 sm:flex-none"
                        >
                            <i class="ph-bold ph-book-open text-base" aria-hidden="true"></i>
                            <span>{{ __('Langsung Pinjam') }}</span>
                        </button>
                    @else
                        <a 
                            x-cloak
                            x-show="hasLoan"
                            href="{{ route('profil.index') }}"
                            class="min-h-[44px] px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-2 shadow-sm transition-colors flex-1 sm:flex-none"
                        >
                            <i class="ph-bold ph-check-circle text-base" aria-hidden="true"></i>
                            <span>{{ __('Pinjaman Saya') }}</span>
                        </a>

                        <button 
                            x-cloak
                            x-show="!hasLoan && liveStok <= 0"
                            type="button"
                            disabled
                            class="min-h-[44px] px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-2 flex-1 sm:flex-none"
                        >
                            <span>{{ __('Stok Habis') }}</span>
                        </button>

                        <button 
                            x-show="!hasLoan && liveStok > 0"
                            type="button"
                            @click="submitDirectBorrow()"
                            :disabled="borrowBusy"
                            class="min-h-[44px] px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-2 shadow-sm transition-all flex-1 sm:flex-none disabled:opacity-60"
                        >
                            <i class="ph-bold text-base" :class="borrowBusy ? 'ph-spinner animate-spin' : 'ph-lightning'" aria-hidden="true"></i>
                            <span x-text="borrowBusy ? '{{ __('Memproses...') }}' : '{{ __('Langsung Pinjam') }}'"></span>
                        </button>
                    @endguest

                    <!-- Tambah keranjang -->
                    @guest
                        <button 
                            type="button"
                            @click="openAuthModal('{{ __('Tambah Keranjang') }}')"
                            class="min-h-[44px] px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 text-slate-800 dark:text-slate-100 font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-2 transition-colors flex-1 sm:flex-none"
                        >
                            <i class="ph-bold ph-shopping-cart text-base" aria-hidden="true"></i>
                            <span>{{ __('Tambah Keranjang') }}</span>
                        </button>
                    @else
                        <!-- When not yet in cart -->
                        <button 
                            x-show="cartQty({{ $buku->id }}) === 0"
                            type="button"
                            @click="addToCart({{ $buku->id }})"
                            :disabled="cartBusy || liveStok <= 0"
                            class="min-h-[44px] px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-blue-400 text-slate-800 dark:text-slate-100 font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-2 transition-colors flex-1 sm:flex-none disabled:opacity-50"
                        >
                            <i class="ph-bold ph-shopping-cart text-base" aria-hidden="true"></i>
                            <span>{{ __('Tambah Keranjang') }}</span>
                        </button>

                        <!-- When in cart: show [- qty +] typable stepper -->
                        <div 
                            x-cloak
                            x-show="cartQty({{ $buku->id }}) > 0"
                            class="min-h-[44px] px-2 py-1.5 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-xs sm:text-sm inline-flex items-center justify-between gap-1.5 flex-1 sm:flex-none"
                        >
                            <button 
                                type="button"
                                @click="updateCartQty({{ $buku->id }}, 'decrement')"
                                :disabled="cartBusy"
                                aria-label="Kurangi jumlah"
                                class="w-8 h-8 rounded-lg bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors disabled:opacity-40"
                            >
                                <i class="ph-bold ph-minus text-xs" aria-hidden="true"></i>
                            </button>
                            <div class="inline-flex items-center gap-1 px-1">
                                <input
                                    type="number"
                                    min="1"
                                    :max="liveStok"
                                    :value="cartQty({{ $buku->id }})"
                                    @change="updateCartQty({{ $buku->id }}, 'set', $event.target.value)"
                                    @keydown.enter.prevent="$event.target.blur()"
                                    aria-label="Ketik jumlah buku"
                                    class="w-12 h-8 text-center text-xs sm:text-sm font-extrabold rounded-lg bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 outline-none focus:border-emerald-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                >
                                <button
                                    type="button"
                                    @click="cartOpen = true"
                                    class="text-xs font-extrabold hover:underline hidden sm:inline"
                                >
                                    {{ __('Buku') }}
                                </button>
                            </div>
                            <button 
                                type="button"
                                @click="updateCartQty({{ $buku->id }}, 'increment')"
                                :disabled="cartBusy || cartQty({{ $buku->id }}) >= liveStok"
                                aria-label="Tambah jumlah"
                                class="w-8 h-8 rounded-lg bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors disabled:opacity-40"
                            >
                                <i class="ph-bold ph-plus text-xs" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endguest

                    <!-- Wishlist -->
                    @guest
                        <button 
                            type="button"
                            @click="openAuthModal('{{ __('Wishlist') }}')"
                            class="min-h-[44px] px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-rose-300 text-slate-700 dark:text-slate-200 hover:text-rose-500 font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <i class="ph-bold ph-heart text-base" aria-hidden="true"></i>
                            <span>{{ __('Tambah Wishlist') }}</span>
                        </button>
                    @else
                        <button 
                            type="button"
                            @click="toggleFav()"
                            :disabled="favoriteLoading"
                            class="min-h-[44px] px-4 py-2.5 rounded-xl border font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-1.5 transition-colors"
                            :class="isFavorite 
                                ? 'bg-rose-50 dark:bg-rose-950/60 border-rose-300 dark:border-rose-800 text-rose-600 dark:text-rose-300' 
                                : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-rose-300 hover:text-rose-500'"
                        >
                            <i class="text-base" :class="isFavorite ? 'ph-fill ph-heart' : 'ph-bold ph-heart'" aria-hidden="true"></i>
                            <span x-text="isFavorite ? '{{ __('Tersimpan') }}' : '{{ __('Tambah Wishlist') }}'"></span>
                        </button>
                    @endguest
                </div>

                <hr class="border-slate-100 dark:border-slate-800">

                <!-- Sinopsis -->
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white mb-1.5">
                        {{ __('Sinopsis') }}
                    </h2>

                    @php
                        $sinopsisText = $buku->sinopsis ?: 'Belum ada ringkasan sinopsis untuk buku ini. Anda dapat langsung meminjam buku untuk membaca isi lengkapnya.';
                        $isLongSynopsis = mb_strlen($sinopsisText) > 240;
                    @endphp

                    <div class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        <div x-show="!synopsisExpanded">
                            <p class="{{ $isLongSynopsis ? 'line-clamp-3' : '' }}">{{ $sinopsisText }}</p>
                            @if($isLongSynopsis)
                                <button 
                                    type="button"
                                    @click="synopsisExpanded = true"
                                    class="text-blue-600 dark:text-blue-400 font-bold hover:underline mt-1 inline-flex items-center gap-1"
                                >
                                    <span>{{ __('Baca selengkapnya') }}</span>
                                    <i class="ph-bold ph-caret-down text-xs" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>

                        <div x-show="synopsisExpanded" style="display: none;">
                            <p class="whitespace-pre-line">{{ $sinopsisText }}</p>
                            <button 
                                type="button"
                                @click="synopsisExpanded = false"
                                class="text-blue-600 dark:text-blue-400 font-bold hover:underline mt-2 inline-flex items-center gap-1"
                            >
                                <span>{{ __('Tutup ringkasan') }}</span>
                                <i class="ph-bold ph-caret-up text-xs" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Spesifikasi buku -->
                <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden bg-slate-50/70 dark:bg-slate-800/40 text-xs sm:text-sm">
                    <div class="grid grid-cols-2 divide-x divide-slate-200 dark:divide-slate-800">
                        <div class="divide-y divide-slate-200 dark:divide-slate-800">
                            <div class="p-3 sm:p-3.5">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-0.5">{{ __('PENERBIT') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100 text-xs sm:text-sm truncate block">{{ $buku->penerbit ?: '-' }}</span>
                            </div>
                            <div class="p-3 sm:p-3.5">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-0.5">{{ __('TEBAL HALAMAN') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100 text-xs sm:text-sm block">{{ $buku->tebal_halaman ? $buku->tebal_halaman . ' ' . __('Halaman') : '-' }}</span>
                            </div>
                            <div class="p-3 sm:p-3.5">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-0.5">{{ __('GENRE') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100 text-xs sm:text-sm truncate block">{{ $buku->kategori->nama_kategori ?? 'Umum' }}</span>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-200 dark:divide-slate-800">
                            <div class="p-3 sm:p-3.5">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-0.5">{{ __('TANGGAL TERBIT') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100 text-xs sm:text-sm block">{{ $buku->tanggal_terbit_formatted }}</span>
                            </div>
                            <div class="p-3 sm:p-3.5">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-0.5">{{ __('ISBN') }}</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-100 text-[11px] sm:text-xs truncate block">{{ $buku->isbn ?: '-' }}</span>
                            </div>
                            <div class="p-3 sm:p-3.5">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-0.5">{{ __('BAHASA') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100 text-xs sm:text-sm block">{{ $buku->bahasa ?: 'Indonesia' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Komentar dan rating -->
    <section id="komentar-section" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-5 sm:p-8 space-y-6 scroll-mt-24">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ __('Komentar') }}
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ __('Diskusi & penilaian tentang') }} "{{ $buku->judul }}"
                </p>
            </div>
            <span class="min-w-[32px] h-7 px-2.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-extrabold inline-flex items-center justify-center" x-text="totalCommentsCount">
                {{ $buku->ratings->count() }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Form komentar -->
            <div class="lg:col-span-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-950/30 p-4 sm:p-5 space-y-3.5">
                @auth
                    @php
                        $commentUser = auth()->user();
                        $commentPalette = $commentUser->color_palette;
                    @endphp
                    <div class="flex items-center gap-3">
                        @if($commentUser->avatar_url)
                            <img src="{{ $commentUser->avatar_url }}" alt="{{ $commentUser->name }}" width="40" height="40" class="w-10 h-10 rounded-full object-cover shrink-0">
                        @else
                            <div 
                                class="w-10 h-10 rounded-full font-extrabold text-sm flex items-center justify-center shrink-0"
                                style="background-color: {{ $commentPalette['bg'] }}; color: {{ $commentPalette['text'] }};"
                            >
                                {{ $commentUser->initial }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate">{{ $commentUser->name }}</p>
                            <p class="text-[11px] text-blue-600 dark:text-blue-400 truncate">{{ '@' . $commentUser->display_username }}</p>
                        </div>
                    </div>

                    <form action="{{ route('buku.rating', $buku->id) }}" method="POST" @submit.prevent="submitComment()" class="space-y-3">
                        @csrf
                        <input type="hidden" name="bintang" :value="selectedRating">

                        <!-- Interactive Star Picker + Live 0/500 Counter -->
                        <div class="flex items-center justify-between gap-2 pt-1">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ __('Beri rating:') }}</span>
                                <div class="flex items-center gap-0.5">
                                    <template x-for="star in 5" :key="star">
                                        <button 
                                            type="button"
                                            @click="selectedRating = star"
                                            @mouseenter="ratingHover = star"
                                            @mouseleave="ratingHover = selectedRating"
                                            :aria-label="star + ' star'"
                                            class="p-0.5 text-lg transition-transform hover:scale-110"
                                        >
                                            <i class="ph-fill ph-star" :class="star <= ratingHover ? 'text-amber-400' : 'text-slate-300 dark:text-slate-700'" aria-hidden="true"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <span class="text-[11px] font-medium text-slate-400" x-text="commentText.length + '/500'">0/500</span>
                        </div>

                        <div>
                            <label for="ulasan-textarea" class="sr-only">{{ __('Komentar') }}</label>
                            <textarea 
                                id="ulasan-textarea"
                                name="ulasan"
                                x-model="commentText"
                                maxlength="500"
                                rows="4"
                                required
                                placeholder="{{ __('Bagikan pendapat tentang isi buku ini...') }}"
                                class="w-full rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-blue-500 p-3 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 outline-none resize-none transition-colors"
                            ></textarea>
                        </div>

                        <p class="text-[11px] text-slate-400 dark:text-slate-500 leading-relaxed">
                            {{ __('Gunakan bahasa yang sopan. Spam dan komentar kasar otomatis ditolak.') }}
                        </p>

                        <button 
                            type="submit"
                            :disabled="commentSubmitting || commentText.trim().length === 0"
                            class="w-full min-h-[44px] rounded-xl bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400/70 dark:disabled:bg-blue-800/50 text-white font-bold text-xs sm:text-sm py-2.5 flex items-center justify-center gap-2 transition-colors"
                        >
                            <i class="ph-bold text-sm" :class="commentSubmitting ? 'ph-spinner animate-spin' : 'ph-paper-plane-tilt'" aria-hidden="true"></i>
                            <span x-text="commentSubmitting ? 'Mengirim...' : '{{ __('Kirim komentar') }}'"></span>
                        </button>
                    </form>
                @else
                    <!-- Guest Comment Prompt Card -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-extrabold text-sm flex items-center justify-center shrink-0">
                            P
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Pembaca') }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Masuk untuk memberi rating & komentar') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <div class="flex items-center gap-0.5 text-amber-400 text-lg">
                            @for($s = 1; $s <= 5; $s++)
                                <i class="ph-fill ph-star" aria-hidden="true"></i>
                            @endfor
                        </div>
                        <span class="text-[11px] font-medium text-slate-400">0/500</span>
                    </div>

                    <div>
                        <label for="guest-ulasan-textarea" class="sr-only">{{ __('Komentar') }}</label>
                        <textarea 
                            id="guest-ulasan-textarea"
                            rows="4"
                            readonly
                            @click="openAuthModal('{{ __('Komentar') }}')"
                            placeholder="{{ __('Bagikan pendapat tentang isi buku ini...') }}"
                            class="w-full rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-3 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 outline-none resize-none cursor-pointer"
                        ></textarea>
                    </div>

                    <p class="text-[11px] text-slate-400 dark:text-slate-500 leading-relaxed">
                        {{ __('Gunakan bahasa yang sopan. Spam dan komentar kasar otomatis ditolak.') }}
                    </p>

                    <button 
                        type="button"
                        @click="openAuthModal('{{ __('Komentar') }}')"
                        class="w-full min-h-[44px] rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm py-2.5 flex items-center justify-center gap-2 transition-colors"
                    >
                        <i class="ph-bold ph-paper-plane-tilt text-sm" aria-hidden="true"></i>
                        <span>{{ __('Kirim komentar') }}</span>
                    </button>
                @endauth
            </div>

            <!-- Daftar komentar -->
            <div class="lg:col-span-7 space-y-3.5">
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                    {{ __('Komentar terbaru') }}
                </h3>

                <div x-show="totalCommentsCount === 0" class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-950/30 py-14 px-6 text-center space-y-1.5">
                    <div class="w-11 h-11 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl">
                        <i class="ph ph-chat-circle" aria-hidden="true"></i>
                    </div>
                    <p class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200">
                        {{ __('Belum ada komentar') }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                        {{ __('Jadilah pembaca pertama yang membuka diskusi positif.') }}
                    </p>
                </div>

                <div x-show="totalCommentsCount > 0" class="space-y-3 max-h-[460px] overflow-y-auto pr-1">
                    <template x-for="item in newComments" :key="'new-' + item.id">
                        <article class="p-4 rounded-2xl border border-blue-200 dark:border-blue-800/80 bg-blue-50/40 dark:bg-blue-950/20 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <template x-if="item.avatar_url">
                                        <img :src="item.avatar_url" :alt="item.nama_penilai" width="34" height="34" class="w-8 h-8 rounded-full object-cover shrink-0">
                                    </template>
                                    <template x-if="!item.avatar_url">
                                        <span 
                                            class="w-8 h-8 rounded-full text-xs font-extrabold flex items-center justify-center shrink-0"
                                            :style="`background-color: ${item.palette?.bg || '#dbeafe'}; color: ${item.palette?.text || '#1d4ed8'};`"
                                            x-text="item.initial || 'P'"
                                        ></span>
                                    </template>
                                    <div class="min-w-0">
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate" x-text="item.nama_penilai"></h4>
                                        <span class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold block" x-text="item.waktu"></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-0.5 text-amber-400 text-xs shrink-0">
                                    <template x-for="s in 5" :key="s">
                                        <i :class="s <= item.bintang ? 'ph-fill ph-star' : 'ph ph-star text-slate-300 dark:text-slate-600'"></i>
                                    </template>
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed" x-text="item.ulasan"></p>
                        </article>
                    </template>

                    @foreach($buku->ratings as $ulasan)
                        @php
                            $rUser = $ulasan->user;
                            $rPalette = $rUser ? $rUser->color_palette : ['bg' => '#dbeafe', 'text' => '#1d4ed8'];
                            $rInitial = $rUser ? $rUser->initial : mb_strtoupper(mb_substr(trim($ulasan->nama_penilai ?: 'P'), 0, 1));
                        @endphp
                        <article class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    @if($rUser && $rUser->avatar_url)
                                        <img src="{{ $rUser->avatar_url }}" alt="{{ $ulasan->nama_penilai }}" width="34" height="34" class="w-8 h-8 rounded-full object-cover shrink-0">
                                    @else
                                        <span 
                                            class="w-8 h-8 rounded-full text-xs font-extrabold flex items-center justify-center shrink-0"
                                            style="background-color: {{ $rPalette['bg'] }}; color: {{ $rPalette['text'] }};"
                                        >
                                            {{ $rInitial }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                                            {{ $rUser?->name ?? $ulasan->nama_penilai }}
                                        </h4>
                                        <span class="text-[10px] text-slate-400 block">{{ $ulasan->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-0.5 text-amber-400 text-xs shrink-0">
                                    @for($s = 1; $s <= 5; $s++)
                                        <i class="{{ $s <= $ulasan->bintang ? 'ph-fill ph-star' : 'ph ph-star text-slate-300 dark:text-slate-600' }}" aria-hidden="true"></i>
                                    @endfor
                                </div>
                            </div>

                            @if($ulasan->ulasan)
                                <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                                    {{ $ulasan->ulasan }}
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>

        </div>
    </section>

    <!-- Buku serupa -->
    @if($relatedBooks->isNotEmpty())
        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ __('Buku Serupa di Kategori') }} {{ $buku->kategori->nama_kategori ?? '' }}
                </h2>
                <a href="{{ route('perpustakaan.index', ['kategori' => $buku->kategori_id]) }}" class="text-xs sm:text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                    <span>{{ __('Lihat semua') }}</span>
                    <i class="ph-bold ph-caret-right text-xs" aria-hidden="true"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-5">
                @foreach($relatedBooks as $book)
                    @include('partials.book-card', ['book' => $book])
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection
