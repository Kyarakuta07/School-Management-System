<?php

namespace App\Kernel\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Authentication Filter
 *
 * Checks legacy session for authentication and role-based access.
 * Only legacy session is checked (not Shield) because the dual-auth
 * bridge can desync after session regeneration.
 */
class AuthFilter implements FilterInterface
{
    /**
     * Check if the user has an active session and the correct role.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = \Config\Services::session();
        $isApi = $request->isAJAX() || strpos($request->getPath(), 'api/') === 0;

        // Check session
        if (!$session->get('id_nethera')) {
            if ($isApi) {
                return \Config\Services::response()
                    ->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'error' => 'Authentication required',
                        'code' => 'AUTH_REQUIRED',
                    ]);
            }

            return redirect()->to(base_url('login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        // Check role
        if (!empty($arguments)) {
            $userRole = strtolower($session->get('role') ?? '');
            $allowedRoles = array_map('strtolower', $arguments);

            if (!in_array($userRole, $allowedRoles, true)) {
                if ($isApi) {
                    return \Config\Services::response()
                        ->setStatusCode(403)
                        ->setJSON([
                            'success' => false,
                            'error' => 'Access denied. Your role does not have permission.',
                            'code' => 'FORBIDDEN',
                        ]);
                }

                return redirect()->to(base_url('beranda'))->with('error', 'Access denied.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed.
    }
}
