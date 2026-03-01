<?php

namespace App\Modules\Sanctuary\Controllers;

use App\Kernel\BaseController;

use App\Modules\Sanctuary\Services\SanctuaryService;
use App\Modules\User\Services\ActivityLogService;
use App\Config\GameConfig;

/**
 * SanctuaryPageController — Serves My Sanctuary and Guild Hall pages.
 * Ported from legacy moe/user/my_sanctuary.php and guild_hall.php.
 * 
 * REFACTORED: Business logic moved to SanctuaryService
 */
class SanctuaryPageController extends BaseController
{
    protected $activityLog;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        \helper(['url', 'form', 'common']);
        $this->activityLog = service('activityLog');
    }

    /**
     * My Sanctuary page — treasury, upgrades, daily rewards, member list.
     * Handles both GET (display) and POST (donate/upgrade/daily_claim).
     */
    public function index()
    {
        $userId = \Config\Services::session()->get('id_nethera');
        $db = \Config\Database::connect();

        // Initialize Models
        $sanctuaryModel = new \App\Modules\Sanctuary\Models\SanctuaryModel();
        $sanctuaryRepo = new \App\Modules\Sanctuary\Repositories\SanctuaryRepository();
        $petService = service('petService');
        $userModel = new \App\Modules\User\Models\UserModel();

        // Get user's sanctuary ID and role
        $userRow = $db->table('nethera')->select('id_sanctuary, sanctuary_role, gold')->where('id_nethera', $userId)->get()->getRowArray();
        if (!$userRow || empty($userRow['id_sanctuary'])) {
            return redirect()->to(base_url('beranda'))->with('error', 'Sanctuary not found.');
        }

        $sanctuaryId = (int) $userRow['id_sanctuary'];
        $userRole = $userRow['sanctuary_role'] ?? 'member';
        $userGold = (int) ($userRow['gold'] ?? 0);

        // Get consolidated sanctuary stats (Query 1)
        $sanctuaryStats = $sanctuaryRepo->getSanctuaryStats($sanctuaryId);
        if (!$sanctuaryStats) {
            return redirect()->to(base_url('beranda'))->with('error', 'Sanctuary data missing.');
        }

        $sanctuaryName = $sanctuaryStats['nama_sanctuary'];
        $sanctuaryGold = (int) $sanctuaryStats['gold'];
        $memberCount = (int) $sanctuaryStats['member_count'];
        $totalPp = (int) $sanctuaryStats['total_pp'];
        $factionSlug = strtolower($sanctuaryName);
        $isLeader = in_array($userRole, ['hosa', 'vizier']);

        // Leadership (Query 2)
        $leaders = $sanctuaryRepo->getLeaders($sanctuaryId);
        $hosa = $leaders['hosa'];
        $viziers = $leaders['viziers'];

        // Regular Members (Query 3)
        $members = $sanctuaryRepo->getMembers($sanctuaryId);

        // Upgrades (Query 4)
        $upgradesRaw = $db->query("SELECT upgrade_type, level FROM sanctuary_upgrades WHERE sanctuary_id = ?", [$sanctuaryId])->getResultArray();
        $upgrades = [];
        foreach ($upgradesRaw as $u)
            $upgrades[$u['upgrade_type']] = $u['level'];

        // Initialize SanctuaryService
        $sanctuaryService = service('sanctuaryService');
        $upgradeConfig = $sanctuaryService->getUpgradeConfig();

        // Active pet for daily reward (Query 5)
        $activePet = $petService->getActivePet($userId);

        // Daily claim (Using Service)
        $dailyStatus = $sanctuaryService->canClaimDaily($userId, $sanctuaryId);
        $canClaimDaily = $dailyStatus['canClaim'];
        $nextClaimTime = $dailyStatus['nextClaimTime'];

        // Pet cap check
        $petAtCap = false;
        $petStageName = 'Baby';
        if ($activePet) {
            $currentStage = $activePet['evolution_stage'] ?? 'egg';
            $levelCap = GameConfig::getPetLevelCap($currentStage);
            $petAtCap = ($activePet['level'] >= $levelCap);
            $petStageName = GameConfig::getPetStageName($currentStage);
        }

        // Handle POST actions
        [$actionMessage, $actionSuccess, $userGold, $sanctuaryGold, $canClaimDaily, $nextClaimTime, $upgrades] = $this->handlePostAction(
            $userId,
            $sanctuaryId,
            $userGold,
            $sanctuaryGold,
            $isLeader,
            $sanctuaryService,
            $upgrades,
            $activePet,
            $canClaimDaily,
            $nextClaimTime
        );

        return view('App\Modules\Sanctuary\Views\my_sanctuary', [
            'sanctuaryName' => $sanctuaryName,
            'userRole' => $userRole,
            'factionSlug' => $factionSlug,
            'isLeader' => $isLeader,
            'memberCount' => $memberCount,
            'totalPp' => $totalPp,
            'hosa' => $hosa,
            'viziers' => $viziers,
            'members' => $members,
            'sanctuaryGold' => $sanctuaryGold,
            'userGold' => $userGold,
            'upgrades' => $upgrades,
            'upgradeConfig' => $upgradeConfig,
            'activePet' => $activePet,
            'canClaimDaily' => $canClaimDaily,
            'nextClaimTime' => $nextClaimTime,
            'petAtCap' => $petAtCap,
            'petStageName' => $petStageName,
            'actionMessage' => $actionMessage,
            'actionSuccess' => $actionSuccess,
        ]);
    }

    /**
     * Handle POST actions (donate, upgrade, daily_claim).
     * Extracted from index() to reduce method size.
     */
    private function handlePostAction(
        int $userId,
        int $sanctuaryId,
        int $userGold,
        int $sanctuaryGold,
        bool $isLeader,
        $sanctuaryService,
        array $upgrades,
        ?array $activePet,
        bool $canClaimDaily,
        int $nextClaimTime
    ): array {
        $actionMessage = '';
        $actionSuccess = false;

        if ($this->request->getMethod() !== 'POST') {
            return [$actionMessage, $actionSuccess, $userGold, $sanctuaryGold, $canClaimDaily, $nextClaimTime, $upgrades];
        }

        $action = $this->request->getPost('action');

        if ($action === 'donate') {
            $donateAmount = (int) $this->request->getPost('amount');
            $result = $sanctuaryService->processDonation($userId, $sanctuaryId, $donateAmount, $userGold, $sanctuaryGold);
            $actionMessage = $result['message'];
            $actionSuccess = $result['success'];
            if ($result['success']) {
                $userGold = $result['newUserGold'];
                $sanctuaryGold = $result['newSanctuaryGold'];
                $this->activityLog->log('DONATE', 'SANCTUARY', "Donated {$donateAmount} gold to sanctuary ID: {$sanctuaryId}", $userId);
            }
        }

        if ($action === 'upgrade' && $isLeader) {
            $upgradeType = (string) $this->request->getPost('upgrade_type');
            $result = $sanctuaryService->purchaseUpgrade($sanctuaryId, $upgradeType, $sanctuaryGold, $upgrades);
            $actionMessage = $result['message'];
            $actionSuccess = $result['success'];
            if ($result['success']) {
                $sanctuaryGold = $result['newBalance'];
                $upgrades[$upgradeType] = 1;
                $this->activityLog->log('UPGRADE', 'SANCTUARY', "Purchased upgrade '{$upgradeType}' for sanctuary ID: {$sanctuaryId}", $userId);
            }
        }

        if ($action === 'daily_claim') {
            $result = $sanctuaryService->processDailyClaim($userId, $sanctuaryId, $upgrades, $activePet);
            $actionMessage = $result['message'];
            $actionSuccess = $result['success'];
            if ($result['success']) {
                $userGold += $result['goldEarned'];
                $canClaimDaily = false;
                $nextClaimTime = time() + (24 * 60 * 60);
            }
        }

        return [$actionMessage, $actionSuccess, $userGold, $sanctuaryGold, $canClaimDaily, $nextClaimTime, $upgrades];
    }

    /**
     * Guild Hall — faction-based public sanctuary page.
     * GET /guild/{faction}
     */
    public function guild($faction = '')
    {
        $db = \Config\Database::connect();
        $sanctuaryModel = new \App\Modules\Sanctuary\Models\SanctuaryModel();
        $sanctuaryRepo = new \App\Modules\Sanctuary\Repositories\SanctuaryRepository();

        // Get sanctuary by faction slug (whitelist-validated inside model)
        $sanctuary = $sanctuaryModel->getSanctuaryBySlug($faction);
        if (!$sanctuary) {
            return redirect()->to(base_url('beranda'))->with('error', 'Guild not found.');
        }

        $sanctuaryId = (int) $sanctuary['id_sanctuary'];

        // Get consolidated stats (member count, total PP, etc.)
        $stats = $sanctuaryRepo->getSanctuaryStats($sanctuaryId);

        // Get leaders
        $leadersResult = $sanctuaryRepo->getLeaders($sanctuaryId);
        $leaders = array_merge([$leadersResult['hosa']], $leadersResult['viziers']);
        $leaders = array_filter($leaders); // Remove null if hosa is missing

        // Get members
        $members = $sanctuaryRepo->getMembers($sanctuaryId);

        return view('App\Modules\Sanctuary\Views\guild_hall', [
            'sanctuary' => $sanctuary,
            'leaders' => $leaders,
            'members' => $members,
            'memberCount' => $stats['member_count'] ?? 0,
            'totalPp' => $stats['total_pp'] ?? 0,
            'factionSlug' => strtolower($sanctuary['nama_sanctuary']),
        ]);
    }

    // Helper: Safe avatar URL
    public static function getSafeAvatarUrl($photoFilename): string
    {
        if (empty($photoFilename))
            return '';
        $safeFilename = basename($photoFilename);
        if (!preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $safeFilename))
            return '';
        return 'assets/uploads/profiles/' . $safeFilename;
    }
}
