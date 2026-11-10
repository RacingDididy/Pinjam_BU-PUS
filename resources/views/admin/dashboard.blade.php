@extends('admin.layouts.admin')

@section('title', 'Ringkasan & Keamanan Sistem - Dashboard Admin')
@section('page-title', 'Ringkasan Kinerja Pustakawan, Manajemen Akun & Keamanan Website')

@section('content')
<div 
    x-data="{
        busyAction: false,
        activeSessions: {{ $securityMetrics['active_sessions_count'] }},
        async purgeSessions() {
            if (this.busyAction) return;
            this.busyAction = true;
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
                this.busyAction = false;
            }
        },
        async optimizeSystem() {
            if (this.busyAction) return;
            this.busyAction = true;
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
                this.busyAction = false;
            }
        }
    }"
    class="space-y-5 sm:space-y-7"
>

    <!-- Banner ringkasan -->
    <section class="bg-gradient-to-br from-[#0B1528] via-slate-900 to-indigo-950 rounded-3xl p-4 sm:p-6 text-white shadow-lg border border-slate-800/80 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-500/15 border border-blue-400/30 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Pusat Kendali &amp; Keamanan Sistem</span>
            </div>
            <h2 class="text-base sm:text-xl font-extrabold tracking-tight">
                Pengawasan Kinerja Pustakawan, Akun &amp; Keamanan Data Website
            </h2>
            <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                Admin berfokus penuh pada evaluasi kinerja petugas pustakawan, pengelolaan akun pustakawan &amp; warga sekolah, serta pengamanan integritas data website secara menyeluruh.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a 
                href="{{ route('admin.pustakawan.index') }}" 
                class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors"
            >
                <i class="ph-bold ph-user-check text-sm"></i>
                <span>Pantau Pustakawan ({{ $totalPustakawan }})</span>
            </a>
            <a 
                href="{{ route('admin.users.index') }}" 
                class="px-3.5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors"
            >
                <i class="ph-bold ph-users-three text-sm"></i>
                <span>Kelola Akun ({{ $totalUsers }})</span>
            </a>
            <a 
                href="{{ route('admin.security.backup') }}" 
                class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 text-white font-bold text-xs inline-flex items-center gap-1.5 transition-colors"
            >
                <i class="ph-bold ph-download-simple text-sm text-cyan-300"></i>
                <span>Backup Data JSON</span>
            </a>
        </div>
    </section>

    <!-- Indikator utama -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-5">
        
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-500 block">Indeks Kinerja Pustakawan</span>
                    <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-0.5 block">{{ $librarianData['team_kpi_pct'] }}%</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph-bold ph-user-check"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <div class="flex justify-between text-[11px] font-bold">
                    <span class="text-slate-500">{{ $librarianData['active_pustakawan'] }} Petugas Aktif</span>
                    <span class="text-emerald-600">Respons: {{ $librarianData['queue_resolution_pct'] }}%</span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full relative overflow-hidden" style="width: {{ $librarianData['team_kpi_pct'] }}%">
                        <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-500 block">Skor Keamanan Website</span>
                    <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-0.5 block">{{ $securityMetrics['overall_score_pct'] }}%</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph-bold ph-shield-check"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <div class="flex justify-between text-[11px] font-bold">
                    <span class="text-slate-500">{{ $securityMetrics['security_grade'] }}</span>
                    <span class="text-blue-600">{{ $securityMetrics['protected_records_count'] }} Data</span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-blue-600 rounded-full relative overflow-hidden" style="width: {{ $securityMetrics['overall_score_pct'] }}%">
                        <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-500 block">Akun Terverifikasi OTP</span>
                    <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-0.5 block">{{ $verifiedUsersCount }} / {{ $totalUsers }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph-bold ph-envelope-simple-open"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <div class="flex justify-between text-[11px] font-bold">
                    <span class="text-slate-500">Rasio Keamanan Email</span>
                    <span class="text-indigo-600">{{ $complianceMetrics['email_verified_pct'] }}%</span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-indigo-600 rounded-full relative overflow-hidden" style="width: {{ $complianceMetrics['email_verified_pct'] }}%">
                        <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-500 block">Identitas Warga Sekolah</span>
                    <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-0.5 block">{{ $completeIdentityCount }} / {{ $totalUsers }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph-bold ph-identification-card"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <div class="flex justify-between text-[11px] font-bold">
                    <span class="text-slate-500">Kelengkapan Data</span>
                    <span class="text-amber-600">{{ $complianceMetrics['identity_complete_pct'] }}%</span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-amber-500 rounded-full relative overflow-hidden" style="width: {{ $complianceMetrics['identity_complete_pct'] }}%">
                        <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                    </div>
                </div>
            </div>
        </div>

    </section>

    <!-- Kinerja pustakawan dan keamanan data -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
        
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                            <i class="ph-fill ph-chart-line-up"></i>
                        </span>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                            Pantauan Kinerja Petugas Pustakawan
                        </h3>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Memantau produktivitas kerja, ketuntasan pelayanan persetujuan, dan status akun setiap pustakawan.
                    </p>
                </div>
                <a 
                    href="{{ route('admin.pustakawan.index') }}" 
                    class="self-start sm:self-auto px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-extrabold text-xs inline-flex items-center gap-1 transition-colors shrink-0"
                >
                    <span>Kelola Pustakawan</span>
                    <i class="ph-bold ph-arrow-right"></i>
                </a>
            </div>

            <!-- Ringkasan Beban Kerja Tim Pustakawan -->
            <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Pustakawan</span>
                    <span class="text-lg sm:text-xl font-black text-slate-900 mt-0.5 block">{{ $librarianData['total_pustakawan'] }} Petugas</span>
                    <span class="text-[10px] font-semibold text-emerald-600 block">{{ $librarianData['active_pustakawan'] }} Aktif Bertugas</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Keputusan Layanan</span>
                    <span class="text-lg sm:text-xl font-black text-blue-600 mt-0.5 block">{{ $librarianData['handled_queue_count'] }} Selesai</span>
                    <span class="text-[10px] font-semibold text-slate-500 block">{{ $librarianData['pending_queue_count'] }} Antrean Menunggu</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Rasio Ketuntasan</span>
                    <span class="text-lg sm:text-xl font-black text-emerald-600 mt-0.5 block">{{ $librarianData['queue_resolution_pct'] }}%</span>
                    <span class="text-[10px] font-semibold text-slate-500 block">Target Layanan &ge; 90%</span>
                </div>
            </div>

            <!-- Daftar Kartu KPI Setiap Pustakawan -->
            <div class="space-y-3">
                @forelse($librarianData['staff_list'] as $staff)
                    <div class="p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 bg-slate-50/40 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <span 
                                    class="w-10 h-10 rounded-full flex items-center justify-center font-extrabold text-xs shrink-0"
                                    style="background-color: {{ $staff['palette']['bg'] }}; color: {{ $staff['palette']['text'] }};"
                                >
                                    {{ $staff['initial'] }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 truncate">{{ $staff['name'] }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $staff['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                            {{ $staff['kpi_status'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 truncate">{{ $staff['email'] }} &bull; {{ $staff['last_action_label'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0 text-[11px] font-bold">
                                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700">
                                    {{ $staff['total_decisions'] }} Keputusan
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                    Respons: {{ $staff['avg_response_label'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar KPI Pustakawan -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-[11px] font-bold">
                                <span class="text-slate-500">Skor Produktivitas &amp; Kesiapan Petugas</span>
                                <span class="text-emerald-600">{{ $staff['kpi_score_pct'] }}%</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-200/80 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full relative overflow-hidden" style="width: {{ $staff['kpi_score_pct'] }}%">
                                    <div class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-slate-500 rounded-2xl border border-dashed border-slate-200">
                        Belum ada akun Pustakawan terdaftar. Tambahkan akun Pustakawan melalui menu <strong>Kinerja &amp; Akun Pustakawan</strong>.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right (5 cols): Pusat Pengamanan Data & Pertahanan Website -->
        <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4 flex flex-col justify-between">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3.5">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">
                                <i class="ph-fill ph-lock-key"></i>
                            </span>
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                                Pengamanan Data &amp; Sesi Website
                            </h3>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Kendali keamanan data keseluruhan website, audit sesi login, &amp; pencadangan database.
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-extrabold">
                        Proteksi Aktif
                    </span>
                </div>

                <!-- Indikator Audit Keamanan Data -->
                <div class="grid grid-cols-2 gap-2.5 text-xs">
                    <div class="p-3 rounded-2xl border border-slate-200/80 bg-slate-50/60">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Sesi Login Aktif</span>
                        <span class="text-lg font-black text-slate-900 mt-0.5 block"><span x-text="activeSessions"></span> Perangkat</span>
                        <span class="text-[10px] text-emerald-600 font-semibold">Terpantau Real-Time</span>
                    </div>
                    <div class="p-3 rounded-2xl border border-slate-200/80 bg-slate-50/60">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Data Dilindungi</span>
                        <span class="text-lg font-black text-blue-600 mt-0.5 block">{{ $securityMetrics['protected_records_count'] }} Baris</span>
                        <span class="text-[10px] text-slate-500 font-semibold">Enkripsi Bcrypt Aktif</span>
                    </div>
                    <div class="p-3 rounded-2xl border border-slate-200/80 bg-slate-50/60">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Belum Verifikasi OTP</span>
                        <span class="text-lg font-black {{ $securityMetrics['unverified_accounts_count'] > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-0.5 block">
                            {{ $securityMetrics['unverified_accounts_count'] }} Akun
                        </span>
                        <span class="text-[10px] text-slate-500 font-semibold">Wajib Verifikasi Gmail</span>
                    </div>
                    <div class="p-3 rounded-2xl border border-slate-200/80 bg-slate-50/60">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Akun Dibekukan</span>
                        <span class="text-lg font-black text-rose-600 mt-0.5 block">{{ $securityMetrics['suspended_accounts_count'] }} Akun</span>
                        <span class="text-[10px] text-slate-500 font-semibold">Akses Diblokir Admin</span>
                    </div>
                </div>

                <!-- Tombol Aksi Keamanan Cepat -->
                <div class="space-y-2 pt-1">
                    <a 
                        href="{{ route('admin.security.backup') }}" 
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs flex items-center justify-between transition-colors"
                    >
                        <span class="inline-flex items-center gap-2">
                            <i class="ph-bold ph-database text-base text-cyan-400"></i>
                            <span>Unduh Cadangan Data Sistem (Backup JSON)</span>
                        </span>
                        <i class="ph-bold ph-download-simple"></i>
                    </a>

                    <button 
                        type="button"
                        @click="purgeSessions()"
                        :disabled="busyAction"
                        class="w-full px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-extrabold text-xs flex items-center justify-between transition-colors"
                    >
                        <span class="inline-flex items-center gap-2">
                            <i class="ph-bold ph-shield-warning text-base"></i>
                            <span>Putuskan Semua Sesi Perangkat Lain</span>
                        </span>
                        <i class="ph-bold ph-sign-out"></i>
                    </button>

                    <button 
                        type="button"
                        @click="optimizeSystem()"
                        :disabled="busyAction"
                        class="w-full px-4 py-2.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-extrabold text-xs flex items-center justify-between transition-colors"
                    >
                        <span class="inline-flex items-center gap-2">
                            <i class="ph-bold ph-arrows-clockwise text-base"></i>
                            <span>Segarkan Cache &amp; Konfigurasi Keamanan</span>
                        </span>
                        <i class="ph-bold ph-check-circle"></i>
                    </button>
                </div>
            </div>

            <div class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-100 text-[11px] text-indigo-900 flex items-start gap-2">
                <i class="ph-fill ph-info text-indigo-600 text-base shrink-0 mt-0.5"></i>
                <span>
                    Komposisi Otoritas Saat Ini: <strong>{{ $totalAdmins }} Admin</strong>, <strong>{{ $totalPustakawan }} Pustakawan</strong>, dan <strong>{{ $totalStudents }} Akun Warga Sekolah</strong>.
                </span>
            </div>
        </div>

    </section>

</div>
@endsection
