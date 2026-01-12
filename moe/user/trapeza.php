<?php
require_once '../core/security_config.php';
session_start();

// Check if user is logged in - Allow both Nethera and Vasiki (admin)
if (!isset($_SESSION['status_login']) || ($_SESSION['role'] != 'Nethera' && $_SESSION['role'] != 'Vasiki')) {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'User';
$id_nethera = $_SESSION['id_nethera'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trapeza - MOE Banking</title>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&family=Outfit:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/trapeza.css">
</head>

<body>

    <!-- Header -->
    <header class="trapeza-header">
        <button class="back-btn" onclick="window.location.href='beranda.php'">
            <i class="fas fa-arrow-left"></i>
        </button>
        <h1 class="header-title">TRAPEZA</h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span id="username-display"><?php echo htmlspecialchars($username); ?></span>
        </div>
    </header>

    <!-- Main Container -->
    <main class="trapeza-container">

        <!-- Balance Card -->
        <section class="balance-card">
            <div class="balance-header">
                <span class="balance-label">Your Balance</span>
                <button class="refresh-btn" onclick="refreshBalance()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="balance-amount">
                <i class="fas fa-coins gold-icon"></i>
                <span id="gold-balance">0</span>
            </div>
            <div class="balance-footer">
                <span class="username-small">@<?php echo htmlspecialchars($username); ?></span>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="quick-actions">
            <button class="action-btn transfer-btn" onclick="openTransferModal()">
                <i class="fas fa-paper-plane"></i>
                <span>Transfer</span>
            </button>
            <button class="action-btn history-btn" onclick="showHistory()">
                <i class="fas fa-history"></i>
                <span>History</span>
            </button>
        </section>

        <!-- Recent Transactions Preview -->
        <section class="recent-section">
            <div class="section-header">
                <h2>Recent Transactions</h2>
                <button class="view-all-btn" onclick="showHistory()">View All</button>
            </div>
            <div id="recent-transactions" class="transactions-list">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading transactions...</p>
                </div>
            </div>
        </section>

    </main>

    <!-- Transfer Modal -->
    <div id="transferModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Transfer Gold</h2>
                <button class="close-btn" onclick="closeTransferModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="transferForm">

                    <!-- Recipient Search -->
                    <div class="form-group">
                        <label for="recipient">Recipient</label>
                        <div class="search-container">
                            <input type="text" id="recipient" placeholder="Search by username..." autocomplete="off"
                                required>
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <div id="searchResults" class="search-results"></div>
                    </div>

                    <!-- Amount -->
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <div class="amount-container">
                            <i class="fas fa-coins"></i>
                            <input type="number" id="amount" placeholder="Min: 10, Max: 1000" min="10" max="1000"
                                required>
                        </div>
                        <small class="form-hint">Daily limit: 3000 gold | Max 5 transfers/day</small>
                    </div>

                    <!-- Description (Optional) -->
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <input type="text" id="description" placeholder="e.g., Payment for item" maxlength="255">
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Transfer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content confirm-modal">
            <div class="modal-header">
                <h2>Confirm Transfer</h2>
            </div>
            <div class="modal-body">
                <div class="confirm-details">
                    <div class="confirm-row">
                        <span class="label">To:</span>
                        <span class="value" id="confirm-recipient">-</span>
                    </div>
                    <div class="confirm-row">
                        <span class="label">Amount:</span>
                        <span class="value gold" id="confirm-amount">-</span>
                    </div>
                    <div class="confirm-row">
                        <span class="label">Description:</span>
                        <span class="value" id="confirm-description">-</span>
                    </div>
                </div>
                <div class="confirm-actions">
                    <button class="cancel-btn" onclick="closeConfirmModal()">Cancel</button>
                    <button class="confirm-btn" onclick="executeTransfer()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content history-modal">
            <div class="modal-header">
                <h2>Transaction History</h2>
                <button class="close-btn" onclick="closeHistoryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="allTransactions" class="transactions-list full-list">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading history...</p>
                    </div>
                </div>
                <button id="loadMoreBtn" class="load-more-btn" onclick="loadMoreTransactions()">
                    Load More
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Scripts -->
    <script src="js/trapeza.js"></script>
</body>

</html>