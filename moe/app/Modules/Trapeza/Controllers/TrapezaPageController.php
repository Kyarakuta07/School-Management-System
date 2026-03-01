<?php

namespace App\Modules\Trapeza\Controllers;

use CodeIgniter\Controller;

/**
 * Trapeza (Bank) Page Controller
 */
class TrapezaPageController extends Controller
{
    public function index()
    {
        $session = session();

        return view('App\Modules\Trapeza\Views\trapeza', [
            'pageTitle' => 'Trapeza Bank - MOE Virtual Academy',
            'activePage' => 'trapeza',
            'userId' => $session->get('id_nethera'),
            'userName' => htmlspecialchars($session->get('nama_lengkap') ?? ''),
            'userRole' => $session->get('role'),
        ]);
    }
}
