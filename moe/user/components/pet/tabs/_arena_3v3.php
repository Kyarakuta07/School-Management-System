<!-- 3V3 ARENA TAB (Premium) -->
<section id="arena3v3" class="tab-panel">
    <!-- Premium 3v3 Header -->
    <div class="arena-premium-header">
        <h2 class="arena-title">
            <i class="fas fa-users-sword"></i>
            <span>3v3 Team Battle</span>
        </h2>
        <p class="arena-subtitle">Assemble your ultimate team</p>
    </div>

    <!-- Team Selection Area -->
    <div class="team-selection-premium">
        <h3 class="section-title">
            <i class="fas fa-shield-alt"></i>
            Select Your Team (3 Pets)
        </h3>

        <!-- Team Slots Display -->
        <div class="team-slots-display">
            <div class="team-slot empty" data-slot="1">
                <i class="fas fa-plus"></i>
                <span>Slot 1</span>
            </div>
            <div class="team-slot empty" data-slot="2">
                <i class="fas fa-plus"></i>
                <span>Slot 2</span>
            </div>
            <div class="team-slot empty" data-slot="3">
                <i class="fas fa-plus"></i>
                <span>Slot 3</span>
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