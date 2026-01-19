/**
 * MOE Pet System - Premium Leaderboard JavaScript
 * Enhanced with podium, tabs, and element pills
 */

var ASSETS_BASE = '/moe/assets/pets/';
var currentSort = 'level';
var currentElement = 'all';

/**
 * Initialize leaderboard tab
 */
function initLeaderboard() {
    setupTabListeners();
    setupElementPillListeners();
    loadPetLeaderboard();
}

/**
 * Setup tab click listeners
 */
function setupTabListeners() {
    var tabs = document.querySelectorAll('.lb-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            currentSort = tab.dataset.sort;

            // Update hidden select for legacy support
            var sortSelect = document.getElementById('lb-sort');
            if (sortSelect) sortSelect.value = currentSort;

            loadPetLeaderboard();
        });
    });
}

/**
 * Setup element pill click listeners
 */
function setupElementPillListeners() {
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('element-pill')) {
            var pills = document.querySelectorAll('.element-pill');
            pills.forEach(function (p) { p.classList.remove('active'); });
            e.target.classList.add('active');
            currentElement = e.target.dataset.element;

            // Update hidden select for legacy support
            var elementSelect = document.getElementById('lb-element');
            if (elementSelect) elementSelect.value = currentElement;

            loadPetLeaderboard();
        }
    });
}

/**
 * Load pet leaderboard from API
 */
function loadPetLeaderboard() {
    var podiumContainer = document.getElementById('podium-section');
    var listContainer = document.getElementById('leaderboard-list');

    if (!listContainer) {
        console.error('Leaderboard container not found');
        return;
    }

    // Show loading
    if (podiumContainer) podiumContainer.innerHTML = '';
    listContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading champions...</span></div>';

    var url = 'api/router.php?action=get_pet_leaderboard&sort=' + currentSort + '&element=' + currentElement + '&limit=15';

    fetch(url)
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                renderPodium(data.leaderboard);
                renderLeaderboard(data.leaderboard);
                populateElementPills(data.available_elements);
            } else {
                listContainer.innerHTML = '<div class="empty-state">' + (data.error || 'Failed to load') + '</div>';
            }
        })
        .catch(function (error) {
            console.error('Leaderboard fetch error:', error);
            listContainer.innerHTML = '<div class="empty-state">Network error</div>';
        });
}

/**
 * Render top 3 podium
 */
function renderPodium(pets) {
    var container = document.getElementById('podium-section');
    if (!container || !pets || pets.length === 0) return;

    var top3 = pets.slice(0, 3);
    var html = '';

    var crowns = ['👑', '🥈', '🥉'];
    var statLabels = { level: 'Level', wins: 'Wins', power: 'Power' };

    for (var i = 0; i < top3.length; i++) {
        var pet = top3[i];
        var rank = i + 1;
        var displayName = pet.nickname || pet.species_name;
        var imgPath = ASSETS_BASE + pet.current_image;

        var mainStat = '';
        if (currentSort === 'wins') {
            mainStat = pet.battle_wins + ' wins';
        } else if (currentSort === 'power') {
            mainStat = pet.power_score + ' pwr';
        } else {
            mainStat = 'Lv.' + pet.level;
        }

        html += '<div class="podium-pet rank-' + rank + '">';
        html += '<div class="podium-avatar">';
        html += '<span class="podium-crown">' + crowns[i] + '</span>';
        html += '<img class="podium-img" src="' + imgPath + '" onerror="this.src=\'../assets/placeholder.png\'" alt="' + displayName + '">';
        html += '</div>';
        html += '<div class="podium-name">' + displayName + '</div>';
        html += '<div class="podium-stat">' + mainStat + '</div>';
        html += '<div class="podium-stand">' + rank + '</div>';
        html += '</div>';
    }

    container.innerHTML = html;
}

/**
 * Render leaderboard list (rank 4+)
 */
function renderLeaderboard(pets) {
    var container = document.getElementById('leaderboard-list');

    if (!pets || pets.length === 0) {
        container.innerHTML = '<div class="empty-state">No pets found</div>';
        return;
    }

    // Skip top 3 (they're in podium)
    var rest = pets.slice(3);

    if (rest.length === 0) {
        container.innerHTML = '<div class="empty-state" style="opacity: 0.5; font-size: 0.85rem;">Top 3 shown above</div>';
        return;
    }

    var html = '';
    for (var i = 0; i < rest.length; i++) {
        var pet = rest[i];
        var rank = i + 4;
        var shinyClass = pet.is_shiny ? 'shiny' : '';
        var displayName = pet.nickname || pet.species_name;
        var imgPath = ASSETS_BASE + pet.current_image;
        var shinyMark = pet.is_shiny ? ' ✨' : '';

        var mainStat = '';
        var statLabel = '';
        if (currentSort === 'wins') {
            mainStat = pet.battle_wins;
            statLabel = 'Wins';
        } else if (currentSort === 'power') {
            mainStat = pet.power_score;
            statLabel = 'Power';
        } else {
            mainStat = 'Lv.' + pet.level;
            statLabel = 'Level';
        }

        var elementClass = pet.element ? pet.element.toLowerCase() : '';

        html += '<div class="lb-pet-card">';
        html += '<div class="rank">#' + rank + '</div>';
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
 * Populate element pills
 */
function populateElementPills(elements) {
    var container = document.getElementById('element-pills');
    if (!container || !elements) return;

    var html = '<button class="element-pill ' + (currentElement === 'all' ? 'active' : '') + '" data-element="all">All</button>';

    for (var i = 0; i < elements.length; i++) {
        var el = elements[i];
        var isActive = currentElement === el ? 'active' : '';
        html += '<button class="element-pill ' + isActive + '" data-element="' + el + '">' + el + '</button>';
    }

    container.innerHTML = html;
}

/**
 * Load battle history tab
 */
function loadBattleHistoryTab() {
    var listContainer = document.getElementById('history-list');
    var winsEl = document.getElementById('total-wins');
    var lossesEl = document.getElementById('total-losses');
    var streakEl = document.getElementById('win-streak');

    if (!listContainer) return;

    listContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading...</span></div>';

    fetch('api/router.php?action=battle_history')
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success) {
                listContainer.innerHTML = '<div class="empty-history">Failed to load history</div>';
                return;
            }

            if (winsEl) winsEl.textContent = data.stats.wins || 0;
            if (lossesEl) lossesEl.textContent = data.stats.losses || 0;
            if (streakEl) streakEl.textContent = data.stats.current_streak || 0;

            var history = data.history || [];
            if (history.length === 0) {
                listContainer.innerHTML = '<div class="empty-history">No battles yet!</div>';
                return;
            }

            var html = history.map(function (battle) {
                var date = new Date(battle.created_at).toLocaleDateString('id-ID');
                var won = battle.won ? true : false;

                return '<div class="history-item">' +
                    '<div class="history-pets">' +
                    '<span class="history-pet-name">' + (battle.pet_name || 'Your Pet') + '</span>' +
                    '<span class="history-vs">VS</span>' +
                    '<span class="history-pet-name">' + (battle.opponent_name || 'Enemy') + '</span>' +
                    '</div>' +
                    '<div class="history-result-wrap">' +
                    '<span class="history-result ' + (won ? 'win' : 'lose') + '">' + (won ? '✓ WIN' : '✗ LOSE') + '</span>' +
                    '<span class="history-date">' + date + '</span>' +
                    '</div>' +
                    '</div>';
            }).join('');

            listContainer.innerHTML = html;
        })
        .catch(function (error) {
            listContainer.innerHTML = '<div class="empty-history">Network error</div>';
        });
}

// Expose to global scope
window.loadPetLeaderboard = loadPetLeaderboard;
window.initLeaderboard = initLeaderboard;
window.loadBattleHistoryTab = loadBattleHistoryTab;
