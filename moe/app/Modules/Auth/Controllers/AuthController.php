<?php

namespace App\Modules\Auth\Controllers;

use App\Kernel\BaseController;
use App\Kernel\Libraries\MailService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthController - Handles all authentication operations.
 * Ported from legacy moe/auth/handlers/ and moe/index.php.
 *
 * Routes handled:
 *   GET  /login          → showLogin()
 *   POST /login          → attemptLogin()
 *   GET  /register       → showRegister()
 *   POST /register       → attemptRegister()
 *   GET  /forgot-password → showForgotPassword()
 *   POST /forgot-password → attemptForgotPassword()
 *   GET  /verify-otp     → showVerifyOtp()
 *   POST /verify-otp     → attemptVerifyOtp()
 *   GET  /resend-otp     → resendOtp()
 *   GET  /logout         → logout()
 *   GET  /register-success → showSuccess()
 */
use App\Modules\User\Services\ActivityLogService;

class AuthController extends BaseController
{
    protected $activityLog;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->activityLog = service('activityLog');
    }
    // ─── LOGIN ─────────────────────────────────────────────

    /** Show login form */
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (session('id_nethera')) {
            return redirect()->to(base_url('beranda'));
        }

        return view('App\Modules\Auth\Views\login', [
            'pageTitle' => 'Login - MOE',
            'authTitle' => 'MEDITERRANEAN OF EGYPT',
            'authSubtitle' => 'ENTER YOUR CREDENTIALS',
            'bodyClass' => 'page-login',
        ]);
    }

    /** Process login POST */
    public function attemptLogin()
    {
        // Rate limit: 10 login attempts per minute per IP──
        $throttler = \Config\Services::throttler();
        $clientIp = $this->request->getIPAddress();
        // Sanitize IP for cache key (Windows doesn't allow ':' in filenames)
        $safeIp = str_replace(':', '_', $clientIp);

        if ($throttler->check('login_' . $safeIp, 5, MINUTE) === false) {
            $secondsLeft = $throttler->getTokenTime();
            $this->activityLog->log('RATE_LIMIT', 'AUTH', "Login throttled for IP: {$clientIp}");
            return redirect()->to(base_url('login'))
                ->with('error', "Terlalu banyak percobaan login. Coba lagi dalam {$secondsLeft} detik.");
        }

        $username = trim($this->request->getPost('username'));
        $password = $this->request->getPost('password');

        $authService = service('authService');
        $result = $authService->login($username, $password);

        if (!$result['success']) {
            if ($result['locked'] ?? false) {
                $this->activityLog->log('LOCKOUT', 'AUTH', "Account locked for username: {$username}");
            }

            // Redirect unverified users to OTP page
            if ($result['needs_verification'] ?? false) {
                return redirect()->to(base_url('verify-otp?user=' . urlencode($result['username'])))
                    ->with('warning', $result['message']);
            }

            // Pending/Ditolak/Diblokir → show as warning, not error
            if (isset($result['status'])) {
                return redirect()->to(base_url('login'))->with('warning', $result['message']);
            }

            return redirect()->to(base_url('login'))->with('error', $result['message']);
        }

        $user = $result['user'];

        // Set session vars and regenerate session ID for security
        session()->regenerate();
        \Config\Services::session()->set([
            'id_nethera' => $user['id_nethera'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role' => $user['role'],
            'status_login' => 'berhasil',
            'last_activity' => time(),
        ]);

        $this->activityLog->log('LOGIN_SUCCESS', 'AUTH', "Logged in as {$user['username']} ({$user['role']})", $user['id_nethera']);

        // Redirect based on role
        if ($user['role'] === ROLE_VASIKI) {
            return redirect()->to(base_url('admin'));
        } elseif ($user['role'] === ROLE_HAKAES) {
            return redirect()->to(base_url('admin/classes'));
        } else {
            return redirect()->to(base_url('beranda'));
        }
    }

    // ─── REGISTER ──────────────────────────────────────────

    /** Show registration form */
    public function showRegister()
    {
        return view('App\Modules\Auth\Views\register', [
            'pageTitle' => 'Register - MOE',
            'authTitle' => 'REGISTRASI ANGGOTA BARU',
            'authSubtitle' => 'Join the Guardians of the Mediterranean',
            'bodyClass' => 'page-login',
        ]);
    }

    /** Process registration POST */
    public function attemptRegister()
    {
        // Rate limit: max 5 registration attempts per 10 min per IP
        $throttler = \Config\Services::throttler();
        $clientIp = $this->request->getIPAddress();
        $safeIp = str_replace(':', '_', $clientIp);

        if ($throttler->check('register_' . $safeIp, 5, 10 * MINUTE) === false) {
            $this->activityLog->log('RATE_LIMIT', 'AUTH', "Registration throttled for IP: {$clientIp}");
            return redirect()->to(base_url('register'))
                ->withInput()
                ->with('error', 'Terlalu banyak percobaan registrasi. Silakan coba lagi dalam beberapa menit.');
        }

        $rules = [
            'nama_lengkap' => [
                'rules' => 'required|min_length[3]|max_length[100]|regex_match[/^[a-zA-Z\s\'.]+$/]',
                'errors' => [
                    'required' => 'Nama lengkap wajib diisi.',
                    'min_length' => 'Nama lengkap minimal 3 karakter.',
                    'max_length' => 'Nama lengkap maksimal 100 karakter.',
                    'regex_match' => 'Nama lengkap hanya boleh huruf, spasi, apostrof, dan titik.',
                ],
            ],
            'username' => [
                'rules' => 'required|min_length[3]|max_length[50]|regex_match[/^[a-zA-Z0-9_]+$/]',
                'errors' => [
                    'required' => 'Username wajib diisi.',
                    'min_length' => 'Username minimal 3 karakter.',
                    'max_length' => 'Username maksimal 50 karakter.',
                    'regex_match' => 'Username hanya boleh huruf, angka, dan underscore. Tanpa spasi atau karakter spesial.',
                ],
            ],
            'email' => [
                'rules' => 'required|valid_email|max_length[100]',
                'errors' => [
                    'required' => 'Email wajib diisi.',
                    'valid_email' => 'Format email tidak valid.',
                    'max_length' => 'Email maksimal 100 karakter.',
                ],
            ],
            'noHP' => [
                'rules' => 'required|min_length[10]|max_length[15]|regex_match[/^[0-9+\-]+$/]',
                'errors' => [
                    'required' => 'Nomor HP wajib diisi.',
                    'min_length' => 'Nomor HP minimal 10 digit.',
                    'max_length' => 'Nomor HP maksimal 15 digit.',
                    'regex_match' => 'Nomor HP hanya boleh angka, tanda + dan -.',
                ],
            ],
            'password' => [
                'rules' => 'required|min_length[8]|regex_match[/[A-Z]/]|regex_match[/[a-z]/]|regex_match[/[0-9]/]',
                'errors' => [
                    'required' => 'Password wajib diisi.',
                    'min_length' => 'Password minimal 8 karakter.',
                    'regex_match' => 'Password harus mengandung huruf besar, huruf kecil, dan angka.',
                ],
            ],
            'password_confirm' => [
                'rules' => 'required|matches[password]',
                'errors' => [
                    'required' => 'Konfirmasi password wajib diisi.',
                    'matches' => 'Konfirmasi password tidak cocok.',
                ],
            ],
            'tanggal_lahir' => [
                'rules' => 'required|valid_date',
                'errors' => [
                    'required' => 'Tanggal lahir wajib diisi.',
                    'valid_date' => 'Format tanggal lahir tidak valid.',
                ],
            ],
            'periode_masuk' => [
                'rules' => 'required|integer|greater_than_equal_to[1]',
                'errors' => [
                    'required' => 'Periode masuk wajib diisi.',
                    'integer' => 'Periode masuk harus berupa angka.',
                    'greater_than_equal_to' => 'Periode masuk minimal 1.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->to(base_url('register'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Custom validation: age 10-100 years
        $tanggalLahir = $this->request->getPost('tanggal_lahir');
        $birthDate = new \DateTime($tanggalLahir);
        $today = new \DateTime();
        $age = $today->diff($birthDate)->y;

        $customErrors = [];

        if ($birthDate > $today) {
            $customErrors['tanggal_lahir'] = 'Tanggal lahir tidak boleh di masa depan.';
        } elseif ($age < 10) {
            $customErrors['tanggal_lahir'] = 'Usia minimal 10 tahun untuk mendaftar.';
        } elseif ($age > 100) {
            $customErrors['tanggal_lahir'] = 'Tanggal lahir tidak valid.';
        }

        // Custom validation: password ≠ username
        $username = trim($this->request->getPost('username'));
        $password = $this->request->getPost('password');

        if (strtolower($password) === strtolower($username)) {
            $customErrors['password'] = 'Password tidak boleh sama dengan username.';
        }

        if (!empty($customErrors)) {
            return redirect()->to(base_url('register'))
                ->withInput()
                ->with('errors', $customErrors);
        }

        $payload = [
            'nama_lengkap' => trim($this->request->getPost('nama_lengkap')),
            'username' => trim($this->request->getPost('username')),
            'email' => trim($this->request->getPost('email')),
            'noHP' => trim($this->request->getPost('noHP')),
            'password' => $this->request->getPost('password'),
            'tanggal_lahir' => $this->request->getPost('tanggal_lahir'),
            'periode_masuk' => (int) $this->request->getPost('periode_masuk'),
        ];

        // DNS MX validation — reject domains that can't receive email
        $emailDomain = substr($payload['email'], strpos($payload['email'], '@') + 1);
        if (!checkdnsrr($emailDomain, 'MX')) {
            return redirect()->to(base_url('register'))
                ->withInput()
                ->with('error', 'Domain email tidak valid. Pastikan Anda menggunakan email yang aktif (contoh: Gmail, Yahoo, Outlook).');
        }

        $authService = service('authService');
        $result = $authService->register($payload);

        if (!$result['success']) {
            if ($result['locked'] ?? false) {
                $this->activityLog->log('LOCKOUT', 'AUTH', "Account locked for username: {$payload['username']} (Registration)");
            }
            return redirect()->to(base_url('register'))
                ->withInput()
                ->with('error', $result['message']);
        }

        $this->activityLog->log('REGISTER', 'AUTH', "New registration attempt: {$payload['username']} ({$payload['email']})");

        return redirect()->to(base_url('verify-otp?user=' . urlencode($payload['username'])));
    }

    // ─── FORGOT PASSWORD ───────────────────────────────────

    /** Show forgot password form */
    public function showForgotPassword()
    {
        return view('App\Modules\Auth\Views\forgot_password', [
            'pageTitle' => 'Forgot Password - MOE',
            'authTitle' => 'RESET PASSWORD',
            'authSubtitle' => 'Masukkan alamat email terdaftar Anda',
            'bodyClass' => 'page-login',
        ]);
    }

    /** Process forgot password POST (rate limited) */
    public function attemptForgotPassword()
    {
        // Rate limit: 3 per 10 min per IP
        $throttler = \Config\Services::throttler();
        $clientIp = $this->request->getIPAddress();
        $safeIp = str_replace(':', '_', $clientIp);

        if ($throttler->check('forgot_' . $safeIp, 3, 10 * MINUTE) === false) {
            return redirect()->to(base_url('forgot-password'))
                ->with('info', 'Tautan reset telah dikirim ke email Anda.');
        }

        $email = trim($this->request->getPost('email'));
        $authService = service('authService');
        $authService->forgotPassword($email);

        // Always show success (anti-enumeration)
        return redirect()->to(base_url('forgot-password'))
            ->with('info', 'Jika email terdaftar, tautan reset telah dikirim.');
    }

    // ─── RESET PASSWORD ───────────────────────────────────

    /** Show reset password form */
    public function showResetPassword()
    {
        $token = $this->request->getGet('token');

        if (empty($token)) {
            return redirect()->to(base_url('forgot-password'))
                ->with('error', 'Token reset tidak valid.');
        }

        return view('App\Modules\Auth\Views\reset_password', [
            'pageTitle' => 'Reset Password - MOE',
            'authTitle' => 'RESET PASSWORD',
            'authSubtitle' => 'Masukkan password baru Anda',
            'bodyClass' => 'page-login',
            'token' => $token,
        ]);
    }

    /** Process reset password POST */
    public function attemptResetPassword()
    {
        if (
            !$this->validate([
                'token' => 'required',
                'password' => 'required|min_length[8]',
                'password_confirm' => 'required|matches[password]',
            ])
        ) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');

        $authService = service('authService');
        $result = $authService->resetPassword($token, $password);

        if (!$result['success']) {
            return redirect()->to(base_url('forgot-password'))
                ->with('error', 'Token tidak valid atau sudah kadaluarsa. Silakan minta ulang.');
        }

        $this->activityLog->log('PASSWORD_RESET', 'AUTH', 'Password reset completed via token');

        return redirect()->to(base_url('login'))
            ->with('success', 'Password berhasil direset. Silakan login dengan password baru.');
    }

    // ─── OTP VERIFICATION ──────────────────────────────────

    /** Show OTP verification form */
    public function showVerifyOtp()
    {
        $username = $this->request->getGet('user');

        if (empty($username)) {
            return redirect()->to(base_url('register'));
        }

        $authService = service('authService');
        if (!$authService->hasPendingOTP($username)) {
            return redirect()->to(base_url('register-success?username=' . urlencode($username)));
        }

        return view('App\Modules\Auth\Views\verify_otp', [
            'pageTitle' => 'Verifikasi OTP - MOE',
            'authTitle' => 'VERIFIKASI AKUN',
            'authSubtitle' => 'Masukkan kode OTP yang dikirim ke email Anda',
            'bodyClass' => 'page-login',
            'username' => $username,
        ]);
    }

    /** Process OTP verification POST */
    public function attemptVerifyOtp()
    {
        $throttler = \Config\Services::throttler();
        $clientIp = $this->request->getIPAddress();
        $safeIp = str_replace(':', '_', $clientIp);

        if ($throttler->check('otp_' . $safeIp, 10, MINUTE) === false) {
            $username = $this->request->getPost('username') ?? '';
            return redirect()->to(base_url('verify-otp?user=' . urlencode($username)))
                ->with('error', 'Terlalu banyak percobaan. Tunggu sebentar.');
        }

        $username = $this->request->getPost('username');
        $otp_code = $this->request->getPost('otp_code');

        $authService = service('authService');
        if ($authService->verifyOTP($username, $otp_code)) {
            $this->activityLog->log('OTP_VERIFIED', 'AUTH', "OTP verified for {$username}");
            return redirect()->to(base_url('register-success?username=' . urlencode($username)));
        }

        $this->activityLog->log('OTP_FAILED', 'AUTH', "OTP failed attempt for {$username}");

        return redirect()->to(base_url('verify-otp?user=' . urlencode($username)))
            ->with('error', 'Kode OTP salah atau sudah kadaluarsa.');
    }

    // ─── RESEND OTP ────────────────────────────────────────

    /** Resend OTP code to user's email (rate limited) */
    public function resendOtp()
    {
        $username = $this->request->getPost('username');

        if (empty($username)) {
            return redirect()->to(base_url('register'));
        }

        // Rate limit: max 3 resend per 5 min per IP
        $throttler = \Config\Services::throttler();
        $clientIp = $this->request->getIPAddress();
        $safeIp = str_replace(':', '_', $clientIp);

        if ($throttler->check('resend_otp_' . $safeIp, 3, 5 * MINUTE) === false) {
            return redirect()->to(base_url('verify-otp?user=' . urlencode($username)))
                ->with('error', 'Terlalu banyak permintaan. Tunggu beberapa menit.');
        }

        $authService = service('authService');
        $result = $authService->resendOTP($username);

        if (!$result['success']) {
            return redirect()->to(base_url('verify-otp?user=' . urlencode($username)))
                ->with('error', $result['message'] ?? 'Gagal mengirim email.');
        }

        return redirect()->to(base_url('verify-otp?user=' . urlencode($username)))
            ->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    // ─── SUCCESS PAGE ──────────────────────────────────────

    /** Show registration success page */
    public function showSuccess()
    {
        $username = $this->request->getGet('username') ?? 'Anggota Baru';
        return view('App\Modules\Auth\Views\success', [
            'username' => $username,
            'pageTitle' => 'Registrasi Berhasil - MOE',
            'authTitle' => 'MEDITERRANEAN OF EGYPT',
            'authSubtitle' => 'Registrasi berhasil',
            'bodyClass' => 'page-login',
        ]);
    }

    // ─── ADMIN: UNLOCK ACCOUNT ─────────────────────────────

    /**
     * Manually unlock a locked account (Vasiki only).
     */
    public function unlockAccount()
    {
        if (!session('id_nethera') || strtolower(session('role') ?? '') !== 'vasiki') {
            return redirect()->to(base_url('admin'))->with('error', 'Akses ditolak.');
        }

        $userId = (int) $this->request->getPost('user_id');
        if (!$userId) {
            return redirect()->back()->with('error', 'User ID tidak valid.');
        }

        $userModel = new \App\Modules\User\Models\UserModel();
        $userModel->resetLockout($userId);

        $this->activityLog->log('ADMIN_UNLOCK', 'AUTH', "Admin unlocked user ID: {$userId}");

        return redirect()->back()->with('success', 'Akun berhasil dibuka kuncinya.');
    }

    // ─── LOGOUT ────────────────────────────────────────────

    /** Logout — destroy session */
    public function logout()
    {
        $userId = session('id_nethera');
        if ($userId) {
            $this->activityLog->log('LOGOUT', 'AUTH', "User logged out", $userId);
        }

        \Config\Services::session()->destroy();
        return redirect()->to(base_url('login'))
            ->with('success', 'Anda telah berhasil logout.');
    }
}
