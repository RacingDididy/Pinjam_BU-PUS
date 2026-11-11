@php
    $isWishlisted = in_array($book->id, $wishlistIds ?? []);
    $ratingCount = $book->total_ratings_count;
    $avgRating = $book->average_rating;
    $eagerLoadCover = $eager ?? false;
@endphp

<article 
    x-data="{
        fav: {{ $isWishlisted ? 'true' : 'false' }},
        favBusy: false,
        async toggleWishlist() {
            if (this.favBusy) return;
            this.favBusy = true;
            try {
                const res = await fetch('{{ route('buku.favorite.toggle', $book->id) }}', {
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
                    this.fav = data.is_favorite;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.favBusy = false;
            }
        }
    }"
    class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 flex flex-col justify-between transition-all duration-200 hover:border-blue-400/70 dark:hover:border-blue-500/60 hover:shadow-md"
>
    <div>
        <!-- Cover Frame -->
        <div class="relative aspect-[3/4.2] rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800">
            <a href="{{ route('buku.show', $book->id) }}" class="block w-full h-full focus-visible:outline-none">
                <img 
                    src="{{ $book->cover_url }}" 
                    alt="{{ $book->judul }}" 
                    width="300"
                    height="420"
                    @if($eagerLoadCover) fetchpriority="high" @else loading="lazy" @endif
                    onerror="this.onerror=null;this.src='https://placehold.co/400x600/1e293b/ffffff?text={{ urlencode($book->judul) }}';"
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                >
            </a>

            <!-- Top-Left Category Pill -->
            <a 
                href="{{ route('perpustakaan.index', ['kategori' => $book->kategori_id]) }}"
                class="absolute top-2 left-2 max-w-[68%] truncate px-2 py-0.5 rounded-full bg-blue-600/95 hover:bg-blue-700 text-white text-[10px] font-bold shadow-sm transition-colors"
            >
                {{ $book->kategori->nama_kategori ?? 'Umum' }}
            </a>

            <!-- Top-Right Wishlist Button -->
            @guest
                <button 
                    type="button"
                    @click.prevent="$dispatch('open-auth-modal', { action: '{{ __('Wishlist') }}' })"
                    aria-label="{{ __('Wishlist') }}"
                    class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/95 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-700 text-slate-500 dark:text-slate-300 hover:text-rose-500 dark:hover:text-rose-400 flex items-center justify-center shadow-sm transition-transform active:scale-90"
                >
                    <i class="ph-bold ph-heart text-sm" aria-hidden="true"></i>
                </button>
            @else
                <button 
                    type="button"
                    @click.prevent="toggleWishlist()"
                    :disabled="favBusy"
                    aria-label="{{ __('Wishlist') }}"
                    class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/95 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center shadow-sm transition-transform active:scale-90"
                    :class="fav ? 'text-rose-500 border-rose-200 dark:border-rose-800' : 'text-slate-500 dark:text-slate-300 hover:text-rose-500'"
                >
                    <i class="text-sm" :class="fav ? 'ph-fill ph-heart' : 'ph-bold ph-heart'" aria-hidden="true"></i>
                </button>
            @endguest
        </div>

        <!-- Book Info -->
        <div class="pt-2.5 px-0.5">
            <a href="{{ route('buku.show', $book->id) }}" class="block group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                <h3 class="text-xs sm:text-[13px] font-bold text-slate-900 dark:text-slate-100 line-clamp-2 leading-snug">
                    {{ $book->judul }}
                </h3>
            </a>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                {{ $book->pengarang }}
            </p>

            <!-- Rating, Views & Stock Row -->
            <div class="mt-1.5 flex items-center justify-between gap-1 text-[10px] sm:text-[11px]">
                <div class="inline-flex items-center gap-2 min-w-0">
                    <span class="inline-flex items-center gap-0.5 font-semibold text-slate-700 dark:text-slate-300">
                        <i class="ph-fill ph-star text-amber-400 text-xs" aria-hidden="true"></i>
                        @if($ratingCount > 0)
                            <span>{{ number_format($avgRating, 1) }}</span>
                            <span class="text-slate-400 dark:text-slate-500">({{ $ratingCount }})</span>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 font-normal">0.0</span>
                        @endif
                    </span>

                    <span class="inline-flex items-center gap-0.5 text-slate-400 dark:text-slate-500" title="{{ number_format($book->views_count) }} {{ __('dilihat') }}">
                        <i class="ph-bold ph-eye text-xs" aria-hidden="true"></i>
                        <span>{{ number_format($book->views_count) }}</span>
                    </span>
                </div>

                <span class="font-bold shrink-0 {{ $book->stok > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $book->stok > 0 ? __('Stok') . ' ' . $book->stok : __('Habis') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Tombol aksi -->
    <div class="mt-2.5 grid grid-cols-2 gap-1.5">
        <a 
            href="{{ route('buku.show', $book->id) }}"
            class="min-h-[36px] px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-[11px] sm:text-xs font-semibold flex items-center justify-center transition-colors"
        >
            {{ __('Detail') }}
        </a>

        @if($book->stok <= 0)
            <button 
                type="button"
                disabled
                class="min-h-[36px] px-2 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 text-[11px] sm:text-xs font-semibold flex items-center justify-center cursor-not-allowed"
            >
                {{ __('Habis') }}
            </button>
        @else
            @guest
                <button 
                    type="button"
                    @click="$dispatch('open-auth-modal', { action: '{{ __('Pinjam') }}' })"
                    class="min-h-[36px] px-2 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-[11px] sm:text-xs font-bold flex items-center justify-center gap-1 shadow-sm transition-colors"
                >
                    <span>{{ __('Pinjam') }}</span>
                </button>
            @else
                <button 
                    x-show="cartQty({{ $book->id }}) === 0"
                    type="button"
                    @click="addToCart({{ $book->id }})"
                    :disabled="cartBusy"
                    class="min-h-[36px] px-2 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-white text-[11px] sm:text-xs font-bold flex items-center justify-center gap-1 shadow-sm transition-all disabled:opacity-60"
                >
                    <span>{{ __('Pinjam') }}</span>
                </button>

                <div 
                    x-cloak
                    x-show="cartQty({{ $book->id }}) > 0"
                    class="min-h-[36px] rounded-lg bg-blue-600 text-white flex items-center justify-between px-1 shadow-sm"
                >
                    <button 
                        type="button"
                        @click="updateCartQty({{ $book->id }}, 'decrement')"
                        :disabled="cartBusy"
                        aria-label="Kurangi jumlah"
                        class="w-6 h-7 rounded-md hover:bg-blue-700 flex items-center justify-center transition-colors disabled:opacity-40"
                    >
                        <i class="ph-bold ph-minus text-[10px]" aria-hidden="true"></i>
                    </button>
                    <input
                        type="number"
                        min="1"
                        max="{{ (int) $book->stok }}"
                        :value="cartQty({{ $book->id }})"
                        @change="updateCartQty({{ $book->id }}, 'set', $event.target.value)"
                        @keydown.enter.prevent="$event.target.blur()"
                        aria-label="Ketik jumlah buku"
                        class="w-9 h-6 text-center text-[11px] sm:text-xs font-extrabold bg-blue-700/60 focus:bg-white focus:text-slate-900 text-white rounded outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    >
                    <button 
                        type="button"
                        @click="updateCartQty({{ $book->id }}, 'increment')"
                        :disabled="cartBusy || cartQty({{ $book->id }}) >= {{ (int) $book->stok }}"
                        aria-label="Tambah jumlah"
                        class="w-6 h-7 rounded-md hover:bg-blue-700 flex items-center justify-center transition-colors disabled:opacity-40"
                    >
                        <i class="ph-bold ph-plus text-[10px]" aria-hidden="true"></i>
                    </button>
                </div>
            @endguest
        @endif
    </div>
</article>
