/**
 * Arena/Battle System Module
 * @module pet/arena
 * @description Handles arena opponents, battle history, and battle initiation
 * 
 * @author MOE Development Team
 * @version 2.0.0
 */

import { API_BASE, ASSETS_BASE } from './config.js';
import { state } from './state.js';
import { showToast } from './ui.js';

// ================================================
// ARENA INITIALIZATION
// ================================================

/**
 * Initialize arena tab navigation (opponents vs history)
 * @returns {void}
 */
export function initArenaTabs() {
    document.querySelectorAll('.arena-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.arena-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const view = tab.dataset.view;
            document.getElementById('arena-opponents').style.display = view === 'opponents' ? 'block' : 'none';
            document.getElementById('arena-history').style.display = view === 'history' ? 'block' : 'none';

            if (view === 'opponents') {
                loadOpponents();
            } else {
                loadBattleHistory();
            }
        });
    });
}

// ================================================
// OPPONENTS
// ================================================

/**
 * Load available opponents for 1v1 battles
 * @async
 * @returns {Promise<void>}
 */
export async function loadOpponents() {
    const container = document.getElementById('arena-opponents');
    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Finding opponents...</p></div>';

    // Helper to get correct image based on level
    function getPetImageByLevel(pet) {
        const level = parseInt(pet.level) || 1;
        let img;
        if (level >= 10) {
            img = pet.img_adult || pet.img_baby || pet.img_egg;
        } else if (level >= 5) {
            img = pet.img_baby || pet.img_egg || pet.img_adult;
        } else {
            img = pet.img_egg || pet.img_baby || pet.img_adult;
        }
        return img || 'placeholder.png';
    }

    try {
        const response = await fetch(`${API_BASE}?action=get_opponents`);
        const data = await response.json();

        if (data.success && data.opponents.length > 0) {
            container.innerHTML = data.opponents.map(opp => `
                <div class="opponent-card">
                    <img src="${ASSETS_BASE}${getPetImageByLevel(opp)}" alt="${opp.species_name}" class="opponent-img"
                         onerror="this.src='../assets/placeholder.png'">
                    <div class="opponent-info">
                        <h3 class="opponent-name">${opp.display_name}</h3>
                        <p class="opponent-owner">Owner: ${opp.owner_name}</p>
                        <div class="opponent-stats">
                            <span class="element-badge ${opp.element.toLowerCase()}">${opp.element}</span>
                            <span class="pet-level">Lv.${opp.level}</span>
                        </div>
                    </div>
                    <button class="battle-btn" onclick="startBattle(${opp.pet_id})">
                        <i class="fas fa-bolt"></i> Fight
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-message">No opponents available right now. Check back later!</div>';
        }
    } catch (error) {
        console.error('Error loading opponents:', error);
        container.innerHTML = '<div class="empty-message">Failed to load opponents</div>';
    }
}

// ================================================
// BATTLE ACTIONS
// ================================================

/**
 * Start a battle against an opponent
 * @param {number} defenderPetId - The ID of the opponent's pet
 * @returns {void}
 */
export function startBattle(defenderPetId) {
    if (!state.activePet) {
        showToast('You need an active pet to battle!', 'warning');
        return;
    }

    if (state.activePet.status === 'DEAD') {
        showToast('Cannot battle with a dead pet!', 'error');
        return;
    }

    window.location.href = `battle_arena.php?attacker_id=${state.activePet.id}&defender_id=${defenderPetId}`;
}

/**
 * Display the battle result modal
 * @param {Object} data - Battle result data
 * @param {number} data.winner_pet_id - ID of the winning pet
 * @param {Object} data.attacker - Attacker pet data
 * @param {number} data.attacker.pet_id - Attacker pet ID
 * @param {string} data.attacker.name - Attacker pet name
 * @param {number} data.attacker.final_hp - Attacker's remaining HP
 * @param {Object} data.defender - Defender pet data
 * @param {number} data.defender.pet_id - Defender pet ID
 * @param {string} data.defender.name - Defender pet name
 * @param {number} data.defender.final_hp - Defender's remaining HP
 * @param {Array<Object>} data.battle_log - Array of battle round logs
 * @param {Object} [data.rewards] - Rewards for winner
 * @returns {void}
 */
export function showBattleResult(data) {
    const modal = document.getElementById('battle-modal');

    const title = document.getElementById('battle-title');
    if (data.winner_pet_id === data.attacker.pet_id) {
        title.textContent = '👑 Victory!';
        title.style.color = '#2ecc71';
    } else if (data.winner_pet_id === data.defender.pet_id) {
        title.textContent = '💀 Defeat';
        title.style.color = '#e74c3c';
    } else {
        title.textContent = '⚖️ Draw';
        title.style.color = '#f39c12';
    }

    document.getElementById('battle-atk-name').textContent = data.attacker.name;
    document.getElementById('battle-atk-hp').textContent = data.attacker.final_hp;
    document.getElementById('battle-def-name').textContent = data.defender.name;
    document.getElementById('battle-def-hp').textContent = data.defender.final_hp;

    const logEl = document.getElementById('battle-log');
    logEl.innerHTML = data.battle_log.map(entry => `
        <div class="battle-log-entry">
            Round ${entry.round}: <strong>${entry.actor}</strong> ${entry.action}s for <span style="color: #e74c3c">${entry.damage}</span> damage!
        </div>
    `).join('');

    const rewardsEl = document.getElementById('battle-rewards');
    if (data.winner_pet_id === data.attacker.pet_id && data.rewards) {
        document.getElementById('reward-gold').textContent = data.rewards.gold;
        document.getElementById('reward-exp').textContent = data.rewards.exp;
        rewardsEl.style.display = 'flex';
    } else {
        rewardsEl.style.display = 'none';
    }

    modal.classList.add('show');
}

/**
 * Close the battle result modal
 * @returns {void}
 */
export function closeBattleModal() {
    document.getElementById('battle-modal').classList.remove('show');
}

// ================================================
// BATTLE HISTORY
// ================================================

/**
 * Load the user's battle history
 * @async
 * @returns {Promise<void>}
 */
export async function loadBattleHistory() {
    const container = document.getElementById('battle-history');

    try {
        const response = await fetch(`${API_BASE}?action=battle_history`);
        const data = await response.json();

        if (data.success && data.battles.length > 0) {
            container.innerHTML = data.battles.map(battle => {
                const date = new Date(battle.created_at).toLocaleDateString();
                return `
                    <div class="battle-history-item">
                        <div class="battle-header">
                            <span class="battle-date">${date}</span>
                        </div>
                        <div class="battle-participants">
                            <span>${battle.atk_display}</span>
                            <span class="battle-vs">VS</span>
                            <span>${battle.def_display}</span>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<div class="empty-message">No battles yet. Challenge someone!</div>';
        }
    } catch (error) {
        console.error('Error loading battle history:', error);
    }
}
