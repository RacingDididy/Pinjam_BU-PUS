<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember', true);

        if (Auth::attempt($credentials, $remember)) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->is_active === false) {
                Auth::logout();
                $msg = 'Akun Anda sedang dinonaktifkan sementara oleh Administrator. Hubungi petugas perpustakaan.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }

                return back()->withErrors(['email' => $msg])->onlyInput('email');
            }

            if (! $user->isEmailVerified()) {
                Auth::logout();
                $mailResult = $this->issueAndSendVerificationLink($user);
                $token = $mailResult['token'];
                $request->session()->put('pending_register_user_id', $user->id);
                $showFallbackBtn = in_array(config('mail.default'), ['log', 'array'], true) || ! $mailResult['mail_sent'];

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'needs_verification' => true,
                        'email' => $user->email,
                        'dev_verification_url' => $showFallbackBtn
                            ? route('verification.link', ['id' => $user->id, 'token' => $token])
                            : null,
                        'message' => 'Email Anda belum diverifikasi. Kami baru saja mengirimkan ulang tautan verifikasi ke '.$user->email.'.',
                    ], 422);
                }

                return back()->withErrors(['email' => 'Silakan verifikasi email Anda terlebih dahulu melalui tautan yang dikirim ke Gmail.']);
            }

            $request->session()->regenerate();

            $redirectUrl = route('home');
            if ($user->isAdmin()) {
                $redirectUrl = route('admin.dashboard');
            } elseif ($user->isPustakawan()) {
                $redirectUrl = route('pustakawan.dashboard');
            }

            $successMsg = 'Selamat datang kembali, '.$user->name.'!';

            if ($request->wantsJson()) {
                $request->session()->flash('success', $successMsg);

                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect' => $redirectUrl,
                    'redirect_url' => $redirectUrl,
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                ]);
            }

            return redirect()->intended($redirectUrl)
                ->with('success', $successMsg);
        }

        $errMsg = 'Email atau kata sandi yang Anda masukkan tidak sesuai.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $errMsg,
            ], 422);
        }

        return back()->withErrors([
            'email' => $errMsg,
        ])->onlyInput('email');
    }

    public function showRegisterForm(): View
    {
        return view('auth.login', ['initialMode' => 'register']);
    }

    public function register(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $email = strtolower(trim($validated['email']));
        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $existingUser->isEmailVerified()) {
            $msg = 'Email ini sudah terdaftar dan aktif. Silakan masuk menggunakan kata sandi Anda.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'already_registered' => true,
                    'email' => $email,
                    'message' => $msg,
                ], 422);
            }

            return redirect()->route('login')->with('info', $msg);
        }

        if (! $existingUser) {
            $emailPrefix = Str::before($email, '@');
            $cleanName = Str::headline(preg_replace('/[^a-zA-Z0-9]+/', ' ', $emailPrefix) ?: 'Warga Sekolah');
            $baseUsername = Str::lower(preg_replace('/[^a-zA-Z0-9]/', '', $emailPrefix) ?: 'pembaca');

            $user = User::create([
                'name' => $cleanName,
                'username' => $baseUsername.random_int(10, 999),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'user',
                'is_active' => true,
                'nama_sekolah' => 'SMK Negeri 5 Surakarta',
                'tahun_ajaran' => '2025/2026',
            ]);
        } else {
            $user = $existingUser;
        }

        $mailResult = $this->issueAndSendVerificationLink($user);
        $token = $mailResult['token'];
        $request->session()->put('pending_register_user_id', $user->id);

        $showFallbackBtn = in_array(config('mail.default'), ['log', 'array'], true) || ! $mailResult['mail_sent'];
        $verificationUrl = route('verification.link', ['id' => $user->id, 'token' => $token]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'step' => 'waiting_verification',
                'email' => $user->email,
                'message' => 'Email verifikasi telah dikirim ke '.$user->email.'. Silakan tekan tombol Verifikasi di Gmail Anda.',
                'dev_verification_url' => $showFallbackBtn ? $verificationUrl : null,
            ]);
        }

        return redirect()->route('home', ['waiting_verify' => $user->email]);
    }

    public function verifyFromEmailLink(Request $request, int $id, string $token): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = User::find($id);

        if (! $user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Akun tidak ditemukan.'], 404);
            }

            return redirect()->route('home')->with('error', 'Tautan verifikasi tidak valid.');
        }

        if (! $user->isEmailVerified() || ! empty($user->verification_code)) {
            if ($user->verification_code !== $token) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Tautan verifikasi tidak valid atau sudah diganti.'], 422);
                }

                return redirect()->route('home')->with('error', 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.');
            }

            if ($user->verification_code_expires_at && $user->verification_code_expires_at->isPast()) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Tautan verifikasi telah kedaluwarsa. Silakan kirim ulang.'], 422);
                }

                return redirect()->route('home')->with('error', 'Tautan verifikasi telah kedaluwarsa.');
            }

            $user->update([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'verification_code' => null,
                'verification_code_expires_at' => null,
            ]);
        }

        Auth::login($user, true);
        $request->session()->put('needs_password_setup', true);
        $request->session()->put('setup_password_email', $user->email);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'verified' => true,
                'email' => $user->email,
                'message' => 'Verifikasi berhasil! Silakan buat kata sandi baru untuk akun Anda.',
            ]);
        }

        return redirect()->route('home', ['setup_password' => 1])
            ->with('success', 'Verifikasi berhasil! Silakan buat kata sandi akun Anda.');
    }

    public function checkVerificationStatus(Request $request): JsonResponse
    {
        $user = null;

        if (Auth::check()) {
            $user = Auth::user();
        } elseif ($request->session()->has('pending_register_user_id')) {
            $user = User::find($request->session()->get('pending_register_user_id'));
        } elseif ($request->filled('email')) {
            $user = User::where('email', strtolower(trim((string) $request->query('email'))))->first();
        }

        if ($user && $user->isEmailVerified() && empty($user->verification_code)) {
            if (! Auth::check()) {
                Auth::login($user, true);
            }
            $request->session()->put('needs_password_setup', true);
            $request->session()->put('setup_password_email', $user->email);

            return response()->json([
                'verified' => true,
                'email' => $user->email,
                'message' => 'Verifikasi berhasil! Silakan buat kata sandi baru akun Anda.',
            ]);
        }

        return response()->json([
            'verified' => false,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $method = $request->input('method', 'email');

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'nisn' => ['nullable', 'string'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $email = strtolower(trim($validated['email']));
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat email tersebut belum terdaftar di sistem PINJAM BU-PUS.',
            ], 404);
        }

        if ($user->is_active === false) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini sedang dinonaktifkan sementara oleh Administrator.',
            ], 403);
        }

        // Metode pemulihan 2: Verifikasi Identitas Sekolah (Email + NISN)
        if ($method === 'nisn') {
            $nisnInput = preg_replace('/\D/', '', (string) ($validated['nisn'] ?? ''));

            if (strlen($nisnInput) !== 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masukkan 10 digit nomor NISN Anda dengan benar.',
                ], 422);
            }

            if (empty($user->nisn)) {
                return response()->json([
                    'success' => false,
                    'message' => 'NISN belum terdaftar pada akun ini. Silakan gunakan metode Verifikasi Email.',
                ], 422);
            }

            if ($user->nisn !== $nisnInput) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data NISN tidak cocok dengan alamat email tersebut.',
                ], 422);
            }

            $user->update([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'verification_code' => null,
                'verification_code_expires_at' => null,
            ]);

            $request->session()->put('pending_register_user_id', $user->id);
            $request->session()->put('needs_password_setup', true);
            $request->session()->put('setup_password_email', $user->email);

            return response()->json([
                'success' => true,
                'step' => 'set_password',
                'email' => $user->email,
                'message' => 'Identitas NISN berhasil diverifikasi! Silakan buat kata sandi baru Anda.',
            ]);
        }

        // Metode pemulihan 1: Kirim tautan verifikasi reset password ke Email
        $mailResult = $this->issueAndSendVerificationLink($user);
        $token = $mailResult['token'];
        $request->session()->put('pending_register_user_id', $user->id);

        $showFallbackBtn = in_array(config('mail.default'), ['log', 'array'], true) || ! $mailResult['mail_sent'];
        $verificationUrl = route('verification.link', ['id' => $user->id, 'token' => $token]);

        return response()->json([
            'success' => true,
            'step' => 'waiting_verification',
            'email' => $user->email,
            'message' => 'Tautan reset kata sandi telah dikirim ke '.$user->email.'. Silakan buka Gmail Anda.',
            'dev_verification_url' => $showFallbackBtn ? $verificationUrl : null,
        ]);
    }

    public function setupPassword(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/\d/', 'confirmed'],
            'name' => ['nullable', 'string', 'max:100'],
        ], [
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal terdiri dari 8 karakter.',
            'password.regex' => 'Kata sandi wajib mengandung huruf dan minimal 1 angka.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user && $request->session()->has('pending_register_user_id')) {
            $user = User::find($request->session()->get('pending_register_user_id'));
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi verifikasi tidak ditemukan. Silakan ulangi proses verifikasi.',
            ], 401);
        }

        $updateData = [
            'password' => Hash::make($validated['password']),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ];

        if (! empty($validated['name'])) {
            $updateData['name'] = trim($validated['name']);
        }

        $user->update($updateData);

        Auth::login($user, true);
        $request->session()->forget(['needs_password_setup', 'setup_password_email', 'pending_register_user_id']);

        $successMsg = 'Kata sandi berhasil disimpan! Selamat datang, '.$user->name.'.';

        if ($request->wantsJson()) {
            $request->session()->flash('success', $successMsg);

            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'redirect_url' => route('home'),
            ]);
        }

        return redirect()->route('home')->with('success', $successMsg);
    }

    public function resendVerificationCode(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        if (! $user && $request->session()->has('pending_register_user_id')) {
            $user = User::find($request->session()->get('pending_register_user_id'));
        }

        if (! $user && $request->filled('email')) {
            $user = User::where('email', strtolower(trim((string) $request->input('email'))))->first();
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat email tidak ditemukan.',
            ], 404);
        }

        if ($user->isEmailVerified() && empty($user->verification_code)) {
            return response()->json([
                'success' => true,
                'verified' => true,
                'message' => 'Email Anda sudah terverifikasi.',
            ]);
        }

        $mailResult = $this->issueAndSendVerificationLink($user);
        $token = $mailResult['token'];
        $showFallbackBtn = in_array(config('mail.default'), ['log', 'array'], true) || ! $mailResult['mail_sent'];
        $verificationUrl = route('verification.link', ['id' => $user->id, 'token' => $token]);

        return response()->json([
            'success' => true,
            'message' => 'Tautan verifikasi baru telah dikirim ke '.$user->email.'.',
            'dev_verification_url' => $showFallbackBtn ? $verificationUrl : null,
        ]);
    }

    public function showVerifyForm(): RedirectResponse
    {
        return redirect()->route('home', ['setup_password' => session('needs_password_setup') ? 1 : 0]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        return $this->checkVerificationStatus($request);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * @return array{token: string, mail_sent: bool}
     */
    protected function issueAndSendVerificationLink(User $user): array
    {
        $token = Str::random(32);

        $user->update([
            'verification_code' => $token,
            'verification_code_expires_at' => now()->addMinutes(30),
        ]);

        $mailSent = false;
        try {
            Mail::to($user->email)->send(new EmailVerificationCodeMail($user, $token));
            $mailSent = true;
        } catch (Throwable $e) {
            Log::warning('Gagal mengirim email verifikasi ke '.$user->email.': '.$e->getMessage());
        }

        return [
            'token' => $token,
            'mail_sent' => $mailSent,
        ];
    }
}
