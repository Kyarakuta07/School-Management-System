/**
 * MOE Pet System - Arena & Achievements Module
 * Mediterranean of Egypt Virtual Pet Companion
 * Handles Arena battles and Achievements/Badges
 */

// ================================================
// CONSTANTS
// ================================================
const ASSETS_BASE = '../assets/pets/';

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
                        <img src="${ASSETS_BASE}${opp.img_adult}" alt="${opp.display_name}"
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
// ================================================

// Load achievements/badges
async function loadAchievements() {
    const container = document.getElementById('achievements-grid');
    if (!container) {
        console.error('achievements-grid element not found');
        return;
    }

    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading achievements...</p></div>';

    try {
        const response = await fetch('api/router.php?action=get_achievements');
        const data = await response.json();

        if (data.success && data.achievements && data.achievements.length > 0) {
            container.innerHTML = data.achievements.map(ach => {
                const progress = ach.current_progress || 0;
                const total = ach.target_value || 100;
                const percentage = Math.min((progress / total) * 100, 100);
                const isComplete = ach.is_completed || progress >= total;

                return `
                    <div class="achievement-card ${isComplete ? 'completed' : ''}">
                        <div class="achievement-icon">
                            <i class="fas ${ach.icon || 'fa-trophy'}"></i>
                        </div>
                        <div class="achievement-info">
                            <h4 class="achievement-name">${ach.name}</h4>
                            <p class="achievement-desc">${ach.description}</p>
                            <div class="achievement-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${percentage}%"></div>
                                </div>
                                <span class="progress-text">${progress} / ${total}</span>
                            </div>
                            ${ach.reward_gold ? `<div class="achievement-reward"><i class="fas fa-coins"></i> ${ach.reward_gold}</div>` : ''}
                        </div>
                        ${isComplete ? '<div class="achievement-badge"><i class="fas fa-check"></i></div>' : ''}
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = `
                <div class="empty-message">
                    <i class="fas fa-medal"></i>
                    <p>No achievements yet. Complete challenges to earn badges!</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading achievements:', error);
        container.innerHTML = '<div class="empty-message">Failed to load achievements</div>';
    }
}

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
        // Get user's pets from main pet.js
        const userPets = window.userPets;

        if (!userPets || userPets.length === 0) {
            container.innerHTML = `
                <div class="empty-message">
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
                <div class="empty-message">
                    <i class="fas fa-heart-broken"></i>
                    <p>You need at least 3 alive pets for team battles!</p>
                    <p class="text-small">You have ${alivePets.length} alive pet(s). Heal or get more pets.</p>
                </div>
            `;
            return;
        }

        // Render team selection
        container.innerHTML = `
            <div class="team-info">
                <p class="team-instruction">
                    <i class="fas fa-info-circle"></i>
                    Select 3 pets for your team. Battles use your top 3 strongest pets.
                </p>
            </div>
            <div class="team-grid">
                ${alivePets.slice(0, 6).map((pet, index) => `
                    <div class="team-pet-card ${index < 3 ? 'team-member' : ''}">
                        <img src="${ASSETS_BASE}${pet.img_adult}" 
                             alt="${pet.nickname || pet.species_name}"
                             onerror="this.src='../assets/placeholder.png'">
                        <div class="team-pet-info">
                            <h4>${pet.nickname || pet.species_name}</h4>
                            <div class="team-pet-stats">
                                <span class="stat-label">Lv.${pet.level}</span>
                                <span class="element-badge ${pet.element.toLowerCase()}">${pet.element}</span>
                            </div>
                            <div class="team-pet-combat">
                                <span title="Attack"><i class="fas fa-sword"></i> ${pet.atk}</span>
                                <span title="Defense"><i class="fas fa-shield"></i> ${pet.def}</span>
                                <span title="HP"><i class="fas fa-heart"></i> ${pet.hp}</span>
                            </div>
                        </div>
                        ${index < 3 ? '<div class="team-badge">Team</div>' : ''}
                    </div>
                `).join('')}
            </div>
            <div class="team-summary">
                <p><strong>Team Power:</strong> ${alivePets.slice(0, 3).reduce((sum, p) => sum + (p.atk + p.def + p.hp), 0)}</p>
            </div>
        `;

    } catch (error) {
        console.error('Error loading team selection:', error);
        container.innerHTML = '<div class="empty-message">Failed to load team selection</div>';
    }
}


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

    // Also load if already on arena tab
    const arenaContent = document.getElementById('arena');
    if (arenaContent && arenaContent.classList.contains('active')) {
        loadArenaStats();
    }

    //Reload stats whenever tab becomes visible (catches return from battle)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            const arenaContent = document.getElementById('arena');
            if (arenaContent && arenaContent.classList.contains('active')) {
                loadArenaStats();
            }
        }
    });
});
