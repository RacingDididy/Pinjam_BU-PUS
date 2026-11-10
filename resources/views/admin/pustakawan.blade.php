@extends('admin.layouts.admin')

@section('title', 'Kinerja & Kelola Pustakawan - Dashboard Admin')
@section('page-title', 'Pantauan Kinerja Pustakawan & Manajemen Akun Petugas')

@section('content')
<div 
    x-data="{
        staffList: {{ Js::from($staffAccounts) }},
        kpiMap: {{ Js::from($librarianData['by_user_id']) }},
        roleTab: 'all',
        addStaffModalOpen: false,
        resetPassModalUser: null,
        newPassword: '',
        busyStaffId: null,
        creatingStaff: false,
        newStaff: {
            name: '',
            email: '',
            password: '',
            role: 'pustakawan',
            nama_kelas: 'Petugas Perpustakaan',
            no_whatsapp: ''
        },
        get filteredStaff() {
            return (this.staffList || []).filter(s => {
                if (this.roleTab === 'all') return true;
                return s.role === this.roleTab;
            });
        },
        getKpi(staffId) {
            return this.kpiMap[staffId] || {
                total_decisions: 0,
                approved_count: 0,
                rejected_count: 0,
                avg_response_label: 'Siap Bertugas',
                last_action_label: 'Siap melayani',
                kpi_score_pct: 100,
                kpi_status: 'Aktif & Siap'
            };
        },
        async submitNewStaff() {
            if (this.creatingStaff) return;
            this.creatingStaff = true;
            try {
                const res = await fetch('{{ route('admin.users.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.newStaff)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.staffList.unshift(data.user);
                    this.addStaffModalOpen = false;
                    this.newStaff = { name: '', email: '', password: '', role: 'pustakawan', nama_kelas: 'Petugas Perpustakaan', no_whatsapp: '' };
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    const err = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal menambah petugas.');
                    $dispatch('notify-toast', { message: err, type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.creatingStaff = false;
            }
        },
        async changeStaffRole(staff, newRole) {
            this.busyStaffId = staff.id;
            try {
                const res = await fetch(`/admin/users/${staff.id}/role`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ role: newRole })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (newRole === 'user') {
                        this.staffList = this.staffList.filter(s => s.id !== staff.id);
                    } else {
                        const idx = this.staffList.findIndex(s => s.id === staff.id);
                        if (idx !== -1) this.staffList[idx] = data.user;
                    }
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal mengubah role petugas.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyStaffId = null;
            }
        },
        async toggleStaffActive(staff) {
            this.busyStaffId = staff.id;
            try {
                const res = await fetch(`/admin/users/${staff.id}/toggle-active`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.staffList.findIndex(s => s.id === staff.id);
                    if (idx !== -1) this.staffList[idx] = data.user;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal mengubah status petugas.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyStaffId = null;
            }
        },
        async submitResetPassword() {
            if (!this.resetPassModalUser || this.newPassword.length < 8) return;
            this.busyStaffId = this.resetPassModalUser.id;
            try {
                const res = await fetch(`/admin/users/${this.resetPassModalUser.id}/reset-password`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ password: this.newPassword })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.resetPassModalUser = null;
                    this.newPassword = '';
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal mereset password.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyStaffId = null;
            }
        },
        async removeStaff(staff) {
            if (!confirm(`Hapus akun petugas ${staff.name} secara permanen?`)) return;
            this.busyStaffId = staff.id;
            try {
                const res = await fetch(`/admin/users/${staff.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.staffList = this.staffList.filter(s => s.id !== staff.id);
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal menghapus akun petugas.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyStaffId = null;
            }
        }
    }"
    class="space-y-5 sm:space-y-6"
>

    <!-- Header -->
    <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight">
                Evaluasi Kinerja Pustakawan &amp; Kelola Akun Petugas
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Pantau KPI pelayanan setiap pustakawan serta kelola kredensial akun para Pustakawan dan Administrator sistem.
            </p>
        </div>

        <button 
            type="button"
            @click="addStaffModalOpen = true"
            class="self-start sm:self-auto px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors shrink-0"
        >
            <i class="ph-bold ph-user-plus text-sm"></i>
            <span>Tambah Pustakawan / Admin</span>
        </button>
    </section>

    <!-- Ringkasan kinerja -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 block">Skor Kinerja Pustakawan</span>
            <span class="text-2xl font-black text-emerald-600 mt-1 block">{{ $librarianData['team_kpi_pct'] }}%</span>
            <span class="text-[10px] font-semibold text-slate-400 mt-0.5 block">Rata-rata kesiapan &amp; respons</span>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 block">Petugas Pustakawan</span>
            <span class="text-2xl font-black text-slate-900 mt-1 block">{{ $librarianData['active_pustakawan'] }} / {{ $librarianData['total_pustakawan'] }}</span>
            <span class="text-[10px] font-semibold text-emerald-600 mt-0.5 block">Akun pustakawan aktif</span>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 block">Administrator Sistem</span>
            <span class="text-2xl font-black text-indigo-600 mt-1 block">{{ $librarianData['total_admins'] }} Admin</span>
            <span class="text-[10px] font-semibold text-slate-400 mt-0.5 block">Dukungan multi-admin aktif</span>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 block">Ketuntasan Pelayanan</span>
            <span class="text-2xl font-black text-blue-600 mt-1 block">{{ $librarianData['queue_resolution_pct'] }}%</span>
            <span class="text-[10px] font-semibold text-slate-400 mt-0.5 block">{{ $librarianData['handled_queue_count'] }} ditangani pustakawan</span>
        </div>
    </section>

    <!-- Daftar petugas -->
    <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-1.5 overflow-x-auto">
                <button 
                    type="button"
                    @click="roleTab = 'all'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-colors"
                    :class="roleTab === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                    Semua Petugas (<span x-text="staffList.length"></span>)
                </button>
                <button 
                    type="button"
                    @click="roleTab = 'pustakawan'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-colors"
                    :class="roleTab === 'pustakawan' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                >
                    Pustakawan (<span x-text="staffList.filter(s => s.role === 'pustakawan').length"></span>)
                </button>
                <button 
                    type="button"
                    @click="roleTab = 'admin'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-colors"
                    :class="roleTab === 'admin' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
                >
                    Admin (<span x-text="staffList.filter(s => s.role === 'admin').length"></span>)
                </button>
            </div>

            <span class="text-[11px] font-semibold text-slate-400">
                Klik <strong>Reset Sandi</strong> jika pustakawan lupa kata sandi.
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="staff in filteredStaff" :key="staff.id">
                <article class="rounded-2xl border border-slate-200/80 p-4 sm:p-5 space-y-4 bg-slate-50/40 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span 
                                    class="w-11 h-11 rounded-full flex items-center justify-center font-extrabold text-sm shrink-0"
                                    :style="`background-color: ${staff.palette.bg}; color: ${staff.palette.text};`"
                                    x-text="staff.initial"
                                ></span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h3 class="text-sm font-extrabold text-slate-900 truncate" x-text="staff.name"></h3>
                                        <span 
                                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                                            :class="staff.role === 'pustakawan' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800'"
                                            x-text="staff.role"
                                        ></span>
                                        <span 
                                            x-show="!staff.is_active"
                                            class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-extrabold"
                                        >Dibekukan</span>
                                    </div>
                                    <p class="text-xs text-slate-500 truncate mt-0.5" x-text="staff.email"></p>
                                </div>
                            </div>

                            <select 
                                :value="staff.role"
                                @change="changeStaffRole(staff, $event.target.value)"
                                :disabled="busyStaffId === staff.id"
                                class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-extrabold text-slate-700 outline-none shrink-0"
                            >
                                <option value="pustakawan">Role: Pustakawan</option>
                                <option value="admin">Role: Admin</option>
                                <option value="user">Turunkan ke User</option>
                            </select>
                        </div>

                        <!-- Metrik Kinerja Khusus Pustakawan -->
                        <template x-if="staff.role === 'pustakawan'">
                            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/70 space-y-2.5">
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div class="p-2 rounded-xl bg-slate-50">
                                        <span class="text-[10px] font-bold text-slate-400 block">Keputusan</span>
                                        <span class="text-sm font-black text-slate-900" x-text="getKpi(staff.id).total_decisions"></span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-emerald-50/60">
                                        <span class="text-[10px] font-bold text-emerald-700 block">Disetujui</span>
                                        <span class="text-sm font-black text-emerald-700" x-text="getKpi(staff.id).approved_count"></span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-rose-50/60">
                                        <span class="text-[10px] font-bold text-rose-700 block">Ditolak</span>
                                        <span class="text-sm font-black text-rose-700" x-text="getKpi(staff.id).rejected_count"></span>
                                    </div>
                                </div>

                                <div class="space-y-1 pt-1">
                                    <div class="flex justify-between text-[11px] font-bold">
                                        <span class="text-slate-500">Skor Kinerja &amp; Kesiapan Layanan</span>
                                        <span class="text-emerald-600" x-text="getKpi(staff.id).kpi_score_pct + '%'"></span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div 
                                            class="h-full bg-emerald-500 rounded-full relative overflow-hidden"
                                            :style="`width: ${getKpi(staff.id).kpi_score_pct}%`"
                                        >
                                            <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Info Otoritas untuk Sesama Admin -->
                        <template x-if="staff.role === 'admin'">
                            <div class="p-3 rounded-2xl bg-indigo-50/50 border border-indigo-100 text-[11px] text-indigo-900 flex items-center justify-between">
                                <span class="font-bold">Hak Akses: Pengawasan Sistem, Petugas &amp; Keamanan</span>
                                <span class="font-extrabold text-indigo-700">Aktif</span>
                            </div>
                        </template>
                    </div>

                    <!-- Tombol Kendali Akun Petugas -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200/60">
                        <button 
                            type="button"
                            @click="resetPassModalUser = staff; newPassword = ''"
                            class="px-3 py-1.5 rounded-xl bg-white hover:bg-blue-50 text-slate-700 hover:text-blue-600 border border-slate-200 font-bold text-xs inline-flex items-center gap-1.5 transition-colors"
                        >
                            <i class="ph-bold ph-key"></i>
                            <span>Reset Sandi</span>
                        </button>

                        <div class="flex items-center gap-1.5">
                            <button 
                                type="button"
                                @click="toggleStaffActive(staff)"
                                :disabled="busyStaffId === staff.id"
                                class="px-3 py-1.5 rounded-xl border text-xs font-bold transition-colors"
                                :class="staff.is_active ? 'bg-white border-slate-200 text-slate-600 hover:bg-amber-50 hover:text-amber-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
                                x-text="staff.is_active ? 'Bekukan Akses' : 'Aktifkan Kembali'"
                            ></button>

                            <button 
                                type="button"
                                @click="removeStaff(staff)"
                                :disabled="busyStaffId === staff.id"
                                title="Hapus akun petugas"
                                class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:border-rose-200 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition-colors"
                            >
                                <i class="ph-bold ph-trash"></i>
                            </button>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <!-- Modal tambah petugas -->
    <div 
        x-cloak
        x-show="addStaffModalOpen"
        x-transition
        class="fixed inset-0 !mt-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="addStaffModalOpen = false"></div>
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200 p-5 sm:p-7 z-10 space-y-4 my-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Tambah Akun Pustakawan / Admin</h3>
                    <p class="text-xs text-slate-500">Buat kredensial resmi untuk petugas perpustakaan atau admin baru.</p>
                </div>
                <button type="button" @click="addStaffModalOpen = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <form @submit.prevent="submitNewStaff()" class="space-y-3.5 text-xs">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nama Lengkap Petugas *</label>
                    <input type="text" x-model="newStaff.name" required placeholder="Contoh: Budi Santoso, S.I.Pust." class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Jabatan / Role Sistem *</label>
                    <select x-model="newStaff.role" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-bold">
                        <option value="pustakawan">Pustakawan (Pengelola Buku, Kategori &amp; Persetujuan)</option>
                        <option value="admin">Administrator (Pengawas Kinerja, User &amp; Keamanan)</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Email Resmi Petugas *</label>
                    <input type="email" x-model="newStaff.email" required placeholder="pustakawan2@smkn5solo.sch.id" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                </div>

                <div x-data="{ showStaffPass: false }">
                    <label class="font-bold text-slate-700 block mb-1">Kata Sandi Keamanan *</label>
                    <div class="relative">
                        <input :type="showStaffPass ? 'text' : 'password'" x-model="newStaff.password" required minlength="8" placeholder="Minimal 8 karakter & 1 angka" class="w-full pl-3 pr-9 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                        <button type="button" @click="showStaffPass = !showStaffPass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                            <i class="ph-bold text-sm" :class="showStaffPass ? 'ph-eye-slash' : 'ph-eye'"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nomor WhatsApp Aktif</label>
                    <input type="text" x-model="newStaff.no_whatsapp" placeholder="081234567890" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addStaffModalOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 font-bold text-slate-600">Batal</button>
                    <button type="submit" :disabled="creatingStaff" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold">
                        <span x-show="!creatingStaff">Simpan Petugas</span>
                        <span x-show="creatingStaff">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal reset sandi -->
    <div 
        x-cloak
        x-show="resetPassModalUser"
        x-transition
        class="fixed inset-0 !mt-0 z-50 flex items-center justify-center p-3 sm:p-4"
    >
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="resetPassModalUser = null"></div>
        <template x-if="resetPassModalUser">
            <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200 p-5 sm:p-6 z-10 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Reset Kata Sandi Petugas</h3>
                        <p class="text-xs text-slate-500" x-text="resetPassModalUser.name + ' (' + resetPassModalUser.email + ')'"></p>
                    </div>
                    <button type="button" @click="resetPassModalUser = null" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                        <i class="ph-bold ph-x"></i>
                    </button>
                </div>

                <form @submit.prevent="submitResetPassword()" class="space-y-3.5 text-xs" x-data="{ showResetStaffPass: false }">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Kata Sandi Baru (Min. 8 Karakter & 1 Angka) *</label>
                        <div class="relative">
                            <input :type="showResetStaffPass ? 'text' : 'password'" x-model="newPassword" required minlength="8" placeholder="Minimal 8 karakter & 1 angka..." class="w-full pl-3 pr-9 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                            <button type="button" @click="showResetStaffPass = !showResetStaffPass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                                <i class="ph-bold text-sm" :class="showResetStaffPass ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="resetPassModalUser = null" class="px-4 py-2 rounded-xl border border-slate-200 font-bold text-slate-600">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold">Simpan Sandi Baru</button>
                    </div>
                </form>
            </div>
        </template>
    </div>

</div>
@endsection

