@extends('pustakawan.layouts.pustakawan')

@section('title', 'Laporan & Persetujuan Peminjaman Buku')
@section('page-title', 'Laporan & Persetujuan Peminjaman Buku')

@section('content')
<div 
    x-data="{
        transactions: {{ Js::from($loanTransactions) }},
        metrics: {{ Js::from($reportMetrics) }},
        activeTab: '{{ in_array($initialTab, ['all', 'menunggu_approval', 'penting', 'sedang_dipinjam', 'terlambat', 'selesai', 'ditolak']) ? $initialTab : 'all' }}',
        searchLoan: '',
        busyTrx: null,
        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },
        countByTab(tab) {
            const list = this.transactions || [];
            if (tab === 'all') return list.length;
            if (tab === 'menunggu_approval') return list.filter(t => t.status === 'menunggu_approval').length;
            if (tab === 'penting') return list.filter(t => t.prioritas === 'penting').length;
            if (tab === 'sedang_dipinjam') return list.filter(t => t.status === 'sedang_dipinjam').length;
            if (tab === 'terlambat') return list.filter(t => t.status === 'terlambat').length;
            if (tab === 'selesai') return list.filter(t => t.status === 'selesai').length;
            if (tab === 'ditolak') return list.filter(t => t.status === 'ditolak').length;
            return 0;
        },
        get filteredTransactions() {
            return (this.transactions || []).filter(t => {
                if (this.activeTab === 'menunggu_approval' && t.status !== 'menunggu_approval') return false;
                if (this.activeTab === 'penting' && t.prioritas !== 'penting') return false;
                if (this.activeTab === 'sedang_dipinjam' && t.status !== 'sedang_dipinjam') return false;
                if (this.activeTab === 'terlambat' && t.status !== 'terlambat') return false;
                if (this.activeTab === 'selesai' && t.status !== 'selesai') return false;
                if (this.activeTab === 'ditolak' && t.status !== 'ditolak') return false;

                if (!this.searchLoan.trim()) return true;
                const q = this.searchLoan.toLowerCase();
                const inBorrower = (t.borrower.name || '').toLowerCase().includes(q)
                    || (t.borrower.nisn || '').toLowerCase().includes(q)
                    || (t.borrower.nis || '').toLowerCase().includes(q)
                    || (t.borrower.nama_kelas || '').toLowerCase().includes(q)
                    || (t.kode_transaksi || '').toLowerCase().includes(q);
                const inBooks = (t.books || []).some(b => (b.judul || '').toLowerCase().includes(q));
                return inBorrower || inBooks;
            });
        },
        confirmModalOpen: false,
        confirmMode: 'approve',
        confirmTargetTrx: null,
        rejectReason: 'Identitas NIS/NISN belum sesuai dengan data siswa aktif SMK Negeri 5 Surakarta.',
        openConfirmModal(trx, mode = 'approve') {
            this.confirmTargetTrx = trx;
            this.confirmMode = mode;
            if (mode === 'reject') {
                this.rejectReason = 'Identitas NIS/NISN belum sesuai dengan data siswa aktif SMK Negeri 5 Surakarta.';
            }
            this.confirmModalOpen = true;
        },
        closeConfirmModal() {
            if (this.busyTrx) return;
            this.confirmModalOpen = false;
            this.confirmTargetTrx = null;
        },
        async approveTrx(kode) {
            if (this.busyTrx) return;
            this.busyTrx = kode;
            try {
                const res = await fetch(`/pustakawan/peminjaman/${encodeURIComponent(kode)}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.transactions = data.transactions;
                    this.metrics = data.reportMetrics;
                    this.confirmModalOpen = false;
                    this.confirmTargetTrx = null;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal menyetujui peminjaman.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyTrx = null;
            }
        },
        async rejectTrx(kode) {
            if (this.busyTrx) return;
            this.busyTrx = kode;
            try {
                const res = await fetch(`/pustakawan/peminjaman/${encodeURIComponent(kode)}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ catatan_pustakawan: this.rejectReason })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.transactions = data.transactions;
                    this.metrics = data.reportMetrics;
                    this.confirmModalOpen = false;
                    this.confirmTargetTrx = null;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal menolak peminjaman.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyTrx = null;
            }
        },
        async completeTrx(kode) {
            if (this.busyTrx) return;
            this.busyTrx = kode;
            try {
                const res = await fetch(`/pustakawan/peminjaman/${encodeURIComponent(kode)}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.transactions = data.transactions;
                    this.metrics = data.reportMetrics;
                    this.confirmModalOpen = false;
                    this.confirmTargetTrx = null;
                    $dispatch('notify-toast', { message: data.message, type: 'success' });
                } else {
                    $dispatch('notify-toast', { message: data.message || 'Gagal memproses pengembalian.', type: 'error' });
                }
            } catch (e) {
                $dispatch('notify-toast', { message: 'Gangguan jaringan.', type: 'error' });
            } finally {
                this.busyTrx = null;
            }
        }
    }"
    class="space-y-5 sm:space-y-6"
>

    <!-- Panel Cepat Export Laporan Excel (Data Peminjaman & Data Buku: Sudah Jadi & Raw) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-3.5 sm:p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="ph-bold ph-microsoft-excel-logo"></i>
            </div>
            <div>
                <h2 class="text-xs sm:text-sm font-extrabold text-slate-900">Export Laporan Excel (.xlsx) &mdash; Data Jadi &amp; Data Raw</h2>
                <p class="text-[11px] text-slate-500">Unduh laporan peminjaman atau katalog buku dalam bentuk tabel siap cetak maupun data mentah (raw).</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Export Peminjaman -->
            <div class="inline-flex items-center rounded-xl border border-blue-200 bg-blue-50/60 p-1 gap-1">
                <span class="px-2 text-[11px] font-extrabold text-blue-800 flex items-center gap-1">
                    <i class="ph-bold ph-Arrows-Left-Right"></i>
                    <span>Peminjaman:</span>
                </span>
                <a :href="'{{ route('pustakawan.export.peminjaman') }}?mode=formatted&status=' + activeTab" class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-blue-600 hover:text-white text-blue-700 border border-blue-200/80 text-[11px] font-bold transition-colors shadow-2xs">
                    Data Jadi
                </a>
                <a :href="'{{ route('pustakawan.export.peminjaman') }}?mode=raw&status=' + activeTab" class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-800 hover:text-white text-slate-700 border border-slate-200 text-[11px] font-bold transition-colors shadow-2xs">
                    Data Raw
                </a>
                <a :href="'{{ route('pustakawan.export.peminjaman') }}?mode=both&status=' + activeTab" class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold transition-colors shadow-2xs">
                    Lengkap (2 Sheet)
                </a>
            </div>

            <!-- Export Buku -->
            <div class="inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50/60 p-1 gap-1">
                <span class="px-2 text-[11px] font-extrabold text-emerald-800 flex items-center gap-1">
                    <i class="ph-bold ph-books"></i>
                    <span>Buku:</span>
                </span>
                <a href="{{ route('pustakawan.export.buku', ['mode' => 'formatted']) }}" class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-emerald-600 hover:text-white text-emerald-700 border border-emerald-200/80 text-[11px] font-bold transition-colors shadow-2xs">
                    Data Jadi
                </a>
                <a href="{{ route('pustakawan.export.buku', ['mode' => 'raw']) }}" class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-800 hover:text-white text-slate-700 border border-slate-200 text-[11px] font-bold transition-colors shadow-2xs">
                    Data Raw
                </a>
                <a href="{{ route('pustakawan.export.buku', ['mode' => 'both']) }}" class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition-colors shadow-2xs">
                    Lengkap (2 Sheet)
                </a>
            </div>
        </div>
    </div>

    <!-- Ringkasan status laporan -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3">
        <button type="button" @click="setTab('all')" class="text-left p-3.5 rounded-2xl border transition-all" :class="activeTab === 'all' ? 'bg-slate-900 text-white border-slate-900 shadow-sm' : 'bg-white border-slate-200/80 hover:border-slate-400'">
            <span class="text-[11px] font-bold block opacity-75">Semua Laporan</span>
            <span class="text-xl sm:text-2xl font-black mt-0.5 block" x-text="countByTab('all')"></span>
        </button>

        <button type="button" @click="setTab('menunggu_approval')" class="text-left p-3.5 rounded-2xl border transition-all" :class="activeTab === 'menunggu_approval' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-white border-slate-200/80 hover:border-amber-400'">
            <span class="text-[11px] font-bold block" :class="activeTab === 'menunggu_approval' ? 'text-white/90' : 'text-amber-700'">Butuh Diterima</span>
            <span class="text-xl sm:text-2xl font-black mt-0.5 block" x-text="countByTab('menunggu_approval')"></span>
        </button>

        <button type="button" @click="setTab('sedang_dipinjam')" class="text-left p-3.5 rounded-2xl border transition-all" :class="activeTab === 'sedang_dipinjam' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white border-slate-200/80 hover:border-blue-400'">
            <span class="text-[11px] font-bold block" :class="activeTab === 'sedang_dipinjam' ? 'text-white/90' : 'text-blue-700'">Sedang Dipinjam</span>
            <span class="text-xl sm:text-2xl font-black mt-0.5 block" x-text="countByTab('sedang_dipinjam')"></span>
        </button>

        <button type="button" @click="setTab('terlambat')" class="text-left p-3.5 rounded-2xl border transition-all" :class="activeTab === 'terlambat' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-white border-slate-200/80 hover:border-rose-400'">
            <span class="text-[11px] font-bold block" :class="activeTab === 'terlambat' ? 'text-white/90' : 'text-rose-700'">Terlambat &amp; Denda</span>
            <span class="text-xl sm:text-2xl font-black mt-0.5 block" x-text="countByTab('terlambat')"></span>
        </button>

        <button type="button" @click="setTab('selesai')" class="text-left p-3.5 rounded-2xl border transition-all" :class="activeTab === 'selesai' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white border-slate-200/80 hover:border-emerald-400'">
            <span class="text-[11px] font-bold block" :class="activeTab === 'selesai' ? 'text-white/90' : 'text-emerald-700'">Sudah Dikembalikan</span>
            <span class="text-xl sm:text-2xl font-black mt-0.5 block" x-text="countByTab('selesai')"></span>
        </button>

        <button type="button" @click="setTab('ditolak')" class="text-left p-3.5 rounded-2xl border transition-all" :class="activeTab === 'ditolak' ? 'bg-slate-700 text-white border-slate-700 shadow-sm' : 'bg-white border-slate-200/80 hover:border-slate-400'">
            <span class="text-[11px] font-bold block" :class="activeTab === 'ditolak' ? 'text-white/90' : 'text-slate-600'">Ditolak</span>
            <span class="text-xl sm:text-2xl font-black mt-0.5 block" x-text="countByTab('ditolak')"></span>
        </button>
    </div>

    <!-- Daftar laporan peminjaman -->
    <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-5">
        
        <!-- Filter tab dan pencarian -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
                <button 
                    type="button"
                    @click="setTab('all')"
                    class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all inline-flex items-center gap-1.5"
                    :class="activeTab === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                    <i class="ph-bold ph-list-bullets"></i>
                    <span>Semua</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'" x-text="countByTab('all')"></span>
                </button>

                <button 
                    type="button"
                    @click="setTab('menunggu_approval')"
                    class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all inline-flex items-center gap-1.5"
                    :class="activeTab === 'menunggu_approval' ? 'bg-amber-500 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'"
                >
                    <i class="ph-bold ph-hourglass-high"></i>
                    <span>Butuh Diterima</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'menunggu_approval' ? 'bg-white/25 text-white' : 'bg-amber-200/80 text-amber-900'" x-text="countByTab('menunggu_approval')"></span>
                </button>

                <button 
                    type="button"
                    @click="setTab('sedang_dipinjam')"
                    class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all inline-flex items-center gap-1.5"
                    :class="activeTab === 'sedang_dipinjam' ? 'bg-blue-600 text-white shadow-sm' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                >
                    <i class="ph-bold ph-book-open-text"></i>
                    <span>Sedang Dipinjam</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'sedang_dipinjam' ? 'bg-white/25 text-white' : 'bg-blue-200/70 text-blue-900'" x-text="countByTab('sedang_dipinjam')"></span>
                </button>

                <button 
                    type="button"
                    @click="setTab('terlambat')"
                    class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all inline-flex items-center gap-1.5"
                    :class="activeTab === 'terlambat' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'"
                >
                    <i class="ph-bold ph-warning-octagon"></i>
                    <span>Terlambat &amp; Denda</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'terlambat' ? 'bg-white/25 text-white' : 'bg-rose-200/70 text-rose-900'" x-text="countByTab('terlambat')"></span>
                </button>

                <button 
                    type="button"
                    @click="setTab('selesai')"
                    class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all inline-flex items-center gap-1.5"
                    :class="activeTab === 'selesai' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                >
                    <i class="ph-bold ph-check-circle"></i>
                    <span>Sudah Dikembalikan</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'selesai' ? 'bg-white/25 text-white' : 'bg-emerald-200/70 text-emerald-900'" x-text="countByTab('selesai')"></span>
                </button>

                <button 
                    type="button"
                    @click="setTab('ditolak')"
                    class="px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all inline-flex items-center gap-1.5"
                    :class="activeTab === 'ditolak' ? 'bg-slate-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                    <i class="ph-bold ph-x-circle"></i>
                    <span>Ditolak</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'ditolak' ? 'bg-white/25 text-white' : 'bg-slate-200 text-slate-700'" x-text="countByTab('ditolak')"></span>
                </button>
            </div>

            <!-- Search Input -->
            <div class="relative shrink-0">
                <i class="ph ph-magnifying-glass text-slate-400 text-sm absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input 
                    type="text"
                    x-model="searchLoan"
                    placeholder="Cari kode TRX, nama siswa, NISN, buku..."
                    class="w-full lg:w-64 pl-8 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold outline-none focus:border-emerald-500"
                >
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="filteredTransactions.length === 0" class="py-14 text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                <i class="ph ph-tray"></i>
            </div>
            <p class="text-xs sm:text-sm font-extrabold text-slate-800">Tidak ada data laporan pada tab ini.</p>
            <p class="text-[11px] text-slate-500">Pilih tab status lain di atas atau ubah kata kunci pencarian.</p>
        </div>

        <!-- Detailed Loan Cards -->
        <div class="space-y-4">
            <template x-for="trx in filteredTransactions" :key="trx.kode_transaksi">
                <article 
                    class="rounded-2xl border p-4 sm:p-5 space-y-4 transition-all"
                    :class="trx.status === 'menunggu_approval'
                        ? 'border-amber-300 bg-amber-50/15 shadow-sm'
                        : (trx.status === 'terlambat' ? 'border-rose-300 bg-rose-50/15' : 'border-slate-200/90 bg-white')"
                >
                    <!-- Header: Code, Status, Dates, Denda, Action Buttons -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-extrabold font-mono text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-md border border-blue-200">
                                    #<span x-text="trx.kode_transaksi"></span>
                                </span>

                                <span 
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border"
                                    :class="{
                                        'bg-amber-50 text-amber-700 border-amber-200': trx.status === 'menunggu_approval',
                                        'bg-blue-50 text-blue-700 border-blue-200': trx.status === 'sedang_dipinjam',
                                        'bg-rose-50 text-rose-700 border-rose-200': trx.status === 'terlambat' || trx.status === 'ditolak',
                                        'bg-emerald-50 text-emerald-700 border-emerald-200': trx.status === 'selesai'
                                    }"
                                    x-text="trx.status_label"
                                ></span>

                                <template x-if="trx.overdue_days > 0">
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-600 text-white text-[10px] font-extrabold inline-flex items-center gap-1">
                                        <i class="ph-fill ph-warning-octagon"></i>
                                        Terlambat <span x-text="trx.overdue_days"></span> Hari &bull; Denda <span x-text="trx.denda_formatted"></span>
                                    </span>
                                </template>
                            </div>

                            <div class="text-xs text-slate-500 flex flex-wrap items-center gap-x-4 gap-y-1 pt-0.5">
                                <span><i class="ph-bold ph-calendar-blank"></i> Tanggal Pinjam: <strong class="text-slate-800" x-text="trx.tanggal_pengajuan"></strong></span>
                                <span><i class="ph-bold ph-clock"></i> Batas Kembali (7 Hari): <strong class="text-slate-800" x-text="trx.tenggat_kembali"></strong></span>
                                <template x-if="trx.tanggal_kembali">
                                    <span><i class="ph-bold ph-check-circle text-emerald-600"></i> Dikembalikan: <strong class="text-emerald-700" x-text="trx.tanggal_kembali"></strong></span>
                                </template>
                            </div>
                        </div>

                        <!-- Instant AJAX Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <template x-if="trx.status === 'menunggu_approval'">
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <button 
                                        type="button"
                                        @click="openConfirmModal(trx, 'approve')"
                                        :disabled="busyTrx === trx.kode_transaksi"
                                        class="flex-1 sm:flex-initial px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs inline-flex items-center justify-center gap-1.5 shadow-sm transition-colors disabled:opacity-50"
                                    >
                                        <i class="ph-bold" :class="busyTrx === trx.kode_transaksi ? 'ph-spinner animate-spin' : 'ph-check-circle'"></i>
                                        <span>Terima Peminjaman</span>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="openConfirmModal(trx, 'reject')"
                                        :disabled="busyTrx === trx.kode_transaksi"
                                        class="px-3.5 py-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs inline-flex items-center justify-center gap-1 transition-colors disabled:opacity-50"
                                    >
                                        <i class="ph-bold ph-x-circle"></i>
                                        <span>Tolak</span>
                                    </button>
                                </div>
                            </template>

                            <template x-if="trx.status === 'sedang_dipinjam' || trx.status === 'terlambat'">
                                <button 
                                    type="button"
                                    @click="openConfirmModal(trx, 'complete')"
                                    :disabled="busyTrx === trx.kode_transaksi"
                                    class="w-full sm:w-auto px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs inline-flex items-center justify-center gap-1.5 shadow-sm transition-colors disabled:opacity-50"
                                >
                                    <i class="ph-bold" :class="busyTrx === trx.kode_transaksi ? 'ph-spinner animate-spin' : 'ph-arrow-u-up-left'"></i>
                                    <span>Tandai Sudah Dikembalikan</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Progress Bar Alur Peminjaman Card -->
                    <div class="space-y-1.5 bg-slate-50 rounded-xl p-3 border border-slate-200/60">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-slate-600">Tahap Progres Alur Peminjaman (<span x-text="trx.step"></span>/4)</span>
                            <span class="text-blue-600 font-extrabold" x-text="trx.progress_pct + '%'"></span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-200 overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all duration-500 relative overflow-hidden"
                                :class="trx.status === 'selesai' ? 'bg-emerald-500' : (trx.status === 'ditolak' || trx.status === 'terlambat' ? 'bg-rose-500' : 'bg-blue-600')"
                                :style="`width: ${trx.progress_pct}%`"
                            >
                                <div x-show="trx.status !== 'selesai'" class="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer-bar"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail peminjam dan buku -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        
                        <!-- Identitas peminjam -->
                        <div class="lg:col-span-5 rounded-2xl bg-slate-50/90 border border-slate-200/80 p-3.5 space-y-2.5 text-xs">
                            <div class="flex items-center justify-between border-b border-slate-200/70 pb-2">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                                    DETAIL LENGKAP PEMINJAM
                                </span>
                                <span class="text-[11px] font-bold text-emerald-700" x-text="trx.borrower.nama_sekolah"></span>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <span 
                                    class="w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-xs shrink-0"
                                    :style="`background-color: ${trx.borrower.palette.bg}; color: ${trx.borrower.palette.text};`"
                                    x-text="trx.borrower.initial"
                                ></span>
                                <div class="min-w-0">
                                    <p class="font-extrabold text-slate-900 truncate" x-text="trx.borrower.name"></p>
                                    <p class="text-[11px] text-slate-500 truncate" x-text="trx.borrower.email"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-1 text-[11px]">
                                <div class="p-2 rounded-lg bg-white border border-slate-200/60">
                                    <span class="text-[10px] text-slate-400 font-bold block">NISN (10 Digit)</span>
                                    <span class="font-mono font-extrabold text-blue-600" x-text="trx.borrower.nisn"></span>
                                </div>
                                <div class="p-2 rounded-lg bg-white border border-slate-200/60">
                                    <span class="text-[10px] text-slate-400 font-bold block">NIS &amp; Kelas</span>
                                    <span class="font-bold text-slate-800" x-text="trx.borrower.nis + ' / ' + trx.borrower.nama_kelas"></span>
                                </div>
                                <div class="p-2 rounded-lg bg-white border border-slate-200/60">
                                    <span class="text-[10px] text-slate-400 font-bold block">Tahun Ajaran &amp; Lahir</span>
                                    <span class="font-bold text-slate-800" x-text="trx.borrower.tahun_ajaran + ' • ' + trx.borrower.tanggal_lahir"></span>
                                </div>
                                <div class="p-2 rounded-lg bg-white border border-slate-200/60">
                                    <span class="text-[10px] text-slate-400 font-bold block">Telepon / WhatsApp</span>
                                    <span class="font-bold text-emerald-700" x-text="trx.borrower.no_whatsapp"></span>
                                </div>
                                <div class="col-span-2 p-2 rounded-lg bg-white border border-slate-200/60">
                                    <span class="text-[10px] text-slate-400 font-bold block">Alamat Tempat Tinggal</span>
                                    <span class="font-semibold text-slate-700" x-text="trx.borrower.alamat"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Right (7 cols): Daftar Buku yang Dipinjam -->
                        <div class="lg:col-span-7 rounded-2xl bg-slate-50/50 border border-slate-200/80 p-3.5 space-y-2.5">
                            <div class="flex items-center justify-between border-b border-slate-200/70 pb-2">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                                    DAFTAR BUKU YANG DIPINJAM (<span x-text="trx.total_books"></span> EKSEMPLAR)
                                </span>
                                <template x-if="trx.catatan_peminjam">
                                    <span class="text-[11px] italic text-slate-600" x-text="'Catatan: ' + trx.catatan_peminjam"></span>
                                </template>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="b in trx.books" :key="b.loan_id">
                                    <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 flex items-center gap-3">
                                        <img :src="b.cover_url" :alt="b.judul" class="w-11 h-15 object-cover rounded-lg bg-slate-100 shrink-0">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-extrabold text-slate-900 truncate" x-text="b.judul"></p>
                                            <p class="text-[11px] text-slate-500 truncate" x-text="b.pengarang"></p>
                                            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-extrabold">
                                                    <span x-text="b.jumlah"></span> Buku
                                                </span>
                                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-semibold" x-text="b.kategori"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </article>
            </template>
        </div>

    </section>

    <!-- Card Popup Konfirmasi Persetujuan, Penolakan, & Pengembalian Buku -->
    <div
        x-show="confirmModalOpen && confirmTargetTrx"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="closeConfirmModal()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
    >
        <div
            @click.outside="closeConfirmModal()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="w-full max-w-lg rounded-3xl bg-white border border-slate-200 shadow-2xl overflow-hidden"
        >
            <template x-if="confirmTargetTrx">
                <div>
                    <!-- Header Modal -->
                    <div
                        class="px-5 py-4 text-white flex items-start justify-between gap-3"
                        :class="confirmMode === 'approve' ? 'bg-gradient-to-r from-emerald-600 to-teal-600' : (confirmMode === 'reject' ? 'bg-gradient-to-r from-rose-600 to-red-600' : 'bg-gradient-to-r from-blue-600 to-indigo-600')"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-xl shrink-0">
                                <i class="ph-bold" :class="confirmMode === 'approve' ? 'ph-shield-check' : (confirmMode === 'reject' ? 'ph-x-circle' : 'ph-arrow-u-up-left')"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3
                                        class="text-sm sm:text-base font-extrabold tracking-tight"
                                        x-text="confirmMode === 'approve' ? 'Konfirmasi Persetujuan Peminjaman' : (confirmMode === 'reject' ? 'Konfirmasi Penolakan Peminjaman' : 'Konfirmasi Pengembalian Buku')"
                                    ></h3>
                                    <span class="px-2 py-0.5 rounded-md bg-white/20 font-mono text-[11px] font-bold" x-text="'#' + confirmTargetTrx.kode_transaksi"></span>
                                </div>
                                <p
                                    class="text-[11px] text-white/90 mt-0.5"
                                    x-text="confirmMode === 'approve' ? 'Periksa kesesuaian NIS, NISN, dan Kelas siswa sebelum menyetujui.' : (confirmMode === 'reject' ? 'Stok buku akan dikembalikan otomatis ke katalog perpustakaan.' : 'Pastikan buku fisik telah diterima dengan lengkap di meja sirkulasi.')"
                                ></p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeConfirmModal()"
                            class="w-8 h-8 rounded-xl bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition-colors shrink-0"
                        >
                            <i class="ph-bold ph-x text-sm"></i>
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                        <!-- Banner Peringatan Verifikasi Siswa SMKN 5 -->
                        <div x-show="confirmMode === 'approve'" class="p-3 rounded-2xl bg-amber-50 border border-amber-200/90 flex items-start gap-2.5">
                            <i class="ph-fill ph-warning-circle text-amber-600 text-base shrink-0 mt-0.5"></i>
                            <p class="text-[11px] text-amber-900 leading-relaxed">
                                <strong>Cek Keaslian Siswa:</strong> Pastikan <strong>NIS</strong> dan <strong>NISN</strong> di bawah benar merupakan siswa aktif <strong>SMK Negeri 5 Surakarta</strong> untuk mencegah peminjaman oleh pihak luar.
                            </p>
                        </div>

                        <!-- Kartu Data Identitas & NIS Peminjam -->
                        <div class="rounded-2xl bg-slate-50 border border-slate-200/90 p-3.5 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200/70 pb-2">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                                    IDENTITAS SISWA PEMINJAM
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-extrabold" x-text="confirmTargetTrx.borrower.nama_sekolah"></span>
                            </div>

                            <div class="flex items-center gap-3">
                                <span
                                    class="w-10 h-10 rounded-full flex items-center justify-center font-extrabold text-sm shrink-0"
                                    :style="`background-color: ${confirmTargetTrx.borrower.palette.bg}; color: ${confirmTargetTrx.borrower.palette.text};`"
                                    x-text="confirmTargetTrx.borrower.initial"
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-extrabold text-slate-900 truncate" x-text="confirmTargetTrx.borrower.name"></p>
                                    <p class="text-xs text-slate-500 truncate" x-text="confirmTargetTrx.borrower.email"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                                <div class="p-2.5 rounded-xl bg-white border-2 border-emerald-200">
                                    <span class="text-[10px] font-bold text-slate-400 block">NIS Siswa</span>
                                    <span class="font-mono font-black text-sm text-emerald-700" x-text="confirmTargetTrx.borrower.nis"></span>
                                </div>
                                <div class="p-2.5 rounded-xl bg-white border border-slate-200">
                                    <span class="text-[10px] font-bold text-slate-400 block">NISN (10 Digit)</span>
                                    <span class="font-mono font-extrabold text-xs text-blue-600" x-text="confirmTargetTrx.borrower.nisn"></span>
                                </div>
                                <div class="col-span-2 sm:col-span-1 p-2.5 rounded-xl bg-white border border-slate-200">
                                    <span class="text-[10px] font-bold text-slate-400 block">Kelas &amp; Jurusan</span>
                                    <span class="font-extrabold text-xs text-slate-800" x-text="confirmTargetTrx.borrower.nama_kelas"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Denda jika Terlambat saat Complete -->
                        <template x-if="confirmMode === 'complete' && confirmTargetTrx.overdue_days > 0">
                            <div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-rose-800">
                                    <i class="ph-fill ph-warning-octagon text-lg text-rose-600"></i>
                                    <span class="text-xs font-bold">Terlambat <strong x-text="confirmTargetTrx.overdue_days"></strong> Hari</span>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg bg-rose-600 text-white font-extrabold text-xs" x-text="'Denda: ' + confirmTargetTrx.denda_formatted"></span>
                            </div>
                        </template>

                        <!-- Ringkasan Buku yang Dipinjam -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-extrabold text-slate-700">
                                    Daftar Buku (<span x-text="confirmTargetTrx.total_books"></span> Eksemplar)
                                </span>
                                <span class="text-[11px] text-slate-500">
                                    Tenggat: <strong class="text-slate-800" x-text="confirmTargetTrx.tenggat_kembali"></strong>
                                </span>
                            </div>

                            <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                <template x-for="b in confirmTargetTrx.books" :key="b.loan_id">
                                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                                        <img :src="b.cover_url" :alt="b.judul" class="w-9 h-12 object-cover rounded-lg bg-slate-200 shrink-0">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-extrabold text-slate-900 truncate" x-text="b.judul"></p>
                                            <p class="text-[11px] text-slate-500 truncate" x-text="b.pengarang"></p>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-extrabold text-[11px] shrink-0">
                                            <span x-text="b.jumlah"></span> Buku
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Form Alasan Penolakan (Muncul jika mode === 'reject') -->
                        <div x-show="confirmMode === 'reject'" x-transition class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 space-y-2">
                            <label class="block text-xs font-extrabold text-rose-800">
                                Catatan Penolakan untuk Peminjam:
                            </label>
                            <textarea
                                x-model="rejectReason"
                                rows="2"
                                class="w-full rounded-xl bg-white border border-rose-200 px-3 py-2 text-xs text-slate-800 outline-none focus:border-rose-500"
                                placeholder="Tuliskan alasan penolakan..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer Tombol Konfirmasi -->
                    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
                        <button
                            type="button"
                            @click="closeConfirmModal()"
                            :disabled="busyTrx === confirmTargetTrx.kode_transaksi"
                            class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors"
                        >
                            Batal
                        </button>

                        <div class="flex items-center gap-2">
                            <template x-if="confirmMode === 'approve'">
                                <button
                                    type="button"
                                    @click="confirmMode = 'reject'"
                                    :disabled="busyTrx === confirmTargetTrx.kode_transaksi"
                                    class="px-3.5 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs inline-flex items-center gap-1 transition-colors"
                                >
                                    <i class="ph-bold ph-x-circle"></i>
                                    <span>Tolak</span>
                                </button>
                            </template>

                            <template x-if="confirmMode === 'approve'">
                                <button
                                    type="button"
                                    @click="approveTrx(confirmTargetTrx.kode_transaksi)"
                                    :disabled="busyTrx === confirmTargetTrx.kode_transaksi"
                                    class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors disabled:opacity-50"
                                >
                                    <i class="ph-bold text-sm" :class="busyTrx === confirmTargetTrx.kode_transaksi ? 'ph-spinner animate-spin' : 'ph-check-circle'"></i>
                                    <span x-text="busyTrx === confirmTargetTrx.kode_transaksi ? 'Menyetujui...' : 'Ya, NIS Sesuai & Setujui'"></span>
                                </button>
                            </template>

                            <template x-if="confirmMode === 'reject'">
                                <button
                                    type="button"
                                    @click="rejectTrx(confirmTargetTrx.kode_transaksi)"
                                    :disabled="busyTrx === confirmTargetTrx.kode_transaksi"
                                    class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors disabled:opacity-50"
                                >
                                    <i class="ph-bold text-sm" :class="busyTrx === confirmTargetTrx.kode_transaksi ? 'ph-spinner animate-spin' : 'ph-x-circle'"></i>
                                    <span x-text="busyTrx === confirmTargetTrx.kode_transaksi ? 'Menolak...' : 'Ya, Tolak Pengajuan'"></span>
                                </button>
                            </template>

                            <template x-if="confirmMode === 'complete'">
                                <button
                                    type="button"
                                    @click="completeTrx(confirmTargetTrx.kode_transaksi)"
                                    :disabled="busyTrx === confirmTargetTrx.kode_transaksi"
                                    class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs inline-flex items-center gap-1.5 shadow-sm transition-colors disabled:opacity-50"
                                >
                                    <i class="ph-bold text-sm" :class="busyTrx === confirmTargetTrx.kode_transaksi ? 'ph-spinner animate-spin' : 'ph-check-circle'"></i>
                                    <span x-text="busyTrx === confirmTargetTrx.kode_transaksi ? 'Memproses...' : 'Ya, Buku Sudah Dikembalikan'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection

