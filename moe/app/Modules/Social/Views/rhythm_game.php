<?= $this->extend('layouts/user') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('css/social/rhythm_game.css?v=' . time()) ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="game-container">
    <!-- Hub Screen / Song Selection -->
    <div class="hub-screen" id="hub-screen">
        <div class="hub-header">
            <a href="<?= base_url('pet') ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="hub-title-group">
                <div class="hub-title">
                    <span class="music-icon">♪</span>
                    <h1>Rhythm Game</h1>
                </div>
                <p class="hub-subtitle">Tap the beat, feel the rhythm</p>
            </div>
            <?php if (user_has_role(ROLE_VASIKI)): ?>
                <a href="<?= base_url('rhythm/import') ?>" class="import-osu-btn" title="Import osu! Map">
                    <i class="fas fa-file-import"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Waveform decoration -->
        <div class="waveform-bar">
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
        </div>

        <div class="hub-controls">
            <span class="key-hint"><kbd>D</kbd> <kbd>F</kbd> <kbd>J</kbd> <kbd>K</kbd> to play &nbsp;·&nbsp;
                <kbd>ESC</kbd> pause</span>
        </div>

        <div id="songs-container" class="songs-container">
            <div class="loading" style="text-align:center; padding: 2rem; color: rgba(255,255,255,0.5);">
                <i class="fas fa-spinner fa-spin"></i> Loading songs...
            </div>
        </div>
    </div>

    <!-- Game Screen -->
    <div class="game-screen hidden" id="game-screen">
        <div class="game-header">
            <a href="javascript:void(0)" onclick="exitGame()" class="back-btn"><i class="fas fa-times"></i></a>
            <div class="game-stats">
                <div class="stat-box score-box">
                    <span class="stat-label">Score</span>
                    <span class="stat-value" id="score-display">0</span>
                </div>
                <div class="stat-box combo-box">
                    <span class="stat-label">Combo</span>
                    <span class="stat-value" id="combo-display">0</span>
                </div>
                <div class="stat-box lives-box">
                    <div class="lives-container" id="lives-display"></div>
                </div>
            </div>
        </div>

        <div class="game-area">
            <div class="track-area" id="track-area">
                <div class="track-lane" data-lane="0"></div>
                <div class="track-lane" data-lane="1"></div>
                <div class="track-lane" data-lane="2"></div>
                <div class="track-lane" data-lane="3"></div>
            </div>
            <div class="hit-horizon"></div>

            <!-- Touch input zones -->
            <div class="touch-areas">
                <div class="touch-lane" data-lane="0"></div>
                <div class="touch-lane" data-lane="1"></div>
                <div class="touch-lane" data-lane="2"></div>
                <div class="touch-lane" data-lane="3"></div>
            </div>
        </div>
    </div>

    <!-- Countdown Overlay -->
    <div class="overlay hidden" id="countdown-overlay">
        <div class="overlay-content">
            <h1 id="countdown-number" style="font-size: 6rem;">3</h1>
        </div>
    </div>

    <!-- Pause Overlay -->
    <div class="overlay hidden" id="pause-overlay">
        <div class="overlay-content">
            <h1 style="font-size: 2rem; color: #DAA520; margin-bottom: 8px;">⏸ PAUSED</h1>
            <p style="color: rgba(255,255,255,0.5); margin-bottom: 24px; font-size: 0.9rem;">Press ESC or tap Resume to
                continue
            </p>
            <div style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
                <button class="start-btn" onclick="togglePause()" style="min-width: 200px;">
                    <i class="fas fa-play"></i> Resume
                </button>
                <button class="return-btn" onclick="exitGame()"
                    style="min-width: 200px; background: rgba(231, 76, 60, 0.2); border: 1px solid rgba(231, 76, 60, 0.4); color: #e74c3c;">
                    <i class="fas fa-flag"></i> Forfeit
                </button>
            </div>
        </div>
    </div>

    <!-- Results Overlay -->
    <div class="overlay hidden" id="result-overlay">
        <div class="overlay-content result-content">
            <h1 id="result-title">🎵 Results</h1>
            <div id="result-grade" class="result-grade">-</div>
            <div class="result-stats">
                <div class="result-row">
                    <span class="result-label">Score</span>
                    <span class="result-value" id="final-score">0</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Max Combo</span>
                    <span class="result-value" id="max-combo">0</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Perfect Hits</span>
                    <span class="result-value" id="perfect-hits">0</span>
                </div>
            </div>
            <div class="rewards-section">
                <h3>Rewards</h3>
                <div id="rewards-container">
                    <div class="rewards-row">
                        <span><i class="fas fa-coins text-warning"></i> Gold</span>
                        <span id="reward-gold">+0</span>
                    </div>
                    <div class="rewards-row">
                        <span><i class="fas fa-sparkles text-info"></i> EXP</span>
                        <span id="reward-exp">+0</span>
                    </div>
                    <div style="text-align:center;margin-top:8px;">
                        <span id="reward-info" style="font-size:0.75rem;color:rgba(255,255,255,0.4);"></span>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <button class="return-btn" onclick="returnToSongSelect()">
                    <i class="fas fa-list"></i> Song List
                </button>
                <button class="return-btn" onclick="returnToPet()"
                    style="background: rgba(255,255,255,0.1); color: #fff;">
                    <i class="fas fa-paw"></i> Pet Page
                </button>
            </div>
        </div>
    </div>

    <!-- Effects container (positioned above everything for hit effects) -->
    <div id="effects-container" class="effects-container"></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('page_scripts') ?>
<script src="<?= base_url('js/shared/sound_manager.js') ?>"></script>
<script>
    window.MUSIC_PATH = '<?= base_url('assets/music/') ?>';
</script>
<script src="<?= base_url('js/social/rhythm_game_v2.js?v=' . time()) ?>"></script>
<?= $this->endSection() ?>