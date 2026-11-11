@extends('pustakawan.layouts.pustakawan')

@section('title', 'Kelola Katalog Buku - Panel Pustakawan')
@section('page-title', 'Kelola Katalog Buku')

@php
    $kategoriListJson = $kategoris->map(fn ($k) => [
        'id' => $k->id,
        'nama_kategori' => $k->nama_kategori,
    ])->values();
    $initialStocks = $bukus->getCollection()->mapWithKeys(fn ($b) => [$b->id => (int) $b->stok]);
@endphp

@section('content')
<div 
    class="w-full" 
    x-data="{ 
        openCreateModal: false,
        exportMenuOpen: false,
        pageIds: {{ Js::from($bukus->pluck('id')->map(fn($id) => (int) $id)->values()) }},
        deletedIds: [],
        selectedIds: [],
        stocks: {{ Js::from($initialStocks) }},
        stockSavingId: null,
        deleteModalOpen: false,
        deleteSubmitting: false,
        deleteMode: 'single',
        deleteTargetId: null,
        deleteTargetUrl: '',
        deleteTargetTitle: '',
        toastMsg: '',
        toastType: 'success',
        toastTimer: null,
        showToast(msg, type = 'success') {
            this.toastMsg = msg;
            this.toastType = type;
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toastMsg = ''; }, 3500);
        },
        isDeleted(id) {
            return this.deletedIds.includes(Number(id));
        },
        isSelected(id) {
            return this.selectedIds.includes(Number(id));
        },
        toggleSelect(id) {
            const numId = Number(id);
            if (this.isSelected(numId)) {
                this.selectedIds = this.selectedIds.filter(i => i !== numId);
            } else {
                this.selectedIds.push(numId);
            }
        },
        get activePageIds() {
            return this.pageIds.filter(id => !this.deletedIds.includes(Number(id)));
        },
        get isAllSelected() {
            return this.activePageIds.length > 0 && this.activePageIds.every(id => this.selectedIds.includes(Number(id)));
        },
        toggleSelectAll() {
            if (this.isAllSelected) {
                this.selectedIds = [];
            } else {
                this.selectedIds = [...this.activePageIds];
            }
        },
        openSingleDelete(id, url, title) {
            this.deleteMode = 'single';
            this.deleteTargetId = Number(id);
            this.deleteTargetUrl = url;
            this.deleteTargetTitle = title;
            this.deleteModalOpen = true;
        },
        openBulkDelete() {
            if (this.selectedIds.length === 0) return;
            this.deleteMode = 'bulk';
            this.deleteModalOpen = true;
        },
        async confirmDeleteAction() {
            if (this.deleteSubmitting) return;
            this.deleteSubmitting = true;
            const csrf = document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}';
            try {
                if (this.deleteMode === 'single') {
                    const res = await fetch(this.deleteTargetUrl, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        if (this.deleteTargetId) {
                            this.deletedIds.push(this.deleteTargetId);
                            this.selectedIds = this.selectedIds.filter(i => i !== this.deleteTargetId);
                        }
                        this.deleteModalOpen = false;
                        this.showToast(data.message || 'Buku berhasil dihapus!', 'success');
                    } else {
                        this.showToast(data.message || 'Gagal menghapus buku.', 'error');
                    }
                } else {
                    const res = await fetch('{{ route('pustakawan.buku.bulk-destroy') }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({ ids: this.selectedIds })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.deletedIds.push(...this.selectedIds);
                        this.selectedIds = [];
                        this.deleteModalOpen = false;
                        this.showToast(data.message || 'Buku terpilih berhasil dihapus!', 'success');
                    } else {
                        this.showToast(data.message || 'Gagal menghapus buku terpilih.', 'error');
                    }
                }
            } catch (e) {
                this.showToast('Terjadi gangguan jaringan saat menghapus buku.', 'error');
            } finally {
                this.deleteSubmitting = false;
            }
        },
        async saveBookStock(bookId, newVal) {
            let parsed = parseInt(newVal, 10);
            if (isNaN(parsed) || parsed < 0) parsed = 0;
            if (parsed > 9999) parsed = 9999;
            this.stocks[bookId] = parsed;
            this.stockSavingId = bookId;
            const csrf = document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}';
            try {
                const res = await fetch(`/pustakawan/buku/${bookId}/stok`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ stok: parsed })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.stocks[bookId] = data.stok;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Gagal memperbarui stok.', 'error');
                }
            } catch (e) {
                this.showToast('Gagal menyimpan perubahan stok.', 'error');
            } finally {
                this.stockSavingId = null;
            }
        }
    }"
>

    <!-- Floating Toast Notification (No Page Reload) -->
    <div 
        x-show="toastMsg" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-5 right-5 z-50 max-w-sm px-4 py-3 rounded-2xl shadow-xl border flex items-center gap-2.5 text-xs sm:text-sm font-bold"
        :class="toastType === 'error' ? 'bg-rose-600 text-white border-rose-500' : 'bg-slate-900 text-white border-slate-800'"
    >
        <i class="ph-fill text-lg shrink-0" :class="toastType === 'error' ? 'ph-warning-circle text-rose-200' : 'ph-check-circle text-emerald-400'"></i>
        <span x-text="toastMsg"></span>
    </div>

    <!-- Kontainer tabel buku -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Pencarian dan tombol aksi -->
        <div class="p-3.5 sm:p-6 border-b border-slate-100 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            
            <!-- Search Input -->
            <form action="{{ route('pustakawan.buku.index') }}" method="GET" class="w-full lg:w-80 relative">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari buku, pengarang, ISBN..." 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all pr-10"
                >
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 transition-colors">
                    <i class="ph ph-magnifying-glass text-base sm:text-lg"></i>
                </button>
            </form>

            <!-- Tombol Aksi Kanan (Hapus Banyak, Export Excel, & Tambah Buku) -->
            <div class="flex flex-wrap items-center gap-2">
                <template x-if="selectedIds.length > 0">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button 
                            type="button"
                            @click="selectedIds = []"
                            class="px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs transition-all"
                        >
                            Batal (<span x-text="selectedIds.length"></span>)
                        </button>
                        <button 
                            type="button"
                            @click="openBulkDelete()"
                            class="flex-1 sm:flex-initial bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-sm shadow-rose-500/20 transition-all active:scale-95"
                        >
                            <i class="ph-bold ph-trash text-base"></i>
                            <span>Hapus Terpilih (<span x-text="selectedIds.length"></span>)</span>
                        </button>
                    </div>
                </template>

                <!-- Dropdown Export Excel Data Buku -->
                <div class="relative" @click.away="exportMenuOpen = false">
                    <button
                        type="button"
                        @click="exportMenuOpen = !exportMenuOpen"
                        class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-sm shadow-emerald-500/20 transition-all"
                    >
                        <i class="ph-bold ph-microsoft-excel-logo text-base sm:text-lg"></i>
                        <span>Export Excel Buku</span>
                        <i class="ph-bold ph-caret-down text-xs"></i>
                    </button>

                    <div
                        x-show="exportMenuOpen"
                        x-cloak
                        x-transition
                        class="absolute right-0 mt-2 w-72 rounded-2xl bg-white border border-slate-200 shadow-xl p-2 z-30 space-y-1"
                    >
                        <div class="px-2.5 py-1.5 border-b border-slate-100">
                            <p class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wider">Pilih Format Laporan Excel</p>
                            <p class="text-[10px] text-slate-400">Unduh katalog buku dalam format .xlsx</p>
                        </div>
                        <a
                            href="{{ route('pustakawan.export.buku', ['mode' => 'both', 'search' => request('search')]) }}"
                            @click="exportMenuOpen = false"
                            class="flex items-start gap-2.5 p-2.5 rounded-xl hover:bg-emerald-50 transition-colors group"
                        >
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="ph-bold ph-files text-base"></i>
                            </div>
                            <div>
                                <p class="text-xs font-extrabold text-slate-900 group-hover:text-emerald-700">Paket Lengkap (2 Sheet)</p>
                                <p class="text-[10px] text-slate-500">Laporan Sudah Jadi + Data Raw Mentah</p>
                            </div>
                        </a>
                        <a
                            href="{{ route('pustakawan.export.buku', ['mode' => 'formatted', 'search' => request('search')]) }}"
                            @click="exportMenuOpen = false"
                            class="flex items-start gap-2.5 p-2.5 rounded-xl hover:bg-blue-50 transition-colors group"
                        >
                            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="ph-bold ph-table text-base"></i>
                            </div>
                            <div>
                                <p class="text-xs font-extrabold text-slate-900 group-hover:text-blue-700">Data Sudah Jadi (Siap Cetak)</p>
                                <p class="text-[10px] text-slate-500">Tabel rapi berformat kop SMK N 5 Surakarta</p>
                            </div>
                        </a>
                        <a
                            href="{{ route('pustakawan.export.buku', ['mode' => 'raw', 'search' => request('search')]) }}"
                            @click="exportMenuOpen = false"
                            class="flex items-start gap-2.5 p-2.5 rounded-xl hover:bg-slate-100 transition-colors group"
                        >
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="ph-bold ph-database text-base"></i>
                            </div>
                            <div>
                                <p class="text-xs font-extrabold text-slate-900">Data Raw / Mentah</p>
                                <p class="text-[10px] text-slate-500">Kolom database murni untuk olah data lanjut</p>
                            </div>
                        </a>
                    </div>
                </div>

                <button 
                    type="button"
                    @click="openCreateModal = true" 
                    class="w-full sm:w-auto bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-sm shadow-blue-500/20 transition-all active:scale-95"
                >
                    <i class="ph-bold ph-plus-circle text-base sm:text-lg"></i>
                    <span>Tambah Buku Baru</span>
                </button>
            </div>

        </div>

        <!-- Bar Pilih Semua untuk Mobile -->
        @if($bukus->count() > 0)
            <div class="md:hidden px-3.5 py-2.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input 
                        type="checkbox" 
                        :checked="isAllSelected" 
                        @change="toggleSelectAll()"
                        class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                    >
                    <span class="text-xs font-bold text-slate-700">Pilih Semua di Halaman Ini</span>
                </label>
                <span x-show="selectedIds.length > 0" class="text-[11px] font-extrabold text-blue-600">
                    <span x-text="selectedIds.length"></span> dipilih
                </span>
            </div>
        @endif

        <!-- Daftar Kartu Buku Ringkas untuk Mobile -->
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($bukus as $buku)
                <div 
                    x-show="!isDeleted({{ $buku->id }})"
                    x-transition
                    class="p-3.5 flex items-start gap-3 transition-colors"
                    :class="isSelected({{ $buku->id }}) ? 'bg-blue-50/50' : ''"
                >
                    <div class="pt-1 shrink-0">
                        <input 
                            type="checkbox" 
                            :checked="isSelected({{ $buku->id }})"
                            @change="toggleSelect({{ $buku->id }})"
                            class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                        >
                    </div>

                    <div class="w-14 h-20 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-200/70">
                        <img src="{{ $buku->cover_url }}" alt="{{ $buku->judul }}" class="w-full h-full object-cover">
                    </div>

                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="font-bold text-xs text-slate-900 line-clamp-2 leading-snug">
                                {{ $buku->judul }}
                            </h4>
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-600 border border-blue-100 shrink-0">
                                {{ $buku->kategori->nama_kategori ?? 'Umum' }}
                            </span>
                        </div>

                        <p class="text-[11px] text-slate-500 truncate">
                            {{ $buku->pengarang }} &bull; {{ $buku->tanggal_terbit_formatted }}
                        </p>

                        <div class="pt-1 flex items-center justify-between gap-2">
                            <!-- Inline Typable Stock Stepper -->
                            <div class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                                <button
                                    type="button"
                                    @click="saveBookStock({{ $buku->id }}, (stocks[{{ $buku->id }}] || 0) - 1)"
                                    class="w-6 h-6 rounded text-slate-600 hover:bg-white flex items-center justify-center text-[10px]"
                                >
                                    <i class="ph-bold ph-minus"></i>
                                </button>
                                <input
                                    type="number"
                                    min="0"
                                    :value="stocks[{{ $buku->id }}]"
                                    @change="saveBookStock({{ $buku->id }}, $event.target.value)"
                                    class="w-10 h-6 text-center text-xs font-extrabold text-emerald-700 bg-white rounded border-x border-slate-200 outline-none"
                                >
                                <button
                                    type="button"
                                    @click="saveBookStock({{ $buku->id }}, (stocks[{{ $buku->id }}] || 0) + 1)"
                                    class="w-6 h-6 rounded text-slate-600 hover:bg-white flex items-center justify-center text-[10px]"
                                >
                                    <i class="ph-bold ph-plus"></i>
                                </button>
                            </div>

                            <div class="inline-flex items-center gap-1.5">
                                <a 
                                    href="{{ route('pustakawan.buku.edit', $buku) }}" 
                                    class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:text-blue-600 text-[11px] font-bold inline-flex items-center gap-1"
                                >
                                    <i class="ph-bold ph-pencil-simple text-xs"></i>
                                    <span>Edit</span>
                                </a>

                                <button 
                                    type="button" 
                                    @click="openSingleDelete({{ $buku->id }}, '{{ route('pustakawan.buku.destroy', $buku) }}', {{ Js::from($buku->judul) }})"
                                    class="w-7 h-7 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors"
                                    title="Hapus Buku"
                                >
                                    <i class="ph-bold ph-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-10 px-4 text-center text-slate-500">
                    <p class="text-xs font-bold text-slate-700">Tidak ada data buku yang sesuai</p>
                </div>
            @endforelse
        </div>

        <!-- Tabel data buku (Desktop) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-4 pl-6 pr-2 w-10">
                            <input 
                                type="checkbox" 
                                :checked="isAllSelected"
                                @change="toggleSelectAll()"
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                title="Pilih Semua di Halaman Ini"
                            >
                        </th>
                        <th class="py-4 px-6">Buku &amp; Tanggal Terbit</th>
                        <th class="py-4 px-6">Kategori</th>
                        <th class="py-4 px-6">Stok</th>
                        <th class="py-4 px-6">ISBN</th>
                        <th class="py-4 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($bukus as $buku)
                        <tr 
                            x-show="!isDeleted({{ $buku->id }})"
                            x-transition
                            class="hover:bg-slate-50/60 transition-colors group"
                            :class="isSelected({{ $buku->id }}) ? 'bg-blue-50/50' : ''"
                        >
                            <!-- Kolom Checkbox Pilih -->
                            <td class="py-4 pl-6 pr-2">
                                <input 
                                    type="checkbox" 
                                    :checked="isSelected({{ $buku->id }})"
                                    @change="toggleSelect({{ $buku->id }})"
                                    class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                >
                            </td>
                            
                            <!-- Kolom Buku: Thumbnail + Judul + Penulis • Tanggal Terbit -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-11 h-15 rounded-lg overflow-hidden bg-slate-100 shrink-0 shadow-sm border border-slate-200/60 aspect-[3/4.2]">
                                        <img src="{{ $buku->cover_url }}" alt="{{ $buku->judul }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="max-w-xs">
                                        <h4 class="font-bold text-slate-900 leading-snug line-clamp-2 group-hover:text-blue-600 transition-colors">
                                            {{ $buku->judul }}
                                        </h4>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $buku->pengarang }} &bull; <span class="text-slate-600 font-medium">{{ $buku->tanggal_terbit_formatted }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom Kategori -->
                            <td class="py-4 px-6">
                                <span class="inline-block px-3 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-100">
                                    {{ $buku->kategori->nama_kategori ?? 'Umum' }}
                                </span>
                            </td>

                            <!-- Kolom Stok: Dapat Diketik Langsung + Stepper -->
                            <td class="py-4 px-6">
                                <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-0.5 shadow-2xs">
                                    <button
                                        type="button"
                                        @click="saveBookStock({{ $buku->id }}, (stocks[{{ $buku->id }}] || 0) - 1)"
                                        :disabled="stockSavingId === {{ $buku->id }}"
                                        class="w-7 h-7 rounded-lg text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors"
                                        title="Kurangi Stok"
                                    >
                                        <i class="ph-bold ph-minus text-xs"></i>
                                    </button>
                                    <input
                                        type="number"
                                        min="0"
                                        max="9999"
                                        :value="stocks[{{ $buku->id }}]"
                                        @change="saveBookStock({{ $buku->id }}, $event.target.value)"
                                        @keydown.enter.prevent="$event.target.blur()"
                                        title="Ketik langsung jumlah stok lalu tekan Enter"
                                        class="w-12 h-7 text-center text-xs font-extrabold text-emerald-700 bg-transparent border-x border-slate-100 outline-none focus:bg-emerald-50/50 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                    >
                                    <button
                                        type="button"
                                        @click="saveBookStock({{ $buku->id }}, (stocks[{{ $buku->id }}] || 0) + 1)"
                                        :disabled="stockSavingId === {{ $buku->id }}"
                                        class="w-7 h-7 rounded-lg text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors"
                                        title="Tambah Stok"
                                    >
                                        <i class="ph-bold ph-plus text-xs"></i>
                                    </button>
                                </div>
                            </td>

                            <!-- Kolom ISBN -->
                            <td class="py-4 px-6">
                                <span class="text-slate-700 font-mono text-xs">
                                    {{ $buku->isbn }}
                                </span>
                            </td>

                            <!-- Kolom Aksi: Edit & Hapus -->
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a 
                                        href="{{ route('pustakawan.buku.edit', $buku) }}" 
                                        class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Edit Buku"
                                    >
                                        <i class="ph-bold ph-pencil-simple text-base"></i>
                                    </a>

                                    <button 
                                        type="button" 
                                        @click="openSingleDelete({{ $buku->id }}, '{{ route('pustakawan.buku.destroy', $buku) }}', {{ Js::from($buku->judul) }})"
                                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                        title="Hapus Buku"
                                    >
                                        <i class="ph-bold ph-trash text-base"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-xl">
                                    <i class="ph ph-books"></i>
                                </div>
                                <p class="font-medium text-slate-700">Tidak ada data buku yang sesuai</p>
                                <p class="text-xs text-slate-400 mt-1">Gunakan kata kunci lain atau tambahkan buku baru ke sistem.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-3.5 sm:p-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
            <div>
                Menampilkan {{ $bukus->firstItem() ?? 0 }} hingga {{ $bukus->lastItem() ?? 0 }} dari {{ number_format($bukus->total(), 0, ',', '.') }} buku
            </div>

            <div class="flex items-center gap-1.5">
                @if ($bukus->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-300 cursor-not-allowed">Prev</span>
                @else
                    <a href="{{ $bukus->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-blue-600 transition-colors">Prev</a>
                @endif

                @foreach ($bukus->getUrlRange(max(1, $bukus->currentPage() - 2), min($bukus->lastPage(), $bukus->currentPage() + 2)) as $page => $url)
                    @if ($page == $bukus->currentPage())
                        <span class="w-8 h-8 rounded-lg bg-[#2563EB] text-white font-bold flex items-center justify-center shadow-sm">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" class="w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-blue-600 flex items-center justify-center transition-colors">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach

                @if ($bukus->hasMorePages())
                    <a href="{{ $bukus->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-blue-600 transition-colors">Next</a>
                @else
                    <span class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-300 cursor-not-allowed">Next</span>
                @endif
            </div>
        </div>

    </div>

    <!-- Modal tambah buku -->
    <div 
        x-show="openCreateModal" 
        x-cloak 
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
        <div 
            @click.away="openCreateModal = false"
            class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-2xl w-full max-h-[92vh] flex flex-col overflow-hidden transform transition-all"
        >
            <!-- Header Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 shrink-0 bg-white">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                        <i class="ph-bold ph-plus-circle"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-tight">Tambah Buku Baru</h3>
                        <p class="text-xs text-slate-500">Lengkapi informasi buku beserta tanggal terbit &amp; kategori koleksi.</p>
                    </div>
                </div>
                <button type="button" @click="openCreateModal = false" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 p-1.5 rounded-lg transition-colors">
                    <i class="ph-bold ph-x text-lg"></i>
                </button>
            </div>

            <!-- Form Tambah Buku -->
            <form 
                action="{{ route('pustakawan.buku.store') }}" 
                method="POST" 
                enctype="multipart/form-data" 
                class="flex flex-col flex-1 overflow-hidden"
                x-data="{ 
                    pubDay: {{ (int) date('j') }},
                    pubMonth: {{ (int) date('n') }},
                    pubYear: {{ (int) date('Y') }},
                    bookDate: '{{ date('Y-m-d') }}',
                    bookStock: 5,
                    selectedKategoriId: @js($kategoris->first()?->id ?? ''),
                    kategoriSearch: '',
                    kategoriList: @js($kategoriListJson),
                    showAddKategori: false,
                    newKategoriName: '',
                    addingKategori: false,
                    kategoriError: '',
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    syncDateFromParts() {
                        let y = parseInt(this.pubYear, 10);
                        if (isNaN(y) || y < 1900) y = 1900;
                        if (y > {{ date('Y') + 1 }}) y = {{ date('Y') + 1 }};
                        let m = parseInt(this.pubMonth, 10);
                        if (isNaN(m) || m < 1) m = 1;
                        if (m > 12) m = 12;
                        const maxD = new Date(y, m, 0).getDate();
                        let d = parseInt(this.pubDay, 10);
                        if (isNaN(d) || d < 1) d = 1;
                        if (d > maxD) d = maxD;
                        this.pubDay = d;
                        this.pubMonth = m;
                        this.bookDate = `${String(y).padStart(4, '0')}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    },
                    syncPartsFromDate() {
                        if (!this.bookDate) return;
                        const parts = this.bookDate.split('-');
                        if (parts.length === 3) {
                            this.pubYear = parseInt(parts[0], 10) || {{ date('Y') }};
                            this.pubMonth = parseInt(parts[1], 10) || 1;
                            this.pubDay = parseInt(parts[2], 10) || 1;
                        }
                    },
                    get formattedPubDate() {
                        const mName = this.monthNames[(this.pubMonth || 1) - 1] || 'Januari';
                        return `${String(this.pubDay || 1).padStart(2, '0')} ${mName} ${this.pubYear || '{{ date('Y') }}'}`;
                    },
                    get filteredKategoris() {
                        const q = (this.kategoriSearch || '').toLowerCase().trim();
                        if (!q) return this.kategoriList;
                        return this.kategoriList.filter(k => k.nama_kategori.toLowerCase().includes(q));
                    },
                    get selectedKategoriName() {
                        const found = this.kategoriList.find(k => String(k.id) === String(this.selectedKategoriId));
                        return found ? found.nama_kategori : 'Belum dipilih';
                    },
                    categoryIcon(name) {
                        const n = (name || '').toLowerCase();
                        if (n.includes('komputer') || n.includes('teknologi') || n.includes('informatika') || n.includes('rpl')) return 'ph-desktop-tower';
                        if (n.includes('agama') || n.includes('islam')) return 'ph-mosque';
                        if (n.includes('bahasa') || n.includes('inggris') || n.includes('indonesia')) return 'ph-translate';
                        if (n.includes('sains') || n.includes('ipa') || n.includes('matematika') || n.includes('fisika') || n.includes('kimia') || n.includes('kesehatan')) return 'ph-atom';
                        if (n.includes('ekonomi') || n.includes('bisnis') || n.includes('akuntansi') || n.includes('manajemen')) return 'ph-chart-line-up';
                        if (n.includes('sejarah') || n.includes('geografi') || n.includes('ips') || n.includes('budaya')) return 'ph-globe-hemisphere-east';
                        if (n.includes('biografi') || n.includes('tokoh')) return 'ph-user-focus';
                        if (n.includes('seni') || n.includes('desain') || n.includes('kriya') || n.includes('musik') || n.includes('olahraga')) return 'ph-paint-brush-broad';
                        if (n.includes('fiksi') || n.includes('novel') || n.includes('sastra') || n.includes('cerita') || n.includes('komik')) return 'ph-sparkle';
                        if (n.includes('psikologi') || n.includes('filsafat') || n.includes('pengembangan')) return 'ph-brain';
                        if (n.includes('resep') || n.includes('kuliner') || n.includes('boga')) return 'ph-cooking-pot';
                        if (n.includes('hukum') || n.includes('sosial') || n.includes('ppkn') || n.includes('pancasila')) return 'ph-scales';
                        if (n.includes('mesin') || n.includes('otomotif') || n.includes('elektro') || n.includes('teknik') || n.includes('bangunan')) return 'ph-wrench';
                        return 'ph-books';
                    },
                    async createQuickKategori() {
                        const name = (this.newKategoriName || '').trim();
                        if (!name || this.addingKategori) return;
                        this.addingKategori = true;
                        this.kategoriError = '';
                        try {
                            const res = await fetch('{{ route('pustakawan.kategori.quick') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ nama_kategori: name })
                            });
                            const data = await res.json();
                            if (res.ok && data.success && data.kategori) {
                                this.kategoriList.push(data.kategori);
                                this.selectedKategoriId = data.kategori.id;
                                this.newKategoriName = '';
                                this.showAddKategori = false;
                            } else {
                                const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
                                this.kategoriError = firstErr || data.message || 'Gagal menambah kategori.';
                            }
                        } catch (e) {
                            this.kategoriError = 'Gagal menghubungi server.';
                        } finally {
                            this.addingKategori = false;
                        }
                    },
                    imageMode: 'upload', 
                    useExtraImages: false,
                    previewFront: '', 
                    frontName: '', 
                    previewSide: '', 
                    sideName: '', 
                    previewBack: '', 
                    backName: '', 
                    handleFile(slot, e) { 
                        const file = e.target.files[0]; 
                        if (!file) return;
                        const url = URL.createObjectURL(file);
                        if (slot === 'front') {
                            this.frontName = file.name;
                            this.previewFront = url;
                        } else if (slot === 'side') {
                            this.sideName = file.name;
                            this.previewSide = url;
                        } else if (slot === 'back') {
                            this.backName = file.name;
                            this.previewBack = url;
                        }
                    } 
                }"
            >
                @csrf
                <input type="hidden" name="use_extra_images" :value="useExtraImages ? '1' : '0'">
                <input type="hidden" name="tahun_terbit" :value="pubYear">
                <input type="hidden" name="tgl_hari" :value="pubDay">
                <input type="hidden" name="tgl_bulan" :value="pubMonth">
                <input type="hidden" name="tgl_tahun" :value="pubYear">
                <input type="hidden" name="kategori_id" :value="selectedKategoriId">

                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Judul Buku -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Judul Buku <span class="text-rose-500">*</span></label>
                            <input type="text" name="judul" required placeholder="Contoh: Laskar Pelangi" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
                        </div>

                        <!-- Pengarang -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pengarang <span class="text-rose-500">*</span></label>
                            <input type="text" name="pengarang" required placeholder="Contoh: Andrea Hirata" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
                        </div>

                        <!-- Penerbit -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Penerbit <span class="text-rose-500">*</span></label>
                            <input type="text" name="penerbit" required placeholder="Contoh: Bentang Pustaka" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
                        </div>

                        <!-- Tanggal Terbit Lengkap (Tanggal, Bulan, Tahun) -->
                        <div class="sm:col-span-2 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="ph-bold ph-calendar-blank text-blue-600"></i>
                                    <span>Tanggal Terbit (Tanggal, Bulan, Tahun) <span class="text-rose-500">*</span></span>
                                </label>
                                <span class="px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-[11px] font-bold" x-text="formattedPubDate"></span>
                            </div>

                            <div class="grid grid-cols-12 gap-2 items-center">
                                <div class="col-span-3 sm:col-span-2">
                                    <span class="block text-[10px] font-bold text-slate-500 mb-1">Tanggal</span>
                                    <input type="number" min="1" max="31" x-model.number="pubDay" @input="syncDateFromParts()" class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-bold text-center text-slate-900 outline-none focus:border-blue-500">
                                </div>
                                <div class="col-span-5 sm:col-span-4">
                                    <span class="block text-[10px] font-bold text-slate-500 mb-1">Bulan</span>
                                    <select x-model.number="pubMonth" @change="syncDateFromParts()" class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-bold text-slate-900 outline-none focus:border-blue-500">
                                        <template x-for="(mName, idx) in monthNames" :key="idx">
                                            <option :value="idx + 1" :selected="pubMonth === (idx + 1)" x-text="(idx + 1) + ' - ' + mName"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-span-4 sm:col-span-3">
                                    <span class="block text-[10px] font-bold text-slate-500 mb-1">Tahun</span>
                                    <input type="number" min="1900" max="{{ date('Y') + 1 }}" x-model.number="pubYear" @input="syncDateFromParts()" class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-bold text-center text-slate-900 outline-none focus:border-blue-500">
                                </div>
                                <div class="col-span-12 sm:col-span-3">
                                    <span class="block text-[10px] font-bold text-slate-500 mb-1">Kalender</span>
                                    <input type="date" name="tanggal_terbit" x-model="bookDate" @change="syncPartsFromDate()" required class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- ISBN -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">ISBN <span class="text-rose-500">*</span></label>
                            <input type="text" name="isbn" required placeholder="Contoh: 9786022916964" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all font-mono">
                        </div>

                        <!-- Stok (Typable + Stepper) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jumlah Stok (Ketik / Klik) <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-1.5">
                                <div class="flex-1 flex items-center rounded-xl border border-slate-200 bg-white p-0.5">
                                    <button type="button" @click="bookStock = Math.max(0, (parseInt(bookStock, 10) || 0) - 1)" class="w-9 h-9 rounded-lg text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                                        <i class="ph-bold ph-minus text-xs"></i>
                                    </button>
                                    <input type="number" name="stok" x-model.number="bookStock" required min="0" class="w-full text-center font-extrabold text-sm text-slate-900 bg-transparent outline-none px-2 py-1.5">
                                    <button type="button" @click="bookStock = (parseInt(bookStock, 10) || 0) + 1" class="w-9 h-9 rounded-lg text-slate-600 hover:bg-slate-100 flex items-center justify-center">
                                        <i class="ph-bold ph-plus text-xs"></i>
                                    </button>
                                </div>
                                <button type="button" @click="bookStock = (parseInt(bookStock, 10) || 0) + 5" class="px-2.5 py-2 rounded-xl bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-xs font-extrabold text-slate-600">+5</button>
                            </div>
                        </div>

                        <!-- Pilihan Kategori Visual Interaktif -->
                        <div class="sm:col-span-2 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="ph-bold ph-squares-four text-blue-600"></i>
                                        <span>Pilih Kategori Koleksi <span class="text-rose-500">*</span></span>
                                    </label>
                                    <p class="text-[11px] text-slate-500">Terpilih: <strong class="text-blue-600" x-text="selectedKategoriName"></strong></p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <input type="text" x-model="kategoriSearch" placeholder="Cari kategori..." class="w-32 sm:w-40 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs outline-none focus:border-blue-500">
                                    <button type="button" @click="showAddKategori = !showAddKategori" class="px-2.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold">
                                        + Baru
                                    </button>
                                </div>
                            </div>

                            <div x-show="showAddKategori" x-transition class="p-2.5 rounded-xl bg-white border border-blue-200 flex items-center gap-2">
                                <input type="text" x-model="newKategoriName" @keydown.enter.prevent="createQuickKategori()" placeholder="Nama kategori baru..." class="flex-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs outline-none focus:border-blue-500">
                                <button type="button" @click="createQuickKategori()" :disabled="addingKategori" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold">Simpan</button>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-44 overflow-y-auto pr-1">
                                <template x-for="kat in filteredKategoris" :key="kat.id">
                                    <button
                                        type="button"
                                        @click="selectedKategoriId = kat.id"
                                        :class="String(selectedKategoriId) === String(kat.id)
                                            ? 'border-blue-600 bg-blue-600 text-white shadow-xs'
                                            : 'border-slate-200 bg-white hover:border-blue-300 text-slate-700'"
                                        class="flex items-center gap-2 p-2 rounded-xl border text-left transition-all"
                                    >
                                        <div
                                            :class="String(selectedKategoriId) === String(kat.id) ? 'bg-white/20 text-white' : 'bg-blue-50 text-blue-600'"
                                            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                        >
                                            <i class="ph-bold text-sm" :class="categoryIcon(kat.nama_kategori)"></i>
                                        </div>
                                        <span class="text-xs font-bold line-clamp-1 flex-1" x-text="kat.nama_kategori"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Gambar buku -->
                        <div class="sm:col-span-2 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3.5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                                        Cover Buku (Upload Gambar / Link URL)
                                    </label>
                                    <p class="text-[11px] text-slate-500">Pilih metode untuk memasukkan gambar sampul buku.</p>
                                </div>
                                <div class="inline-flex p-1 bg-white border border-slate-200 rounded-xl text-xs self-start sm:self-auto shrink-0">
                                    <button 
                                        type="button" 
                                        @click="imageMode = 'upload'"
                                        :class="imageMode === 'upload' ? 'bg-[#2563EB] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold transition-all"
                                    >
                                        <i class="ph-bold ph-upload-simple"></i>
                                        <span>Upload File</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="imageMode = 'url'"
                                        :class="imageMode === 'url' ? 'bg-[#2563EB] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold transition-all"
                                    >
                                        <i class="ph-bold ph-link"></i>
                                        <span>Link URL</span>
                                    </button>
                                </div>
                            </div>

                            <!-- 1. Sampul Depan (Selalu tampil) -->
                            <div class="bg-white rounded-xl border border-slate-200 p-3.5 flex items-center gap-4">
                                <div class="w-20 h-28 shrink-0 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden flex flex-col items-center justify-center">
                                    <template x-if="previewFront">
                                        <img :src="previewFront" alt="Preview Sampul Depan" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!previewFront">
                                        <div class="text-center p-2 text-slate-400">
                                            <i class="ph ph-image text-xl block mb-0.5"></i>
                                            <span class="text-[10px] font-medium block leading-tight">Preview</span>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex-1 min-w-0 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-800">1. Sampul Depan</span>
                                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-bold">Utama</span>
                                    </div>

                                    <div x-show="imageMode === 'upload'">
                                        <label class="flex flex-col items-center justify-center w-full py-3 px-3 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer bg-slate-50/50 hover:bg-blue-50/40 hover:border-blue-400 transition-all text-center">
                                            <span class="text-xs font-semibold text-slate-700 inline-flex items-center gap-1.5">
                                                <i class="ph-bold ph-cloud-arrow-up text-blue-600 text-sm"></i>
                                                <span>Klik untuk pilih gambar sampul depan</span>
                                            </span>
                                            <span class="text-[10px] text-slate-400 mt-0.5" x-show="!frontName">PNG, JPG, JPEG, WEBP (Maks. 3MB)</span>
                                            <span class="text-[11px] font-bold text-blue-600 mt-0.5 truncate max-w-full" x-show="frontName" x-text="frontName"></span>
                                            <input type="file" name="cover_file" accept="image/*" class="hidden" @change="handleFile('front', $event)">
                                        </label>
                                    </div>

                                    <div x-show="imageMode === 'url'" class="space-y-1">
                                        <input 
                                            type="url" 
                                            name="cover_image" 
                                            x-model="previewFront" 
                                            placeholder="https://... tempel link URL sampul depan" 
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2 text-xs text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sinopsis -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Sinopsis / Ringkasan</label>
                            <textarea name="sinopsis" rows="3" placeholder="Tuliskan sinopsis singkat buku..." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-end gap-3 shrink-0">
                    <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition-all">
                        Simpan Buku
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pop-up Konfirmasi Hapus Buku (Satuan & Pilih Banyak - Smooth AJAX) -->
    <div 
        x-show="deleteModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 !mt-0"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div 
            @click.away="deleteModalOpen = false"
            class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full overflow-hidden p-5 sm:p-6 text-center space-y-4"
        >
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center mx-auto text-2xl shadow-xs">
                <i class="ph-bold ph-trash"></i>
            </div>

            <div class="space-y-1.5">
                <h3 class="text-base sm:text-lg font-extrabold text-slate-900" x-text="deleteMode === 'bulk' ? 'Hapus ' + selectedIds.length + ' Buku Sekaligus?' : 'Hapus Buku dari Katalog?'"></h3>
                
                <template x-if="deleteMode === 'single'">
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                        Apakah Anda yakin ingin menghapus buku <strong class="text-slate-800" x-text="'&quot;' + deleteTargetTitle + '&quot;'"></strong>? Data buku yang dihapus tidak dapat dikembalikan.
                    </p>
                </template>

                <template x-if="deleteMode === 'bulk'">
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                        Apakah Anda yakin ingin menghapus <strong class="text-rose-600" x-text="selectedIds.length + ' buku yang dipilih'"></strong> secara permanen dari katalog perpustakaan?
                    </p>
                </template>
            </div>

            <div class="pt-2 flex items-center gap-2.5">
                <button 
                    type="button" 
                    @click="deleteModalOpen = false"
                    :disabled="deleteSubmitting"
                    class="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm transition-colors"
                >
                    Batal
                </button>
                <button 
                    type="button"
                    @click="confirmDeleteAction()"
                    :disabled="deleteSubmitting"
                    class="flex-1 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-rose-500/20 transition-all inline-flex items-center justify-center gap-1.5 disabled:opacity-60"
                >
                    <i class="ph-bold text-sm" :class="deleteSubmitting ? 'ph-spinner animate-spin' : 'ph-trash'"></i>
                    <span x-text="deleteSubmitting ? 'Menghapus...' : (deleteMode === 'bulk' ? 'Hapus (' + selectedIds.length + ' Buku)' : 'Ya, Hapus')"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
