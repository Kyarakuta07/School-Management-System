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

// ================================================
// BATTLE STATE
// ================================================
const BattleState = {
    playerHp: BATTLE_CONFIG.attackerMaxHp,
    enemyHp: BATTLE_CONFIG.defenderMaxHp,
    isPlayerTurn: true,
    isBattleOver: false,
    turnCount: 0
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
// SKILL USAGE
// ================================================
function useSkill(skillId, baseDamage, skillElement) {
    if (BattleState.isBattleOver || !BattleState.isPlayerTurn) return;

    // Disable skills during animation
    disableSkills(true);
    BattleState.isPlayerTurn = false;
    BattleState.turnCount++;

    // Calculate damage
    const multiplier = getElementalMultiplier(skillElement, BATTLE_CONFIG.defenderElement);
    const levelBonus = 1 + (BATTLE_CONFIG.attackerLevel * 0.02);
    const defenseReduction = 1 - (BATTLE_CONFIG.defenderBaseDef * 0.005);
    const critChance = Math.random() < 0.15;
    const critMultiplier = critChance ? 1.5 : 1;

    let damage = Math.floor(baseDamage * multiplier * levelBonus * defenseReduction * critMultiplier);
    damage = Math.max(1, damage); // Minimum 1 damage

    // Play attack animation
    DOM.playerSprite.classList.add('attacking');
    SoundManager.attack();

    // Show projectile flying to enemy
    showProjectile(DOM.playerSprite, DOM.enemySprite, skillElement);

    setTimeout(() => {
        DOM.playerSprite.classList.remove('attacking');
        DOM.enemySprite.classList.add('hit');

        // Apply damage
        BattleState.enemyHp = Math.max(0, BattleState.enemyHp - damage);
        updateHpDisplay();

        // Show damage popup and sparks
        showDamagePopup(DOM.enemySprite, damage, multiplier, critChance);
        showDamageSparks(DOM.enemySprite, critChance);
        if (critChance) {
            SoundManager.critical();
        } else {
            SoundManager.damage();
        }

        // Log the attack
        let logClass = 'player-action';
        let logText = `You dealt ${damage} damage!`;
        if (multiplier > 1) {
            logClass += ' effective';
            logText += ' Super effective!';
        } else if (multiplier < 1) {
            logClass += ' weak';
            logText += ' Not very effective...';
        }
        if (critChance) {
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
}

// ================================================
// ENEMY AI (SMART)
// ================================================
function enemyTurn() {
    if (BattleState.isBattleOver) return;

    DOM.turnIndicator.textContent = "ENEMY TURN";
    DOM.turnIndicator.classList.add('enemy-turn');

    setTimeout(() => {
        // Smart AI: Pick the best skill
        let bestSkill = null;
        let bestDamage = 0;

        const defenderSkills = BATTLE_CONFIG.defenderSkills;

        if (defenderSkills && defenderSkills.length > 0) {
            defenderSkills.forEach(skill => {
                const multiplier = getElementalMultiplier(skill.skill_element, BATTLE_CONFIG.attackerElement);
                const potentialDamage = skill.base_damage * multiplier;

                if (potentialDamage > bestDamage) {
                    bestDamage = potentialDamage;
                    bestSkill = skill;
                }
            });
        }

        // Fallback if no skills
        if (!bestSkill) {
            bestSkill = {
                skill_name: 'Attack',
                base_damage: 25 + (BATTLE_CONFIG.defenderLevel * 2),
                skill_element: BATTLE_CONFIG.defenderElement
            };
        }

        // Calculate actual damage
        const multiplier = getElementalMultiplier(bestSkill.skill_element, BATTLE_CONFIG.attackerElement);
        const levelBonus = 1 + (BATTLE_CONFIG.defenderLevel * 0.02);
        const defenseReduction = 1 - (BATTLE_CONFIG.attackerBaseDef * 0.005);
        const critChance = Math.random() < 0.1;
        const critMultiplier = critChance ? 1.5 : 1;

        let damage = Math.floor(bestSkill.base_damage * multiplier * levelBonus * defenseReduction * critMultiplier);
        damage = Math.max(1, damage);

        // Play attack animation
        DOM.enemySprite.classList.add('attacking');
        SoundManager.attack();

        // Show projectile flying to player
        showProjectile(DOM.enemySprite, DOM.playerSprite, bestSkill.skill_element || BATTLE_CONFIG.defenderElement);

        setTimeout(() => {
            DOM.enemySprite.classList.remove('attacking');
            DOM.playerSprite.classList.add('hit');

            // Apply damage
            BattleState.playerHp = Math.max(0, BattleState.playerHp - damage);
            updateHpDisplay();

            // Show damage popup and sparks
            showDamagePopup(DOM.playerSprite, damage, multiplier, critChance);
            showDamageSparks(DOM.playerSprite, critChance);
            if (critChance) {
                SoundManager.critical();
            } else {
                SoundManager.damage();
            }

            // Log the attack
            let logClass = 'enemy-action';
            let logText = `Enemy used ${bestSkill.skill_name}! ${damage} damage!`;
            if (multiplier > 1) logText += ' Super effective!';
            if (critChance) logText = `CRITICAL! ${logText}`;
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
    }, 1000);
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
