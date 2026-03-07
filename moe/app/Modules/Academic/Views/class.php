<?= $this->extend('layouts/user') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= asset_v('css/academic/class_style.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="main-dashboard-wrapper">

    <!-- TOP NAVIGATION -->
    <?= $this->include('App\Modules\User\Views\partials\navbar') ?>

    <!-- HEADER -->
    <header class="hero-header">
        <div class="hero-content">
            <div class="greeting-section">
                <!-- Emoji/Icon for Academy -->
                <div class="greeting-emoji">📜</div>
                <div class="greeting-text">
                    <p class="greeting-line">Kurikulum Akademi,</p>
                    <h1 class="user-name-hero"><?= esc($userName) ?></h1>
                </div>
            </div>

            <div class="hero-badge-container">
                <div class="sanctuary-badge <?= strtolower(str_replace(' ', '-', $userSanctuary)) ?>">
                    <span class="badge-label">SANCTUARY</span>
                    <span class="badge-value"><?= $userSanctuary ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="class-main-content">

        <!-- LEFT COLUMN: My Grades & Ranking -->
        <section class="class-sidebar">

            <!-- MY GRADES CARD -->
            <?= $this->include('App\Modules\Academic\Views\partials\class\_my_grades') ?>

            <!-- TOP SCHOLARS LEADERBOARD -->
            <?= $this->include('App\Modules\Academic\Views\partials\class\_top_scholars') ?>

            <!-- STUDENT QUIZ PROGRESS -->
            <?= $this->include('App\Modules\Academic\Views\partials\class\_quiz_progress') ?>

            <!-- SANCTUARY RANKING -->
            <?= $this->include('App\Modules\Academic\Views\partials\class\_sanctuary_ranking') ?>

            <!-- GRADE MANAGEMENT (HAKAES/VASIKI) -->
            <?= $this->include('App\Modules\Academic\Views\partials\class\_grade_management') ?>

        </section>

        <!-- RIGHT COLUMN: Schedule & Subjects -->
        <section class="class-content">

            <!-- CLASS SCHEDULE -->
            <?= $this->include('App\Modules\Academic\Views\partials\class\_schedule') ?>

            <!-- SUBJECT CARDS -->
            <?= $this->include('App\Modules\Academic\Views\partials\class\_subjects') ?>

        </section>

    </main>
</div>

<!-- Bottom Nav -->
<?= $this->include('App\Modules\User\Views\partials\bottom_nav') ?>
<?= $this->endSection() ?>

<?= $this->section('page_scripts') ?>
<script src="<?= asset_v('js/academic/class_page.js') ?>"></script>
<?= $this->endSection() ?>