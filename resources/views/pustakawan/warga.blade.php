@extends('pustakawan.layouts.pustakawan')

@section('title', 'Pantau Warga Sekolah / Peminjam')
@section('page-title', 'Pemantauan Data Warga Sekolah (Read-Only)')

@section('content')
<div 
    x-data="{
        students: {{ Js::from($studentsPayload) }},
        metrics: {{ Js::from($reportMetrics) }},
        filterTab: 'all',
        searchStudent: '',
        selectedStudent: null,
        get filteredStudents() {
            return (this.students || []).filter(s => {
                if (this.filterTab === 'complete' && !s.identity_complete) return false;
                if (this.filterTab === 'incomplete' && s.identity_complete) return false;
                if (this.filterTab === 'borrowing' && s.active_loans_count <= 0) return false;

                if (!this.searchStudent.trim()) return true;
                const q = this.searchStudent.toLowerCase();
                return (s.name || '').toLowerCase().includes(q)
                    || (s.nisn || '').toLowerCase().includes(q)
                    || (s.nis || '').toLowerCase().includes(q)
                    || (s.nama_kelas || '').toLowerCase().includes(q)
                    || (s.email || '').toLowerCase().includes(q);
            });
        }
    }"
    class="space-y-5 sm:space-y-6"
>

    <!-- Summary Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4">
        <div class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 block">Total Warga Sekolah</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5 block" x-text="students.length"></span>
        </div>
        <div class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 block">Identitas Lengkap</span>
            <span class="text-xl sm:text-2xl font-black text-emerald-600 mt-0.5 block" x-text="students.filter(s => s.identity_complete).length"></span>
        </div>
        <div class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 block">Belum Lengkap</span>
            <span class="text-xl sm:text-2xl font-black text-amber-600 mt-0.5 block" x-text="students.filter(s => !s.identity_complete).length"></span>
        </div>
        <div class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 block">Sedang Meminjam</span>
            <span class="text-xl sm:text-2xl font-black text-blue-600 mt-0.5 block" x-text="students.filter(s => s.active_loans_count > 0).length"></span>
        </div>
    </div>

    <!-- Main Student Directory Card -->
    <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <button type="button" @click="filterTab = 'all'" class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-colors" :class="filterTab === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    Semua Siswa (<span x-text="students.length"></span>)
                </button>
                <button type="button" @click="filterTab = 'complete'" class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-colors" :class="filterTab === 'complete' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'">
                    Identitas Lengkap (<span x-text="students.filter(s => s.identity_complete).length"></span>)
                </button>
                <button type="button" @click="filterTab = 'incomplete'" class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-colors" :class="filterTab === 'incomplete' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'">
                    Belum Lengkap (<span x-text="students.filter(s => !s.identity_complete).length"></span>)
                </button>
                <button type="button" @click="filterTab = 'borrowing'" class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-colors" :class="filterTab === 'borrowing' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'">
                    Sedang Meminjam (<span x-text="students.filter(s => s.active_loans_count > 0).length"></span>)
                </button>
            </div>

            <div class="relative shrink-0">
                <i class="ph ph-magnifying-glass text-slate-400 text-sm absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input 
                    type="text"
                    x-model="searchStudent"
                    placeholder="Cari nama siswa, NISN, NIS, kelas..."
                    class="w-full lg:w-64 pl-8 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold outline-none focus:border-emerald-500"
                >
            </div>
        </div>

        <div x-show="filteredStudents.length === 0" class="py-12 text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                <i class="ph ph-users"></i>
            </div>
            <p class="text-xs sm:text-sm font-bold text-slate-700">Belum ada data siswa warga sekolah pada filter ini.</p>
        </div>

        <!-- Daftar Kartu Ringkas untuk Mobile -->
        <div class="grid grid-cols-1 gap-3 md:hidden" x-show="filteredStudents.length > 0">
            <template x-for="s in filteredStudents" :key="'mob-stu-' + s.id">
                <div class="rounded-2xl border border-slate-200/80 p-3.5 space-y-2.5 bg-slate-50/50">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span 
                                class="w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-xs shrink-0"
                                :style="`background-color: ${s.palette.bg}; color: ${s.palette.text};`"
                                x-text="s.initial"
                            ></span>
                            <div class="min-w-0">
                                <span class="font-extrabold text-xs text-slate-900 block truncate" x-text="s.name"></span>
                                <span class="text-[11px] text-slate-500 block truncate" x-text="s.email"></span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold shrink-0">
                            <span x-text="s.active_loans_count"></span> aktif
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-[11px] bg-white p-2.5 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase block">NISN / NIS</span>
                            <span class="font-mono font-bold text-blue-600" x-text="s.nisn"></span>
                            <span class="text-[10px] text-slate-500" x-text="' / ' + s.nis"></span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase block">Kelas</span>
                            <span class="font-bold text-slate-800" x-text="s.nama_kelas"></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-0.5">
                        <span class="text-[11px] font-semibold text-emerald-700 truncate" x-text="'WA: ' + s.no_whatsapp"></span>
                        <button 
                            type="button"
                            @click="selectedStudent = s"
                            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-[11px] inline-flex items-center gap-1 shrink-0"
                        >
                            <i class="ph-bold ph-identification-card"></i>
                            <span>Detail</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Tabel Desktop -->
        <div class="hidden md:block overflow-x-auto" x-show="filteredStudents.length > 0">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                        <th class="py-3 px-3">Nama Siswa</th>
                        <th class="py-3 px-3">NISN &amp; NIS</th>
                        <th class="py-3 px-3">Kelas &amp; Tahun Ajaran</th>
                        <th class="py-3 px-3">Kontak WhatsApp</th>
                        <th class="py-3 px-3">Aktivitas Pinjam</th>
                        <th class="py-3 px-3 text-right">Detail Identitas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <template x-for="s in filteredStudents" :key="s.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-2.5">
                                    <span 
                                        class="w-8 h-8 rounded-full flex items-center justify-center font-extrabold text-xs shrink-0"
                                        :style="`background-color: ${s.palette.bg}; color: ${s.palette.text};`"
                                        x-text="s.initial"
                                    ></span>
                                    <div>
                                        <span class="font-extrabold text-slate-900 block" x-text="s.name"></span>
                                        <span class="text-[11px] text-slate-500" x-text="s.email"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-mono font-bold text-blue-600 block" x-text="s.nisn"></span>
                                <span class="text-[11px] text-slate-500" x-text="'NIS: ' + s.nis"></span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-800 block" x-text="s.nama_kelas"></span>
                                <span class="text-[11px] text-slate-500" x-text="s.tahun_ajaran"></span>
                            </td>
                            <td class="py-3 px-3 font-semibold text-emerald-700" x-text="s.no_whatsapp"></td>
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-bold">
                                    <span x-text="s.active_loans_count"></span> aktif / <span x-text="s.total_loans_count"></span> total
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button 
                                    type="button"
                                    @click="selectedStudent = s"
                                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold text-[11px] inline-flex items-center gap-1 transition-colors"
                                >
                                    <i class="ph-bold ph-identification-card"></i>
                                    <span>Lihat Detail</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Modal detail siswa -->
    <div 
        x-cloak
        x-show="selectedStudent"
        x-transition
        class="fixed inset-0 !mt-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="selectedStudent = null"></div>
        <template x-if="selectedStudent">
            <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 p-5 sm:p-7 z-10 space-y-4 my-auto">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3.5">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 block">Kartu Identitas Peminjam (Read-Only)</span>
                        <h3 class="text-base font-extrabold text-slate-900 mt-0.5" x-text="selectedStudent.name"></h3>
                        <p class="text-xs text-slate-500" x-text="selectedStudent.email"></p>
                    </div>
                    <button type="button" @click="selectedStudent = null" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                        <i class="ph-bold ph-x"></i>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">NISN (10 Digit)</span>
                        <span class="font-mono font-extrabold text-blue-600 mt-0.5 block" x-text="selectedStudent.nisn"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">NIS Sekolah</span>
                        <span class="font-extrabold text-slate-900 mt-0.5 block" x-text="selectedStudent.nis"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Nama Sekolah</span>
                        <span class="font-bold text-slate-900 mt-0.5 block" x-text="selectedStudent.nama_sekolah"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Kelas &amp; Tahun Ajaran</span>
                        <span class="font-bold text-slate-900 mt-0.5 block" x-text="selectedStudent.nama_kelas + ' (' + selectedStudent.tahun_ajaran + ')'"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Nomor Telepon</span>
                        <span class="font-bold text-slate-900 mt-0.5 block" x-text="selectedStudent.no_telepon"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Nomor WhatsApp</span>
                        <span class="font-bold text-emerald-700 mt-0.5 block" x-text="selectedStudent.no_whatsapp"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Tanggal Lahir</span>
                        <span class="font-bold text-slate-900 mt-0.5 block" x-text="selectedStudent.tanggal_lahir"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Kelengkapan Data</span>
                        <span class="font-extrabold text-emerald-600 mt-0.5 block" x-text="selectedStudent.identity_pct + '%'"></span>
                    </div>
                    <div class="col-span-2 p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Lokasi / Alamat</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block leading-relaxed" x-text="selectedStudent.alamat"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

</div>
@endsection

