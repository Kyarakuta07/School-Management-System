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
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase();
            applyFilters();
        });
        searchInitialized = true;
    } else {
        console.warn('pet-search input not found');
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

    applyFilters();
}

// Sort collection
function sortCollection(sortType) {
    currentSort = sortType;
    applyFilters();
}

// Debounce timer for applyFilters
let applyFiltersTimeout = null;

// Apply all filters and sorting to the grid directly
function applyFilters() {
    clearTimeout(applyFiltersTimeout);
    applyFiltersTimeout = setTimeout(() => {
        if (window.renderCollection) {
            window.renderCollection();
        }
    }, 100);
}

// Get filtered and sorted pets
function getFilteredPets() {
    if (!window.userPets) {
        console.warn('[Collection] window.userPets is missing');
        return [];
    }

    let filtered = [...window.userPets];

    // Apply search
    if (searchQuery) {
        filtered = filtered.filter(pet => {
            const nickname = pet.nickname || '';
            const species = pet.species_name || '';
            const name = (nickname || species).toLowerCase();
            return name.includes(searchQuery);
        });
    }

    // Apply element filter
    if (currentFilter !== 'all') {
        filtered = filtered.filter(pet => {
            if (!pet.element) return false;
            return pet.element.toLowerCase() === currentFilter;
        });
    }

    // Apply sort
    const rarityOrder = { 'Legendary': 4, 'Epic': 3, 'Rare': 2, 'Common': 1 };

    switch (currentSort) {
        case 'level-desc':
            filtered.sort((a, b) => (parseInt(b.level) || 0) - (parseInt(a.level) || 0));
            break;
        case 'level-asc':
            filtered.sort((a, b) => (parseInt(a.level) || 0) - (parseInt(b.level) || 0));
            break;
        case 'rarity-desc':
            filtered.sort((a, b) => (rarityOrder[b.rarity] || 0) - (rarityOrder[a.rarity] || 0));
            break;
        case 'name-asc':
            filtered.sort((a, b) => {
                const nameA = (a.nickname || a.species_name || '').toLowerCase();
                const nameB = (b.nickname || b.species_name || '').toLowerCase();
                return nameA.localeCompare(nameB);
            });
            break;
        case 'recent':
            filtered.sort((a, b) => (parseInt(b.id) || 0) - (parseInt(a.id) || 0));
            break;
    }

    return filtered;
}

// Update stats panel
function updateCollectionStats() {
    const stats = {
        total: 0,
        common: 0,
        rare: 0,
        epic: 0,
        legendary: 0,
        shiny: 0
    };

    if (!window.userPets) return;

    stats.total = window.userPets.length;

    window.userPets.forEach(pet => {
        if (!pet.rarity) return;
        const rarity = pet.rarity.toLowerCase();
        if (stats.hasOwnProperty(rarity)) {
            stats[rarity]++;
        }
        if (pet.is_shiny == 1 || pet.is_shiny === true) {
            stats.shiny++;
        }
    });

    const totalEl = document.getElementById('stat-total');
    const raritiesEl = document.getElementById('stat-rarities');
    const shinyEl = document.getElementById('stat-shiny');

    if (totalEl) totalEl.textContent = stats.total;
    if (raritiesEl) raritiesEl.textContent = `${stats.common}/${stats.rare}/${stats.epic}/${stats.legendary}`;
    if (shinyEl) shinyEl.textContent = stats.shiny;
}

// Expose functions to window
window.getFilteredPets = getFilteredPets;
window.filterCollection = filterCollection;
window.sortCollection = sortCollection;
window.initCollectionSearch = initCollectionSearch;
window.updateCollectionStats = updateCollectionStats;
