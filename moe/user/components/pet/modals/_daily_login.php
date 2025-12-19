<!-- Daily Login Modal -->
<div class="daily-login-modal" id="daily-login-modal">
    <div class="daily-login-content">
        <button class="daily-close-btn" onclick="closeDailyModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="daily-login-header">
            <h2>🎁 Daily Login Reward!</h2>
            <p>Day <span id="daily-current-day">1</span> of 30</p>
        </div>
        <div class="daily-calendar" id="daily-calendar"></div>
        <div class="daily-reward-display">
            <div class="reward-label">Today's Reward</div>
            <div class="reward-content" id="daily-reward-content">
                <i class="fas fa-coins reward-gold"></i>
                <span id="daily-reward-text">50 Gold</span>
            </div>
        </div>
        <div class="streak-counter">
            <i class="fas fa-fire"></i>
            <span>Total Logins: <strong id="daily-total-logins">0</strong></span>
        </div>
        <button class="claim-reward-btn" id="claim-reward-btn" onclick="claimDailyReward()">
            <i class="fas fa-gift"></i> Claim Reward!
        </button>
    </div>
</div>