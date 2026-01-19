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

<!-- Leaderboard Module (INLINE to avoid path issues) -->
<script>
    console.log('🚀 [LB] INLINE Leaderboard Script Init');
    
    // Global Config
    var LB_ASSETS = '/moe/assets/pets/'; // Fallback
    // Attempt to detect if we are on /user/ or /moe/user/
    if (window.location.pathname.indexOf('/moe/') === -1) {
        LB_ASSETS = '/assets/pets/';
    }

    var lbCurrentSort = 'level';
    var lbCurrentElement = 'all';

    // Core Function
    function initLeaderboard() {
        console.log('⚡ [LB] initLeaderboard FIRED');
        // Check if container exists
        if (!document.getElementById('leaderboard-list')) {
             console.error('❌ [LB] #leaderboard-list not found!');
             return;
        }

        try {
            setupLBTabs();
            // Initial Load
            loadPetLeaderboard();
        } catch(e) {
            console.error('🔥 [LB] Critical Error:', e);
            document.getElementById('leaderboard-list').innerHTML = '<div class="error-state">System Error: ' + e.message + '</div>';
        }
    }

    // Helper: Logic for Tabs
    function setupLBTabs() {
        var tabs = document.querySelectorAll('.lb-tab');
        tabs.forEach(function(tab) {
            tab.onclick = function() {
                // UI Toggle
                tabs.forEach(function(t) { t.classList.remove('active'); });
                tab.classList.add('active');
                
                // Update State
                lbCurrentSort = tab.dataset.sort;
                console.log('👉 [LB] Sort changed to:', lbCurrentSort);
                
                // Reload
                loadPetLeaderboard();
            };
        });
        
        // Element Pills
        var pillContainer = document.getElementById('element-pills');
        if (pillContainer && !pillContainer.dataset.listening) {
            pillContainer.dataset.listening = "true";
            pillContainer.onclick = function(e) {
                if (e.target.classList.contains('element-pill')) {
                    var pills = pillContainer.querySelectorAll('.element-pill');
                    pills.forEach(function(p) { p.classList.remove('active'); });
                    e.target.classList.add('active');
                    
                    lbCurrentElement = e.target.dataset.element;
                    console.log('👉 [LB] Element changed to:', lbCurrentElement);
                    loadPetLeaderboard();
                }
            };
        }
    }

    // API Call
    function loadPetLeaderboard() {
        var list = document.getElementById('leaderboard-list');
        var podium = document.getElementById('podium-section');
        
        // Loader
        list.innerHTML = '<div class="loading-spinner" style="padding:20px;text-align:center"><div class="spinner"></div><p>Summoning Champions...</p></div>';
        if (podium) podium.innerHTML = ''; // Clear podium during load

        // URL Construction (Relative API is safest)
        var apiUrl = 'api/router.php?action=get_pet_leaderboard' 
                   + '&sort=' + lbCurrentSort 
                   + '&element=' + lbCurrentElement 
                   + '&limit=15'
                   + '&t=' + Date.now(); // No cache

        console.log('📡 [LB] Fetching:', apiUrl);

        fetch(apiUrl)
            .then(function(res) {
                if (!res.ok) throw new Error('API Error ' + res.status);
                return res.json();
            })
            .then(function(data) {
                if (data.success) {
                    console.log('✅ [LB] Data received');
                    renderLB_Podium(data.leaderboard);
                    renderLB_List(data.leaderboard);
                } else {
                    console.error('❌ [LB] API Error:', data.error);
                    list.innerHTML = '<div class="empty-state">' + (data.error || 'Unknown Error') + '</div>';
                }
            })
            .catch(function(err) {
                console.error('💥 [LB] Network Error:', err);
                list.innerHTML = '<div class="empty-state" style="color:#ff6b6b">Connection Failed.<br><small>Double check network</small><br><button onclick="loadPetLeaderboard()" style="margin-top:10px">Retry</button></div>';
            });
    }

    // Rendering Logic
    function renderLB_Podium(pets) {
        var container = document.getElementById('podium-section');
        if (!container || !pets || pets.length === 0) return;
        
        var top3 = pets.slice(0, 3);
        var crowns = ['👑', '🥈', '🥉'];
        var html = '';
        
        top3.forEach(function(pet, index) {
            var rank = index + 1;
            var name = pet.nickname || pet.species_name;
            var img = LB_ASSETS + pet.current_image;
            var stat = getLBStat(pet);
            
            html += '<div class="podium-pet rank-' + rank + '">';
            html +=   '<div class="podium-avatar">';
            html +=     '<span class="podium-crown">' + crowns[index] + '</span>';
            html +=     '<img class="podium-img" src="' + img + '" onerror="this.src=\'../assets/placeholder.png\'">';
            html +=   '</div>';
            html +=   '<div class="podium-name">' + name + '</div>';
            html +=   '<div class="podium-stat">' + stat + '</div>';
            html +=   '<div class="podium-stand">' + rank + '</div>';
            html += '</div>';
        });
        
        container.innerHTML = html;
    }

    function renderLB_List(pets) {
        var container = document.getElementById('leaderboard-list');
        var rest = pets.slice(3);
        
        if (rest.length === 0) {
             container.innerHTML = pets.length > 0 
                ? '<div class="empty-state">Top 3 Only!</div>' 
                : '<div class="empty-state">No Data</div>';
             return;
        }

        var html = '';
        rest.forEach(function(pet, index) {
            var rank = index + 4;
            var name = pet.nickname || pet.species_name;
            var img = LB_ASSETS + pet.current_image;
            var stat = getLBStat(pet, true);
            var elClass = (pet.element || '').toLowerCase();
            
            html += '<div class="lb-pet-card">';
            html +=   '<div class="rank">#' + rank + '</div>';
            html +=   '<img class="pet-img" src="' + img + '" onerror="this.src=\'../assets/placeholder.png\'">';
            html +=   '<div class="pet-info">';
            html +=     '<div class="pet-name ' + (pet.is_shiny?'shiny':'') + '">' + name + '</div>';
            html +=     '<div class="pet-meta">';
            html +=       '<span class="element-badge ' + elClass + '">' + (pet.element || '?') + '</span>';
            html +=       '<span class="owner">' + pet.owner_name + '</span>';
            html +=     '</div>';
            html +=   '</div>';
            html +=   '<div class="pet-stats">' + stat + '</div>';
            html += '</div>';
        });
        
        container.innerHTML = html;
    }

    function getLBStat(pet, detailed) {
        if (detailed) {
            // For list view: Value + Label
            if (lbCurrentSort === 'wins') return '<div class="stat-main">' + pet.battle_wins + '</div><div class="stat-label">Wins</div>';
            return '<div class="stat-main">Lv.' + pet.level + '</div><div class="stat-label">Level</div>';
        }
        // For podium: Single string
        if (lbCurrentSort === 'wins') return pet.battle_wins + ' wins';
        return 'Lv.' + pet.level;
    }

    // BATTLE HISTORY TAB (PREMIUM REDESIGN)
    function loadBattleHistoryTab() {
        console.log('📜 [History] Loading battle history...');
        var listContainer = document.getElementById('history-list');
        var winsEl = document.getElementById('total-wins');
        var lossesEl = document.getElementById('total-losses');
        var streakEl = document.getElementById('win-streak');
        
        // Inject Premium CSS if not present
        if (!document.getElementById('history-css')) {
            var link = document.createElement('link');
            link.id = 'history-css';
            link.rel = 'stylesheet';
            link.href = '/moe/user/css/history_premium.css?v=' + Date.now();
            document.head.appendChild(link);
        }

        if (!listContainer) return;
        
        listContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading battle records...</span></div>';
        
        fetch('api/router.php?action=battle_history&limit=20&t=' + Date.now())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    listContainer.innerHTML = '<div class="empty-state">Unable to load history</div>';
                    return;
                }
                
                // Update Stats
                if (winsEl) winsEl.textContent = data.stats.wins || 0;
                if (lossesEl) lossesEl.textContent = data.stats.losses || 0;
                if (streakEl) streakEl.textContent = data.stats.current_streak || 0;
                
                var history = data.history || [];
                if (history.length === 0) {
                    listContainer.innerHTML = '<div class="empty-state">No battles recorded yet.<br><small>Fight in the Arena!</small></div>';
                    return;
                }
                
                var html = history.map(function(battle) {
                    var date = new Date(battle.created_at).toLocaleDateString();
                    var won = battle.won ? true : false;
                    
                    // My Pet Data
                    var myName = battle.my_pet_name || 'My Pet';
                    var myLvl = battle.my_pet_level || '?';
                    var myImg = LB_ASSETS + (battle.my_pet_image || 'default.png');
                    
                    // Opponent Data
                    var oppName = battle.opp_pet_name || 'Wild Pet';
                    var oppLvl = battle.opp_pet_level || '?';
                    var oppImg = LB_ASSETS + (battle.opp_pet_image || 'default.png');
                    var oppOwner = battle.opp_username ? 'Trainer: ' + battle.opp_username : 'Wild Enzyme';

                    return `
                        <div class="history-card ${won ? 'win' : 'lose'}">
                            <!-- My Pet -->
                            <div class="h-pet player">
                                <div class="h-pet-avatar">
                                    <img class="h-pet-img" src="${myImg}" onerror="this.src='../assets/placeholder.png'">
                                    <div class="h-lvl-badge">Lv.${myLvl}</div>
                                </div>
                                <div class="h-info">
                                    <span class="h-pet-name">${myName}</span>
                                    <span class="h-owner-name">You</span>
                                </div>
                            </div>

                            <!-- VS / Result -->
                            <div class="h-result">
                                <span class="h-res-text ${won ? 'win' : 'lose'}">${won ? 'VICTORY' : 'DEFEAT'}</span>
                                <span class="h-vs">VS</span>
                                <span class="h-date">${date}</span>
                            </div>

                            <!-- Opponent -->
                            <div class="h-pet enemy">
                                <div class="h-pet-avatar">
                                    <img class="h-pet-img" src="${oppImg}" onerror="this.src='../assets/placeholder.png'">
                                    <div class="h-lvl-badge" style="background:linear-gradient(45deg, #333, #555)">Lv.${oppLvl}</div>
                                </div>
                                <div class="h-info">
                                    <span class="h-pet-name">${oppName}</span>
                                    <span class="h-owner-name">${oppOwner}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                listContainer.innerHTML = html;
            })
            .catch(function(e) {
                console.error('[History] Error:', e);
                listContainer.innerHTML = '<div class="empty-state">Network Error</div>';
            });
    }

    // Attach to Window
    window.initLeaderboard = initLeaderboard;
    window.loadPetLeaderboard = loadPetLeaderboard;
    window.loadBattleHistoryTab = loadBattleHistoryTab;
    
    // Auto-init if param exists
    if (new URLSearchParams(window.location.search).get('tab') === 'leaderboard') {
        setTimeout(initLeaderboard, 500);
    }
</script>

<!-- Arena Integration Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', function () {
                const targetTab = this.dataset.tab;
                // Add minor delay to ensure visibility
                setTimeout(() => {
                    if (targetTab === 'leaderboard') {
                        if (typeof window.initLeaderboard === 'function') {
                            window.initLeaderboard(); 
                        } else {
                            console.error('❌ initLeaderboard still missing!');
                        }
                    }
                    if (targetTab === 'arena') loadOpponents();
                    if (targetTab === 'arena3v3') loadTeamSelection();
                    if (targetTab === 'war' && typeof initSanctuaryWar === 'function') initSanctuaryWar();
                    if (targetTab === 'collection' && typeof initCollectionSearch === 'function') initCollectionSearch();
                }, 100);
            });
        });
        console.log('✓ Arena tab integration ready');
    });
</script>