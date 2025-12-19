// ================================================
// COLLECTION PHASE 2: SEARCH, FILTER, SORT
// ================================================

// State variables
let currentFilter = 'all';
let currentSort = 'level-desc';
let searchQuery = '';

// Initialize search input listener
function initCollectionSearch() {
    const searchInput = document.getElementById('pet-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase();
            renderCollection();
        });
    }
}

// Filter collection by element
function filterCollection(filter) {
    currentFilter = filter;

    // Update active pill
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.classList.remove('active');
    });
    const targetPill = document.querySelector(`[data-filter="${filter}"]`);
    if (targetPill) {
        targetPill.classList.add('active');
    }

    renderCollection();
}

// Sort collection
function sortCollection(sortType) {
    currentSort = sortType;
    renderCollection();
}

// Get filtered and sorted pets
function getFilteredPets() {
    let filtered = [...window.userPets];

    // Apply search
    if (searchQuery) {
        filtered = filtered.filter(pet => {
            const name = (pet.nickname || pet.species_name).toLowerCase();
            return name.includes(searchQuery);
        });
    }

    // Apply element filter
    if (currentFilter !== 'all') {
        filtered = filtered.filter(pet => pet.element.toLowerCase() === currentFilter);
    }

    // Apply sort
    const rarityOrder = { 'Legendary': 4, 'Epic': 3, 'Rare': 2, 'Common': 1 };

    switch (currentSort) {
        case 'level-desc':
            filtered.sort((a, b) => b.level - a.level);
            break;
        case 'level-asc':
            filtered.sort((a, b) => a.level - b.level);
            break;
        case 'rarity-desc':
            filtered.sort((a, b) => (rarityOrder[b.rarity] || 0) - (rarityOrder[a.rarity] || 0));
            break;
        case 'name-asc':
            filtered.sort((a, b) => {
                const nameA = (a.nickname || a.species_name).toLowerCase();
                const nameB = (b.nickname || b.species_name).toLowerCase();
                return nameA.localeCompare(nameB);
            });
            break;
        case 'recent':
            filtered.sort((a, b) => b.id - a.id);
            break;
    }

    return filtered;
}

// Update stats panel
function updateCollectionStats() {
    const stats = {
        total: window.userPets.length,
        common: 0,
        rare: 0,
        epic: 0,
        legendary: 0,
        shiny: 0
    };

    window.userPets.forEach(pet => {
        const rarity = pet.rarity.toLowerCase();
        stats[rarity]++;
        if (pet.is_shiny) stats.shiny++;
    });

    // Update UI elements
    const totalEl = document.getElementById('stat-total');
    const raritiesEl = document.getElementById('stat-rarities');
    const shinyEl = document.getElementById('stat-shiny');

    if (totalEl) totalEl.textContent = stats.total;
    if (raritiesEl) raritiesEl.textContent = `${stats.common}/${stats.rare}/${stats.epic}/${stats.legendary}`;
    if (shinyEl) shinyEl.textContent = stats.shiny;
}

