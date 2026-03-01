<?php

namespace App\Modules\Academic\Controllers;

use App\Kernel\BaseController;

use App\Modules\Academic\Models\GradeModel;
use App\Modules\Academic\Models\ScheduleModel;
use App\Modules\Academic\Models\QuizModel;
use App\Modules\Academic\Repositories\GradeRepository;
use App\Modules\Sanctuary\Models\SanctuaryModel;
use App\Modules\Academic\Config\SubjectConfig;
use App\Modules\User\Models\UserModel;

/**
 * Class Page Controller (serves class.php HTML page)
 * Refactored: all raw queries moved to Models; N+1 quiz loop fixed.
 */
class ClassPageController extends BaseController
{
    private GradeModel $gradeModel;
    private GradeRepository $gradeRepo;
    private ScheduleModel $scheduleModel;
    private QuizModel $quizModel;
    private SanctuaryModel $sanctuaryModel;
    private UserModel $userModel;

    /**
     * Subject metadata — sourced from centralized SubjectConfig.
     */
    private array $subjects;

    public function __construct()
    {
        $this->subjects = SubjectConfig::$subjects;
        $this->gradeModel = new GradeModel();
        $this->gradeRepo = new GradeRepository();
        $this->scheduleModel = new ScheduleModel();
        $this->quizModel = new QuizModel();
        $this->sanctuaryModel = new SanctuaryModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $session = session();
        $userId = (int) $session->get('id_nethera');
        $role = $session->get('role') ?? '';

        // === 1. Schedules (ordered by day of week) ===
        $schedules = $this->scheduleModel->getOrdered();

        // === 2. User grades ===
        $userGrades = $this->gradeModel->getUserGrades($userId);

        // === 3. User rank ===
        $userRank = 0;
        if ($userGrades) {
            $userRank = $this->gradeModel->getUserRank((int) $userGrades['total_pp']);
        }

        // === 4. Top 5 scholars ===
        $topScholars = $this->gradeRepo->getTopScholars(5);

        // === 5. Sanctuary ranking ===
        $sanctuaryRanking = $this->gradeRepo->getSanctuaryRanking();

        // === 6. All sanctuaries (filter dropdown) ===
        $allSanctuaries = $this->sanctuaryModel->orderBy('nama_sanctuary')->findAll();

        // === 7. User sanctuary name (via UserModel — no inline query) ===
        $userInfo = $this->userModel->getUserDashboardInfo($userId);
        $userSanctuary = $userInfo['nama_sanctuary'] ?? 'Unknown';

        // === 8. Student quiz progress (N+1 fixed: 2 queries instead of 10) ===
        $studentProgress = [];
        if ($role === ROLE_NETHERA) {
            $studentProgress = $this->quizModel->getStudentProgress($userId, array_keys($this->subjects));
        }

        // === 9. Grade management data (Hakaes / Vasiki) ===
        $canManageGrades = can('manage_grades', $role);
        $isVasiki = ($role === ROLE_VASIKI);
        $allStudents = [];
        $allGrades = [];
        $hakaesSub = null;
        $hakaesSubName = null;

        if ($role === ROLE_HAKAES) {
            $sched = $this->scheduleModel->getHakaesSubject($userId);
            if ($sched) {
                $hakaesSubName = $sched['class_name'];
                $hakaesSub = strtolower(str_replace(' ', '_', $sched['class_name']));
            }
        }

        if ($canManageGrades) {
            $allGrades = $this->gradeRepo->getAllStudentsWithGrades();
            $allStudents = $allGrades; // Same data, used for student picker
        }

        return view('App\Modules\Academic\Views\class', [
            'pageTitle' => 'My Class - MOE Virtual Academy',
            'activePage' => 'class',
            'userId' => $userId,
            'userName' => esc($session->get('nama_lengkap') ?? ''),
            'userRole' => $role,
            'subjects' => $this->subjects,
            'schedules' => $schedules,
            'userGrades' => $userGrades,
            'userRank' => $userRank,
            'topScholars' => $topScholars,
            'sanctuaryRanking' => $sanctuaryRanking,
            'allSanctuaries' => $allSanctuaries,
            'userSanctuary' => $userSanctuary,
            'studentProgress' => $studentProgress,
            'canManageGrades' => $canManageGrades,
            'isVasiki' => $isVasiki,
            'allStudents' => $allStudents,
            'allGrades' => $allGrades,
            'hakaesSub' => $hakaesSub,
            'hakaesSubName' => $hakaesSubName,
        ]);
    }
}
