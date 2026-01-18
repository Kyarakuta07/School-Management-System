/**
 * MOE Pet System - Leaderboard JavaScript
 * Handles leaderboard loading and display
 */

var ASSETS_BASE = '/moe/assets/pets/';

/**
 * Initialize leaderboard tab
 */
function initLeaderboard() {
    loadPetLeaderboard();
}

/**
 * Load pet leaderboard from API
 */
function loadPetLeaderboard() {
    var sortSelect = document.getElementById('lb-sort');
    var elementSelect = document.getElementById('lb-element');
    var listContainer = document.getElementById('leaderboard-list');

    if (!listContainer) {
        console.error('Leaderboard container not found');
        return;
    }

    var sort = sortSelect ? sortSelect.value : 'level';
    var element = elementSelect ? elementSelect.value : 'all';

    listContainer.innerHTML = '<div class="loading-spinner">Loading...</div>';

    var url = 'api/router.php?action=get_pet_leaderboard&sort=' + sort + '&element=' + element + '&limit=15';
    console.log('Fetching leaderboard:', url);

    fetch(url)
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            console.log('Leaderboard response:', data);
            if (data.success) {
                renderLeaderboard(data);
                populateElementFilter(data.available_elements);
            } else {
                console.error('API error:', data.error);
                listContainer.innerHTML = '<div class="empty-state">' + (data.error || 'Failed to load') + '</div>';
            }
        })
        .catch(function (error) {
            console.error('Leaderboard fetch error:', error);
            listContainer.innerHTML = '<div class="empty-state">Network error</div>';
        });
}

/**
 * Render leaderboard list
 */
function renderLeaderboard(data) {
    var container = document.getElementById('leaderboard-list');
    var pets = data.leaderboard;

    if (!pets || pets.length === 0) {
        container.innerHTML = '<div class="empty-state">No pets found</div>';
        return;
    }

    var html = '';
    for (var i = 0; i < pets.length; i++) {
        var pet = pets[i];
        var rankClass = pet.rank <= 3 ? 'rank-' + pet.rank : '';
        var shinyClass = pet.is_shiny ? 'shiny' : '';
        var displayName = pet.nickname || pet.species_name;
        var imgPath = ASSETS_BASE + pet.current_image;
        var shinyMark = pet.is_shiny ? ' *' : '';

        var sortSelect = document.getElementById('lb-sort');
        var sortValue = sortSelect ? sortSelect.value : 'level';
        var mainStat = '';
        var statLabel = '';

        if (sortValue === 'wins') {
            mainStat = pet.battle_wins;
            statLabel = 'Wins';
        } else if (sortValue === 'power') {
            mainStat = pet.power_score;
            statLabel = 'Power';
        } else {
            mainStat = 'Lv.' + pet.level;
            statLabel = 'Level';
        }

        var elementClass = pet.element ? pet.element.toLowerCase() : '';

        html += '<div class="lb-pet-card ' + rankClass + '">';
        html += '<div class="rank">' + getRankIcon(pet.rank) + '</div>';
        html += '<img class="pet-img" src="' + imgPath + '" onerror="this.src=\'../assets/placeholder.png\'" alt="' + displayName + '">';
        html += '<div class="pet-info">';
        html += '<div class="pet-name ' + shinyClass + '">' + displayName + shinyMark + '</div>';
        html += '<div class="pet-meta">';
        html += '<span class="element-badge ' + elementClass + '">' + (pet.element || '?') + '</span>';
        html += '<span class="owner">' + pet.owner_name + '</span>';
        html += '</div>';
        html += '</div>';
        html += '<div class="pet-stats">';
        html += '<div class="stat-main">' + mainStat + '</div>';
        html += '<div class="stat-label">' + statLabel + '</div>';
        html += '</div>';
        html += '</div>';
    }

    container.innerHTML = html;
}

/**
 * Get rank icon
 */
function getRankIcon(rank) {
    if (rank === 1) return '1st';
    if (rank === 2) return '2nd';
    if (rank === 3) return '3rd';
    return '#' + rank;
}

/**
 * Populate element filter dropdown
 */
function populateElementFilter(elements) {
    var select = document.getElementById('lb-element');
    if (!select || !elements) return;

    var currentValue = select.value;

    select.innerHTML = '<option value="all">All Elements</option>';

    for (var i = 0; i < elements.length; i++) {
        var el = elements[i];
        var option = document.createElement('option');
        option.value = el;
        option.textContent = el;
        select.appendChild(option);
    }

    if (currentValue) select.value = currentValue;
}

// Expose to global scope
window.loadPetLeaderboard = loadPetLeaderboard;
window.initLeaderboard = initLeaderboard;
