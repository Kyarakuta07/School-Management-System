<?php

namespace App\Modules\Social\Controllers;

use App\Kernel\BaseController;

/**
 * ImportBeatmapPageController — Admin-only page for importing osu! beatmaps.
 * Ported from legacy moe/user/import_beatmap.php.
 */
class ImportBeatmapPageController extends BaseController
{
    public function index()
    {
        // Only Vasiki (admin) can access
        if (session()->get('role') !== ROLE_VASIKI) {
            return redirect()->to(base_url('beranda'));
        }

        return view('App\Modules\Social\Views\import_beatmap');
    }
}
