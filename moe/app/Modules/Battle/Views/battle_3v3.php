<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, orientation=landscape">
    <meta name="screen-orientation" content="landscape">
    <title>⚔️ Battle Arena 3v3 - MOE Pet</title>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Outfit:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Battle 3v3 CSS -->
    <link rel="stylesheet" href="<?= base_url('css/battle/battle_3v3.css') ?>">

    <!-- PixiJS for visual effects -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.3.2/pixi.min.js"></script>
</head>

<body>
    <!-- PixiJS Effects Container -->
    <div id="pixi-container"></div>

    <!-- Rotate Device Overlay (shows in portrait) -->
    <div class="rotate-overlay" id="rotate-overlay">
        <div class="rotate-content">
            <i class="fas fa-mobile-alt rotate-icon"></i>
            <h2>Please Rotate Your Device</h2>
            <p>This game requires landscape mode</p>
            <div class="rotate-arrow">
                <i class="fas fa-sync-alt"></i>
            </div>
        </div>
    </div>

    <!-- Mystical Background Particles -->
    <div class="battle-mystical-bg">
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="god-rays"></div>
        <div class="vignette-overlay"></div>
        <div class="hit-flash" id="hit-flash"></div>
    </div>

    <!-- Battle Container (hidden in portrait) -->
    <main class="battle-container" id="battle-container">
        <!-- Header -->
        <header class="battle-header">
            <button class="back-btn" onclick="forfeitBattle()">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="turn-info">
                <span class="opponent-name" id="opponent-name">VS Opponent</span>
                <span class="turn-indicator" id="turn-indicator">YOUR TURN</span>
                <span class="turn-count" id="turn-count">Turn 1</span>
            </div>
            <button class="forfeit-btn" onclick="forfeitBattle()">
                <i class="fas fa-flag"></i> Forfeit
            </button>
        </header>

        <!-- Battle Stage -->
        <section class="battle-stage">
            <!-- Enemy Team (Top Right) -->
            <div class="team-area enemy-area enemy-stage">
                <div class="team-indicators" id="enemy-indicators">
                    <!-- Filled by JS -->
                </div>
                <div class="active-pet enemy-pet" id="enemy-active">
                    <div class="gloss-overlay"></div>
                    <div class="pet-header">
                        <h3 class="pet-name" id="enemy-name">Enemy Pet</h3>
                        <span class="element-badge" id="enemy-element">Fire</span>
                    </div>
                    <div class="hp-bar-container">
                        <div class="hp-bar" id="enemy-hp-bar" style="width: 100%"></div>
                        <span class="hp-text" id="enemy-hp-text">100/100</span>
                    </div>
                    <div class="pet-sprite enemy-sprite" id="enemy-sprite">
                        <img src="" alt="Enemy Pet" id="enemy-img">
                    </div>
                </div>
            </div>

            <!-- VS Divider -->
            <div class="vs-divider">
                <span>VS</span>
            </div>

            <!-- Player Team (Bottom Left) -->
            <div class="team-area player-area player-stage">
                <div class="active-pet player-pet" id="player-active">
                    <div class="gloss-overlay"></div>
                    <div class="pet-sprite player-sprite" id="player-sprite">
                        <img src="" alt="Your Pet" id="player-img">
                    </div>
                    <div class="hp-bar-container">
                        <div class="hp-bar player-hp" id="player-hp-bar" style="width: 100%"></div>
                        <span class="hp-text" id="player-hp-text">100/100</span>
                    </div>
                    <div class="pet-header">
                        <h3 class="pet-name" id="player-name">Your Pet</h3>
                        <span class="element-badge" id="player-element">Fire</span>
                    </div>
                </div>
                <div class="team-indicators" id="player-indicators">
                    <!-- Filled by JS -->
                </div>
            </div>
        </section>

        <!-- Battle Log -->
        <aside class="battle-log" id="battle-log">
            <div class="log-entry">Battle Start! Choose your attack!</div>
        </aside>

        <!-- Control Panel -->
        <nav class="control-panel">
            <!-- Skill Grid 2x2 -->
            <div class="skills-grid" id="skills-grid">
                <!-- Filled by JS -->
            </div>

            <!-- Swap Button -->
            <button class="swap-btn" id="swap-btn" onclick="openSwapModal()">
                <i class="fas fa-exchange-alt"></i>
                <span>SWAP</span>
            </button>
        </nav>
    </main>

    <!-- Swap Modal -->
    <div class="modal-overlay hidden" id="swap-modal">
        <div class="modal-content">
            <h2><i class="fas fa-exchange-alt"></i> Switch Pet</h2>
            <div class="swap-pets" id="swap-pets">
                <!-- Filled by JS -->
            </div>
            <button class="modal-close" onclick="closeSwapModal()">Cancel</button>
        </div>
    </div>

    <!-- Result Overlay -->
    <div class="result-overlay hidden" id="result-overlay">
        <div class="result-content">
            <h1 id="result-title">🏆 Victory!</h1>
            <div class="result-stats">
                <div class="result-row">
                    <span><i class="fas fa-coins"></i> Gold Earned</span>
                    <span id="reward-gold">+0</span>
                </div>
                <div class="result-row">
                    <span><i class="fas fa-star"></i> EXP Earned</span>
                    <span id="reward-exp">+0</span>
                </div>
                <div class="result-row" id="rp-reward-row">
                    <span><i class="fas fa-shield-alt"></i> Rank Points</span>
                    <span id="reward-rp">+0</span>
                </div>
            </div>
            <button class="return-btn" onclick="returnToArena()">
                <i class="fas fa-trophy"></i> Return to Arena
            </button>
        </div>
    </div>

    <!-- Battle Config -->
    <script>
        const BATTLE_ID = '<?= esc($battleId) ?>';
        const API_BASE = '<?= base_url('api/') ?>';
        const ASSET_BASE = '<?= base_url() ?>';
        const CSRF_TOKEN = '<?= csrf_hash() ?>';
        const CSRF_HEADER = '<?= csrf_header() ?>';
    </script>

    <!-- Battle JS -->
    <script src="<?= base_url('js/shared/sound_manager.js') ?>"></script>
    <script src="<?= base_url('js/shared/csrf_helper.js') ?>"></script>
    <script src="<?= base_url('js/battle/pixi_battle.js') ?>"></script>
    <script src="<?= base_url('js/battle/battle_3v3.js') ?>"></script>
</body>

</html>