<?php
/**
 * MOE Pet System - Rhythm Game
 * Fullscreen rhythm game experience
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['id_nethera'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['id_nethera'];

// Get pet_id from URL
$pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : 0;
$pet_img = isset($_GET['pet_img']) ? htmlspecialchars($_GET['pet_img']) : '';

if (!$pet_id) {
    header("Location: pet.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>🎵 Rhythm Game - MOE Pet</title>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Outfit:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Game CSS -->
    <link rel="stylesheet" href="css/rhythm_game.css">
</head>

<body>
    <!-- Game Container -->
    <div class="game-container">
        <!-- Header Bar -->
        <div class="game-header">
            <button class="back-btn" onclick="exitGame()">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="game-stats">
                <div class="stat-box score-box">
                    <span class="stat-label">SCORE</span>
                    <span class="stat-value" id="score-display">0</span>
                </div>
                <div class="stat-box combo-box">
                    <span class="stat-label">COMBO</span>
                    <span class="stat-value" id="combo-display">0</span>
                </div>
                <div class="stat-box timer-box">
                    <span class="stat-label">TIME</span>
                    <span class="stat-value" id="timer-display">30</span>
                </div>
            </div>
        </div>

        <!-- Pet Display -->
        <div class="pet-display">
            <img id="dancing-pet" src="<?php echo $pet_img; ?>" alt="Dancing Pet"
                onerror="this.src='../assets/placeholder.png'">
        </div>

        <!-- Game Area (4 Lanes) -->
        <div class="game-area" id="game-area">
            <div class="lane" data-lane="0">
                <div class="lane-key">D</div>
            </div>
            <div class="lane" data-lane="1">
                <div class="lane-key">F</div>
            </div>
            <div class="lane" data-lane="2">
                <div class="lane-key">J</div>
            </div>
            <div class="lane" data-lane="3">
                <div class="lane-key">K</div>
            </div>

            <!-- Hit Zone -->
            <div class="hit-zone">
                <div class="hit-indicator"></div>
            </div>

            <!-- Notes will be spawned here by JS -->
        </div>

        <!-- Touch Areas (Mobile) -->
        <div class="touch-areas">
            <div class="touch-lane" data-lane="0"></div>
            <div class="touch-lane" data-lane="1"></div>
            <div class="touch-lane" data-lane="2"></div>
            <div class="touch-lane" data-lane="3"></div>
        </div>

        <!-- Start Overlay -->
        <div class="overlay" id="start-overlay">
            <div class="overlay-content">
                <h1>🎵 Rhythm Game</h1>
                <p>Tap notes when they reach the hit zone!</p>
                <div class="controls-info">
                    <div class="control-row">
                        <span class="key">D</span>
                        <span class="key">F</span>
                        <span class="key">J</span>
                        <span class="key">K</span>
                    </div>
                    <p class="or-text">or tap on mobile</p>
                </div>
                <button class="start-btn" onclick="startGame()">
                    <i class="fas fa-play"></i> START
                </button>
            </div>
        </div>

        <!-- Result Overlay -->
        <div class="overlay hidden" id="result-overlay">
            <div class="overlay-content result-content">
                <h1 id="result-title">🎉 Great!</h1>
                <div class="result-stats">
                    <div class="result-row">
                        <span class="result-label">Final Score</span>
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
                <div class="rewards-section" id="rewards-section">
                    <h3>🎁 Rewards</h3>
                    <div class="rewards-row">
                        <span>😊 Mood</span>
                        <span id="reward-mood">+0</span>
                    </div>
                    <div class="rewards-row">
                        <span>⭐ EXP</span>
                        <span id="reward-exp">+0</span>
                    </div>
                </div>
                <button class="return-btn" onclick="returnToPet()">
                    <i class="fas fa-paw"></i> Return to Pet
                </button>
            </div>
        </div>
    </div>

    <!-- Game Config -->
    <script>
        const PET_ID = <?php echo $pet_id; ?>;
        const API_BASE = 'pet_api.php';
    </script>

    <!-- Game JS -->
    <script src="js/sound_manager.js"></script>
    <script src="js/rhythm_game.js"></script>
</body>

</html>