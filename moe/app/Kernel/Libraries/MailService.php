<?php

namespace App\Kernel\Libraries;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * MailService - Centralized email sending service.
 * 
 * Ported from legacy moe/auth/handlers/ (register.php, resend_otp.php, forgot_password.php).
 * Uses PHPMailer with Gmail SMTP. Config from CI4 .env file.
 * 
 * Usage:
 *   $mail = new \App\Libraries\MailService();
 *   $mail->sendOtp($email, $username, $otpCode);
 *   $mail->sendPasswordReset($email, $username, $resetLink);
 */
class MailService
{
    private string $smtpHost;
    private string $smtpUser;
    private string $smtpPass;
    private int $smtpPort;
    private string $smtpSecure;
    private string $fromName;

    public function __construct()
    {
        // Load from CI4 .env
        $this->smtpHost = env('SMTP_HOST', 'smtp.gmail.com');
        $this->smtpUser = env('SMTP_USER', '');
        $this->smtpPass = env('SMTP_PASS', '');
        $this->smtpPort = (int) env('SMTP_PORT', 465);
        $this->smtpSecure = env('SMTP_SECURE', 'ssl'); // 'ssl' = SMTPS (port 465)
        $this->fromName = env('SMTP_FROM_NAME', 'MOE Registration');
    }

    /**
     * Create a pre-configured PHPMailer instance.
     */
    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpUser;
        $mail->Password = $this->smtpPass;
        $mail->SMTPSecure = $this->smtpSecure === 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $this->smtpPort;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($this->smtpUser, $this->fromName);

        return $mail;
    }

    // ─── OTP EMAIL ────────────────────────────────────────

    /**
     * Send OTP verification email.
     * Used during registration and OTP resend.
     *
     * @param string $toEmail Recipient email
     * @param string $username Recipient username
     * @param string $otpCode 6-digit OTP code
     * @return array{success:bool, error?:string}
     */
    public function sendOtp(string $toEmail, string $username, string $otpCode): array
    {
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            log_message('error', 'MailService: SMTP credentials not configured in .env');
            return ['success' => false, 'error' => 'SMTP credentials not configured'];
        }

        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $username);

            $mail->isHTML(true);
            $mail->Subject = 'Kode Verifikasi Akun Mediterranean of Egypt (OTP)';
            $mail->Body = $this->otpTemplate($username, $otpCode);

            $mail->send();
            log_message('info', "MailService: OTP email sent to {$toEmail}");
            return ['success' => true];

        } catch (Exception $e) {
            log_message('error', "MailService OTP Error: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── PASSWORD RESET EMAIL ─────────────────────────────

    /**
     * Send password reset email with token link.
     *
     * @param string $toEmail Recipient email
     * @param string $username Recipient username
     * @param string $resetLink Full URL with token
     * @return array{success:bool, error?:string}
     */
    public function sendPasswordReset(string $toEmail, string $username, string $resetLink): array
    {
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            log_message('error', 'MailService: SMTP credentials not configured in .env');
            return ['success' => false, 'error' => 'SMTP credentials not configured'];
        }

        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $username);

            $mail->isHTML(true);
            $mail->Subject = 'Permintaan Reset Password Akun MOE';
            $mail->Body = $this->resetTemplate($username, $resetLink);

            $mail->send();
            log_message('info', "MailService: Reset email sent to {$toEmail}");
            return ['success' => true];

        } catch (Exception $e) {
            log_message('error', "MailService Reset Error: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── HTML TEMPLATES ───────────────────────────────────
    // Egyptian gold-themed templates matching legacy design

    private function otpTemplate(string $username, string $otpCode): string
    {
        return <<<HTML
        <html>
        <body style="font-family: Lato, sans-serif; background-color: #0d0d0d; color: #fff; padding: 20px; text-align: center;">
            <div style="max-width: 500px; margin: auto; padding: 20px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; border: 1px solid #DAA520;">
                <h2 style="color: #DAA520; font-family: Cinzel;">Verifikasi Akun Nethera Anda</h2>
                <p>Halo <strong>{$username}</strong>,</p>
                <p>Berikut adalah kode 6 digit Anda:</p>
                <h1 style="color: #DAA520; font-size: 3rem; letter-spacing: 5px; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 5px;">
                    {$otpCode}
                </h1>
                <p>Kode ini berlaku selama 5 menit. Jangan bagikan kode ini kepada siapapun.</p>
                <hr style="border-color: rgba(255, 255, 255, 0.2);">
            </div>
        </body>
        </html>
        HTML;
    }

    private function resetTemplate(string $username, string $resetLink): string
    {
        return <<<HTML
        <html>
        <body style="font-family: Lato, sans-serif; background-color: #0d0d0d; color: #fff; padding: 20px;">
            <div style="max-width: 500px; margin: auto; padding: 20px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; border: 1px solid #DAA520;">
                <h2 style="color: #DAA520; font-family: Cinzel;">Reset Password</h2>
                <p>Halo <strong>{$username}</strong>,</p>
                <p>Anda telah meminta tautan reset password. Klik tombol di bawah ini:</p>
                <a href="{$resetLink}" style="display: inline-block; padding: 10px 20px; background-color: #DAA520; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px;">
                    Reset Password
                </a>
                <p style="margin-top: 20px; font-size: 0.9em; color: #aaa;">Tautan ini akan kedaluwarsa dalam 30 menit. Jika Anda tidak meminta reset ini, abaikan email ini.</p>
            </div>
        </body>
        </html>
        HTML;
    }
}
