<?php
/**
 * MOE Pet System - Main Pet Page V2
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Complete Redesign - Premium Dark Egyptian Fantasy Theme
 * Mobile-First with Desktop Support
 */

session_start();
include '../config/connection.php';

// Authentication check
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Nethera') {
    header("Location: ../index.php?pesan=gagal_akses");
    exit();
}

$user_id = $_SESSION['id_nethera'];
$nama_pengguna = htmlspecialchars($_SESSION['nama_lengkap']);

// Get user gold
$gold_stmt = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
mysqli_stmt_bind_param($gold_stmt, "i", $user_id);
mysqli_stmt_execute($gold_stmt);
$gold_result = mysqli_stmt_get_result($gold_stmt);
$user_gold = 0;
if ($gold_row = mysqli_fetch_assoc($gold_result)) {
    $user_gold = $gold_row['gold'] ?? 0;
}
mysqli_stmt_close($gold_stmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0a">
    <title>Pet Companion - MOE</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="css/pet_v2.css">
    <link rel="stylesheet" href="css/gacha_premium.css">
    <link rel="stylesheet" href="css/gacha_result_modal.css">
    <link rel="stylesheet" href="css/my_pet_premium.css">
    <link rel="stylesheet" href="css/collection_premium.css">
    <link rel="stylesheet" href="css/shop_premium.css">
    <link rel="stylesheet" href="css/daily_login.css">

    <!-- PixiJS for particles -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.3.2/pixi.min.js"></script>

    <!-- Spline 3D Viewer -->
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.0.54/build/spline-viewer.js"></script>
</head>

<body class="pet-page">
    <!-- 3D Background Layer (Spline) -->
    <div class="spline-bg-container" id="spline-bg">
        <!-- Uncomment to add 3D scene when you have a Spline URL -->
        <!-- <spline-viewer url="YOUR_SPLINE_SCENE_URL" loading-anim></spline-viewer> -->
    </div>

    <!-- Background -->
    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <!-- PixiJS Container -->
    <div id="pixi-bg-container"></div>

    <!-- App Container -->
    <div class="app-container">

        <!-- Hero Header -->
        <header class="hero-header">
            <div class="header-content">
                <div class="header-left">
                    <a href="beranda.php" class="back-btn" title="Back to Dashboard">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="header-title-group">
                        <h1 class="header-title">Pet Companion</h1>
                        <span class="header-subtitle">Virtual Pet System</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="gold-display" title="Your Gold">
                        <i class="fas fa-coins"></i>
                        <span id="user-gold"><?= number_format($user_gold) ?></span>
                    </div>
                    <button class="btn-icon" onclick="openHelpModal()" title="Help & Tutorial">
                        <i class="fas fa-question-circle"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Tab Navigation -->
        <nav class="tab-nav">
            <button class="tab-btn active" data-tab="my-pet">
                <i class="fas fa-paw"></i>
                <span>My Pet</span>
            </button>
            <button class="tab-btn" data-tab="collection">
                <i class="fas fa-th"></i>
                <span>Collection</span>
            </button>
            <button class="tab-btn" data-tab="gacha">
                <i class="fas fa-egg"></i>
                <span>Gacha</span>
            </button>
            <button class="tab-btn" data-tab="shop">
                <i class="fas fa-store"></i>
                <span>Shop</span>
            </button>
            <button class="tab-btn" data-tab="arena">
                <i class="fas fa-shield-alt"></i>
                <span>Arena</span>
            </button>
            <button class="tab-btn" data-tab="arena3v3">
                <i class="fas fa-dragon"></i>
                <span>3v3</span>
            </button>
            <button class="tab-btn" data-tab="achievements">
                <i class="fas fa-trophy"></i>
                <span>Badges</span>
            </button>
        </nav>

        <!-- Main Content -->
        <main class="main-content">

            <!-- MY PET TAB -->
            <section id="my-pet" class="tab-panel active">

                <!-- Pet Showcase - Enhanced Premium Stage -->
                <div class="my-pet-showcase" id="pet-stage">
                    <!-- Mystical Background Particles -->
                    <div class="showcase-mystical-bg">
                        <div class="mystical-orb"></div>
                        <div class="mystical-orb"></div>
                        <div class="mystical-orb"></div>
                        <div class="mystical-orb"></div>
                    </div>

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
                        <!-- Glow Rings -->
                        <div class="pet-glow-ring ring-outer"></div>
                        <div class="pet-glow-ring ring-middle"></div>
                        <div class="pet-glow-ring ring-inner"></div>

                        <!-- Pet Pedestal -->
                        <div class="pet-pedestal">
                            <div class="pedestal-glow"></div>
                        </div>

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
                                <svg class="progress-ring" width="80" height="80">
                                    <circle class="progress-ring-bg" cx="40" cy="40" r="35"></circle>
                                    <circle class="progress-ring-fill health-fill" id="health-ring" cx="40" cy="40"
                                        r="35"></circle>
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
                                <svg class="progress-ring" width="80" height="80">
                                    <circle class="progress-ring-bg" cx="40" cy="40" r="35"></circle>
                                    <circle class="progress-ring-fill hunger-fill" id="hunger-ring" cx="40" cy="40"
                                        r="35"></circle>
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
                                <svg class="progress-ring" width="80" height="80">
                                    <circle class="progress-ring-bg" cx="40" cy="40" r="35"></circle>
                                    <circle class="progress-ring-fill mood-fill" id="mood-ring" cx="40" cy="40" r="35">
                                    </circle>
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
            </section>

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
                                    <div class="rate-row">
                                        <span class="rate-label legendary">Legendary</span>
                                        <span class="rate-value">0.5%</span>
                                    </div>
                                </div>
                                <button class="gacha-summon-btn normal-btn" onclick="performGacha('normal')">
                                    <i class="fas fa-hand-sparkles"></i>
                                    <span>Summon</span>
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
                            <p><strong>Pro Tip:</strong> Premium Summon has <span class="highlight-text">3x higher
                                    Legendary rate</span> - perfect for legendary hunting!</p>
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

            <!-- SHOP TAB -->
            <section id="shop" class="tab-panel">
                <!-- Premium Shop Header -->
                <div class="shop-premium-header">
                    <h2 class="shop-title">
                        <i class="fas fa-store"></i>
                        <span>Mystical Bazaar</span>
                    </h2>
                    <p class="shop-subtitle">Acquire powerful items for your companions</p>
                </div>

                <!-- Enhanced Category Tabs -->
                <div class="shop-tabs-premium" id="shop-tabs">
                    <button class="shop-tab-pill active" data-shop="food">
                        <i class="fas fa-drumstick-bite"></i>
                        <span>Food</span>
                    </button>
                    <button class="shop-tab-pill" data-shop="potion">
                        <i class="fas fa-flask"></i>
                        <span>Potions</span>
                    </button>
                    <button class="shop-tab-pill" data-shop="special">
                        <i class="fas fa-gem"></i>
                        <span>Special</span>
                    </button>
                </div>

                <!-- Premium Grid Container -->
                <div class="shop-premium-grid" id="shop-grid">
                    <!-- Shop items rendered by JS -->
                </div>

                <!-- Enhanced Inventory Section -->
                <div class="inventory-premium-section">
                    <div class="inventory-header">
                        <h3><i class="fas fa-box-open"></i> My Inventory</h3>
                        <span class="inventory-count" id="inventory-count">0 items</span>
                    </div>
                    <div class="inventory-premium-grid" id="inventory-grid">
                        <!-- Inventory items rendered by JS -->
                    </div>
                </div>
            </section>

            <!-- ARENA TAB -->
            <section id="arena" class="tab-panel">
                <div class="arena-content">
                    <div class="arena-header">
                        <h3 class="collection-title">Battle Arena</h3>
                        <span class="arena-battles" id="arena-battles">3 / 3 Battles</span>
                    </div>
                    <div class="opponents-grid" id="opponents-grid">
                        <!-- Opponents rendered by JS -->
                    </div>
                </div>
            </section>

            <!-- 3V3 ARENA TAB -->
            <section id="arena3v3" class="tab-panel">
                <div class="arena-content">
                    <div class="arena-header">
                        <h3 class="collection-title">3v3 Team Battle</h3>
                    </div>
                    <div class="team-selection" id="team-selection">
                        <!-- Team selection rendered by JS -->
                    </div>
                    <a href="battle_3v3.php" class="btn-primary" style="width: 100%; text-align: center;">
                        <i class="fas fa-dragon"></i>
                        Enter 3v3 Arena
                    </a>
                </div>
            </section>

            <!-- ACHIEVEMENTS TAB -->
            <section id="achievements" class="tab-panel">
                <div class="achievements-grid" id="achievements-grid">
                    <!-- Achievements rendered by JS -->
                </div>
            </section>

        </main>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="beranda.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="class.php" class="nav-item">
            <i class="fas fa-book-open"></i>
            <span>Class</span>
        </a>
        <a href="pet.php" class="nav-item active">
            <i class="fas fa-paw"></i>
            <span>Pet</span>
        </a>
        <a href="trapeza.php" class="nav-item">
            <i class="fas fa-credit-card"></i>
            <span>Bank</span>
        </a>
        <a href="punishment.php" class="nav-item">
            <i class="fas fa-gavel"></i>
            <span>Rules</span>
        </a>
    </nav>

    <!-- MODALS -->

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
    </div>

    <!-- Rename Modal -->
    <div class="modal-overlay" id="rename-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Rename Pet</h3>
                <button class="modal-close" onclick="closeRenameModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-input" id="new-name-input" placeholder="Enter new name" maxlength="50">
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeRenameModal()">Cancel</button>
                <button class="btn-primary" onclick="savePetName()">Save</button>
            </div>
        </div>
    </div>

    <!-- Item Use Modal -->
    <div class="modal-overlay" id="item-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="item-modal-title">Use Item</h3>
                <button class="modal-close" onclick="closeItemModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="item-list" id="item-list">
                    <!-- Items rendered by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Shop Buy Modal -->
    <div class="modal-overlay" id="shop-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Buy Item</h3>
                <button class="modal-close" onclick="closeShopModal()">&times;</button>
            </div>
            <div class="modal-body" id="shop-modal-body">
                <!-- Item details rendered by JS -->
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeShopModal()">Cancel</button>
                <button class="btn-primary" id="shop-buy-btn">Buy</button>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal-overlay" id="help-modal">
        <div class="modal-content" style="max-width: 500px; max-height: 80vh;">
            <div class="modal-header">
                <h3 class="modal-title">Help & Tutorial</h3>
                <button class="modal-close" onclick="closeHelpModal()">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <div class="help-section">
                    <h4 style="color: var(--gold); margin-bottom: 12px;"><i class="fas fa-paw"></i> Getting Started</h4>
                    <p style="color: #aaa; line-height: 1.6; margin-bottom: 16px;">
                        Get your first pet from the Gacha tab! Use gold to roll for random pets with different elements
                        and rarities.
                    </p>
                </div>
                <div class="help-section">
                    <h4 style="color: var(--gold); margin-bottom: 12px;"><i class="fas fa-heart"></i> Taking Care</h4>
                    <p style="color: #aaa; line-height: 1.6; margin-bottom: 16px;">
                        Feed your pet to restore hunger, play to boost mood, and heal when health is low. Stats decay
                        over time!
                    </p>
                </div>
                <div class="help-section">
                    <h4 style="color: var(--gold); margin-bottom: 12px;"><i class="fas fa-shield-alt"></i> Battle</h4>
                    <p style="color: #aaa; line-height: 1.6;">
                        Enter the Arena to battle other pets! Win to earn EXP, gold, and level up your companion.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sell Pet Modal -->
    <div class="modal-overlay" id="sell-modal">
        <div class="modal-content" style="max-width: 320px;">
            <div class="modal-header">
                <h3 class="modal-title">Sell Pet</h3>
                <button class="modal-close" onclick="closeSellModal()">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 16px;">
                <img src="" alt="" id="sell-pet-img"
                    style="width: 70px; height: 70px; object-fit: contain; margin-bottom: 8px;">
                <h3 id="sell-pet-name" style="color: #fff; margin-bottom: 2px; font-size: 1rem;"></h3>
                <span id="sell-pet-level" style="color: var(--gold); font-size: 0.8rem;"></span>
                <div
                    style="margin-top: 12px; padding: 12px; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 10px;">
                    <p style="color: #888; font-size: 0.75rem; margin-bottom: 6px;">You will receive:</p>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="fas fa-coins" style="color: #FFD700; font-size: 1rem;"></i>
                        <span id="sell-price" style="color: #FFD700; font-size: 1.2rem; font-weight: 700;"></span>
                        <span style="color: #888; font-size: 0.85rem;">Gold</span>
                    </div>
                </div>
                <p style="color: #E74C3C; font-size: 0.7rem; margin-top: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> This action cannot be undone!
                </p>
            </div>
            <div class="modal-footer" style="padding: 12px; gap: 8px;">
                <button class="btn-secondary" onclick="closeSellModal()"
                    style="padding: 8px 16px; font-size: 0.8rem;">Cancel</button>
                <button class="btn-primary" id="confirm-sell-btn" onclick="confirmSellPet()"
                    style="background: linear-gradient(135deg, #E74C3C, #C0392B); padding: 8px 16px; font-size: 0.8rem;">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Evolution Modal -->
    <div class="modal-overlay" id="evolution-modal">
        <div class="modal-content" style="max-width: 500px; max-height: 80vh;">
            <div class="modal-header">
                <h3 class="modal-title" id="evo-title">Evolve Pet</h3>
                <button class="modal-close" onclick="closeEvolutionModal()">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <div
                    style="text-align: center; margin-bottom: 16px; padding: 16px; background: rgba(155, 89, 182, 0.1); border-radius: 12px;">
                    <p style="color: #888; font-size: 0.85rem;">Current Stage: <span id="evo-current-stage"
                            style="color: var(--gold);">Egg</span></p>
                    <p style="color: #888; font-size: 0.85rem;">Next Stage: <span id="evo-next-stage"
                            style="color: #9B59B6;">Baby</span></p>
                    <p style="color: #888; font-size: 0.85rem;">Required Level: <span id="evo-required-level"
                            style="color: var(--gold);">10</span></p>
                    <p style="color: #888; font-size: 0.85rem;">Required Rarity: <span id="evo-required-rarity"
                            style="color: var(--gold);">-</span></p>
                </div>
                <div style="margin-bottom: 12px;">
                    <p style="color: #fff; font-weight: 600; margin-bottom: 8px;">Select 3 Fodder Pets (<span
                            id="evo-selected-count">0</span>/3)</p>
                    <p style="color: #666; font-size: 0.8rem;">These pets will be consumed in the evolution.</p>
                </div>
                <div class="fodder-grid" id="fodder-grid"
                    style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; max-height: 200px; overflow-y: auto;">
                    <!-- Fodder cards rendered by JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeEvolutionModal()">Cancel</button>
                <button class="btn-primary" id="confirm-evolution-btn" onclick="confirmEvolution()" disabled
                    style="background: linear-gradient(135deg, #9B59B6, #8E44AD);">
                    <i class="fas fa-star"></i> Evolve
                </button>
            </div>
        </div>
    </div>

    <!-- Evolution Confirm Modal -->
    <div class="modal-overlay" id="evolution-confirm-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Evolution</h3>
                <button class="modal-close" onclick="closeEvoConfirmModal()">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <i class="fas fa-exclamation-triangle"
                    style="font-size: 3rem; color: #F39C12; margin-bottom: 16px;"></i>
                <p style="color: #fff; margin-bottom: 8px;">Are you sure you want to evolve?</p>
                <p style="color: #E74C3C; font-size: 0.85rem;">The 3 selected fodder pets will be permanently
                    consumed!</p>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeEvoConfirmModal()">Cancel</button>
                <button class="btn-primary" id="proceed-evolution-btn" onclick="proceedEvolution()"
                    style="background: linear-gradient(135deg, #9B59B6, #8E44AD);">
                    <i class="fas fa-star"></i> Proceed with Evolution
                </button>
            </div>
        </div>
    </div>

    <!-- Daily Login Modal -->
    <div class="daily-login-modal" id="daily-login-modal">
        <div class="daily-login-content">
            <button class="daily-close-btn" onclick="closeDailyModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="daily-login-header">
                <h2>🎁 Daily Login Reward!</h2>
                <p>Day <span id="daily-current-day">1</span> of 30</p>
            </div>
            <div class="daily-calendar" id="daily-calendar"></div>
            <div class="daily-reward-display">
                <div class="reward-label">Today's Reward</div>
                <div class="reward-content" id="daily-reward-content">
                    <i class="fas fa-coins reward-gold"></i>
                    <span id="daily-reward-text">50 Gold</span>
                </div>
            </div>
            <div class="streak-counter">
                <i class="fas fa-fire"></i>
                <span>Total Logins: <strong id="daily-total-logins">0</strong></span>
            </div>
            <button class="claim-reward-btn" id="claim-reward-btn" onclick="claimDailyReward()">
                <i class="fas fa-gift"></i> Claim Reward!
            </button>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/pixi_bg.js"></script>
    <script src="js/pet.js"></script>
    <script src="js/pixi_pet.js"></script>
    <script src="js/pet_animations.js"></script>
    <script src="js/pet_hardcore_update.js"></script>

    <style>
        /* Additional inline styles for modals and forms */
        .form-input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-gold);
            border-radius: var(--radius-md);
            color: #fff;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--gold);
            background: rgba(0, 0, 0, 0.6);
            box-shadow: 0 0 20px rgba(218, 165, 32, 0.2);
        }

        .shop-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .shop-tab {
            padding: 10px 20px;
            background: transparent;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-full);
            color: #666;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .shop-tab.active {
            background: rgba(218, 165, 32, 0.15);
            border-color: var(--gold);
            color: var(--gold);
        }

        .shop-grid,
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .shop-item,
        .inventory-item {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .shop-item:hover,
        .inventory-item:hover {
            border-color: var(--border-gold);
            transform: translateY(-2px);
        }

        .shop-item img,
        .inventory-item img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .shop-item-name,
        .inventory-item-name {
            font-size: 0.75rem;
            color: #ccc;
            margin-bottom: 4px;
        }

        .shop-item-price {
            font-size: 0.7rem;
            color: var(--gold);
            font-weight: 700;
        }

        .inventory-item-qty {
            font-size: 0.65rem;
            color: #888;
        }

        .inventory-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border-subtle);
        }

        .arena-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .arena-battles {
            padding: 6px 14px;
            background: rgba(218, 165, 32, 0.1);
            border: 1px solid var(--border-gold);
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            color: var(--gold);
        }

        .opponents-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .opponent-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .opponent-card:hover {
            border-color: var(--border-gold);
            transform: translateX(4px);
        }

        .opponent-img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .opponent-info {
            flex: 1;
        }

        .opponent-name {
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }

        .opponent-level {
            font-size: 0.8rem;
            color: var(--gold);
        }

        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .achievement-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 16px;
            text-align: center;
        }

        .achievement-card.unlocked {
            border-color: var(--gold);
        }

        .achievement-card.locked {
            opacity: 0.5;
            filter: grayscale(50%);
        }

        .achievement-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .achievement-name {
            font-size: 0.75rem;
            color: #ccc;
        }

        .item-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .item-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .item-row:hover {
            background: rgba(218, 165, 32, 0.1);
        }

        .item-row img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #fff;
        }

        .item-qty {
            font-size: 0.8rem;
            color: #888;
        }

        @media (min-width: 480px) {

            .shop-grid,
            .inventory-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .achievements-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>

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

    <!-- ============================================ -->
    <!-- SHOP MODALS -->
    <!-- ============================================ -->
    
    <!-- Shop Purchase Modal -->
    <div class="modal" id="shop-purchase-modal">
        <div class="modal-backdrop" onclick="closeShopPurchaseModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-shopping-cart"></i>
                    Purchase Item
                </h3>
                <button class="modal-close" onclick="closeShopPurchaseModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <img id="shop-modal-img" src="" alt="Item">
                <h4 id="shop-modal-name">Item Name</h4>
                <p id="shop-modal-desc">Item description</p>
                
                <div class="shop-qty-controls">
                    <button class="qty-btn" onclick="adjustShopQty(-1)">-</button>
                    <input type="number" id="shop-qty-input" value="1" min="1" max="99" 
                           onchange="updateShopTotal()">
                    <button class="qty-btn" onclick="adjustShopQty(1)">+</button>
                </div>
                
                <div class="shop-price-summary">
                    <div class="price-row">
                        <span class="price-label">Unit Price:</span>
                        <span class="price-value">
                            <i class="fas fa-coins"></i>
                            <span id="shop-unit-price">0</span>
                        </span>
                    </div>
                    <div class="price-row">
                        <span class="price-label">Total:</span>
                        <span class="price-value">
                            <i class="fas fa-coins"></i>
                            <span id="shop-total-price">0</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-btn-cancel" onclick="closeShopPurchaseModal()">Cancel</button>
                <button class="modal-btn-confirm" onclick="confirmShopPurchase()">
                    <i class="fas fa-check"></i>
                    Confirm Purchase
                </button>
            </div>
        </div>
    </div>

    <!-- Revive Pet Modal -->
    <div class="modal" id="revive-modal">
        <div class="modal-backdrop" onclick="closeReviveModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-heart"></i>
                    Revive Pet
                </h3>
                <button class="modal-close" onclick="closeReviveModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p style="text-align: center; color: rgba(255,255,255,0.8); margin-bottom: 1.5rem;">
                    Select a dead pet to revive:
                </p>
                <div id="dead-pets-list" class="dead-pets-grid">
                    <!-- Dead pets will be rendered here by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Use Modal -->
    <div class="modal" id="bulk-use-modal">
        <div class="modal-backdrop" onclick="closeBulkModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-boxes"></i>
                    <span id="bulk-modal-title">Use Item</span>
                </h3>
                <button class="modal-close" onclick="closeBulkModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <img id="bulk-item-img" src="" alt="Item" style="width: 100px; height: 100px; object-fit: contain; margin: 1rem auto; display: block;">
                <p id="bulk-item-desc" style="text-align: center; color: rgba(255,255,255,0.7); margin-bottom: 1.5rem;"></p>
                
                <div class="shop-qty-controls">
                    <button class="qty-btn" onclick="adjustQty(-1)">-</button>
                    <input type="number" id="bulk-item-qty" value="1" min="1" max="99">
                    <button class="qty-btn" onclick="adjustQty(1)">+</button>
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-btn-cancel" onclick="closeBulkModal()">Cancel</button>
                <button class="modal-btn-confirm" onclick="confirmBulkUse()">
                    <i class="fas fa-check"></i>
                    Use Item
                </button>
            </div>
        </div>
    </div>

</body>

</html>