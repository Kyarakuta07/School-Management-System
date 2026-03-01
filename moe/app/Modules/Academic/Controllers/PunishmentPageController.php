<?php

namespace App\Modules\Academic\Controllers;

use App\Kernel\BaseController;

use App\Modules\Academic\Models\PunishmentModel;
use App\Modules\User\Models\UserModel;
use App\Config\PunishmentConfig;

/**
 * PunishmentPageController
 * Handles the Punishment & Discipline page.
 * - Nethera: View their own punishment history and status
 * - Anubis/Vasiki: Manage punishments for all Nethera users
 *
 * Refactored: POST handlers and data loading extracted into private methods.
 */
class PunishmentPageController extends BaseController
{
    protected PunishmentModel $punishmentModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->punishmentModel = new PunishmentModel();
        $this->userModel = new UserModel();
    }

    /**
     * Main page: dispatches POST or renders view.
     */
    public function index()
    {
        $userId = (int) session()->get('id_nethera');
        $role = session()->get('role');
        $canManage = can('manage_punishment', $role);

        // PRG: handle POST actions first
        if ($this->request->getMethod() === 'POST' && $canManage) {
            return $this->handlePostAction($userId);
        }

        // GET: render page
        return $this->renderPage($userId, $role, $canManage);
    }

    // ------------------------------------------------------------------
    // POST Handlers
    // ------------------------------------------------------------------

    private function handlePostAction(int $userId)
    {
        $action = $this->request->getPost('action');

        switch ($action) {
            case 'add_punishment':
                return $this->addPunishment($userId);
            case 'release_punishment':
                return $this->releasePunishment($userId);
            default:
                return redirect()->to(base_url('punishment'));
        }
    }

    private function addPunishment(int $givenBy)
    {
        if (
            !$this->validate([
                'target_id' => 'required|integer|greater_than[0]',
                'jenis_pelanggaran' => 'required|max_length[255]',
                'deskripsi' => 'permit_empty|max_length[1000]',
                'jenis_hukuman' => 'required|max_length[100]',
                'poin' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            ])
        ) {
            session()->setFlashdata('punishment_error', implode(' ', $this->validator->getErrors()));
            return redirect()->to(base_url('punishment'));
        }

        $jenisHukuman = trim($this->request->getPost('jenis_hukuman') ?? '');

        // Determine locked features based on punishment type
        $lockedFeaturesMap = [
            'Warning' => '',                                    // No lock
            'Feature Lock' => 'trapeza,pet,class',                   // Lock main features
            'Suspension' => 'trapeza,pet,class,battle,rhythm',     // Lock ALL features
            'Probation' => 'trapeza,pet',                         // Limited lock
        ];
        $lockedFeatures = $lockedFeaturesMap[$jenisHukuman] ?? '';

        $this->punishmentModel->insert([
            'id_nethera' => (int) $this->request->getPost('target_id'),
            'jenis_pelanggaran' => trim($this->request->getPost('jenis_pelanggaran') ?? ''),
            'deskripsi_pelanggaran' => trim($this->request->getPost('deskripsi') ?? ''),
            'jenis_hukuman' => $jenisHukuman,
            'poin_pelanggaran' => (int) $this->request->getPost('poin'),
            'status_hukuman' => 'active',
            'locked_features' => $lockedFeatures,
            'given_by' => $givenBy,
        ]);

        session()->setFlashdata('punishment_success', 'Punishment berhasil ditambahkan!');
        return redirect()->to(base_url('punishment'));
    }

    private function releasePunishment(int $releasedBy)
    {
        $punishmentId = (int) $this->request->getPost('punishment_id');
        if ($punishmentId) {
            $this->punishmentModel->update($punishmentId, [
                'status_hukuman' => 'completed',
                'tanggal_selesai' => date('Y-m-d H:i:s'),
                'released_by' => $releasedBy,
            ]);
            session()->setFlashdata('punishment_success', 'Punishment berhasil dilepas!');
        }
        return redirect()->to(base_url('punishment'));
    }

    // ------------------------------------------------------------------
    // View Renderer
    // ------------------------------------------------------------------

    private function renderPage(int $userId, string $role, bool $canManage)
    {
        // Lock message from redirect
        $lockedFeature = $this->request->getGet('locked') ?? '';
        $lockedMessages = [
            'trapeza' => 'Akses ke Trapeza (Bank) dibatasi karena Anda memiliki hukuman aktif.',
            'pet' => 'Akses ke Pet System dibatasi karena Anda memiliki hukuman aktif.',
            'class' => 'Akses ke Class Schedule dibatasi karena Anda memiliki hukuman aktif.',
            'battle' => 'Akses ke Arena Battle dibatasi karena Anda memiliki hukuman aktif.',
            'rhythm' => 'Akses ke Rhythm Game dibatasi karena Anda memiliki hukuman aktif.',
        ];
        $lockMessage = $lockedMessages[$lockedFeature] ?? '';

        // User sanctuary name
        $userInfo = $this->userModel->getUserDashboardInfo($userId);
        $sanctuaryName = $userInfo['nama_sanctuary'] ?? 'Unknown';

        // Load punishment data
        $data = $this->loadPunishmentData($userId, $canManage);

        return view('App\Modules\Academic\Views\punishment', array_merge($data, [
            'activePage' => 'punishment',
            'role' => $role,
            'canManage' => $canManage,
            'lockMessage' => $lockMessage,
            'sanctuaryName' => $sanctuaryName,
            'violationTypes' => PunishmentConfig::getViolationTypes(),
            'punishmentTypes' => PunishmentConfig::getPunishmentTypes(),
            'codeOfConduct' => PunishmentConfig::getCodeOfConduct(),
            'actionMessage' => session()->getFlashdata('punishment_success') ?? '',
            'actionError' => session()->getFlashdata('punishment_error') ?? '',
        ]));
    }

    // ------------------------------------------------------------------
    // Data Loading
    // ------------------------------------------------------------------

    private function loadPunishmentData(int $userId, bool $canManage): array
    {
        $punishmentHistory = [];
        $activePunishments = [];
        $totalPunishmentPoints = 0;
        $allNethera = [];
        $pager = null;

        try {
            if ($canManage) {
                $punishmentHistory = $this->punishmentModel
                    ->select('punishment_log.*, nethera.nama_lengkap as user_name')
                    ->join('nethera', 'punishment_log.id_nethera = nethera.id_nethera')
                    ->orderBy('punishment_log.tanggal_pelanggaran', 'DESC')
                    ->paginate(20, 'history');

                $pager = $this->punishmentModel->pager;

                $activePunishments = $this->punishmentModel->db->table('punishment_log p')
                    ->select('p.*, n.nama_lengkap as user_name')
                    ->join('nethera n', 'p.id_nethera = n.id_nethera')
                    ->where('p.status_hukuman', 'active')
                    ->orderBy('p.tanggal_pelanggaran', 'DESC')
                    ->get()->getResultArray();

                $allNethera = $this->punishmentModel->db->table('nethera')
                    ->select('id_nethera, nama_lengkap')
                    ->where('role', 'Nethera')
                    ->orderBy('nama_lengkap', 'ASC')
                    ->get()->getResultArray();
            } else {
                $punishmentHistory = $this->punishmentModel->where('id_nethera', $userId)
                    ->orderBy('tanggal_pelanggaran', 'DESC')
                    ->paginate(10, 'history');

                $pager = $this->punishmentModel->pager;

                $activePunishments = $this->punishmentModel->getActivePunishments($userId);
            }

            $totalPunishmentPoints = $this->punishmentModel->getTotalPoints($userId);
        } catch (\Exception $e) {
            log_message('error', 'Punishment query error: ' . $e->getMessage());
        }

        return [
            'punishmentHistory' => $punishmentHistory,
            'activePunishments' => $activePunishments,
            'totalPunishmentPoints' => $totalPunishmentPoints,
            'allNethera' => $allNethera,
            'pager' => $pager,
        ];
    }
}
