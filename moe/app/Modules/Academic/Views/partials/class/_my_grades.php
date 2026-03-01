<div class="class-card grades-card">
    <h3 class="card-title"><i class="fa-solid fa-scroll"></i> MY GRADES</h3>

    <?php if ($userGrades): ?>
        <div class="grades-summary">
            <div class="total-pp">
                <span class="pp-value">
                    <?= number_format($userGrades['total_pp']) ?>
                </span>
                <span class="pp-label">Prestige Points</span>
            </div>
            <?php if ($userRank > 0): ?>
                <div class="rank-badge">
                    <i class="fa-solid fa-medal"></i>
                    <span>Rank #
                        <?= $userRank ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div class="grades-grid">
            <?php foreach ($subjects as $key => $subject): ?>
                <div class="grade-item" style="--subject-color: <?= $subject['color'] ?>">
                    <i class="fa-solid <?= $subject['icon'] ?>"></i>
                    <div class="grade-info">
                        <span class="grade-name">
                            <?= $subject['name'] ?>
                        </span>
                        <span class="grade-value">
                            <?= $userGrades[$key] ?? 0 ?> PP
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-grades">
            <i class="fa-solid fa-question-circle"></i>
            <p>Belum ada data nilai.</p>
            <small>Nilai akan muncul setelah mengikuti kelas.</small>
        </div>
    <?php endif; ?>
</div>