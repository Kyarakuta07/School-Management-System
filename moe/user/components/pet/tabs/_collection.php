<!-- COLLECTION TAB -->
<section id="collection" class="tab-panel">
    <!-- Enhanced Collection Header -->
    <div class="collection-premium-header">
        <div class="collection-title-section">
            <h2 class="collection-main-title">
                <i class="fas fa-book-open"></i>
                <span>My Collection</span>
            </h2>
            <p class="collection-subtitle">Manage your legendary companions</p>
        </div>
        <div class="collection-count-badge" id="pet-count-badge">0 / 25</div>
    </div>

    <!-- Search, Filter, Sort Controls -->
    <div class="collection-controls">
        <!-- Search Bar -->
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="pet-search" class="search-input" placeholder="Search by name...">
        </div>

        <!-- Element Filters -->
        <div class="filter-pills">
            <button class="filter-pill active" data-filter="all" onclick="filterCollection('all')">
                <i class="fas fa-th"></i> All
            </button>
            <button class="filter-pill" data-filter="fire" onclick="filterCollection('fire')">
                🔥 Fire
            </button>
            <button class="filter-pill" data-filter="water" onclick="filterCollection('water')">
                💧 Water
            </button>
            <button class="filter-pill" data-filter="earth" onclick="filterCollection('earth')">
                🌿 Earth
            </button>
            <button class="filter-pill" data-filter="air" onclick="filterCollection('air')">
                💨 Air
            </button>
        </div>

        <!-- Sort Dropdown -->
        <div class="sort-container">
            <select id="pet-sort" class="sort-select" onchange="sortCollection(this.value)">
                <option value="level-desc">Level ↓</option>
                <option value="level-asc">Level ↑</option>
                <option value="rarity-desc">Rarity ↓</option>
                <option value="name-asc">Name A-Z</option>
                <option value="recent">Recent</option>
            </select>
        </div>
    </div>

    <!-- Stats Panel -->
    <div class="collection-stats-panel" id="stats-panel">
        <div class="stat-item">
            <i class="fas fa-paw"></i>
            <div class="stat-content">
                <span class="stat-value" id="stat-total">0</span>
                <span class="stat-label">Total</span>
            </div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="rarity-icons">
                <span class="rarity-dot common" title="Common"></span>
                <span class="rarity-dot rare" title="Rare"></span>
                <span class="rarity-dot epic" title="Epic"></span>
                <span class="rarity-dot legendary" title="Legendary"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value" id="stat-rarities">0/0/0/0</span>
                <span class="stat-label">C/R/E/L</span>
            </div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <i class="fas fa-star"></i>
            <div class="stat-content">
                <span class="stat-value" id="stat-shiny">0</span>
                <span class="stat-label">Shiny</span>
            </div>
        </div>
    </div>

    <!-- Collection Grid (populated by JS) -->
    <div class="collection-premium-grid" id="collection-grid">
        <!-- Pet cards rendered by JS -->
    </div>

    <!-- Pet Detail Modal -->
    <div class="pet-detail-modal" id="pet-detail-modal">
        <div class="pet-detail-backdrop" onclick="closePetDetail()"></div>
        <div class="pet-detail-content">
            <!-- Close Button -->
            <button class="pet-detail-close" onclick="closePetDetail()">
                <i class="fas fa-times"></i>
            </button>

            <!-- Pet Header -->
            <div class="pet-detail-header">
                <div class="pet-detail-img-container">
                    <img id="detail-pet-img" src="" alt="Pet" class="pet-detail-img">
                    <div class="detail-shiny-indicator" id="detail-shiny-tag">✨ SHINY</div>
                </div>
                <div class="pet-detail-info">
                    <h2 class="pet-detail-name" id="detail-pet-name">Pet Name</h2>
                    <div class="pet-detail-badges">
                        <span class="detail-badge element" id="detail-element">Fire</span>
                        <span class="detail-badge rarity" id="detail-rarity">Rare</span>
                        <span class="detail-badge level" id="detail-level">Lv.10</span>
                    </div>
                    <div class="pet-detail-stage" id="detail-stage">
                        <i class="fas fa-egg"></i> <span>Egg Stage</span>
                    </div>
                </div>
            </div>

            <!-- Battle Stats Section -->
            <div class="pet-detail-section">
                <h3 class="section-title">⚔️ Battle Stats</h3>
                <p class="section-subtitle">Calculated combat statistics</p>
                <div class="battle-stats-grid">
                    <div class="battle-stat-card">
                        <div class="stat-icon health">❤️</div>
                        <div class="stat-value" id="detail-hp">0</div>
                        <div class="stat-label">Health</div>
                    </div>
                    <div class="battle-stat-card">
                        <div class="stat-icon attack">⚔️</div>
                        <div class="stat-value" id="detail-atk">0</div>
                        <div class="stat-label">Attack</div>
                    </div>
                    <div class="battle-stat-card">
                        <div class="stat-icon power">💪</div>
                        <div class="stat-value" id="detail-power">0</div>
                        <div class="stat-label">Power</div>
                    </div>
                </div>
            </div>

            <!-- Base Stats Section -->
            <div class="pet-detail-section">
                <h3 class="section-title">📊 Base Stats</h3>
                <div class="base-stats-list">
                    <div class="base-stat-row">
                        <span class="base-label">Base HP</span>
                        <span class="base-value" id="detail-base-hp">0</span>
                    </div>
                    <div class="base-stat-row">
                        <span class="base-label">Base Attack</span>
                        <span class="base-value" id="detail-base-atk">0</span>
                    </div>
                    <div class="base-stat-row">
                        <span class="base-label">Base Defense</span>
                        <span class="base-value" id="detail-base-def">0</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pet-detail-actions">
                <button class="detail-action-btn primary" id="detail-set-active-btn" onclick="setActiveFromDetail()">
                    <i class="fas fa-star"></i> Set as Active
                </button>
            </div>
        </div>
    </div>
</section>