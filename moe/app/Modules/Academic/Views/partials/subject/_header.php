<a href="<?= base_url('class') ?>" class="back-btn">
    <i class="fas fa-arrow-left"></i> Back to Classes
</a>

<div class="subject-header">
    <div class="subject-icon" style="background: <?= esc($currentSubject['color']) ?>;">
        <i class="fas <?= esc($currentSubject['icon']) ?>"></i>
    </div>
    <h1>
        <?= esc($currentSubject['name']) ?>
    </h1>
    <p>
        <?= esc($currentSubject['desc']) ?>
    </p>
</div>