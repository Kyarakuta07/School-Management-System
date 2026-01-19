/**
 * MOE Pet System - Battle Arena Engine
 * Dragon City-style turn-based combat with elemental advantages
 */

// ================================================
// ELEMENTAL SYSTEM
// ================================================
const ELEMENT_CHART = {
    fire: { strongAgainst: 'plant', weakAgainst: 'water' },
    water: { strongAgainst: 'fire', weakAgainst: 'plant' },
    plant: { strongAgainst: 'water', weakAgainst: 'fire' },
    dark: { strongAgainst: 'light', weakAgainst: 'light' },
    light: { strongAgainst: 'dark', weakAgainst: 'dark' },
    air: { strongAgainst: null, weakAgainst: null }
};

function getElementalMultiplier(attackerElement, defenderElement) {
    const chart = ELEMENT_CHART[attackerElement];
    if (!chart) return 1.0;

    if (chart.strongAgainst === defenderElement) {
        return 1.5; // Super effective
    } else if (chart.weakAgainst === defenderElement) {
        return 0.7; // Not very effective
    }
    return 1.0; // Normal
}

/**
 * Get damage bonus based on pet rarity
 * @param {string} rarity - Common, Rare, Epic, or Legendary
 * @returns {number} Bonus multiplier (0.0 to 0.25)
 */
function getRarityBonus(rarity) {
    const bonuses = {
        'common': 0.0,
        'rare': 0.05,
        'epic': 0.12,
        'legendary': 0.25
    };
    return bonuses[(rarity || 'common').toLowerCase()] || 0.0;
}

// ================================================
// STATUS EFFECTS SYSTEM
// ================================================
const STATUS_CONFIG = {
    burn: { icon: '🔥', name: 'Burn', damagePercent: 5, preventsAction: false },
    poison: { icon: '☠️', name: 'Poison', damagePercent: 3, preventsAction: false },
    freeze: { icon: '❄️', name: 'Freeze', damagePercent: 0, preventsAction: true },
    stun: { icon: '⚡', name: 'Stun', damagePercent: 0, preventsAction: true },
    atk_down: { icon: '🔻', name: 'ATK Down', damagePercent: 0, preventsAction: false },
    def_down: { icon: '🛡️', name: 'DEF Down', damagePercent: 0, preventsAction: false }
};

/**
 * Process turn-start effects for a target
 * @param {string} target - 'player' or 'enemy'
 * @returns {Object} { damage: number, logs: string[], canAct: boolean }
 */
function processTurnStartEffects(target) {
    const effects = target === 'player' ? BattleState.playerEffects : BattleState.enemyEffects;
    const maxHp = target === 'player' ? BATTLE_CONFIG.attackerMaxHp : BATTLE_CONFIG.defenderMaxHp;
    const targetName = target === 'player' ? 'Your pet' : 'Enemy';

    let totalDamage = 0;
    let logs = [];
    let canAct = true;
    let blockReason = null;

    // Process each effect
    for (let i = effects.length - 1; i >= 0; i--) {
        const effect = effects[i];
        const config = STATUS_CONFIG[effect.type];

        if (!config) continue;

        // Check if prevents action
        if (config.preventsAction) {
            canAct = false;
            blockReason = config.name;
            logs.push(`${config.icon} ${targetName} is ${config.name} and cannot act!`);
        }

        // Apply DOT damage
        if (config.damagePercent > 0) {
            const dotDamage = Math.ceil(maxHp * (config.damagePercent / 100));
            totalDamage += dotDamage;
            logs.push(`${config.icon} ${targetName} took ${dotDamage} ${config.name} damage!`);
        }

        // Decrement turns
        effect.turns_left--;

        // Remove expired effects
        if (effect.turns_left <= 0) {
            logs.push(`${config.icon} ${targetName} is no longer ${config.name}!`);
            effects.splice(i, 1);
        }
    }

    // Update effects display
    updateStatusEffectsDisplay();

    return { damage: totalDamage, logs, canAct, blockReason };
}

/**
 * Add a status effect to target
 * @param {string} target - 'player' or 'enemy'
 * @param {Object} statusData - { type, turns_left, icon, name }
 */
function addStatusEffect(target, statusData) {
    if (!statusData || !statusData.type) return;

    const effects = target === 'player' ? BattleState.playerEffects : BattleState.enemyEffects;

    // Check if already has this effect
    const existing = effects.find(e => e.type === statusData.type);
    if (existing) {
        // Refresh duration if new is longer
        if (statusData.turns_left > existing.turns_left) {
            existing.turns_left = statusData.turns_left;
        }
        return;
    }

    // Add new effect
    effects.push({
        type: statusData.type,
        turns_left: statusData.turns_left,
        icon: statusData.icon || STATUS_CONFIG[statusData.type]?.icon || '❓',
        name: statusData.name || STATUS_CONFIG[statusData.type]?.name || 'Unknown'
    });

    // Update display
    updateStatusEffectsDisplay();
}

/**
 * Update status effect icons on battle UI
 */
function updateStatusEffectsDisplay() {
    // Update player effects
    let playerEffectsHtml = BattleState.playerEffects.map(e =>
        `<span class="status-icon" title="${e.name} (${e.turns_left} turns)">${e.icon}</span>`
    ).join('');

    // Update enemy effects
    let enemyEffectsHtml = BattleState.enemyEffects.map(e =>
        `<span class="status-icon" title="${e.name} (${e.turns_left} turns)">${e.icon}</span>`
    ).join('');

    // Find or create status containers
    let playerStatusEl = document.getElementById('player-status-effects');
    let enemyStatusEl = document.getElementById('enemy-status-effects');

    if (!playerStatusEl) {
        const playerSprite = document.querySelector('.player-sprite');
        if (playerSprite) {
            playerStatusEl = document.createElement('div');
            playerStatusEl.id = 'player-status-effects';
            playerStatusEl.className = 'status-effects-container';
            playerSprite.parentElement.appendChild(playerStatusEl);
        }
    }

    if (!enemyStatusEl) {
        const enemySprite = document.querySelector('.enemy-sprite');
        if (enemySprite) {
            enemyStatusEl = document.createElement('div');
            enemyStatusEl.id = 'enemy-status-effects';
            enemyStatusEl.className = 'status-effects-container';
            enemySprite.parentElement.appendChild(enemyStatusEl);
        }
    }

    if (playerStatusEl) playerStatusEl.innerHTML = playerEffectsHtml;
    if (enemyStatusEl) enemyStatusEl.innerHTML = enemyEffectsHtml;
}
// ================================================
// BATTLE STATE
// ================================================
const BattleState = {
    playerHp: BATTLE_CONFIG.attackerMaxHp,
    enemyHp: BATTLE_CONFIG.defenderMaxHp,
    isPlayerTurn: true,
    isBattleOver: false,
    turnCount: 0,
    playerEffects: [],  // Active status effects on player
    enemyEffects: []    // Active status effects on enemy
};

// ================================================
// DOM ELEMENTS
// ================================================
const DOM = {};

document.addEventListener('DOMContentLoaded', () => {
    DOM.playerHpBar = document.getElementById('player-hp-bar');
    DOM.playerHpText = document.getElementById('player-hp-text');
    DOM.enemyHpBar = document.getElementById('enemy-hp-bar');
    DOM.enemyHpText = document.getElementById('enemy-hp-text');
    DOM.battleLog = document.getElementById('battle-log');
    DOM.turnIndicator = document.getElementById('turn-indicator');
    DOM.skillsPanel = document.getElementById('skills-panel');
    DOM.resultOverlay = document.getElementById('result-overlay');
    DOM.playerSprite = document.querySelector('.player-sprite');
    DOM.enemySprite = document.querySelector('.enemy-sprite');

    updateHpDisplay();
});

// ================================================
// SKILL USAGE (SERVER-SIDE for anti-cheat security)
// All damage calculation is done on the server
// ================================================
async function useSkill(skillId, baseDamage, skillElement) {
    if (BattleState.isBattleOver || !BattleState.isPlayerTurn) return;

    // Disable skills during animation
    disableSkills(true);
    BattleState.isPlayerTurn = false;
    BattleState.turnCount++;

    // Play attack animation immediately for responsiveness
    DOM.playerSprite.classList.add('attacking');
    SoundManager.attack();
    showProjectile(DOM.playerSprite, DOM.enemySprite, skillElement);

    try {
        // Call server-side API for damage calculation
        const response = await fetch(`${API_BASE}?action=attack_1v1`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                skill_id: skillId,
                attacker_pet_id: BATTLE_CONFIG.attackerPetId,
                defender_pet_id: BATTLE_CONFIG.defenderPetId
            })
        });

        const data = await response.json();

        if (!data.success) {
            console.error('Attack failed:', data.error);
            addBattleLog('Attack failed: ' + (data.error || 'Unknown error'), 'error');
            BattleState.isPlayerTurn = true;
            disableSkills(false);
            DOM.playerSprite.classList.remove('attacking');
            return;
        }

        // Process server response
        const damage = data.damage_dealt;
        const isDodge = data.is_dodge;
        const isCritical = data.is_critical;
        const isGlancing = data.is_glancing;
        const isLucky = data.is_lucky;
        const elemAdvantage = data.element_advantage;
        const skillName = data.skill_name;
        const statusApplied = data.status_applied;

        // If status effect was applied to enemy, add it
        if (statusApplied && statusApplied.applied) {
            addStatusEffect('enemy', statusApplied);
        }

        setTimeout(() => {
            DOM.playerSprite.classList.remove('attacking');

            // Handle DODGE
            if (isDodge) {
                DOM.enemySprite.classList.add('dodge');
                addBattleLog('💨 Enemy dodged the attack!', 'dodge');

                setTimeout(() => {
                    DOM.enemySprite.classList.remove('dodge');
                    enemyTurn();
                }, 600);
                return;
            }

            // Apply damage
            DOM.enemySprite.classList.add('hit');
            BattleState.enemyHp = Math.max(0, BattleState.enemyHp - damage);
            updateHpDisplay();

            // Calculate elemMultiplier for visual effects
            const elemMultiplier = elemAdvantage === 'super_effective' ? 1.5 :
                elemAdvantage === 'not_effective' ? 0.5 : 1.0;

            // Show damage popup and sparks
            showDamagePopup(DOM.enemySprite, damage, elemMultiplier, isCritical);
            showDamageSparks(DOM.enemySprite, isCritical || isLucky);

            if (isCritical) {
                SoundManager.critical();
            } else {
                SoundManager.damage();
            }

            // Build log message with variance info
            let logClass = 'player-action';
            let logText = `You dealt ${damage} damage!`;

            if (isGlancing) {
                logClass += ' glancing';
                logText = `⚡ Glancing blow! ${logText}`;
            } else if (isLucky) {
                logClass += ' lucky';
                logText = `🍀 Lucky hit! ${logText}`;
            }

            if (elemAdvantage === 'super_effective') {
                logClass += ' effective';
                logText += ' Super effective!';
            } else if (elemAdvantage === 'not_effective') {
                logClass += ' weak';
                logText += ' Not very effective...';
            }

            if (isCritical) {
                logClass += ' critical';
                logText = `CRITICAL HIT! ${logText}`;
            }
            addBattleLog(logText, logClass);

            setTimeout(() => {
                DOM.enemySprite.classList.remove('hit');

                // Check if enemy defeated
                if (BattleState.enemyHp <= 0) {
                    endBattle(true);
                } else {
                    // Enemy turn
                    enemyTurn();
                }
            }, 400);
        }, 300);

    } catch (error) {
        console.error('Attack error:', error);
        addBattleLog('Network error during attack', 'error');
        BattleState.isPlayerTurn = true;
        disableSkills(false);
        DOM.playerSprite.classList.remove('attacking');
    }
}

// ================================================
// ENEMY AI (SERVER-SIDE for anti-cheat security)
// Smart AI skill selection is now done on the server
// ================================================
async function enemyTurn() {
    if (BattleState.isBattleOver) return;

    DOM.turnIndicator.textContent = "ENEMY TURN";
    DOM.turnIndicator.classList.add('enemy-turn');

    // Add delay for better UX
    await new Promise(resolve => setTimeout(resolve, 800));

    try {
        // Call server-side API for enemy's attack
        const response = await fetch(`${API_BASE}?action=enemy_turn_1v1`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                attacker_pet_id: BATTLE_CONFIG.attackerPetId,
                defender_pet_id: BATTLE_CONFIG.defenderPetId,
                defender_hp: BattleState.enemyHp,
                defender_max_hp: BATTLE_CONFIG.defenderMaxHp
            })
        });

        const data = await response.json();

        if (!data.success) {
            console.error('Enemy turn failed:', data.error);
            addBattleLog('Enemy turn failed: ' + (data.error || 'Unknown error'), 'error');
            // Give player turn back
            BattleState.isPlayerTurn = true;
            DOM.turnIndicator.textContent = "YOUR TURN";
            DOM.turnIndicator.classList.remove('enemy-turn');
            disableSkills(false);
            return;
        }

        // Process server response
        const damage = data.damage_dealt;
        const isDodge = data.is_dodge;
        const isCritical = data.is_critical;
        const isGlancing = data.is_glancing;
        const isLucky = data.is_lucky;
        const elemAdvantage = data.element_advantage;
        const skillName = data.skill_name;
        const skillElement = data.skill_element || BATTLE_CONFIG.defenderElement;

        // Play attack animation
        DOM.enemySprite.classList.add('attacking');
        SoundManager.attack();
        showProjectile(DOM.enemySprite, DOM.playerSprite, skillElement);

        setTimeout(() => {
            DOM.enemySprite.classList.remove('attacking');

            // Handle DODGE
            if (isDodge) {
                DOM.playerSprite.classList.add('dodge');
                addBattleLog('💨 You dodged the enemy attack!', 'player-dodge');

                setTimeout(() => {
                    DOM.playerSprite.classList.remove('dodge');
                    // Player turn
                    BattleState.isPlayerTurn = true;
                    DOM.turnIndicator.textContent = "YOUR TURN";
                    DOM.turnIndicator.classList.remove('enemy-turn');
                    disableSkills(false);
                }, 600);
                return;
            }

            // Apply damage
            DOM.playerSprite.classList.add('hit');
            BattleState.playerHp = Math.max(0, BattleState.playerHp - damage);
            updateHpDisplay();

            // Calculate elemMultiplier for visual effects
            const elemMultiplier = elemAdvantage === 'super_effective' ? 1.5 :
                elemAdvantage === 'not_effective' ? 0.5 : 1.0;

            // Show damage popup and sparks
            showDamagePopup(DOM.playerSprite, damage, elemMultiplier, isCritical);
            showDamageSparks(DOM.playerSprite, isCritical || isLucky);

            if (isCritical) {
                SoundManager.critical();
            } else {
                SoundManager.damage();
            }

            // Build log message with variance info
            let logClass = 'enemy-action';
            let logText = `Enemy used ${skillName}! ${damage} damage!`;

            if (isGlancing) logText = `⚡ Glancing! ${logText}`;
            else if (isLucky) logText = `🍀 Lucky! ${logText}`;

            if (elemAdvantage === 'super_effective') logText += ' Super effective!';
            if (isCritical) logText = `CRITICAL! ${logText}`;
            addBattleLog(logText, logClass);

            setTimeout(() => {
                DOM.playerSprite.classList.remove('hit');

                // Check if player defeated
                if (BattleState.playerHp <= 0) {
                    endBattle(false);
                } else {
                    // Player turn
                    BattleState.isPlayerTurn = true;
                    DOM.turnIndicator.textContent = "YOUR TURN";
                    DOM.turnIndicator.classList.remove('enemy-turn');
                    disableSkills(false);
                }
            }, 400);
        }, 300);

    } catch (error) {
        console.error('Enemy turn error:', error);
        addBattleLog('Network error during enemy turn', 'error');
        // Give player turn back
        BattleState.isPlayerTurn = true;
        DOM.turnIndicator.textContent = "YOUR TURN";
        DOM.turnIndicator.classList.remove('enemy-turn');
        disableSkills(false);
    }
}

// ================================================
// UI UPDATES
// ================================================
function updateHpDisplay() {
    // Player HP
    const playerPercent = (BattleState.playerHp / BATTLE_CONFIG.attackerMaxHp) * 100;
    DOM.playerHpBar.style.width = playerPercent + '%';
    DOM.playerHpText.textContent = `${BattleState.playerHp} / ${BATTLE_CONFIG.attackerMaxHp}`;

    if (playerPercent < 30) {
        DOM.playerHpBar.classList.add('low');
    }

    // Enemy HP
    const enemyPercent = (BattleState.enemyHp / BATTLE_CONFIG.defenderMaxHp) * 100;
    DOM.enemyHpBar.style.width = enemyPercent + '%';
    DOM.enemyHpText.textContent = `${BattleState.enemyHp} / ${BATTLE_CONFIG.defenderMaxHp}`;

    if (enemyPercent < 30) {
        DOM.enemyHpBar.classList.add('low');
    }
}

function showDamagePopup(targetElement, damage, multiplier, isCrit) {
    const popup = document.createElement('div');
    popup.className = 'damage-popup';

    if (isCrit) {
        popup.classList.add('critical');
    } else if (multiplier > 1) {
        popup.classList.add('effective');
    } else if (multiplier < 1) {
        popup.classList.add('weak');
    }

    popup.textContent = `-${damage}`;

    const rect = targetElement.getBoundingClientRect();
    popup.style.left = (rect.left + rect.width / 2) + 'px';
    popup.style.top = (rect.top + 20) + 'px';

    document.body.appendChild(popup);

    setTimeout(() => popup.remove(), 1000);
}

function addBattleLog(message, className = '') {
    const entry = document.createElement('div');
    entry.className = 'log-entry ' + className;
    entry.textContent = message;
    DOM.battleLog.appendChild(entry);
    DOM.battleLog.scrollTop = DOM.battleLog.scrollHeight;
}

function disableSkills(disabled) {
    const buttons = DOM.skillsPanel.querySelectorAll('.skill-btn');
    buttons.forEach(btn => btn.disabled = disabled);
}

// ================================================
// BATTLE END
// ================================================
function endBattle(playerWon) {
    BattleState.isBattleOver = true;
    disableSkills(true);

    // Calculate rewards
    let goldReward = 0;
    let expReward = 0;

    if (playerWon) {
        // HARDCORE ECONOMY: Minimal gold rewards (was 20-50 base)
        goldReward = 2 + Math.floor(Math.random() * 6) + Math.floor(BATTLE_CONFIG.defenderLevel * 0.2);
        expReward = 30 + Math.floor(Math.random() * 30) + (BATTLE_CONFIG.defenderLevel * 3);
    }

    // Submit result to backend
    submitBattleResult(playerWon, goldReward, expReward);

    // Show result after delay
    setTimeout(() => {
        const resultTitle = document.getElementById('result-title');
        if (playerWon) {
            resultTitle.textContent = '🏆 Victory!';
            resultTitle.className = 'victory';
            SoundManager.victory();
            showVictoryConfetti(); // Premium confetti celebration!
        } else {
            resultTitle.textContent = '💀 Defeat...';
            resultTitle.className = 'defeat';
            SoundManager.defeat();
        }

        document.getElementById('reward-gold').textContent = `+${goldReward}`;
        document.getElementById('reward-exp').textContent = `+${expReward}`;

        DOM.resultOverlay.classList.remove('hidden');
    }, 1000);
}

async function submitBattleResult(playerWon, gold, exp) {
    try {
        await fetch(`${API_BASE}?action=battle_result`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                attacker_pet_id: BATTLE_CONFIG.attackerPetId,
                defender_pet_id: BATTLE_CONFIG.defenderPetId,
                winner: playerWon ? 'attacker' : 'defender',
                gold_reward: gold,
                exp_reward: exp
            })
        });
    } catch (error) {
        console.error('Error submitting battle result:', error);
    }
}

// ================================================
// NAVIGATION
// ================================================
function forfeitBattle() {
    if (BattleState.isBattleOver) {
        returnToArena();
        return;
    }

    if (confirm('Forfeit this battle? You will lose!')) {
        endBattle(false);
    }
}

function returnToArena() {
    window.location.href = 'pet.php?tab=arena';
}

// ================================================
// PROJECTILE ANIMATION
// ================================================
/**
 * Show projectile animation from attacker to defender
 * @param {Element} fromEl - Attacker sprite element
 * @param {Element} toEl - Defender sprite element
 * @param {string} element - Element type for projectile color
 */
function showProjectile(fromEl, toEl, element = 'fire') {
    const projectile = document.createElement('div');
    projectile.className = `projectile ${element.toLowerCase()}`;

    const fromRect = fromEl.getBoundingClientRect();
    const toRect = toEl.getBoundingClientRect();

    // Start position (center of attacker)
    const startX = fromRect.left + fromRect.width / 2 - 10;
    const startY = fromRect.top + fromRect.height / 2 - 10;

    // End position (center of defender)
    const endX = toRect.left + toRect.width / 2 - 10;
    const endY = toRect.top + toRect.height / 2 - 10;

    projectile.style.left = startX + 'px';
    projectile.style.top = startY + 'px';
    document.body.appendChild(projectile);

    // Animate to target
    requestAnimationFrame(() => {
        projectile.style.transition = 'all 0.35s ease-out';
        projectile.style.left = endX + 'px';
        projectile.style.top = endY + 'px';
        projectile.style.transform = 'scale(1.3)';
    });

    // Remove after animation with explosion effect
    setTimeout(() => {
        projectile.style.opacity = '0';
        projectile.style.transform = 'scale(2.5)';
        projectile.style.boxShadow = '0 0 50px currentColor, 0 0 80px currentColor';
        setTimeout(() => projectile.remove(), 200);
    }, 350);
}

// ================================================
// SCREEN SHAKE EFFECT
// ================================================
function screenShake(intensity = 10, duration = 200) {
    const container = document.querySelector('.battle-container');
    if (!container) return;

    const startTime = Date.now();
    const originalTransform = container.style.transform || '';

    function shake() {
        const elapsed = Date.now() - startTime;
        if (elapsed > duration) {
            container.style.transform = originalTransform;
            return;
        }

        const progress = elapsed / duration;
        const currentIntensity = intensity * (1 - progress);
        const x = (Math.random() - 0.5) * currentIntensity * 2;
        const y = (Math.random() - 0.5) * currentIntensity * 2;

        container.style.transform = `translate(${x}px, ${y}px)`;
        requestAnimationFrame(shake);
    }

    shake();
}

// ================================================
// DAMAGE SPARKS EFFECT
// ================================================
function showDamageSparks(targetEl, isCritical = false) {
    if (!targetEl) return;

    const rect = targetEl.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;

    const sparkCount = isCritical ? 15 : 8;
    const color = isCritical ? '#f1c40f' : '#e74c3c';

    for (let i = 0; i < sparkCount; i++) {
        const spark = document.createElement('div');
        spark.style.cssText = `
            position: fixed;
            width: ${isCritical ? 8 : 5}px;
            height: ${isCritical ? 8 : 5}px;
            background: ${color};
            border-radius: 50%;
            pointer-events: none;
            z-index: 1000;
            left: ${centerX}px;
            top: ${centerY}px;
            box-shadow: 0 0 10px ${color};
        `;
        document.body.appendChild(spark);

        const angle = (i / sparkCount) * Math.PI * 2;
        const distance = 30 + Math.random() * (isCritical ? 50 : 30);
        const targetX = centerX + Math.cos(angle) * distance;
        const targetY = centerY + Math.sin(angle) * distance;

        requestAnimationFrame(() => {
            spark.style.transition = 'all 0.3s ease-out';
            spark.style.left = targetX + 'px';
            spark.style.top = targetY + 'px';
            spark.style.opacity = '0';
            spark.style.transform = 'scale(0)';
        });

        setTimeout(() => spark.remove(), 350);
    }

    // Screen shake
    if (isCritical) {
        screenShake(12, 250);
    } else {
        screenShake(5, 150);
    }
}

// ================================================
// VICTORY CONFETTI EFFECT
// ================================================
function showVictoryConfetti() {
    const colors = ['#DAA520', '#f1c40f', '#ffffff', '#27ae60', '#3498db'];

    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            const color = colors[Math.floor(Math.random() * colors.length)];
            const size = 6 + Math.random() * 8;

            confetti.style.cssText = `
                position: fixed;
                width: ${size}px;
                height: ${size * 0.6}px;
                background: ${color};
                left: ${Math.random() * window.innerWidth}px;
                top: -10px;
                pointer-events: none;
                z-index: 1000;
                border-radius: 2px;
            `;
            document.body.appendChild(confetti);

            const targetY = window.innerHeight + 20;
            const targetX = parseFloat(confetti.style.left) + (Math.random() - 0.5) * 100;

            requestAnimationFrame(() => {
                confetti.style.transition = 'all 2s ease-out';
                confetti.style.top = targetY + 'px';
                confetti.style.left = targetX + 'px';
                confetti.style.transform = `rotate(${Math.random() * 720}deg)`;
                confetti.style.opacity = '0';
            });

            setTimeout(() => confetti.remove(), 2500);
        }, i * 30);
    }
}
