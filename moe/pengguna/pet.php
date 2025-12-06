<?php
/**
 * MOE Pet System - Main Pet Page
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Mobile-First Design with "Fake 3D" CSS Animations
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

// Get user gold (add column if doesn't exist)
$gold_result = mysqli_query($conn, "SELECT gold FROM nethera WHERE id_nethera = $user_id");
$user_gold = 0;
if ($gold_row = mysqli_fetch_assoc($gold_result)) {
    $user_gold = $gold_row['gold'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pet Companion - MOE</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../assets/css/landing-style.css">
    <link rel="stylesheet" href="css/pet.css">
</head>

<body class="pet-page">

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <!-- Main Container -->
    <div class="pet-container">

        <!-- Header -->
        <header class="pet-header">
            <a href="beranda.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="header-title">Pet Companion</h1>
            <div class="gold-display">
                <i class="fas fa-coins"></i>
                <span id="user-gold"><?php echo number_format($user_gold); ?></span>
            </div>
        </header>

        <!-- Tab Navigation -->
        <nav class="pet-tabs">
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
                <i class="fas fa-swords"></i>
                <span>Arena</span>
            </button>
        </nav>

        <!-- Tab Content -->
        <main class="pet-content">

            <!-- MY PET TAB -->
            <section id="my-pet" class="tab-content active">
                <div class="pet-stage" id="pet-stage">
                    <!-- Pet will be rendered here -->
                    <div class="no-pet-message">
                        <i class="fas fa-egg fa-3x"></i>
                        <p>No active pet!</p>
                        <button class="action-btn primary" onclick="switchTab('gacha')">
                            Get Your First Pet
                        </button>
                    </div>
                </div>

                <!-- Pet Info Card -->
                <div class="pet-info-card" id="pet-info" style="display: none;">
                    <div class="pet-name-row">
                        <h2 class="pet-name" id="pet-name">Loading...</h2>
                        <span class="pet-level" id="pet-level">Lv.1</span>
                    </div>
                    <div class="pet-element" id="pet-element">
                        <span class="element-badge fire">Fire</span>
                        <span class="rarity-badge common">Common</span>
                    </div>

                    <!-- Status Bars -->
                    <div class="status-bars">
                        <div class="status-bar">
                            <div class="status-label">
                                <i class="fas fa-heart"></i>
                                <span>Health</span>
                            </div>
                            <div class="bar-container">
                                <div class="bar-fill health" id="health-bar" style="width: 100%"></div>
                            </div>
                            <span class="status-value" id="health-value">100</span>
                        </div>
                        <div class="status-bar">
                            <div class="status-label">
                                <i class="fas fa-drumstick-bite"></i>
                                <span>Hunger</span>
                            </div>
                            <div class="bar-container">
                                <div class="bar-fill hunger" id="hunger-bar" style="width: 100%"></div>
                            </div>
                            <span class="status-value" id="hunger-value">100</span>
                        </div>
                        <div class="status-bar">
                            <div class="status-label">
                                <i class="fas fa-smile"></i>
                                <span>Mood</span>
                            </div>
                            <div class="bar-container">
                                <div class="bar-fill mood" id="mood-bar" style="width: 100%"></div>
                            </div>
                            <span class="status-value" id="mood-value">100</span>
                        </div>
                    </div>

                    <!-- EXP Bar -->
                    <div class="exp-container">
                        <div class="exp-label">EXP</div>
                        <div class="exp-bar-container">
                            <div class="exp-bar-fill" id="exp-bar" style="width: 0%"></div>
                        </div>
                        <span class="exp-text" id="exp-text">0 / 100</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-grid" id="action-buttons" style="display: none;">
                    <button class="action-btn feed" id="btn-feed">
                        <i class="fas fa-bone"></i>
                        <span>Feed</span>
                    </button>
                    <button class="action-btn play" id="btn-play">
                        <i class="fas fa-futbol"></i>
                        <span>Play</span>
                    </button>
                    <button class="action-btn heal" id="btn-heal">
                        <i class="fas fa-flask"></i>
                        <span>Heal</span>
                    </button>
                    <button class="action-btn shelter" id="btn-shelter">
                        <i class="fas fa-home"></i>
                        <span>Shelter</span>
                    </button>
                </div>
            </section>

            <!-- COLLECTION TAB -->
            <section id="collection" class="tab-content">
                <div class="collection-grid" id="collection-grid">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Loading pets...</p>
                    </div>
                </div>
            </section>

            <!-- GACHA TAB -->
            <section id="gacha" class="tab-content">
                <div class="gacha-stage">
                    <div class="gacha-egg-container">
                        <img src="../assets/pets/gacha_egg.png" alt="Gacha Egg" class="gacha-egg" id="gacha-egg">
                        <div class="gacha-glow"></div>
                    </div>
                </div>

                <div class="gacha-options">
                    <div class="gacha-card" data-type="1">
                        <div class="gacha-icon bronze">
                            <i class="fas fa-egg"></i>
                        </div>
                        <h3>Bronze Summon</h3>
                        <p>All rarities available</p>
                        <div class="gacha-price">
                            <i class="fas fa-coins"></i>
                            <span>100</span>
                        </div>
                        <button class="gacha-btn" onclick="performGacha(1)">Summon</button>
                    </div>

                    <div class="gacha-card" data-type="2">
                        <div class="gacha-icon silver">
                            <i class="fas fa-egg"></i>
                        </div>
                        <h3>Silver Summon</h3>
                        <p>Rare+ guaranteed</p>
                        <div class="gacha-price">
                            <i class="fas fa-coins"></i>
                            <span>150</span>
                        </div>
                        <button class="gacha-btn" onclick="performGacha(2)">Summon</button>
                    </div>

                    <div class="gacha-card" data-type="3">
                        <div class="gacha-icon gold">
                            <i class="fas fa-egg"></i>
                        </div>
                        <h3>Golden Summon</h3>
                        <p>Epic+ guaranteed</p>
                        <div class="gacha-price">
                            <i class="fas fa-coins"></i>
                            <span>500</span>
                        </div>
                        <button class="gacha-btn" onclick="performGacha(3)">Summon</button>
                    </div>
                </div>

                <div class="gacha-rates">
                    <h4>Drop Rates (Bronze)</h4>
                    <div class="rate-row">
                        <span class="rate-label common">Common</span>
                        <span class="rate-value">60%</span>
                    </div>
                    <div class="rate-row">
                        <span class="rate-label rare">Rare</span>
                        <span class="rate-value">25%</span>
                    </div>
                    <div class="rate-row">
                        <span class="rate-label epic">Epic</span>
                        <span class="rate-value">12%</span>
                    </div>
                    <div class="rate-row">
                        <span class="rate-label legendary">Legendary</span>
                        <span class="rate-value">3%</span>
                    </div>
                </div>
            </section>

            <!-- SHOP TAB -->
            <section id="shop" class="tab-content">
                <div class="shop-tabs">
                    <button class="shop-tab active" data-category="food">Food</button>
                    <button class="shop-tab" data-category="potion">Potions</button>
                    <button class="shop-tab" data-category="special">Special</button>
                </div>
                <div class="shop-grid" id="shop-grid">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Loading shop...</p>
                    </div>
                </div>

                <!-- Inventory Section -->
                <div class="inventory-section">
                    <h3 class="section-title">My Inventory</h3>
                    <div class="inventory-grid" id="inventory-grid">
                        <p class="empty-message">No items yet</p>
                    </div>
                </div>
            </section>

            <!-- ARENA TAB -->
            <section id="arena" class="tab-content">
                <div class="arena-header">
                    <h2>Battle Arena</h2>
                    <p>Challenge other pets to async battles!</p>
                </div>

                <div class="arena-tabs">
                    <button class="arena-tab active" data-view="opponents">Find Opponent</button>
                    <button class="arena-tab" data-view="history">Battle History</button>
                </div>

                <div class="arena-content" id="arena-opponents">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Finding opponents...</p>
                    </div>
                </div>

                <div class="arena-content" id="arena-history" style="display: none;">
                    <div class="battle-history-list" id="battle-history">
                        <p class="empty-message">No battles yet</p>
                    </div>
                </div>
            </section>

        </main>

    </div>

    <!-- Gacha Result Modal -->
    <div class="modal" id="gacha-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content gacha-result">
            <div class="result-glow" id="result-glow"></div>
            <div class="result-pet" id="result-pet">
                <img src="" alt="New Pet" id="result-pet-img">
            </div>
            <h2 class="result-title" id="result-title">Congratulations!</h2>
            <div class="result-info">
                <span class="result-name" id="result-name">Pet Name</span>
                <span class="result-rarity" id="result-rarity">Common</span>
            </div>
            <p class="result-shiny" id="result-shiny" style="display: none;">✨ SHINY! ✨</p>
            <button class="modal-close-btn" onclick="closeGachaModal()">Awesome!</button>
        </div>
    </div>

    <!-- Battle Result Modal -->
    <div class="modal" id="battle-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content battle-result">
            <h2 class="battle-title" id="battle-title">Battle Result</h2>
            <div class="battle-pets">
                <div class="battle-pet attacker">
                    <div class="battle-pet-img"></div>
                    <span class="battle-pet-name" id="battle-atk-name">Your Pet</span>
                    <div class="battle-hp">
                        <span id="battle-atk-hp">100</span> HP
                    </div>
                </div>
                <div class="battle-vs">VS</div>
                <div class="battle-pet defender">
                    <div class="battle-pet-img"></div>
                    <span class="battle-pet-name" id="battle-def-name">Opponent</span>
                    <div class="battle-hp">
                        <span id="battle-def-hp">100</span> HP
                    </div>
                </div>
            </div>
            <div class="battle-log" id="battle-log">
                <!-- Battle log entries will be inserted here -->
            </div>
            <div class="battle-rewards" id="battle-rewards" style="display: none;">
                <span class="reward-item"><i class="fas fa-coins"></i> +<span id="reward-gold">0</span></span>
                <span class="reward-item"><i class="fas fa-star"></i> +<span id="reward-exp">0</span> EXP</span>
            </div>
            <button class="modal-close-btn" onclick="closeBattleModal()">Close</button>
        </div>
    </div>

    <!-- Item Use Modal -->
    <div class="modal" id="item-modal">
        <div class="modal-backdrop" onclick="closeItemModal()"></div>
        <div class="modal-content item-select">
            <h2>Select Item</h2>
            <div class="item-list" id="item-list">
                <!-- Items will be loaded here -->
            </div>
            <button class="modal-cancel-btn" onclick="closeItemModal()">Cancel</button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="toast-icon fas fa-check-circle"></i>
        <span class="toast-message">Message</span>
    </div>

    <!-- JavaScript -->
    <script src="js/pet.js"></script>

</body>

</html>