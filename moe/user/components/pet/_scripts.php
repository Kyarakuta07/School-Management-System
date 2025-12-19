<!-- JavaScript -->
<script src="js/pixi_bg.js"></script>
<script type="module" src="js/pet/main.js"></script>
<script src="js/pixi_pet.js"></script>
<script src="js/pet_animations.js"></script>
<script src="js/pet_hardcore_update.js"></script>

<!-- Arena & Achievements Module -->
<script src="js/pet_arena.js"></script>

<!-- Collection Phase 2 (Search, Filter, Sort) -->
<script src="js/collection_phase2.js"></script>

<!-- Arena Integration Script -->
<script>
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', () => {
        // Listen for tab clicks
        document.querySelectorAll('.main-tab').forEach(tab => {
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
            });
        });

        console.log('✓ Arena tab integration ready');
    });
</script>