<!-- JavaScript (with cache busting) -->
<script src="<?= asset('user/js/pixi_bg.js', '../') ?>"></script>
<script type="module" src="<?= asset('user/js/pet/main.js', '../') ?>"></script>
<script src="<?= asset('user/js/pixi_pet.js', '../') ?>"></script>
<script src="<?= asset('user/js/pet_animations.js', '../') ?>"></script>
<script src="<?= asset('user/js/pet_hardcore_update.js', '../') ?>"></script>

<!-- Arena & Achievements Module -->
<script src="<?= asset('user/js/pet_arena.js', '../') ?>"></script>

<!-- Collection Phase 2 (Search, Filter, Sort) -->
<script src="<?= asset('user/js/collection_phase2.js', '../') ?>"></script>

<!-- Sanctuary War -->
<script src="<?= asset('user/js/sanctuary_war.js', '../') ?>"></script>

<!-- Leaderboard (inline to avoid encoding issues) -->
<script>
    var ASSETS_BASE = '/moe/assets/pets/';
    var currentSort = 'level';
    var currentElement = 'all';
 function initLeaderboard() {
        setupLeaderboardTabs();
        setupElementPills();
        loadPetLeaderboard();
    }

   function setupLeaderboardTabs() {
        var tabs = document.querySelectorAll('.lb-tab');
        tabs.forEach(function (tab) {
            tab.onclick = function () {
                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                currentSort = tab.dataset.sort;
                var sortSelect = document.getElementById('lb-sort');
                if (sortSelect) sortSelect.value = currentSort;
                loadPetLeaderboard();
            };
        });
    }

    function setupElementPills() {
        var container = document.getElementById('element-pills');
        if (container) {
            container.onclick = function (e) {
                if (e.target.classList.contains('element-pill')) {
                    var pills = container.querySelectorAll('.element-pill');
                    pills.forEach(function (p) { p.classList.remove('active'); });
                    e.target.classList.add('active');
                    currentElement = e.target.dataset.element;
                    loadPetLeaderboard();
                }
            };
        }
    }

    function loadPetLeaderboard() {
        console.log('[LB] loadPetLeaderboard called');
        var podiumContainer = document.getElementById('podium-section');
        var listContainer = document.getElementById('leaderboard-list');
        
        if (!listContainer) { 
            console.error('[LB] Container not found'); 
            return; 
        }

        if (podiumContainer) podiumContainer.innerHTML = '';
        listContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading champions...</span></div>';

        // Simple URL
        var url = '/moe/user/api/router.php?action=get_pet_leaderboard&sort=' + currentSort + '&element=' + currentElement + '&limit=15';
        console.log('[LB] Fetching:', url);
        
        fetch(url)
            .then(function(r) { 
                console.log('[LB] Response:', r.status);
                return r.json(); 
            })
            .then(function(data) {
                console.log('[LB] Data:', data);
                if (data.success) {
                    renderPodium(data.leaderboard);
                    renderLeaderboard(data.leaderboard);
                    populateElementPills(data.available_elements);
                } else {
                    listContainer.innerHTML = '<div class="empty-state">' + (data.error || 'Failed') + '</div>';
                }
            })
            .catch(function(e) {
                console.error('[LB] Error:', e);
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
            var mainStat = currentSort === 'wins' ? pet.battle_wins + ' wins' : (currentSort === 'power' ? pet.power_score + ' pwr' : 'Lv.' + pet.level);
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
        if (rest.length === 0) { container.innerHTML = '<div class="empty-state" style="opacity:0.5">Top 3 shown above</div>'; return; }
        var html = '';
        for (var i = 0; i < rest.length; i++) {
            var pet = rest[i];
            var rank = i + 4;
            var displayName = pet.nickname || pet.species_name;
            var imgPath = ASSETS_BASE + pet.current_image;
            var mainStat = currentSort === 'wins' ? pet.battle_wins : (currentSort === 'power' ? pet.power_score : 'Lv.' + pet.level);
            var statLabel = currentSort === 'wins' ? 'Wins' : (currentSort === 'power' ? 'Power' : 'Level');
            var elClass = pet.element ? pet.element.toLowerCase() : '';
            var shinyClass = pet.is_shiny ? 'shiny' : '';
            html += '<div class="lb-pet-card">';
            html += '<div class="rank">#' + rank + '</div>';
            html += '<img class="pet-img" src="' + imgPath + '" onerror="this.src=\'../assets/placeholder.png\'">';
            html += '<div class="pet-info"><div class="pet-name ' + shinyClass + '">' + displayName + '</div>';
            html += '<div class="pet-meta"><span class="element-badge ' + elClass + '">' + (pet.element || '?') + '</span>';
            html += '<span class="owner">' + pet.owner_name + '</span></div></div>';
            html += '<div class="pet-stats"><div class="stat-main">' + mainStat + '</div><div class="stat-label">' + statLabel + '</div></div></div>';
        }
        container.innerHTML = html;
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

    window.loadPetLeaderboard = loadPetLeaderboard;
    window.initLeaderboard = initLeaderboard;
</script>

<!-- Arena Integration Script -->
<script>
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', () => {
        // Listen for ALL tab clicks (not just main-tab)
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', function () {
                const targetTab = this.dataset.tab;

                // Load arena opponents when arena tab is clicked
                if (targetTab === 'arena') {
                    setTimeout(() => loadOpponents(), 100);
                }

                // Load achievements when achievements tab is clicked
                if (targetTab === 'achievements') {
                    setTimeout(() => loadAchievements(), 100);
                }

                // Load team selection when 3v3 tab is clicked
                if (targetTab === 'arena3v3') {
                    setTimeout(() => loadTeamSelection(), 100);
                }

                // Initialize collection search/filter when collection tab is clicked
                if (targetTab === 'collection') {
                    setTimeout(() => {
                        if (typeof initCollectionSearch === 'function') {
                            initCollectionSearch();
                        }
                    }, 100);
                }

                // Initialize Sanctuary War when war tab is clicked
                if (targetTab === 'war') {
                    setTimeout(() => {
                        if (typeof initSanctuaryWar === 'function') {
                            initSanctuaryWar();
                        }
                    }, 100);
                }

                // Initialize Leaderboard when leaderboard tab is clicked
                if (targetTab === 'leaderboard') {
                    setTimeout(() => {
                        if (typeof initLeaderboard === 'function') {
                            initLeaderboard();
                        }
                    }, 100);
                }
            });
        });

        console.log('✓ Arena tab integration ready');
    });
</script>