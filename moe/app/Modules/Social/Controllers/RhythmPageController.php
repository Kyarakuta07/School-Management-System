<?php

namespace App\Modules\Social\Controllers;

use App\Kernel\BaseController;

/**
 * Rhythm Game Page Controller
 */
class RhythmPageController extends BaseController
{
    public function index()
    {
        $session = session();

        return view('App\Modules\Social\Views\rhythm_game', [
            'pageTitle' => 'Rhythm Game - MOE Virtual Academy',
            'bodyClass' => 'rhythm-page',
            'activePage' => 'pet',
            'userId' => $session->get('id_nethera'),
            'userName' => htmlspecialchars($session->get('nama_lengkap') ?? ''),
            'userRole' => $session->get('role'),
        ]);
    }
}
