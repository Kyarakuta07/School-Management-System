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
    <link rel="stylesheet" href="<?= base_url('css/battle/battle_arena_premium.css') ?>">

    <!-- PixiJS for Premium Effects -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.3.2/pixi.min.js"></script>
</head>

<body>
    <!-- PixiJS Container -->
    <div id="pixi-container"></div>

    <!-- Mystical Background Particles -->
    <div class="battle-mystical-bg">
        <div class="god-rays"></div>
        <div class="dust-motes"></div>
        <div class="vignette-overlay"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
        <div class="mystical-particle"></div>
    </div>

    <div class="battle-container">
        <!-- Header -->
        <header class="battle-header">
            <button class="back-btn" onclick="forfeitBattle()">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h1>⚔️ BATTLE</h1>
            <div class="turn-indicator" id="turn-indicator">YOUR TURN</div>
        </header>

        <!-- Cinematic VS Overlay -->
        <div class="vs-aura-container">
            <div class="vs-text">VS</div>
        </div>

        <!-- Battle Stage -->
        <main class="battle-stage">
            <!-- Defender (Enemy) - Top -->
            <div class="combatant enemy-side">
                <div class="combat-hud">
                    <div class="pet-info">
                        <span class="pet-name">
                            <?= esc($defender['nickname'] ?? $defender['species_name']) ?>
                        </span>
                        <div class="pet-meta">
                            <span class="pet-level">Lv. <?= $defender['level'] ?></span>
                            <span class="element-badge <?= strtolower($defender['element']) ?>">
                                <?= $defender['element'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="hp-bar-container">
                        <div class="hp-bar" id="enemy-hp-bar" style="width: 100%"></div>
                        <span class="hp-text" id="enemy-hp-text">
                            <?= $defenderMaxHp ?> / <?= $defenderMaxHp ?>
                        </span>
                    </div>
                </div>
                <div class="pet-sprite enemy-sprite">
                    <div class="pet-shadow"></div>
                    <img src="<?= base_url('assets/pets/' . esc($defenderImg)) ?>"
                        alt="<?= esc($defender['species_name']) ?>" id="enemy-pet-img"
                        onerror="this.src='<?= base_url('assets/placeholder.png') ?>'">
                </div>
            </div>


            <!-- Attacker (Player) - Bottom -->
            <div class="combatant player-side">
                <div class="pet-sprite player-sprite">
                    <div class="pet-shadow"></div>
                    <img src="<?= base_url('assets/pets/' . esc($attackerImg)) ?>"
                        alt="<?= esc($attacker['species_name']) ?>" id="player-pet-img"
                        onerror="this.src='<?= base_url('assets/placeholder.png') ?>'">
                </div>
                <div class="combat-hud">
                    <div class="hp-bar-container">
                        <div class="hp-bar player-hp" id="player-hp-bar" style="width: 100%"></div>
                        <span class="hp-text" id="player-hp-text">
                            <?= $attackerMaxHp ?> / <?= $attackerMaxHp ?>
                        </span>
                    </div>
                    <div class="pet-info">
                        <span class="pet-name">
                            <?= esc($attacker['nickname'] ?? $attacker['species_name']) ?>
                        </span>
                        <div class="pet-meta">
                            <span class="pet-level">Lv. <?= $attacker['level'] ?></span>
                            <span class="element-badge <?= strtolower($attacker['element']) ?>">
                                <?= $attacker['element'] ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Battle Log -->
        <section class="battle-log" id="battle-log" aria-live="polite">
            <div class="log-entry">Battle Start! Choose your attack!</div>
        </section>

        <!-- Skill Buttons -->
        <nav class="skills-panel" id="skills-panel">
            <?php if (count($attackerSkills) > 0): ?>
                <?php foreach ($attackerSkills as $index => $skill): ?>
                    <button
                        class="skill-btn <?= strtolower($skill['skill_element']) ?> <?= $skill['is_special'] ? 'special' : '' ?>"
                        data-skill-id="<?= $skill['id'] ?>" data-damage="<?= $skill['base_damage'] ?>"
                        data-element="<?= $skill['skill_element'] ?>"
                        onclick="useSkill(<?= $skill['id'] ?>, <?= $skill['base_damage'] ?>, '<?= $skill['skill_element'] ?>')">
                        <span class="skill-name">
                            <?= esc($skill['skill_name']) ?>
                        </span>
                        <span class="skill-damage">
                            <i class="fas fa-bolt"></i>
                            <?= $skill['base_damage'] ?>
                        </span>
                        <span class="skill-element element-icon <?= $skill['skill_element'] ?>"></span>
                    </button>
                <?php endforeach; ?>
            <?php else: ?>
                <button class="skill-btn" onclick="useSkill(0, 25, '<?= strtolower($attacker['element']) ?>')">
                    <span class="skill-name">Basic Attack</span>
                    <span class="skill-damage"><i class="fas fa-bolt"></i> 25</span>
                </button>
                <button class="skill-btn" onclick="useSkill(0, 40, '<?= strtolower($attacker['element']) ?>')">
                    <span class="skill-name">Power Strike</span>
                    <span class="skill-damage"><i class="fas fa-bolt"></i> 40</span>
                </button>
                <button class="skill-btn special" onclick="useSkill(0, 60, '<?= strtolower($attacker['element']) ?>')">
                    <span class="skill-name">Special Attack</span>
                    <span class="skill-damage"><i class="fas fa-bolt"></i> 60</span>
                </button>
                <button class="skill-btn special" onclick="useSkill(0, 80, '<?= strtolower($attacker['element']) ?>')">
                    <span class="skill-name">Ultimate</span>
                    <span class="skill-damage"><i class="fas fa-bolt"></i> 80</span>
                </button>
            <?php endif; ?>
        </nav>

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
                    <div class="result-row" id="rp-reward-row">
                        <span>Rank Points</span>
                        <span id="reward-rp">+0</span>
                    </div>
                </div>
                <button class="return-btn" onclick="returnToArena()">
                    <i class="fas fa-trophy"></i> Return to Arena
                </button>
            </div>
        </div>
    </div>

    <!-- Battle Config (data-attributes for external JS) -->
    <div id="battle-config" style="display:none" data-attacker-pet-id="<?= $attackerPetId ?>"
        data-defender-pet-id="<?= $defenderPetId ?>" data-attacker-element="<?= strtolower($attacker['element']) ?>"
        data-defender-element="<?= strtolower($defender['element']) ?>" data-attacker-max-hp="<?= $attackerMaxHp ?>"
        data-defender-max-hp="<?= $defenderMaxHp ?>" data-attacker-level="<?= $attacker['level'] ?>"
        data-defender-level="<?= $defender['level'] ?>" data-attacker-base-atk="<?= $attackerBattleAtk ?>"
        data-defender-base-atk="<?= $defenderBattleAtk ?>" data-attacker-base-def="<?= $attackerBattleDef ?>"
        data-defender-base-def="<?= $defenderBattleDef ?>" data-attacker-rarity="<?= $attacker['rarity'] ?? 'Common' ?>"
        data-defender-rarity="<?= $defender['rarity'] ?? 'Common' ?>"
        data-attacker-evolution="<?= $attacker['evolution_stage'] ?? 'egg' ?>"
        data-defender-evolution="<?= $defender['evolution_stage'] ?? 'egg' ?>"
        data-defender-skills='<?= json_encode($defenderSkills) ?>' data-csrf-token="<?= csrf_hash() ?>"
        data-csrf-header="<?= csrf_header() ?>" data-api-base="<?= base_url('api/') ?>"
        data-asset-base="<?= base_url() ?>"></div>

    <!-- Battle JS -->
    <script src="<?= base_url('js/shared/sound_manager.js') ?>"></script>
    <script src="<?= base_url('js/shared/csrf_helper.js') ?>"></script>
    <script src="<?= base_url('js/battle/battle_arena.js') ?>?v=2"></script>
    <script src="<?= base_url('js/battle/pixi_battle.js') ?>"></script>
</body>

</html>