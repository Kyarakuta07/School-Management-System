<!-- Pet Leaderboard Tab - Premium Design -->

<div class="tab-content" id="tab-leaderboard" style="display: none;">

    <div class="leaderboard-container">



        <!-- Header with animated trophy -->

        <div class="leaderboard-header">

            <div class="lb-trophy">ðŸ†</div>

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

            <button class="lb-tab active" data-sort="level">

                <span class="tab-icon">ðŸ…</span>

                <span class="tab-label">Level</span>

            </button>

            <button class="lb-tab" data-sort="wins">

                <span class="tab-icon">âš”ï¸</span>

                <span class="tab-label">Wins</span>

            </button>

            <button class="lb-tab" data-sort="power">

                <span class="tab-icon">ðŸ’ª</span>

                <span class="tab-label">Power</span>

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

                <span class="search-icon">ðŸ”</span>

                <input type="text" id="lb-search" placeholder="Find Tamer or Pet...">

            </div>

        </div>



        <!-- Period Toggle (Monthly / All Time) -->

        <div class="period-toggle-container">

            <span class="period-toggle-label">Leaderboard Period <span id="period-label">(Resets Monthly)</span></span>

            <div class="period-toggle" id="period-toggle">

                <button type="button" class="period-btn active" data-period="monthly">ðŸŒ™ Monthly</button>

                <button type="button" class="period-btn" data-period="alltime">â­ All Time</button>

            </div>

        </div>



        <!-- Rewards Preview Banner -->

        <div class="rewards-banner" id="rewards-banner">

            <h4>ðŸ† Season Rewards</h4>

            <div class="rewards-list">

                <div class="reward-item"><span class="rank-icon">ðŸ¥‡</span> 500 Coins + Exclusive Title</div>

                <div class="reward-item"><span class="rank-icon">ðŸ¥ˆ</span> 300 Coins</div>

                <div class="reward-item"><span class="rank-icon">ðŸ¥‰</span> 150 Coins</div>

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

                <span>ðŸ›ï¸</span>

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
    :root {

        --lb-gold: #D4AF37;

        --lb-gold-light: #FFD700;

        --lb-silver: #C0C0C0;

        --lb-bronze: #CD7F32;

        --lb-bg-glass: rgba(15, 15, 35, 0.6);

        --lb-border: rgba(255, 255, 255, 0.08);

    }



    .leaderboard-container {

        padding: clamp(0.75rem, 3vw, 2rem);

        max-width: 900px;

        margin: 0 auto;

    }



    .leaderboard-container::before {

        content: '';

        position: absolute;

        top: 0;

        left: 50%;

        transform: translateX(-50%);

        width: 150%;

        height: 300px;

        background: radial-gradient(ellipse at center top, rgba(212, 175, 55, 0.12) 0%, transparent 70%);

        pointer-events: none;

        z-index: -1;

    }



    .leaderboard-header {

        text-align: center;

        padding: clamp(1rem, 4vw, 2rem);

        background: linear-gradient(135deg, var(--lb-bg-glass), rgba(30, 30, 60, 0.4));

        backdrop-filter: blur(20px);

        border-radius: clamp(16px, 4vw, 28px);

        margin-bottom: clamp(1rem, 3vw, 1.5rem);

        border: 1px solid var(--lb-border);

        position: relative;

        overflow: hidden;

    }



    .lb-trophy {

        font-size: clamp(2.5rem, 8vw, 4rem);

        animation: trophyFloat 3s ease-in-out infinite;

        filter: drop-shadow(0 0 20px rgba(212, 175, 55, 0.5));

    }



    @keyframes trophyFloat {



        0%,

        100% {

            transform: translateY(0) rotate(-3deg);

        }



        50% {

            transform: translateY(-12px) rotate(3deg);

        }

    }



    .lb-title h2 {

        font-family: 'Cinzel', serif;

        font-size: clamp(1.3rem, 5vw, 2.2rem);

        background: linear-gradient(135deg, var(--lb-gold) 0%, var(--lb-gold-light) 50%, var(--lb-gold) 100%);

        background-size: 200% auto;

        -webkit-background-clip: text;

        -webkit-text-fill-color: transparent;

        background-clip: text;

        margin: 0.5rem 0 0.3rem;

        letter-spacing: clamp(1px, 0.5vw, 3px);

        animation: goldShimmer 3s ease-in-out infinite;

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



    .season-info {

        display: inline-flex;

        align-items: center;

        gap: clamp(0.3rem, 1vw, 0.6rem);

        margin-top: 0.5rem;

        padding: clamp(0.3rem, 1vw, 0.5rem) clamp(0.6rem, 2vw, 1rem);

        background: rgba(0, 0, 0, 0.4);

        border-radius: 30px;

        border: 1px solid rgba(255, 215, 0, 0.2);

        font-size: clamp(0.7rem, 2vw, 0.85rem);

    }



    .season-label {

        color: rgba(255, 255, 255, 0.6);

    }



    .season-countdown {

        color: var(--lb-gold-light);

        font-weight: 700;

        font-family: 'Courier New', monospace;

        text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);

    }



    .leaderboard-tabs {

        display: flex;

        gap: clamp(0.3rem, 1vw, 0.5rem);

        justify-content: center;

        margin-bottom: clamp(0.75rem, 2vw, 1.25rem);

        flex-wrap: wrap;

    }



    .lb-tab {

        background: var(--lb-bg-glass);

        backdrop-filter: blur(10px);

        border: 1px solid var(--lb-border);

        border-radius: 30px;

        padding: clamp(0.5rem, 1.5vw, 0.7rem) clamp(0.8rem, 2vw, 1.2rem);

        color: rgba(255, 255, 255, 0.6);

        cursor: pointer;

        transition: all 0.3s ease;

        display: flex;

        align-items: center;

        gap: 0.3rem;

        font-size: clamp(0.7rem, 2vw, 0.85rem);

        font-weight: 600;

    }



    .lb-tab:hover {

        background: rgba(212, 175, 55, 0.1);

        transform: translateY(-2px);

    }



    .lb-tab.active {

        background: linear-gradient(135deg, rgba(212, 175, 55, 0.25), rgba(255, 215, 0, 0.1));

        border-color: var(--lb-gold);

        color: var(--lb-gold);

        box-shadow: 0 4px 20px rgba(212, 175, 55, 0.2);

    }



    .tab-icon {

        font-size: clamp(0.9rem, 2.5vw, 1.1rem);

    }



    .tab-label {

        display: none;

    }



    @media (min-width: 480px) {

        .tab-label {

            display: inline;

        }

    }



    .element-pills {

        display: flex;

        gap: clamp(0.25rem, 1vw, 0.4rem);

        justify-content: center;

        flex-wrap: wrap;

        margin-bottom: clamp(1rem, 2vw, 1.5rem);

    }



    .element-pill {

        background: rgba(255, 255, 255, 0.05);

        border: 1px solid rgba(255, 255, 255, 0.12);

        border-radius: 20px;

        padding: clamp(0.25rem, 0.8vw, 0.4rem) clamp(0.5rem, 1.5vw, 0.8rem);

        color: rgba(255, 255, 255, 0.6);

        font-size: clamp(0.6rem, 1.8vw, 0.75rem);

        cursor: pointer;

        transition: all 0.2s ease;

    }



    .element-pill:hover {

        background: rgba(255, 255, 255, 0.1);

    }



    .element-pill.active {

        background: rgba(212, 175, 55, 0.15);

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

        border-radius: 16px;

        padding: clamp(0.7rem, 2vw, 0.9rem) 1rem clamp(0.7rem, 2vw, 0.9rem) 2.8rem;

        color: #fff;

        font-size: clamp(0.8rem, 2vw, 0.95rem);

        transition: all 0.3s ease;

    }



    .lb-search-box input::placeholder {

        color: rgba(255, 255, 255, 0.35);

    }



    .lb-search-box input:focus {

        border-color: var(--lb-gold);

        box-shadow: 0 0 25px rgba(212, 175, 55, 0.15);

        outline: none;

    }



    .lb-search-box .search-icon {

        position: absolute;

        left: 1rem;

        top: 50%;

        transform: translateY(-50%);

        color: rgba(255, 255, 255, 0.35);

        font-size: 1rem;

    }



    .period-toggle-container {

        text-align: center;

        margin-bottom: clamp(1rem, 2vw, 1.5rem);

    }



    .period-toggle-label {

        display: block;

        font-size: clamp(0.65rem, 1.8vw, 0.75rem);

        color: rgba(255, 255, 255, 0.45);

        margin-bottom: 0.5rem;

    }



    #period-label {

        color: var(--lb-gold);

    }



    .period-toggle {

        display: inline-flex;

        background: rgba(0, 0, 0, 0.4);

        border-radius: 14px;

        padding: 4px;

        gap: 4px;

    }



    .period-btn {

        background: transparent;

        border: none;

        padding: clamp(0.4rem, 1.2vw, 0.55rem) clamp(0.8rem, 2vw, 1.1rem);

        color: rgba(255, 255, 255, 0.5);

        font-size: clamp(0.7rem, 1.8vw, 0.8rem);

        font-weight: 600;

        border-radius: 11px;

        cursor: pointer;

        transition: all 0.25s ease;

    }



    .period-btn:hover {

        color: #fff;

        background: rgba(255, 255, 255, 0.08);

    }



    .period-btn.active {

        background: linear-gradient(135deg, var(--lb-gold) 0%, #B8860B 100%);

        color: #1a1a2e;

        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.35);

    }



    .rewards-banner {

        background: linear-gradient(135deg, rgba(212, 175, 55, 0.08), rgba(255, 215, 0, 0.03));

        border: 1px solid rgba(212, 175, 55, 0.25);

        border-radius: clamp(12px, 3vw, 18px);

        padding: clamp(0.75rem, 2vw, 1rem);

        margin-bottom: clamp(1rem, 2vw, 1.5rem);

        text-align: center;

    }



    .rewards-banner h4 {

        color: var(--lb-gold);

        font-size: clamp(0.8rem, 2vw, 0.95rem);

        margin: 0 0 0.5rem;

        font-weight: 700;

    }



    .rewards-list {

        display: flex;

        justify-content: center;

        gap: clamp(0.5rem, 2vw, 1rem);

        flex-wrap: wrap;

    }



    .reward-item {

        display: flex;

        align-items: center;

        gap: 0.25rem;

        font-size: clamp(0.65rem, 1.8vw, 0.8rem);

        color: rgba(255, 255, 255, 0.65);

    }



    .reward-item .rank-icon {

        font-size: clamp(0.9rem, 2.5vw, 1.15rem);

    }



    .podium-section {

        display: grid;

        grid-template-columns: 1fr 1.2fr 1fr;

        gap: clamp(0.5rem, 2vw, 1rem);

        align-items: end;

        margin-bottom: clamp(1.5rem, 3vw, 2rem);

        min-height: clamp(180px, 40vw, 280px);

    }



    .podium-pet {

        text-align: center;

        cursor: pointer;

        transition: all 0.4s ease;

        background: var(--lb-bg-glass);

        backdrop-filter: blur(15px);

        border-radius: clamp(14px, 3vw, 20px);

        padding: clamp(0.5rem, 2vw, 1rem);

        border: 1px solid var(--lb-border);

    }



    .podium-pet:hover {

        transform: translateY(-8px) scale(1.02);

        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);

    }



    .podium-pet.rank-1 {

        order: 2;

        border-color: rgba(255, 215, 0, 0.4);

        background: linear-gradient(180deg, rgba(255, 215, 0, 0.1), var(--lb-bg-glass));

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

        margin-bottom: 0.4rem;

    }



    .podium-crown {

        position: absolute;

        top: clamp(-18px, -4vw, -25px);

        left: 50%;

        transform: translateX(-50%);

        font-size: clamp(1.2rem, 4vw, 1.8rem);

        filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.5));

        z-index: 2;

    }



    .podium-pet.rank-1 .podium-crown {

        font-size: clamp(1.5rem, 5vw, 2.2rem);

        animation: crownBounce 2s ease-in-out infinite;

    }



    @keyframes crownBounce {

        0%,
        100% {
            transform: translateX(-50%) translateY(0);
        }

        50% {
            transform: translateX(-50%) translateY(-5px);
        }
    }
</style>