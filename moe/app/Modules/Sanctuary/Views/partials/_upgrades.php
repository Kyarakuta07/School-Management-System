<?php
/**
 * sanctuary/_upgrades.php
 * Displays list of available sanctuary upgrades.
 */
?>
<div class="control-card">
    <div class="card-header">
        <i class="fas fa-arrow-up"></i>
        <h3>Sanctuary Upgrades</h3>
        <?php if (!$isLeader): ?><span style="margin-left: auto; font-size: 0.75rem; color: #888;">(Leaders
                Only)</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php foreach ($upgradeConfig as $type => $config): ?>
            <?php $isPurchased = isset($upgrades[$type]);
            $canAfford = $sanctuaryGold >= $config['cost']; ?>
            <div class="upgrade-item" style="<?= !$isPurchased && !$canAfford ? 'opacity: 0.5;' : '' ?>">
                <div class="upgrade-icon"><i class="fas <?= $config['icon'] ?>"></i></div>
                <div class="upgrade-info">
                    <div class="upgrade-name">
                        <?= esc($config['name']) ?>
                    </div>
                    <div class="upgrade-desc">
                        <?= esc($config['desc']) ?>
                    </div>
                </div>
                <?php if ($isPurchased): ?>
                    <span style="color: #4a4; font-weight: 700;"><i class="fas fa-check"></i> ACTIVE</span>
                <?php elseif ($isLeader): ?>
                    <form method="POST" action="<?= site_url('my-sanctuary') ?>" style="margin: 0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="upgrade">
                        <input type="hidden" name="upgrade_type" value="<?= $type ?>">
                        <button type="submit" class="donate-btn"
                            style="padding: 8px 15px; font-size: 0.85rem; background: <?= $canAfford ? 'linear-gradient(135deg, var(--gold), #B8860B)' : '#444' ?>;"
                            <?= !$canAfford ? 'disabled' : '' ?>>
                            <?= number_format($config['cost']) ?> G
                        </button>
                    </form>
                <?php else: ?>
                    <span style="color: #666; font-size: 0.85rem;"><i class="fas fa-lock"></i> Leaders Only</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>