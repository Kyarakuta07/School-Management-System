<?php

namespace App\Kernel\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Academic\Models\PunishmentModel;

/**
 * PunishmentFilter — Enforces active punishments by blocking access to locked features.
 * 
 * Punishment types and their effects:
 *   - Warning:      No feature lock (informational only)
 *   - Feature Lock:  Locks trapeza, pet, class
 *   - Suspension:    Locks ALL features (trapeza, pet, class, battle, rhythm)
 *   - Probation:     Locks trapeza, pet
 */
class PunishmentFilter implements FilterInterface
{
    /**
     * Map of route segments → feature names used in locked_features column.
     */
    private const ROUTE_FEATURE_MAP = [
        'class' => 'class',
        'pet' => 'pet',
        'trapeza' => 'trapeza',
        'battle' => 'battle',
        'battle-3v3' => 'battle',
        'rhythm' => 'rhythm',
        'subject' => 'class',
    ];

    /**
     * @param RequestInterface $request
     * @param array|null       $arguments Route-specific arguments (unused)
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId = (int) $session->get('id_nethera');
        $role = $session->get('role');

        // Don't enforce on admins/managers
        if (!$userId || in_array($role, [ROLE_ANUBIS, ROLE_VASIKI])) {
            return;
        }

        // Determine which feature the current route maps to
        $uri = trim($request->getUri()->getPath(), '/');
        $segments = explode('/', $uri);

        // Get the relevant segment (after the base path)
        // URL pattern: /School-Management-System/ci4_poc/public/{feature}
        $featureSegment = end($segments); // Last segment = feature name

        // Also check the segment before last for nested routes
        $feature = self::ROUTE_FEATURE_MAP[$featureSegment] ?? null;
        if (!$feature) {
            return; // Route not protected
        }

        // Check if this feature is locked for the user
        $punishmentModel = new PunishmentModel();
        if ($punishmentModel->isFeatureLocked($userId, $feature)) {
            return redirect()->to(base_url("punishment?locked={$feature}"));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
