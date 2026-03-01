<!-- My Pet Tab Content -->
<section id="my-pet" class="tab-panel active center-content section-margin-lg">

    <!-- Pet Showcase - Enhanced Premium Stage -->
    <div class="my-pet-showcase" id="pet-stage">

        <!-- No Pet State -->
        <div class="no-pet-state" id="no-pet-message">
            <div class="no-pet-icon">
                <i class="fas fa-egg"></i>
            </div>
            <h3 class="no-pet-title">No Active Pet!</h3>
            <p class="no-pet-desc">Summon your first companion from the Gacha</p>
            <button class="btn-summon" onclick="switchTab('gacha')">
                <i class="fas fa-sparkles"></i>
                <span>Get Your First Pet</span>
            </button>
        </div>

        <!-- Pet Display Container (populated by JS) -->
        <div class="pet-display-zone" id="pet-display-zone" style="display: none;">
            <!-- Premium Stage Accents -->
            <div class="showcase-accents">
                <div class="accent corner-tl"></div>
                <div class="accent corner-tr"></div>
                <div class="accent corner-bl"></div>
                <div class="accent corner-br"></div>
            </div>

            <!-- Grand Showcase V3 Atmospheric Effects -->
            <div class="showcase-god-rays"></div>
            <div class="showcase-dust-particles"></div>
            <div class="showcase-vignette"></div>

            <div class="pet-floor-shadow"></div>

            <!-- Pet Image (rendered by JS) -->
            <div id="pet-img-container"></div>

            <!-- Shiny Sparkle Effect -->
            <div class="shiny-sparkles" id="shiny-sparkles" style="display: none;">
                <div class="sparkle"></div>
                <div class="sparkle"></div>
                <div class="sparkle"></div>
            </div>
        </div>
    </div>

    <!-- Pet Info Header -->
    <div class="pet-info-header" id="pet-info-header" style="display: none;">
        <div class="pet-name-section">
            <h2 class="pet-display-name" id="pet-name">Loading...</h2>
            <button class="btn-edit-name" onclick="openRenameModal()" title="Rename Pet">
                <i class="fas fa-pen"></i>
            </button>
        </div>
        <div class="pet-meta-badges">
            <span class="level-badge" id="pet-level">Lv.1</span>
            <span class="element-badge fire" id="pet-element-badge">Fire</span>
            <span class="rarity-badge common" id="pet-rarity-badge">Common</span>
            <span class="shiny-tag" id="shiny-tag" style="display: none;">
                <i class="fas fa-star"></i> SHINY
            </span>
        </div>
    </div>

    <!-- Enhanced Stats Cards -->
    <div class="stats-cards-container" id="stats-container" style="display: none;">
        <!-- Health Card -->
        <div class="stat-card health-card">
            <div class="stat-card-header">
                <i class="fas fa-heart"></i>
                <span>Health</span>
            </div>
            <div class="stat-card-body">
                <div class="stat-progress-ring">
                    <svg class="progress-ring" viewBox="0 0 80 80">
                        <circle class="progress-ring-bg" cx="40" cy="40" r="35"></circle>
                        <circle class="progress-ring-fill health-fill" id="health-ring" cx="40" cy="40" r="35"></circle>
                    </svg>
                    <div class="stat-value-center" id="health-value">100</div>
                </div>
            </div>
        </div>

        <!-- Hunger Card -->
        <div class="stat-card hunger-card">
            <div class="stat-card-header">
                <i class="fas fa-drumstick-bite"></i>
                <span>Hunger</span>
            </div>
            <div class="stat-card-body">
                <div class="stat-progress-ring">
                    <svg class="progress-ring" viewBox="0 0 80 80">
                        <circle class="progress-ring-bg" cx="40" cy="40" r="35"></circle>
                        <circle class="progress-ring-fill hunger-fill" id="hunger-ring" cx="40" cy="40" r="35"></circle>
                    </svg>
                    <div class="stat-value-center" id="hunger-value">100</div>
                </div>
            </div>
        </div>

        <!-- Mood Card -->
        <div class="stat-card mood-card">
            <div class="stat-card-header">
                <i class="fas fa-smile"></i>
                <span>Mood</span>
            </div>
            <div class="stat-card-body">
                <div class="stat-progress-ring">
                    <svg class="progress-ring" viewBox="0 0 80 80">
                        <circle class="progress-ring-bg" cx="40" cy="40" r="35"></circle>
                        <circle class="progress-ring-fill mood-fill" id="mood-ring" cx="40" cy="40" r="35"></circle>
                    </svg>
                    <div class="stat-value-center" id="mood-value">100</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Experience Bar -->
    <div class="exp-card" id="exp-card" style="display: none;">
        <div class="exp-card-header">
            <div class="exp-label">
                <i class="fas fa-star"></i>
                <span>Experience</span>
            </div>
            <span class="exp-text" id="exp-text">0 / 100</span>
        </div>
        <div class="exp-bar-container">
            <div class="exp-bar">
                <div class="exp-bar-fill" id="exp-bar"></div>
                <div class="exp-bar-glow"></div>
            </div>
        </div>
    </div>

    <!-- Action Buttons Grid -->
    <div class="action-buttons-grid" id="action-buttons" style="display: none;">
        <button class="action-card feed-card" id="btn-feed">
            <div class="action-icon">
                <i class="fas fa-bone"></i>
            </div>
            <span class="action-label">Feed</span>
            <div class="action-glow"></div>
        </button>

        <button class="action-card play-card" id="btn-play">
            <div class="action-icon">
                <i class="fas fa-gamepad"></i>
            </div>
            <span class="action-label">Play</span>
            <div class="action-glow"></div>
        </button>

        <button class="action-card heal-card" id="btn-heal">
            <div class="action-icon">
                <i class="fas fa-heart"></i>
            </div>
            <span class="action-label">Heal</span>
            <div class="action-glow"></div>
        </button>

        <button class="action-card shelter-card" id="btn-shelter">
            <div class="action-icon">
                <i class="fas fa-home"></i>
            </div>
            <span class="action-label">Shelter</span>
            <div class="action-glow"></div>
        </button>
    </div>

    <!-- Dead Pet Revive CTA -->
    <div class="revive-cta-container" id="revive-cta-container"
        style="display: none; text-align: center; margin-top: var(--space-4);">
        <p style="color: var(--color-danger); margin-bottom: var(--space-2); font-weight: bold; font-size: 1.1rem;">Your
            pet has fallen.</p>
        <button class="btn-primary"
            onclick="document.dispatchEvent(new CustomEvent('openItemModal', { detail: { type: 'revive' } }))"
            style="width: 100%; border-color: var(--color-danger); background: rgba(231, 76, 60, 0.1); color: var(--color-danger); font-size: 1.1rem; padding: 12px; box-shadow: 0 0 15px rgba(231, 76, 60, 0.4);">
            <i class="fas fa-heart-crack"></i> Use Revival Item
        </button>
    </div>
</section>