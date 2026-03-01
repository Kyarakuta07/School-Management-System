<?php

namespace App\Modules\Admin\Controllers;

use App\Kernel\BaseController;

/**
 * AdminNetheraController — User management CRUD for Vasiki admin.
 * Ported from legacy moe/admin/pages/manage_nethera.php, edit_nethera.php,
 * proses_update_nethera.php, delete_nethera.php, ajax_search_nethera.php.
 */
class AdminNetheraController extends BaseController
{
    protected $userModel;
    protected $sanctuaryModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->userModel = new \App\Modules\User\Models\UserModel();
        $this->sanctuaryModel = new \App\Modules\Sanctuary\Models\SanctuaryModel();
    }
    /**
     * List all Nethera users with status counts.
     * GET /admin/nethera
     */
    public function index()
    {
        // Role enforced by route filter 'auth:Vasiki' in Routes.php

        // 1. Consolidated Aggregate Counts
        $countsQuery = $this->userModel->getStatusCounts();

        $totalCount = 0;
        $statusCounts = [
            'Aktif' => 0,
            'Hiatus' => 0,
            'Out' => 0
        ];

        foreach ($countsQuery as $row) {
            $totalCount += (int) $row['total'];
            if (isset($statusCounts[$row['status_akun']])) {
                $statusCounts[$row['status_akun']] = (int) $row['total'];
            }
        }

        // 2. Paginated User List
        $perPage = 15;
        $allNethera = $this->userModel->getNetheraWithSanctuary($perPage);
        $pager = $this->userModel->pager;

        return view('App\Modules\Admin\Views\manage_nethera', [
            'pageTitle' => 'Manage Nethera',
            'currentPage' => ROLE_NETHERA,
            'totalCount' => $totalCount,
            'aktifCount' => $statusCounts['Aktif'],
            'hiatusCount' => $statusCounts['Hiatus'],
            'outCount' => $statusCounts['Out'],
            'allNethera' => $allNethera,
            'pager' => $pager->links('default', 'default_full'),
        ]);
    }

    /**
     * Edit form for a single Nethera user.
     * GET /admin/nethera/edit/{id}
     */
    public function edit($id = 0)
    {
        // Role enforced by route filter 'auth:Vasiki' in Routes.php

        $id = (int) $id;
        $netheraData = $this->userModel->find($id);

        if (!$netheraData) {
            return redirect()->to(base_url('admin/nethera'))->with('error', 'User not found.');
        }

        $sanctuaries = $this->sanctuaryModel->orderBy('nama_sanctuary', 'ASC')->findAll();

        return view('App\Modules\Admin\Views\edit_nethera', [
            'pageTitle' => 'Edit Nethera',
            'currentPage' => ROLE_NETHERA,
            'extraCss' => ['edit_form.css'],
            'netheraData' => $netheraData,
            'sanctuaries' => $sanctuaries,
        ]);
    }

    /**
     * Process user update (with auto-registration number).
     * POST /admin/nethera/update
     */
    public function update()
    {
        // Role enforced by route filter 'auth:Vasiki' in Routes.php

        if (
            !$this->validate([
                'id_nethera' => 'required|integer|greater_than[0]',
                'nama_lengkap' => 'required|min_length[3]|max_length[100]',
                'username' => 'required|alpha_numeric|min_length[3]|max_length[50]',
                'status_akun' => 'required|in_list[Aktif,Pending,Out]',
                'noHP' => 'permit_empty|min_length[10]|max_length[15]',
                'password' => 'permit_empty|min_length[8]',
            ])
        ) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $idNethera = (int) $this->request->getPost('id_nethera');
        $namaLengkap = $this->request->getPost('nama_lengkap');
        $username = $this->request->getPost('username');
        $postedNoReg = $this->request->getPost('no_registrasi');
        $idSanctuary = $this->request->getPost('id_sanctuary');
        $periodeMasuk = $this->request->getPost('periode_masuk');
        $statusAkun = $this->request->getPost('status_akun');
        $noHP = $this->request->getPost('noHP');
        $password = $this->request->getPost('password');

        // Get old data
        $dataLama = $this->userModel->find($idNethera);
        if (!$dataLama) {
            return redirect()->to(base_url('admin/nethera'))->with('error', 'User not found.');
        }

        $finalNoReg = $postedNoReg;

        // Auto-generate registration number when status changes to Aktif
        if ($statusAkun === 'Aktif' && empty($dataLama['no_registrasi'])) {
            if (empty($idSanctuary)) {
                return redirect()->to(base_url('admin/nethera/edit/' . $idNethera))->with('error', 'Wajib pilih Sanctuary sebelum mengaktifkan.');
            }

            $sanctuary = $this->sanctuaryModel->find($idSanctuary);
            if ($sanctuary) {
                $namaSanctuary = strtoupper($sanctuary['nama_sanctuary']);
                $prefix = $namaSanctuary . '_' . $periodeMasuk . '_';
                $prefixPattern = $prefix . '%';

                $lastReg = $this->userModel->where('no_registrasi LIKE', $prefixPattern)
                    ->orderBy('LENGTH(no_registrasi)', 'DESC')
                    ->orderBy('no_registrasi', 'DESC')
                    ->first();

                if ($lastReg) {
                    $parts = explode('_', $lastReg['no_registrasi']);
                    $newNumber = (int) end($parts) + 1;
                } else {
                    $newNumber = 1;
                }

                $finalNoReg = $prefix . $newNumber;
            }
        }

        // Build update
        $updateData = [
            'nama_lengkap' => $namaLengkap,
            'username' => $username,
            'no_registrasi' => $finalNoReg,
            'id_sanctuary' => $idSanctuary,
            'periode_masuk' => $periodeMasuk,
            'status_akun' => $statusAkun,
            'noHP' => $noHP,
        ];

        // Password update (only if provided)
        if (!empty($password)) {
            $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->userModel->update($idNethera, $updateData);

        return redirect()->to(base_url('admin/nethera'))->with('success', 'Data berhasil diupdate.');
    }

    /**
     * Delete (or deactivate) a Nethera user.
     * POST /admin/nethera/delete
     */
    public function delete()
    {
        // Role enforced by route filter 'auth:Vasiki' in Routes.php

        $id = (int) $this->request->getPost('id_nethera');

        // Soft delete: change status to 'Out'
        $this->userModel->update($id, ['status_akun' => 'Out']);

        return redirect()->to(base_url('admin/nethera'))->with('success', 'User berhasil dihapus.');
    }

    /**
     * AJAX search endpoint for Nethera users.
     * GET /admin/nethera/search?q=...&status=...
     */
    public function search()
    {
        // Role enforced by route filter 'auth:Vasiki' in Routes.php

        $query = trim($this->request->getGet('q') ?? '');
        $status = $this->request->getGet('status') ?? '';

        $results = $this->userModel->searchWithSanctuary($query, $status);

        return $this->response->setJSON(['data' => $results]);
    }
}
