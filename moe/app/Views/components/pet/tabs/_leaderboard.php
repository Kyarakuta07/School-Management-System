<!-- Pet Leaderboard Tab - Premium Design v2 (No Emoji) -->
<div class="tab-content" id="leaderboard" style="display: none;">
    <div class="leaderboard-container">

        <!-- Header with animated trophy -->
        <div class="leaderboard-header-premium">
            <div class="leaderboard-header-bg"
                style="background-image: url('<?= asset_v('assets/leaderboard/podium_bg.png') ?>');"></div>
            <div class="leaderboard-header-overlay"></div>

            <div class="lb-header-content">
                <div class="lb-trophy-icon"></div>
                <div class="lb-title">
                    <h2>HALL OF CHAMPIONS</h2>
                    <div class="season-info">
                        <span class="season-label">Season Reset:</span>
                        <span class="season-countdown" id="season-countdown">--d --h --m</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 3 Podium -->
        <div class="podium-container-premium">
            <div class="podium-platform">
                <!-- Rank 2 (Silver) -->
                <div class="podium-spot rank-2" id="podium-rank-2">
                    <div class="podium-member-empty">
                        <div class="podium-avatar-silhouette"></div>
                        <div class="podium-name-placeholder">II. --</div>
                    </div>
                </div>

                <!-- Rank 1 (Gold) -->
                <div class="podium-spot rank-1" id="podium-rank-1">
                    <div class="podium-member-empty">
                        <div class="podium-avatar-silhouette"></div>
                        <div class="podium-name-placeholder">I. --</div>
                    </div>
                </div>

                <!-- Rank 3 (Bronze) -->
                <div class="podium-spot rank-3" id="podium-rank-3">
                    <div class="podium-member-empty">
                        <div class="podium-avatar-silhouette"></div>
                        <div class="podium-name-placeholder">III. --</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="lb-filters-container">
            <div class="leaderboard-tabs">
                <button class="lb-tab active" data-sort="rank">
                    <span class="tab-icon-css icon-medal"></span>
                    <span class="tab-label">Ranking</span>
                </button>
                <button class="lb-tab" data-sort="level">
                    <span class="tab-icon-css icon-medal"></span>
                    <span class="tab-label">Top Level</span>
                </button>
                <button class="lb-tab" data-sort="wins">
                    <span class="tab-icon-css icon-sword"></span>
                    <span class="tab-label">Most Wins</span>
                </button>
            </div>

            <!-- Elements Scroller -->
            <div class="element-pills-wrapper">
                <div class="element-pills" id="element-pills">
                    <button class="element-pill active" data-element="all">All Elements</button>
                    <!-- Dynamically populated -->
                </div>
            </div>

            <!-- Search Bar -->
            <div class="lb-search-wrapper">
                <div class="lb-search-box">
                    <span class="search-icon-css"></span>
                    <input type="text" id="lb-search" placeholder="Search by name or owner...">
                </div>
            </div>
        </div>

        <!-- Champion List -->
        <div class="leaderboard-list" id="leaderboard-list">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <span>Loading champions...</span>
            </div>
        </div>

        <!-- Load More Container -->
        <div id="lb-load-more-container" class="load-more-container" style="display: none;">
            <button class="lb-load-more-btn shimmer-active" onclick="loadMoreLeaderboard()">
                <i class="fas fa-plus-circle"></i>
                Summon More Champions
            </button>
        </div>

        <!-- Hall of Fame (Past Winners) -->
        <div class="hall-of-fame-section" id="hall-of-fame">
            <div class="hof-header-premium" onclick="toggleHallOfFame()">
                <div class="hof-icon-gold"></div>
                <span>LEGENDARY CHAMPIONS</span>
                <i class="fas fa-chevron-down" id="hof-toggle-icon"></i>
            </div>
            <div class="hof-content-collapsible" id="hof-content" style="display: none;">
                <div class="hof-grid" id="hof-list">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <div class="lb-status-bar">
            <span>Showing Top 100 Guardians</span>
        </div>

    </div>
</div>

<link rel="stylesheet" href="<?= asset_v('css/pet/leaderboard.css') ?>">