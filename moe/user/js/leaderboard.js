/**
 * MOE Pet System - Leaderboard JavaScript
 * Handles leaderboard loading and display
 */

const ASSETS_BASE = '/moe/assets/pets/';

/**
 * Initialize leaderboard tab
 */
function initLeaderboard() {
    loadPetLeaderboard();
}

/**
 * Load pet leaderboard from API
 */
async function loadPetLeaderboard() {
    const sortSelect = document.getElementById('lb-sort');
    const elementSelect = document.getElementById('lb-element');
    const listContainer = document.getElementById('leaderboard-list');

    if (!listContainer) {
        console.error('Leaderboard container not found');
        return;
    }

    const sort = sortSelect ? sortSelect.value : 'level';
    const element = elementSelect ? elementSelect.value : 'all';

    listContainer.innerHTML = '<div class="loading-spinner">Loading...</div>';

    try {
        const url = `api/router.php?action=get_pet_leaderboard&sort=${sort}&element=${element}&limit=15`;
        console.log('Fetching leaderboard:', url);

        const response = await fetch(url);
        const text = await response.text();

        // Try to parse JSON, handle errors
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', text.substring(0, 500));
            listContainer.innerHTML = '<div class="empty-state">Server error - check console</div>';
            return;
        }

        console.log('Leaderboard response:', data);

        if (data.success) {
            renderLeaderboard(data);
            populateElementFilter(data.available_elements);
        } else {
            console.error('API error:', data.error);
            listContainer.innerHTML = `<div class="empty-state">${data.error || 'Failed to load'}</div>`;
        }
    } catch (error) {
        console.error('Leaderboard fetch error:', error);
        listContainer.innerHTML = '<div class="empty-state">Network error</div>';
    }
}

/**
 * Render leaderboard list
 */
function renderLeaderboard(data) {
    const container = document.getElementById('leaderboard-list');
    const pets = data.leaderboard;

    if (!pets || pets.length === 0) {
        container.innerHTML = '<div class="empty-state">No pets found</div>';
        return;
    }

    container.innerHTML = pets.map(pet => {
        const rankClass = pet.rank <= 3 ? `rank-${pet.rank}` : '';
        const shinyClass = pet.is_shiny ? 'shiny' : '';
        const displayName = pet.nickname || pet.species_name;
        const imgPath = ASSETS_BASE + pet.current_image;

        // Determine main stat based on sort
        const sortValue = document.getElementById('lb-sort')?.value || 'level';
        let mainStat = '';
        let statLabel = '';

        switch (sortValue) {
            case 'wins':
                mainStat = pet.battle_wins;
                statLabel = 'Wins';
                break;
            case 'power':
                mainStat = pet.power_score;
                statLabel = 'Power';
                break;
            default:
                mainStat = `Lv.${pet.level}`;
                statLabel = 'Level';
        }

        return `
            <div class="lb-pet-card ${rankClass}">
                <div class="rank">${getRankIcon(pet.rank)}</div>
                <img class="pet-img" src="${imgPath}" 
                     onerror="this.src='../assets/placeholder.png'" alt="${displayName}">
                <div class="pet-info">
                    <div class="pet-name ${shinyClass}">${displayName} ${pet.is_shiny ? '✨' : ''}</div>
                    <div class="pet-meta">
                        <span class="element-badge ${pet.element?.toLowerCase()}">${pet.element || '?'}</span>
                        <span class="owner">${pet.owner_name}</span>
                    </div>
                </div>
                <div class="pet-stats">
                    <div class="stat-main">${mainStat}</div>
                    <div class="stat-label">${statLabel}</div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Get rank icon
 */
function getRankIcon(rank) {
    switch (rank) {
        case 1: return '\u{1F947}'; // Gold medal
        case 2: return '\u{1F948}'; // Silver medal
        case 3: return '\u{1F949}'; // Bronze medal
        default: return '#' + rank;
    }
}

/**
 * Populate element filter dropdown
 */
function populateElementFilter(elements) {
    const select = document.getElementById('lb-element');
    if (!select || !elements) return;

    // Keep current selection
    const currentValue = select.value;

    // Clear and rebuild
    select.innerHTML = '<option value="all">All Elements</option>';

    const elementIcons = {
        'fire': '[F]',
        'water': '[W]',
        'earth': '[E]',
        'air': '[A]',
        'light': '[L]',
        'dark': '[D]',
        'nature': '[N]',
        'electric': '[Z]'
    };

    elements.forEach(el => {
        const icon = elementIcons[el.toLowerCase()] || '[?]';
        const option = document.createElement('option');
        option.value = el;
        option.textContent = `${icon} ${el}`;
        select.appendChild(option);
    });

    // Restore selection
    if (currentValue) select.value = currentValue;
}

// Expose to global scope
window.loadPetLeaderboard = loadPetLeaderboard;
window.initLeaderboard = initLeaderboard;
