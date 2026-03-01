<?php

namespace App\Modules\Battle\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Kernel\Libraries\RateLimiter;

/**
 * Arena Quota Filter
 * 
 * Enforces a daily battle quota (e.g. 5 battles/day) with a fixed reset time.
 */
class ArenaQuotaFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = \Config\Services::session();
        $userId = $session->get('id_nethera');

        if (!$userId) {
            return null; // AuthFilter will handle unauthorized users
        }

        $arenaName = $arguments[0] ?? 'arena_battle';
        $limit = (int) ($arguments[1] ?? 5);
        $resetHour = 0; // 00:00 WIB (Midnight)

        $limiter = new RateLimiter();
        // Check-only: don't deduct quota here. Let the controller deduct after successful battle start.
        $status = $limiter->getDailyStatus((string) $userId, $arenaName, $limit, $resetHour, 'Asia/Jakarta');

        if ($status['remaining'] <= 0) {
            return \Config\Services::response()
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'error' => "Batas harian tercapai! Kamu hanya bisa bermain {$limit} kali per hari.",
                    'remaining' => 0,
                    'resets_at' => $status['resets_at'],
                    'message' => "Reset berikutnya pada " . date('d M Y, H:i', strtotime($status['resets_at'])) . " WIB"
                ]);
        }

        return null; // Proceed
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
