/**
 * MOE Pet System - Leaderboard V2 (Premium)
 * Handles leaderboard pods, tabs, and element classification
 */

console.error('[LB] leaderboard.js STARTING UP');
// alert('Leaderboard Script Loaded!'); // Debug alert

var ASSETS_BASE = '/moe/assets/pets/';
var currentSort = 'level';
var currentElement = 'all';

function initLeaderboard() {
    console.log('[LB] initLeaderboard called');
    try {
        setupLeaderboardTabs();
        setupElementPills();
        loadPetLeaderboard();
    } catch (e) {
        console.error('[LB] initLeaderboard error:', e);
    }
}

function setupLeaderboardTabs() {
    var tabs = document.querySelectorAll('.lb-tab');
    if (tabs.length === 0) console.warn('[LB] No tabs found');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            console.log('[LB] Tab clicked:', tab.dataset.sort);
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            currentSort = tab.dataset.sort;
            var sortSelect = document.getElementById('lb-sort');
            if (sortSelect) sortSelect.value = currentSort;
            loadPetLeaderboard();
        });
    });
}

function setupElementPills() {
    var container = document.getElementById('element-pills');
    if (!container) return;

    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('element-pill')) {
            console.log('[LB] Pill clicked:', e.target.dataset.element);
            var pills = container.querySelectorAll('.element-pill');
            pills.forEach(function (p) { p.classList.remove('active'); });
            e.target.classList.add('active');
            currentElement = e.target.dataset.element;
            loadPetLeaderboard();
        }
    });
}

function loadPetLeaderboard() {
    console.log('[LB] Loading leaderboard. Sort:', currentSort, 'Element:', currentElement);
    var podiumContainer = document.getElementById('podium-section');
    var listContainer = document.getElementById('leaderboard-list');

    if (!listContainer) {
        console.error('[LB] List container not found');
        return;
    }

    // Don't clear list completely to avoid flickering if possible, but for now clear
    if (podiumContainer) podiumContainer.innerHTML = '';
    listContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading champions...</span></div>';

    var url = '/user/api/router.php?action=get_pet_leaderboard&sort=' + currentSort + '&element=' + currentElement + '&limit=15';
    console.log('[LB] Fetch URL:', url);

    fetch(url)
        .then(function (r) {
            console.log('[LB] Response:', r.status);
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            console.log('[LB] Data success:', data.success);
            if (data.success) {
                renderPodium(data.leaderboard);
                renderLeaderboard(data.leaderboard);
                populateElementPills(data.available_elements);
            } else {
                listContainer.innerHTML = '<div class="empty-state">' + (data.error || 'Failed to load') + '</div>';
            }
        })
        .catch(function (e) {
            console.error('[LB] Fetch error:', e);
            listContainer.innerHTML = '<div class="empty-state">Error: ' + e.message + '<br><button onclick="loadPetLeaderboard()" style="margin-top:10px;padding:8px 16px;cursor:pointer;">Retry</button></div>';
        });
}

function renderPodium(pets) {
    var container = document.getElementById('podium-section');
    if (!container || !pets || pets.length === 0) return;

    var top3 = pets.slice(0, 3);
    var crowns = ['👑', '🥈', '🥉'];
    var html = '';

    for (var i = 0; i < top3.length; i++) {
        var pet = top3[i];
        var rank = i + 1;
        var displayName = pet.nickname || pet.species_name;
        var imgPath = ASSETS_BASE + pet.current_image;
        var mainStat = getStatLabel(pet);

        html += '<div class="podium-pet rank-' + rank + '">';
        html += '<div class="podium-avatar"><span class="podium-crown">' + crowns[i] + '</span>';
        html += '<img class="podium-img" src="' + imgPath + '" onerror="this.src=\'../assets/placeholder.png\'"></div>';
        html += '<div class="podium-name">' + displayName + '</div>';
        html += '<div class="podium-stat">' + mainStat + '</div>';
        html += '<div class="podium-stand">' + rank + '</div></div>';
    }

    container.innerHTML = html;
}

function renderLeaderboard(pets) {
    var container = document.getElementById('leaderboard-list');
    if (!pets || pets.length === 0) { container.innerHTML = '<div class="empty-state">No pets found</div>'; return; }

    var rest = pets.slice(3);
    if (rest.length === 0) {
        container.innerHTML = '<div class="empty-state" style="opacity:0.5; font-size: 0.85rem;">Top 3 shown above</div>';
        return;
    }

    var html = '';
    for (var i = 0; i < rest.length; i++) {
        var pet = rest[i];
        var rank = i + 4;

        var displayName = pet.nickname || pet.species_name;
        var imgPath = ASSETS_BASE + pet.current_image;
        var mainStat = getStatValue(pet);
        var statLabel = getStatName();
        var elClass = pet.element ? pet.element.toLowerCase() : '';
        var shinyClass = pet.is_shiny ? 'shiny' : '';
        var shinyMark = pet.is_shiny ? ' ✨' : '';

        html += '<div class="lb-pet-card">';
        html += '<div class="rank">#' + rank + '</div>';
        html += '<img class="pet-img" src="' + imgPath + '" onerror="this.src=\'../assets/placeholder.png\'">';
        html += '<div class="pet-info"><div class="pet-name ' + shinyClass + '">' + displayName + shinyMark + '</div>';
        html += '<div class="pet-meta"><span class="element-badge ' + elClass + '">' + (pet.element || '?') + '</span>';
        html += '<span class="owner">' + pet.owner_name + '</span></div></div>';
        html += '<div class="pet-stats"><div class="stat-main">' + mainStat + '</div><div class="stat-label">' + statLabel + '</div></div></div>';
    }

    container.innerHTML = html;
}

function getStatLabel(pet) {
    if (currentSort === 'wins') return pet.battle_wins + ' wins';
    if (currentSort === 'power') return pet.power_score + ' pwr';
    return 'Lv.' + pet.level;
}

function getStatValue(pet) {
    if (currentSort === 'wins') return pet.battle_wins;
    if (currentSort === 'power') return pet.power_score;
    return 'Lv.' + pet.level;
}

function getStatName() {
    if (currentSort === 'wins') return 'Wins';
    if (currentSort === 'power') return 'Power';
    return 'Level';
}

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

// BATTLE HISTORY TAB
function loadBattleHistoryTab() {
    var listContainer = document.getElementById('history-list');
    var winsEl = document.getElementById('total-wins');
    var lossesEl = document.getElementById('total-losses');
    var streakEl = document.getElementById('win-streak');

    if (!listContainer) return;

    listContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading...</span></div>';

    // Absolute path is safer
    fetch('/moe/user/api/router.php?action=battle_history')
        .then(function (r) { return r.json(); })
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
                var date = new Date(battle.created_at).toLocaleDateString();
                var won = battle.won ? true : false;
                return '<div class="history-item"><div class="history-pets"><span class="history-pet-name">' + (battle.pet_name || 'Pet') + '</span><span class="history-vs">VS</span><span class="history-pet-name">' + (battle.opponent_name || 'Enemy') + '</span></div><div class="history-result-wrap"><span class="history-result ' + (won ? 'win' : 'lose') + '">' + (won ? '✓ WIN' : '✗ LOSE') + '</span><span class="history-date">' + date + '</span></div></div>';
            }).join('');

            listContainer.innerHTML = html;
        })
        .catch(function (e) {
            console.warn('[History] Load error:', e);
            listContainer.innerHTML = '<div class="empty-history">Network error</div>';
        });
}

// EXPOSE GLOBALS
window.loadPetLeaderboard = loadPetLeaderboard;
window.initLeaderboard = initLeaderboard;
window.loadBattleHistoryTab = loadBattleHistoryTab;
console.log('[LB] Globals exposed. type of initLeaderboard:', typeof window.initLeaderboard);
