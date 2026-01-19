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

<!-- Leaderboard Module -->
<script src="/moe/user/js/leaderboard.js?v=<?= time() ?>"></script>

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