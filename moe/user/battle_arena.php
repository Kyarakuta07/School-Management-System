<?php
/**
 * MOE Pet System - Battle Arena
 * Dragon City-style turn-based combat
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['id_nethera'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['id_nethera'];

// Include database connection and rate limiter
include '../config/connection.php';
require_once '../core/rate_limiter.php';

// Check battle rate limit (3 battles per day)
$rate_limiter = new RateLimiter($conn);
$user_id_str = 'user_' . $user_id;
$current_attempts = $rate_limiter->getAttempts($user_id_str, 'pet_battle');

if ($current_attempts >= 3) {
    // Redirect back with error
    header("Location: pet.php?tab=arena&error=battle_limit");
    exit();
}

// Get battle parameters from URL
$defender_pet_id = isset($_GET['defender_id']) ? intval($_GET['defender_id']) : 0;
$attacker_pet_id = isset($_GET['attacker_id']) ? intval($_GET['attacker_id']) : 0;

if (!$defender_pet_id || !$attacker_pet_id) {
    header("Location: pet.php?tab=arena&error=missing_pets");
    exit();
}

// Get attacker pet data (user's pet)
$atk_query = mysqli_prepare(
    $conn,
    "SELECT up.*, ps.name as species_name, ps.element, ps.rarity, ps.img_adult
     FROM user_pets up 
     JOIN pet_species ps ON up.species_id = ps.id 
     WHERE up.id = ? AND up.user_id = ?"
);
mysqli_stmt_bind_param($atk_query, "ii", $attacker_pet_id, $user_id);
mysqli_stmt_execute($atk_query);
$atk_result = mysqli_stmt_get_result($atk_query);
$attacker = mysqli_fetch_assoc($atk_result);
mysqli_stmt_close($atk_query);

if (!$attacker) {
    header("Location: pet.php?tab=arena&error=invalid_attacker");
    exit();
}

// Get defender pet data
$def_query = mysqli_prepare(
    $conn,
    "SELECT up.*, ps.name as species_name, ps.element, ps.rarity, ps.img_adult,
            n.nama_lengkap as owner_name
     FROM user_pets up 
     JOIN pet_species ps ON up.species_id = ps.id 
     JOIN nethera n ON up.user_id = n.id_nethera
     WHERE up.id = ?"
);
mysqli_stmt_bind_param($def_query, "i", $defender_pet_id);
mysqli_stmt_execute($def_query);
$def_result = mysqli_stmt_get_result($def_query);
$defender = mysqli_fetch_assoc($def_result);
mysqli_stmt_close($def_query);

if (!$defender) {
    header("Location: pet.php?tab=arena&error=invalid_defender");
    exit();
}

// Get attacker's skills
$skills_query = mysqli_prepare(
    $conn,
    "SELECT * FROM pet_skills WHERE species_id = ? ORDER BY skill_slot"
);
mysqli_stmt_bind_param($skills_query, "i", $attacker['species_id']);
mysqli_stmt_execute($skills_query);
$skills_result = mysqli_stmt_get_result($skills_query);
$attacker_skills = [];
while ($skill = mysqli_fetch_assoc($skills_result)) {
    $attacker_skills[] = $skill;
}
mysqli_stmt_close($skills_query);

// Get defender's skills (for AI)
$def_skills_query = mysqli_prepare(
    $conn,
    "SELECT * FROM pet_skills WHERE species_id = ? ORDER BY skill_slot"
);
mysqli_stmt_bind_param($def_skills_query, "i", $defender['species_id']);
mysqli_stmt_execute($def_skills_query);
$def_skills_result = mysqli_stmt_get_result($def_skills_query);
$defender_skills = [];
while ($skill = mysqli_fetch_assoc($def_skills_result)) {
    $defender_skills[] = $skill;
}
mysqli_stmt_close($def_skills_query);

// Calculate effective HP based on level
$attacker_max_hp = 100 + ($attacker['level'] * 5);
$defender_max_hp = 100 + ($defender['level'] * 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>⚔️ Battle Arena - MOE Pet</title>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Outfit:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Battle CSS -->
    <link rel="stylesheet" href="css/battle_arena_premium.css">
</head>

<body>
    <div class="battle-container">
        <!-- Header -->
        <div class="battle-header">
            <button class="back-btn" onclick="forfeitBattle()">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h1>⚔️ BATTLE</h1>
            <div class="turn-indicator" id="turn-indicator">YOUR TURN</div>
        </div>

        <!-- Battle Stage -->
        <div class="battle-stage">
            <!-- Defender (Enemy) - Top -->
            <div class="combatant enemy-side">
                <div class="pet-info">
                    <span
                        class="pet-name"><?php echo htmlspecialchars($defender['nickname'] ?? $defender['species_name']); ?></span>
                    <span class="pet-level">Lv.<?php echo $defender['level']; ?></span>
                    <span
                        class="element-badge <?php echo strtolower($defender['element']); ?>"><?php echo $defender['element']; ?></span>
                </div>
                <div class="hp-bar-container">
                    <div class="hp-bar" id="enemy-hp-bar" style="width: 100%"></div>
                    <span class="hp-text" id="enemy-hp-text"><?php echo $defender_max_hp; ?> /
                        <?php echo $defender_max_hp; ?></span>
                </div>
                <div class="pet-sprite enemy-sprite">
                    <img src="../assets/pets/<?php echo htmlspecialchars($defender['img_adult']); ?>"
                        alt="<?php echo htmlspecialchars($defender['species_name']); ?>" id="enemy-pet-img"
                        onerror="this.src='../assets/placeholder.png'">
                </div>
            </div>

            <!-- VS Divider -->
            <div class="vs-divider">
                <span>VS</span>
            </div>

            <!-- Attacker (Player) - Bottom -->
            <div class="combatant player-side">
                <div class="pet-sprite player-sprite">
                    <img src="../assets/pets/<?php echo htmlspecialchars($attacker['img_adult']); ?>"
                        alt="<?php echo htmlspecialchars($attacker['species_name']); ?>" id="player-pet-img"
                        onerror="this.src='../assets/placeholder.png'">
                </div>
                <div class="hp-bar-container">
                    <div class="hp-bar player-hp" id="player-hp-bar" style="width: 100%"></div>
                    <span class="hp-text" id="player-hp-text"><?php echo $attacker_max_hp; ?> /
                        <?php echo $attacker_max_hp; ?></span>
                </div>
                <div class="pet-info">
                    <span
                        class="pet-name"><?php echo htmlspecialchars($attacker['nickname'] ?? $attacker['species_name']); ?></span>
                    <span class="pet-level">Lv.<?php echo $attacker['level']; ?></span>
                    <span
                        class="element-badge <?php echo strtolower($attacker['element']); ?>"><?php echo $attacker['element']; ?></span>
                </div>
            </div>
        </div>

        <!-- Battle Log -->
        <div class="battle-log" id="battle-log">
            <div class="log-entry">Battle Start! Choose your attack!</div>
        </div>

        <!-- Skill Buttons -->
        <div class="skills-panel" id="skills-panel">
            <?php if (count($attacker_skills) > 0): ?>
                <?php foreach ($attacker_skills as $index => $skill): ?>
                    <button class="skill-btn <?php echo $skill['is_special'] ? 'special' : ''; ?>"
                        data-skill-id="<?php echo $skill['id']; ?>" data-damage="<?php echo $skill['base_damage']; ?>"
                        data-element="<?php echo $skill['skill_element']; ?>"
                        onclick="useSkill(<?php echo $skill['id']; ?>, <?php echo $skill['base_damage']; ?>, '<?php echo $skill['skill_element']; ?>')">
                        <span class="skill-name"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                        <span class="skill-damage">
                            <i class="fas fa-bolt"></i> <?php echo $skill['base_damage']; ?>
                        </span>
                        <span class="skill-element element-icon <?php echo $skill['skill_element']; ?>"></span>
                    </button>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default skills if none defined -->
                <button class="skill-btn" onclick="useSkill(0, 25, '<?php echo strtolower($attacker['element']); ?>')">
                    <span class="skill-name">Basic Attack</span>
                    <span class="skill-damage"><i class="fas fa-bolt"></i> 25</span>
                </button>
                <button class="skill-btn" onclick="useSkill(0, 40, '<?php echo strtolower($attacker['element']); ?>')">
                    <span class="skill-name">Power Strike</span>
                    <span class="skill-damage"><i class="fas fa-bolt"></i> 40</span>
                </button>
                <button class="skill-btn special"
                    onclick="useSkill(0, 60, '<?php echo strtolower($attacker['element']); ?>')">
                    <span class="skill-name">Special Attack</span>
                    <span class="skill-damage"><i class="fas fa-bolt"></i> 60</span>
                </button>
                <button class="skill-btn special"
                    onclick="useSkill(0, 80, '<?php echo strtolower($attacker['element']); ?>')">
                    <span class="skill-name">Ultimate</span>
                    <span class="skill-damage"><i class="fas fa-bolt"></i> 80</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Result Overlay -->
        <div class="result-overlay hidden" id="result-overlay">
            <div class="result-content">
                <h1 id="result-title">🏆 Victory!</h1>
                <div class="result-stats">
                    <div class="result-row">
                        <span>Gold Earned</span>
                        <span id="reward-gold">+0</span>
                    </div>
                    <div class="result-row">
                        <span>EXP Earned</span>
                        <span id="reward-exp">+0</span>
                    </div>
                </div>
                <button class="return-btn" onclick="returnToArena()">
                    <i class="fas fa-trophy"></i> Return to Arena
                </button>
            </div>
        </div>
    </div>

    <!-- Battle Config -->
    <script>
        const BATTLE_CONFIG = {
            attackerPetId: <?php echo $attacker_pet_id; ?>,
            defenderPetId: <?php echo $defender_pet_id; ?>,
            attackerElement: '<?php echo strtolower($attacker['element']); ?>',
            defenderElement: '<?php echo strtolower($defender['element']); ?>',
            attackerMaxHp: <?php echo $attacker_max_hp; ?>,
            defenderMaxHp: <?php echo $defender_max_hp; ?>,
            attackerLevel: <?php echo $attacker['level']; ?>,
            defenderLevel: <?php echo $defender['level']; ?>,
            attackerBaseAtk: 10,
            defenderBaseAtk: 10,
            attackerBaseDef: 10,
            defenderBaseDef: 10,
            defenderSkills: <?php echo json_encode($defender_skills); ?>
        };
        const API_BASE = 'pet_api.php';
    </script>

    <!-- Battle JS -->
    <script src="js/sound_manager.js"></script>
    <script src="js/battle_arena.js"></script>
</body>

</html>