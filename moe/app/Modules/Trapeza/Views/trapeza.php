<?= $this->extend('layouts/user') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= asset_v('css/trapeza/trapeza.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="main-dashboard-wrapper">
    <!-- TOP NAVIGATION -->
    <?= $this->include('App\Modules\User\Views\partials\navbar') ?>

    <!-- HEADER -->
    <header class="hero-header">
        <div class="hero-content">
            <div class="greeting-section">
                <div class="greeting-emoji">🏛️</div>
                <div class="greeting-text">
                    <p class="greeting-line">Trapeza Mobile Banking,</p>
                    <h1 class="user-name-hero"><?= $userName ?></h1>
                </div>
            </div>

            <div class="hero-badges">
                <div class="sanctuary-badge">
                    <i class="fas fa-coins"></i>
                    <span>Secure Vault mode</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="trapeza-container">

        <!-- Balance Card -->
        <?= $this->include('App\Modules\Trapeza\Views\partials\_balance_card') ?>

        <!-- Quick Actions -->
        <?= $this->include('App\Modules\Trapeza\Views\partials\_quick_actions') ?>

        <!-- Recent Transactions -->
        <?= $this->include('App\Modules\Trapeza\Views\partials\_transactions_list') ?>

    </main>

    <!-- Modals (Transfer, Confirm, History, Toast) -->
    <?= $this->include('App\Modules\Trapeza\Views\partials\_modals') ?>

    <!-- Bottom Nav -->
    <?= $this->include('App\Modules\User\Views\partials\bottom_nav') ?>

</div>
<?= $this->endSection() ?>

<?= $this->section('page_scripts') ?>
<script src="<?= asset_v('js/trapeza/trapeza_banking.js') ?>"></script>
<?= $this->endSection() ?>