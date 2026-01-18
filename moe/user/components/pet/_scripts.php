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
    function initLeaderboard() { loadPetLeaderboard(); }
    function loadPetLeaderboard() {
        var sortSelect = document.getElementById('lb-sort');
        var elementSelect = document.getElementById('lb-element');
        var listContainer = document.getElementById('leaderboard-list');
        if (!listContainer) { console.error('Leaderboard container not found'); return; }
        var sort = sortSelect ? sortSelect.value : 'level';
        var element = elementSelect ? elementSelect.value : 'all';
        listContainer.innerHTML = '<div class="loading-spinner">Loading...</div>';
        var url = 'api/router.php?action=get_pet_leaderboard&sort=' + sort + '&element=' + element + '&limit=15';
        fetch(url).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success) { renderLeaderboard(data); populateElementFilter(data.available_elements); }
            else { listContainer.innerHTML = '<div class="empty-state">' + (data.error || 'Failed') + '</div>'; }
        }).catch(function (e) { listContainer.innerHTML = '<div class="empty-state">Network error</div>'; });
    }
    function renderLeaderboard(data) {
        var container = document.getElementById('leaderboard-list');
        var pets = data.leaderboard;
        if (!pets || pets.length === 0) { container.innerHTML = '<div class="empty-state">No pets found</div>'; return; }
        var html = '';
        for (var i = 0; i < pets.length; i++) {
            var pet = pets[i];
            var rankClass = pet.rank <= 3 ? 'rank-' + pet.rank : '';
            var displayName = pet.nickname || pet.species_name;
            var imgPath = ASSETS_BASE + pet.current_image;
            var sortVal = document.getElementById('lb-sort');
            var sv = sortVal ? sortVal.value : 'level';
            var mainStat = sv === 'wins' ? pet.battle_wins : (sv === 'power' ? pet.power_score : 'Lv.' + pet.level);
            var statLabel = sv === 'wins' ? 'Wins' : (sv === 'power' ? 'Power' : 'Level');
            var elClass = pet.element ? pet.element.toLowerCase() : '';
            html += '<div class="lb-pet-card ' + rankClass + '">';
            html += '<div class="rank">' + getRankIcon(pet.rank) + '</div>';
            html += '<img class="pet-img" src="' + imgPath + '" onerror="this.src=\\'../ assets / placeholder.png\\'" alt="' + displayName + '">';
            html += '<div class="pet-info"><div class="pet-name">' + displayName + '</div>';
            html += '<div class="pet-meta"><span class="element-badge ' + elClass + '">' + (pet.element || '?') + '</span>';
            html += '<span class="owner">' + pet.owner_name + '</span></div></div>';
            html += '<div class="pet-stats"><div class="stat-main">' + mainStat + '</div><div class="stat-label">' + statLabel + '</div></div></div>';
        }
        container.innerHTML = html;
    }
    function getRankIcon(rank) { if (rank === 1) return '1st'; if (rank === 2) return '2nd'; if (rank === 3) return '3rd'; return '#' + rank; }
    function populateElementFilter(elements) {
        var select = document.getElementById('lb-element');
        if (!select || !elements) return;
        select.innerHTML = '<option value="all">All Elements</option>';
        for (var i = 0; i < elements.length; i++) {
            var opt = document.createElement('option');
            opt.value = elements[i];
            opt.textContent = elements[i];
            select.appendChild(opt);
        }
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