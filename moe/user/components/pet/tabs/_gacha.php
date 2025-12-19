<!-- GACHA TAB -->
<section id="gacha" class="tab-panel">
    <div class="gacha-section">
        <!-- Header Title -->
        <div class="gacha-header">
            <h2 class="gacha-title">
                <i class="fas fa-sparkles"></i>
                Summon Portal
            </h2>
            <p class="gacha-subtitle">Call forth legendary companions from the void</p>
        </div>

        <!-- Mystical Background Effects -->
        <div class="gacha-mystical-bg">
            <div class="mystical-particle"></div>
            <div class="mystical-particle"></div>
            <div class="mystical-particle"></div>
            <div class="mystical-particle"></div>
            <div class="mystical-particle"></div>
            <div class="mystical-particle"></div>
        </div>

        <!-- Egg Display with Enhanced Glow -->
        <div class="gacha-display-container">
            <div class="gacha-display">
                <div class="gacha-glow-ring"></div>
                <div class="gacha-glow-ring secondary"></div>
                <div class="gacha-pedestal"></div>
                <img src="../assets/pets/gacha_egg.png" alt="Gacha Egg" class="gacha-egg" id="gacha-egg">
                <div class="gacha-egg-glow"></div>
            </div>
        </div>

        <!-- Gacha Type Comparison Cards -->
        <div class="gacha-comparison">
            <!-- Normal Gacha Card -->
            <div class="gacha-card normal-card">
                <div class="gacha-card-header">
                    <i class="fas fa-egg"></i>
                    <h3>Normal Summon</h3>
                </div>
                <div class="gacha-card-body">
                    <div class="gacha-cost-badge">
                        <i class="fas fa-coins"></i>
                        <span>100</span>
                    </div>
                    <div class="gacha-rates">
                        <div class="rate-row">
                            <span class="rate-label common">Common</span>
                            <span class="rate-value">80%</span>
                        </div>
                        <div class="rate-row">
                            <span class="rate-label rare">Rare</span>
                            <span class="rate-value">17%</span>
                        </div>
                        <div class="rate-row">
                            <span class="rate-label epic">Epic</span>
                            <span class="rate-value">2.5%</span>
                        </div>
                    </div>
                    <button class="gacha-summon-btn normal-btn" onclick="performGacha('standard')">
                        <i class="fas fa-egg"></i>
                        <span>Normal Summon</span>
                    </button>
                </div>
            </div>

            <!-- Premium Gacha Card -->
            <div class="gacha-card premium-card">
                <div class="gacha-card-ribbon">
                    <span>BEST VALUE</span>
                </div>
                <div class="gacha-card-header premium">
                    <i class="fas fa-crown"></i>
                    <h3>Premium Summon</h3>
                </div>
                <div class="gacha-card-body">
                    <div class="gacha-cost-badge premium">
                        <i class="fas fa-coins"></i>
                        <span>500</span>
                    </div>
                    <div class="premium-guarantee">
                        <i class="fas fa-certificate"></i>
                        <span>Epic+ Guaranteed</span>
                    </div>
                    <div class="gacha-rates premium">
                        <div class="rate-row">
                            <span class="rate-label epic">Epic</span>
                            <span class="rate-value">75%</span>
                        </div>
                        <div class="rate-row highlight">
                            <span class="rate-label legendary">Legendary</span>
                            <span class="rate-value">25%</span>
                            <span class="rate-boost">3x!</span>
                        </div>
                    </div>
                    <button class="gacha-summon-btn premium-btn" onclick="performGacha('premium')">
                        <i class="fas fa-hat-wizard"></i>
                        <span>Premium Summon</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="gacha-info-section">
            <div class="gacha-tip">
                <i class="fas fa-lightbulb"></i>
                <p><strong>Pro Tip:</strong> Premium Summon has <span class="highlight-text">3x higher Legendary
                        rate</span> - perfect for legendary hunting!</p>
            </div>
            <div class="gacha-stats">
                <div class="stat-item">
                    <i class="fas fa-dice"></i>
                    <span>Random species per rarity</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-star"></i>
                    <span>1% chance for Shiny variant</span>
                </div>
            </div>
        </div>
    </div>
</section>