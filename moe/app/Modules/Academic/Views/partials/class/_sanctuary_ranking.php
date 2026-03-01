<div class="class-card ranking-card">
    <h3 class="card-title"><i class="fa-solid fa-trophy"></i> SANCTUARY RANKING</h3>

    <div class="ranking-list">
        <?php
        $rank = 0;
        foreach ($sanctuaryRanking as $sanctuary):
            $rank++;
            $isMine = ($sanctuary['nama_sanctuary'] === $userSanctuary);
            ?>
            <div class="ranking-item <?= $isMine ? 'my-sanctuary' : '' ?>">
                <span class="rank-position rank-<?= $rank ?>">#
                    <?= $rank ?>
                </span>
                <div class="rank-info">
                    <span class="rank-name">
                        <?= esc($sanctuary['nama_sanctuary']) ?>
                    </span>
                    <span class="rank-members">
                        <?= $sanctuary['member_count'] ?> members
                    </span>
                </div>
                <span class="rank-points">
                    <?= number_format($sanctuary['total_points']) ?> PP
                </span>
            </div>
        <?php endforeach; ?>

        <?php if (empty($sanctuaryRanking)): ?>
            <p class="no-data">Belum ada data ranking.</p>
        <?php endif; ?>
    </div>
</div>