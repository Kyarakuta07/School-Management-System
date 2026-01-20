/**
 * MOE Pet System - Arena & Achievements Module
 * Mediterranean of Egypt Virtual Pet Companion
 * Handles Arena battles and Achievements/Badges
 */

// ================================================
// CONSTANTS
// ================================================
const ASSETS_BASE = '../assets/pets/';

/**
 * Get pet image path based on evolution stage
 * @param {Object} pet - Pet object
 * @returns {string} Image path
 */
function getArenaPetImage(pet) {
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

    return ASSETS_BASE + (imgKey || 'default/egg.png');
}

// ================================================
// ARENA SYSTEM
// ================================================

// Load opponents for arena battles
async function loadOpponents() {
    const container = document.getElementById('arena-opponents');
    if (!container) {
        console.error('arena-opponents element not found');
        return;
    }

    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Finding opponents...</p></div>';

    try {
        const response = await fetch('api/router.php?action=get_opponents');
        const data = await response.json();

        if (data.success && data.opponents && data.opponents.length > 0) {
            container.innerHTML = data.opponents.map((opp, index) => {
                // Calculate stat percentages for mini bars
                const hpPercent = Math.min((opp.hp / 100) * 100, 100);
                const atkPercent = Math.min((opp.atk / 100) * 100, 100);
                const defPercent = Math.min((opp.def / 100) * 100, 100);

                // Mock win/loss data (replace with actual data from API)
                const wins = opp.wins || Math.floor(Math.random() * 50);
                const losses = opp.losses || Math.floor(Math.random() * 20);

                return `
                <div class="opponent-card-premium">
                    <div class="opponent-rank">#${index + 1}</div>
                    <div class="opponent-pet-display">
                        <img src="${getArenaPetImage(opp)}" alt="${opp.display_name}"
                             onerror="this.src='../assets/placeholder.png'">
                        <span class="rarity-badge ${(opp.rarity || 'common').toLowerCase()}">${opp.rarity || 'Common'}</span>
                    </div>
                    <div class="opponent-info">
                        <h4 class="pet-name">${opp.display_name}</h4>
                        <p class="pet-level">Lv. ${opp.level}</p>
                        <div class="owner-info">
                            <i class="fas fa-user"></i>
                            <span>${opp.owner_name}</span>
                            ${opp.sanctuary ? '<span class="sanctuary">' + opp.sanctuary + '</span>' : ''}
                        </div>
                    </div>
                    <div class="opponent-stats-preview">
                        <div class="stat-mini">
                            <span>HP</span>
                            <div class="stat-bar"><div class="fill" style="width: ${hpPercent}%"></div></div>
                        </div>
                        <div class="stat-mini">
                            <span>ATK</span>
                            <div class="stat-bar"><div class="fill" style="width: ${atkPercent}%"></div></div>
                        </div>
                        <div class="stat-mini">
                            <span>DEF</span>
                            <div class="stat-bar"><div class="fill" style="width: ${defPercent}%"></div></div>
                        </div>
                    </div>
                    <div class="opponent-record">
                        <span class="wins">W: ${wins}</span>
                        <span class="losses">L: ${losses}</span>
                    </div>
                    <button class="battle-btn-premium" onclick="startBattle(${opp.pet_id})">
                        <i class="fas fa-bolt"></i>
                        Challenge
                    </button>
                </div>
            `;
            }).join('');
        } else {
            container.innerHTML = '<div class="empty-message">No opponents available right now. Check back later!</div>';
        }
    } catch (error) {
        console.error('Error loading opponents:', error);
        container.innerHTML = '<div class="empty-message">Failed to load opponents</div>';
    }
}

// Start battle with selected opponent
window.startBattle = function (defenderPetId) {
    console.log('Starting battle with defender:', defenderPetId);

    // Get active pet - try window.activePet first, then search in userPets
    let activePet = window.activePet;

    // Fallback: find from userPets array if window.activePet not set
    if (!activePet && window.userPets && window.userPets.length > 0) {
        activePet = window.userPets.find(pet => pet.is_active === 1 || pet.is_active === '1');
        console.log('Found active pet from userPets:', activePet);
    }

    // Check if user has active pet
    if (!activePet) {
        // Use showToast if available, otherwise alert
        if (typeof showToast === 'function') {
            showToast('You need an active pet to battle!', 'warning');
        } else {
            alert('You need an active pet to battle! Please select a pet first.');
        }
        console.error('No active pet found. window.userPets:', window.userPets);
        return;
    }

    // Check if pet is alive
    if (activePet.status === 'DEAD') {
        if (typeof showToast === 'function') {
            showToast('Cannot battle with a dead pet!', 'error');
        } else {
            alert('Your pet is dead! Revive it first.');
        }
        return;
    }

    // Redirect to battle arena page
    console.log('Redirecting to battle arena...');
    window.location.href = `battle_arena.php?attacker_id=${activePet.id}&defender_id=${defenderPetId}`;
}

// ================================================
// ACHIEVEMENTS / BADGES SYSTEM
// NOTE: Achievements code has been migrated to ES6 module: js/pet/achievements.js
// The functions loadAchievements() and claimAchievement() are now in that module.
// ================================================

// ================================================
// 3V3 TEAM BATTLE SYSTEM
// ================================================

// Load team selection for 3v3 battles
async function loadTeamSelection() {
    const container = document.getElementById('team-selection');
    if (!container) {
        console.error('team-selection element not found');
        return;
    }

    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading your pets...</p></div>';

    try {
        // Get user's pets - try window.userPets first, fallback to API
        let userPets = window.userPets;

        // If userPets not loaded yet, fetch from API
        if (!userPets || userPets.length === 0) {
            console.log('🔍 Fetching pets for 3v3 from API...');
            const response = await fetch('api/router.php?action=get_pets');
            const data = await response.json();

            if (data.success && data.pets) {
                userPets = data.pets;
                window.userPets = data.pets; // Cache for later use
                console.log('✅ Fetched', userPets.length, 'pets for 3v3');
            } else {
                throw new Error(data.error || 'Failed to load pets');
            }
        }

        if (!userPets || userPets.length === 0) {
            container.innerHTML = `
                <div class="no-pets-message">
                    <i class="fas fa-paw"></i>
                    <p>You need pets to form a team! Visit the Gacha tab to get pets.</p>
                </div>
            `;
            return;
        }

        // Filter alive pets only
        const alivePets = userPets.filter(pet => pet.status !== 'DEAD');

        if (alivePets.length < 3) {
            container.innerHTML = `
                <div class="no-pets-message">
                    <i class="fas fa-heart-broken"></i>
                    <p>You need at least 3 alive pets for team battles!</p>
                    <p class="text-small">You have ${alivePets.length} alive pet(s). Heal or get more pets.</p>
                </div>
            `;
            return;
        }

        // Render selectable pet cards
        container.innerHTML = alivePets.map(pet => {
            const imgPath = getArenaPetImage(pet);
            const displayName = pet.nickname || pet.species_name;
            const rarity = (pet.rarity || 'common').toLowerCase();

            return `
                <div class="selectable-pet-card" 
                     data-pet-id="${pet.id}"
                     onclick="toggle3v3Selection(this, ${pet.id})">
                    <div class="rarity-indicator ${rarity}"></div>
                    <img src="${imgPath}" alt="${displayName}"
                         onerror="this.src='../assets/placeholder.png'">
                    <div class="pet-name-mini">${displayName}</div>
                    <div class="pet-level-mini">Lv. ${pet.level}</div>
                    <span class="element-mini">${pet.element || 'Normal'}</span>
                </div>
            `;
        }).join('');

        console.log('✅ Rendered', alivePets.length, 'pets for 3v3 selection');

    } catch (error) {
        console.error('Error loading team selection:', error);
        container.innerHTML = `
            <div class="no-pets-message">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Failed to load pets. Please refresh the page.</p>
            </div>
        `;
    }
}

// Selected pets for 3v3 team (max 3)
let selected3v3Pets = [];

// Toggle pet selection for 3v3 team
function toggle3v3Selection(cardElement, petId) {
    const maxTeamSize = 3;
    const index = selected3v3Pets.findIndex(p => p.id === petId);

    if (index > -1) {
        // Already selected - remove from team
        selected3v3Pets.splice(index, 1);
        cardElement.classList.remove('selected');
    } else {
        // Not selected - add to team if not full
        if (selected3v3Pets.length >= maxTeamSize) {
            if (typeof showToast === 'function') {
                showToast('You can only select 3 pets!', 'warning');
            }
            return;
        }

        // Find pet from userPets
        const pet = (window.userPets || []).find(p => p.id === petId);
        if (pet) {
            selected3v3Pets.push(pet);
            cardElement.classList.add('selected');
        }
    }

    // Update team slots display
    updateTeamSlots3v3();
}

// Update team slots display
function updateTeamSlots3v3() {
    const slots = document.querySelectorAll('.team-slot');

    slots.forEach((slot, index) => {
        if (selected3v3Pets[index]) {
            const pet = selected3v3Pets[index];
            const imgPath = getArenaPetImage(pet);
            const displayName = pet.nickname || pet.species_name;

            slot.innerHTML = `
                <img src="${imgPath}" alt="${displayName}"
                     onerror="this.src='../assets/placeholder.png'">
                <button class="remove-btn" onclick="event.stopPropagation(); removePetFromSlot(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            slot.classList.remove('empty');
            slot.classList.add('filled');
        } else {
            slot.innerHTML = `
                <i class="fas fa-plus"></i>
                <span>Slot ${index + 1}</span>
            `;
            slot.classList.add('empty');
            slot.classList.remove('filled');
        }
    });
}

// Remove pet from team slot
function removePetFromSlot(slotIndex) {
    if (selected3v3Pets[slotIndex]) {
        const petId = selected3v3Pets[slotIndex].id;

        // Remove from array
        selected3v3Pets.splice(slotIndex, 1);

        // Update card in grid
        const card = document.querySelector(`.selectable-pet-card[data-pet-id="${petId}"]`);
        if (card) {
            card.classList.remove('selected');
        }

        // Update slots display
        updateTeamSlots3v3();
    }
}

// Make functions globally accessible
window.toggle3v3Selection = toggle3v3Selection;
window.removePetFromSlot = removePetFromSlot;

// Start 3v3 battle with selected pets
async function start3v3Battle() {
    const btn = document.getElementById('btn-start-3v3');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Starting Battle...</span>';
    }

    try {
        // Check if 3 pets are selected
        if (selected3v3Pets.length !== 3) {
            // Fallback: use top 3 pets by power
            const userPets = window.userPets || [];
            const alivePets = userPets.filter(pet => pet.status !== 'DEAD');

            if (alivePets.length < 3) {
                if (typeof showToast === 'function') {
                    showToast('You need at least 3 alive pets for 3v3 battle!', 'warning');
                } else {
                    alert('You need at least 3 alive pets for 3v3 battle!');
                }
                resetButton();
                return;
            }

            // Sort by power and pick top 3
            const sortedPets = alivePets.sort((a, b) => {
                const powerA = (a.atk || 0) + (a.def || 0) + (a.hp || 0);
                const powerB = (b.atk || 0) + (b.def || 0) + (b.hp || 0);
                return powerB - powerA;
            });

            selected3v3Pets = sortedPets.slice(0, 3);
        }

        const teamPetIds = selected3v3Pets.map(p => p.id);

        console.log('🎮 Starting 3v3 with pets:', teamPetIds);

        // Call API to create battle (opponent_user_id 0 means AI/random opponent)
        const response = await fetch('api/router.php?action=start_battle_3v3', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pet_ids: teamPetIds,
                opponent_user_id: 0  // AI opponent
            })
        });

        const data = await response.json();

        if (data.success && data.battle_id) {
            // Save opponent name for battle page
            sessionStorage.setItem('opponent_name', data.opponent_name || 'Wild Trainer');

            // Redirect to battle page
            window.location.href = `battle_3v3.php?battle_id=${data.battle_id}`;
        } else {
            throw new Error(data.error || 'Failed to create battle');
        }

    } catch (error) {
        console.error('Error starting 3v3 battle:', error);
        if (typeof showToast === 'function') {
            showToast('Failed to start battle: ' + error.message, 'error');
        } else {
            alert('Failed to start battle: ' + error.message);
        }
        resetButton();
    }

    function resetButton() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-dragon"></i> <span>Enter 3v3 Arena</span>';
        }
    }
}

// Make function globally accessible
window.start3v3Battle = start3v3Battle;

// Initialize arena module
console.log('✓ Arena module loaded');

// ================================================
// ARENA STATS UPDATE FUNCTION
// ================================================
async function loadArenaStats() {
    // Show loading state
    const battlesEl = document.getElementById('arena-battles');
    const winsEl = document.getElementById('total-wins');
    const winRateEl = document.getElementById('win-rate');
    const streakEl = document.getElementById('current-streak');

    // Set loading indicators
    if (battlesEl) battlesEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    if (winsEl) winsEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    if (winRateEl) winRateEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    if (streakEl) streakEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        console.log('🔍 Fetching arena stats...');
        const response = await fetch('api/router.php?action=battle_history');

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('📊 API Response:', data);

        if (data.success) {
            const stats = data.stats || {};
            const wins = stats.wins || 0;
            const losses = stats.losses || 0;
            const streak = stats.current_streak || 0;
            const battlesRemaining = stats.battles_remaining !== undefined ? stats.battles_remaining : 3;

            console.log('✅ Stats parsed:', { wins, losses, streak, battlesRemaining });

            // Update stats bar
            updateArenaStats(wins, losses, streak);

            // Update battles remaining display
            if (battlesEl) {
                battlesEl.textContent = `${battlesRemaining} / 3`;
            }

            console.log('✅ Arena stats updated successfully');
        } else {
            console.error('❌ API returned success: false', data);
            throw new Error(data.error || 'Failed to load stats');
        }
    } catch (error) {
        console.error('❌ Error loading arena stats:', error);

        // Reset to default values on error
        if (battlesEl) battlesEl.textContent = '3 / 3';
        if (winsEl) winsEl.textContent = '0';
        if (winRateEl) winRateEl.textContent = '0%';
        if (streakEl) streakEl.textContent = '0';

        // Show error toast if available
        if (typeof showToast === 'function') {
            showToast('Failed to load arena stats', 'error');
        }
    }
}

function updateArenaStats(wins, losses, streak) {
    // Update wins
    const winsEl = document.getElementById('total-wins');
    if (winsEl) winsEl.textContent = wins || 0;

    // Calculate and update win rate
    const total = (wins || 0) + (losses || 0);
    const winRate = total > 0 ? Math.round((wins / total) * 100) : 0;
    const winRateEl = document.getElementById('win-rate');
    if (winRateEl) winRateEl.textContent = `${winRate}%`;

    // Update streak
    const streakEl = document.getElementById('current-streak');
    if (streakEl) streakEl.textContent = streak || 0;
}

// Initialize arena stats when tab is opened
document.addEventListener('DOMContentLoaded', () => {
    // Load stats when Arena tab is clicked
    const arenaTab = document.querySelector('[data-tab="arena"]');
    if (arenaTab) {
        arenaTab.addEventListener('click', () => {
            setTimeout(loadArenaStats, 100);
        });
    }

    // Load team selection when 3v3 Arena tab is clicked
    const arena3v3Tab = document.querySelector('[data-tab="arena3v3"]');
    if (arena3v3Tab) {
        arena3v3Tab.addEventListener('click', () => {
            // Reset selected pets when opening tab
            selected3v3Pets = [];
            setTimeout(() => {
                loadTeamSelection();
                updateTeamSlots3v3();
            }, 100);
        });
    }

    // Also load if already on arena tab
    const arenaContent = document.getElementById('arena');
    if (arenaContent && arenaContent.classList.contains('active')) {
        loadArenaStats();
    }

    // Also load 3v3 if already on that tab
    const arena3v3Content = document.getElementById('arena3v3');
    if (arena3v3Content && arena3v3Content.classList.contains('active')) {
        selected3v3Pets = [];
        loadTeamSelection();
    }

    // NOTE: Achievements tab listeners are now in ES6 module: js/pet/achievements.js
    //Reload stats whenever tab becomes visible (catches return from battle)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            const arenaContent = document.getElementById('arena');
            if (arenaContent && arenaContent.classList.contains('active')) {
                loadArenaStats();
            }

            const arena3v3Content = document.getElementById('arena3v3');
            if (arena3v3Content && arena3v3Content.classList.contains('active')) {
                loadTeamSelection();
            }
            // NOTE: Achievements visibility handler is now in ES6 module
        }
    });
});
