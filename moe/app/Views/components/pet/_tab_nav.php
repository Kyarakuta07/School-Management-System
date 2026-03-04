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
    <button class="tab-btn" data-tab="library">
        <i class="fas fa-dragon"></i>
        <span>Bestiary</span>
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