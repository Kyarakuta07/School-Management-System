<!-- QUIZ SECTION -->
<h2 style="color: #d4af37; margin: 32px 0 16px; font-size: 1.3rem;">
    <i class="fas fa-question-circle"></i> Quizzes & Exams
</h2>

<?php if ($canManage): ?>
    <button class="add-material-btn" onclick="openQuizModal()"
        style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
        <i class="fas fa-plus"></i> Create Quiz
    </button>
<?php endif; ?>

<?php if (!empty($quizzes)): ?>
    <div class="materials-grid">
        <?php foreach ($quizzes as $quiz): ?>
            <div class="material-card">
                <div class="material-header">
                    <div class="material-type-icon" style="background: rgba(155,89,182,0.2); color: #9b59b6;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <span class="material-title">
                        <?= esc($quiz['title']) ?>
                    </span>
                    <?php if ($canManage): ?>
                        <span
                            style="padding: 4px 10px; border-radius: 12px; font-size: 0.75rem;
                            background: <?= $quiz['status'] === 'active' ? 'rgba(76,175,80,0.2)' : ($quiz['status'] === 'draft' ? 'rgba(255,193,7,0.2)' : 'rgba(231,76,60,0.2)') ?>;
                            color: <?= $quiz['status'] === 'active' ? '#4caf50' : ($quiz['status'] === 'draft' ? '#ffc107' : '#e74c3c') ?>;">
                            <?= ucfirst(esc($quiz['status'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="material-meta">
                    <i class="fas fa-question"></i>
                    <?= esc($quiz['question_count']) ?> questions ·
                    <i class="fas fa-clock"></i>
                    <?= esc($quiz['time_limit']) ?> min ·
                    <i class="fas fa-trophy"></i> Pass:
                    <?= esc($quiz['passing_score']) ?>%
                </div>
                <p style="color: rgba(255,255,255,0.7); margin: 12px 0; font-size: 0.9rem;">
                    <?= esc($quiz['description'] ?: 'No description') ?>
                </p>

                <?php if ($canManage): ?>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="<?= base_url('quiz/manage?id=' . $quiz['id_quiz']) ?>" class="pdf-btn view"
                            style="background: rgba(155,89,182,0.2); color: #9b59b6;">
                            <i class="fas fa-cog"></i> Manage
                        </a>
                        <button
                            onclick="updateQuizStatus(<?= $quiz['id_quiz'] ?>, '<?= $quiz['status'] === 'active' ? 'closed' : 'active' ?>')"
                            class="pdf-btn download" style="border: none; cursor: pointer;">
                            <i class="fas fa-<?= $quiz['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                            <?= $quiz['status'] === 'active' ? 'Close' : 'Activate' ?>
                        </button>
                    </div>
                <?php else: ?>
                    <?php $canTake = ($quiz['attempts_used'] ?? 0) < $quiz['max_attempts'] && $quiz['question_count'] > 0; ?>
                    <?php if ($canTake): ?>
                        <a href="<?= base_url('quiz/attempt?id=' . $quiz['id_quiz']) ?>" class="pdf-btn view"
                            style="background: rgba(76,175,80,0.2); color: #4caf50;">
                            <i class="fas fa-play"></i> Take Quiz
                        </a>
                        <span style="font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-left: 12px;">
                            Attempts:
                            <?= $quiz['attempts_used'] ?? 0 ?>/
                            <?= esc($quiz['max_attempts']) ?>
                        </span>
                    <?php else: ?>
                        <span style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">
                            <i class="fas fa-check-circle"></i> Completed
                            (
                            <?= $quiz['attempts_used'] ?? 0 ?>/
                            <?= esc($quiz['max_attempts']) ?> attempts used)
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-materials">
        <i class="fas fa-clipboard-list"></i>
        <h3>No Quizzes Yet</h3>
        <p>Quizzes will appear here once the teacher creates them.</p>
    </div>
<?php endif; ?>