<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTask;
use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->ensureDefaultAdminTasks();

        $users = User::latest()->get();
        $adminTasks = AdminTask::orderByRaw("CASE WHEN prioritas = 'penting' THEN 0 ELSE 1 END")
            ->orderBy('is_completed')
            ->latest()
            ->get();

        $totalUsers = $users->count();
        $totalStudents = $users->where('role', 'user')->count();
        $totalPustakawan = $users->where('role', 'pustakawan')->count();
        $totalAdmins = $users->where('role', 'admin')->count();

        $verifiedUsersCount = $users->filter(fn (User $u) => $u->isEmailVerified())->count();
        $completeIdentityCount = $users->filter(fn (User $u) => $u->isSchoolIdentityComplete())->count();
        $activeUsersCount = $users->where('is_active', true)->count();

        $totalTasks = $adminTasks->count();
        $completedTasks = $adminTasks->where('is_completed', true)->count();
        $avgTaskProgress = $totalTasks > 0 ? (int) round($adminTasks->avg('progres')) : 0;

        $librarianData = $this->buildLibrarianPerformance($users);
        $securityMetrics = $this->buildSecurityMetrics($users, $avgTaskProgress);

        $complianceMetrics = [
            'email_verified_pct' => $totalUsers > 0 ? (int) round(($verifiedUsersCount / $totalUsers) * 100) : 100,
            'identity_complete_pct' => $totalUsers > 0 ? (int) round(($completeIdentityCount / $totalUsers) * 100) : 100,
            'active_users_pct' => $totalUsers > 0 ? (int) round(($activeUsersCount / $totalUsers) * 100) : 100,
            'task_progress_pct' => $avgTaskProgress,
        ];

        return view('admin.dashboard', compact(
            'users',
            'totalUsers',
            'totalStudents',
            'totalPustakawan',
            'totalAdmins',
            'verifiedUsersCount',
            'completeIdentityCount',
            'activeUsersCount',
            'adminTasks',
            'totalTasks',
            'completedTasks',
            'complianceMetrics',
            'librarianData',
            'securityMetrics'
        ));
    }

    public function pustakawanIndex(): View
    {
        $users = User::latest()->get();
        $librarianData = $this->buildLibrarianPerformance($users);

        $staffAccounts = $users
            ->whereIn('role', ['pustakawan', 'admin'])
            ->map(fn (User $u) => $this->formatUserPayload($u, $librarianData['by_user_id'][$u->id] ?? null))
            ->values();

        return view('admin.pustakawan', compact(
            'librarianData',
            'staffAccounts'
        ));
    }

    public function usersIndex(): View
    {
        $users = User::latest()->get();

        $totalUsers = $users->count();
        $totalStudents = $users->where('role', 'user')->count();
        $totalPustakawan = $users->where('role', 'pustakawan')->count();
        $totalAdmins = $users->where('role', 'admin')->count();
        $verifiedUsersCount = $users->filter(fn (User $u) => $u->isEmailVerified())->count();
        $completeIdentityCount = $users->filter(fn (User $u) => $u->isSchoolIdentityComplete())->count();
        $activeUsersCount = $users->where('is_active', true)->count();

        $usersPayload = $users->map(fn (User $u) => $this->formatUserPayload($u))->values();

        return view('admin.users', compact(
            'users',
            'usersPayload',
            'totalUsers',
            'totalStudents',
            'totalPustakawan',
            'totalAdmins',
            'verifiedUsersCount',
            'completeIdentityCount',
            'activeUsersCount'
        ));
    }

    public function tasksIndex(): View
    {
        $this->ensureDefaultAdminTasks();

        $users = User::latest()->get();
        $adminTasks = AdminTask::orderByRaw("CASE WHEN prioritas = 'penting' THEN 0 ELSE 1 END")
            ->orderBy('is_completed')
            ->latest()
            ->get();

        $totalTasks = $adminTasks->count();
        $completedTasks = $adminTasks->where('is_completed', true)->count();
        $avgTaskProgress = $totalTasks > 0 ? (int) round($adminTasks->avg('progres')) : 0;

        $securityMetrics = $this->buildSecurityMetrics($users, $avgTaskProgress);

        return view('admin.tasks', compact(
            'adminTasks',
            'totalTasks',
            'completedTasks',
            'avgTaskProgress',
            'securityMetrics'
        ));
    }

    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/\d/'],
            'role' => ['required', 'in:user,pustakawan,admin'],
            'nisn' => ['nullable', 'regex:/^\d{10}$/'],
            'nis' => ['nullable', 'string', 'max:5'],
            'nama_kelas' => ['nullable', 'string', 'max:60'],
            'tahun_ajaran' => ['nullable', 'string', 'max:25'],
            'no_whatsapp' => ['nullable', 'string', 'max:25'],
            'no_telepon' => ['nullable', 'string', 'max:25'],
        ], [
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.regex' => 'Kata sandi wajib mengandung huruf dan minimal 1 angka.',
            'nisn.regex' => 'NISN wajib tepat 10 digit angka.',
            'nis.max' => 'NIS maksimal 5 karakter sesuai format umum sekolah.',
        ]);

        $baseUsername = Str::slug($validated['name'], '');
        if ($baseUsername === '') {
            $baseUsername = 'wargasekolah';
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $baseUsername.random_int(10, 999),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => now(),
            'is_active' => true,
            'profile_color' => $validated['role'] === 'pustakawan' ? 'emerald' : ($validated['role'] === 'admin' ? 'indigo' : 'blue'),
            'nama_sekolah' => 'SMK Negeri 5 Surakarta',
            'nisn' => $validated['nisn'] ?? null,
            'nis' => $validated['nis'] ?? null,
            'nama_kelas' => $validated['nama_kelas'] ?? ($validated['role'] === 'pustakawan' ? 'Petugas Perpustakaan' : ($validated['role'] === 'admin' ? 'Administrator Sistem' : null)),
            'tahun_ajaran' => $validated['tahun_ajaran'] ?? '2025/2026',
            'no_whatsapp' => $validated['no_whatsapp'] ?? null,
            'no_telepon' => $validated['no_telepon'] ?? ($validated['no_whatsapp'] ?? null),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil ditambahkan dan diamankan di sistem.',
            'user' => $this->formatUserPayload($user),
        ]);
    }

    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:user,pustakawan,admin'],
        ]);

        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menurunkan role akun Admin Anda sendiri.',
            ], 422);
        }

        $user->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => "Role {$user->name} berhasil diubah menjadi ".strtoupper($validated['role']).'.',
            'user' => $this->formatUserPayload($user->fresh()),
        ]);
    }

    public function toggleUserActive(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menonaktifkan akun Admin yang sedang Anda gunakan.',
            ], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active
                ? "Akses akun {$user->name} telah diaktifkan kembali."
                : "Akses akun {$user->name} telah dibekukan sementara demi keamanan.",
            'user' => $this->formatUserPayload($user->fresh()),
        ]);
    }

    public function verifyUserManual(User $user): JsonResponse
    {
        $user->update([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Email {$user->email} berhasil diverifikasi secara manual oleh Admin.",
            'user' => $this->formatUserPayload($user->fresh()),
        ]);
    }

    public function resetUserPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/\d/'],
        ], [
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.regex' => 'Kata sandi wajib mengandung huruf dan minimal 1 angka.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Kata sandi untuk akun {$user->name} ({$user->email}) berhasil diperbarui oleh Admin.",
        ]);
    }

    public function deleteUser(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun sendiri.',
            ], 422);
        }

        $name = $user->name;
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Akun {$name} berhasil dihapus dari sistem.",
        ]);
    }

    public function downloadSecurityBackup(): StreamedResponse
    {
        $filename = 'backup-keamanan-pinjam-bupus-'.now()->format('Y-m-d-His').'.json';

        $payload = [
            'metadata' => [
                'application' => 'PINJAM BU-PUS SMK Negeri 5 Surakarta',
                'exported_at' => now()->toIso8601String(),
                'exported_by' => auth()->user()?->email,
            ],
            'summary' => [
                'total_users' => User::count(),
                'total_admins' => User::where('role', 'admin')->count(),
                'total_pustakawan' => User::where('role', 'pustakawan')->count(),
                'total_warga_sekolah' => User::where('role', 'user')->count(),
                'total_admin_tasks' => AdminTask::count(),
            ],
            'accounts' => User::select([
                'id',
                'name',
                'username',
                'email',
                'role',
                'is_active',
                'email_verified_at',
                'nisn',
                'nis',
                'nama_sekolah',
                'nama_kelas',
                'tahun_ajaran',
                'no_telepon',
                'no_whatsapp',
                'created_at',
            ])->get(),
            'admin_tasks' => AdminTask::all(),
        ];

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function purgeOtherSessions(Request $request): JsonResponse
    {
        $purged = 0;

        if (Schema::hasTable('sessions')) {
            $currentSessionId = $request->session()->getId();
            $purged = DB::table('sessions')
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "Keamanan sesi diperbarui. {$purged} sesi login perangkat lain telah diputus.",
            'purged_count' => $purged,
        ]);
    }

    public function optimizeSystemSecurity(): JsonResponse
    {
        Artisan::call('optimize:clear');

        return response()->json([
            'success' => true,
            'message' => 'Cache aplikasi, rute, dan konfigurasi keamanan website berhasil disegarkan.',
        ]);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:160'],
            'deskripsi' => ['nullable', 'string', 'max:600'],
            'kategori_tugas' => ['required', 'string', 'max:60'],
            'prioritas' => ['required', 'in:penting,normal'],
        ]);

        $task = AdminTask::create([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'kategori_tugas' => $validated['kategori_tugas'],
            'prioritas' => $validated['prioritas'],
            'progres' => 0,
            'is_completed' => false,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas operasional Admin berhasil ditambahkan.',
            'task' => $task,
        ]);
    }

    public function updateTaskProgress(Request $request, AdminTask $task): JsonResponse
    {
        $validated = $request->validate([
            'progres' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $progres = (int) $validated['progres'];
        $task->update([
            'progres' => $progres,
            'is_completed' => $progres >= 100,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progres tugas Admin diperbarui.',
            'task' => $task->fresh(),
        ]);
    }

    public function toggleTaskComplete(AdminTask $task): JsonResponse
    {
        $newCompleted = ! $task->is_completed;
        $task->update([
            'is_completed' => $newCompleted,
            'progres' => $newCompleted ? 100 : ($task->progres >= 100 ? 50 : $task->progres),
        ]);

        return response()->json([
            'success' => true,
            'message' => $newCompleted ? 'Tugas ditandai selesai (100%).' : 'Status tugas dibuka kembali.',
            'task' => $task->fresh(),
        ]);
    }

    public function deleteTask(AdminTask $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tugas operasional Admin berhasil dihapus.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLibrarianPerformance($users): array
    {
        $pustakawanUsers = $users->where('role', 'pustakawan')->values();
        $adminUsers = $users->where('role', 'admin')->values();

        $allLoans = Peminjaman::select(['id', 'status', 'approved_by', 'approved_at', 'created_at'])->get();
        $totalQueueCount = $allLoans->count();
        $pendingQueueCount = $allLoans->where('status', 'menunggu_approval')->count();
        $handledQueueCount = max(0, $totalQueueCount - $pendingQueueCount);
        $queueResolutionPct = $totalQueueCount > 0
            ? (int) round(($handledQueueCount / $totalQueueCount) * 100)
            : 100;

        $totalCatalogManaged = Buku::count();
        $totalCategoriesManaged = Kategori::count();

        $byUserId = [];
        $staffRows = [];

        foreach ($pustakawanUsers as $staff) {
            $decisions = $allLoans->where('approved_by', $staff->id);
            $totalDecisions = $decisions->count();
            $approvedCount = $decisions->whereIn('status', ['sedang_dipinjam', 'terlambat', 'selesai'])->count();
            $rejectedCount = $decisions->where('status', 'ditolak')->count();

            $responseMinutesList = [];
            foreach ($decisions as $d) {
                if ($d->approved_at && $d->created_at) {
                    $mins = max(1, (int) $d->created_at->diffInMinutes($d->approved_at));
                    $responseMinutesList[] = $mins;
                }
            }

            $avgResponseMinutes = count($responseMinutesList) > 0
                ? (int) round(array_sum($responseMinutesList) / count($responseMinutesList))
                : 0;

            $lastActionAt = $decisions->max('approved_at');

            // Hitung skor KPI pustakawan
            $baseReadiness = ($staff->is_active ? 50 : 15) + ($staff->isEmailVerified() ? 20 : 0);
            $serviceScore = $pendingQueueCount === 0
                ? 30
                : (int) round(($queueResolutionPct / 100) * 30);
            $kpiScorePct = min(100, $baseReadiness + $serviceScore);

            $kpiStatus = match (true) {
                ! $staff->is_active => 'Akun Nonaktif',
                $kpiScorePct >= 85 => 'Sangat Optimal',
                $kpiScorePct >= 70 => 'Aktif & Responsif',
                default => 'Perlu Evaluasi Antrean',
            };

            $item = [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => $staff->role,
                'is_active' => (bool) $staff->is_active,
                'email_verified' => $staff->isEmailVerified(),
                'initial' => $staff->initial,
                'palette' => $staff->color_palette,
                'total_decisions' => $totalDecisions,
                'approved_count' => $approvedCount,
                'rejected_count' => $rejectedCount,
                'avg_response_label' => $avgResponseMinutes > 0 ? "{$avgResponseMinutes} menit" : 'Siap Melayani',
                'last_action_label' => $lastActionAt ? Carbon::parse($lastActionAt)->diffForHumans() : 'Belum ada log keputusan',
                'kpi_score_pct' => $kpiScorePct,
                'kpi_status' => $kpiStatus,
                'joined_at' => $staff->created_at?->format('d M Y') ?? '-',
            ];

            $byUserId[$staff->id] = $item;
            $staffRows[] = $item;
        }

        $teamKpiPct = count($staffRows) > 0
            ? (int) round(collect($staffRows)->avg('kpi_score_pct'))
            : 0;

        return [
            'staff_list' => $staffRows,
            'by_user_id' => $byUserId,
            'total_pustakawan' => $pustakawanUsers->count(),
            'active_pustakawan' => $pustakawanUsers->where('is_active', true)->count(),
            'total_admins' => $adminUsers->count(),
            'active_admins' => $adminUsers->where('is_active', true)->count(),
            'team_kpi_pct' => $teamKpiPct,
            'queue_resolution_pct' => $queueResolutionPct,
            'pending_queue_count' => $pendingQueueCount,
            'handled_queue_count' => $handledQueueCount,
            'total_catalog_managed' => $totalCatalogManaged,
            'total_categories_managed' => $totalCategoriesManaged,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSecurityMetrics($users, int $avgTaskProgress): array
    {
        $totalUsers = max(1, $users->count());
        $verifiedCount = $users->filter(fn (User $u) => $u->isEmailVerified())->count();
        $activeCount = $users->where('is_active', true)->count();
        $suspendedCount = $users->where('is_active', false)->count();
        $unverifiedCount = $users->count() - $verifiedCount;

        $students = $users->where('role', 'user');
        $incompleteIdentityCount = $students->filter(fn (User $u) => ! $u->isSchoolIdentityComplete())->count();

        $activeSessionsCount = 1;
        if (Schema::hasTable('sessions')) {
            $activeSessionsCount = max(1, (int) DB::table('sessions')->count());
        }

        $emailVerifiedRatio = ($verifiedCount / $totalUsers) * 100;
        $activeAccountRatio = ($activeCount / $totalUsers) * 100;

        $overallSecurityScore = (int) round(($emailVerifiedRatio * 0.45) + ($activeAccountRatio * 0.35) + ($avgTaskProgress * 0.20));
        $overallSecurityScore = max(10, min(100, $overallSecurityScore));

        $protectedRecordsCount = User::count() + AdminTask::count() + Buku::count() + Kategori::count() + Peminjaman::count();

        return [
            'overall_score_pct' => $overallSecurityScore,
            'security_grade' => $overallSecurityScore >= 85 ? 'Sangat Aman (Terlindungi)' : ($overallSecurityScore >= 70 ? 'Aman Terkendali' : 'Perlu Audit Verifikasi'),
            'active_sessions_count' => $activeSessionsCount,
            'verified_accounts_count' => $verifiedCount,
            'unverified_accounts_count' => $unverifiedCount,
            'suspended_accounts_count' => $suspendedCount,
            'incomplete_identity_count' => $incompleteIdentityCount,
            'protected_records_count' => $protectedRecordsCount,
            'https_active' => request()->isSecure() || str_contains(config('app.url', ''), 'https'),
            'smtp_configured' => config('mail.default') === 'smtp',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $kpiData
     * @return array<string, mixed>
     */
    private function formatUserPayload(User $user, ?array $kpiData = null): array
    {
        $decisionsCount = Peminjaman::where('approved_by', $user->id)->count();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->display_username,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'email_verified' => $user->isEmailVerified(),
            'identity_complete' => $user->isSchoolIdentityComplete(),
            'identity_pct' => $user->identity_completion_percentage,
            'nisn' => $user->nisn ?? '-',
            'nis' => $user->nis ?? '-',
            'nama_sekolah' => $user->nama_sekolah ?? 'SMK Negeri 5 Surakarta',
            'tahun_ajaran' => $user->tahun_ajaran ?? '-',
            'nama_kelas' => $user->nama_kelas ?? '-',
            'no_telepon' => $user->no_telepon ?? '-',
            'no_whatsapp' => $user->no_whatsapp ?? '-',
            'tanggal_lahir' => $user->tanggal_lahir ? $user->tanggal_lahir->format('d M Y') : '-',
            'alamat' => $user->alamat ?? '-',
            'initial' => $user->initial,
            'palette' => $user->color_palette,
            'staff_decisions_count' => $decisionsCount,
            'kpi_score_pct' => $kpiData['kpi_score_pct'] ?? ($user->is_active ? 100 : 40),
            'kpi_status' => $kpiData['kpi_status'] ?? ($user->is_active ? 'Aktif & Siap' : 'Nonaktif'),
            'joined_at' => $user->created_at?->format('d M Y') ?? '-',
        ];
    }

    private function ensureDefaultAdminTasks(): void
    {
        if (AdminTask::count() > 0) {
            return;
        }

        $defaults = [
            [
                'judul' => 'Evaluasi & Pemantauan Kinerja Pelayanan Pustakawan Mingguan',
                'deskripsi' => 'Memantau tingkat kecepatan respons persetujuan pustakawan, beban kerja petugas, dan memastikan tidak ada antrean layanan yang tertunda.',
                'kategori_tugas' => 'Kinerja Pustakawan',
                'prioritas' => 'penting',
                'progres' => 85,
                'is_completed' => false,
            ],
            [
                'judul' => 'Pengelolaan Akun Pustakawan & Audit Hak Akses Multi-Admin',
                'deskripsi' => 'Memastikan akun para pustakawan dan administrator memiliki kredensial yang sah, terverifikasi, serta pembagian wewenang yang aman.',
                'kategori_tugas' => 'Manajemen Petugas',
                'prioritas' => 'penting',
                'progres' => 100,
                'is_completed' => true,
            ],
            [
                'judul' => 'Pengamanan Data Website, Pencadangan (Backup JSON) & Audit Sesi Login',
                'deskripsi' => 'Mengunduh cadangan data berkala seluruh akun sistem, membersihkan sesi login perangkat yang tidak dikenal, dan memastikan enkripsi berjalan baik.',
                'kategori_tugas' => 'Keamanan Data',
                'prioritas' => 'penting',
                'progres' => 75,
                'is_completed' => false,
            ],
            [
                'judul' => 'Verifikasi Keamanan Email OTP & Validasi NISN 10 Digit Warga Sekolah',
                'deskripsi' => 'Memeriksa akun warga sekolah yang baru mendaftar agar terverifikasi melalui Gmail dan terbebas dari akun duplikat/spam.',
                'kategori_tugas' => 'Keamanan Akun',
                'prioritas' => 'normal',
                'progres' => 60,
                'is_completed' => false,
            ],
        ];

        foreach ($defaults as $item) {
            AdminTask::create($item);
        }
    }
}
