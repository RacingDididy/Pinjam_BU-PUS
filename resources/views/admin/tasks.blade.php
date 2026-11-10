@extends('admin.layouts.admin')

@section('title', 'Tugas Admin & Proteksi Data - Dashboard Admin')
@section('page-title', 'Papan Tugas Operasional Admin & Pengamanan Data Website')

@section('content')
<div 
    x-data="{
        tasks: {{ Js::from($adminTasks) }},
        priorityFilter: 'all',
        addTaskModalOpen: false,
        busyTaskId: null,
        creatingTask: false,
        busySecurity: false,
        activeSessions: {{ $securityMetrics['active_sessions_count'] }},
        newTask: {
            judul: '',
            kategori_tugas: 'Kinerja Pustakawan',
            prioritas: 'penting',
            deskripsi: ''
        },
        quickTemplates: [
            {
                judul: 'Evaluasi Kecepatan Persetujuan & Kinerja Pustakawan',
                kategori_tugas: 'Kinerja Pustakawan',
                prioritas: 'penting',
                deskripsi: 'Memeriksa log persetujuan pustakawan agar antrean layanan tertangani tepat waktu.'
            },
            {
                judul: 'Audit Hak Akses Akun Pustakawan & Multi-Admin',
                kategori_tugas: 'Manajemen Petugas',
                prioritas: 'penting',
                deskripsi: 'Memverifikasi kredensial petugas perpustakaan dan memastikan keamanan role Admin.'
            },
            {
                judul: 'Pencadangan (Backup JSON) & Pembersihan Sesi Login',
                kategori_tugas: 'Keamanan Data',
                prioritas: 'penting',
                deskripsi: 'Mengunduh salinan cadangan data akun sistem dan memutus sesi perangkat yang tidak dikenal.'
            },
            {
                judul: 'Pemeriksaan Akun Belum Verifikasi Email OTP & NISN',
                kategori_tugas: 'Keamanan Akun',
                prioritas: 'normal',
                deskripsi: 'Menertibkan akun baru yang belum menyelesaikan verifikasi Gmail atau NISN 10 digit.'
            }
        ],
        applyTemplate(tpl) {
            this.newTask = {
                judul: tpl.judul,
                kategori_tugas: tpl.kategori_tugas,
                prioritas: tpl.prioritas,
                deskripsi: tpl.deskripsi
            };
            this.addTaskModalOpen = true;
        },
        get filteredTasks() {
            return (this.tasks || []).filter(t => {
                if (this.priorityFilter === 'all') return true;
                if (this.priorityFilter === 'completed') return t.is_completed || Number(t.progres) >= 100;
                if (this.priorityFilter === 'pending') return !t.is_completed && Number(t.progres) < 100;
                return t.prioritas === this.priorityFilter;
            });
        },
        get avgTaskProgress() {
            if (!this.tasks || this.tasks.length === 0) return 0;
            const sum = this.tasks.reduce((acc, t) => acc + Number(t.progres || 0), 0);
            return Math.round(sum / this.tasks.length);
        },
        get completedTaskCount() {
            return (this.tasks || []).filter(t => t.is_completed || Number(t.progres) >= 100).length;
        },
        async purgeSessions() {
            if (this.busySecurity) return;
            this.busySecurity = true;
            try {
                const res = await fetch('{{ route('admin.security.purge-sessions') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.activeSessions = 1;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal membersihkan sesi login.', type: 'error' });
            } finally {
                this.busySecurity = false;
            }
        },
        async optimizeSystem() {
            if (this.busySecurity) return;
            this.busySecurity = true;
            try {
                const res = await fetch('{{ route('admin.security.optimize') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal menyegarkan sistem.', type: 'error' });
            } finally {
                this.busySecurity = false;
            }
        },
        async submitNewTask() {
            if (this.creatingTask || !this.newTask.judul.trim()) return;
            this.creatingTask = true;
            try {
                const res = await fetch('{{ route('admin.tasks.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.newTask)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.tasks.unshift(data.task);
                    this.addTaskModalOpen = false;
                    this.newTask = { judul: '', kategori_tugas: 'Kinerja Pustakawan', prioritas: 'penting', deskripsi: '' };
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal menambahkan tugas.', type: 'error' });
            } finally {
                this.creatingTask = false;
            }
        },
        async setTaskProgress(task, pct) {
            this.busyTaskId = task.id;
            try {
                const res = await fetch(`/admin/tasks/${task.id}/progress`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ progres: pct })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.tasks.findIndex(t => t.id === task.id);
                    if (idx !== -1) this.tasks[idx] = data.task;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal mengubah progres.', type: 'error' });
            } finally {
                this.busyTaskId = null;
            }
        },
        async removeTask(task) {
            this.busyTaskId = task.id;
            try {
                const res = await fetch(`/admin/tasks/${task.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.tasks = this.tasks.filter(t => t.id !== task.id);
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal menghapus tugas.', type: 'error' });
            } finally {
                this.busyTaskId = null;
            }
        }
    }"
    class="space-y-5 sm:space-y-6"
>

    <!-- Panel keamanan data -->
    <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                        <i class="ph-fill ph-shield-check"></i>
                    </span>
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900">
                        Pusat Pengamanan Data Keseluruhan Website
                    </h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Amankan database akun, putuskan sesi login mencurigakan, dan segarkan konfigurasi keamanan aplikasi.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="{{ route('admin.security.backup') }}" 
                    class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs inline-flex items-center gap-1.5 transition-colors"
                >
                    <i class="ph-bold ph-download-simple text-cyan-400"></i>
                    <span>Unduh Backup JSON</span>
                </a>
                <button 
                    type="button"
                    @click="purgeSessions()"
                    :disabled="busySecurity"
                    class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-extrabold text-xs inline-flex items-center gap-1.5 transition-colors"
                >
                    <i class="ph-bold ph-sign-out"></i>
                    <span>Putuskan Sesi Lain (<span x-text="activeSessions"></span>)</span>
                </button>
                <button 
                    type="button"
                    @click="optimizeSystem()"
                    :disabled="busySecurity"
                    class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-extrabold text-xs inline-flex items-center gap-1.5 transition-colors"
                >
                    <i class="ph-bold ph-arrows-clockwise"></i>
                    <span>Segarkan Cache Keamanan</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Indeks Keamanan</span>
                <span class="text-xl font-black text-blue-600 mt-0.5 block">{{ $securityMetrics['overall_score_pct'] }}%</span>
                <span class="text-[10px] font-semibold text-slate-500 block">{{ $securityMetrics['security_grade'] }}</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Data Dilindungi</span>
                <span class="text-xl font-black text-slate-900 mt-0.5 block">{{ $securityMetrics['protected_records_count'] }} Record</span>
                <span class="text-[10px] font-semibold text-emerald-600 block">Siap Backup JSON</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Akun Belum Verifikasi</span>
                <span class="text-xl font-black text-amber-600 mt-0.5 block">{{ $securityMetrics['unverified_accounts_count'] }} Akun</span>
                <span class="text-[10px] font-semibold text-slate-500 block">Menunggu OTP Gmail</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Akun Dibekukan</span>
                <span class="text-xl font-black text-rose-600 mt-0.5 block">{{ $securityMetrics['suspended_accounts_count'] }} Akun</span>
                <span class="text-[10px] font-semibold text-slate-500 block">Akses Diblokir Admin</span>
            </div>
        </div>
    </section>

    <!-- Template tugas cepat -->
    <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-3.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                    Template Cepat Tugas Utama Admin
                </h3>
                <p class="text-[11px] text-slate-500">
                    Klik salah satu template di bawah untuk langsung menambahkan agenda pengawasan pustakawan atau pengamanan data.
                </p>
            </div>
            <button 
                type="button"
                @click="addTaskModalOpen = true"
                class="self-start sm:self-auto px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors shrink-0"
            >
                <i class="ph-bold ph-plus-circle text-sm"></i>
                <span>Buat Tugas Kustom</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
            <template x-for="(tpl, idx) in quickTemplates" :key="idx">
                <button 
                    type="button"
                    @click="applyTemplate(tpl)"
                    class="text-left p-3 rounded-2xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50/40 transition-all space-y-1 group"
                >
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[10px] font-extrabold" x-text="tpl.kategori_tugas"></span>
                        <i class="ph-bold ph-plus-circle text-slate-400 group-hover:text-indigo-600"></i>
                    </div>
                    <p class="text-xs font-extrabold text-slate-800 line-clamp-2" x-text="tpl.judul"></p>
                </button>
            </template>
        </div>
    </section>

    <!-- Papan tugas admin -->
    <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-2">
            <div class="flex items-center justify-between text-xs font-extrabold">
                <span class="text-slate-700">
                    Progres Keseluruhan Tugas Admin (<span x-text="completedTaskCount"></span> dari <span x-text="tasks.length"></span> selesai)
                </span>
                <span class="text-indigo-600" x-text="avgTaskProgress + '%'"></span>
            </div>
            <div class="w-full h-3 rounded-full bg-slate-200 overflow-hidden">
                <div 
                    class="h-full bg-gradient-to-r from-indigo-600 via-blue-500 to-emerald-500 rounded-full transition-all duration-500 relative overflow-hidden"
                    :style="`width: ${avgTaskProgress}%`"
                >
                    <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
            <button 
                type="button"
                @click="priorityFilter = 'all'"
                class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition-colors shrink-0"
                :class="priorityFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
                Semua Tugas (<span x-text="tasks.length"></span>)
            </button>
            <button 
                type="button"
                @click="priorityFilter = 'penting'"
                class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition-colors shrink-0"
                :class="priorityFilter === 'penting' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'"
            >
                Prioritas Penting
            </button>
            <button 
                type="button"
                @click="priorityFilter = 'pending'"
                class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition-colors shrink-0"
                :class="priorityFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'"
            >
                Sedang Dikerjakan
            </button>
            <button 
                type="button"
                @click="priorityFilter = 'completed'"
                class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition-colors shrink-0"
                :class="priorityFilter === 'completed' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
            >
                Selesai (100%)
            </button>
        </div>

        <!-- Tasks Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4">
            <template x-for="task in filteredTasks" :key="task.id">
                <article 
                    class="rounded-2xl border p-4 sm:p-5 space-y-3 transition-all"
                    :class="task.prioritas === 'penting' 
                        ? 'border-rose-200/90 bg-rose-50/20' 
                        : 'border-slate-200/90 bg-slate-50/40'"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span 
                                class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider"
                                :class="task.prioritas === 'penting' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700'"
                                x-text="task.prioritas === 'penting' ? 'Prioritas Penting' : 'Normal'"
                            ></span>
                            <span class="px-2.5 py-0.5 rounded-md bg-slate-200/70 text-slate-700 text-[10px] font-bold" x-text="task.kategori_tugas"></span>
                        </div>
                        <button 
                            type="button"
                            @click="removeTask(task)"
                            title="Hapus tugas"
                            class="text-slate-400 hover:text-rose-600 p-1 rounded-lg transition-colors"
                        >
                            <i class="ph-bold ph-trash text-sm"></i>
                        </button>
                    </div>

                    <div>
                        <h3 
                            class="text-xs sm:text-sm font-extrabold text-slate-900 leading-snug"
                            :class="Number(task.progres) >= 100 ? 'line-through text-slate-500' : ''"
                            x-text="task.judul"
                        ></h3>
                        <p x-show="task.deskripsi" class="text-[11px] text-slate-500 mt-1 leading-relaxed" x-text="task.deskripsi"></p>
                    </div>

                    <!-- Progress Bar & Quick Stepper -->
                    <div class="space-y-2 pt-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-600">Progres Penyelesaian</span>
                            <span 
                                class="font-extrabold"
                                :class="Number(task.progres) >= 100 ? 'text-emerald-600' : 'text-indigo-600'"
                                x-text="task.progres + '%'"
                            ></span>
                        </div>
                        <div class="w-full h-2.5 rounded-full bg-slate-200 overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all duration-500 relative overflow-hidden"
                                :class="Number(task.progres) >= 100 ? 'bg-emerald-500' : 'bg-indigo-600'"
                                :style="`width: ${task.progres}%`"
                            >
                                <div x-show="Number(task.progres) < 100" class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-1.5 pt-1 flex-wrap">
                            <div class="flex items-center gap-1">
                                <template x-for="stepPct in [0, 25, 50, 75, 100]" :key="stepPct">
                                    <button 
                                        type="button"
                                        @click="setTaskProgress(task, stepPct)"
                                        :disabled="busyTaskId === task.id"
                                        class="px-2 py-1 rounded-lg text-[10px] font-extrabold border transition-colors"
                                        :class="Number(task.progres) === stepPct 
                                            ? 'bg-slate-900 text-white border-slate-900' 
                                            : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-400'"
                                        x-text="stepPct + '%'"
                                    ></button>
                                </template>
                            </div>
                            <span 
                                x-show="Number(task.progres) >= 100"
                                class="text-[11px] font-extrabold text-emerald-600 inline-flex items-center gap-1"
                            >
                                <i class="ph-fill ph-check-circle"></i>
                                Selesai
                            </span>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <!-- Modal tambah tugas -->
    <div 
        x-cloak
        x-show="addTaskModalOpen"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="addTaskModalOpen = false"></div>
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200 p-5 sm:p-7 z-10 space-y-4 my-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Buat Tugas Operasional Admin</h3>
                    <p class="text-xs text-slate-500">Tambahkan agenda evaluasi kinerja pustakawan atau keamanan data.</p>
                </div>
                <button type="button" @click="addTaskModalOpen = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <form @submit.prevent="submitNewTask()" class="space-y-3.5 text-xs">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Judul Tugas Admin *</label>
                    <input type="text" x-model="newTask.judul" required placeholder="Contoh: Evaluasi Kinerja Pelayanan Pustakawan" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Kategori Tugas</label>
                        <select x-model="newTask.kategori_tugas" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-bold">
                            <option value="Kinerja Pustakawan">Kinerja Pustakawan</option>
                            <option value="Manajemen Petugas">Manajemen Petugas</option>
                            <option value="Keamanan Data">Keamanan Data</option>
                            <option value="Keamanan Akun">Keamanan Akun</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Prioritas</label>
                        <select x-model="newTask.prioritas" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-bold">
                            <option value="penting">Prioritas Penting</option>
                            <option value="normal">Normal</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Deskripsi Tugas</label>
                    <textarea x-model="newTask.deskripsi" rows="3" placeholder="Rincian langkah pengawasan atau pengamanan yang perlu dilakukan..." class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addTaskModalOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 font-bold text-slate-600">Batal</button>
                    <button type="submit" :disabled="creatingTask" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold">
                        <span x-show="!creatingTask">Simpan Tugas</span>
                        <span x-show="creatingTask">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
