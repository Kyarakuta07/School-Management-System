<div class="class-card top-scholars-card">
    <div class="card-header-row">
        <h3 class="card-title"><i class="fa-solid fa-trophy"></i> TOP SCHOLARS</h3>
        <select id="sanctuary-filter" class="sanctuary-filter" onchange="filterScholars(this.value)">
            <option value="">All Sanctuaries</option>
            <?php foreach ($allSanctuaries as $sanctuary): ?>
                <option value="<?= $sanctuary['id_sanctuary'] ?>">
                    <?= esc($sanctuary['nama_sanctuary']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="scholars-list" id="scholars-list">
        <?php if (!empty($topScholars)): ?>
            <?php $rank = 1;
            foreach ($topScholars as $scholar): ?>
                <div class="scholar-row rank-<?= $rank ?>" data-sanctuary="<?= $scholar['id_sanctuary'] ?>">
                    <div class="scholar-rank">
                        <?php if ($rank === 1): ?>
                            <span class="rank-icon gold">👑</span>
                        <?php elseif ($rank === 2): ?>
                            <span class="rank-icon silver">🥈</span>
                        <?php elseif ($rank === 3): ?>
                            <span class="rank-icon bronze">🥉</span>
                        <?php else: ?>
                            <span class="rank-number">#
                                <?= $rank ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="scholar-info">
                        <span class="scholar-name">
                            <?= esc($scholar['nama_lengkap']) ?>
                        </span>
                        <span class="scholar-sanctuary">
                            <i class="fa-solid fa-shield-halved"></i>
                            <?= esc($scholar['nama_sanctuary'] ?? 'Unknown') ?>
                        </span>
                    </div>
                    <div class="scholar-pp">
                        <span class="pp-value">
                            <?= number_format($scholar['total_pp']) ?>
                        </span>
                        <span class="pp-label">PP</span>
                    </div>
                </div>
                <?php $rank++; endforeach; ?>
        <?php else: ?>
            <div class="no-scholars">
                <i class="fa-solid fa-users-slash"></i>
                <p>No rankings available yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>