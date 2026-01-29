<!-- JavaScript (with cache busting) -->
<script src="<?= asset('user/js/pixi_bg.js', '../') ?>"></script>
<script type="module" src="<?= asset('user/js/pet/main.js', '../') ?>"></script>
<script src="<?= asset('user/js/sprite_config.js', '../') ?>"></script>
<script src="<?= asset('user/js/pixi_pet.js', '../') ?>"></script>
<script src="<?= asset('user/js/pet_animations.js', '../') ?>"></script>
<script src="<?= asset('user/js/pet_hardcore_update.js', '../') ?>"></script>
<script src="<?= asset('user/js/pet/detail-modal.js', '../') ?>"></script>

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
    if (window.location.pathname.indexOf('/moe/') === -1) {
        LB_ASSETS = '/assets/pets/';
    }

    var lbCurrentSort = 'level';
    var lbCurrentElement = 'all';
    var lbCurrentPeriod = 'monthly';
    var lbSearchQuery = '';
    var lbCachedData = []; // Store data for client-side search/filter

    // Core Function
    function initLeaderboard() {
        console.log('⚡ [LB] initLeaderboard FIRED');
        if (!document.getElementById('leaderboard-list')) return;

        setupLBTabs();
        setupLBSearch();
        initSeasonTimer();
        loadPetLeaderboard();
        renderHallOfFame();
    }

    function setupLBTabs() {
        // Sort Tabs
        document.querySelectorAll('.lb-tab').forEach(tab => {
            tab.onclick = () => {
                document.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                lbCurrentSort = tab.dataset.sort;
                loadPetLeaderboard();
            };
        });

        // Element Pills
        const pillContainer = document.getElementById('element-pills');
        if (pillContainer) {
            pillContainer.onclick = (e) => {
                if (e.target.classList.contains('element-pill')) {
                    document.querySelectorAll('.element-pill').forEach(p => p.classList.remove('active'));
                    e.target.classList.add('active');
                    lbCurrentElement = e.target.dataset.element;
                    loadPetLeaderboard();
                }
            };
        }

        // Period Toggle
        const periodContainer = document.getElementById('period-toggle');
        if (periodContainer) {
            periodContainer.querySelectorAll('.period-btn').forEach(btn => {
                btn.onclick = (e) => {
                    e.preventDefault();
                    periodContainer.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    lbCurrentPeriod = btn.dataset.period;
                    loadPetLeaderboard();
                };
            });
        }
    }

    function setupLBSearch() {
        const input = document.getElementById('lb-search');
        if (!input) return;

        input.addEventListener('input', (e) => {
            lbSearchQuery = e.target.value.toLowerCase();
            // Filter cached data info
            filterAndRender(lbCachedData);
        });
    }

    function initSeasonTimer() {
        const el = document.getElementById('season-countdown');
        if (!el) return;

        // Mock Season End: End of current month
        const now = new Date();
        const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);

        const updateTimer = () => {
            const diff = endOfMonth - new Date();
            if (diff <= 0) {
                el.textContent = "ENDED";
                return;
            }
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            el.textContent = `${d}d ${h}h ${m}m`;
        };

        updateTimer();
        setInterval(updateTimer, 60000);
    }

    function loadPetLeaderboard() {
        const list = document.getElementById('leaderboard-list');
        const podium = document.getElementById('podium-section');

        list.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Summoning Champions...</span></div>';

        // URL Construction
        const apiUrl = `api/router.php?action=get_pet_leaderboard&sort=${lbCurrentSort}&element=${lbCurrentElement}&period=${lbCurrentPeriod}&limit=50&t=${Date.now()}`;

        fetch(apiUrl)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Enrich data with mock stats if missing (Backend Placeholder)
                    lbCachedData = data.leaderboard.map(pet => {
                        // Mock fields if backend doesn't send them yet
                        if (pet.win_rate === undefined) pet.win_rate = Math.floor(Math.random() * 40) + 40; // 40-80%
                        if (pet.streak === undefined) pet.streak = Math.floor(Math.random() * 10);
                        if (pet.tier === undefined) pet.tier = getTier(pet.level || 0);
                        return pet;
                    });
                    filterAndRender(lbCachedData);
                } else {
                    list.innerHTML = `<div class="empty-state">${data.error || 'Unknown Error'}</div>`;
                }
            })
            .catch(err => {
                console.error(err);
                list.innerHTML = '<div class="empty-state">Connection Failed</div>';
            });
    }

    // Helper: Simple Tier Logic
    function getTier(level) {
        if (level >= 90) return 'Master';
        if (level >= 70) return 'Diamond';
        if (level >= 50) return 'Gold';
        if (level >= 30) return 'Silver';
        return 'Bronze';
    }

    function filterAndRender(data) {
        let filtered = data;
        if (lbSearchQuery) {
            filtered = data.filter(p =>
                (p.nickname && p.nickname.toLowerCase().includes(lbSearchQuery)) ||
                (p.owner_name && p.owner_name.toLowerCase().includes(lbSearchQuery))
            );
        }

        renderLB_Podium(filtered);
        renderLB_List(filtered);
    }

    // Detail Modal Integration
    function openLeaderboardPetDetail(petId) {
        // Find pet in cached data
        const pet = lbCachedData.find(p => p.pet_id == petId || p.id == petId);
        if (!pet) {
            console.error('Pet not found in cache:', petId);
            return;
        }

        // Map to format expected by detail-modal.js
        const modalPet = {
            id: pet.pet_id,
            nickname: pet.nickname,
            species_name: pet.species_name,
            element: pet.element,
            rarity: pet.rarity,
            level: pet.level,
            is_shiny: pet.is_shiny,
            shiny_hue: pet.shiny_hue || 0,
            evolution_stage: pet.evolution_stage,
            current_image: pet.current_image,
            base_health: pet.base_health || 120, // Default fallback
            base_attack: pet.base_attack,
            base_defense: pet.base_defense,
            status: 'ALIVE',
            is_active: false
        };

        if (window.openPetDetail) {
            window.openPetDetail(modalPet);
            // Hide "Set Active" button for others' pets
            setTimeout(() => {
                const btn = document.getElementById('detail-set-active-btn');
                if (btn) btn.style.display = 'none';
            }, 50);
        } else {
            console.error('openPetDetail not available');
        }
    }

    function renderLB_Podium(pets) {
        const container = document.getElementById('podium-section');
        if (!container) return;

        if (pets.length === 0) {
            container.innerHTML = '';
            return;
        }

        const top3 = pets.slice(0, 3);

        container.innerHTML = top3.map((pet, i) => {
            const rank = i + 1;
            const name = pet.nickname || pet.species_name;
            const img = LB_ASSETS + (pet.current_image || 'egg.png');

            return `
                <div class="podium-pet rank-${rank}" onclick="openLeaderboardPetDetail(${pet.pet_id})">
                    <div class="podium-avatar">
                        <div class="podium-crown"></div>
                        <img class="podium-img" src="${img}" onerror="this.src='/moe/assets/placeholder.png'">
                    </div>
                    <div class="podium-name">${name}</div>
                    <div class="podium-owner">${pet.owner_name}</div>
                    <div class="podium-stat">${getLBStat(pet)}</div>
                    <div class="podium-stand">${pet.tier || 'Tier ' + rank}</div>
                </div>
            `;
        }).join('');
    }

    function renderLB_List(pets) {
        const container = document.getElementById('leaderboard-list');
        const rest = pets.slice(3);

        if (rest.length === 0) {
            container.innerHTML = pets.length > 0
                ? '<div class="empty-state" style="padding:1rem;">Top 3 Only!</div>'
                : '<div class="empty-state">No Champions Found</div>';
            return;
        }

        container.innerHTML = rest.map((pet, i) => {
            const rank = i + 4;
            const name = pet.nickname || pet.species_name;
            const img = LB_ASSETS + (pet.current_image || 'egg.png');
            const elClass = (pet.element || '').toLowerCase();

            return `
                <div class="lb-pet-card" onclick="openLeaderboardPetDetail(${pet.pet_id})">
                    <div class="rank">#${rank}</div>
                    <img class="pet-img" src="${img}" onerror="this.src='/moe/assets/placeholder.png'">
                    
                    <div class="pet-info">
                        <div class="pet-name ${pet.is_shiny ? 'shiny' : ''}">
                            ${name} <span class="tier-badge ${getTierClass(pet.tier)}">${pet.tier}</span>
                        </div>
                        <div class="pet-meta">
                            <span class="element-badge ${elClass}">${pet.element}</span>
                            <span class="owner">${pet.owner_name}</span>
                        </div>
                    </div>
                    
                    <div class="pet-stats-row" style="display:flex; gap:15px; text-align:center">
                        <div class="stat-group">
                            <div class="stat-val" style="color:#ddd; font-size:0.8rem">${pet.win_rate}%</div>
                            <div class="stat-lbl" style="font-size:0.6rem; color:#666">WR</div>
                        </div>
                         <div class="stat-group">
                            <div class="stat-val" style="color:#ddd; font-size:0.8rem">${pet.streak}</div>
                            <div class="stat-lbl" style="font-size:0.6rem; color:#666">Streak</div>
                        </div>
                        <div class="stat-main-group">
                             ${getLBStat(pet, true)}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function getLBStat(pet, detailed) {
        if (detailed) {
            if (lbCurrentSort === 'wins') return `<div class="stat-main">${pet.battle_wins}</div><div class="stat-label">Wins</div>`;
            if (lbCurrentSort === 'power') return `<div class="stat-main">${pet.power_score}</div><div class="stat-label">Power</div>`;
            return `<div class="stat-main">${pet.level}</div><div class="stat-label">Lvl</div>`;
        }
        if (lbCurrentSort === 'wins') return `${pet.battle_wins} Wins`;
        if (lbCurrentSort === 'power') return `${pet.power_score} Power`;
        return `Lv.${pet.level}`;
    }

    // Hall of Fame - Load from API with fallback
    function renderHallOfFame() {
        const container = document.getElementById('hof-list');
        if (!container) return;

        container.innerHTML = '<div class="loading-spinner" style="padding:10px"><div class="spinner small"></div></div>';

        fetch(`api/router.php?action=get_hall_of_fame&limit=6&t=${Date.now()}`)
            .then(res => res.json())
            .then(data => {
                const winners = data.hall_of_fame || [];

                if (winners.length === 0) {
                    // Fallback mock data
                    const mockWinners = [
                        { month_year: 'Dec 2025', species_name: 'Shadowfox', current_image: 'shadowfox_adult.png' },
                        { month_year: 'Nov 2025', species_name: 'Inferno', current_image: 'inferno_adult.png' },
                        { month_year: 'Oct 2025', species_name: 'AquaSpirit', current_image: 'aquaspirit_adult.png' },
                    ];
                    renderHofItems(container, mockWinners);
                } else {
                    renderHofItems(container, winners);
                }
            })
            .catch(() => {
                // Error fallback
                container.innerHTML = '<div style="padding:10px;color:#666;font-size:0.75rem">Coming Soon</div>';
            });
    }

    function renderHofItems(container, winners) {
        container.innerHTML = winners.map(w => `
            <div class="hof-item">
                <img src="${LB_ASSETS}${w.current_image}" onerror="this.src='/moe/assets/placeholder.png'">
                <div class="hof-month">${w.month_year || 'Past'}</div>
                <div class="hof-name">${w.nickname || w.species_name}</div>
            </div>
        `).join('');
    }

    // ================================================
    // BATTLE HISTORY TAB (PREMIUM REDESIGN + PAGINATION)
    // ================================================
    var historyOffset = 0;
    var historyLimit = 20;
    var isHistoryLoading = false;

    function loadBattleHistoryTab() {
        console.log('📜 [History] Initial load...');
        historyOffset = 0;
        fetchHistory(false);
    }

    function fetchHistory(append) {
        var listContainer = document.getElementById('history-list');
        var winsEl = document.getElementById('total-wins');
        var lossesEl = document.getElementById('total-losses');
        var streakEl = document.getElementById('win-streak');

        if (!document.getElementById('history-css')) {
            var link = document.createElement('link');
            link.id = 'history-css';
            link.rel = 'stylesheet';
            link.href = '/moe/user/css/history_premium.css?v=' + Date.now();
            document.head.appendChild(link);
        }

        if (!listContainer) return;
        if (isHistoryLoading) return;
        isHistoryLoading = true;

        if (!append) {
            listContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading battle records...</span></div>';
        } else {
            var existingBtn = document.getElementById('load-more-btn-container');
            if (existingBtn) existingBtn.remove();
            var spinner = document.createElement('div');
            spinner.id = 'history-spinner-bottom';
            spinner.className = 'loading-spinner';
            spinner.innerHTML = '<div class="spinner small"></div>';
            listContainer.appendChild(spinner);
        }

        var url = 'api/router.php?action=battle_history&limit=' + historyLimit + '&offset=' + historyOffset + '&t=' + Date.now();

        fetch(url)
            .then(r => r.json())
            .then(data => {
                isHistoryLoading = false;
                var bottomSpinner = document.getElementById('history-spinner-bottom');
                if (bottomSpinner) bottomSpinner.remove();

                if (!data.success) {
                    if (!append) listContainer.innerHTML = '<div class="empty-state">Unable to load history</div>';
                    return;
                }

                if (winsEl) winsEl.textContent = data.stats.wins || 0;
                if (lossesEl) lossesEl.textContent = data.stats.losses || 0;
                if (streakEl) streakEl.textContent = data.stats.current_streak || 0;

                var defWinsEl = document.getElementById('defense-wins');
                var defLossesEl = document.getElementById('defense-losses');
                if (defWinsEl) defWinsEl.textContent = data.stats.defense_wins || 0;
                if (defLossesEl) defLossesEl.textContent = data.stats.defense_losses || 0;

                var history = data.history || [];

                if (!append && history.length === 0) {
                    listContainer.innerHTML = '<div class="empty-state">No battles recorded yet.<br><small>Fight in the Arena!</small></div>';
                    return;
                }

                var html = history.map(battle => {
                    var date = new Date(battle.created_at).toLocaleDateString();
                    var won = battle.won ? true : false;
                    var isDefender = battle.battle_role === 'defender';
                    var roleLabel = isDefender ? '🛡️ DEFENDED' : '⚔️ ATTACKED';
                    var roleClass = isDefender ? 'defender' : 'attacker';

                    var myName = battle.my_pet_name || 'My Pet';
                    var myLvl = battle.my_pet_level || '?';
                    var myImg = LB_ASSETS + (battle.my_pet_image || 'default.png');

                    var oppName = battle.opp_pet_name || 'Wild Pet';
                    var oppLvl = battle.opp_pet_level || '?';
                    var oppImg = LB_ASSETS + (battle.opp_pet_image || 'default.png');
                    var oppOwner = battle.opp_username ? 'Trainer: ' + battle.opp_username : 'Wild Enzyme';

                    return `
                        <div class="history-card ${won ? 'win' : 'lose'} ${roleClass}">
                            <div class="h-role-badge ${roleClass}">${roleLabel}</div>
                            <div class="h-pet player">
                                <div class="h-pet-avatar">
                                    <img class="h-pet-img" src="${myImg}" onerror="this.src='../assets/placeholder.png'">
                                    <div class="h-lvl-badge">Lv.${myLvl}</div>
                                </div>
                                <div class="h-info"><span class="h-pet-name">${myName}</span><span class="h-owner-name">You</span></div>
                            </div>
                            <div class="h-result">
                                <span class="h-res-text ${won ? 'win' : 'lose'}">${won ? 'VICTORY' : 'DEFEAT'}</span>
                                <span class="h-vs">VS</span>
                                <span class="h-date">${date}</span>
                            </div>
                            <div class="h-pet enemy">
                                <div class="h-pet-avatar">
                                    <img class="h-pet-img" src="${oppImg}" onerror="this.src='../assets/placeholder.png'">
                                    <div class="h-lvl-badge" style="background:linear-gradient(45deg, #333, #555)">Lv.${oppLvl}</div>
                                </div>
                                <div class="h-info"><span class="h-pet-name">${oppName}</span><span class="h-owner-name">${oppOwner}</span></div>
                            </div>
                        </div>
                    `;
                }).join('');

                if (!append) {
                    listContainer.innerHTML = html;
                } else {
                    var temp = document.createElement('div');
                    temp.innerHTML = html;
                    while (temp.firstChild) {
                        listContainer.appendChild(temp.firstChild);
                    }
                }

                if (history.length === historyLimit) {
                    var btnContainer = document.createElement('div');
                    btnContainer.id = 'load-more-btn-container';
                    btnContainer.className = 'load-more-container';
                    btnContainer.innerHTML = '<button class="load-more-btn">Load More Results</button>';
                    listContainer.appendChild(btnContainer);
                    btnContainer.querySelector('button').onclick = () => {
                        historyOffset += historyLimit;
                        fetchHistory(true);
                    };
                }
            })
            .catch(e => {
                isHistoryLoading = false;
                console.error('[History] Error:', e);
                if (!append) listContainer.innerHTML = '<div class="empty-state">Network Error</div>';
            });
    }

    // Update renderLB_List to use tier badge class
    function getTierClass(tier) {
        return (tier || 'bronze').toLowerCase();
    }

    // Attach to Window
    window.initLeaderboard = initLeaderboard;
    window.loadPetLeaderboard = loadPetLeaderboard;
    window.loadBattleHistoryTab = loadBattleHistoryTab;
    window.openLeaderboardPetDetail = openLeaderboardPetDetail;

    // Auto-init
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