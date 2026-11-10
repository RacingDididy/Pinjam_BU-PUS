@extends('admin.layouts.admin')

@section('title', 'Manajemen Akun User - Dashboard Admin')
@section('page-title', 'Direktori & Pengamanan Akun Pengguna')

@section('content')
<div 
    x-data="{
        users: {{ Js::from($usersPayload) }},
        searchUser: '',
        roleFilter: 'all',
        identityFilter: 'all',
        selectedUser: null,
        resetPassModalUser: null,
        newPassword: '',
        addUserModalOpen: false,
        busyUserId: null,
        creatingUser: false,
        newUser: {
            name: '',
            email: '',
            password: '',
            role: 'user',
            nisn: '',
            nis: '',
            nama_kelas: '',
            tahun_ajaran: '2025/2026',
            no_whatsapp: ''
        },
        get filteredUsers() {
            return (this.users || []).filter(u => {
                if (this.roleFilter !== 'all' && u.role !== this.roleFilter) return false;
                if (this.identityFilter === 'complete' && !u.identity_complete) return false;
                if (this.identityFilter === 'incomplete' && u.identity_complete) return false;
                if (!this.searchUser.trim()) return true;
                const q = this.searchUser.toLowerCase();
                return (u.name || '').toLowerCase().includes(q)
                    || (u.email || '').toLowerCase().includes(q)
                    || (u.nisn || '').toLowerCase().includes(q)
                    || (u.nis || '').toLowerCase().includes(q)
                    || (u.nama_kelas || '').toLowerCase().includes(q);
            });
        },
        sanitizeNewUserNisn() {
            this.newUser.nisn = (this.newUser.nisn || '').replace(/\D/g, '').slice(0, 10);
        },
        async submitNewUser() {
            if (this.creatingUser) return;
            if (this.newUser.nisn && this.newUser.nisn.length !== 10) {
                $dispatch('notify-toast', { message: 'NISN wajib tepat 10 digit angka.', type: 'error' });
                return;
            }
            this.creatingUser = true;
            try {
                const res = await fetch('{{ route('admin.users.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.newUser)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.users.unshift(data.user);
                    this.addUserModalOpen = false;
                    this.newUser = { name: '', email: '', password: '', role: 'user', nisn: '', nis: '', nama_kelas: '', tahun_ajaran: '2025/2026', no_whatsapp: '' };
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    const err = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal menambah akun.');
                    $dispatch('notify-toast', { message: err, type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.creatingUser = false;
            }
        },
        async changeRole(user, newRole) {
            this.busyUserId = user.id;
            try {
                const res = await fetch(`/admin/users/${user.id}/role`, {
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
                    const idx = this.users.findIndex(u => u.id === user.id);
                    if (idx !== -1) this.users[idx] = data.user;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal mengubah role.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyUserId = null;
            }
        },
        async toggleActive(user) {
            this.busyUserId = user.id;
            try {
                const res = await fetch(`/admin/users/${user.id}/toggle-active`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.users.findIndex(u => u.id === user.id);
                    if (idx !== -1) this.users[idx] = data.user;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal mengubah status akun.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyUserId = null;
            }
        },
        async verifyManual(user) {
            this.busyUserId = user.id;
            try {
                const res = await fetch(`/admin/users/${user.id}/verify`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.users.findIndex(u => u.id === user.id);
                    if (idx !== -1) this.users[idx] = data.user;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gagal memverifikasi email.', type: 'error' });
            } finally {
                this.busyUserId = null;
            }
        },
        async submitResetPassword() {
            if (!this.resetPassModalUser || this.newPassword.length < 8) return;
            this.busyUserId = this.resetPassModalUser.id;
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
                this.busyUserId = null;
            }
        },
        async removeUser(user) {
            if (!confirm(`Hapus akun ${user.name} secara permanen?`)) return;
            this.busyUserId = user.id;
            try {
                const res = await fetch(`/admin/users/${user.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.users = this.users.filter(u => u.id !== user.id);
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal menghapus akun.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyUserId = null;
            }
        }
    }"
    class="space-y-5 sm:space-y-6"
>

    <!-- Header & Quick Stats -->
    <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6">
        <div>
            <h2 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight">
                Manajemen Akun &amp; Keamanan Identitas Pengguna
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelola verifikasi email OTP, validasi identitas NISN 10 digit, pemulihan kata sandi, serta pembagian hak akses role.
            </p>
        </div>
        <button 
            type="button"
            @click="addUserModalOpen = true"
            class="self-start sm:self-auto px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors shrink-0"
        >
            <i class="ph-bold ph-user-plus text-sm"></i>
            <span>Tambah Akun Baru</span>
        </button>
    </section>

    <!-- Filter & Directory Card -->
    <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex flex-wrap items-center gap-1.5 text-xs font-bold">
                <span class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800">
                    Total Akun: <strong x-text="users.length"></strong>
                </span>
                <span class="px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700">
                    Warga Sekolah: <strong x-text="users.filter(u => u.role === 'user').length"></strong>
                </span>
                <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700">
                    Pustakawan: <strong x-text="users.filter(u => u.role === 'pustakawan').length"></strong>
                </span>
                <span class="px-2.5 py-1 rounded-xl bg-indigo-50 text-indigo-700">
                    Admin: <strong x-text="users.filter(u => u.role === 'admin').length"></strong>
                </span>
            </div>

            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <div class="relative">
                    <i class="ph ph-magnifying-glass text-slate-400 text-sm absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input 
                        type="text"
                        x-model="searchUser"
                        placeholder="Cari nama, NISN, kelas, email..."
                        class="w-full sm:w-60 pl-8 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500"
                    >
                </div>

                <select 
                    x-model="roleFilter"
                    class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 outline-none"
                >
                    <option value="all">Semua Role</option>
                    <option value="user">Warga Sekolah (User)</option>
                    <option value="pustakawan">Pustakawan</option>
                    <option value="admin">Admin</option>
                </select>

                <select 
                    x-model="identityFilter"
                    class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 outline-none"
                >
                    <option value="all">Semua Status Identitas</option>
                    <option value="complete">Identitas Lengkap (100%)</option>
                    <option value="incomplete">Belum Lengkap</option>
                </select>
            </div>
        </div>

        <!-- Mobile Card List (Compact on Mobile) -->
        <div class="grid grid-cols-1 gap-3 md:hidden">
            <template x-for="u in filteredUsers" :key="'mob-' + u.id">
                <div class="rounded-2xl border border-slate-200/80 p-3.5 space-y-3 bg-slate-50/40">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span 
                                class="w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-xs shrink-0"
                                :style="`background-color: ${u.palette.bg}; color: ${u.palette.text};`"
                                x-text="u.initial"
                            ></span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-extrabold text-xs text-slate-900 truncate" x-text="u.name"></span>
                                    <span x-show="!u.is_active" class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[9px] font-extrabold">Dibekukan</span>
                                </div>
                                <span class="text-[11px] text-slate-500 block truncate" x-text="u.email"></span>
                            </div>
                        </div>

                        <select 
                            :value="u.role"
                            @change="changeRole(u, $event.target.value)"
                            :disabled="busyUserId === u.id"
                            class="px-2 py-1 rounded-lg border text-[10px] font-extrabold outline-none shrink-0"
                            :class="{
                                'bg-blue-50 text-blue-700 border-blue-200': u.role === 'user',
                                'bg-emerald-50 text-emerald-700 border-emerald-200': u.role === 'pustakawan',
                                'bg-indigo-50 text-indigo-700 border-indigo-200': u.role === 'admin'
                            }"
                        >
                            <option value="user">User</option>
                            <option value="pustakawan">Pustakawan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-[11px] bg-white p-2.5 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase block">NISN / NIS</span>
                            <span class="font-mono font-bold text-slate-800" x-text="u.nisn + ' / ' + u.nis"></span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase block">Kelas / Jabatan</span>
                            <span class="font-bold text-slate-800" x-text="u.nama_kelas + ' (' + u.identity_pct + '%)'"></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <div>
                            <template x-if="u.email_verified">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold">
                                    <i class="ph-fill ph-check-circle"></i> Terverifikasi
                                </span>
                            </template>
                            <template x-if="!u.email_verified">
                                <button type="button" @click="verifyManual(u)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-300 text-[10px] font-extrabold">
                                    <i class="ph-bold ph-check"></i> Verifikasi Manual
                                </button>
                            </template>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="selectedUser = u" class="px-2.5 py-1 rounded-lg bg-slate-200/70 text-slate-700 font-bold text-[10px]">Detail</button>
                            <button type="button" @click="resetPassModalUser = u; newPassword = ''" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 font-bold text-[10px]">Sandi</button>
                            <button type="button" @click="toggleActive(u)" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 font-bold text-[10px]" x-text="u.is_active ? 'Bekukan' : 'Aktifkan'"></button>
                            <button type="button" @click="removeUser(u)" class="w-7 h-7 rounded-lg border border-rose-200 text-rose-600 flex items-center justify-center"><i class="ph-bold ph-trash text-xs"></i></button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                        <th class="py-3 px-3">Akun Pengguna</th>
                        <th class="py-3 px-3">Identitas Sekolah (NISN &amp; Kelas)</th>
                        <th class="py-3 px-3">Keamanan Email &amp; Identitas</th>
                        <th class="py-3 px-3">Role Akses</th>
                        <th class="py-3 px-3 text-right">Kendali Keamanan Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <template x-for="u in filteredUsers" :key="u.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-3">
                                <div class="flex items-center gap-2.5">
                                    <span 
                                        class="w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-xs shrink-0"
                                        :style="`background-color: ${u.palette.bg}; color: ${u.palette.text};`"
                                        x-text="u.initial"
                                    ></span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-extrabold text-slate-900 truncate" x-text="u.name"></span>
                                            <span x-show="!u.is_active" class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[10px] font-extrabold">Dibekukan</span>
                                        </div>
                                        <span class="text-[11px] text-slate-500 block truncate" x-text="u.email"></span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-3">
                                <div class="space-y-0.5">
                                    <div class="font-bold text-slate-800">
                                        NISN: <span class="font-mono text-blue-600" x-text="u.nisn"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        NIS: <span x-text="u.nis"></span> &bull; Kelas/Jabatan: <strong x-text="u.nama_kelas"></strong>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        WA: <span x-text="u.no_whatsapp"></span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-3">
                                <div class="space-y-1.5 max-w-[180px]">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="u.email_verified">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold">
                                                <i class="ph-fill ph-check-circle"></i> Email Terverifikasi
                                            </span>
                                        </template>
                                        <template x-if="!u.email_verified">
                                            <button 
                                                type="button"
                                                @click="verifyManual(u)"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-extrabold transition-colors"
                                            >
                                                <i class="ph-bold ph-check"></i> Verifikasi Manual
                                            </button>
                                        </template>
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex justify-between text-[10px] font-bold">
                                            <span class="text-slate-500">Identitas</span>
                                            <span :class="u.identity_complete ? 'text-emerald-600' : 'text-amber-600'" x-text="u.identity_pct + '%'"></span>
                                        </div>
                                        <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                            <div 
                                                class="h-full rounded-full"
                                                :class="u.identity_complete ? 'bg-emerald-500' : 'bg-amber-500'"
                                                :style="`width: ${u.identity_pct}%`"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-3">
                                <select 
                                    :value="u.role"
                                    @change="changeRole(u, $event.target.value)"
                                    :disabled="busyUserId === u.id"
                                    class="px-2.5 py-1.5 rounded-xl border text-xs font-extrabold outline-none transition-colors"
                                    :class="{
                                        'bg-blue-50 text-blue-700 border-blue-200': u.role === 'user',
                                        'bg-emerald-50 text-emerald-700 border-emerald-200': u.role === 'pustakawan',
                                        'bg-indigo-50 text-indigo-700 border-indigo-200': u.role === 'admin'
                                    }"
                                >
                                    <option value="user">Warga Sekolah</option>
                                    <option value="pustakawan">Pustakawan</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </td>

                            <td class="py-3.5 px-3 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button 
                                        type="button"
                                        @click="selectedUser = u"
                                        class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-600 font-bold text-[11px] inline-flex items-center gap-1 transition-colors"
                                    >
                                        <i class="ph-bold ph-identification-card"></i>
                                        <span>Identitas</span>
                                    </button>

                                    <button 
                                        type="button"
                                        @click="resetPassModalUser = u; newPassword = ''"
                                        class="px-2.5 py-1.5 rounded-xl border border-slate-200 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 font-bold text-[11px] inline-flex items-center gap-1 transition-colors"
                                    >
                                        <i class="ph-bold ph-key"></i>
                                        <span>Sandi</span>
                                    </button>

                                    <button 
                                        type="button"
                                        @click="toggleActive(u)"
                                        :disabled="busyUserId === u.id"
                                        class="px-2.5 py-1.5 rounded-xl border text-[11px] font-bold transition-colors"
                                        :class="u.is_active ? 'border-slate-200 text-slate-600 hover:bg-amber-50 hover:text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                                        x-text="u.is_active ? 'Bekukan' : 'Aktifkan'"
                                    ></button>

                                    <button 
                                        type="button"
                                        @click="removeUser(u)"
                                        :disabled="busyUserId === u.id"
                                        title="Hapus akun"
                                        class="w-8 h-8 rounded-xl border border-slate-200 hover:border-rose-200 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition-colors"
                                    >
                                        <i class="ph-bold ph-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="filteredUsers.length === 0" class="p-8 text-center text-xs text-slate-500">
            Tidak ada akun pengguna yang sesuai dengan pencarian atau filter.
        </div>
    </section>

    <!-- Modal detail identitas -->
    <div 
        x-cloak
        x-show="selectedUser"
        x-transition
        class="fixed inset-0 !mt-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="selectedUser = null"></div>
        <template x-if="selectedUser">
            <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 p-5 sm:p-7 z-10 space-y-4 my-auto">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3.5">
                    <div class="flex items-center gap-3">
                        <span 
                            class="w-11 h-11 rounded-full flex items-center justify-center font-black text-base"
                            :style="`background-color: ${selectedUser.palette.bg}; color: ${selectedUser.palette.text};`"
                            x-text="selectedUser.initial"
                        ></span>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900" x-text="selectedUser.name"></h3>
                            <p class="text-xs text-slate-500" x-text="selectedUser.email"></p>
                        </div>
                    </div>
                    <button type="button" @click="selectedUser = null" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                        <i class="ph-bold ph-x"></i>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">NISN (10 Digit)</span>
                        <span class="font-mono font-extrabold text-slate-900 mt-0.5 block" x-text="selectedUser.nisn"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">NIS Sekolah</span>
                        <span class="font-extrabold text-slate-900 mt-0.5 block" x-text="selectedUser.nis"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Sekolah</span>
                        <span class="font-bold text-slate-900 mt-0.5 block" x-text="selectedUser.nama_sekolah"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Kelas &amp; Tahun Ajaran</span>
                        <span class="font-bold text-slate-900 mt-0.5 block" x-text="selectedUser.nama_kelas + ' (' + selectedUser.tahun_ajaran + ')'"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Nomor Telepon</span>
                        <span class="font-bold text-slate-900 mt-0.5 block" x-text="selectedUser.no_telepon"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Nomor WhatsApp</span>
                        <span class="font-bold text-emerald-700 mt-0.5 block" x-text="selectedUser.no_whatsapp"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Tanggal Lahir</span>
                        <span class="font-bold text-slate-900 mt-0.5 block" x-text="selectedUser.tanggal_lahir"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Terdaftar Sejak</span>
                        <span class="font-bold text-blue-600 mt-0.5 block" x-text="selectedUser.joined_at"></span>
                    </div>
                    <div class="col-span-2 p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Lokasi / Alamat Tempat Tinggal</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block leading-relaxed" x-text="selectedUser.alamat"></span>
                    </div>
                </div>
            </div>
        </template>
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
                        <h3 class="text-base font-extrabold text-slate-900">Reset Kata Sandi Akun</h3>
                        <p class="text-xs text-slate-500" x-text="resetPassModalUser.name + ' (' + resetPassModalUser.email + ')'"></p>
                    </div>
                    <button type="button" @click="resetPassModalUser = null" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                        <i class="ph-bold ph-x"></i>
                    </button>
                </div>

                <form @submit.prevent="submitResetPassword()" class="space-y-3.5 text-xs" x-data="{ showResetPass: false }">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Kata Sandi Baru (Min. 8 Karakter & 1 Angka) *</label>
                        <div class="relative">
                            <input :type="showResetPass ? 'text' : 'password'" x-model="newPassword" required minlength="8" placeholder="Minimal 8 karakter & 1 angka..." class="w-full pl-3 pr-9 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                            <button type="button" @click="showResetPass = !showResetPass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                                <i class="ph-bold text-sm" :class="showResetPass ? 'ph-eye-slash' : 'ph-eye'"></i>
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

    <!-- Modal tambah akun -->
    <div 
        x-cloak
        x-show="addUserModalOpen"
        x-transition
        class="fixed inset-0 !mt-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="addUserModalOpen = false"></div>
        <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 p-5 sm:p-7 z-10 space-y-4 my-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Tambah Akun Warga Sekolah / Petugas</h3>
                    <p class="text-xs text-slate-500">Akun yang dibuat Admin otomatis terverifikasi.</p>
                </div>
                <button type="button" @click="addUserModalOpen = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>

            <form @submit.prevent="submitNewUser()" class="space-y-3.5 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Nama Lengkap *</label>
                        <input type="text" x-model="newUser.name" required class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Role Hak Akses *</label>
                        <select x-model="newUser.role" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-bold">
                            <option value="user">Warga Sekolah (User)</option>
                            <option value="pustakawan">Pustakawan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Email Aktif *</label>
                        <input type="email" x-model="newUser.email" required class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                    </div>
                    <div x-data="{ showCreatePass: false }">
                        <label class="font-bold text-slate-700 block mb-1">Kata Sandi Awal *</label>
                        <div class="relative">
                            <input :type="showCreatePass ? 'text' : 'password'" x-model="newUser.password" required minlength="8" placeholder="Min. 8 karakter & 1 angka" class="w-full pl-3 pr-9 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                            <button type="button" @click="showCreatePass = !showCreatePass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                                <i class="ph-bold text-sm" :class="showCreatePass ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">NISN (10 Digit)</label>
                        <input type="text" x-model="newUser.nisn" @input="sanitizeNewUserNisn()" maxlength="10" placeholder="0081234567" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono font-bold">
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">NIS (Maks 5 Huruf)</label>
                        <input type="text" x-model="newUser.nis" maxlength="5" placeholder="23015" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Kelas / Jabatan</label>
                        <input type="text" x-model="newUser.nama_kelas" placeholder="XII RPL A" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                    </div>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nomor WhatsApp</label>
                    <input type="text" x-model="newUser.no_whatsapp" placeholder="081234567890" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addUserModalOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 font-bold text-slate-600">Batal</button>
                    <button type="submit" :disabled="creatingUser" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold">
                        <span x-show="!creatingUser">Simpan Akun</span>
                        <span x-show="creatingUser">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
