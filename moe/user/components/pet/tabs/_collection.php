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
</section>