<?php

use App\Http\Controllers\Admin\BukuController as AdminBukuController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PerpustakaanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\Pustakawan\DashboardController as PustakawanDashboardController;
use Illuminate\Support\Facades\Route;

// Halaman publik
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/media/covers/{filename}', function (string $filename) {
    $safeName = basename($filename);
    $path = is_dir('/data/covers') && file_exists('/data/covers/'.$safeName)
        ? '/data/covers/'.$safeName
        : public_path('covers/'.$safeName);

    abort_unless(file_exists($path), 404);

    return response()->file($path, [
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->name('media.covers');
Route::get('/locale/{locale}', [HomeController::class, 'switchLocale'])->name('locale.switch');
Route::get('/informasi/aturan-peminjaman', [HomeController::class, 'aturanPeminjaman'])->name('informasi.aturan-peminjaman');
Route::get('/informasi/panduan-pengembalian', [HomeController::class, 'panduanPengembalian'])->name('informasi.panduan-pengembalian');

// Katalog perpustakaan
Route::get('/perpustakaan', [PerpustakaanController::class, 'index'])->name('perpustakaan.index');

// Profil dan pinjaman user
Route::get('/pinjaman-saya', [ProfilController::class, 'index'])->name('profil.index');
Route::middleware('auth')->group(function () {
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/identitas', [ProfilController::class, 'updateIdentity'])->name('profil.identity.update');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password.update');
    Route::get('/profil/pinjaman-live', [ProfilController::class, 'liveLoansStatus'])->name('profil.loans.live');
    Route::post('/peminjaman/{peminjaman}/return', [ProfilController::class, 'returnLoanItem'])->name('profil.loans.return');

    Route::get('/checkout', [BukuController::class, 'showCheckout'])->name('buku.checkout.show');
});

// Detail buku, keranjang, rating, dan wishlist
Route::get('/buku/{buku}', [BukuController::class, 'show'])->name('buku.show');
Route::post('/buku/{buku}/pinjam-langsung', [BukuController::class, 'borrowDirect'])->name('buku.borrow.direct');
Route::post('/buku/{buku}/rating', [BukuController::class, 'storeRating'])->name('buku.rating');
Route::post('/buku/{buku}/cart', [BukuController::class, 'addToCart'])->name('buku.cart.add');
Route::patch('/buku/{buku}/cart', [BukuController::class, 'updateCartQuantity'])->name('buku.cart.update');
Route::delete('/buku/{buku}/cart', [BukuController::class, 'removeFromCart'])->name('buku.cart.remove');
Route::post('/pinjam/checkout', [BukuController::class, 'checkoutCart'])->name('buku.cart.checkout');
Route::post('/buku/{buku}/favorite', [BukuController::class, 'toggleFavorite'])->name('buku.favorite.toggle');

// Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Verifikasi email dan setup password
Route::get('/email/verify-link/{id}/{token}', [AuthController::class, 'verifyFromEmailLink'])->name('verification.link');
Route::get('/auth/check-verification', [AuthController::class, 'checkVerificationStatus'])->name('auth.check.verification');
Route::post('/email/verify', [AuthController::class, 'verifyCode'])->name('verification.verify');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.password.forgot');
Route::post('/auth/setup-password', [AuthController::class, 'setupPassword'])->name('auth.password.setup');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationCode'])->name('verification.resend');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerifyForm'])->name('verification.notice');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Panel Admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/pustakawan', [AdminDashboardController::class, 'pustakawanIndex'])->name('pustakawan.index');
    Route::get('/warga-sekolah', [AdminDashboardController::class, 'usersIndex'])->name('users.index');
    Route::get('/tugas', [AdminDashboardController::class, 'tasksIndex'])->name('tasks.index');

    // Manajemen akun
    Route::post('/users', [AdminDashboardController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}/role', [AdminDashboardController::class, 'updateUserRole'])->name('users.role');
    Route::patch('/users/{user}/toggle-active', [AdminDashboardController::class, 'toggleUserActive'])->name('users.toggle-active');
    Route::patch('/users/{user}/verify', [AdminDashboardController::class, 'verifyUserManual'])->name('users.verify');
    Route::patch('/users/{user}/reset-password', [AdminDashboardController::class, 'resetUserPassword'])->name('users.reset-password');
    Route::post('/users/bulk-delete', [AdminDashboardController::class, 'bulkDeleteUsers'])->name('users.bulk-destroy');
    Route::delete('/users/{user}', [AdminDashboardController::class, 'deleteUser'])->name('users.destroy');

    // Keamanan sistem
    Route::get('/security/backup', [AdminDashboardController::class, 'downloadSecurityBackup'])->name('security.backup');
    Route::post('/security/purge-sessions', [AdminDashboardController::class, 'purgeOtherSessions'])->name('security.purge-sessions');
    Route::post('/security/optimize', [AdminDashboardController::class, 'optimizeSystemSecurity'])->name('security.optimize');

    // Tugas admin
    Route::post('/tasks', [AdminDashboardController::class, 'storeTask'])->name('tasks.store');
    Route::patch('/tasks/{task}/progress', [AdminDashboardController::class, 'updateTaskProgress'])->name('tasks.progress');
    Route::patch('/tasks/{task}/toggle', [AdminDashboardController::class, 'toggleTaskComplete'])->name('tasks.toggle');
    Route::delete('/tasks/{task}', [AdminDashboardController::class, 'deleteTask'])->name('tasks.destroy');
});

// Panel Pustakawan
Route::prefix('pustakawan')->name('pustakawan.')->middleware(['auth', 'pustakawan'])->group(function () {
    Route::get('/', [PustakawanDashboardController::class, 'index'])->name('dashboard');
    Route::get('/laporan', [PustakawanDashboardController::class, 'laporan'])->name('laporan');
    Route::get('/kategori', [PustakawanDashboardController::class, 'kategoriIndex'])->name('kategori.index');
    Route::get('/warga-sekolah', [PustakawanDashboardController::class, 'wargaIndex'])->name('warga.index');

    // Persetujuan dan pengembalian pinjaman
    Route::post('/peminjaman/{kodeTransaksi}/approve', [PustakawanDashboardController::class, 'approveLoan'])->name('peminjaman.approve');
    Route::post('/peminjaman/{kodeTransaksi}/reject', [PustakawanDashboardController::class, 'rejectLoan'])->name('peminjaman.reject');
    Route::post('/peminjaman/{kodeTransaksi}/complete', [PustakawanDashboardController::class, 'completeLoan'])->name('peminjaman.complete');

    // Export Laporan Excel (.xlsx) - Data Jadi & Data Raw
    Route::get('/export/buku', [PustakawanDashboardController::class, 'exportBuku'])->name('export.buku');
    Route::get('/export/peminjaman', [PustakawanDashboardController::class, 'exportPeminjaman'])->name('export.peminjaman');

    // Kategori buku
    Route::post('/kategori', [PustakawanDashboardController::class, 'storeKategori'])->name('kategori.store');
    Route::post('/kategori/quick', [AdminBukuController::class, 'quickStoreKategori'])->name('kategori.quick');
    Route::post('/kategori/bulk-delete', [PustakawanDashboardController::class, 'bulkDestroyKategori'])->name('kategori.bulk-destroy');
    Route::put('/kategori/{kategori}', [PustakawanDashboardController::class, 'updateKategori'])->name('kategori.update');
    Route::delete('/kategori/{kategori}', [PustakawanDashboardController::class, 'destroyKategori'])->name('kategori.destroy');

    // Katalog buku
    Route::patch('/buku/{buku}/stok', [AdminBukuController::class, 'updateStock'])->name('buku.stok');
    Route::delete('/buku-bulk-delete', [AdminBukuController::class, 'bulkDestroy'])->name('buku.bulk-destroy');
    Route::resource('buku', AdminBukuController::class);
});
