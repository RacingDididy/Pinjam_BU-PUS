@extends('pustakawan.layouts.pustakawan')

@section('title', 'Edit Buku - Panel Pustakawan')
@section('page-title', 'Edit Data Buku')

@php
    $initialDate = old('tanggal_terbit', $buku->tanggal_terbit_input);
    try {
        $parsedDate = \Carbon\Carbon::parse($initialDate);
    } catch (\Throwable) {
        $parsedDate = now();
    }
    $kategoriListJson = $kategoris->map(fn ($k) => [
        'id' => $k->id,
        'nama_kategori' => $k->nama_kategori,
    ])->values();
@endphp

@section('content')
<div 
    class="w-full space-y-4 sm:space-y-5"
    x-data="{ 
        bookTitle: @js(old('judul', $buku->judul)),
        bookAuthor: @js(old('pengarang', $buku->pengarang)),
        bookPublisher: @js(old('penerbit', $buku->penerbit)),
        pubDay: {{ (int) $parsedDate->format('j') }},
        pubMonth: {{ (int) $parsedDate->format('n') }},
        pubYear: {{ (int) old('tahun_terbit', $parsedDate->format('Y')) }},
        bookDate: '{{ $parsedDate->format('Y-m-d') }}',
        bookIsbn: @js(old('isbn', $buku->isbn)),
        bookStock: {{ (int) old('stok', $buku->stok) }},
        selectedKategoriId: @js(old('kategori_id', $buku->kategori_id)),
        kategoriSearch: '',
        kategoriList: @js($kategoriListJson),
        showAddKategori: false,
        newKategoriName: '',
        addingKategori: false,
        kategoriError: '',
        imageMode: '{{ str_starts_with((string) $buku->cover_image, 'http') ? 'url' : 'upload' }}', 
        useExtraImages: {{ ($buku->side_image_raw || $buku->back_image_raw) ? 'true' : 'false' }},
        previewFront: '{{ old('cover_image', $buku->cover_url) }}',
        frontUrlInput: '{{ old('cover_image', str_starts_with((string) $buku->cover_image, 'http') ? $buku->cover_image : '') }}',
        frontName: '',
        previewSide: '{{ old('side_image', $buku->side_image_url ?? '') }}',
        sideUrlInput: '{{ old('side_image', str_starts_with((string) $buku->side_image_raw, 'http') ? $buku->side_image_raw : '') }}',
        sideName: '',
        previewBack: '{{ old('back_image', $buku->back_image_url ?? '') }}',
        backUrlInput: '{{ old('back_image', str_starts_with((string) $buku->back_image_raw, 'http') ? $buku->back_image_raw : '') }}',
        backName: '',
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
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
    @if ($errors->any())
        <div class="p-3.5 sm:p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form 
        action="{{ route('pustakawan.buku.update', $buku->id) }}" 
        method="POST" 
        enctype="multipart/form-data"
        class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-start"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="use_extra_images" :value="useExtraImages ? '1' : '0'">
        <input type="hidden" name="tahun_terbit" :value="pubYear">
        <input type="hidden" name="tgl_hari" :value="pubDay">
        <input type="hidden" name="tgl_bulan" :value="pubMonth">
        <input type="hidden" name="tgl_tahun" :value="pubYear">
        <input type="hidden" name="kategori_id" :value="selectedKategoriId">

        <!-- Kolom Kiri: Form Informasi Buku -->
        <div class="lg:col-span-7 xl:col-span-8 bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-6 shadow-sm space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
                <div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900">Perbarui Informasi Buku</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ubah data detail buku beserta tanggal terbit dan kategori koleksi.</p>
                </div>
                <a href="{{ route('pustakawan.buku.index') }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-xs font-bold text-slate-700 flex items-center gap-1.5 transition-colors">
                    <i class="ph-bold ph-arrow-left"></i>
                    <span>Kembali ke Daftar</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Judul -->
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Judul Buku</label>
                    <input type="text" name="judul" x-model="bookTitle" required value="{{ old('judul', $buku->judul) }}" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>

                <!-- Pengarang -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pengarang</label>
                    <input type="text" name="pengarang" x-model="bookAuthor" required value="{{ old('pengarang', $buku->pengarang) }}" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>

                <!-- Penerbit -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Penerbit</label>
                    <input type="text" name="penerbit" x-model="bookPublisher" required value="{{ old('penerbit', $buku->penerbit) }}" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>

                <!-- Tanggal Terbit Lengkap (Tanggal, Bulan, Tahun) -->
                <div class="sm:col-span-2 p-3.5 rounded-2xl bg-slate-50/80 border border-slate-200/90 space-y-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="text-[11px] font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ph-bold ph-calendar-blank text-blue-600 text-sm"></i>
                            <span>Tanggal Terbit (Tanggal, Bulan, Tahun)</span>
                        </label>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-[11px] font-bold">
                            <i class="ph-fill ph-check-circle text-xs"></i>
                            <span x-text="formattedPubDate"></span>
                        </span>
                    </div>

                    <div class="grid grid-cols-12 gap-2 sm:gap-2.5 items-center">
                        <div class="col-span-3 sm:col-span-2">
                            <span class="block text-[10px] font-bold text-slate-500 mb-1">Tanggal</span>
                            <input
                                type="number"
                                min="1"
                                max="31"
                                x-model.number="pubDay"
                                @input="syncDateFromParts()"
                                placeholder="Tgl"
                                class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs sm:text-sm font-bold text-center text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                            >
                        </div>

                        <div class="col-span-5 sm:col-span-4">
                            <span class="block text-[10px] font-bold text-slate-500 mb-1">Bulan</span>
                            <select
                                x-model.number="pubMonth"
                                @change="syncDateFromParts()"
                                class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs sm:text-sm font-bold text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                            >
                                <template x-for="(mName, idx) in monthNames" :key="idx">
                                    <option :value="idx + 1" :selected="pubMonth === (idx + 1)" x-text="(idx + 1) + ' - ' + mName"></option>
                                </template>
                            </select>
                        </div>

                        <div class="col-span-4 sm:col-span-3">
                            <span class="block text-[10px] font-bold text-slate-500 mb-1">Tahun</span>
                            <input
                                type="number"
                                min="1900"
                                max="{{ date('Y') + 1 }}"
                                x-model.number="pubYear"
                                @input="syncDateFromParts()"
                                placeholder="Tahun"
                                class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs sm:text-sm font-bold text-center text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                            >
                        </div>

                        <div class="col-span-12 sm:col-span-3">
                            <span class="block text-[10px] font-bold text-slate-500 mb-1">Kalender Cepat</span>
                            <input
                                type="date"
                                name="tanggal_terbit"
                                x-model="bookDate"
                                @change="syncPartsFromDate()"
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-blue-500 outline-none"
                            >
                        </div>
                    </div>
                </div>

                <!-- ISBN -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">ISBN</label>
                    <input type="text" name="isbn" x-model="bookIsbn" required value="{{ old('isbn', $buku->isbn) }}" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none font-mono">
                </div>

                <!-- Jumlah Stok (Dapat Diketik Langsung + Stepper & Quick Chips) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jumlah Stok</label>
                    <div class="flex items-center gap-1.5">
                        <div class="flex-1 flex items-center rounded-xl border border-slate-200 bg-white p-0.5 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                            <button
                                type="button"
                                @click="bookStock = Math.max(0, (parseInt(bookStock, 10) || 0) - 1)"
                                class="w-9 h-9 rounded-lg text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors shrink-0"
                                aria-label="Kurangi stok"
                            >
                                <i class="ph-bold ph-minus text-xs"></i>
                            </button>
                            <input
                                type="number"
                                name="stok"
                                x-model.number="bookStock"
                                required
                                min="0"
                                placeholder="Ketik jumlah..."
                                class="w-full text-center font-extrabold text-xs sm:text-sm text-slate-900 bg-transparent outline-none px-2 py-1.5"
                            >
                            <button
                                type="button"
                                @click="bookStock = (parseInt(bookStock, 10) || 0) + 1"
                                class="w-9 h-9 rounded-lg text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors shrink-0"
                                aria-label="Tambah stok"
                            >
                                <i class="ph-bold ph-plus text-xs"></i>
                            </button>
                        </div>
                        <button type="button" @click="bookStock = (parseInt(bookStock, 10) || 0) + 5" class="px-2 py-2 rounded-xl bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-[11px] font-extrabold text-slate-600 transition-colors shrink-0">+5</button>
                        <button type="button" @click="bookStock = (parseInt(bookStock, 10) || 0) + 10" class="px-2 py-2 rounded-xl bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-[11px] font-extrabold text-slate-600 transition-colors shrink-0">+10</button>
                    </div>
                </div>

                <!-- Pilihan Kategori Visual Interaktif (Menggantikan Dropdown) -->
                <div class="sm:col-span-2 p-3.5 sm:p-4 rounded-2xl bg-slate-50/90 border border-slate-200/90 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <label class="text-[11px] font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="ph-bold ph-squares-four text-blue-600 text-sm"></i>
                                <span>Pilih Kategori Koleksi</span>
                            </label>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Klik kartu kategori di bawah (Terpilih: <strong class="text-blue-600" x-text="selectedKategoriName"></strong>)
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <i class="ph ph-magnifying-glass text-slate-400 text-xs absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                                <input
                                    type="text"
                                    x-model="kategoriSearch"
                                    placeholder="Cari kategori..."
                                    class="w-36 sm:w-44 rounded-xl border border-slate-200 bg-white pl-7 pr-2.5 py-1.5 text-xs text-slate-800 outline-none focus:border-blue-500"
                                >
                            </div>
                            <button
                                type="button"
                                @click="showAddKategori = !showAddKategori; kategoriError = ''"
                                class="px-2.5 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80 text-xs font-bold inline-flex items-center gap-1 transition-colors"
                            >
                                <i class="ph-bold" :class="showAddKategori ? 'ph-x' : 'ph-plus'"></i>
                                <span x-text="showAddKategori ? 'Tutup' : 'Kategori Baru'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Form Tambah Kategori Cepat Inline -->
                    <div x-show="showAddKategori" x-transition class="p-3 rounded-xl bg-white border border-blue-200 space-y-2">
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                x-model="newKategoriName"
                                @keydown.enter.prevent="createQuickKategori()"
                                placeholder="Ketik nama kategori baru (misal: Teknik Mesin)..."
                                class="flex-1 rounded-xl border border-slate-200 px-3 py-1.5 text-xs text-slate-900 outline-none focus:border-blue-500"
                            >
                            <button
                                type="button"
                                @click="createQuickKategori()"
                                :disabled="addingKategori || !newKategoriName.trim()"
                                class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold disabled:opacity-50 inline-flex items-center gap-1"
                            >
                                <i class="ph-bold" :class="addingKategori ? 'ph-spinner animate-spin' : 'ph-check'"></i>
                                <span>Simpan</span>
                            </button>
                        </div>
                        <p x-show="kategoriError" class="text-[11px] font-semibold text-rose-600" x-text="kategoriError"></p>
                    </div>

                    <!-- Grid Kartu Kategori -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2 max-h-56 overflow-y-auto pr-1">
                        <template x-for="kat in filteredKategoris" :key="kat.id">
                            <button
                                type="button"
                                @click="selectedKategoriId = kat.id"
                                :class="String(selectedKategoriId) === String(kat.id)
                                    ? 'border-blue-600 bg-blue-600 text-white shadow-sm ring-2 ring-blue-500/25'
                                    : 'border-slate-200/90 bg-white hover:border-blue-300 hover:bg-blue-50/40 text-slate-700'"
                                class="group relative flex items-center gap-2.5 p-2.5 rounded-xl border text-left transition-all"
                            >
                                <div
                                    :class="String(selectedKategoriId) === String(kat.id)
                                        ? 'bg-white/20 text-white'
                                        : 'bg-blue-50 text-blue-600 group-hover:bg-blue-100'"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                >
                                    <i class="ph-bold text-base" :class="categoryIcon(kat.nama_kategori)"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold leading-snug line-clamp-2" x-text="kat.nama_kategori"></p>
                                </div>
                                <i
                                    x-show="String(selectedKategoriId) === String(kat.id)"
                                    class="ph-fill ph-check-circle text-sm text-white shrink-0"
                                ></i>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Sinopsis -->
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Sinopsis Buku</label>
                    <textarea name="sinopsis" rows="5" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none leading-relaxed">{{ old('sinopsis', $buku->sinopsis) }}</textarea>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2.5">
                <a href="{{ route('pustakawan.buku.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs sm:text-sm hover:bg-slate-50 transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-base"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>

        <!-- Kolom Kanan: Sampul Buku & Pratinjau Katalog -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-4 sm:space-y-5">
            <!-- Panel Upload Cover & Gambar Buku -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 uppercase tracking-wider">Sampul &amp; Gambar Buku</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Unggah file atau gunakan link URL gambar.</p>
                    </div>

                    <div class="inline-flex p-0.5 bg-slate-100 border border-slate-200 rounded-xl shrink-0">
                        <button 
                            type="button" 
                            @click="imageMode = 'upload'"
                            :class="imageMode === 'upload' ? 'bg-[#2563EB] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                        >
                            <i class="ph-bold ph-upload-simple"></i>
                            <span>Upload</span>
                        </button>
                        <button 
                            type="button" 
                            @click="imageMode = 'url'"
                            :class="imageMode === 'url' ? 'bg-[#2563EB] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                        >
                            <i class="ph-bold ph-link"></i>
                            <span>Link URL</span>
                        </button>
                    </div>
                </div>

                <!-- 1. Sampul Depan -->
                <div class="bg-slate-50 rounded-xl border border-slate-200/80 p-3 flex items-center gap-3">
                    <div class="w-16 h-22 shrink-0 rounded-lg border border-slate-200 bg-white overflow-hidden flex flex-col items-center justify-center">
                        <template x-if="previewFront">
                            <img :src="previewFront" alt="Preview Cover" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!previewFront">
                            <div class="text-center p-1.5 text-slate-400">
                                <i class="ph ph-image text-lg block"></i>
                                <span class="text-[9px] font-medium block">Depan</span>
                            </div>
                        </template>
                    </div>

                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800">1. Sampul Depan</span>
                            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold">Utama</span>
                        </div>

                        <div x-show="imageMode === 'upload'">
                            <label class="flex flex-col items-center justify-center w-full py-2.5 px-2.5 border border-dashed border-slate-300 rounded-xl cursor-pointer bg-white hover:bg-blue-50/40 hover:border-blue-400 transition-all text-center">
                                <span class="text-[11px] font-semibold text-slate-700 inline-flex items-center gap-1">
                                    <i class="ph-bold ph-cloud-arrow-up text-blue-600 text-sm"></i>
                                    <span class="truncate" x-text="frontName || 'Ganti File Sampul'"></span>
                                </span>
                                <span class="text-[10px] text-slate-400" x-show="!frontName">PNG, JPG, WEBP (Maks. 3MB)</span>
                                <input type="file" name="cover_file" accept="image/*" class="hidden" @change="handleFile('front', $event)">
                            </label>
                        </div>

                        <div x-show="imageMode === 'url'">
                            <input 
                                type="url" 
                                name="cover_image" 
                                x-model="frontUrlInput"
                                @input="if (frontUrlInput) previewFront = frontUrlInput"
                                placeholder="https://... URL sampul depan" 
                                class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-800 outline-none focus:border-blue-500"
                            >
                        </div>
                    </div>
                </div>

                <!-- Opsi ON/OFF Gambar Tambahan -->
                <div class="pt-1 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors" :class="useExtraImages ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500'">
                            <i class="ph-bold ph-images text-sm"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-800 truncate">Gambar Samping &amp; Belakang</p>
                    </div>

                    <button 
                        type="button" 
                        @click="useExtraImages = !useExtraImages"
                        :class="useExtraImages ? 'bg-[#2563EB]' : 'bg-slate-300'"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        role="switch"
                        :aria-checked="useExtraImages"
                    >
                        <span 
                            :class="useExtraImages ? 'translate-x-5' : 'translate-x-0'"
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                        ></span>
                    </button>
                </div>

                <!-- 2 & 3: Samping & Belakang -->
                <div x-show="useExtraImages" x-transition class="space-y-2.5">
                    <!-- 2. Sampul Samping -->
                    <div class="bg-slate-50 rounded-xl border border-slate-200/80 p-2.5 flex items-center gap-3">
                        <div class="w-12 h-16 shrink-0 rounded-lg border border-slate-200 bg-white overflow-hidden flex flex-col items-center justify-center">
                            <template x-if="previewSide">
                                <img :src="previewSide" alt="Preview Samping" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!previewSide">
                                <span class="text-[9px] font-medium text-slate-400">Samping</span>
                            </template>
                        </div>

                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-800">2. Sampul Samping</span>
                                <span class="text-[10px] text-slate-400">Opsional</span>
                            </div>

                            <div x-show="imageMode === 'upload'">
                                <label class="flex items-center justify-center gap-1.5 w-full py-1.5 px-2.5 border border-dashed border-slate-300 rounded-lg cursor-pointer bg-white hover:border-blue-400 transition-all text-center">
                                    <i class="ph-bold ph-upload-simple text-blue-600 text-xs"></i>
                                    <span class="text-[11px] font-semibold text-slate-700 truncate" x-text="sideName || 'Pilih Gambar'"></span>
                                    <input type="file" name="side_file" accept="image/*" class="hidden" @change="handleFile('side', $event)">
                                </label>
                            </div>

                            <div x-show="imageMode === 'url'">
                                <input 
                                    type="url" 
                                    name="side_image" 
                                    x-model="sideUrlInput"
                                    @input="if (sideUrlInput) previewSide = sideUrlInput"
                                    placeholder="https://... URL samping" 
                                    class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs outline-none focus:border-blue-500"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 3. Sampul Belakang -->
                    <div class="bg-slate-50 rounded-xl border border-slate-200/80 p-2.5 flex items-center gap-3">
                        <div class="w-12 h-16 shrink-0 rounded-lg border border-slate-200 bg-white overflow-hidden flex flex-col items-center justify-center">
                            <template x-if="previewBack">
                                <img :src="previewBack" alt="Preview Belakang" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!previewBack">
                                <span class="text-[9px] font-medium text-slate-400">Belakang</span>
                            </template>
                        </div>

                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-800">3. Sampul Belakang</span>
                                <span class="text-[10px] text-slate-400">Opsional</span>
                            </div>

                            <div x-show="imageMode === 'upload'">
                                <label class="flex items-center justify-center gap-1.5 w-full py-1.5 px-2.5 border border-dashed border-slate-300 rounded-lg cursor-pointer bg-white hover:border-blue-400 transition-all text-center">
                                    <i class="ph-bold ph-upload-simple text-blue-600 text-xs"></i>
                                    <span class="text-[11px] font-semibold text-slate-700 truncate" x-text="backName || 'Pilih Gambar'"></span>
                                    <input type="file" name="back_file" accept="image/*" class="hidden" @change="handleFile('back', $event)">
                                </label>
                            </div>

                            <div x-show="imageMode === 'url'">
                                <input 
                                    type="url" 
                                    name="back_image" 
                                    x-model="backUrlInput"
                                    @input="if (backUrlInput) previewBack = backUrlInput"
                                    placeholder="https://... URL belakang" 
                                    class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs outline-none focus:border-blue-500"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Pratinjau Kartu Buku di Katalog -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <span class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Pratinjau Katalog</span>
                    <a href="{{ route('buku.show', $buku->id) }}" target="_blank" class="text-[11px] font-bold text-blue-600 hover:underline inline-flex items-center gap-1">
                        <span>Lihat di Web</span>
                        <i class="ph-bold ph-arrow-square-out"></i>
                    </a>
                </div>

                <div class="flex items-start gap-3.5 p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                    <div class="w-20 h-28 rounded-xl bg-slate-200 border border-slate-300 overflow-hidden shrink-0 shadow-xs">
                        <template x-if="previewFront">
                            <img :src="previewFront" alt="Cover Preview" class="w-full h-full object-cover">
                        </template>
                    </div>
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-1">
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                                :class="Number(bookStock) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="Number(bookStock) > 0 ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                <span x-text="Number(bookStock) > 0 ? ('Tersedia ' + bookStock + ' Eks') : 'Stok Habis'"></span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold" x-text="selectedKategoriName"></span>
                        </div>
                        <h5 class="text-xs sm:text-sm font-extrabold text-slate-900 line-clamp-2 leading-snug" x-text="bookTitle || 'Judul Buku'"></h5>
                        <p class="text-[11px] font-semibold text-slate-500 truncate" x-text="bookAuthor || 'Nama Pengarang'"></p>
                        <p class="text-[10px] text-slate-400 truncate">
                            <span x-text="bookPublisher || 'Penerbit'"></span> &bull; <span x-text="formattedPubDate"></span>
                        </p>
                        <p class="text-[10px] font-mono text-slate-400 truncate" x-text="'ISBN: ' + (bookIsbn || '-')"></p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
