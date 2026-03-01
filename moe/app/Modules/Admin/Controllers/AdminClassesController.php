<?php

namespace App\Modules\Admin\Controllers;

use App\Kernel\BaseController;

/**
 * AdminClassesController — Grade and Schedule management.
 * Ported from legacy manage_classes.php, add/edit_grade.php, add/edit_schedule.php,
 * and their corresponding process/delete files.
 */
class AdminClassesController extends BaseController
{
    protected $gradeModel;
    protected $gradeRepo;
    protected $scheduleModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->gradeModel = new \App\Modules\Academic\Models\GradeModel();
        $this->gradeRepo = new \App\Modules\Academic\Repositories\GradeRepository();
        $this->scheduleModel = new \App\Modules\Academic\Models\ScheduleModel();
    }

    /**
     * Main classes page: chart, schedule table, grade table.
     * GET /admin/classes
     */
    public function index()
    {
        // Sanctuary points chart
        $chartData = $this->gradeRepo->getSanctuaryPoints();

        $sanctuaryLabels = array_column($chartData, 'nama_sanctuary');
        $sanctuaryPoints = array_column($chartData, 'total_points');

        // All schedules
        $allSchedules = $this->scheduleModel->orderBy('id_schedule', 'ASC')->findAll();

        // All grades with student names
        $allGrades = $this->gradeRepo->getGradesWithInfo();

        return view('App\Modules\Admin\Views\manage_classes', [
            'pageTitle' => 'Manage Classes',
            'currentPage' => 'classes',
            'sanctuaryLabels' => $sanctuaryLabels,
            'sanctuaryPoints' => $sanctuaryPoints,
            'allSchedules' => $allSchedules,
            'allGrades' => $allGrades,
        ]);
    }

    // ========== GRADE CRUD ==========

    /**
     * Add grade form.
     * GET /admin/grades/add
     */
    public function addGrade()
    {
        $userModel = new \App\Modules\User\Models\UserModel();
        $activeNethera = $userModel->where('status_akun', 'Aktif')->orderBy('nama_lengkap', 'ASC')->findAll();

        return view('App\Modules\Admin\Views\add_grade', [
            'pageTitle' => 'Add Grade',
            'currentPage' => 'classes',
            'extraCss' => ['edit_schedule.css'],
            'activeNethera' => $activeNethera,
        ]);
    }

    public function storeGrade()
    {
        if (
            !$this->validate([
                'id_nethera' => 'required|integer|greater_than[0]',
                'pop_culture' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'mythology' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'history_of_egypt' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'oceanology' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'astronomy' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            ])
        ) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $history = (int) $this->request->getPost('history');
        $popCulture = (int) $this->request->getPost('pop_culture');
        $mythology = (int) $this->request->getPost('mythology');
        $historyOfEgypt = (int) $this->request->getPost('history_of_egypt');
        $oceanology = (int) $this->request->getPost('oceanology');
        $astronomy = (int) $this->request->getPost('astronomy');
        $totalPP = $history + $popCulture + $mythology + $historyOfEgypt + $oceanology + $astronomy;

        $this->gradeModel->insert([
            'id_nethera' => (int) $this->request->getPost('id_nethera'),
            'class_name' => trim($this->request->getPost('class_name')),
            'history' => $history,
            'pop_culture' => $popCulture,
            'mythology' => $mythology,
            'history_of_egypt' => $historyOfEgypt,
            'oceanology' => $oceanology,
            'astronomy' => $astronomy,
            'total_pp' => $totalPP,
        ]);

        return redirect()->to(base_url('admin/classes'))->with('success', 'Nilai berhasil ditambahkan.');
    }

    /**
     * Edit grade form.
     * GET /admin/grades/edit/{id}
     */
    public function editGrade($id = 0)
    {
        $gradeData = $this->gradeModel->select('class_grades.*, nethera.nama_lengkap')
            ->join('nethera', 'class_grades.id_nethera = nethera.id_nethera')
            ->where('class_grades.id_grade', (int) $id)
            ->first();

        if (!$gradeData) {
            return redirect()->to(base_url('admin/classes'))->with('error', 'Grade not found.');
        }

        return view('App\Modules\Admin\Views\edit_grade', [
            'pageTitle' => 'Edit Nilai Kelas',
            'currentPage' => 'classes',
            'extraCss' => ['edit_schedule.css'],
            'gradeData' => $gradeData,
        ]);
    }

    public function updateGrade()
    {
        if (
            !$this->validate([
                'id_grade' => 'required|integer|greater_than[0]',
                'pop_culture' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'mythology' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'history_of_egypt' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'oceanology' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'astronomy' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            ])
        ) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $history = (int) $this->request->getPost('history');
        $popCulture = (int) $this->request->getPost('pop_culture');
        $mythology = (int) $this->request->getPost('mythology');
        $historyOfEgypt = (int) $this->request->getPost('history_of_egypt');
        $oceanology = (int) $this->request->getPost('oceanology');
        $astronomy = (int) $this->request->getPost('astronomy');
        $totalPP = $history + $popCulture + $mythology + $historyOfEgypt + $oceanology + $astronomy;

        $this->gradeModel->update((int) $this->request->getPost('id_grade'), [
            'class_name' => trim($this->request->getPost('class_name')),
            'history' => $history,
            'pop_culture' => $popCulture,
            'mythology' => $mythology,
            'history_of_egypt' => $historyOfEgypt,
            'oceanology' => $oceanology,
            'astronomy' => $astronomy,
            'total_pp' => $totalPP,
        ]);

        return redirect()->to(base_url('admin/classes'))->with('success', 'Nilai berhasil diupdate.');
    }

    /**
     * Delete a grade record.
     * POST /admin/grades/delete
     */
    public function deleteGrade()
    {
        $id = (int) $this->request->getPost('id');
        $this->gradeModel->delete($id);

        return redirect()->to(base_url('admin/classes'))->with('success', 'Nilai berhasil dihapus.');
    }

    /**
     * AJAX search for grades.
     * GET /admin/grades/search?q=...
     */
    public function searchGrades()
    {
        $query = trim($this->request->getGet('q') ?? '');
        $results = $this->gradeRepo->search($query);

        return $this->response->setJSON(['data' => $results]);
    }

    // ========== SCHEDULE CRUD ==========

    /**
     * Add schedule form.
     * GET /admin/schedule/add
     */
    public function addSchedule()
    {
        return view('App\Modules\Admin\Views\schedule_form', [
            'pageTitle' => 'Add Class Schedule',
            'currentPage' => 'classes',
            'extraCss' => ['edit_schedule.css'],
            'isEdit' => false,
            'scheduleData' => null,
        ]);
    }

    public function storeSchedule()
    {
        $insertData = [
            'class_name' => trim($this->request->getPost('class_name')),
            'hakaes_name' => trim($this->request->getPost('hakaes_name')),
            'schedule_day' => trim($this->request->getPost('schedule_day')),
            'schedule_time' => trim($this->request->getPost('schedule_time')),
            'class_description' => trim($this->request->getPost('class_description')),
        ];

        // File upload
        $classImage = $this->request->getFile('class_image');
        if ($classImage && $classImage->isValid() && !$classImage->hasMoved()) {
            $newName = $classImage->getRandomName();
            $classImage->move(FCPATH . 'uploads/class_images', $newName);
            $insertData['class_image_url'] = 'uploads/class_images/' . $newName;
        }

        $this->scheduleModel->insert($insertData);

        return redirect()->to(base_url('admin/classes'))->with('success', 'Jadwal berhasil ditambahkan.');
    }

    /**
     * Edit schedule form.
     * GET /admin/schedule/edit/{id}
     */
    public function editSchedule($id = 0)
    {
        $scheduleData = $this->scheduleModel->find((int) $id);

        if (!$scheduleData) {
            return redirect()->to(base_url('admin/classes'))->with('error', 'Schedule not found.');
        }

        return view('App\Modules\Admin\Views\schedule_form', [
            'pageTitle' => 'Edit Schedule',
            'currentPage' => 'classes',
            'extraCss' => ['edit_schedule.css'],
            'isEdit' => true,
            'scheduleData' => $scheduleData,
        ]);
    }

    public function updateSchedule()
    {
        $idSchedule = (int) $this->request->getPost('id_schedule');
        $oldImagePath = $this->request->getPost('old_image_path');

        $updateData = [
            'class_name' => trim($this->request->getPost('class_name')),
            'hakaes_name' => trim($this->request->getPost('hakaes_name')),
            'schedule_day' => trim($this->request->getPost('schedule_day')),
            'schedule_time' => trim($this->request->getPost('schedule_time')),
            'class_description' => trim($this->request->getPost('class_description')),
        ];

        // File upload
        $classImage = $this->request->getFile('class_image');
        if ($classImage && $classImage->isValid() && !$classImage->hasMoved()) {
            $newName = $classImage->getRandomName();
            $classImage->move(FCPATH . 'uploads/class_images', $newName);
            $updateData['class_image_url'] = 'uploads/class_images/' . $newName;

            // Delete old image
            if (!empty($oldImagePath) && file_exists(FCPATH . $oldImagePath)) {
                unlink(FCPATH . $oldImagePath);
            }
        } else {
            $updateData['class_image_url'] = $oldImagePath;
        }

        $this->scheduleModel->update($idSchedule, $updateData);

        return redirect()->to(base_url('admin/classes'))->with('success', 'Jadwal berhasil diupdate.');
    }

    /**
     * Delete a schedule.
     * POST /admin/schedule/delete
     */
    public function deleteSchedule()
    {
        $id = (int) $this->request->getPost('id');

        // Delete image file if exists
        $schedule = $this->scheduleModel->find($id);
        if (!empty($schedule['class_image_url']) && file_exists(FCPATH . $schedule['class_image_url'])) {
            unlink(FCPATH . $schedule['class_image_url']);
        }

        $this->scheduleModel->delete($id);

        return redirect()->to(base_url('admin/classes'))->with('success', 'Jadwal berhasil dihapus.');
    }
}
