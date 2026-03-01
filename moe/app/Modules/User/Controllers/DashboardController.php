<?php

namespace App\Modules\User\Controllers;

use App\Kernel\BaseController;

use App\Modules\User\Models\UserModel;

/**
 * Dashboard Controller (serves Beranda/Home page)
 * Refactored: raw queries moved to UserModel and PetModel.
 */
class DashboardController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $session = session();
        $userId = (int) $session->get('id_nethera');
        $userName = htmlspecialchars($session->get('nama_lengkap') ?? '');
        $userRole = $session->get('role');

        // 1. User info with sanctuary (via UserModel)
        $userInfo = $this->userModel->getUserDashboardInfo($userId);

        // 2. Active pet (via PetService — decoupled from PetModel)
        $petService = service('petService');
        $activePet = $petService->getActivePet($userId);

        // Pet display helpers
        $petImage = null;
        $petDisplayName = null;
        $petBuffText = null;

        if ($activePet) {
            $level = (int) $activePet['level'];
            if ($level >= 15) {
                $petImage = base_url('assets/pets/' . $activePet['img_adult']);
            } elseif ($level >= 5) {
                $petImage = base_url('assets/pets/' . $activePet['img_baby']);
            } else {
                $petImage = base_url('assets/pets/' . ($activePet['img_egg'] ?? 'default/egg.png'));
            }
            $petDisplayName = $activePet['nickname'] ?? $activePet['species_name'] ?? 'Pet';
            $buffValue = $activePet['passive_buff_value'] ?? 0;
            $buffType = $activePet['passive_buff_type'] ?? 'none';
            $petBuffText = '+' . $buffValue . '% ' . ucfirst(str_replace('_', ' ', $buffType));
        }

        // 3. Time-based greeting (timezone set globally in Config/App.php)
        $hour = (int) date('G');
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Selamat Pagi';
            $greetingEmoji = '🌅';
        } elseif ($hour >= 12 && $hour < 17) {
            $greeting = 'Selamat Siang';
            $greetingEmoji = '☀️';
        } elseif ($hour >= 17 && $hour < 21) {
            $greeting = 'Selamat Sore';
            $greetingEmoji = '🌆';
        } else {
            $greeting = 'Selamat Malam';
            $greetingEmoji = '🌙';
        }

        $canAccessAdmin = in_array($userRole, [ROLE_VASIKI, ROLE_ANUBIS, ROLE_HAKAES]);

        return view('App\Modules\User\Views\beranda', [
            'pageTitle' => 'Beranda - ' . ($userInfo['nama_sanctuary'] ?? 'MOE'),
            'activePage' => 'beranda',
            'userName' => $userName,
            'userRole' => $userRole,
            'userInfo' => $userInfo,
            'activePet' => $activePet,
            'petImage' => $petImage,
            'petDisplayName' => $petDisplayName,
            'petBuffText' => $petBuffText,
            'greeting' => $greeting,
            'greetingEmoji' => $greetingEmoji,
            'canAccessAdmin' => $canAccessAdmin,
            'sanctuaryName' => $userInfo['nama_sanctuary'] ?? '',
            'sanctuaryDesc' => $userInfo['deskripsi'] ?? '',
            'profilePhoto' => $userInfo['profile_photo'] ?? '',
            'funFact' => $userInfo['fun_fact'] ?? 'Belum ada funfact.',
            'factionSlug' => strtolower(str_replace(' ', '', $userInfo['faction_slug'] ?? 'ammit')),
        ]);
    }
}
