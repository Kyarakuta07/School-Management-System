<!-- ARENA TAB (1v1 Premium) -->
<section id="arena" class="tab-panel">
    <!-- Premium Arena Header -->
    <div class="arena-premium-header">
        <div class="arena-header-bg-layer"
            style="background-image: url('<?= asset_v('assets/ui/arena_header_bg.png') ?>');"></div>
        <div class="arena-header-overlay"></div>

        <div class="arena-header-content">
            <h2 class="arena-title">
                <i class="fas fa-swords"></i>
                <span>Battle Arena</span>
            </h2>
            <p class="arena-subtitle">Prove your glory in the sands of time</p>

            <!-- Battles Remaining Visual counter -->
            <div class="battles-energy-orb-container">
                <div class="energy-orb">
                    <div class="energy-fill" id="energy-fill" style="height: 40%;"></div>
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="battles-count-text">
                    <span id="arena-battles">0 / 5</span>
                    <small>Battles Remaining</small>
                </div>
            </div>

            <div class="arena-header-footer">
                <div class="quota-reset-info">
                    <i class="fas fa-clock"></i> Next reset in <span id="arena-reset-time">00:00</span>
                </div>
                <button id="btn-reset-quota" class="btn-reset-quota" style="display: none;" onclick="useArenaTicket()">
                    <i class="fas fa-ticket-alt"></i> Reset Quota
                </button>
            </div>
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