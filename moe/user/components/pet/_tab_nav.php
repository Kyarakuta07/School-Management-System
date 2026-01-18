<!-- Tab Navigation with Battle Dropdown -->
<nav class="tab-nav">
    <button class="tab-btn active" data-tab="my-pet">
        <i class="fas fa-paw"></i>
        <span>My Pet</span>
    </button>
    <button class="tab-btn" data-tab="collection">
        <i class="fas fa-book-open"></i>
        <span>Collection</span>
    </button>
    <button class="tab-btn" data-tab="gacha">
        <i class="fas fa-egg"></i>
        <span>Gacha</span>
    </button>
    <button class="tab-btn" data-tab="shop">
        <i class="fas fa-store"></i>
        <span>Shop</span>
    </button>

    <!-- Battle Dropdown -->
    <div class="tab-dropdown">
        <button class="tab-btn dropdown-trigger" id="battle-dropdown-btn">
            <i class="fas fa-swords"></i>
            <span>Battle</span>
            <i class="fas fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-menu" id="battle-dropdown-menu">
            <button class="dropdown-item" data-tab="arena">
                <i class="fas fa-shield-alt"></i> Arena 1v1
            </button>
            <button class="dropdown-item" data-tab="arena3v3">
                <i class="fas fa-users"></i> Arena 3v3
            </button>
            <button class="dropdown-item" data-tab="war">
                <i class="fas fa-flag"></i> Sanctuary War
            </button>
            <button class="dropdown-item" data-tab="leaderboard">
                <i class="fas fa-crown"></i> Leaderboard
            </button>
        </div>
    </div>

    <button class="tab-btn" data-tab="achievements">
        <i class="fas fa-trophy"></i>
        <span>Badges</span>
    </button>
</nav>

<script>
    // Dropdown toggle
    document.getElementById('battle-dropdown-btn')?.addEventListener('click', function (e) {
        e.stopPropagation();
        const menu = document.getElementById('battle-dropdown-menu');
        menu.classList.toggle('show');
        this.classList.toggle('active-dropdown');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        const dropdown = document.querySelector('.tab-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            document.getElementById('battle-dropdown-menu')?.classList.remove('show');
            document.getElementById('battle-dropdown-btn')?.classList.remove('active-dropdown');
        }
    });

    // Handle dropdown item clicks
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function () {
            const tab = this.dataset.tab;
            // Find and click the corresponding hidden tab functionality
            switchToTab(tab);
            // Close dropdown
            document.getElementById('battle-dropdown-menu')?.classList.remove('show');
            document.getElementById('battle-dropdown-btn')?.classList.remove('active-dropdown');
            // Mark dropdown as active visually
            document.getElementById('battle-dropdown-btn')?.classList.add('active');
        });
    });

    function switchToTab(tabName) {
        // Remove active from all tabs
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
        // Show selected tab content
        const tabContent = document.getElementById('tab-' + tabName);
        if (tabContent) tabContent.style.display = 'block';
        // Trigger load functions
        if (tabName === 'arena' && typeof loadOpponents === 'function') loadOpponents();
        if (tabName === 'arena3v3' && typeof loadTeamSelection === 'function') loadTeamSelection();
        if (tabName === 'war' && typeof initSanctuaryWar === 'function') initSanctuaryWar();
        if (tabName === 'leaderboard' && typeof initLeaderboard === 'function') initLeaderboard();
    }
</script>