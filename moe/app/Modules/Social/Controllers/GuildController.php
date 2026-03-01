<?php

namespace App\Modules\Social\Controllers;

use App\Kernel\BaseController;

use App\Modules\Sanctuary\Models\SanctuaryModel;
use App\Modules\Sanctuary\Repositories\SanctuaryRepository;

/**
 * GuildController
 * 
 * Handles the public Guild Hall page.
 * Ported from legacy moe/user/guild_hall.php
 * 
 * Routes:
 *   GET /guild?faction=horus → index()
 */
class GuildController extends BaseController
{
    /**
     * Display Guild Hall page for a specific faction.
     * Accepts faction as URL segment (/guild/horus) or query param (/guild?faction=horus).
     */
    public function index($factionSegment = null)
    {
        // Support both /guild/horus and /guild?faction=horus
        $factionSlug = $factionSegment ?? $this->request->getGet('faction') ?? '';
        $factionSlug = strtolower(trim($factionSlug));

        $sanctuaryModel = new SanctuaryModel();
        $sanctuaryRepo = new SanctuaryRepository();

        // Whitelist-validated lookup
        $sanctuary = $sanctuaryModel->getSanctuaryBySlug($factionSlug);

        if (!$sanctuary) {
            log_message('warning', "Invalid faction access attempt: " . substr($factionSlug, 0, 50));
            return redirect()->to(base_url('beranda'));
        }

        $sanctuaryId = (int) $sanctuary['id_sanctuary'];

        // Fetch guild data
        $memberCount = $sanctuaryModel->getMemberCount($sanctuaryId);
        $totalPP = $sanctuaryRepo->getTotalPP($sanctuaryId);
        $leadership = $sanctuaryRepo->getLeaders($sanctuaryId);
        $members = $sanctuaryRepo->getMembers($sanctuaryId);

        // Check if current user is a member
        $userId = session()->get('id_nethera');
        $isMember = $userId ? $sanctuaryModel->isUserMember($userId, $sanctuaryId) : false;

        return view('App\Modules\Social\Views\guild', [
            'pageTitle' => esc($sanctuary['nama_sanctuary']) . ' - Guild Hall',
            'bodyClass' => 'page-guild',
            'sanctuary' => $sanctuary,
            'factionSlug' => $factionSlug,
            'memberCount' => $memberCount,
            'totalPP' => $totalPP,
            'hosa' => $leadership['hosa'],
            'viziers' => $leadership['viziers'],
            'members' => $members,
            'isMember' => $isMember,
        ]);
    }
}
