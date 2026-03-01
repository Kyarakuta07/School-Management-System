<?php
/**
 * sanctuary/_daily_reward.php
 * Displays daily sanctuary rewards and active pet bonuses.
 */
?>
<div class="control-card full-width">
    <div class="card-header"><i class="fas fa-gift"></i>
        <h3>Daily Sanctuary Reward</h3>
    </div>
    <div class="card-body" style="text-align: center;">
        <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px;">
            <div>
                <div style="font-size: 2rem; color: var(--gold);">+50 G</div>
                <div style="color: #888; font-size: 0.85rem;">Base Gold</div>
            </div>
            <?php if (isset($upgrades['crystal_vault'])): ?>
                <div>
                    <div style="font-size: 2rem; color: #4a4;">+10 G</div>
                    <div style="color: #888; font-size: 0.85rem;">Crystal Vault</div>
                </div>
            <?php endif; ?>
            <?php if ($activePet): ?>
                <div>
                    <div style="font-size: 2rem; color: #88f;">+10 EXP</div>
                    <div style="color: #888; font-size: 0.85rem;">Pet EXP</div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($activePet): ?>
            <div
                style="background: rgba(0,0,0,0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px; display: inline-block;">
                <p style="color: #aaa; margin: 0 0 5px 0; font-size: 0.85rem;">Your Active Pet:</p>
                <p style="color: #fff; margin: 0; font-weight: 700;">
                    <?= esc($activePet['nickname'] ?? $activePet['species_name']) ?> (Lv.
                    <?= $activePet['level'] ?>
                    <?= $petStageName ?>)
                </p>
                <?php if ($petAtCap): ?>
                    <p style="color: #f80; margin: 5px 0 0 0; font-size: 0.8rem;"><i class="fas fa-exclamation-triangle"></i> At
                        level cap! Evolve to gain EXP.</p>
                <?php endif; ?>
                <?php $isHappy = ($activePet['hunger'] > 80 && $activePet['mood'] > 80); ?>
                <?php if ($isHappy): ?>
                    <p style="color: #4a4; margin: 5px 0 0 0; font-size: 0.8rem;">🌟 Happy Bonus: +20 Gold! (Pet is
                        well cared for)</p>
                <?php else: ?>
                    <p style="color: #888; margin: 5px 0 0 0; font-size: 0.8rem;"><i class="fas fa-info-circle"></i>
                        Keep Hunger & Mood > 80% for +20 Gold bonus!</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p style="color: #888; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> No active pet. Get a
                pet from the Gacha to earn EXP!</p>
        <?php endif; ?>

        <?php if ($canClaimDaily): ?>
            <form method="POST" style="display: inline-block;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="daily_claim">
                <button type="submit" class="donate-btn" style="max-width: 300px;"><i class="fas fa-calendar-check"></i>
                    Claim Daily Reward</button>
            </form>
        <?php else: ?>
            <div style="background: rgba(0,0,0,0.3); border-radius: 10px; padding: 15px; display: inline-block;">
                <p style="color: #888; margin: 0 0 5px 0; font-size: 0.85rem;">Next claim available in:</p>
                <div id="daily-timer" data-remaining="<?= max(0, $nextClaimTime - time()) ?>"
                    style="font-family: 'Cinzel', serif; font-size: 1.5rem; color: var(--gold);">
                    <?php
                    $remaining = max(0, $nextClaimTime - time());
                    $hours = floor($remaining / 3600);
                    $minutes = floor(($remaining % 3600) / 60);
                    echo sprintf('%02d:%02d', $hours, $minutes);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>