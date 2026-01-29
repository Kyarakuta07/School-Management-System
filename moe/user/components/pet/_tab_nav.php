<!-- Tab Navigation - Swipeable with Scroll Indicator -->
<nav class="tab-nav" id="tab-nav">
    <button class="tab-btn active" data-tab="my-pet">
        <i class="fas fa-paw"></i>
        <span>My Pet</span>
    </button>
    <button class="tab-btn" data-tab="collection">
        <i class="fas fa-book"></i>
        <span>Collect</span>
    </button>
    <button class="tab-btn" data-tab="gacha">
        <i class="fas fa-dice"></i>
        <span>Gacha</span>
    </button>
    <button class="tab-btn" data-tab="shop">
        <i class="fas fa-store"></i>
        <span>Shop</span>
    </button>
    <button class="tab-btn" data-tab="battle" id="battle-tab-btn">
        <i class="fas fa-gamepad"></i>
        <span>Battle</span>
    </button>
    <button class="tab-btn" data-tab="achievements">
        <i class="fas fa-trophy"></i>
        <span>Badges</span>
    </button>
</nav>

<!-- Scroll Indicator -->
<div class="scroll-indicator" id="scroll-indicator">
    <div class="indicator-track">
        <div class="indicator-thumb" id="indicator-thumb"></div>
    </div>
</div>

<!-- Battle Bottom Sheet -->
<div class="bottom-sheet-overlay" id="battle-sheet-overlay"></div>
<div class="bottom-sheet" id="battle-sheet">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3>⚔️ Battle Mode</h3>
        <p>Choose your arena</p>
    </div>
    <div class="sheet-options">
        <button class="sheet-option" data-tab="arena">
            <div class="option-icon">🛡️</div>
            <div class="option-info">
                <span class="option-title">Arena 1v1</span>
                <span class="option-desc">Quick battle against random opponent</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </button>
        <button class="sheet-option" data-tab="arena3v3">
            <div class="option-icon">👥</div>
            <div class="option-info">
                <span class="option-title">Arena 3v3</span>
                <span class="option-desc">Team battle with 3 pets</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </button>
        <button class="sheet-option" data-tab="war">
            <div class="option-icon">🚩</div>
            <div class="option-info">
                <span class="option-title">Sanctuary War</span>
                <span class="option-desc">Weekly sanctuary competition</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </button>
        <button class="sheet-option" data-tab="leaderboard">
            <div class="option-icon">👑</div>
            <div class="option-info">
                <span class="option-title">Leaderboard</span>
                <span class="option-desc">Top pets ranking</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </button>
        <button class="sheet-option" data-tab="history">
            <div class="option-icon">📜</div>
            <div class="option-info">
                <span class="option-title">Battle History</span>
                <span class="option-desc">Your battle records</span>
            </div>
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabNav = document.getElementById('tab-nav');
        const battleBtn = document.getElementById('battle-tab-btn');
        const battleSheet = document.getElementById('battle-sheet');
        const battleOverlay = document.getElementById('battle-sheet-overlay');
        const indicatorThumb = document.getElementById('indicator-thumb');

        // Read URL parameter and switch to that tab if specified
        const urlParams = new URLSearchParams(window.location.search);
        const tabFromUrl = urlParams.get('tab');
        if (tabFromUrl && typeof switchToTab === 'function') {
            // Increased delay slightly to defeat race conditions with ui.js
            console.log('Force switching to tab:', tabFromUrl);
            setTimeout(() => switchToTab(tabFromUrl), 300);
        }

        // Scroll indicator
        if (tabNav && indicatorThumb) {
            tabNav.addEventListener('scroll', function () {
                const scrollPercent = tabNav.scrollLeft / (tabNav.scrollWidth - tabNav.clientWidth);
                const thumbPosition = scrollPercent * (100 - 30); // 30% thumb width
                indicatorThumb.style.left = thumbPosition + '%';
            });

            // Hide indicator if no scroll needed
            if (tabNav.scrollWidth <= tabNav.clientWidth) {
                document.getElementById('scroll-indicator').style.display = 'none';
            }
        }

        // Handle ALL main tab button clicks (except Battle which opens sheet)
        document.querySelectorAll('.tab-btn').forEach(btn => {
            if (btn.id !== 'battle-tab-btn') {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const tabName = this.dataset.tab;
                    if (tabName) {
                        switchToTab(tabName);
                    }
                });
            }
        });

        // Battle button opens bottom sheet
        if (battleBtn) {
            battleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openBattleSheet();
            });
        }

        // Close sheet on overlay click
        if (battleOverlay) {
            battleOverlay.addEventListener('click', closeBattleSheet);
        }

        // Handle sheet option clicks
        document.querySelectorAll('.sheet-option').forEach(option => {
            option.addEventListener('click', function () {
                const tabName = this.dataset.tab;
                closeBattleSheet();
                switchToTab(tabName);

                // Mark battle tab as active
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                battleBtn.classList.add('active');
            });
        });

        // Swipe down to close
        let startY = 0;
        battleSheet.addEventListener('touchstart', function (e) {
            startY = e.touches[0].clientY;
        });

        battleSheet.addEventListener('touchmove', function (e) {
            const currentY = e.touches[0].clientY;
            const diff = currentY - startY;
            if (diff > 50) {
                closeBattleSheet();
            }
        });
    });

    function openBattleSheet() {
        document.getElementById('battle-sheet').classList.add('open');
        document.getElementById('battle-sheet-overlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeBattleSheet() {
        document.getElementById('battle-sheet').classList.remove('open');
        document.getElementById('battle-sheet-overlay').classList.remove('show');
        document.body.style.overflow = '';
    }

    function switchToTab(tabName) {
        // Battle submenu items should highlight the Battle button
        const battleTabs = ['arena', 'arena3v3', 'war', 'leaderboard'];
        const isBattleTab = battleTabs.includes(tabName);

        // Remove active from all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');

            // If this is a battle submenu tab, highlight the Battle button
            if (isBattleTab && btn.dataset.tab === 'battle') {
                btn.classList.add('active');
            }
            // Otherwise highlight matching button
            else if (btn.dataset.tab === tabName) {
                btn.classList.add('active');
            }
        });

        // Remove active class from ALL content tabs
        document.querySelectorAll('.tab-content, .tab-panel').forEach(content => {
            content.classList.remove('active');
            content.style.display = 'none';
        });

        // Try new format first (tab-war, tab-leaderboard)
        let tabContent = document.getElementById('tab-' + tabName);

        // If not found, try legacy format (arena, arena3v3, my-pet, collection, etc)
        if (!tabContent) {
            tabContent = document.getElementById(tabName);
        }

        if (tabContent) {
            tabContent.classList.add('active');
            tabContent.style.display = 'block';
        }

        // Update URL without page reload (for bookmarking and consistency)
        const newUrl = window.location.pathname + '?tab=' + tabName;
        history.pushState({ tab: tabName }, '', newUrl);

        // Trigger tab-specific load functions
        setTimeout(() => {
            if (tabName === 'arena' && typeof loadOpponents === 'function') loadOpponents();
            if (tabName === 'arena3v3' && typeof loadTeamSelection === 'function') loadTeamSelection();
            if (tabName === 'war' && typeof initSanctuaryWar === 'function') initSanctuaryWar();
            if (tabName === 'leaderboard') {
                console.log('[TAB] Leaderboard tab - initLeaderboard type:', typeof initLeaderboard);
                if (typeof initLeaderboard === 'function') initLeaderboard();
            }
            if (tabName === 'history' && typeof loadBattleHistoryTab === 'function') loadBattleHistoryTab();
            if (tabName === 'achievements' && typeof loadAchievements === 'function') loadAchievements();
            if (tabName === 'collection' && typeof initCollectionSearch === 'function') initCollectionSearch();
        }, 100);
    }

    // Expose globally
    window.openBattleSheet = openBattleSheet;
    window.closeBattleSheet = closeBattleSheet;
    window.switchToTab = switchToTab;
</script>