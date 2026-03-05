<?php

namespace App\Modules\Auth\Services;

use App\Modules\User\Models\UserModel;
use App\Kernel\Libraries\MailService;

class AuthService
{
    protected $userModel;
    protected $mailService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->mailService = new MailService();
    }

    /**
     * Attempt to log in a user.
     *
     * All failure paths return identical generic messages to prevent enumeration.
     * Status/verification checked after password validation.
     */
    public function login(string $username, string $password): array
    {
        $user = $this->userModel->findByUsername($username);

        // Always run password_verify even for unknown users (prevents timing-based enumeration)
        $dummyHash = '$2y$12$000000000000000000000uGDFGR/sPjOUbOjATnfe4Yjd0N2Xk5u';
        $passwordValid = password_verify($password, $user['password'] ?? $dummyHash);

        // Generic error message — same for ALL failure cases
        $genericError = ['success' => false, 'message' => 'Username atau password salah.'];

        // 1. User not found OR wrong password
        if (!$user || !$passwordValid) {
            if ($user) {
                $this->userModel->incrementFailedAttempts($user['id_nethera']);
            }
            log_message('info', "Login failed: " . (!$user ? "user not found" : "wrong password") . " for '{$username}'");
            return $genericError;
        }

        // Lockout check — after password to avoid confirming user existence
        if ($this->userModel->isLocked($user)) {
            log_message('info', "Login failed: account locked for '{$username}'");
            return array_merge($genericError, ['locked' => true]);
        }

        // 3. Email not verified — password already confirmed, safe to show specific message
        if (empty($user['email_verified_at'])) {
            log_message('info', "Login failed: email not verified for '{$username}'");
            return [
                'success' => false,
                'message' => 'Email belum diverifikasi. Silakan cek inbox email Anda untuk kode OTP.',
                'needs_verification' => true,
                'username' => $username,
            ];
        }

        // 4. Account not approved by admin — password confirmed, give clear feedback
        if ($user['status_akun'] !== 'Aktif') {
            log_message('info', "Login failed: account status '{$user['status_akun']}' for '{$username}'");
            $statusMessages = [
                'Pending' => 'Akun Anda sedang menunggu persetujuan Vasiki (Admin). Mohon bersabar.',
                'Hiatus' => 'Akun Anda sedang dalam status Hiatus. Silakan hubungi admin.',
                'Out' => 'Akun Anda sudah tidak aktif. Silakan hubungi admin.',
            ];
            $msg = $statusMessages[$user['status_akun']] ?? 'Akun Anda belum aktif. Silakan hubungi admin.';
            return ['success' => false, 'message' => $msg, 'status' => $user['status_akun']];
        }

        // 5. SUCCESS — reset lockout counters
        $this->userModel->resetLockout($user['id_nethera']);

        return [
            'success' => true,
            'user' => $user
        ];
    }

    /**
     * Register a new user.
     * Data is stored in SESSION first — only inserted into DB after OTP verification.
     * OTP is generated via CSPRNG and hashed before session storage.
     */
    public function register(array $data): array
    {
        // 1. Duplicate check against DB
        $exists = $this->userModel->groupStart()
            ->where('username', $data['username'])
            ->orWhere('email', $data['email'])
            ->orWhere('noHP', $data['noHP'])
            ->groupEnd()
            ->countAllResults();

        if ($exists > 0) {
            return ['success' => false, 'message' => 'Username, Email, atau No HP sudah terdaftar!'];
        }

        // 2. Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // 3. Generate OTP (CSPRNG)
        $otpCode = (string) random_int(100000, 999999);

        // 4. Store in SESSION (NOT database) — prevents ghost accounts
        $pendingData = $data;
        $pendingData['otp_code'] = hash('sha256', $otpCode);
        $pendingData['otp_expires'] = date('Y-m-d H:i:s', time() + 900); // 15 min
        $pendingData['otp_attempts'] = 0;
        $pendingData['status_akun'] = 'Pending';
        $pendingData['role'] = ROLE_NETHERA;

        session()->set('pending_registration', $pendingData);

        // 5. Send OTP email (plaintext OTP — only hash is in session)
        $mailResult = $this->mailService->sendOtp($data['email'], $data['username'], $otpCode);

        if (!$mailResult['success']) {
            session()->remove('pending_registration');
            return ['success' => false, 'message' => 'Gagal mengirim email verifikasi.'];
        }

        return ['success' => true];
    }

    /**
     * Verify OTP.
     * New flow: checks session data, then inserts into DB on success.
     * Legacy flow: falls back to UserModel for existing unverified DB users.
     */
    public function verifyOTP(string $username, string $code): bool
    {
        // --- New flow: session-based pending registration ---
        $pending = session('pending_registration');
        if ($pending && ($pending['username'] ?? '') === $username) {
            // Check expiry
            if (strtotime($pending['otp_expires']) < time()) {
                return false;
            }

            // Check attempt limit
            if (($pending['otp_attempts'] ?? 0) >= 5) {
                session()->remove('pending_registration');
                return false;
            }

            // Compare OTP hash
            if (hash('sha256', $code) !== $pending['otp_code']) {
                $pending['otp_attempts'] = ($pending['otp_attempts'] ?? 0) + 1;
                session()->set('pending_registration', $pending);
                return false;
            }

            // OTP verified! Insert user into DB
            $insertData = [
                'nama_lengkap' => $pending['nama_lengkap'],
                'username' => $pending['username'],
                'email' => $pending['email'],
                'noHP' => $pending['noHP'],
                'password' => $pending['password'],
                'tanggal_lahir' => $pending['tanggal_lahir'],
                'periode_masuk' => $pending['periode_masuk'],
                'status_akun' => $pending['status_akun'],
                'role' => $pending['role'],
                'email_verified_at' => date('Y-m-d H:i:s'),
                'otp_code' => null,
                'otp_expires' => null,
                'otp_attempts' => 0,
            ];

            $this->userModel->insert($insertData);
            session()->remove('pending_registration');

            log_message('info', "Registration completed for '{$username}' after OTP verification.");
            return true;
        }

        // --- Legacy flow: existing unverified users in DB ---
        return $this->userModel->verifyOTP($username, $code);
    }

    /**
     * Start forgot password process.
     * Token is hashed before storage. Always returns success to prevent enumeration.
     */
    public function forgotPassword(string $email): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            // Return success even for non-existent emails (anti-enumeration)
            log_message('info', "Forgot password: email not found '{$email}'");
            return ['success' => true];
        }

        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        $this->userModel->update($user['id_nethera'], [
            'reset_token' => $hashedToken,
            'token_expires' => date('Y-m-d H:i:s', time() + 1800), // 30 min
        ]);

        // Email the raw token — we hash on consumption
        $resetLink = base_url('reset-password?token=' . $rawToken);
        $result = $this->mailService->sendPasswordReset($email, $user['username'], $resetLink);

        if (!$result['success']) {
            log_message('error', "Failed to send reset email to '{$email}'");
            // Still return success to prevent enumeration
            return ['success' => true];
        }

        return ['success' => true];
    }

    /**
     * Reset password using token.
     * Hashes the incoming token and compares against stored hash.
     */
    public function resetPassword(string $rawToken, string $newPassword): array
    {
        $hashedToken = hash('sha256', $rawToken);

        $user = $this->userModel
            ->where('reset_token', $hashedToken)
            ->where('token_expires >=', date('Y-m-d H:i:s'))
            ->first();

        if (!$user) {
            return ['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.'];
        }

        $this->userModel->update($user['id_nethera'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'reset_token' => null,
            'token_expires' => null,
        ]);

        log_message('info', "Password reset successful for user ID: {$user['id_nethera']}");

        return ['success' => true];
    }

    /**
     * Check if user has a pending OTP.
     * Checks session first (new flow), then DB (legacy).
     */
    public function hasPendingOTP(string $username): bool
    {
        // New flow: check session
        $pending = session('pending_registration');
        if ($pending && ($pending['username'] ?? '') === $username) {
            return true;
        }

        // Legacy flow: check DB
        $user = $this->userModel->findByUsername($username);
        return $user && !empty($user['otp_code']);
    }

    /**
     * Resend OTP code to user email.
     * Uses CSPRNG, hashes before storage. 15 min expiry.
     * Checks session first (new flow), then DB (legacy).
     */
    public function resendOTP(string $username): array
    {
        // --- New flow: session-based pending registration ---
        $pending = session('pending_registration');
        if ($pending && ($pending['username'] ?? '') === $username) {
            $otpCode = (string) random_int(100000, 999999);
            $pending['otp_code'] = hash('sha256', $otpCode);
            $pending['otp_expires'] = date('Y-m-d H:i:s', time() + 900);
            $pending['otp_attempts'] = 0;
            session()->set('pending_registration', $pending);

            return $this->mailService->sendOtp($pending['email'], $pending['username'], $otpCode);
        }

        // --- Legacy flow: DB-based ---
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            return ['success' => false, 'message' => 'User tidak ditemukan.'];
        }

        $otpCode = (string) random_int(100000, 999999);
        $hashedOtp = hash('sha256', $otpCode);

        $this->userModel->update($user['id_nethera'], [
            'otp_code' => $hashedOtp,
            'otp_expires' => date('Y-m-d H:i:s', time() + 900),
            'otp_attempts' => 0,
        ]);

        return $this->mailService->sendOtp($user['email'], $user['username'], $otpCode);
    }
}
