<?php
/**
 * quiz/_question_list.php
 * Displays list of questions for a quiz in management view.
 */
?>
<div class="questions-list">
    <h2><i class="fas fa-list"></i> Questions (
        <?= count($questions) ?>)
    </h2>
    <?php if (!empty($questions)): ?>
        <?php $qNum = 0;
        foreach ($questions as $q):
            $qNum++; ?>
            <div class="question-card" id="question-<?= $q['id_question'] ?>">
                <span class="question-number" style="background: <?= esc($subj['color'] ?? '#333') ?>;">Q
                    <?= $qNum ?>
                </span>
                <div class="question-text">
                    <?= esc($q['question_text']) ?>
                </div>
                <div class="options-grid">
                    <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                        <div class="option-item <?= $q['correct_answer'] === $opt ? 'correct' : '' ?>">
                            <strong>
                                <?= strtoupper($opt) ?>.
                            </strong>
                            <?= esc($q['option_' . $opt]) ?>
                            <?php if ($q['correct_answer'] === $opt): ?><i class="fas fa-check"></i>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="question-footer">
                    <span class="question-points"><i class="fas fa-star"></i>
                        <?= esc($q['points']) ?> points
                    </span>
                    <button class="btn-delete" onclick="deleteQuestion(<?= $q['id_question'] ?>)"><i class="fas fa-trash"></i>
                        Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-question-circle"></i>
            <h3>No Questions Yet</h3>
            <p>Add your first question using the form above.</p>
        </div>
    <?php endif; ?>
</div>