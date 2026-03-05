<?= $this->extend('layouts/user') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= asset_v('css/academic/subject_detail.css') ?>">
<style>
    .bg-fixed {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -2;
        background: url('<?= base_url('images/bg/scholar_bg.webp') ?>') no-repeat center center/cover;
    }

    .bg-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        background: rgba(0, 0, 0, 0.7);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="bg-fixed"></div>
<div class="bg-overlay"></div>

<div class="main-dashboard-wrapper" data-csrf-name="<?= csrf_token() ?>" data-csrf-token="<?= csrf_hash() ?>"
    data-subject="<?= esc($subject) ?>" data-api-base="<?= base_url('api/') ?>" data-base-url="<?= base_url() ?>">
    <!-- TOP NAVIGATION -->
    <?= $this->include('App\Modules\User\Views\partials\navbar') ?>

    <main class="class-main-content" style="max-width: 900px; margin: 0 auto; padding: 20px;">

        <!-- Subject Header -->
        <?= $this->include('App\Modules\Academic\Views\partials\subject\_header') ?>

        <!-- Hakaes: Add Material Button -->
        <?php if ($canManage): ?>
            <button class="add-material-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Material
            </button>
        <?php endif; ?>

        <!-- Materials List -->
        <?= $this->include('App\Modules\Academic\Views\partials\subject\_materials_list') ?>

        <!-- Quiz Section -->
        <?= $this->include('App\Modules\Academic\Views\partials\subject\_quiz_section') ?>

    </main>

    <!-- Modals -->
    <?= $this->include('App\Modules\Academic\Views\partials\subject\_modals') ?>

    <!-- Bottom Nav -->
    <?= $this->include('App\Modules\User\Views\partials\bottom_nav') ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('page_scripts') ?>
<script>
    // Inject server-side config directly â€” always correct regardless of caching
    (function () {
        var origin = window.location.origin;
        var phpBase = '<?= base_url() ?>';
        // Extract path from PHP base_url, but use current origin (fixes port mismatch in dev)
        var path = '/';
        try { path = new URL(phpBase).pathname; } catch (e) { }
        window.MOE_CONFIG = {
            csrfName: '<?= csrf_token() ?>',
            csrfToken: '<?= csrf_hash() ?>',
            apiBase: origin + path + 'api/',
            baseUrl: origin + path,
            subject: '<?= esc($subject) ?>'
        };
    })();
</script>
<script src="<?= asset_v('js/academic/subject_materials.js') ?>"></script>
<script src="<?= asset_v('js/academic/subject_content_preview.js') ?>"></script>
<?= $this->endSection() ?>