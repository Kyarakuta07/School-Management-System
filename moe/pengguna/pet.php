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
    <link rel="stylesheet" href="css/pet_hardcore_update.css">
    <link rel="stylesheet" href="css/pet_help_guide.css">
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
            <div class="header-actions">
                <div class="gold-display">
                    <i class="fas fa-coins"></i>
                    <span id="user-gold"><?php echo number_format($user_gold); ?></span>
                </div>
                <button class="btn-help" onclick="openHelpModal()" title="Tutorial & Help">
                    <i class="fas fa-question-circle"></i>
                </button>
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
                        <button class="btn-rename-icon" onclick="openRenameModal()" title="Rename Pet">
                            <i class="fas fa-edit"></i>
                        </button>
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

    <div class="modal" id="bulk-use-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content item-detail-card">
            <div class="item-header">
                <img src="" id="bulk-item-img" class="item-detail-img">
                <div class="item-detail-info">
                    <h3 id="bulk-item-name">Item Name</h3>
                    <p id="bulk-item-desc">Description here...</p>
                    <span class="item-stock">Owned: <b id="bulk-item-stock">0</b></span>
                </div>
            </div>

            <div class="quantity-selector">
                <label>Use Quantity:</label>
                <div class="qty-controls">
                    <button type="button" class="qty-btn" onclick="adjustQty(-1)">-</button>
                    <input type="number" id="bulk-item-qty" value="1" min="1" readonly>
                    <button type="button" class="qty-btn" onclick="adjustQty(1)">+</button>
                    <button type="button" class="qty-btn max" onclick="setMaxQty()">MAX</button>
                </div>
            </div>

            <div class="modal-actions">
                <button class="modal-cancel-btn" onclick="closeBulkModal()">Cancel</button>
                <button class="modal-confirm-btn" onclick="confirmBulkUse()">
                    Use Item
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================
         RHYTHM GAME MODAL
         ======================================== -->
    <div class="modal" id="rhythm-modal">
        <div class="modal-backdrop" onclick="closeRhythmGame()"></div>
        <div class="modal-content rhythm-game-container">
            <div class="rhythm-header">
                <h2>🎵 Rhythm Game</h2>
                <div class="rhythm-stats">
                    <span class="rhythm-score">Score: <b id="rhythm-score-display">0</b></span>
                    <span class="rhythm-timer" id="rhythm-timer">30s</span>
                </div>
            </div>

            <div class="rhythm-game-area" id="rhythm-game-area">
                <!-- Pet Dancing Animation -->
                <div class="rhythm-pet-container">
                    <img id="rhythm-pet-img" src="" alt="Dancing Pet" class="rhythm-pet-dance">
                </div>

                <!-- Falling Notes Container -->
                <div class="rhythm-notes-container" id="rhythm-notes-container">
                    <!-- Notes will be spawned dynamically via JS -->
                </div>

                <!-- Hit Zone Indicator -->
                <div class="rhythm-hit-zone">
                    <div class="hit-indicator">TAP HERE!</div>
                </div>
            </div>

            <div class="rhythm-instructions">
                <p>Tap/Click the falling notes when they reach the bottom!</p>
            </div>

            <button class="modal-cancel-btn" onclick="closeRhythmGame()">Exit</button>
        </div>
    </div>

    <!-- ========================================
         EVOLUTION SELECTOR MODAL
         ======================================== -->
    <div class="modal" id="evolution-modal">
        <div class="modal-backdrop" onclick="closeEvolutionModal()"></div>
        <div class="modal-content evolution-selector">
            <h2>🔮 Manual Evolution</h2>

            <div class="evolution-info">
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Main Pet: Level 20+</li>
                    <li>Sacrifice: 3 pets of <span id="evo-required-rarity">same rarity</span></li>
                    <li>Cost: <i class="fas fa-coins"></i> 500 Gold</li>
                </ul>
            </div>

            <div class="evolution-warning">
                ⚠️ Sacrificed pets will be permanently deleted!
            </div>

            <div class="fodder-selection-title">
                <h3>Select 3 Fodder Pets (<span id="evo-selected-count">0</span>/3)</h3>
            </div>

            <div class="fodder-grid" id="fodder-grid">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading candidates...</p>
                </div>
            </div>

            <div class="modal-actions">
                <button class="modal-cancel-btn" onclick="closeEvolutionModal()">Cancel</button>
                <button class="modal-confirm-btn" id="confirm-evolution-btn" onclick="confirmEvolution()" disabled>
                    <i class="fas fa-star"></i> Evolve (500 Gold)
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================
         RENAME PET MODAL
         ======================================== -->
    <div class="modal" id="rename-modal">
        <div class="modal-backdrop" onclick="closeRenameModal()"></div>
        <div class="modal-content rename-dialog">
            <h2>✏️ Rename Pet</h2>
            <input type="text" id="rename-input" class="rename-input" placeholder="Enter new nickname..."
                maxlength="50">
            <div class="modal-actions">
                <button class="modal-cancel-btn" onclick="closeRenameModal()">Cancel</button>
                <button class="modal-confirm-btn" onclick="confirmRename()">
                    <i class="fas fa-check"></i> Save
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="toast-icon fas fa-check-circle"></i>
        <span class="toast-message">Message</span>
    </div>

    <div class="modal" id="bulk-use-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content item-detail-card">
            <div class="item-header">
                <img src="" id="bulk-item-img" class="item-detail-img">
                <div class="item-detail-info">
                    <h3 id="bulk-item-name">Item Name</h3>
                    <p id="bulk-item-desc">Description here...</p>
                    <span class="item-stock">Owned: <b id="bulk-item-stock">0</b></span>
                </div>
            </div>

            <div class="quantity-selector">
                <label>Use Quantity:</label>
                <div class="qty-controls">
                    <button type="button" class="qty-btn" onclick="adjustQty(-1)">-</button>
                    <input type="number" id="bulk-item-qty" value="1" min="1" readonly>
                    <button type="button" class="qty-btn" onclick="adjustQty(1)">+</button>
                    <button type="button" class="qty-btn max" onclick="setMaxQty()">MAX</button>
                </div>
            </div>

            <div class="modal-actions">
                <button class="modal-cancel-btn" onclick="closeBulkModal()">Cancel</button>
                <button class="modal-confirm-btn" onclick="confirmBulkUse()">
                    Use Item
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================
         TUTORIAL / HELP MODAL
         ======================================== -->
    <div class="modal" id="help-modal">
        <div class="modal-backdrop" onclick="closeHelpModal()"></div>
        <div class="modal-content help-guide">
            <button class="modal-close-btn" onclick="closeHelpModal()">
                <i class="fas fa-times"></i>
            </button>

            <h2>📖 Tutorial & Panduan Fitur</h2>

            <div class="help-tabs">
                <button class="help-tab active" onclick="switchHelpTab('overview')">Overview</button>
                <button class="help-tab" onclick="switchHelpTab('rhythm')">Rhythm Game</button>
                <button class="help-tab" onclick="switchHelpTab('battle')">Hardcore Battle</button>
                <button class="help-tab" onclick="switchHelpTab('evolution')">Evolution</button>
                <button class="help-tab" onclick="switchHelpTab('economy')">Sell & Rename</button>
            </div>

            <!-- Overview Tab -->
            <div class="help-content" id="help-overview">
                <h3>🎮 Fitur Baru: Hardcore & Rhythm Game Update</h3>
                <p>Sistem Pet sekarang lebih menantang dengan fitur baru:</p>

                <div class="feature-list">
                    <div class="feature-item">
                        <i class="fas fa-music feature-icon"></i>
                        <div>
                            <strong>Rhythm Game</strong>
                            <p>Main mini-game untuk dapat Mood & EXP</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <i class="fas fa-heart-broken feature-icon"></i>
                        <div>
                            <strong>Hardcore Battle</strong>
                            <p>Pet bisa MATI kalau HP habis! (-20 HP per kalah)</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <i class="fas fa-star feature-icon"></i>
                        <div>
                            <strong>Manual Evolution</strong>
                            <p>Korbankan 3 pet untuk evolve pet utama (Lv.20+)</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <i class="fas fa-coins feature-icon"></i>
                        <div>
                            <strong>Sell & Rename</strong>
                            <p>Jual pet untuk gold, atau ganti nama sesuai keinginan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rhythm Game Tab -->
            <div class="help-content" id="help-rhythm" style="display: none;">
                <h3>🎵 Rhythm Game</h3>

                <div class="tutorial-step">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <h4>Cara Main</h4>
                        <p>Klik tombol <strong>"Play"</strong> di tab "My Pet" (hanya jika pet status ALIVE)</p>
                    </div>
                </div>

                <div class="tutorial-step">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <h4>Gameplay</h4>
                        <p>• Not akan jatuh dari atas ke bawah<br>
                            • <strong>Klik/Tap</strong> not saat mencapai zona hijau di bawah<br>
                            • Tiap hit = <strong>+10 poin</strong><br>
                            • Durasi: <strong>30 detik</strong></p>
                    </div>
                </div>

                <div class="tutorial-step">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <h4>Reward</h4>
                        <p>• Score 0-100 = Mood +0-10, EXP +0-16<br>
                            • Score 100-300 = Mood +10-30, EXP +16-50<br>
                            • <strong>Max reward:</strong> Mood +30, EXP +50</p>
                    </div>
                </div>

                <div class="help-tip warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Pet yang DEAD tidak bisa main rhythm game!
                </div>
            </div>

            <!--Hardcore Battle Tab -->
            <div class="help-content" id="help-battle" style="display: none;">
                <h3>⚔️ Hardcore Battle System</h3>

                <div class="tutorial-step">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <h4>HP System</h4>
                        <p>Setiap pet punya <strong>HP (Hit Points)</strong> terpisah dari Health/Mood.<br>
                            HP awal: <strong>100</strong></p>
                    </div>
                </div>

                <div class="tutorial-step">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <h4>Konsekuensi Battle</h4>
                        <p>• <strong>Menang:</strong> Dapat Gold & EXP (HP tetap)<br>
                            • <strong>Kalah:</strong> HP berkurang <strong>-20</strong><br>
                            • <strong>Seri:</strong> Tidak ada perubahan HP</p>
                    </div>
                </div>

                <div class="tutorial-step">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <h4>Death Mechanic</h4>
                        <p>Jika HP mencapai <strong>0</strong>, pet akan <strong>MATI (status: DEAD)</strong><br>
                            • Pet mati tidak bisa battle<br>
                            • Tidak bisa jadi pet aktif<br>
                            • Tidak bisa dipakai item (kecuali revive)</p>
                    </div>
                </div>

                <div class="help-tip danger">
                    <i class="fas fa-skull"></i>
                    <strong>PENTING:</strong> Kalah 5x berturut-turut = MATI! Pastiin pet kamu cukup kuat sebelum
                    battle.
                </div>

                <div class="help-tip info">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Tips:</strong> Beli "Divine Shield" di Shop untuk melindungi pet dari 1x serangan!
                </div>
            </div>

            <!-- Evolution Tab -->
            <div class="help-content" id="help-evolution" style="display: none;">
                <h3>🌟 Manual Evolution (Sacrifice System)</h3>

                <div class="tutorial-step">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <h4>Syarat Evolution</h4>
                        <p>• Pet utama harus <strong>Level 20+</strong><br>
                            • Punya minimal <strong>3 pet lain</strong> dengan rarity yang SAMA<br>
                            • Gold minimal <strong>500</strong></p>
                    </div>
                </div>

                <div class="tutorial-step">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <h4>Cara Evolve</h4>
                        <p>1. Buka tab <strong>Collection</strong><br>
                            2. Cari pet Level 20+, klik tombol <strong>⭐ (Evolve)</strong><br>
                            3. <strong>PILIH 3 pet</strong> yang mau dikorbankan (centang checkbox)<br>
                            4. Pastikan rarity sama semua!<br>
                            5. Klik <strong>"Evolve (500 Gold)"</strong></p>
                    </div>
                </div>

                <div class="tutorial-step">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <h4>Hasil Evolution</h4>
                        <p>• Pet utama naik <strong>1 level</strong><br>
                            • Status berubah jadi <strong>Adult</strong><br>
                            • 3 pet yang dipilih <strong>DIHAPUS PERMANEN</strong><br>
                            • Gold berkurang <strong>-500</strong></p>
                    </div>
                </div>

                <div class="help-tip danger">
                    <i class="fas fa-fire"></i>
                    <strong>PERHATIAN!</strong> Pet yang dikorbankan akan DIHAPUS PERMANEN! Pilih dengan hati-hati.
                </div>

                <div class="help-tip info">
                    <i class="fas fa-lightbulb"></i>
                    <strong>Tips:</strong> Korbankan pet level rendah untuk evolve pet favorit kamu!
                </div>
            </div>

            <!-- Economy Tab -->
            <div class="help-content" id="help-economy" style="display: none;">
                <h3>💰 Sell Pet & Rename</h3>

                <div class="tutorial-section">
                    <h4><i class="fas fa-coins"></i> Sell Pet</h4>

                    <div class="tutorial-step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <p>Di tab <strong>Collection</strong>, klik tombol <strong>💰 (Sell)</strong> pada pet yang
                                tidak aktif</p>
                        </div>
                    </div>

                    <div class="tutorial-step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h4>Harga Jual</h4>
                            <p>• <strong>Common:</strong> 50 + (Level × 10)<br>
                                • <strong>Rare:</strong> 100 + (Level × 10)<br>
                                • <strong>Epic:</strong> 200 + (Level × 10)<br>
                                • <strong>Legendary:</strong> 500 + (Level × 10)</p>
                            <p class="example">Contoh: Rare Lv.15 = 100 + 150 = <strong>250 Gold</strong></p>
                        </div>
                    </div>
                </div>

                <div class="tutorial-section">
                    <h4><i class="fas fa-edit"></i> Rename Pet</h4>

                    <div class="tutorial-step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <p>Di tab <strong>My Pet</strong>, klik icon <strong>✏️ (Edit)</strong> di samping nama pet
                            </p>
                        </div>
                    </div>

                    <div class="tutorial-step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <p>Masukkan nama baru (max 50 karakter), klik <strong>Save</strong></p>
                        </div>
                    </div>
                </div>

                <div class="help-tip warning">
                    <i class="fas fa-ban"></i>
                    <strong>Tidak bisa:</strong> Jual pet yang sedang aktif! Set pet lain sebagai aktif dulu.
                </div>
            </div>

            <div class="help-footer">
                <p>💡 <strong>Butuh bantuan lebih?</strong> Hubungi admin atau lihat dokumentasi lengkap.</p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/pet.js"></script>
    <script src="js/pet_hardcore_update.js"></script>

</body>

</html>