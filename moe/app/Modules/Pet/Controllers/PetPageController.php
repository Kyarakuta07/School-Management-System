<?php

namespace App\Modules\Pet\Controllers;

use App\Kernel\BaseController;
use App\Modules\Pet\Models\PetModel;

/**
 * Pet Page Controller (serves pet.php HTML page)
 *
 * Refactored: all raw DB queries moved to PetModel.
 * Controller now only orchestrates data retrieval and view rendering.
 */
class PetPageController extends BaseController
{
    private PetModel $petModel;

    public function __construct()
    {
        $this->petModel = new PetModel();
    }

    public function index()
    {
        $session = session();

        $userId = (int) $session->get('id_nethera');
        if (!$userId) {
            return redirect()->to(base_url('login'));
        }
        $userName = htmlspecialchars($session->get('nama_lengkap') ?? '');
        $userRole = $session->get('role');

        // 1. User gold & sanctuary ID (via PetModel)
        $userInfo = $this->petModel->getUserGoldAndSanctuary($userId);
        $userGold = (int) ($userInfo['gold'] ?? 0);
        $sanctuaryId = $userInfo['id_sanctuary'] ?? null;

        // 2. Check for Beastiary Library upgrade (via PetModel)
        $hasBeastiary = $this->petModel->hasSanctuaryUpgrade((int) $sanctuaryId, 'Beastiary Library');

        // 3. Discovery stats (via GachaService)
        $discoveryStats = service('gachaService')->getDiscoveryStats($userId);

        // 4. All species for Bestiary (via PetModel — cached 24h)
        $allSpecies = $this->petModel->getAllSpecies();

        // 5. User discovery list (via PetModel)
        $discovery = $this->petModel->getUserDiscovery($userId);
        $discoveredIds = $discovery['discoveredIds'];
        $shinyDiscoveredIds = $discovery['shinyDiscoveredIds'];

        return view('App\Modules\Pet\Views\pet', [
            'pageTitle' => 'Pet Companion - MOE Virtual Academy',
            'bodyClass' => 'pet-page',
            'activePage' => 'pet',
            'userId' => $userId,
            'userName' => $userName,
            'userRole' => $userRole,
            'userGold' => $userGold,
            'hasBeastiary' => $hasBeastiary,
            'discoveryStats' => $discoveryStats,
            'allSpecies' => $allSpecies,
            'discoveredIds' => $discoveredIds,
            'shinyDiscoveredIds' => $shinyDiscoveredIds,
        ]);
    }
}
