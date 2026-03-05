<!-- PixiJS Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.3.2/pixi.min.js"></script>

<script src="<?= asset_v('js/battle/pixi_bg.js') ?>"></script>
<script type="module" src="<?= asset_v('js/pet/main.js') ?>"></script>

<script src="<?= asset_v('js/battle/pixi_pet.js') ?>"></script>
<script src="<?= asset_v('js/user/pet_animations.js') ?>"></script>
<script src="<?= asset_v('js/pet_hardcore.js') ?>"></script>
<script src="<?= asset_v('js/pet/detail-modal.js') ?>"></script>

<!-- Arena & Achievements Module (Now handled by modules) -->

<!-- Collection Phase 2 (Search, Filter, Sort) -->
<script src="<?= asset_v('js/user/collection_phase2.js') ?>"></script>

<!-- Sanctuary War -->
<script src="<?= asset_v('js/battle/sanctuary_war.js') ?>"></script>

<?php // Leaderboard scripts ?>

<!-- Leaderboard Helper (Glue for non-module calls) -->
<script>
    // These functions are exposed to window by leaderboard.js
    // but the init logic in arena integrations still expects them.
</script>

<!-- Battle History Logic moved to pet/arena.js module -->

<!-- Arena Integration now handled by pet/main.js and tabChanged events -->