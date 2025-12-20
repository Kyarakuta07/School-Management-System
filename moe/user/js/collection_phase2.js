// ================================================
// COLLECTION PHASE 2: SEARCH, FILTER, SORT
// ================================================

// State variables
let currentFilter = 'all';
let currentSort = 'level-desc';
let searchQuery = '';

// Initialize search input listener
let searchInitialized = false;

function initCollectionSearch() {
    // Prevent multiple initializations
    if (searchInitialized) return;

    const searchInput = document.getElementById('pet-search');
    if (searchInput) {
        console.log('✓ Collection search initialized');
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase();
            console.log('Search query:', searchQuery);
            if (typeof window.renderCollection === 'function') {
                window.renderCollection();
            }
        });
        searchInitialized = true;
    } else {
        console.warn('pet-search input not found');
    }
}

// Filter collection by element
function filterCollection(filter) {
    console.log('Filter collection:', filter);
    currentFilter = filter;

    // Update active pill
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.classList.remove('active');
    });
    const targetPill = document.querySelector(`[data-filter="${filter}"]`);
    if (targetPill) {
        targetPill.classList.add('active');
    }

    console.log('filterCollection: window.renderCollection available?', typeof window.renderCollection);
    if (typeof window.renderCollection === 'function') {
        window.renderCollection();
    } else {
        console.error('window.renderCollection is not a function!');
    }
}

// Sort collection
function sortCollection(sortType) {
    console.log('Sort collection:', sortType);
    currentSort = sortType;
    if (typeof window.renderCollection === 'function') {
        window.renderCollection();
    }
}

// Get filtered and sorted pets
function getFilteredPets() {
    console.log('getFilteredPets called - searchQuery:', searchQuery, 'currentFilter:', currentFilter);
    let filtered = [...window.userPets];
    console.log('Initial pets count:', filtered.length);

    // Apply search
    if (searchQuery) {
        filtered = filtered.filter(pet => {
            const name = (pet.nickname || pet.species_name).toLowerCase();
            return name.includes(searchQuery);
        });
        console.log('After search filter:', filtered.length);
    }

    // Apply element filter
    if (currentFilter !== 'all') {
        filtered = filtered.filter(pet => pet.element.toLowerCase() === currentFilter);
        console.log('After element filter:', filtered.length);
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

// Expose functions to window for ES6 module access
window.getFilteredPets = getFilteredPets;
window.filterCollection = filterCollection;
window.sortCollection = sortCollection;
window.initCollectionSearch = initCollectionSearch;
window.updateCollectionStats = updateCollectionStats;
