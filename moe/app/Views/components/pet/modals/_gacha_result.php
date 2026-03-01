<!-- Gacha Result Modal (Premium Enhanced) -->
<div class="modal-overlay" id="gacha-modal">
    <div class="gacha-result-modal">
        <!-- Animated Background Based on Rarity -->
        <div class="gacha-result-bg">
            <div class="gacha-result-rays"></div>
            <div class="gacha-result-stars"></div>
        </div>

        <!-- Close Button -->
        <button class="gacha-modal-close" onclick="closeGachaModal()">
            <i class="fas fa-times"></i>
        </button>

        <!-- Title Section -->
        <div class="gacha-result-header">
            <h2 class="gacha-result-title" id="result-title">
                <i class="fas fa-sparkles"></i>
                <span>New Pet!</span>
            </h2>
        </div>

        <!-- Pet Showcase -->
        <div class="gacha-result-showcase">
            <!-- Multi-layered Glow Rings -->
            <div class="showcase-glow-ring ring-1"></div>
            <div class="showcase-glow-ring ring-2"></div>
            <div class="showcase-glow-ring ring-3"></div>

            <!-- Animated Particles -->
            <div class="showcase-particles">
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
            </div>

            <!-- Pet Image -->
            <img src="" alt="" id="result-pet-img" class="gacha-result-pet">

            <!-- Shiny Badge -->
            <div id="result-shiny" class="gacha-shiny-badge">
                <i class="fas fa-star"></i>
                <span>SHINY</span>
            </div>
        </div>

        <!-- Pet Info Card -->
        <div class="gacha-result-info">
            <h3 id="result-name" class="gacha-result-name">Pet Name</h3>
            <div class="gacha-result-rarity">
                <span id="result-rarity" class="rarity-badge-large common">Common</span>
            </div>

            <!-- Quick Stats Preview -->
            <div class="gacha-result-stats">
                <div class="quick-stat">
                    <i class="fas fa-heart"></i>
                    <span>HP 100</span>
                </div>
                <div class="quick-stat">
                    <i class="fas fa-bolt"></i>
                    <span>Lv. 1</span>
                </div>
                <div class="quick-stat">
                    <i class="fas fa-shield"></i>
                    <span id="result-element">Fire</span>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <button class="gacha-result-btn" onclick="closeGachaModal()">
            <div class="btn-shine"></div>
            <i class="fas fa-check-circle"></i>
            <span>Awesome!</span>
        </button>

        <!-- Decoration Elements -->
        <div class="gacha-result-decor gacha-result-decor-left"></div>
        <div class="gacha-result-decor gacha-result-decor-right"></div>
    </div>
</div>