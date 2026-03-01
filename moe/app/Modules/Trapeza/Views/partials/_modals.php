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
                <?= csrf_field() ?>

                <!-- Recipient Search -->
                <div class="form-group">
                    <label for="recipientInput">Recipient</label>
                    <div class="search-container">
                        <input type="text" id="recipientInput" placeholder="Search student...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <div id="searchResults" class="search-results"></div>
                </div>

                <!-- Amount -->
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <div class="amount-container">
                        <i class="fas fa-coins"></i>
                        <input type="number" id="amount" placeholder="Min: 10, Max: 10,000" min="10" max="10000"
                            required>
                    </div>
                    <small class="form-hint">Max 10,000 per transfer | Max 30 transfers/day</small>
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