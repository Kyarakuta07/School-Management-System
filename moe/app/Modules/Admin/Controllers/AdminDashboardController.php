<?php

namespace App\Modules\Admin\Controllers;

use App\Kernel\BaseController;

/**
 * AdminDashboardController — Vasiki Dashboard with stats and charts.
 * Ported from legacy moe/admin/index.php.
 */
class AdminDashboardController extends BaseController
{
    public function index()
    {
        // Role enforced by route filter 'auth:Vasiki' in Routes.php

        $db = \Config\Database::connect();

        // 1. Total Active
        $totalNethera = $db->query("SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Aktif' AND role = 'Nethera'")->getRowArray()['total'] ?? 0;

        // 2. Pending
        $totalPending = $db->query("SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Pending' AND role = 'Nethera'")->getRowArray()['total'] ?? 0;

        // 3. Hiatus
        $totalHiatus = $db->query("SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Hiatus' AND role = 'Nethera'")->getRowArray()['total'] ?? 0;

        // 4. Out
        $totalOut = $db->query("SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Out' AND role = 'Nethera'")->getRowArray()['total'] ?? 0;

        // 5. Total All
        $totalAll = $totalNethera + $totalPending + $totalHiatus + $totalOut;

        // 6. Latest registrations
        $latestUsers = $db->query(
            "SELECT n.nama_lengkap, n.status_akun, n.periode_masuk, s.nama_sanctuary, n.created_at
             FROM nethera n
             LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
             WHERE n.role = 'Nethera'
             ORDER BY n.id_nethera DESC LIMIT 5"
        )->getResultArray();

        // 7. Chart: Members per sanctuary
        $chartData = $db->query(
            "SELECT s.nama_sanctuary, COUNT(n.id_nethera) as jumlah
             FROM sanctuary s
             LEFT JOIN nethera n ON s.id_sanctuary = n.id_sanctuary AND n.status_akun = 'Aktif' AND n.role = 'Nethera'
             GROUP BY s.nama_sanctuary ORDER BY s.id_sanctuary ASC"
        )->getResultArray();

        $sanctuaryLabels = array_column($chartData, 'nama_sanctuary');
        $sanctuaryValues = array_column($chartData, 'jumlah');

        // 8. Sanctuary count
        $sanctuaryCount = count($sanctuaryLabels);

        return view('App\Modules\Admin\Views\dashboard', [
            'pageTitle' => 'Vasiki Dashboard',
            'currentPage' => 'dashboard',
            'userName' => esc(session()->get('nama_lengkap') ?? ''),
            'totalAll' => $totalAll,
            'totalNethera' => $totalNethera,
            'totalPending' => $totalPending,
            'totalHiatus' => $totalHiatus,
            'totalOut' => $totalOut,
            'sanctuaryCount' => $sanctuaryCount,
            'latestUsers' => $latestUsers,
            'sanctuaryLabels' => $sanctuaryLabels,
            'sanctuaryValues' => $sanctuaryValues,
        ]);
    }
}
