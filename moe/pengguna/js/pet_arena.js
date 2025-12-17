/**
 * MOE Pet System - Arena & Achievements Module
 * Mediterranean of Egypt Virtual Pet Companion
 * Handles Arena battles and Achievements/Badges
 */

// ================================================
// ARENA SYSTEM
// ================================================

// Load opponents for arena battles
async function loadOpponents() {
    const container = document.getElementById('opponents-grid');
    if (!container) {
        console.error('opponents-grid element not found');
        return;
    }

    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Finding opponents...</p></div>';

    try {
        const response = await fetch('api/router.php?action=get_opponents');
        const data = await response.json();

        if (data.success && data.opponents && data.opponents.length > 0) {
            container.innerHTML = data.opponents.map(opp => `
                <div class="opponent-card">
                    <img src="${ASSETS_BASE}${opp.img_adult}" alt="${opp.species_name}" class="opponent-img"
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

// Start battle with selected opponent
function startBattle(defenderPetId) {
    // Note: activePet is defined in main pet.js
    if (!window.activePet) {
        showToast('You need an active pet to battle!', 'warning');
        return;
    }

    if (window.activePet.status === 'DEAD') {
        showToast('Cannot battle with a dead pet!', 'error');
        return;
    }

    // Redirect to battle arena page
    window.location.href = `battle_arena.php?attacker_id=${window.activePet.id}&defender_id=${defenderPetId}`;
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
