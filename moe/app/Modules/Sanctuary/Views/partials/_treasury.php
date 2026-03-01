<?php
/**
 * sanctuary/_treasury.php
 * Displays sanctuary and user gold, plus donation form.
 */
?>
<div class="control-card">
    <div class="card-header"><i class="fas fa-coins"></i>
        <h3>Treasury</h3>
    </div>
    <div class="card-body">
        <div class="treasury-display">
            <div class="treasury-amount">
                <?= number_format($sanctuaryGold) ?> G
            </div>
            <div class="treasury-label">Guild Gold Reserve</div>
        </div>
        <p style="color: #888; font-size: 0.85rem; text-align: center; margin-bottom: 15px;">
            Your Gold: <strong style="color: var(--gold);">
                <?= number_format($userGold) ?> G
            </strong>
        </p>
        <form method="POST" action="<?= site_url('my-sanctuary') ?>" style="display: flex; gap: 10px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="donate">
            <input type="number" name="amount" min="10" max="<?= $userGold ?>" value="100"
                style="flex: 1; padding: 12px; border-radius: 8px; border: 1px solid rgba(218,165,32,0.3); background: rgba(0,0,0,0.3); color: #fff; font-size: 1rem;">
            <button type="submit" class="donate-btn" style="flex: 0 0 auto; width: auto; padding: 12px 20px;"
                <?= $userGold < 10 ? 'disabled' : '' ?>>
                <i class="fas fa-hand-holding-usd"></i> Donate
            </button>
        </form>
    </div>
</div>