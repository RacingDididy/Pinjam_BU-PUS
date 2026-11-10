@extends('pustakawan.layouts.pustakawan')

@section('title', 'Kelola Kategori Buku')
@section('page-title', 'Manajemen Kategori Buku Perpustakaan')

@section('content')
<div 
    x-data="{
        kategoris: {{ Js::from($kategoris->map(fn($k) => [
            'id' => $k->id,
            'nama_kategori' => $k->nama_kategori,
            'deskripsi' => $k->deskripsi ?? '',
            'bukus_count' => (int) $k->bukus_count,
            'total_stok' => (int) ($k->total_stok ?? 0),
            'total_dipinjam' => (int) ($k->total_dipinjam ?? 0)
        ])) }},
        metrics: {{ Js::from($reportMetrics) }},
        searchKat: '',
        addKategoriName: '',
        addKategoriDesc: '',
        savingKategori: false,
        selectedIds: [],
        deleteModalOpen: false,
        deleteMode: 'single',
        deleteTargetKat: null,
        deletingKategori: false,
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
        get deletableFilteredIds() {
            return (this.filteredKategoris || [])
                .filter(k => Number(k.bukus_count) === 0)
                .map(k => Number(k.id));
        },
        get isAllDeletableSelected() {
            const ids = this.deletableFilteredIds;
            return ids.length > 0 && ids.every(id => this.selectedIds.includes(id));
        },
        toggleSelectAllDeletable() {
            const ids = this.deletableFilteredIds;
            if (this.isAllDeletableSelected) {
                this.selectedIds = this.selectedIds.filter(id => !ids.includes(id));
            } else {
                this.selectedIds = Array.from(new Set([...this.selectedIds, ...ids]));
            }
        },
        get filteredKategoris() {
            if (!this.searchKat.trim()) return this.kategoris;
            const q = this.searchKat.toLowerCase();
            return (this.kategoris || []).filter(k =>
                (k.nama_kategori || '').toLowerCase().includes(q)
                || (k.deskripsi || '').toLowerCase().includes(q)
            );
        },
        async createKategori() {
            if (this.savingKategori || !this.addKategoriName.trim()) return;
            this.savingKategori = true;
            try {
                const res = await fetch('{{ route('pustakawan.kategori.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        nama_kategori: this.addKategoriName,
                        deskripsi: this.addKategoriDesc
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.kategoris.push(data.kategori);
                    this.addKategoriName = '';
                    this.addKategoriDesc = '';
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    const err = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal menambah kategori.');
                    $dispatch('notify-toast', { message: err, type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.savingKategori = false;
            }
        },
        openSingleDelete(kat) {
            this.deleteMode = 'single';
            this.deleteTargetKat = kat;
            this.deleteModalOpen = true;
        },
        openBulkDelete() {
            if (this.selectedIds.length === 0) return;
            this.deleteMode = 'bulk';
            this.deleteModalOpen = true;
        },
        async confirmDeleteKategori() {
            if (this.deletingKategori) return;
            this.deletingKategori = true;
            try {
                if (this.deleteMode === 'single' && this.deleteTargetKat) {
                    const kat = this.deleteTargetKat;
                    const res = await fetch(`/pustakawan/kategori/${kat.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.kategoris = this.kategoris.filter(k => k.id !== kat.id);
                        this.selectedIds = this.selectedIds.filter(i => i !== Number(kat.id));
                        this.deleteModalOpen = false;
                        $dispatch('notify-toast', { message: data.message, type: 'success' });
                    } else {
                        $dispatch('notify-toast', { message: data.message || 'Tidak dapat menghapus kategori.', type: 'error' });
                    }
                } else if (this.deleteMode === 'bulk') {
                    const res = await fetch('{{ route('pustakawan.kategori.bulk-destroy') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({ ids: this.selectedIds })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        const deleted = (data.deleted_ids || []).map(Number);
                        this.kategoris = this.kategoris.filter(k => !deleted.includes(Number(k.id)));
                        this.selectedIds = [];
                        this.deleteModalOpen = false;
                        $dispatch('notify-toast', { message: data.message, type: 'success' });
                    } else {
                        $dispatch('notify-toast', { message: data.message || 'Gagal menghapus kategori.', type: 'error' });
                    }
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.deletingKategori = false;
            }
        }
    }"
    class="space-y-5 sm:space-y-6"
>

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] sm:text-xs font-bold text-slate-500 block">Total Kategori</span>
            <span class="text-xl sm:text-3xl font-black text-emerald-600 mt-0.5 block" x-text="kategoris.length"></span>
        </div>
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] sm:text-xs font-bold text-slate-500 block">Total Judul Buku</span>
            <span class="text-xl sm:text-3xl font-black text-slate-900 mt-0.5 block" x-text="metrics.total_judul"></span>
        </div>
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] sm:text-xs font-bold text-slate-500 block">Total Stok Buku</span>
            <span class="text-xl sm:text-3xl font-black text-blue-600 mt-0.5 block" x-text="metrics.total_stok"></span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- Form Tambah Kategori (4 Cols) -->
        <section class="lg:col-span-4 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
            <div>
                <h2 class="text-sm sm:text-base font-extrabold text-slate-900">
                    Tambah Kategori Buku Baru
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Tambahkan kategori baru untuk mengelompokkan koleksi buku di katalog PINJAM BU-PUS.
                </p>
            </div>

            <form @submit.prevent="createKategori()" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kategori *</label>
                    <input 
                        type="text"
                        x-model="addKategoriName"
                        required
                        placeholder="Contoh: Modul Kejuruan SMK"
                        class="w-full rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm font-semibold outline-none focus:border-emerald-600 focus:bg-white"
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Singkat</label>
                    <textarea 
                        rows="3"
                        x-model="addKategoriDesc"
                        placeholder="Keterangan koleksi buku pada kategori ini..."
                        class="w-full rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm font-semibold outline-none focus:border-emerald-600 focus:bg-white resize-none"
                    ></textarea>
                </div>
                <button 
                    type="submit"
                    :disabled="savingKategori"
                    class="w-full min-h-[44px] py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs sm:text-sm transition-colors flex items-center justify-center gap-1.5 shadow-sm"
                >
                    <i class="ph-bold ph-plus-circle text-base"></i>
                    <span x-text="savingKategori ? 'Menyimpan...' : 'Simpan Kategori'"></span>
                </button>
            </form>
        </section>

        <!-- Daftar Kategori (8 Cols) -->
        <section class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900">
                        Daftar Kategori Koleksi (<span x-text="filteredKategoris.length"></span>)
                    </h2>
                    <p class="text-xs text-slate-500">Hanya kategori yang belum memiliki buku (0 judul) yang dapat dihapus.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <template x-if="deletableFilteredIds.length > 0">
                        <label class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700 cursor-pointer select-none">
                            <input 
                                type="checkbox" 
                                :checked="isAllDeletableSelected"
                                @change="toggleSelectAllDeletable()"
                                class="w-3.5 h-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                            >
                            <span>Pilih Kosong</span>
                        </label>
                    </template>

                    <template x-if="selectedIds.length > 0">
                        <button 
                            type="button"
                            @click="openBulkDelete()"
                            class="px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-xs transition-all"
                        >
                            <i class="ph-bold ph-trash"></i>
                            <span>Hapus (<span x-text="selectedIds.length"></span>)</span>
                        </button>
                    </template>

                    <div class="relative">
                        <i class="ph ph-magnifying-glass text-slate-400 text-sm absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input 
                            type="text"
                            x-model="searchKat"
                            placeholder="Cari kategori..."
                            class="w-full sm:w-48 pl-8 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold outline-none focus:border-emerald-500"
                        >
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <template x-for="kat in filteredKategoris" :key="kat.id">
                    <div 
                        class="p-4 rounded-2xl border transition-colors flex items-start justify-between gap-3"
                        :class="isSelected(kat.id) ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200/80 bg-slate-50/60'"
                    >
                        <div class="pt-0.5 shrink-0" x-show="Number(kat.bukus_count) === 0">
                            <input 
                                type="checkbox" 
                                :checked="isSelected(kat.id)"
                                @change="toggleSelect(kat.id)"
                                class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                            >
                        </div>

                        <div class="min-w-0 space-y-1.5 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-extrabold text-xs sm:text-sm text-slate-900 truncate" x-text="kat.nama_kategori"></span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-extrabold shrink-0">
                                    <span x-text="kat.bukus_count"></span> judul
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 line-clamp-2" x-text="kat.deskripsi || 'Koleksi buku perpustakaan sekolah'"></p>
                            <div class="flex items-center gap-3 text-[11px] font-bold text-slate-500 pt-0.5">
                                <span>Stok: <strong class="text-slate-800" x-text="kat.total_stok || 0"></strong></span>
                                <span>Dipinjam: <strong class="text-blue-600" x-text="kat.total_dipinjam || 0"></strong></span>
                            </div>
                        </div>

                        <button 
                            type="button"
                            @click="openSingleDelete(kat)"
                            x-show="Number(kat.bukus_count) === 0"
                            title="Hapus kategori kosong"
                            class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:border-rose-200 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center shrink-0 transition-colors"
                        >
                            <i class="ph-bold ph-trash text-xs"></i>
                        </button>
                    </div>
                </template>
            </div>
        </section>

    </div>

    <!-- Modal Pop-up Konfirmasi Hapus Kategori -->
    <div 
        x-show="deleteModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 !mt-0"
    >
        <div 
            @click.away="deleteModalOpen = false"
            class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full overflow-hidden p-5 sm:p-6 text-center space-y-4"
        >
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center mx-auto text-2xl">
                <i class="ph-bold ph-trash"></i>
            </div>

            <div class="space-y-1.5">
                <h3 class="text-base sm:text-lg font-extrabold text-slate-900" x-text="deleteMode === 'bulk' ? 'Hapus ' + selectedIds.length + ' Kategori Sekaligus?' : 'Hapus Kategori Buku?'"></h3>
                <template x-if="deleteMode === 'single' && deleteTargetKat">
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                        Apakah Anda yakin ingin menghapus kategori <strong class="text-slate-800" x-text="'&quot;' + deleteTargetKat.nama_kategori + '&quot;'"></strong>?
                    </p>
                </template>
                <template x-if="deleteMode === 'bulk'">
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                        Apakah Anda yakin ingin menghapus <strong class="text-rose-600" x-text="selectedIds.length + ' kategori kosong yang dipilih'"></strong>?
                    </p>
                </template>
            </div>

            <div class="pt-2 flex items-center gap-2.5">
                <button 
                    type="button" 
                    @click="deleteModalOpen = false"
                    class="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm transition-colors"
                >
                    Batal
                </button>
                <button 
                    type="button"
                    @click="confirmDeleteKategori()"
                    :disabled="deletingKategori"
                    class="flex-1 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-rose-500/20 transition-all inline-flex items-center justify-center gap-1.5"
                >
                    <i class="ph-bold ph-trash text-sm"></i>
                    <span x-text="deletingKategori ? 'Menghapus...' : 'Ya, Hapus'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

