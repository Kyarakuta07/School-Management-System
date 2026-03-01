<?php

namespace App\Modules\User\Controllers;

use App\Kernel\BaseController;

/**
 * PublicPageController - Serves public-facing pages that don't require auth.
 *
 * Routes handled:
 *   GET /staff    → staff()
 *   GET /classes  → classes()
 *   GET /world    → world()
 */
class PublicPageController extends BaseController
{
    /** Staff / Imperial Roster page */
    public function staff()
    {
        return view('App\Modules\User\Views\staff');
    }

    /** Classes / Ancient Knowledge page */
    public function classes()
    {
        return view('App\Modules\User\Views\classes_info');
    }

    /** World Map page */
    public function world()
    {
        return view('App\Modules\User\Views\world');
    }
}
