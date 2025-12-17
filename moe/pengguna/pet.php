<?php
/**
 * MOE Pet System - Main Pet Page V2
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Complete Redesign - Premium Dark Egyptian Fantasy Theme
 * Mobile-First with Desktop Support
 */

session_start();
include '../connection.php';

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

                <!-- Pet Showcase - Premium Stage -->
                <div class="pet-showcase" id="pet-stage">
                    <!-- Ambient particle effects -->
                    <div class="ambient-particles"></div>
                    <!-- Floor glow effect -->
                    <div class="floor-glow"></div>

                    <div class="no-pet-state" id="no-pet-message">
                        <i class="fas fa-egg"></i>
                        <p>No active pet!</p>
                        <button class="btn-primary" onclick="switchTab('gacha')">
                            <i class="fas fa-sparkles"></i>
                            Get Your First Pet
                        </button>
                    </div>
                    <!-- Pet will be rendered here by JS -->
                </div>

                <!-- Pet Info Card -->
                <div class="info-card" id="pet-info" style="display: none;">
                    <div class="pet-name-row">
                        <h2 class="pet-name" id="pet-name">Loading...</h2>
                        <button class="btn-rename" onclick="openRenameModal()" title="Rename">
                            <i class="fas fa-edit"></i>
                        </button>
                        <span class="pet-level" id="pet-level">Lv.1</span>
                    </div>

                    <div class="badge-row" id="pet-element">
                        <span class="badge fire">Fire</span>
                        <span class="badge common">Common</span>
                    </div>

                    <!-- Status Bars -->
                    <div class="stats-grid">
                        <div class="stat-row">
                            <div class="stat-label">
                                <i class="fas fa-heart"></i>
                                <span>HP</span>
                            </div>
                            <div class="stat-bar">
                                <div class="stat-bar-fill health" id="health-bar" style="width: 100%"></div>
                            </div>
                            <span class="stat-value" id="health-value">100</span>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">
                                <i class="fas fa-drumstick-bite"></i>
                                <span>Hunger</span>
                            </div>
                            <div class="stat-bar">
                                <div class="stat-bar-fill hunger" id="hunger-bar" style="width: 100%"></div>
                            </div>
                            <span class="stat-value" id="hunger-value">100</span>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">
                                <i class="fas fa-smile"></i>
                                <span>Mood</span>
                            </div>
                            <div class="stat-bar">
                                <div class="stat-bar-fill mood" id="mood-bar" style="width: 100%"></div>
                            </div>
                            <span class="stat-value" id="mood-value">100</span>
                        </div>
                    </div>

                    <!-- EXP Bar -->
                    <div class="exp-section">
                        <div class="exp-header">
                            <span class="exp-label">Experience</span>
                            <span class="exp-text" id="exp-text">0 / 100</span>
                        </div>
                        <div class="exp-bar">
                            <div class="exp-bar-fill" id="exp-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-grid" id="action-buttons" style="display: none;">
                    <button class="action-btn feed" id="btn-feed">
                        <i class="fas fa-bone"></i>
                        <span>Feed</span>
                    </button>
                    <button class="action-btn play" id="btn-play">
                        <i class="fas fa-gamepad"></i>
                        <span>Play</span>
                    </button>
                    <button class="action-btn heal" id="btn-heal">
                        <i class="fas fa-heart"></i>
                        <span>Heal</span>
                    </button>
                    <button class="action-btn shelter" id="btn-shelter">
                        <i class="fas fa-home"></i>
                        <span>Shelter</span>
                    </button>
                </div>
            </section>

            <!-- COLLECTION TAB -->
            <section id="collection" class="tab-panel">
                <div class="collection-header">
                    <h3 class="collection-title">My Pets</h3>
                    <span class="collection-count" id="pet-count-badge">0 / 25</span>
                </div>
                <div class="collection-grid" id="collection-grid">
                    <!-- Pet cards rendered by JS -->
                </div>
            </section>

            <!-- GACHA TAB -->
            <section id="gacha" class="tab-panel">
                <div class="gacha-section">
                    <div class="gacha-display">
                        <img src="../assets/pets/default/egg.png" alt="Gacha Egg" class="gacha-egg" id="gacha-egg">
                    </div>
                    <div class="gacha-buttons">
                        <button class="gacha-btn normal" onclick="performGacha('normal')">
                            <i class="fas fa-egg"></i>
                            Normal Gacha
                            <span class="gacha-cost">
                                <i class="fas fa-coins"></i> 100
                            </span>
                        </button>
                        <button class="gacha-btn premium" onclick="performGacha('premium')">
                            <i class="fas fa-star"></i>
                            Premium Gacha
                            <span class="gacha-cost">
                                <i class="fas fa-coins"></i> 500
                            </span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- SHOP TAB -->
            <section id="shop" class="tab-panel">
                <div class="shop-tabs" id="shop-tabs">
                    <button class="shop-tab active" data-shop="food">Food</button>
                    <button class="shop-tab" data-shop="potion">Potions</button>
                    <button class="shop-tab" data-shop="special">Special</button>
                </div>
                <div class="shop-grid" id="shop-grid">
                    <!-- Shop items rendered by JS -->
                </div>
                <div class="inventory-section">
                    <h3 class="collection-title">My Inventory</h3>
                    <div class="inventory-grid" id="inventory-grid">
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

    <!-- Gacha Result Modal -->
    <div class="modal-overlay" id="gacha-result-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">New Pet!</h3>
                <button class="modal-close" onclick="closeGachaModal()">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <img src="" alt="" class="gacha-result-img" id="gacha-result-img"
                    style="width: 150px; height: 150px; object-fit: contain; margin-bottom: 16px;">
                <h2 class="gacha-result-name" id="gacha-result-name"></h2>
                <div class="badge-row" id="gacha-result-badges" style="justify-content: center;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="closeGachaModal()" style="width: 100%;">
                    <i class="fas fa-check"></i> Awesome!
                </button>
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

</body>

</html>