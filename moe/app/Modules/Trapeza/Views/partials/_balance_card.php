<!-- Balance Card -->
<section class="balance-card">
    <div class="balance-header">
        <span class="balance-label">Your Balance</span>
        <button class="refresh-btn" onclick="refreshBalance()">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
    <div class="balance-amount">
        <i class="fas fa-coins gold-icon"></i>
        <span id="gold-balance">0</span>
    </div>
    <div class="balance-footer">
        <span class="username-small">@
            <?= esc($userName) ?>
        </span>
    </div>
</section>