<!-- 3V3 ARENA TAB (Premium) -->
<section id="arena3v3" class="tab-panel">
    <!-- Premium 3v3 Header -->
    <div class="arena-premium-header arena-3v3-variant">
        <div class="arena-header-bg-layer"
            style="background-image: url('<?= asset_v('assets/arena/arena_3v3_header_bg.png') ?>');"></div>
        <div class="arena-header-overlay"></div>

        <!-- Particles -->
        <div class="arena-particles-container">
            <?php for ($i = 0; $i < 15; $i++): ?>
                <div class="arena-header-particle"
                    style="--x: <?= rand(-100, 100) ?>px; --y: <?= rand(-200, -500) ?>px; left: <?= rand(0, 100) ?>%; bottom: <?= rand(0, 30) ?>%; animation-delay: <?= rand(0, 5000) ?>ms;">
                </div>
            <?php endfor; ?>
        </div>

        <div class="arena-header-content">
            <h2 class="arena-title">
                <i class="fas fa-users-sword"></i>
                <span>TRIALS OF THREE</span>
            </h2>
            <p class="arena-subtitle">Assemble your ultimate vanguard</p>
        </div>
    </div>

    <!-- Team Selection Area -->
    <div class="team-selection-premium">
        <div class="selection-header-ornate">
            <h3 class="section-title">
                <i class="fas fa-id-card-alt"></i>
                SELECT YOUR TEAM
            </h3>
            <div class="selection-guide">Drag pets or click to fill the three slots below.</div>
        </div>

        <!-- Team Slots Display -->
        <div class="team-slots-display">
            <div class="team-slot-frame" data-slot="1">
                <div class="slot-number">I</div>
                <div class="team-slot empty" data-slot="1" onclick="focusSlot(1)">
                    <i class="fas fa-plus-circle"></i>
                    <span>LEAD</span>
                </div>
            </div>
            <div class="team-slot-frame" data-slot="2">
                <div class="slot-number">II</div>
                <div class="team-slot empty" data-slot="2" onclick="focusSlot(2)">
                    <i class="fas fa-plus-circle"></i>
                    <span>VANGUARD</span>
                </div>
            </div>
            <div class="team-slot-frame" data-slot="3">
                <div class="slot-number">III</div>
                <div class="team-slot empty" data-slot="3" onclick="focusSlot(3)">
                    <i class="fas fa-plus-circle"></i>
                    <span>SUPPORT</span>
                </div>
            </div>
        </div>

        <!-- Available Pets Grid -->
        <h3 class="section-title">
            <i class="fas fa-paw"></i>
            Your Pets
        </h3>
        <div class="available-pets-grid" id="team-selection">
            <!-- Pets rendered by JS with selectable cards -->
        </div>
    </div>

    <!-- Enter Arena Button -->
    <button class="btn-enter-arena-3v3" id="btn-start-3v3" onclick="start3v3Battle()">
        <i class="fas fa-dragon"></i>
        <span>Enter 3v3 Arena</span>
    </button>
</section>