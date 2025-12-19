<!-- ARENA TAB (1v1 Premium) -->
<section id="arena" class="tab-panel">
    <!-- Premium Arena Header -->
    <div class="arena-premium-header">
        <h2 class="arena-title">
            <i class="fas fa-swords"></i>
            <span>Battle Arena</span>
        </h2>
        <p class="arena-subtitle">Challenge opponents and prove your strength</p>

        <!-- Battles Remaining Badge -->
        <div class="battles-remaining">
            <i class="fas fa-bolt"></i>
            <span id="arena-battles">3 / 3</span>
            <small>Battles Today</small>
        </div>
    </div>

    <!-- Arena Stats Bar -->
    <div class="arena-stats-bar">
        <div class="stat-card">
            <i class="fas fa-trophy"></i>
            <div>
                <span class="stat-value" id="total-wins">0</span>
                <span class="stat-label">Wins</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-chart-line"></i>
            <div>
                <span class="stat-value" id="win-rate">0%</span>
                <span class="stat-label">Win Rate</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-fire"></i>
            <div>
                <span class="stat-value" id="current-streak">0</span>
                <span class="stat-label">Streak</span>
            </div>
        </div>
    </div>

    <!-- Premium Opponents Grid -->
    <div class="opponents-premium-grid" id="arena-opponents">
        <!-- Opponent cards rendered by JS -->
    </div>
</section>