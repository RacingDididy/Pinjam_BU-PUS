@extends('pustakawan.layouts.pustakawan')

@section('title', 'Tambah Buku Baru - Panel Pustakawan')
@section('page-title', 'Tambah Buku Baru')

@section('content')
<div 
    class="w-full space-y-4 sm:space-y-5"
    x-data="{ 
        bookTitle: @js(old('judul', '')),
        bookAuthor: @js(old('pengarang', '')),
        bookPublisher: @js(old('penerbit', '')),
        bookYear: @js(old('tahun_terbit', date('Y'))),
        bookIsbn: @js(old('isbn', '')),
        bookStock: @js(old('stok', 5)),
        imageMode: 'upload', 
        useExtraImages: {{ (old('side_image') || old('back_image')) ? 'true' : 'false' }},
        previewFront: '{{ old('cover_image', '') }}',
        frontName: '',
        previewSide: '{{ old('side_image', '') }}',
        sideName: '',
        previewBack: '{{ old('back_image', '') }}',
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
        action="{{ route('pustakawan.buku.store') }}" 
        method="POST" 
        enctype="multipart/form-data" 
        class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-start"
    >
        @csrf
        <input type="hidden" name="use_extra_images" :value="useExtraImages ? '1' : '0'">

        <!-- Kolom Kiri: Form Informasi Buku -->
        <div class="lg:col-span-7 xl:col-span-8 bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-6 shadow-sm space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
                <div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900">Form Buku Baru</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Isi detail buku untuk ditambahkan ke katalog perpustakaan.</p>
                </div>
                <a href="{{ route('pustakawan.buku.index') }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-xs font-bold text-slate-700 flex items-center gap-1.5 transition-colors">
                    <i class="ph-bold ph-arrow-left"></i>
                    <span>Kembali ke Daftar</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Judul Buku *</label>
                    <input type="text" name="judul" x-model="bookTitle" required value="{{ old('judul') }}" placeholder="Contoh: Laskar Pelangi" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pengarang *</label>
                    <input type="text" name="pengarang" x-model="bookAuthor" required value="{{ old('pengarang') }}" placeholder="Contoh: Andrea Hirata" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Penerbit *</label>
                    <input type="text" name="penerbit" x-model="bookPublisher" required value="{{ old('penerbit') }}" placeholder="Contoh: Bentang Pustaka" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tahun Terbit *</label>
                    <input type="number" name="tahun_terbit" x-model="bookYear" required min="1900" max="{{ date('Y') + 1 }}" value="{{ old('tahun_terbit', date('Y')) }}" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">ISBN *</label>
                    <input type="text" name="isbn" x-model="bookIsbn" required value="{{ old('isbn') }}" placeholder="Contoh: 9786022916964" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none font-mono">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori *</label>
                    <select name="kategori_id" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none bg-white">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($kategoris as $kategori)
                            <option value="{{ $kategori->id }}" {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jumlah Stok *</label>
                    <input type="number" name="stok" x-model="bookStock" required min="0" value="{{ old('stok', 5) }}" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Sinopsis Buku</label>
                    <textarea name="sinopsis" rows="5" placeholder="Tuliskan sinopsis singkat buku..." class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none leading-relaxed">{{ old('sinopsis') }}</textarea>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2.5">
                <a href="{{ route('pustakawan.buku.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs sm:text-sm hover:bg-slate-50 transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-base"></i>
                    <span>Simpan Buku</span>
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
                            <img :src="previewFront" alt="Preview Sampul Depan" class="w-full h-full object-cover">
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
                                    <span class="truncate" x-text="frontName || 'Pilih Gambar Sampul'"></span>
                                </span>
                                <span class="text-[10px] text-slate-400" x-show="!frontName">PNG, JPG, WEBP (Maks. 3MB)</span>
                                <input type="file" name="cover_file" accept="image/*" class="hidden" @change="handleFile('front', $event)">
                            </label>
                        </div>

                        <div x-show="imageMode === 'url'">
                            <input 
                                type="url" 
                                name="cover_image" 
                                x-model="previewFront"
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
                                    x-model="previewSide"
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
                                    x-model="previewBack"
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
                    <span class="text-[10px] font-bold text-slate-400">Live Preview</span>
                </div>

                <div class="flex items-start gap-3.5 p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                    <div class="w-20 h-28 rounded-xl bg-slate-200 border border-slate-300 overflow-hidden shrink-0 flex items-center justify-center text-slate-400 shadow-xs">
                        <template x-if="previewFront">
                            <img :src="previewFront" alt="Cover Preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!previewFront">
                            <i class="ph ph-book-open text-2xl"></i>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <span 
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                            :class="Number(bookStock) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="Number(bookStock) > 0 ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                            <span x-text="Number(bookStock) > 0 ? ('Tersedia ' + bookStock + ' Eks') : 'Stok Habis'"></span>
                        </span>
                        <h5 class="text-xs sm:text-sm font-extrabold text-slate-900 line-clamp-2 leading-snug" x-text="bookTitle || 'Judul Buku Baru'"></h5>
                        <p class="text-[11px] font-semibold text-slate-500 truncate" x-text="bookAuthor || 'Nama Pengarang'"></p>
                        <p class="text-[10px] text-slate-400 truncate">
                            <span x-text="bookPublisher || 'Penerbit'"></span> &bull; <span x-text="bookYear || '-'"></span>
                        </p>
                        <p class="text-[10px] font-mono text-slate-400 truncate" x-text="'ISBN: ' + (bookIsbn || '-')"></p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
