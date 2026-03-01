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
                'Ditolak' => 'Pendaftaran akun Anda ditolak. Silakan hubungi admin.',
                'Diblokir' => 'Akun Anda telah diblokir. Silakan hubungi admin.',
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
     * OTP is generated via CSPRNG and hashed before storage.
     */
    public function register(array $data): array
    {
        // 1. Duplicate check
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

        // OTP: CSPRNG + hash before storage
        $otpCode = (string) random_int(100000, 999999);
        $data['otp_code'] = hash('sha256', $otpCode);
        $data['otp_expires'] = date('Y-m-d H:i:s', time() + 300); // 5 min
        $data['otp_attempts'] = 0;
        $data['status_akun'] = 'Pending';
        $data['role'] = ROLE_NETHERA;

        // 4. Atomic transaction: Insert + Send Email
        $db = \Config\Database::connect();
        $db->transStart();

        $this->userModel->insert($data);

        // Email the plaintext OTP (only the hash is stored)
        $mailResult = $this->mailService->sendOtp($data['email'], $data['username'], $otpCode);

        if (!$mailResult['success']) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Gagal mengirim email verifikasi.'];
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return ['success' => false, 'message' => 'Registrasi gagal. Silakan coba lagi.'];
        }

        return ['success' => true];
    }

    /**
     * Verify OTP — delegates to UserModel which handles
     * hash comparison, attempt limits, and email_verified_at.
     */
    public function verifyOTP(string $username, string $code): bool
    {
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
     */
    public function hasPendingOTP(string $username): bool
    {
        $user = $this->userModel->findByUsername($username);
        return $user && !empty($user['otp_code']);
    }

    /**
     * Resend OTP code to user email.
     * Uses CSPRNG, hashes before storage. 5 min expiry.
     */
    public function resendOTP(string $username): array
    {
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            return ['success' => false, 'message' => 'User tidak ditemukan.'];
        }

        $otpCode = (string) random_int(100000, 999999);
        $hashedOtp = hash('sha256', $otpCode);

        $this->userModel->update($user['id_nethera'], [
            'otp_code' => $hashedOtp,
            'otp_expires' => date('Y-m-d H:i:s', time() + 300), // 5 min
            'otp_attempts' => 0,
        ]);

        // Email plaintext OTP
        return $this->mailService->sendOtp($user['email'], $user['username'], $otpCode);
    }
}
