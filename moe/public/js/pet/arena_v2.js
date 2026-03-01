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
        if (level >= 70) {
            img = pet.img_adult || pet.img_baby || pet.img_egg;
        } else if (level >= 30) {
            img = pet.img_baby || pet.img_egg || pet.img_adult;
        } else {
            img = pet.img_egg || pet.img_baby || pet.img_adult;
        }

        // Use absolute path for placeholder if missing
        const placeholder = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../') + 'assets/placeholder.png';
        return img ? ASSETS_BASE + img : placeholder;
    }

    try {
        const response = await fetchWithCsrf(`${API_BASE}battle/opponents`);
        const data = await response.json();

        if (data.success && data.opponents.length > 0) {
            container.innerHTML = data.opponents.map(opp => `
                <div class="opponent-card-premium">
                    <div class="opponent-rank">Rank #${Math.floor(Math.random() * 100) + 1}</div>
                    <div class="opponent-pet-display">
                        <img src="${getPetImageByLevel(opp)}" alt="${opp.species_name}"
                             onerror="this.onerror=null; this.src='${(typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../')}assets/placeholder.png'">
                        <div class="rarity-badge ${opp.rarity?.toLowerCase() || 'common'}">${opp.rarity || 'Common'}</div>
                    </div>
                    <div class="opponent-info">
                        <h3 class="pet-name">${opp.display_name}</h3>
                        <div class="owner-info">
                            <i class="fas fa-user-crown"></i>
                            <span>${opp.owner_name}</span>
                        </div>
                    </div>
                    <div class="opponent-stats-preview">
                        <div class="stat-mini">
                            <span>ATK</span>
                            <div class="stat-bar"><div class="fill" style="width: ${Math.min(100, (opp.level * 1.5))}%"></div></div>
                        </div>
                        <div class="stat-mini">
                            <span>DEF</span>
                            <div class="stat-bar"><div class="fill" style="width: ${Math.min(100, (opp.level * 1.2))}%"></div></div>
                        </div>
                    </div>
                    <button class="battle-btn-premium ${currentBattlesRemaining <= 0 ? 'disabled' : ''}" 
                            onclick="startBattle(${opp.pet_id}, ${opp.species_id || 0}, ${opp.level || 1})"
                            ${currentBattlesRemaining <= 0 ? 'disabled' : ''}>
                        <i class="fas fa-swords"></i> FIGHT
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="no-pets-message">No opponents available right now. Check back later!</div>';
        }
    } catch (error) {
        console.error('Error loading opponents:', error);
        container.innerHTML = '<div class="no-pets-message">Failed to load opponents</div>';
    }
}

// ================================================
// BATTLE ACTIONS
// ================================================

/**
 * Get pet image path based on evolution stage (Legacy helper)
 * @param {Object} pet - Pet object
 * @returns {string} Image path
 */
function getArenaPetImage(pet) {
    if (pet.img && typeof pet.img === 'string' && pet.img.length > 0) {
        return ASSETS_BASE + pet.img;
    }

    const stage = pet.evolution_stage || 'egg';
    let imgKey;

    switch (stage) {
        case 'adult':
            imgKey = pet.img_adult || pet.img_baby || pet.img_egg;
            break;
        case 'baby':
            imgKey = pet.img_baby || pet.img_egg || pet.img_adult;
            break;
        case 'egg':
        default:
            imgKey = pet.img_egg || pet.img_baby || pet.img_adult;
            break;
    }

    const assetRoot = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../');
    return ASSETS_BASE + (imgKey || 'default/egg.png');
}

/**
 * Start a battle against an opponent
 * @async
 * @param {number} defenderPetId - The ID of the opponent's pet
 * @returns {Promise<void>}
 */
export async function startBattle(defenderPetId, speciesId = 0, level = 1) {
    if (!state.activePet) {
        showToast('You need an active pet to battle!', 'warning');
        return;
    }

    if (state.activePet.status === 'DEAD') {
        showToast('Cannot battle with a dead pet!', 'error');
        return;
    }

    // DIM/DISABLE UI to prevent double click
    const btn = event?.currentTarget;
    if (btn) btn.disabled = true;

    try {
        const payload = {
            attacker_pet_id: state.activePet.id,
            defender_pet_id: defenderPetId
        };
        // Send species_id and level for AI opponents
        if (defenderPetId <= 0) {
            payload.species_id = speciesId;
            payload.level = level;
        }

        const response = await fetchWithCsrf(`${API_BASE}battle/start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            const assetRoot = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../');
            window.location.href = `${assetRoot}battle?attacker_id=${state.activePet.id}&defender_id=${defenderPetId}`;
        } else {
            showToast(data.error || 'Failed to start battle', 'error');
            if (btn) btn.disabled = false;
        }
    } catch (error) {
        console.error('Error starting battle:', error);
        showToast('Network error while starting battle', 'error');
        if (btn) btn.disabled = false;
    }
}

// ================================================
// ARENA STATS
// ================================================

let currentBattlesRemaining = 3;

/**
 * Load arena stats and update display
 * @async
 * @returns {Promise<void>}
 */
export async function loadArenaStats() {
    const battlesEl = document.getElementById('arena-battles');
    const winsEl = document.getElementById('total-wins');
    const winRateEl = document.getElementById('win-rate');
    const streakEl = document.getElementById('current-streak');

    if (battlesEl) battlesEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    if (winsEl) winsEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    if (winRateEl) winRateEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    if (streakEl) streakEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const response = await fetch(`${API_BASE}battle/history`);
        const data = await response.json();

        if (data.success) {
            const stats = data.stats || {};
            const wins = stats.wins || 0;
            const losses = stats.losses || 0;
            const streak = stats.current_streak || 0;
            const battlesRemaining = stats.battles_remaining !== undefined ? stats.battles_remaining : 5;
            const battleLimit = stats.limit || 5;

            // Update local state
            currentBattlesRemaining = battlesRemaining;

            // Update stats bar
            updateArenaStats(wins, losses, streak);

            // Update battles remaining display
            if (battlesEl) {
                battlesEl.textContent = `${battlesRemaining} / ${battleLimit}`;

                // Update energy orb visual
                const energyFill = document.getElementById('energy-fill');
                if (energyFill) {
                    const percent = Math.round((battlesRemaining / battleLimit) * 100);
                    energyFill.style.height = `${percent}%`;

                    if (percent < 30) {
                        energyFill.style.background = 'linear-gradient(to top, #ff4444, #ff8888)';
                    } else if (percent < 60) {
                        energyFill.style.background = 'linear-gradient(to top, #ffbb33, #ffcc00)';
                    } else {
                        energyFill.style.background = 'linear-gradient(to top, #00C851, #00E676)';
                    }
                }

                // Update reset time
                const resetTimeEl = document.getElementById('arena-reset-time');
                if (resetTimeEl && stats.resets_at) {
                    const resetDate = new Date(stats.resets_at.replace(' ', 'T'));
                    resetTimeEl.textContent = resetDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }

                // Show reset button if quota is empty
                const resetBtn = document.getElementById('btn-reset-quota');
                if (resetBtn) {
                    resetBtn.style.display = (battlesRemaining <= 0) ? 'inline-flex' : 'none';
                }

                // Dim the challenge button if no attempts left
                const challengeBtns = document.querySelectorAll('.battle-btn-premium');
                challengeBtns.forEach(btn => {
                    if (currentBattlesRemaining <= 0) {
                        btn.classList.add('disabled');
                        btn.disabled = true;
                        btn.title = 'No battles remaining today';
                    } else {
                        btn.classList.remove('disabled');
                        btn.disabled = false;
                        btn.title = '';
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error loading arena stats:', error);
        if (battlesEl) battlesEl.textContent = '3 / 3';
    }
}

/**
 * Update the stats display elements
 * @param {number} wins 
 * @param {number} losses 
 * @param {number} streak 
 */
function updateArenaStats(wins, losses, streak) {
    const winsEl = document.getElementById('total-wins');
    if (winsEl) winsEl.textContent = wins || 0;

    const total = (wins || 0) + (losses || 0);
    const winRate = total > 0 ? Math.round((wins / total) * 100) : 0;
    const winRateEl = document.getElementById('win-rate');
    if (winRateEl) winRateEl.textContent = `${winRate}%`;

    const streakEl = document.getElementById('current-streak');
    if (streakEl) streakEl.textContent = streak || 0;
}

// ================================================
// 3V3 TEAM BATTLE SYSTEM
// ================================================

let selected3v3Pets = [];

/**
 * Load team selection for 3v3 battles
 * @async
 * @returns {Promise<void>}
 */
export async function loadTeamSelection() {
    const container = document.getElementById('team-selection');
    if (!container) return;

    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading your pets...</p></div>';

    try {
        let userPets = state.userPets;
        if (!userPets || userPets.length === 0) {
            const response = await fetch(`${API_BASE}pets`);
            const data = await response.json();
            if (data.success) {
                userPets = data.pets;
                state.userPets = data.pets;
            }
        }

        if (!userPets || userPets.length === 0) {
            container.innerHTML = '<div class="empty-message">No pets available</div>';
            return;
        }

        const eligiblePets = userPets.filter(pet => pet.status !== 'DEAD');
        const assetRoot = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../');

        container.innerHTML = eligiblePets.map(pet => {
            const imgPath = getArenaPetImage(pet);
            const isSheltered = pet.status === 'SHELTER';
            const cardClass = isSheltered ? 'selectable-pet-card sheltered' : 'selectable-pet-card';
            const clickAction = isSheltered ? '' : `onclick="toggle3v3Selection(this, ${pet.id})"`;

            return `
                <div class="${cardClass}" data-pet-id="${pet.id}" ${clickAction}>
                    <div class="rarity-indicator ${(pet.rarity || 'common').toLowerCase()}"></div>
                    <img src="${imgPath}" alt="${pet.nickname || pet.species_name}"
                         onerror="this.onerror=null; this.src='${assetRoot}assets/placeholder.png'">
                    <div class="pet-name-mini">${pet.nickname || pet.species_name}</div>
                    <div class="pet-level-mini">Lv. ${pet.level}</div>
                    ${isSheltered ? '<div class="shelter-overlay"><i class="fas fa-home"></i> IN SHELTER</div>' : ''}
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error loading 3v3 team selection:', error);
    }
}

/**
 * Toggle pet selection for 3v3 team
 * @param {HTMLElement} cardElement 
 * @param {number} petId 
 */
export function toggle3v3Selection(cardElement, petId) {
    const maxTeamSize = 3;
    const index = selected3v3Pets.findIndex(p => p.id == petId);

    if (index > -1) {
        selected3v3Pets.splice(index, 1);
        cardElement.classList.remove('selected');
    } else {
        if (selected3v3Pets.length >= maxTeamSize) {
            showToast('You can only select 3 pets!', 'warning');
            return;
        }

        const pet = (state.userPets || []).find(p => p.id == petId);
        if (pet) {
            selected3v3Pets.push(pet);
            cardElement.classList.add('selected');
        }
    }
    updateTeamSlots3v3();
}

/**
 * Update team slots display
 */
export function updateTeamSlots3v3() {
    const slots = document.querySelectorAll('#arena3v3 .team-slot');
    if (!slots || slots.length === 0) return;

    slots.forEach((slot, index) => {
        if (selected3v3Pets[index]) {
            const pet = selected3v3Pets[index];
            slot.innerHTML = `
                <img src="${getArenaPetImage(pet)}" alt="Slot ${index + 1}"
                     onerror="this.onerror=null; this.src='${(typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../')}assets/placeholder.png'">
                <button class="remove-btn" onclick="event.stopPropagation(); removePetFromSlot(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            slot.classList.remove('empty');
            slot.classList.add('filled');
        } else {
            slot.innerHTML = `<i class="fas fa-plus"></i><span>Slot ${index + 1}</span>`;
            slot.classList.add('empty');
            slot.classList.remove('filled');
        }
    });
}

/**
 * Remove pet from team slot
 * @param {number} slotIndex 
 */
export function removePetFromSlot(slotIndex) {
    if (selected3v3Pets[slotIndex]) {
        const petId = selected3v3Pets[slotIndex].id;
        selected3v3Pets.splice(slotIndex, 1);
        const card = document.querySelector(`.selectable-pet-card[data-pet-id="${petId}"]`);
        if (card) card.classList.remove('selected');
        updateTeamSlots3v3();
    }
}

/**
 * Start 3v3 battle with selected pets
 * @async
 * @returns {Promise<void>}
 */
export async function start3v3Battle() {
    const btn = document.getElementById('btn-start-3v3');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Starting Battle...</span>';
    }

    try {
        if (selected3v3Pets.length !== 3) {
            const validPets = state.userPets.filter(pet => pet.status !== 'DEAD' && pet.status !== 'SHELTER');
            if (validPets.length < 3) {
                showToast('You need at least 3 alive pets for 3v3 battle!', 'warning');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-dragon"></i> <span>Enter 3v3 Arena</span>';
                }
                return;
            }
            selected3v3Pets = validPets.slice(0, 3);
        }

        const teamPetIds = selected3v3Pets.map(p => p.id);
        const response = await fetchWithCsrf(`${API_BASE}battle/start-3v3`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pet_ids: teamPetIds, opponent_user_id: 0 })
        });

        const data = await response.json();
        if (data.success && data.battle_id) {
            const assetRoot = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '/');
            window.location.href = `${assetRoot}battle-3v3?battle_id=${data.battle_id}`;
        } else {
            throw new Error(data.error || 'Failed to create battle');
        }
    } catch (error) {
        console.error('Error starting 3v3 battle:', error);
        showToast('Failed to start battle: ' + error.message, 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-dragon"></i> <span>Enter 3v3 Arena</span>';
        }
    }
}

// ================================================
// BATTLE HISTORY
// ================================================

let historyOffset = 0;
const historyLimit = 10;
let isHistoryLoading = false;

/**
 * Open battle history tab and load data
 */
export function loadBattleHistory() {
    historyOffset = 0;
    fetchHistory(false);
}

/**
 * Fetch battle history from API
 * @param {boolean} append - Whether to append to existing list
 */
export async function fetchHistory(append) {
    const listContainer = document.getElementById('history-list');
    const winsEl = document.getElementById('h-total-wins');
    const lossesEl = document.getElementById('h-total-losses');
    const streakEl = document.getElementById('h-win-streak');

    if (!listContainer || isHistoryLoading) return;
    isHistoryLoading = true;

    if (!append) {
        listContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading battle records...</span></div>';
    } else {
        const existingBtn = document.getElementById('load-more-btn-container');
        if (existingBtn) existingBtn.remove();
        const spinner = document.createElement('div');
        spinner.id = 'history-spinner-bottom';
        spinner.className = 'loading-spinner';
        spinner.innerHTML = '<div class="spinner small"></div>';
        listContainer.appendChild(spinner);
    }

    try {
        const url = `${API_BASE}battle/history?limit=${historyLimit}&offset=${historyOffset}&t=${Date.now()}`;
        const response = await fetch(url);
        const data = await response.json();

        isHistoryLoading = false;
        const bottomSpinner = document.getElementById('history-spinner-bottom');
        if (bottomSpinner) bottomSpinner.remove();

        if (!data.success) {
            if (!append) listContainer.innerHTML = '<div class="empty-state">Unable to load history</div>';
            return;
        }

        const stats = data.stats || {};
        if (winsEl) winsEl.textContent = stats.wins || 0;
        if (lossesEl) lossesEl.textContent = stats.losses || 0;
        if (streakEl) streakEl.textContent = stats.current_streak || 0;

        const history = data.history || [];
        if (!append && history.length === 0) {
            listContainer.innerHTML = '<div class="empty-state">No battles recorded yet.<br><small>Fight in the Arena!</small></div>';
            return;
        }

        const lbAssets = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../') + 'assets/pets/';
        const assetRoot = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../');

        const html = history.map(battle => {
            const date = new Date(battle.created_at).toLocaleDateString();
            const won = !!battle.won;
            const isDefender = battle.battle_role === 'defender';
            const roleLabel = isDefender ? '🛡️ DEFENDED' : '⚔️ ATTACKED';
            const roleClass = isDefender ? 'defender' : 'attacker';
            const modeLabel = battle.mode === '3v3' ? '3v3 Team' : '1v1 Duel';

            const gold = parseInt(battle.reward_gold) || 0;
            const exp = parseInt(battle.reward_exp) || 0;

            const myImg = battle.my_pet_image ? lbAssets + battle.my_pet_image : assetRoot + 'assets/placeholder.png';
            const oppImg = battle.opp_pet_image ? lbAssets + battle.opp_pet_image : assetRoot + 'assets/placeholder.png';

            return `
                <div class="history-card ${won ? 'win' : 'lose'} ${roleClass}">
                    <div class="h-header">
                        <div class="h-role-badge ${roleClass}">${roleLabel}</div>
                        <div class="h-mode-badge">${modeLabel}</div>
                    </div>
                    
                    <div class="h-main-content">
                        <div class="h-pet player">
                            <div class="h-pet-avatar">
                                <img class="h-pet-img" src="${myImg}" onerror="this.onerror=null; this.src='${assetRoot}assets/placeholder.png'">
                                <div class="h-lvl-badge">Lv.${battle.my_pet_level}</div>
                            </div>
                            <div class="h-info">
                                <span class="h-pet-name">${battle.my_pet_name}</span>
                                <span class="h-owner-name">You</span>
                            </div>
                        </div>

                        <div class="h-result">
                            <span class="h-res-text ${won ? 'win' : 'lose'}">${won ? 'VICTORY' : 'DEFEAT'}</span>
                            <div class="h-vs-container">
                                <div class="h-vs-line"></div>
                                <span class="h-vs">VS</span>
                                <div class="h-vs-line"></div>
                            </div>
                            <span class="h-date">${date}</span>
                        </div>

                        <div class="h-pet enemy">
                            <div class="h-pet-avatar">
                                <img class="h-pet-img" src="${oppImg}" onerror="this.onerror=null; this.src='${assetRoot}assets/placeholder.png'">
                                <div class="h-lvl-badge">Lv.${battle.opp_pet_level}</div>
                            </div>
                            <div class="h-info">
                                <span class="h-pet-name">${battle.opp_pet_name}</span>
                                <span class="h-owner-name">${battle.opp_username || 'Opponent'}</span>
                            </div>
                        </div>
                    </div>

                    ${(gold > 0 || exp > 0) ? `
                    <div class="h-rewards">
                        <span class="h-reward-label">REWARDS:</span>
                        <div class="h-reward-items">
                            ${gold > 0 ? `<div class="h-reward gold"><i class="fas fa-coins text-warning"></i> +${gold.toLocaleString()}</div>` : ''}
                            ${exp > 0 ? `<div class="h-reward exp"><i class="fas fa-sparkles text-info"></i> +${exp} EXP</div>` : ''}
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
        }).join('');

        if (!append) {
            listContainer.innerHTML = html;
        } else {
            listContainer.insertAdjacentHTML('beforeend', html);
        }

        if (history.length === historyLimit) {
            const btnContainer = document.createElement('div');
            btnContainer.id = 'load-more-btn-container';
            btnContainer.className = 'load-more-container';
            btnContainer.innerHTML = '<button class="load-more-btn">Load More Results</button>';
            listContainer.appendChild(btnContainer);
            btnContainer.querySelector('button').onclick = () => {
                historyOffset += historyLimit;
                fetchHistory(true);
            };
        }
    } catch (error) {
        console.error('Error fetching history:', error);
        isHistoryLoading = false;
        if (!append) listContainer.innerHTML = '<div class="empty-state">Network Error</div>';
    }
}
// ================================================
// BATTLE RESULTS MODAL
// ================================================

/**
 * Show the battle result modal
 * @param {Object} data - Battle result data
 * @returns {void}
 */
export function showBattleResult(data) {
    const modal = document.getElementById('battle-result-modal');
    if (!modal) return;

    // Populate modal (Custom implement based on data)
    const resultTitle = modal.querySelector('.result-title');
    if (resultTitle) {
        resultTitle.textContent = data.winner_id == state.activePet?.id ? 'Victory!' : 'Defeat';
        resultTitle.className = 'result-title ' + (data.winner_id == state.activePet?.id ? 'win' : 'lose');
    }

    modal.classList.add('show');
}

/**
 * Close the battle result modal
 * @returns {void}
 */
export function closeBattleModal() {
    const modal = document.getElementById('battle-result-modal');
    if (modal) modal.classList.remove('show');

    // Refresh data
    loadArenaStats();
    loadBattleHistory();
}

/**
 * Use an Arena Ticket to reset quota
 * @async
 */
export async function useArenaTicket() {
    console.log('[useArenaTicket] Called. Inventory:', state.userInventory?.length, 'items');

    // 1. Check active pet
    if (!state.activePet || !state.activePet.id) {
        showToast('You need an active pet first!', 'warning');
        return;
    }

    // 2. Find ticket in inventory
    const ticket = (state.userInventory || []).find(it => it.effect_type === 'arena_reset' && it.quantity > 0);
    console.log('[useArenaTicket] Ticket found:', ticket);

    if (!ticket) {
        showToast('You do not have any Arena Tickets! Buy one from the Shop.', 'warning');
        return;
    }

    try {
        const response = await fetchWithCsrf(`${API_BASE}shop/use`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pet_id: state.activePet.id,
                item_id: ticket.item_id,
                quantity: 1
            })
        });

        const data = await response.json();
        console.log('[useArenaTicket] Server response:', data);

        if (data.success) {
            showToast(data.message || 'Arena quota reset!', 'success');
            // Refresh stats & opponents
            loadArenaStats();
            loadOpponents();
            // Trigger global inventory refresh if available
            if (window.loadInventory) window.loadInventory();
        } else {
            showToast(data.message || 'Failed to use ticket', 'error');
        }
    } catch (error) {
        console.error('[useArenaTicket] Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
};
