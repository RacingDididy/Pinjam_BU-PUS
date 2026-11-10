@extends('pustakawan.layouts.pustakawan')

@section('title', 'Kelola Katalog Buku - Panel Pustakawan')
@section('page-title', 'Kelola Katalog Buku')

@section('content')
<div class="w-full" x-data="{ openCreateModal: false }">

    <!-- Kontainer tabel buku -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Pencarian dan tombol tambah -->
        <div class="p-3.5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            
            <!-- Search Input -->
            <form action="{{ route('pustakawan.buku.index') }}" method="GET" class="w-full sm:w-80 relative">
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

            <!-- Tombol Tambah Buku Baru -->
            <button 
                @click="openCreateModal = true" 
                class="w-full sm:w-auto bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs sm:text-sm px-4 py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-sm shadow-blue-500/20 transition-all active:scale-95"
            >
                <i class="ph-bold ph-plus-circle text-base sm:text-lg"></i>
                <span>Tambah Buku Baru</span>
            </button>

        </div>

        <!-- Daftar Kartu Buku Ringkas untuk Mobile -->
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($bukus as $buku)
                <div class="p-3.5 flex items-start gap-3">
                    <div class="w-14 h-20 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-200/70">
                        <img src="{{ $buku->cover_url }}" alt="{{ $buku->judul }}" class="w-full h-full object-cover">
                    </div>

                    <div class="flex-1 min-w-0 space-y-1">
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="font-bold text-xs text-slate-900 line-clamp-2 leading-snug">
                                {{ $buku->judul }}
                            </h4>
                            <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-bold {{ $buku->stok > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                Stok: {{ $buku->stok }}
                            </span>
                        </div>

                        <p class="text-[11px] text-slate-500 truncate">
                            {{ $buku->pengarang }} &bull; {{ $buku->tahun_terbit }}
                        </p>

                        <div class="pt-1 flex items-center justify-between gap-2">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-600 border border-blue-100 truncate max-w-[140px]">
                                {{ $buku->kategori->nama_kategori ?? 'Umum' }}
                            </span>

                            <div class="inline-flex items-center gap-1.5">
                                <a 
                                    href="{{ route('pustakawan.buku.edit', $buku) }}" 
                                    class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:text-blue-600 text-[11px] font-bold inline-flex items-center gap-1"
                                >
                                    <i class="ph-bold ph-pencil-simple text-xs"></i>
                                    <span>Edit</span>
                                </a>

                                <form action="{{ route('pustakawan.buku.destroy', $buku) }}" method="POST" onsubmit="return confirm('Hapus buku {{ addslashes($buku->judul) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        type="submit" 
                                        class="w-7 h-7 rounded-lg border border-rose-200 text-rose-600 flex items-center justify-center"
                                        title="Hapus Buku"
                                    >
                                        <i class="ph-bold ph-trash text-xs"></i>
                                    </button>
                                </form>
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
                        <th class="py-4 px-6">Buku</th>
                        <th class="py-4 px-6">Kategori</th>
                        <th class="py-4 px-6">Stok</th>
                        <th class="py-4 px-6">ISBN</th>
                        <th class="py-4 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($bukus as $buku)
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            
                            <!-- Kolom Buku: Thumbnail + Judul + Penulis • Tahun -->
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
                                            {{ $buku->pengarang }} &bull; {{ $buku->tahun_terbit }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom Kategori: Badge Lembut Biru/Ungu -->
                            <td class="py-4 px-6">
                                <span class="inline-block px-3 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-100">
                                    {{ $buku->kategori->nama_kategori ?? 'Umum' }}
                                </span>
                            </td>

                            <!-- Kolom Stok: Angka Hijau + Teks Tersedia -->
                            <td class="py-4 px-6">
                                <div>
                                    <span class="font-bold text-emerald-600 text-base leading-none block">
                                        {{ $buku->stok }}
                                    </span>
                                    <span class="text-xs text-slate-500 font-medium">Tersedia</span>
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

                                    <form action="{{ route('pustakawan.buku.destroy', $buku) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku {{ addslashes($buku->judul) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Hapus Buku"
                                        >
                                            <i class="ph-bold ph-trash text-base"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500">
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
                {{-- Tombol Prev --}}
                @if ($bukus->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-300 cursor-not-allowed">Prev</span>
                @else
                    <a href="{{ $bukus->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-blue-600 transition-colors">Prev</a>
                @endif

                {{-- Nomor Halaman Aktif & Terdekat --}}
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

                {{-- Tombol Next --}}
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
            class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden transform transition-all"
        >
            <!-- Header Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 shrink-0 bg-white">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                        <i class="ph-bold ph-plus-circle"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-tight">Tambah Buku Baru</h3>
                        <p class="text-xs text-slate-500">Lengkapi informasi buku untuk ditambahkan ke katalog.</p>
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

                        <!-- Tahun Terbit -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tahun Terbit <span class="text-rose-500">*</span></label>
                            <input type="number" name="tahun_terbit" required min="1900" max="{{ date('Y') + 1 }}" value="{{ date('Y') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
                        </div>

                        <!-- ISBN -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">ISBN <span class="text-rose-500">*</span></label>
                            <input type="text" name="isbn" required placeholder="Contoh: 9786022916964" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
                        </div>

                        <!-- Kategori -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori <span class="text-rose-500">*</span></label>
                            <select name="kategori_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($kategoris as $kategori)
                                    <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Stok -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jumlah Stok <span class="text-rose-500">*</span></label>
                            <input type="number" name="stok" required min="0" value="5" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
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
                                        <p class="text-[10px] text-slate-400">Masukkan URL gambar langsung (contoh: https://.../cover.jpg).</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Opsi ON/OFF Gambar Samping & Belakang -->
                            <div class="pt-1 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors" :class="useExtraImages ? 'bg-blue-100 text-blue-600' : 'bg-slate-200/70 text-slate-500'">
                                        <i class="ph-bold ph-images text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800">Tambahkan Gambar Samping & Belakang</p>
                                    </div>
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

                            <!-- 2. Samping & 3. Belakang (Muncul saat ON) -->
                            <div x-show="useExtraImages" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- 2. Samping -->
                                <div class="bg-white rounded-xl border border-slate-200 p-3 flex items-center gap-3">
                                    <div class="w-16 h-20 shrink-0 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden flex flex-col items-center justify-center">
                                        <template x-if="previewSide">
                                            <img :src="previewSide" alt="Samping" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!previewSide">
                                            <div class="text-center p-1 text-slate-400">
                                                <i class="ph ph-book-bookmark text-base block"></i>
                                                <span class="text-[9px] font-medium block">Samping</span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0 space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-800">2. Sampul Samping</span>
                                            <span class="text-[10px] text-slate-400 font-medium">Opsional</span>
                                        </div>
                                        <div x-show="imageMode === 'upload'">
                                            <label class="flex items-center justify-center gap-1.5 w-full py-2 px-2.5 border border-dashed border-slate-300 rounded-lg cursor-pointer bg-slate-50 hover:bg-blue-50/40 hover:border-blue-400 transition-all text-center">
                                                <i class="ph-bold ph-upload-simple text-blue-600 text-xs"></i>
                                                <span class="text-[11px] font-semibold text-slate-700 truncate" x-text="sideName || 'Pilih Gambar'"></span>
                                                <input type="file" name="side_file" accept="image/*" class="hidden" @change="handleFile('side', $event)">
                                            </label>
                                        </div>
                                        <div x-show="imageMode === 'url'">
                                            <input type="url" name="side_image" x-model="previewSide" placeholder="https://... URL samping" class="w-full rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white px-2.5 py-1.5 text-xs outline-none focus:border-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Belakang -->
                                <div class="bg-white rounded-xl border border-slate-200 p-3 flex items-center gap-3">
                                    <div class="w-16 h-20 shrink-0 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden flex flex-col items-center justify-center">
                                        <template x-if="previewBack">
                                            <img :src="previewBack" alt="Belakang" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!previewBack">
                                            <div class="text-center p-1 text-slate-400">
                                                <i class="ph ph-book text-base block"></i>
                                                <span class="text-[9px] font-medium block">Belakang</span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0 space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-800">3. Sampul Belakang</span>
                                            <span class="text-[10px] text-slate-400 font-medium">Opsional</span>
                                        </div>
                                        <div x-show="imageMode === 'upload'">
                                            <label class="flex items-center justify-center gap-1.5 w-full py-2 px-2.5 border border-dashed border-slate-300 rounded-lg cursor-pointer bg-slate-50 hover:bg-blue-50/40 hover:border-blue-400 transition-all text-center">
                                                <i class="ph-bold ph-upload-simple text-blue-600 text-xs"></i>
                                                <span class="text-[11px] font-semibold text-slate-700 truncate" x-text="backName || 'Pilih Gambar'"></span>
                                                <input type="file" name="back_file" accept="image/*" class="hidden" @change="handleFile('back', $event)">
                                            </label>
                                        </div>
                                        <div x-show="imageMode === 'url'">
                                            <input type="url" name="back_image" x-model="previewBack" placeholder="https://... URL belakang" class="w-full rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white px-2.5 py-1.5 text-xs outline-none focus:border-blue-500">
                                        </div>
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

</div>
@endsection
