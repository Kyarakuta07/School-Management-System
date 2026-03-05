<?php

namespace App\Kernel\Controllers;

use App\Kernel\BaseController;

/**
 * SessionController — Lightweight session ping endpoint.
 *
 * Used by session_guard.js to:
 * 1. Keep the session alive (heartbeat)
 * 2. Return a fresh CSRF token after tab-resume
 * 3. Report session validity for the frontend
 */
class SessionController extends BaseController
{
    /**
     * Ping — touch the session and return a fresh CSRF token.
     *
     * If the AuthFilter passes, the session is still valid.
     * We just return the current CSRF hash so JS can update it.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function ping()
    {
        $session = \Config\Services::session();
        $security = \Config\Services::security();

        // Touch last_activity to reset the expiration timer
        $session->set('last_activity', time());

        return $this->response->setJSON([
            'success' => true,
            'csrf_token' => $security->getHash(),
            'expires_in' => config('Session')->expiration,
            'timestamp' => time(),
        ]);
    }
}
