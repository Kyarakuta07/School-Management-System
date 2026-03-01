<!-- Daily Login Modal -->
<div class="modal-overlay" id="daily-login-modal">
    <div class="modal-content daily-login-card">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeDailyModal()">
            <i class="fas fa-times"></i>
        </button>

        <!-- Premium Header -->
        <div class="daily-login-header">
            <div class="header-badge">Daily Gifts</div>
            <h2><i class="fas fa-gem"></i> Monthly Rewards</h2>
            <div class="streak-progress-container">
                <div class="streak-progress-bar">
                    <div class="streak-progress-fill" id="daily-progress-fill" style="width: 0%;"></div>
                </div>
                <div class="streak-labels">
                    <span id="daily-current-day-label">Day 1</span>
                    <span>Bonus @ Day 7, 14, 30</span>
                </div>
            </div>
        </div>

        <!-- Calendar Container -->
        <div class="daily-calendar-wrapper">
            <div class="daily-calendar" id="daily-calendar"></div>
        </div>

        <!-- Current Reward Highlight -->
        <div class="daily-reward-focus">
            <div class="focus-title">Today's Reward</div>
            <div class="focus-content" id="daily-reward-content">
                <div class="focus-icon-wrapper">
                    <i class="fas fa-gift pulse"></i>
                </div>
                <div class="focus-details">
                    <div id="daily-reward-text" class="reward-amount">Loading...</div>
                    <div class="reward-subtitle">Available to claim now</div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="daily-modal-footer">
            <div class="streak-info-mini">
                <i class="fas fa-fire"></i>
                <span>Current Streak: <strong id="daily-total-logins">0</strong></span>
            </div>
            <button class="claim-reward-btn" id="claim-reward-btn" onclick="claimDailyReward()">
                Claim My Reward
            </button>
        </div>
    </div>
</div>