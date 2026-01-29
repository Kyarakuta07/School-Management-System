<!-- Battle History Tab -->
<div class="tab-content" id="tab-history" style="display: none;">
    <div class="history-container">

        <!-- Header -->
        <div class="history-header">
            <div class="history-icon">📜</div>
            <h2>BATTLE HISTORY</h2>
        </div>

        <!-- Stats Summary -->
        <div class="history-stats" id="history-stats">
            <div class="stat-box attack">
                <span class="stat-icon">⚔️</span>
                <span class="stat-value" id="total-wins">0</span>
                <span class="stat-label">Attack Wins</span>
            </div>
            <div class="stat-box attack">
                <span class="stat-value" id="total-losses">0</span>
                <span class="stat-label">Attack Losses</span>
            </div>
            <div class="stat-box defense">
                <span class="stat-icon">🛡️</span>
                <span class="stat-value" id="defense-wins">0</span>
                <span class="stat-label">Defense Wins</span>
            </div>
            <div class="stat-box defense">
                <span class="stat-value" id="defense-losses">0</span>
                <span class="stat-label">Defense Losses</span>
            </div>
            <div class="stat-box streak">
                <span class="stat-icon">🔥</span>
                <span class="stat-value" id="win-streak">0</span>
                <span class="stat-label">Streak</span>
            </div>
        </div>

        <!-- History List -->
        <div class="history-list" id="history-list">
            <div class="loading-spinner">Loading...</div>
        </div>

    </div>
</div>

<style>
    .history-container {
        padding: 1rem;
    }

    .history-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .history-header h2 {
        color: var(--accent-color, #f39c12);
        margin: 0.5rem 0 0 0;
        font-size: 1.2rem;
    }

    .history-icon {
        font-size: 2.5rem;
    }

    .history-stats {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-box {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        text-align: center;
        min-width: 80px;
    }

    .stat-value {
        display: block;
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--accent-color, #f39c12);
    }

    .stat-label {
        font-size: 0.75rem;
        color: #888;
        text-transform: uppercase;
    }

    .history-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .history-item {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .history-pets {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
    }

    .history-pet {
        text-align: center;
    }

    .history-pet img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .history-pet-name {
        font-size: 0.7rem;
        color: #ccc;
        max-width: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .history-vs {
        font-weight: bold;
        color: #666;
        padding: 0 0.5rem;
    }

    .history-result {
        font-weight: bold;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
    }

    .history-result.win {
        background: rgba(46, 204, 113, 0.2);
        color: #2ecc71;
    }

    .history-result.lose {
        background: rgba(231, 76, 60, 0.2);
        color: #e74c3c;
    }

    .history-date {
        font-size: 0.7rem;
        color: #666;
        margin-top: 0.25rem;
    }

    .empty-history {
        text-align: center;
        color: #666;
        padding: 2rem;
    }
</style>