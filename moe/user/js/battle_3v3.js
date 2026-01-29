/**
 * MOE Pet System - Battle 3v3 Engine
 * Dragon City-style turn-based 3v3 combat
 * 
 * All damage calculation is SERVER-SIDE for security.
 * This file only handles UI, animations, and API calls.
 */

// ================================================
// BATTLE STATE
// ================================================
const BattleState = {
    battleId: BATTLE_ID,
    playerPets: [],
    enemyPets: [],
    playerSkills: [],
    activePlayerIndex: 0,
    activeEnemyIndex: 0,
    currentTurn: 'player',
    turnCount: 1,
    status: 'active',
    isAnimating: false
};

/**
 * Get correct pet image based on evolution stage
 * @param {Object} pet - Pet object with evolution_stage and img_* properties
 * @returns {string} Image path
 */
function getBattlePetImage(pet) {
    const stage = pet.evolution_stage || 'adult'; // Default to adult for battle display

    // Try to get image for current stage, fallback to available ones
    let imgKeys;
    switch (stage) {
        case 'egg':
            imgKeys = ['img_egg', 'img_baby', 'img_adult'];
            break;
        case 'baby':
            imgKeys = ['img_baby', 'img_adult', 'img_egg'];
            break;
        case 'adult':
        default:
            imgKeys = ['img_adult', 'img_baby', 'img_egg'];
            break;
    }

    for (const key of imgKeys) {
        if (pet[key] && pet[key] !== '' && pet[key] !== null) {
            return `../assets/pets/${pet[key]}`;
        }
    }

    // Fallback to placeholder
    return '../assets/placeholder.png';
}

// ================================================
// DOM ELEMENTS
// ================================================
const DOM = {};

document.addEventListener('DOMContentLoaded', () => {
    // Initialize DOM references
    DOM.turnIndicator = document.getElementById('turn-indicator');
    DOM.turnCount = document.getElementById('turn-count');
    DOM.playerIndicators = document.getElementById('player-indicators');
    DOM.enemyIndicators = document.getElementById('enemy-indicators');
    DOM.playerName = document.getElementById('player-name');
    DOM.playerElement = document.getElementById('player-element');
    DOM.playerHpBar = document.getElementById('player-hp-bar');
    DOM.playerHpText = document.getElementById('player-hp-text');
    DOM.playerImg = document.getElementById('player-img');
    DOM.playerSprite = document.getElementById('player-sprite');
    DOM.enemyName = document.getElementById('enemy-name');
    DOM.enemyElement = document.getElementById('enemy-element');
    DOM.enemyHpBar = document.getElementById('enemy-hp-bar');
    DOM.enemyHpText = document.getElementById('enemy-hp-text');
    DOM.enemyImg = document.getElementById('enemy-img');
    DOM.enemySprite = document.getElementById('enemy-sprite');
    DOM.battleLog = document.getElementById('battle-log');
    DOM.skillsGrid = document.getElementById('skills-grid');
    DOM.swapBtn = document.getElementById('swap-btn');
    DOM.swapModal = document.getElementById('swap-modal');
    DOM.swapPets = document.getElementById('swap-pets');
    DOM.resultOverlay = document.getElementById('result-overlay');

    // Desktop panel elements (for sync)
    DOM.panelPlayerName = document.getElementById('panel-player-name');
    DOM.panelPlayerElement = document.getElementById('panel-player-element');
    DOM.panelPlayerHp = document.getElementById('panel-player-hp');
    DOM.panelPlayerHpText = document.getElementById('panel-player-hp-text');
    DOM.panelPlayerBench = document.getElementById('panel-player-bench');
    DOM.panelEnemyName = document.getElementById('panel-enemy-name');
    DOM.panelEnemyElement = document.getElementById('panel-enemy-element');
    DOM.panelEnemyHp = document.getElementById('panel-enemy-hp');
    DOM.panelEnemyHpText = document.getElementById('panel-enemy-hp-text');
    DOM.panelEnemyBench = document.getElementById('panel-enemy-bench');
    DOM.desktopBattleLog = document.getElementById('desktop-battle-log');

    // Load battle state
    loadBattleState();
});

// ================================================
// API FUNCTIONS
// ================================================
async function loadBattleState() {
    try {
        const response = await fetch(`${API_BASE}?action=battle_state&battle_id=${BattleState.battleId}`);
        const data = await response.json();

        if (!data.success) {
            console.error('Failed to load battle:', data.error);
            alert('Battle not found or expired!');
            window.location.href = 'pet.php?tab=arena3v3';
            return;
        }

        const state = data.battle_state;
        BattleState.playerPets = state.player_pets;
        BattleState.enemyPets = state.enemy_pets;
        BattleState.activePlayerIndex = state.active_player_index;
        BattleState.activeEnemyIndex = state.active_enemy_index;
        BattleState.currentTurn = state.current_turn;
        BattleState.turnCount = state.turn_count;
        BattleState.status = state.status;
        BattleState.opponentName = state.opponent_name || sessionStorage.getItem('opponent_name') || 'Unknown Trainer';

        // Display opponent name
        if (DOM.opponentName) {
            DOM.opponentName.textContent = `VS ${BattleState.opponentName}`;
        }

        // Load player skills
        await loadPlayerSkills();

        // Render UI
        renderBattleUI();

        // Enable controls if it's player's turn
        if (BattleState.currentTurn === 'player' && BattleState.status === 'active') {
            disableControls(false);
        }

        // If it's enemy's turn (page loaded mid-battle), trigger enemy attack
        if (BattleState.currentTurn === 'enemy' && BattleState.status === 'active') {
            await sleep(500);
            await enemyTurn();
        }

    } catch (error) {
        console.error('Error loading battle state:', error);
    }
}

async function loadPlayerSkills() {
    // Skills are loaded with state, but we can also fetch them separately if needed
    // For now, use default skills based on element
    const activePet = BattleState.playerPets[BattleState.activePlayerIndex];
    BattleState.playerSkills = getDefaultSkills(activePet.element);
}

function getDefaultSkills(element) {
    return [
        { id: 1, skill_name: 'Basic Attack', base_damage: 25, skill_element: element },
        { id: 2, skill_name: 'Power Strike', base_damage: 40, skill_element: element },
        { id: 3, skill_name: 'Special Attack', base_damage: 60, skill_element: element },
        { id: 4, skill_name: 'Ultimate', base_damage: 80, skill_element: element }
    ];
}

/**
 * Handle attack - main gameplay function
 * Calls server API for damage calculation (SECURE)
 */
async function handleAttack(skillId) {
    if (BattleState.status !== 'active') return;
    if (BattleState.currentTurn !== 'player') return;
    if (BattleState.isAnimating) return;

    BattleState.isAnimating = true;
    disableControls(true);

    try {
        // Get player's element for projectile
        const playerPet = BattleState.playerPets[BattleState.activePlayerIndex];
        const playerElement = playerPet.element || 'fire';

        // Play attack animation
        DOM.playerSprite.classList.add('attacking');
        if (typeof SoundManager !== 'undefined') SoundManager.attack();

        await sleep(200);

        // Show projectile flying to enemy
        showProjectile(DOM.playerSprite, DOM.enemySprite, playerElement);

        await sleep(200);
        DOM.playerSprite.classList.remove('attacking');

        // Call API (ALL DAMAGE CALCULATED SERVER-SIDE)
        const response = await fetch(`${API_BASE}?action=battle_attack`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                battle_id: BattleState.battleId,
                skill_id: skillId,
                target_index: BattleState.activeEnemyIndex
            })
        });

        const data = await response.json();

        if (!data.success) {
            console.error('Attack failed:', data.error);
            addBattleLog(data.error, 'enemy-action');
            BattleState.isAnimating = false;
            disableControls(false);
            return;
        }

        // Play hit animation
        DOM.enemySprite.classList.add('hit');
        if (data.is_critical && typeof SoundManager !== 'undefined') {
            SoundManager.critical();
        } else if (typeof SoundManager !== 'undefined') {
            SoundManager.damage();
        }

        // PixiJS damage sparks effect
        if (typeof showDamageSparks === 'function') {
            showDamageSparks(false, data.is_critical);
        }

        // Show floating damage
        showFloatingDamage(DOM.enemySprite, data.damage_dealt, data.is_critical, data.element_advantage);

        // Update HP bar with animation
        updateEnemyHp(data.new_enemy_hp, data.is_fainted);

        // Add logs
        data.logs.forEach(log => {
            let logClass = 'player-action';
            if (log.includes('CRITICAL')) logClass += ' critical';
            if (log.includes('super effective')) logClass += ' effective';
            if (log.includes('not very effective')) logClass += ' weak';
            addBattleLog(log, logClass);
        });

        await sleep(400);
        DOM.enemySprite.classList.remove('hit');

        // Update state from server response
        const battleState = data.battle_state;
        BattleState.currentTurn = battleState.current_turn;
        BattleState.turnCount = battleState.turn_count;
        BattleState.status = battleState.status;
        BattleState.activePlayerIndex = battleState.active_player_index;
        BattleState.activeEnemyIndex = battleState.active_enemy_index;
        BattleState.playerPets = battleState.player_pets;
        BattleState.enemyPets = battleState.enemy_pets;

        // Re-render full UI (important after pet switch)
        renderTeamIndicators();
        renderActivePets();
        renderSkills();
        updateTurnDisplay();

        // Check for battle end
        if (BattleState.status === 'victory') {
            endBattle(true);
            return;
        } else if (BattleState.status === 'defeat') {
            endBattle(false);
            return;
        }

        // If it's now enemy's turn, trigger enemy attack
        if (BattleState.currentTurn === 'enemy') {
            await sleep(500);
            await enemyTurn();
        }

    } catch (error) {
        console.error('Attack error:', error);
    } finally {
        BattleState.isAnimating = false;
        if (BattleState.currentTurn === 'player' && BattleState.status === 'active') {
            disableControls(false);
        }
    }
}

/**
 * Enemy AI turn - now uses server-side API for consistency
 */
async function enemyTurn() {
    if (BattleState.status !== 'active') return;
    if (BattleState.currentTurn !== 'enemy') return;

    addBattleLog("Enemy is thinking...", 'enemy-action');
    await sleep(800);

    try {
        // Call server to process enemy turn
        const response = await fetch(`${API_BASE}?action=battle_enemy_turn`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                battle_id: BattleState.battleId
            })
        });

        const data = await response.json();

        if (!data.success) {
            console.error('Enemy turn failed:', data.error);
            addBattleLog(data.error, 'enemy-action');
            return;
        }

        // Get enemy element for projectile
        const enemyPet = BattleState.enemyPets[BattleState.activeEnemyIndex];
        const enemyElement = enemyPet.element || 'fire';

        // Play enemy attack animation
        DOM.enemySprite.classList.add('attacking');
        if (typeof SoundManager !== 'undefined') SoundManager.attack();

        await sleep(200);

        // Show projectile flying to player
        showProjectile(DOM.enemySprite, DOM.playerSprite, enemyElement);

        await sleep(200);
        DOM.enemySprite.classList.remove('attacking');

        // Show damage to player
        DOM.playerSprite.classList.add('hit');
        if (typeof SoundManager !== 'undefined') SoundManager.damage();

        // PixiJS damage sparks effect (player hit)
        if (typeof showDamageSparks === 'function') {
            showDamageSparks(true, false);
        }

        showFloatingDamage(DOM.playerSprite, data.damage_dealt, false, 'neutral');

        // Add logs
        data.logs.forEach(log => {
            addBattleLog(log, 'enemy-action');
        });

        await sleep(400);
        DOM.playerSprite.classList.remove('hit');

        // Update state from server response
        const battleState = data.battle_state;
        BattleState.currentTurn = battleState.current_turn;
        BattleState.turnCount = battleState.turn_count;
        BattleState.status = battleState.status;
        BattleState.activePlayerIndex = battleState.active_player_index;
        BattleState.activeEnemyIndex = battleState.active_enemy_index;
        BattleState.playerPets = battleState.player_pets;
        BattleState.enemyPets = battleState.enemy_pets;

        // Re-render UI
        renderTeamIndicators();
        renderActivePets();
        updateTurnDisplay();

        // Check for battle end
        if (BattleState.status === 'defeat') {
            endBattle(false);
            return;
        }

        // If player pet fainted and needs to swap
        if (data.player_fainted) {
            const hasAlive = BattleState.playerPets.some(p => !p.is_fainted);
            if (hasAlive) {
                openSwapModal();
                return;
            }
        }

        // Back to player's turn
        if (BattleState.currentTurn === 'player') {
            disableControls(false);
        }

    } catch (error) {
        console.error('Enemy turn error:', error);
    }
}

// Note: switchToPet function is defined below in UI HELPERS section
// ================================================
// RENDER FUNCTIONS
// ================================================
function renderBattleUI() {
    renderTeamIndicators();
    renderActivePets();
    renderSkills();
    updateTurnDisplay();
}

function renderTeamIndicators() {
    // Safety check
    if (!BattleState.playerPets || !BattleState.enemyPets) {
        console.error('Missing pets arrays');
        return;
    }

    // Player indicators
    DOM.playerIndicators.innerHTML = BattleState.playerPets.map((pet, i) => {
        if (!pet) return '';
        return `
        <div class="pet-indicator ${i === BattleState.activePlayerIndex ? 'active' : ''} ${pet.is_fainted ? 'fainted' : ''}">
            <img src="${getBattlePetImage(pet)}" alt="${pet.species_name || 'Pet'}" onerror="this.src='../assets/placeholder.png'">
        </div>
    `;
    }).join('');

    // Enemy indicators
    DOM.enemyIndicators.innerHTML = BattleState.enemyPets.map((pet, i) => {
        if (!pet) return '';
        return `
        <div class="pet-indicator ${i === BattleState.activeEnemyIndex ? 'active' : ''} ${pet.is_fainted ? 'fainted' : ''}">
            <img src="${getBattlePetImage(pet)}" alt="${pet.species_name || 'Pet'}" onerror="this.src='../assets/placeholder.png'">
        </div>
    `;
    }).join('');
}

function renderActivePets() {
    const playerPet = BattleState.playerPets[BattleState.activePlayerIndex];
    const enemyPet = BattleState.enemyPets[BattleState.activeEnemyIndex];

    // Safety check
    if (!playerPet || !enemyPet) {
        console.error('Missing pet data:', {
            playerPet, enemyPet,
            playerIndex: BattleState.activePlayerIndex,
            enemyIndex: BattleState.activeEnemyIndex,
            playerPets: BattleState.playerPets,
            enemyPets: BattleState.enemyPets
        });
        return;
    }

    // Player
    DOM.playerName.textContent = playerPet.nickname || playerPet.species_name || 'Unknown';
    DOM.playerElement.textContent = playerPet.element || 'Fire';
    DOM.playerElement.className = `element-badge ${(playerPet.element || 'fire').toLowerCase()}`;
    DOM.playerImg.src = getBattlePetImage(playerPet);
    DOM.playerImg.onerror = function () { this.src = '../assets/placeholder.png'; };
    updatePlayerHp(playerPet.hp || 0, playerPet.is_fainted);

    // Enemy
    DOM.enemyName.textContent = enemyPet.nickname || enemyPet.species_name || 'Unknown';
    DOM.enemyElement.textContent = enemyPet.element || 'Fire';
    DOM.enemyElement.className = `element-badge ${(enemyPet.element || 'fire').toLowerCase()}`;
    DOM.enemyImg.src = getBattlePetImage(enemyPet);
    DOM.enemyImg.onerror = function () { this.src = '../assets/placeholder.png'; };
    updateEnemyHp(enemyPet.hp || 0, enemyPet.is_fainted);
}

function renderSkills() {
    const activePet = BattleState.playerPets[BattleState.activePlayerIndex];

    // Safety check
    if (!activePet) {
        console.error('No active pet for skills');
        return;
    }

    const skills = BattleState.playerSkills.length > 0
        ? BattleState.playerSkills
        : getDefaultSkills(activePet.element || 'Fire');

    DOM.skillsGrid.innerHTML = skills.slice(0, 4).map(skill => `
        <button class="skill-btn ${(skill.skill_element || 'fire').toLowerCase()}" 
                onclick="handleAttack(${skill.id})"
                ${BattleState.currentTurn !== 'player' ? 'disabled' : ''}>
            <span class="skill-name">${skill.skill_name || 'Attack'}</span>
            <span class="skill-damage"><i class="fas fa-bolt"></i> ${skill.base_damage || 25}</span>
        </button>
    `).join('');
}

function updatePlayerHp(hp, isFainted) {
    const pet = BattleState.playerPets[BattleState.activePlayerIndex];
    const percent = Math.max(0, (hp / pet.max_hp) * 100);
    DOM.playerHpBar.style.width = percent + '%';
    DOM.playerHpText.textContent = `${Math.max(0, hp)}/${pet.max_hp}`;

    // Sync desktop panel
    if (DOM.panelPlayerHp) DOM.panelPlayerHp.style.width = percent + '%';
    if (DOM.panelPlayerHpText) DOM.panelPlayerHpText.textContent = `${Math.max(0, hp)}/${pet.max_hp}`;

    if (percent < 30) {
        DOM.playerHpBar.classList.add('low');
        if (DOM.panelPlayerHp) DOM.panelPlayerHp.classList.add('low');
    } else {
        DOM.playerHpBar.classList.remove('low');
        if (DOM.panelPlayerHp) DOM.panelPlayerHp.classList.remove('low');
    }
}

function updateEnemyHp(hp, isFainted) {
    const pet = BattleState.enemyPets[BattleState.activeEnemyIndex];
    const percent = Math.max(0, (hp / pet.max_hp) * 100);
    DOM.enemyHpBar.style.width = percent + '%';
    DOM.enemyHpText.textContent = `${Math.max(0, hp)}/${pet.max_hp}`;

    // Sync desktop panel
    if (DOM.panelEnemyHp) DOM.panelEnemyHp.style.width = percent + '%';
    if (DOM.panelEnemyHpText) DOM.panelEnemyHpText.textContent = `${Math.max(0, hp)}/${pet.max_hp}`;

    if (percent < 30) {
        DOM.enemyHpBar.classList.add('low');
        if (DOM.panelEnemyHp) DOM.panelEnemyHp.classList.add('low');
    } else {
        DOM.enemyHpBar.classList.remove('low');
        if (DOM.panelEnemyHp) DOM.panelEnemyHp.classList.remove('low');
    }
}

function updateTurnDisplay() {
    DOM.turnIndicator.textContent = BattleState.currentTurn === 'player' ? 'YOUR TURN' : 'ENEMY TURN';
    DOM.turnIndicator.classList.toggle('enemy-turn', BattleState.currentTurn === 'enemy');
    DOM.turnCount.textContent = `Turn ${BattleState.turnCount}`;

    // Update turn glow effects (use stage combatants instead of removed elements)
    const playerStage = document.querySelector('.player-stage');
    const enemyStage = document.querySelector('.enemy-stage');

    if (playerStage && enemyStage) {
        if (BattleState.currentTurn === 'player') {
            playerStage.classList.add('your-turn');
            playerStage.classList.remove('enemy-turn-glow');
            enemyStage.classList.remove('enemy-turn-glow');
            enemyStage.classList.remove('your-turn');
        } else {
            playerStage.classList.remove('your-turn');
            enemyStage.classList.add('enemy-turn-glow');
        }
    }
}

// ================================================
// UI HELPERS
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
    const startX = fromRect.left + fromRect.width / 2;
    const startY = fromRect.top + fromRect.height / 2;

    // End position (center of defender)
    const endX = toRect.left + toRect.width / 2;
    const endY = toRect.top + toRect.height / 2;

    projectile.style.left = startX + 'px';
    projectile.style.top = startY + 'px';

    document.body.appendChild(projectile);

    // Animate to target
    requestAnimationFrame(() => {
        projectile.style.transition = 'all 0.4s ease-out';
        projectile.style.left = endX + 'px';
        projectile.style.top = endY + 'px';
        projectile.style.transform = 'scale(1.3)';
    });

    // Remove after animation
    setTimeout(() => {
        projectile.style.opacity = '0';
        projectile.style.transform = 'scale(2)';
        setTimeout(() => projectile.remove(), 200);
    }, 400);
}

function showFloatingDamage(targetElement, damage, isCritical, advantage) {
    // Use new FCT (Floating Combat Text) system
    const fct = document.createElement('div');
    fct.className = 'fct damage';

    if (isCritical) {
        fct.classList.add('crit');
        fct.textContent = `CRIT! ${damage}`;
        // Trigger screen shake for critical hits
        triggerScreenShake();
    } else if (advantage === 'super_effective') {
        fct.classList.add('effective');
        fct.textContent = `${damage}!`;
    } else if (advantage === 'not_effective') {
        fct.classList.add('weak');
        fct.textContent = `${damage}`;
    } else if (damage === 0 || damage === 'MISS') {
        fct.classList.remove('damage');
        fct.classList.add('miss');
        fct.textContent = 'MISS';
    } else {
        fct.textContent = `-${damage}`;
    }

    // Position above sprite head
    const rect = targetElement.getBoundingClientRect();
    fct.style.left = (rect.left + rect.width / 2) + 'px';
    fct.style.top = (rect.top) + 'px';

    document.body.appendChild(fct);
    setTimeout(() => fct.remove(), 1200);
}

/**
 * Trigger screen shake effect for critical hits
 */
function triggerScreenShake() {
    const stage = document.querySelector('.battle-stage');
    if (!stage) return;

    stage.classList.add('screen-shake');
    setTimeout(() => {
        stage.classList.remove('screen-shake');
    }, 400);
}

/**
 * Show target glow effect on sprite
 * @param {string} target - 'player' or 'enemy'
 * @param {boolean} show - true to show, false to hide
 */
function setTargetGlow(target, show) {
    const spriteEl = document.getElementById(target + '-sprite');
    if (!spriteEl) return;

    if (show) {
        spriteEl.classList.add('targeted');
    } else {
        spriteEl.classList.remove('targeted');
    }
}

function addBattleLog(message, className = '') {
    const entry = document.createElement('div');
    entry.className = 'log-entry ' + className;
    entry.textContent = message;

    // Add to mobile log
    DOM.battleLog.appendChild(entry);
    DOM.battleLog.scrollTop = DOM.battleLog.scrollHeight;

    // Sync to desktop log
    if (DOM.desktopBattleLog) {
        const desktopEntry = entry.cloneNode(true);
        DOM.desktopBattleLog.appendChild(desktopEntry);
        DOM.desktopBattleLog.scrollTop = DOM.desktopBattleLog.scrollHeight;
    }
}

function disableControls(disabled) {
    const buttons = DOM.skillsGrid.querySelectorAll('.skill-btn');
    buttons.forEach(btn => btn.disabled = disabled);
    DOM.swapBtn.disabled = disabled;
}

function openSwapModal() {
    // Safety check - if no pets data, reload battle state
    if (!BattleState.playerPets || BattleState.playerPets.length === 0) {
        console.error('No player pets data available');
        alert('Battle data corrupted. Please start a new battle.');
        return;
    }

    DOM.swapPets.innerHTML = BattleState.playerPets.map((pet, i) => {
        if (!pet) return ''; // Skip undefined pets
        const petName = pet.nickname || pet.species_name || 'Unknown';
        const petHp = pet.hp || 0;
        const petMaxHp = pet.max_hp || 100;
        const petElement = pet.element || 'Fire';
        const isFainted = pet.is_fainted || petHp <= 0;

        return `
        <div class="swap-pet-option ${i === BattleState.activePlayerIndex ? 'current' : ''} ${isFainted ? 'fainted' : ''}"
             onclick="switchToPet(${i})">
            <img src="${getBattlePetImage(pet)}" alt="${petName}" class="swap-pet-img" onerror="this.src='../assets/placeholder.png'">
            <div class="swap-pet-info">
                <div class="swap-pet-name">${petName}</div>
                <div class="swap-pet-hp">HP: ${petHp}/${petMaxHp}</div>
            </div>
            <span class="element-badge ${petElement.toLowerCase()}">${petElement}</span>
        </div>
    `;
    }).join('');

    DOM.swapModal.classList.remove('hidden');
}

function closeSwapModal() {
    DOM.swapModal.classList.add('hidden');
}

async function switchToPet(index) {
    // Can't switch to current pet
    if (index === BattleState.activePlayerIndex) {
        closeSwapModal();
        return;
    }

    // Can't switch to fainted pet
    const pet = BattleState.playerPets[index];
    if (pet.is_fainted || pet.hp <= 0) {
        alert('Cannot switch to a fainted pet!');
        return;
    }

    // Can only switch on player turn
    if (BattleState.currentTurn !== 'player') {
        alert('Can only switch on your turn!');
        return;
    }

    closeSwapModal();
    disableControls(true);
    BattleState.isAnimating = true;

    try {
        // Call API to switch pet (uses a turn)
        const response = await fetch(`${API_BASE}?action=battle_switch`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                battle_id: BATTLE_ID,
                new_pet_index: index
            })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Switch failed');
        }

        // Update state
        const battleState = data.battle_state;
        BattleState.currentTurn = battleState.current_turn;
        BattleState.turnCount = battleState.turn_count;
        BattleState.status = battleState.status;
        BattleState.activePlayerIndex = battleState.active_player_index;
        BattleState.activeEnemyIndex = battleState.active_enemy_index;
        BattleState.playerPets = battleState.player_pets;
        BattleState.enemyPets = battleState.enemy_pets;

        // Log
        data.logs?.forEach(log => addBattleLog(log, 'player-action'));

        // Re-render UI
        renderTeamIndicators();
        renderActivePets();
        renderSkills();
        updateTurnDisplay();

        // If enemy turn, trigger it
        if (BattleState.currentTurn === 'enemy' && BattleState.status === 'active') {
            await sleep(500);
            await enemyTurn();
        }

    } catch (error) {
        console.error('Switch error:', error);
        alert('Failed to switch pet: ' + error.message);
    } finally {
        BattleState.isAnimating = false;
        if (BattleState.currentTurn === 'player' && BattleState.status === 'active') {
            disableControls(false);
        }
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ================================================
// BATTLE END
// ================================================
async function endBattle(playerWon) {
    disableControls(true);

    // Call API to apply HP damage and get rewards from server
    let goldReward = 0;
    let expReward = 0;
    let petsDamaged = [];
    let petsDied = [];

    try {
        const response = await fetch(`${API_BASE}?action=finish_3v3_battle`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ battle_id: BattleState.battleId })
        });
        const data = await response.json();

        if (data.success) {
            goldReward = data.gold_reward || 0;
            expReward = data.exp_reward || 0;
            petsDamaged = data.hardcore?.pets_damaged || [];
            petsDied = data.hardcore?.pets_died || [];

            console.log('Battle finished:', data);
        }
    } catch (error) {
        console.error('Error finishing battle:', error);
    }

    // Trigger PixiJS effects
    if (playerWon) {
        if (typeof showVictoryEffects === 'function') {
            showVictoryEffects();
        }
    } else {
        if (typeof showDefeatEffects === 'function') {
            showDefeatEffects();
        }
    }

    setTimeout(() => {
        const resultTitle = document.getElementById('result-title');
        if (playerWon) {
            resultTitle.textContent = '🏆 Victory!';
            resultTitle.className = 'victory';
            if (typeof SoundManager !== 'undefined') SoundManager.victory();
        } else {
            resultTitle.textContent = '💀 Defeat...';
            resultTitle.className = 'defeat';
            if (typeof SoundManager !== 'undefined') SoundManager.defeat();
        }

        document.getElementById('reward-gold').textContent = `+${goldReward}`;
        document.getElementById('reward-exp').textContent = `+${expReward}`;

        // Show HP damage info if any pets were damaged
        if (petsDamaged.length > 0 || petsDied.length > 0) {
            const damageInfo = document.createElement('div');
            damageInfo.className = 'hardcore-damage-info';
            damageInfo.innerHTML = `
                <div style="color: #e74c3c; font-size: 0.8rem; margin-top: 0.5rem;">
                    ⚠️ Hardcore: ${petsDamaged.length} pet(s) lost HP
                    ${petsDied.length > 0 ? `<br>💀 ${petsDied.length} pet(s) DIED!` : ''}
                </div>
            `;
            document.querySelector('.result-stats').appendChild(damageInfo);
        }

        DOM.resultOverlay.classList.remove('hidden');
    }, 1000);
}

function forfeitBattle() {
    if (BattleState.status !== 'active') {
        returnToArena();
        return;
    }

    if (confirm('Forfeit this battle? You will lose!')) {
        endBattle(false);
    }
}

function returnToArena() {
    window.location.href = 'pet.php?tab=arena3v3';
}
