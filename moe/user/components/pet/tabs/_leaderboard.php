<!-- Pet Leaderboard Tab - Premium Design v2 (No Emoji) -->
<div class="tab-content" id="tab-leaderboard" style="display: none;">
    <div class="leaderboard-container">

        <!-- Header with animated trophy -->
        <div class="leaderboard-header">
            <div class="lb-trophy-icon"></div>
            <div class="lb-title">
                <h2>PET CHAMPIONS</h2>
                <div class="season-info">
                    <span class="season-label">Season Ends in:</span>
                    <span class="season-countdown" id="season-countdown">--d --h --m</span>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="leaderboard-tabs">
            <button class="lb-tab active" data-sort="rank">
                <span class="tab-icon-css icon-medal"></span>
                <span class="tab-label">Rank Points</span>
            </button>
            <button class="lb-tab" data-sort="wins">
                <span class="tab-icon-css icon-sword"></span>
                <span class="tab-label">Wins</span>
            </button>
            <button class="lb-tab" data-sort="power">
                <span class="tab-icon-css icon-power"></span>
                <span class="tab-label">Power</span>
            </button>
            <button class="lb-tab" data-sort="level">
                <span class="tab-icon-css icon-medal"></span>
                <span class="tab-label">Level</span>
            </button>
        </div>

        <!-- Element Filter Pills -->
        <div class="element-pills" id="element-pills">
            <button class="element-pill active" data-element="all">All</button>
            <!-- Populated by JS -->
        </div>

        <!-- Search Bar -->
        <div class="lb-search-wrapper">
            <div class="lb-search-box">
                <span class="search-icon-css"></span>
                <input type="text" id="lb-search" placeholder="Find Tamer or Pet...">
            </div>
        </div>

        <!-- Period Toggle -->
        <div class="period-toggle-container">
            <span class="period-toggle-label">Leaderboard Period <span id="period-label">(Resets Monthly)</span></span>
            <div class="period-toggle" id="period-toggle">
                <button type="button" class="period-btn active" data-period="monthly">Monthly</button>
                <button type="button" class="period-btn" data-period="alltime">All Time</button>
            </div>
        </div>

        <!-- Rewards Preview Banner -->
        <div class="rewards-banner" id="rewards-banner">
            <h4>Season Rewards</h4>
            <div class="rewards-list">
                <div class="reward-item"><span class="rank-badge gold">1st</span> 500 Coins + Title</div>
                <div class="reward-item"><span class="rank-badge silver">2nd</span> 300 Coins</div>
                <div class="reward-item"><span class="rank-badge bronze">3rd</span> 150 Coins</div>
            </div>
        </div>

        <!-- Top 3 Podium -->
        <div class="podium-section" id="podium-section">
            <!-- Will be populated by JS -->
        </div>

        <!-- Rest of Leaderboard -->
        <div class="leaderboard-list" id="leaderboard-list">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <span>Loading champions...</span>
            </div>
        </div>

        <!-- Hall of Fame (Past Winners) -->
        <div class="hall-of-fame" id="hall-of-fame">
            <div class="hof-header">
                <span class="hof-icon"></span>
                <span>Hall of Fame</span>
            </div>
            <div class="hof-list" id="hof-list">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Hidden selects for legacy support -->
        <select id="lb-sort" style="display:none;">
            <option value="level">Level</option>
        </select>
        <select id="lb-element" style="display:none;">
            <option value="all">All</option>
        </select>

    </div>
</div>

<style>
    /* ================================================
       PET LEADERBOARD - PREMIUM DESIGN v2 (NO EMOJI)
       Clean, Compatible, Mobile-First
       ================================================ */

    :root {
        --lb-gold: #D4AF37;
        --lb-gold-light: #FFD700;
        --lb-silver: #C0C0C0;
        --lb-bronze: #CD7F32;
        --lb-bg-glass: rgba(15, 15, 35, 0.7);
        --lb-border: rgba(255, 255, 255, 0.1);
    }

    .leaderboard-container {
        padding: clamp(0.75rem, 3vw, 2rem);
        max-width: 900px;
        margin: 0 auto;
        position: relative;
    }

    /* ================================
       HEADER
       ================================ */
    .leaderboard-header {
        text-align: center;
        padding: clamp(1.25rem, 4vw, 2rem);
        background: linear-gradient(135deg, var(--lb-bg-glass), rgba(30, 30, 60, 0.5));
        backdrop-filter: blur(20px);
        border-radius: clamp(16px, 4vw, 24px);
        margin-bottom: clamp(1rem, 3vw, 1.5rem);
        border: 1px solid var(--lb-border);
        position: relative;
        overflow: hidden;
    }

    .leaderboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--lb-gold), transparent);
    }

    /* Trophy Icon using CSS */
    .lb-trophy-icon {
        width: clamp(50px, 12vw, 70px);
        height: clamp(50px, 12vw, 70px);
        margin: 0 auto 0.5rem;
        background: linear-gradient(135deg, var(--lb-gold) 0%, var(--lb-gold-light) 50%, var(--lb-gold) 100%);
        background-size: 200% auto;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 3h14c.55 0 1 .45 1 1v3c0 2.21-1.79 4-4 4h-1v2h1.5c.28 0 .5.22.5.5s-.22.5-.5.5h-9c-.28 0-.5-.22-.5-.5s.22-.5.5-.5H9v-2H8c-2.21 0-4-1.79-4-4V4c0-.55.45-1 1-1zm1 2v2c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V5H6zM7 15h10c.55 0 1 .45 1 1v1c0 1.1-.9 2-2 2H8c-1.1 0-2-.9-2-2v-1c0-.55.45-1 1-1z'/%3E%3C/svg%3E") center/contain no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 3h14c.55 0 1 .45 1 1v3c0 2.21-1.79 4-4 4h-1v2h1.5c.28 0 .5.22.5.5s-.22.5-.5.5h-9c-.28 0-.5-.22-.5-.5s.22-.5.5-.5H9v-2H8c-2.21 0-4-1.79-4-4V4c0-.55.45-1 1-1zm1 2v2c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V5H6zM7 15h10c.55 0 1 .45 1 1v1c0 1.1-.9 2-2 2H8c-1.1 0-2-.9-2-2v-1c0-.55.45-1 1-1z'/%3E%3C/svg%3E") center/contain no-repeat;
        animation: trophyFloat 3s ease-in-out infinite, goldShimmer 3s ease-in-out infinite;
        filter: drop-shadow(0 0 15px rgba(212, 175, 55, 0.5));
    }

    @keyframes trophyFloat {

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(-8px) scale(1.05);
        }
    }

    @keyframes goldShimmer {

        0%,
        100% {
            background-position: 0% center;
        }

        50% {
            background-position: 200% center;
        }
    }

    .lb-title h2 {
        font-family: 'Cinzel', 'Georgia', serif;
        font-size: clamp(1.4rem, 5vw, 2rem);
        background: linear-gradient(135deg, var(--lb-gold) 0%, var(--lb-gold-light) 50%, var(--lb-gold) 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0.5rem 0 0.5rem;
        letter-spacing: clamp(1px, 0.5vw, 3px);
        animation: goldShimmer 3s ease-in-out infinite;
        text-transform: uppercase;
    }

    .season-info {
        display: inline-flex;
        align-items: center;
        gap: clamp(0.3rem, 1vw, 0.6rem);
        margin-top: 0.25rem;
        padding: clamp(0.4rem, 1vw, 0.5rem) clamp(0.8rem, 2vw, 1.2rem);
        background: rgba(0, 0, 0, 0.4);
        border-radius: 30px;
        border: 1px solid rgba(255, 215, 0, 0.25);
        font-size: clamp(0.75rem, 2vw, 0.9rem);
    }

    .season-label {
        color: rgba(255, 255, 255, 0.6);
    }

    .season-countdown {
        color: var(--lb-gold-light);
        font-weight: 700;
        font-family: 'Courier New', monospace;
        text-shadow: 0 0 8px rgba(255, 215, 0, 0.4);
    }

    /* ================================
       FILTER TABS - CSS Icons
       ================================ */
    .leaderboard-tabs {
        display: flex;
        gap: clamp(0.4rem, 1.5vw, 0.6rem);
        justify-content: center;
        margin-bottom: clamp(0.75rem, 2vw, 1.25rem);
        flex-wrap: wrap;
        padding: 0 0.5rem;
    }

    .lb-tab {
        background: var(--lb-bg-glass);
        backdrop-filter: blur(10px);
        border: 1px solid var(--lb-border);
        border-radius: 25px;
        padding: clamp(0.6rem, 1.5vw, 0.75rem) clamp(1rem, 2.5vw, 1.4rem);
        color: rgba(255, 255, 255, 0.65);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: clamp(0.8rem, 2vw, 0.9rem);
        font-weight: 600;
    }

    .lb-tab:hover {
        background: rgba(212, 175, 55, 0.15);
        border-color: rgba(212, 175, 55, 0.4);
        transform: translateY(-2px);
    }

    .lb-tab.active {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.25), rgba(255, 215, 0, 0.1));
        border-color: var(--lb-gold);
        color: var(--lb-gold);
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.25);
    }

    /* CSS Icon Shapes */
    .tab-icon-css {
        width: 18px;
        height: 18px;
        display: inline-block;
        background: currentColor;
        opacity: 0.8;
    }

    .icon-medal {
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Ccircle cx='12' cy='8' r='6'/%3E%3Cpath d='M8 14l-3 8 4-2 3 4 3-4 4 2-3-8'/%3E%3C/svg%3E") center/contain no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Ccircle cx='12' cy='8' r='6'/%3E%3Cpath d='M8 14l-3 8 4-2 3 4 3-4 4 2-3-8'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .icon-sword {
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M6.92 5H5L14 14l-1.5 1.5L21 17l-1.5-8.5L18 10 9 1H7.08L6 3l3 3-2.08-.08'/%3E%3Cpath d='M3 21l4-4-2-2-4 4 2 2z'/%3E%3C/svg%3E") center/contain no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M6.92 5H5L14 14l-1.5 1.5L21 17l-1.5-8.5L18 10 9 1H7.08L6 3l3 3-2.08-.08'/%3E%3Cpath d='M3 21l4-4-2-2-4 4 2 2z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .icon-power {
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M13 2L3 14h9l-1 8 10-12h-9l1-8z'/%3E%3C/svg%3E") center/contain no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M13 2L3 14h9l-1 8 10-12h-9l1-8z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .tab-label {
        display: inline;
    }

    /* ================================
       ELEMENT PILLS
       ================================ */
    .element-pills {
        display: flex;
        gap: clamp(0.3rem, 1vw, 0.5rem);
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: clamp(1rem, 2vw, 1.5rem);
        padding: 0 0.5rem;
    }

    .element-pill {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        padding: clamp(0.35rem, 1vw, 0.45rem) clamp(0.7rem, 1.8vw, 1rem);
        color: rgba(255, 255, 255, 0.65);
        font-size: clamp(0.7rem, 1.8vw, 0.8rem);
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .element-pill:hover {
        background: rgba(255, 255, 255, 0.12);
    }

    .element-pill.active {
        background: rgba(212, 175, 55, 0.2);
        border-color: var(--lb-gold);
        color: var(--lb-gold);
    }

    .element-pill[data-element="Fire"] {
        border-color: #FF5722;
        color: #FF8A65;
    }

    .element-pill[data-element="Water"] {
        border-color: #2196F3;
        color: #64B5F6;
    }

    .element-pill[data-element="Earth"] {
        border-color: #8BC34A;
        color: #AED581;
    }

    .element-pill[data-element="Air"] {
        border-color: #03A9F4;
        color: #4FC3F7;
    }

    .element-pill[data-element="Light"] {
        border-color: #FFC107;
        color: #FFD54F;
    }

    .element-pill[data-element="Dark"] {
        border-color: #9C27B0;
        color: #BA68C8;
    }

    /* ================================
       SEARCH BAR
       ================================ */
    .lb-search-wrapper {
        margin-bottom: clamp(1rem, 2vw, 1.5rem);
        padding: 0 0.5rem;
    }

    .lb-search-box {
        position: relative;
        max-width: 450px;
        margin: 0 auto;
    }

    .lb-search-box input {
        width: 100%;
        background: var(--lb-bg-glass);
        backdrop-filter: blur(10px);
        border: 1px solid var(--lb-border);
        border-radius: 14px;
        padding: clamp(0.75rem, 2vw, 0.9rem) 1rem clamp(0.75rem, 2vw, 0.9rem) 2.8rem;
        color: #fff;
        font-family: inherit;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
        transition: all 0.3s ease;
    }

    .lb-search-box input::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .lb-search-box input:focus {
        border-color: var(--lb-gold);
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.15);
        outline: none;
    }

    .search-icon-css {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-radius: 50%;
        pointer-events: none;
    }

    .search-icon-css::after {
        content: '';
        position: absolute;
        bottom: -5px;
        right: -5px;
        width: 6px;
        height: 2px;
        background: rgba(255, 255, 255, 0.4);
        transform: rotate(45deg);
    }

    /* ================================
       PERIOD TOGGLE
       ================================ */
    .period-toggle-container {
        text-align: center;
        margin-bottom: clamp(1rem, 2vw, 1.5rem);
    }

    .period-toggle-label {
        display: block;
        font-size: clamp(0.7rem, 1.8vw, 0.8rem);
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.5rem;
    }

    #period-label {
        color: var(--lb-gold);
    }

    .period-toggle {
        display: inline-flex;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 12px;
        padding: 4px;
        gap: 4px;
    }

    .period-btn {
        background: transparent;
        border: none;
        padding: clamp(0.5rem, 1.5vw, 0.6rem) clamp(1rem, 2.5vw, 1.3rem);
        color: rgba(255, 255, 255, 0.55);
        font-size: clamp(0.75rem, 2vw, 0.85rem);
        font-weight: 600;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .period-btn:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
    }

    .period-btn.active {
        background: linear-gradient(135deg, var(--lb-gold) 0%, #B8860B 100%);
        color: #1a1a2e;
        box-shadow: 0 4px 12px rgba(212, 175, 55, 0.35);
    }

    /* ================================
       REWARDS BANNER
       ================================ */
    .rewards-banner {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(255, 215, 0, 0.03));
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: clamp(12px, 3vw, 16px);
        padding: clamp(0.85rem, 2vw, 1.1rem);
        margin-bottom: clamp(1rem, 2vw, 1.5rem);
        text-align: center;
    }

    .rewards-banner h4 {
        color: var(--lb-gold);
        font-size: clamp(0.9rem, 2.2vw, 1rem);
        margin: 0 0 0.6rem;
        font-weight: 700;
    }

    .rewards-list {
        display: flex;
        justify-content: center;
        gap: clamp(0.6rem, 2vw, 1.2rem);
        flex-wrap: wrap;
    }

    .reward-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: clamp(0.7rem, 1.8vw, 0.85rem);
        color: rgba(255, 255, 255, 0.7);
    }

    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 22px;
        padding: 0 6px;
        border-radius: 12px;
        font-size: 0.65rem;
        font-weight: 800;
    }

    .rank-badge.gold {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        color: #1a1a2e;
    }

    .rank-badge.silver {
        background: linear-gradient(135deg, #E8E8E8, #A0A0A0);
        color: #333;
    }

    .rank-badge.bronze {
        background: linear-gradient(135deg, #CD7F32, #8B4513);
        color: #fff;
    }

    /* ================================
       TOP 3 PODIUM - PRESTIGE HIERARCHY
       ================================ */
    .podium-section {
        position: relative;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: clamp(0.3rem, 1vw, 0.6rem);
        align-items: end;
        justify-items: center;
        margin-bottom: clamp(1.5rem, 3vw, 2rem);
        min-height: clamp(200px, 45vw, 280px);
        padding: clamp(0.8rem, 2vw, 1.2rem);
        border-radius: clamp(16px, 3vw, 24px);

        /* Hero Background */
        background-image: url('/moe/assets/Tier/leaderboard background.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;

        /* Allow overflow for visibility */
        overflow: visible;
    }

    .podium-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.75));
        z-index: 0;
        border-radius: inherit;
    }

    .podium-section>* {
        position: relative;
        z-index: 1;
    }

    .podium-pet {
        text-align: center;
        cursor: pointer;
        transition: all 0.4s ease;
        background: var(--lb-bg-glass);
        backdrop-filter: blur(15px);
        border-radius: clamp(14px, 3vw, 20px);
        padding: clamp(0.75rem, 2.5vw, 1.25rem);
        border: 1px solid var(--lb-border);
        touch-action: pan-y;
        -webkit-tap-highlight-color: transparent;
    }

    .podium-pet:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
    }

    .podium-pet.rank-1 {
        order: 2;
        border-color: rgba(255, 215, 0, 0.4);
        background: linear-gradient(180deg, rgba(255, 215, 0, 0.12), var(--lb-bg-glass));
        box-shadow: 0 0 30px rgba(255, 215, 0, 0.15);
    }

    .podium-pet.rank-2 {
        order: 1;
        border-color: rgba(192, 192, 192, 0.3);
    }

    .podium-pet.rank-3 {
        order: 3;
        border-color: rgba(205, 127, 50, 0.3);
    }

    .podium-avatar {
        position: relative;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Tier Halo - Hidden for cleaner look */
    .tier-halo {
        display: none;
    }


    .podium-crown {
        position: absolute;
        top: clamp(-15px, -3vw, -22px);
        left: 50%;
        transform: translateX(-50%);
        width: clamp(20px, 5vw, 30px);
        height: clamp(15px, 4vw, 22px);
        background: var(--lb-gold-light);
        clip-path: polygon(50% 0%, 0% 100%, 25% 60%, 50% 100%, 75% 60%, 100% 100%);
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
        z-index: 2;
    }

    .podium-pet.rank-1 .podium-crown {
        width: clamp(26px, 6vw, 38px);
        height: clamp(20px, 5vw, 28px);
        animation: crownBounce 2s ease-in-out infinite;
    }

    .podium-pet.rank-2 .podium-crown {
        background: var(--lb-silver);
    }

    .podium-pet.rank-3 .podium-crown {
        background: var(--lb-bronze);
    }

    @keyframes crownBounce {

        0%,
        100% {
            transform: translateX(-50%) translateY(0);
        }

        50% {
            transform: translateX(-50%) translateY(-4px);
        }
    }

    .podium-img {
        width: clamp(55px, 16vw, 85px);
        height: clamp(55px, 16vw, 85px);
        object-fit: contain;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, 0.15);
        background: rgba(0, 0, 0, 0.3);
        padding: 4px;
    }

    .podium-pet.rank-1 .podium-img {
        width: clamp(70px, 22vw, 110px);
        height: clamp(70px, 22vw, 110px);
        border-color: var(--lb-gold-light);
        box-shadow: 0 0 25px rgba(255, 215, 0, 0.4);
    }

    .podium-pet.rank-2 .podium-img {
        border-color: var(--lb-silver);
        box-shadow: 0 0 18px rgba(192, 192, 192, 0.3);
    }

    .podium-pet.rank-3 .podium-img {
        border-color: var(--lb-bronze);
        box-shadow: 0 0 18px rgba(205, 127, 50, 0.3);
    }

    .podium-name {
        font-weight: 700;
        font-size: clamp(0.75rem, 2.2vw, 0.95rem);
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .podium-pet.rank-1 .podium-name {
        color: var(--lb-gold-light);
        font-size: clamp(0.85rem, 2.5vw, 1.05rem);
    }

    .podium-owner {
        font-size: clamp(0.6rem, 1.6vw, 0.72rem);
        color: rgba(255, 255, 255, 0.5);
        margin-top: 0.15rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .podium-stat {
        font-size: clamp(0.75rem, 2vw, 0.9rem);
        color: var(--lb-gold);
        font-weight: 700;
        margin-top: 0.3rem;
        text-shadow: 0 0 6px rgba(255, 215, 0, 0.3);
    }

    .podium-stand {
        margin-top: 0.6rem;
        border-radius: 10px;
        padding: clamp(0.5rem, 1.5vw, 0.7rem);
        font-size: clamp(0.55rem, 1.5vw, 0.7rem);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .podium-pet.rank-1 .podium-stand {
        background: linear-gradient(135deg, var(--lb-gold-light), var(--lb-gold));
        color: #1a1a2e;
    }

    .podium-pet.rank-2 .podium-stand {
        background: linear-gradient(135deg, var(--lb-silver), #808080);
        color: #1a1a2e;
    }

    .podium-pet.rank-3 .podium-stand {
        background: linear-gradient(135deg, var(--lb-bronze), #8B4513);
        color: #fff;
    }

    /* ================================
       LEADERBOARD LIST (Rank 4+)
       ================================ */
    .leaderboard-list {
        display: flex;
        flex-direction: column;
        gap: clamp(0.5rem, 1.5vw, 0.7rem);
    }

    .lb-pet-card {
        display: flex;
        align-items: center;
        gap: clamp(0.6rem, 2vw, 1rem);
        padding: clamp(0.7rem, 2vw, 1rem);
        padding-left: clamp(0.5rem, 1.5vw, 0.8rem);
        background: var(--lb-bg-glass);
        backdrop-filter: blur(12px);
        border: 1px solid var(--lb-border);
        border-left: 4px solid var(--lb-border);
        border-radius: clamp(12px, 3vw, 16px);
        cursor: pointer;
        transition: all 0.3s ease;
        touch-action: pan-y;
        -webkit-tap-highlight-color: transparent;
    }

    /* Tier-Colored Left Borders */
    .lb-pet-card.tier-master {
        border-left-color: #9400D3;
    }

    .lb-pet-card.tier-diamond {
        border-left-color: #00BFFF;
    }

    .lb-pet-card.tier-platinum {
        border-left-color: #00CED1;
    }

    .lb-pet-card.tier-gold {
        border-left-color: var(--lb-gold);
    }

    .lb-pet-card.tier-silver {
        border-left-color: var(--lb-silver);
    }

    .lb-pet-card.tier-bronze {
        border-left-color: var(--lb-bronze);
    }

    .lb-pet-card:hover {
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(212, 175, 55, 0.35);
        transform: translateX(6px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    /* Rank Section (Icon + Number) */
    .lb-pet-card .rank-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
        min-width: clamp(36px, 9vw, 48px);
    }

    .lb-pet-card .tier-icon-medium {
        width: clamp(28px, 7vw, 36px);
        height: clamp(28px, 7vw, 36px);
        object-fit: contain;
        filter: drop-shadow(0 0 4px rgba(255, 215, 0, 0.4));
    }

    .lb-pet-card .rank {
        font-weight: 800;
        font-size: clamp(0.7rem, 1.8vw, 0.85rem);
        color: rgba(255, 255, 255, 0.6);
    }

    .lb-pet-card .pet-img {
        width: clamp(45px, 11vw, 58px);
        height: clamp(45px, 11vw, 58px);
        object-fit: contain;
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.25);
        padding: 3px;
        flex-shrink: 0;
    }

    .lb-pet-card .pet-info {
        flex: 1;
        min-width: 0;
    }

    .lb-pet-card .pet-name {
        font-weight: 700;
        font-size: clamp(0.85rem, 2.2vw, 1rem);
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .lb-pet-card .pet-name.shiny {
        color: var(--lb-gold-light);
    }

    .lb-pet-card .pet-meta {
        display: flex;
        gap: clamp(0.3rem, 1vw, 0.5rem);
        align-items: center;
        margin-top: 0.25rem;
        flex-wrap: wrap;
    }

    .lb-pet-card .element-badge {
        font-size: clamp(0.6rem, 1.6vw, 0.7rem);
        padding: 0.18rem 0.45rem;
        border-radius: 6px;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.1);
    }

    .lb-pet-card .element-badge.fire {
        background: rgba(255, 87, 34, 0.25);
        color: #FF8A65;
    }

    .lb-pet-card .element-badge.water {
        background: rgba(33, 150, 243, 0.25);
        color: #64B5F6;
    }

    .lb-pet-card .element-badge.earth {
        background: rgba(139, 195, 74, 0.25);
        color: #AED581;
    }

    .lb-pet-card .element-badge.air {
        background: rgba(3, 169, 244, 0.25);
        color: #4FC3F7;
    }

    .lb-pet-card .element-badge.light {
        background: rgba(255, 193, 7, 0.25);
        color: #FFD54F;
    }

    .lb-pet-card .element-badge.dark {
        background: rgba(156, 39, 176, 0.25);
        color: #BA68C8;
    }

    .lb-pet-card .owner {
        font-size: clamp(0.65rem, 1.7vw, 0.75rem);
        color: rgba(255, 255, 255, 0.5);
    }

    .lb-pet-card .pet-stats {
        text-align: right;
        min-width: 50px;
    }

    .lb-pet-card .stat-main {
        font-weight: 800;
        font-size: clamp(1rem, 2.5vw, 1.15rem);
        color: var(--lb-gold);
    }

    .lb-pet-card .stat-label {
        font-size: clamp(0.55rem, 1.5vw, 0.65rem);
        color: rgba(255, 255, 255, 0.45);
        text-transform: uppercase;
    }

    /* ================================
       TIER BADGES
       ================================ */
    .tier-badge {
        font-size: clamp(0.6rem, 1.5vw, 0.72rem);
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid transparent;
    }

    .tier-badge.bronze {
        background: linear-gradient(135deg, #CD7F32, #8B4513);
        border-color: #CD7F32;
        color: #fff;
        box-shadow: 0 0 8px rgba(205, 127, 50, 0.4);
    }

    .tier-badge.silver {
        background: linear-gradient(135deg, #E8E8E8, #A0A0A0);
        border-color: #C0C0C0;
        color: #333;
        box-shadow: 0 0 8px rgba(192, 192, 192, 0.4);
    }

    .tier-badge.gold {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-color: #FFD700;
        color: #1a1a2e;
        box-shadow: 0 0 12px rgba(255, 215, 0, 0.5);
    }

    .tier-badge.diamond {
        background: linear-gradient(135deg, #00CED1, #4169E1);
        border-color: #00CED1;
        color: #fff;
        box-shadow: 0 0 12px rgba(0, 206, 209, 0.5);
        animation: diamondPulse 2s ease-in-out infinite;
    }

    .tier-badge.master {
        background: linear-gradient(135deg, #9400D3, #FF1493, #9400D3);
        background-size: 200% auto;
        border-color: #9400D3;
        color: #fff;
        animation: masterFlow 2s linear infinite, masterGlow 1.5s ease-in-out infinite;
    }

    @keyframes diamondPulse {

        0%,
        100% {
            box-shadow: 0 0 8px rgba(0, 206, 209, 0.4);
        }

        50% {
            box-shadow: 0 0 18px rgba(0, 206, 209, 0.7);
        }
    }

    @keyframes masterFlow {
        to {
            background-position: 200% center;
        }
    }

    @keyframes masterGlow {

        0%,
        100% {
            box-shadow: 0 0 10px #9400D3;
        }

        50% {
            box-shadow: 0 0 25px #FF1493, 0 0 35px #9400D3;
        }
    }

    /* ================================
       HALL OF FAME
       ================================ */
    .hall-of-fame {
        background: var(--lb-bg-glass);
        backdrop-filter: blur(15px);
        border-radius: clamp(14px, 3vw, 18px);
        padding: clamp(0.85rem, 2vw, 1.25rem);
        margin-top: clamp(1.5rem, 3vw, 2rem);
        border: 1px solid var(--lb-border);
    }

    .hof-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        color: rgba(255, 255, 255, 0.6);
        font-size: clamp(0.8rem, 2vw, 0.95rem);
        font-weight: 600;
    }

    .hof-icon {
        width: 18px;
        height: 18px;
        background: currentColor;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3L4 9v12h16V9l-8-6zm0 2.5L18 10v9H6v-9l6-4.5z'/%3E%3C/svg%3E") center/contain no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3L4 9v12h16V9l-8-6zm0 2.5L18 10v9H6v-9l6-4.5z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .hof-list {
        display: flex;
        gap: clamp(0.5rem, 1.5vw, 0.75rem);
        overflow-x: auto;
        padding: 0.5rem 0;
        scrollbar-width: thin;
        scrollbar-color: rgba(212, 175, 55, 0.3) transparent;
    }

    .hof-list::-webkit-scrollbar {
        height: 4px;
    }

    .hof-list::-webkit-scrollbar-thumb {
        background: rgba(212, 175, 55, 0.3);
        border-radius: 4px;
    }

    .hof-item {
        flex-shrink: 0;
        text-align: center;
        background: rgba(255, 255, 255, 0.04);
        border-radius: 14px;
        padding: clamp(0.6rem, 1.5vw, 0.85rem);
        min-width: clamp(85px, 22vw, 105px);
        border: 1px solid rgba(255, 255, 255, 0.06);
        transition: all 0.3s ease;
    }

    .hof-item:hover {
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(212, 175, 55, 0.25);
    }

    .hof-item img {
        width: clamp(45px, 12vw, 58px);
        height: clamp(45px, 12vw, 58px);
        border-radius: 50%;
        object-fit: contain;
        background: rgba(0, 0, 0, 0.25);
        margin-bottom: 0.35rem;
        border: 2px solid rgba(212, 175, 55, 0.35);
    }

    .hof-month {
        font-size: clamp(0.6rem, 1.6vw, 0.7rem);
        color: var(--lb-gold);
        margin-bottom: 0.2rem;
        font-weight: 600;
    }

    .hof-name {
        font-size: clamp(0.65rem, 1.8vw, 0.8rem);
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ================================
       LOADING & EMPTY STATES
       ================================ */
    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        padding: 2.5rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .spinner {
        width: clamp(32px, 8vw, 42px);
        height: clamp(32px, 8vw, 42px);
        border: 3px solid rgba(212, 175, 55, 0.15);
        border-top-color: var(--lb-gold);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    .spinner.small {
        width: 20px;
        height: 20px;
        border-width: 2px;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .empty-state {
        text-align: center;
        padding: 2.5rem;
        color: rgba(255, 255, 255, 0.5);
        font-size: clamp(0.9rem, 2.2vw, 1rem);
    }

    /* ================================
       LOAD MORE BUTTON
       ================================ */
    .load-more-container {
        text-align: center;
        padding: 1.5rem 0;
    }

    .load-more-btn {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.05));
        border: 1px solid rgba(212, 175, 55, 0.35);
        color: var(--lb-gold);
        padding: clamp(0.65rem, 1.5vw, 0.8rem) clamp(1.6rem, 4vw, 2.2rem);
        border-radius: 25px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
    }

    .load-more-btn:hover {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.25), rgba(212, 175, 55, 0.1));
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.2);
    }

    /* ================================
       DESKTOP ENHANCEMENTS (1024px+)
       ================================ */
    @media (min-width: 1024px) {
        .leaderboard-container {
            padding: 2.5rem;
        }

        .podium-section {
            gap: 1.5rem;
            min-height: 340px;
        }

        .podium-pet {
            padding: 1.5rem;
        }

        .podium-pet.rank-1 .podium-img {
            width: 130px;
            height: 130px;
        }

        .lb-pet-card {
            padding: 1.25rem;
        }

        .lb-pet-card:hover {
            transform: translateX(10px);
        }
    }

    @media (min-width: 1440px) {
        50% {
            box-shadow: 0 0 25px #FF1493, 0 0 35px #9400D3;
        }
    }

    /* ================================
       HALL OF FAME
       ================================ */
    .hall-of-fame {
        background: var(--lb-bg-glass);
        backdrop-filter: blur(15px);
        border-radius: clamp(14px, 3vw, 18px);
        padding: clamp(0.85rem, 2vw, 1.25rem);
        margin-top: clamp(1.5rem, 3vw, 2rem);
        border: 1px solid var(--lb-border);
    }

    .hof-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        color: rgba(255, 255, 255, 0.6);
        font-size: clamp(0.8rem, 2vw, 0.95rem);
        font-weight: 600;
    }

    .hof-icon {
        width: 18px;
        height: 18px;
        background: currentColor;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3L4 9v12h16V9l-8-6zm0 2.5L18 10v9H6v-9l6-4.5z'/%3E%3C/svg%3E") center/contain no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3L4 9v12h16V9l-8-6zm0 2.5L18 10v9H6v-9l6-4.5z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .hof-list {
        display: flex;
        gap: clamp(0.5rem, 1.5vw, 0.75rem);
        overflow-x: auto;
        padding: 0.5rem 0;
        scrollbar-width: thin;
        scrollbar-color: rgba(212, 175, 55, 0.3) transparent;
    }

    .hof-list::-webkit-scrollbar {
        height: 4px;
    }

    .hof-list::-webkit-scrollbar-thumb {
        background: rgba(212, 175, 55, 0.3);
        border-radius: 4px;
    }

    .hof-item {
        flex-shrink: 0;
        text-align: center;
        background: rgba(255, 255, 255, 0.04);
        border-radius: 14px;
        padding: clamp(0.6rem, 1.5vw, 0.85rem);
        min-width: clamp(85px, 22vw, 105px);
        border: 1px solid rgba(255, 255, 255, 0.06);
        transition: all 0.3s ease;
    }

    .hof-item:hover {
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(212, 175, 55, 0.25);
    }

    .hof-item img {
        width: clamp(45px, 12vw, 58px);
        height: clamp(45px, 12vw, 58px);
        border-radius: 50%;
        object-fit: contain;
        background: rgba(0, 0, 0, 0.25);
        margin-bottom: 0.35rem;
        border: 2px solid rgba(212, 175, 55, 0.35);
    }

    .hof-month {
        font-size: clamp(0.6rem, 1.6vw, 0.7rem);
        color: var(--lb-gold);
        margin-bottom: 0.2rem;
        font-weight: 600;
    }

    .hof-name {
        font-size: clamp(0.65rem, 1.8vw, 0.8rem);
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ================================
       LOADING & EMPTY STATES
       ================================ */
    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        padding: 2.5rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .spinner {
        width: clamp(32px, 8vw, 42px);
        height: clamp(32px, 8vw, 42px);
        border: 3px solid rgba(212, 175, 55, 0.15);
        border-top-color: var(--lb-gold);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    .spinner.small {
        width: 20px;
        height: 20px;
        border-width: 2px;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .empty-state {
        text-align: center;
        padding: 2.5rem;
        color: rgba(255, 255, 255, 0.5);
        font-size: clamp(0.9rem, 2.2vw, 1rem);
    }

    /* ================================
       LOAD MORE BUTTON
       ================================ */
    .load-more-container {
        text-align: center;
        padding: 1.5rem 0;
    }

    .load-more-btn {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.05));
        border: 1px solid rgba(212, 175, 55, 0.35);
        color: var(--lb-gold);
        padding: clamp(0.65rem, 1.5vw, 0.8rem) clamp(1.6rem, 4vw, 2.2rem);
        border-radius: 25px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
    }

    .load-more-btn:hover {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.25), rgba(212, 175, 55, 0.1));
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.2);
    }

    /* ================================
       DESKTOP ENHANCEMENTS (1024px+)
       ================================ */
    @media (min-width: 1024px) {
        .leaderboard-container {
            padding: 2.5rem;
        }

        .podium-section {
            gap: 1.5rem;
            min-height: 340px;
        }

        .podium-pet {
            padding: 1.5rem;
        }

        .podium-pet.rank-1 .podium-img {
            width: 130px;
            height: 130px;
        }

        .lb-pet-card {
            padding: 1.25rem;
        }

        .lb-pet-card:hover {
            transform: translateX(10px);
        }
    }

    @media (min-width: 1440px) {
        .leaderboard-container {
            max-width: 1000px;
        }
    }

    /* ================================
       TIER ICON STYLES
       ================================ */
    .tier-icon {
        width: 28px;
        height: 28px;
        object-fit: contain;
        vertical-align: middle;
        filter: drop-shadow(0 0 4px rgba(255, 215, 0, 0.4));
        transition: transform 0.2s ease, filter 0.2s ease;
    }

    .tier-icon:hover {
        transform: scale(1.1);
        filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.6));
    }

    .tier-icon-small {
        width: 20px;
        height: 20px;
        object-fit: contain;
        vertical-align: middle;
        margin-left: 6px;
        filter: drop-shadow(0 0 3px rgba(255, 215, 0, 0.3));
        transition: transform 0.2s ease;
    }

    .tier-icon-small:hover {
        transform: scale(1.15);
    }

    .podium-rp {
        font-size: clamp(0.7rem, 2vw, 0.9rem);
        color: var(--lb-gold);
        font-weight: 700;
        margin-top: 0.3rem;
        text-shadow: 0 0 8px rgba(255, 215, 0, 0.3);
    }

    .podium-stand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: 0.6rem;
        border-radius: 10px;
        padding: clamp(0.5rem, 1.5vw, 0.7rem);
        font-size: clamp(0.6rem, 1.6vw, 0.75rem);
        font-weight: 700;
        text-transform: uppercase;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid var(--lb-border);
        transition: all 0.3s ease;
    }

    .podium-pet:hover .podium-stand {
        transform: translateY(-2px);
    }

    .podium-pet.rank-1 .podium-stand {
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.05));
        border-color: var(--lb-gold);
    }

    .podium-pet.rank-2 .podium-stand {
        background: linear-gradient(135deg, rgba(192, 192, 192, 0.15), rgba(192, 192, 192, 0.05));
        border-color: var(--lb-silver);
    }

    .podium-pet.rank-3 .podium-stand {
        background: linear-gradient(135deg, rgba(205, 127, 50, 0.15), rgba(205, 127, 50, 0.05));
        border-color: var(--lb-bronze);
    }
</style>