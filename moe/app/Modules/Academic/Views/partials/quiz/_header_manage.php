<?php
/**
 * quiz/_header_manage.php
 * Displays quiz title, status, and metadata for management view.
 */
?>
<div class="quiz-header">
    <h1>
        <i class="fas fa-clipboard-list"></i>
        <?= esc($quiz['title']) ?>
        <span class="status-badge <?= esc($quiz['status']) ?>">
            <?= ucfirst(esc($quiz['status'])) ?>
        </span>
    </h1>
    <div class="quiz-meta">
        <i class="fas fa-question"></i>
        <?= count($questions) ?> questions ·
        <i class="fas fa-clock"></i>
        <?= esc($quiz['time_limit']) ?> min ·
        <i class="fas fa-trophy"></i> Pass:
        <?= esc($quiz['passing_score']) ?>%
    </div>
</div>