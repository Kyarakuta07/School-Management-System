<!-- PixiJS Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.3.2/pixi.min.js"></script>

<script src="<?= base_url('js/battle/pixi_bg.js?v=' . time()) ?>"></script>
<script type="module" src="<?= base_url('js/pet/main.js?v=' . time()) ?>"></script>

<script src="<?= base_url('js/battle/pixi_pet.js?v=' . time()) ?>"></script>
<script src="<?= base_url('js/user/pet_animations.js?v=' . time()) ?>"></script>
<script src="<?= base_url('js/pet_hardcore.js?v=' . time()) ?>"></script>
<script src="<?= base_url('js/pet/detail-modal.js') ?>"></script>

<!-- Arena & Achievements Module (Now handled by modules) -->

<!-- Collection Phase 2 (Search, Filter, Sort) -->
<script src="<?= base_url('js/user/collection_phase2.js') ?>"></script>

<!-- Sanctuary War -->
<script src="<?= base_url('js/battle/sanctuary_war.js?v=' . time()) ?>"></script>

<!-- Leaderboard logic is now imported by main.js -->

<!-- Leaderboard Helper (Glue for non-module calls) -->
<script>
    // These functions are exposed to window by leaderboard.js
    // but the init logic in arena integrations still expects them.
</script>

<!-- Battle History Logic moved to pet/arena.js module -->

<!-- Arena Integration now handled by pet/main.js and tabChanged events -->