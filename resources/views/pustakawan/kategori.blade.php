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
        async deleteKategori(kat) {
            if (!confirm(`Hapus kategori '${kat.nama_kategori}'?`)) return;
            try {
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
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Tidak dapat menghapus kategori.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
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
                <div class="relative">
                    <i class="ph ph-magnifying-glass text-slate-400 text-sm absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input 
                        type="text"
                        x-model="searchKat"
                        placeholder="Cari kategori..."
                        class="w-full sm:w-52 pl-8 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold outline-none focus:border-emerald-500"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <template x-for="kat in filteredKategoris" :key="kat.id">
                    <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/60 flex items-start justify-between gap-3">
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
                            @click="deleteKategori(kat)"
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

</div>
@endsection

