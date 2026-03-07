<?= $this->extend('layouts/user') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= asset_v('css/user/beranda_style.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="main-dashboard-wrapper">
    <!-- TOP NAVIGATION -->
    <?= $this->include('App\Modules\User\Views\partials\navbar') ?>

    <!-- HEADER -->
    <header class="hero-header">
        <div class="hero-content">
            <div class="greeting-section">
                <span class="greeting-emoji">
                    <?= $greetingEmoji ?>
                </span>
                <div class="greeting-text">
                    <p class="greeting-line">
                        <?= $greeting ?>,
                    </p>
                    <h1 class="user-name-hero">
                        <?= esc($userName) ?>
                    </h1>
                </div>
            </div>
            <?php if ($canAccessAdmin): ?>
                <a href="<?= base_url('admin') ?>" class="admin-btn-hero" title="Admin Dashboard">
                    <i class="fa-solid fa-crown"></i>
                </a>
            <?php endif; ?>
            <form action="<?= base_url('logout') ?>" method="POST" style="display: inline; margin: 0;">
                <?= csrf_field() ?>
                <button type="submit" class="logout-btn-hero" title="Logout"
                    style="border: none; cursor: pointer; background: none; font: inherit; color: inherit; padding: 0;">
                    <i class="fa-solid fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
        <div class="sanctuary-badge">
            <i class="fas fa-shield-alt"></i>
            <span>
                <?= esc($sanctuaryName) ?> Sanctuary
            </span>
        </div>
    </header>

    <!-- MAIN DASHBOARD GRID -->
    <main class="dashboard-grid">

        <!-- LEFT: PROFILE + FUNFACT -->
        <section class="dashboard-card profile-card animate-entrance delay-1">
            <div class="card-header">
                <h3><i class="fas fa-user-circle"></i> My Profile</h3>
            </div>
            <div class="card-body profile-body">
                <div class="avatar-container" onclick="document.getElementById('photoUploadInput').click()">
                    <?php if ($profilePhoto): ?>
                        <img src="<?= base_url('assets/uploads/profiles/' . esc($profilePhoto)) ?>" alt=""
                            class="avatar-img" id="avatarPreview">
                    <?php else: ?>
                        <div class="avatar-placeholder" id="avatarPreview">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <div class="avatar-overlay">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <input type="file" id="photoUploadInput" accept="image/jpeg,image/png,image/gif,image/webp"
                    style="display: none;">

                <h2 class="profile-name">
                    <?= esc($userName) ?>
                </h2>
                <p class="profile-role"><i class="fas fa-user-shield"></i>
                    <?= esc($userRole) ?>
                </p>

                <div class="profile-divider"></div>

                <!-- Fun Fact -->
                <div class="funfact-section">
                    <div class="funfact-header">
                        <i class="fas fa-lightbulb"></i>
                        <span>My Fun Fact</span>
                        <button class="edit-btn-mini" onclick="openFunfactModal()">
                            <i class="fas fa-pen"></i>
                        </button>
                    </div>
                    <p class="funfact-text" id="funfactDisplay">
                        <?= esc($funFact) ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- RIGHT: STUDY BUDDY + SANCTUARY -->
        <section class="right-column">
            <?php if ($activePet): ?>
                <a href="<?= base_url('pet') ?>" class="dashboard-card study-buddy-card animate-entrance delay-2">
                    <div class="card-header">
                        <h3><i class="fas fa-dragon"></i> Study Buddy</h3>
                        <span class="card-link">View All →</span>
                    </div>
                    <div class="card-body buddy-body">
                        <div class="buddy-pet">
                            <img src="<?= $petImage ?>" alt="<?= esc($petDisplayName) ?>" class="buddy-img"
                                onerror="this.src='<?= asset_v('assets/placeholder.png') ?>'">
                            <span class="element-badge <?= strtolower($activePet['element']) ?>">
                                <?= $activePet['element'] ?>
                            </span>
                        </div>
                        <div class="buddy-info">
                            <h4 class="buddy-name">
                                <?= esc($petDisplayName) ?>
                            </h4>
                            <span class="buddy-level">Level
                                <?= $activePet['level'] ?>
                            </span>
                            <p class="buddy-buff"><i class="fas fa-sparkles"></i>
                                <?= $petBuffText ?>
                            </p>
                        </div>
                    </div>
                </a>
            <?php else: ?>
                <a href="<?= base_url('pet') ?>" class="dashboard-card study-buddy-card empty animate-entrance delay-2">
                    <div class="card-header">
                        <h3><i class="fas fa-dragon"></i> Study Buddy</h3>
                    </div>
                    <div class="card-body buddy-empty">
                        <i class="fas fa-egg"></i>
                        <p>No active companion</p>
                        <span class="get-pet-btn">Get Your Pet →</span>
                    </div>
                </a>
            <?php endif; ?>

            <!-- SANCTUARY CARD -->
            <a href="<?= base_url('my-sanctuary') ?>" class="dashboard-card sanctuary-card animate-entrance delay-3">
                <div class="card-header">
                    <h3><i class="fas fa-ankh"></i> My Sanctuary</h3>
                    <span class="card-link">Enter Control Room →</span>
                </div>
                <div class="card-body sanctuary-body-premium">
                    <div class="sanctuary-emblem-wrapper">
                        <img src="<?= base_url('assets/faction emblem/faction_' . esc($factionSlug) . '.png') ?>"
                            alt="<?= esc($sanctuaryName) ?>" class="sanctuary-emblem-img"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="sanctuary-emblem-fallback" style="display: none;">
                            <i class="fas fa-ankh"></i>
                        </div>
                    </div>
                    <h4 class="sanctuary-name-premium">
                        <?= esc($sanctuaryName) ?> Sanctuary
                    </h4>
                    <p class="sanctuary-tagline">Blessed by the Ancient Gods</p>
                    <div class="sanctuary-cta-btn">
                        <i class="fas fa-door-open"></i> Manage Sanctuary
                    </div>
                </div>
            </a>
        </section>
    </main>
</div>

<!-- Fun Fact Modal -->
<div class="modal-overlay" id="funfactModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Fun Fact</h3>
            <button class="modal-close" onclick="closeFunfactModal()">&times;</button>
        </div>
        <div class="modal-body">
            <textarea id="funfactInput" placeholder="Tulis fun fact tentang dirimu..."
                maxlength="500"><?= esc($funFact !== 'Belum ada funfact.' ? $funFact : '') ?></textarea>
            <div class="char-count"><span id="charCount">0</span>/500</div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeFunfactModal()">Batal</button>
            <button class="btn-save" onclick="saveFunfact()">Simpan</button>
        </div>
    </div>
</div>

<!-- Bottom Nav -->
<?= $this->include('App\Modules\User\Views\partials\bottom_nav') ?>
<?= $this->endSection() ?>

<?= $this->section('page_scripts') ?>
<script>
    const CSRF_NAME = '<?= csrf_token() ?>';
    const CSRF_TOKEN = '<?= csrf_hash() ?>';
</script>
<script src="<?= asset_v('js/user/beranda.js') ?>"></script>
<?= $this->endSection() ?>