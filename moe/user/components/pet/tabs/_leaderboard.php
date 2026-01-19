<!-- Pet Leaderboard Tab - Premium Design -->
<div class="tab-content" id="tab-leaderboard" style="display: none;">
    <div class="leaderboard-container">

        <!-- Header with animated trophy -->
        <div class="leaderboard-header">
            <div class="lb-trophy">🏆</div>
            <div class="lb-title">
                <h2>PET CHAMPIONS</h2>
                <p class="lb-subtitle">Top trainers across all realms</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="leaderboard-tabs">
            <button class="lb-tab active" data-sort="level">
                <span class="tab-icon">🏅</span>
                <span class="tab-label">Level</span>
            </button>
            <button class="lb-tab" data-sort="wins">
                <span class="tab-icon">⚔️</span>
                <span class="tab-label">Wins</span>
            </button>
            <button class="lb-tab" data-sort="power">
                <span class="tab-icon">💪</span>
                <span class="tab-label">Power</span>
            </button>
        </div>

        <!-- Element Filter Pills -->
        <div class="element-pills" id="element-pills">
            <button class="element-pill active" data-element="all">All</button>
            <!-- Populated by JS -->
        </div>

        <!-- Period Toggle (Monthly / All Time) -->
        <div class="period-toggle-container">
            <span class="period-toggle-label">Leaderboard Period <span id="period-label">(Resets Monthly)</span></span>
            <div class="period-toggle" id="period-toggle">
                <button type="button" class="period-btn active" data-period="monthly">🌙 Monthly</button>
                <button type="button" class="period-btn" data-period="alltime">⭐ All Time</button>
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
   PET LEADERBOARD - PREMIUM RESPONSIVE DESIGN
   ================================================ */
    .leaderboard-container {
        padding: 1rem;
        max-width: 800px;
        margin: 0 auto;
    }

    /* Header */
    .leaderboard-header {
        text-align: center;
        padding: 1.5rem 1rem;
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(255, 215, 0, 0.05));
        border-radius: 20px;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(212, 175, 55, 0.3);
    }

    .lb-trophy {
        font-size: 3rem;
        animation: trophyBounce 2s ease-in-out infinite;
        filter: drop-shadow(0 0 15px rgba(212, 175, 55, 0.6));
    }

    @keyframes trophyBounce {

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(-8px) scale(1.05);
        }
    }

    .lb-title h2 {
        font-family: 'Cinzel', serif;
        font-size: clamp(1.4rem, 5vw, 2rem);
        background: linear-gradient(135deg, #D4AF37, #FFD700);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0.5rem 0 0.25rem;
        letter-spacing: 2px;
    }

    .lb-subtitle {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.85rem;
        margin: 0;
    }

    /* Filter Tabs */
    .leaderboard-tabs {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .lb-tab {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 0.6rem 1rem;
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .lb-tab:hover {
        background: rgba(212, 175, 55, 0.1);
        border-color: rgba(212, 175, 55, 0.3);
    }

    .lb-tab.active {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(255, 215, 0, 0.1));
        border-color: #D4AF37;
        color: #D4AF37;
    }

    .tab-icon {
        font-size: 1rem;
    }

    /* Element Pills */
    .element-pills {
        display: flex;
        gap: 0.4rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        padding: 0 0.5rem;
    }

    .element-pill {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        padding: 0.35rem 0.75rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .element-pill:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .element-pill.active {
        background: rgba(212, 175, 55, 0.2);
        border-color: #D4AF37;
        color: #D4AF37;
    }

    /* Element colors */
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

    /* Period Toggle */
    .period-toggle-container {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .period-toggle-label {
        display: block;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.5rem;
    }

    #period-label {
        color: #D4AF37;
    }

    .period-toggle {
        display: inline-flex;
        gap: 0.5rem;
        background: rgba(0, 0, 0, 0.3);
        padding: 4px;
        border-radius: 12px;
    }

    .period-btn {
        background: transparent;
        border: none;
        padding: 0.5rem 1rem;
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .period-btn:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
    }

    .period-btn.active {
        background: linear-gradient(135deg, #D4AF37 0%, #B8860B 100%);
        color: #1a1a2e;
    }

    /* ================================================
   TOP 3 PODIUM
   ================================================ */
    .podium-section {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        padding: 0 0.5rem;
        min-height: 180px;
    }

    .podium-pet {
        text-align: center;
        transition: transform 0.3s ease;
    }

    .podium-pet:hover {
        transform: translateY(-5px);
    }

    .podium-pet.rank-1 {
        order: 2;
    }

    .podium-pet.rank-2 {
        order: 1;
    }

    .podium-pet.rank-3 {
        order: 3;
    }

    .podium-owner {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
        margin-top: -2px;
        margin-bottom: 2px;
        max-width: 80px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-left: auto;
        margin-right: auto;
    }

    .podium-pet.rank-1 .podium-owner {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .podium-avatar {
        position: relative;
        margin-bottom: 0.5rem;
    }

    .podium-img {
        width: 70px;
        height: 70px;
        object-fit: contain;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, 0.2);
        background: rgba(0, 0, 0, 0.3);
        padding: 5px;
    }

    .podium-pet.rank-1 .podium-img {
        width: 90px;
        height: 90px;
        border-color: #FFD700;
        box-shadow: 0 0 25px rgba(255, 215, 0, 0.5);
    }

    .podium-pet.rank-2 .podium-img {
        border-color: #C0C0C0;
        box-shadow: 0 0 15px rgba(192, 192, 192, 0.4);
    }

    .podium-pet.rank-3 .podium-img {
        border-color: #CD7F32;
        box-shadow: 0 0 15px rgba(205, 127, 50, 0.4);
    }

    .podium-crown {
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 1.5rem;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
    }

    .podium-pet.rank-1 .podium-crown {
        font-size: 2rem;
        top: -25px;
    }

    .podium-name {
        font-weight: 700;
        font-size: 0.8rem;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 80px;
    }

    .podium-pet.rank-1 .podium-name {
        color: #FFD700;
        font-size: 0.9rem;
        max-width: 100px;
    }

    .podium-stat {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        margin-top: 0.2rem;
    }

    .podium-stand {
        width: 70px;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 900;
        margin-top: 0.5rem;
    }

    .podium-pet.rank-1 .podium-stand {
        height: 60px;
        width: 90px;
        background: linear-gradient(180deg, #FFD700 0%, #B8860B 100%);
        color: #fff;
    }

    .podium-pet.rank-2 .podium-stand {
        height: 45px;
        background: linear-gradient(180deg, #C0C0C0 0%, #808080 100%);
        color: #fff;
    }

    .podium-pet.rank-3 .podium-stand {
        height: 35px;
        background: linear-gradient(180deg, #CD7F32 0%, #8B4513 100%);
        color: #fff;
    }

    /* ================================================
   LEADERBOARD LIST (Rank 4+)
   ================================================ */
    .leaderboard-list {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .lb-pet-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        transition: all 0.2s ease;
    }

    .lb-pet-card:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(212, 175, 55, 0.3);
        transform: translateX(5px);
    }

    .lb-pet-card .rank {
        min-width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.5);
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
    }

    .lb-pet-card .pet-img {
        width: 48px;
        height: 48px;
        object-fit: contain;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.2);
        padding: 4px;
    }

    .lb-pet-card .pet-info {
        flex: 1;
        min-width: 0;
    }

    .lb-pet-card .pet-name {
        font-weight: 700;
        font-size: 0.9rem;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lb-pet-card .pet-name.shiny {
        color: #FFD700;
    }

    .lb-pet-card .pet-meta {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        margin-top: 0.2rem;
        flex-wrap: wrap;
    }

    .lb-pet-card .element-badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.4rem;
        border-radius: 6px;
        font-weight: 600;
    }

    .lb-pet-card .owner {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .lb-pet-card .pet-stats {
        text-align: right;
        min-width: 50px;
    }

    .lb-pet-card .stat-main {
        font-weight: 800;
        font-size: 1rem;
        color: #D4AF37;
    }

    .lb-pet-card .stat-label {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.4);
        text-transform: uppercase;
    }

    /* Loading Spinner */
    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        padding: 2rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .spinner {
        width: 32px;
        height: 32px;
        border: 3px solid rgba(212, 175, 55, 0.2);
        border-top-color: #D4AF37;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* ================================================
   RESPONSIVE - MOBILE FIRST
   ================================================ */

    /* Extra Small (320px - phones) */
    @media (max-width: 359px) {
        .leaderboard-container {
            padding: 0.5rem;
        }

        .lb-trophy {
            font-size: 2rem;
        }

        .lb-title h2 {
            font-size: 1.2rem;
        }

        .leaderboard-tabs {
            gap: 0.3rem;
        }

        .lb-tab {
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
        }

        .tab-label {
            display: none;
        }

        .tab-icon {
            font-size: 1.1rem;
        }

        .element-pills {
            gap: 0.25rem;
        }

        .element-pill {
            padding: 0.25rem 0.5rem;
            font-size: 0.65rem;
        }

        .podium-section {
            gap: 0.3rem;
            min-height: 150px;
        }

        .podium-img {
            width: 50px;
            height: 50px;
        }

        .podium-pet.rank-1 .podium-img {
            width: 65px;
            height: 65px;
        }

        .podium-name {
            font-size: 0.65rem;
            max-width: 55px;
        }

        .podium-pet.rank-1 .podium-name {
            font-size: 0.75rem;
            max-width: 70px;
        }

        .podium-stand {
            width: 55px;
        }

        .podium-pet.rank-1 .podium-stand {
            width: 70px;
            height: 50px;
        }

        .podium-pet.rank-2 .podium-stand {
            height: 35px;
        }

        .podium-pet.rank-3 .podium-stand {
            height: 28px;
        }

        .podium-crown {
            font-size: 1rem;
            top: -12px;
        }

        .podium-pet.rank-1 .podium-crown {
            font-size: 1.3rem;
            top: -15px;
        }

        .lb-pet-card {
            padding: 0.5rem;
            gap: 0.5rem;
        }

        .lb-pet-card .rank {
            min-width: 26px;
            height: 26px;
            font-size: 0.7rem;
        }

        .lb-pet-card .pet-img {
            width: 40px;
            height: 40px;
        }

        .lb-pet-card .pet-name {
            font-size: 0.8rem;
        }

        .lb-pet-card .stat-main {
            font-size: 0.85rem;
        }
    }

    /* Small (360px - 479px) */
    @media (min-width: 360px) and (max-width: 479px) {
        .leaderboard-container {
            padding: 0.75rem;
        }

        .tab-label {
            display: none;
        }

        .tab-icon {
            font-size: 1.2rem;
        }

        .podium-img {
            width: 60px;
            height: 60px;
        }

        .podium-pet.rank-1 .podium-img {
            width: 75px;
            height: 75px;
        }

        .podium-name {
            max-width: 65px;
        }

        .podium-pet.rank-1 .podium-name {
            max-width: 85px;
        }
    }

    /* Medium (480px - 639px) */
    @media (min-width: 480px) and (max-width: 639px) {
        .tab-label {
            display: inline;
        }

        .lb-tab {
            padding: 0.5rem 0.9rem;
        }
    }

    /* Large (640px+) */
    @media (min-width: 640px) {
        .leaderboard-container {
            padding: 1.5rem;
        }

        .leaderboard-header {
            padding: 2rem;
        }

        .lb-trophy {
            font-size: 4rem;
        }

        .podium-img {
            width: 80px;
            height: 80px;
        }

        .podium-pet.rank-1 .podium-img {
            width: 100px;
            height: 100px;
        }

        .podium-name {
            max-width: 100px;
            font-size: 0.9rem;
        }

        .podium-pet.rank-1 .podium-name {
            max-width: 120px;
            font-size: 1rem;
        }

        .podium-stand {
            width: 80px;
        }

        .podium-pet.rank-1 .podium-stand {
            width: 100px;
            height: 70px;
        }

        .lb-pet-card {
            padding: 1rem;
            gap: 1rem;
        }

        .lb-pet-card .pet-img {
            width: 56px;
            height: 56px;
        }
    }
</style>