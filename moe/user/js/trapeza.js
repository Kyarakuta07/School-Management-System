/**
 * TRAPEZA - MOE Banking System Client
 * Handles balance, transfers, and transaction history
 */

const API_BASE = 'api/router.php';

let currentBalance = 0;
let selectedRecipient = null;
let transferData = {};
let transactionOffset = 0;
const TRANSACTIONS_PER_PAGE = 20;

// ================================================
// INIT & DATA LOADING
// ================================================

document.addEventListener('DOMContentLoaded', () => {
    loadBalance();
    loadRecentTransactions();
    setupEventListeners();
});

function setupEventListeners() {
    // Transfer form
    const transferForm = document.getElementById('transferForm');
    if (transferForm) {
        transferForm.addEventListener('submit', handleTransferSubmit);
    }

    // Recipient search
    const recipientInput = document.getElementById('recipient');
    if (recipientInput) {
        let searchTimeout;
        recipientInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => searchRecipients(e.target.value), 300);
        });
    }

    // Close modals on outside click
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });
}

// ================================================
// BALANCE
// ================================================

async function loadBalance() {
    try {
        const response = await fetch(`${API_BASE}?action=get_balance`);
        const data = await response.json();

        if (data.success) {
            currentBalance = data.balance;
            document.getElementById('gold-balance').textContent = data.balance.toLocaleString();
        } else {
            showToast(data.error || 'Failed to load balance', 'error');
        }
    } catch (error) {
        console.error('Balance load error:', error);
        showToast('Network error while loading balance', 'error');
    }
}

function refreshBalance() {
    const refreshBtn = document.querySelector('.refresh-btn i');
    refreshBtn.classList.add('fa-spin');

    loadBalance().finally(() => {
        setTimeout(() => {
            refreshBtn.classList.remove('fa-spin');
        }, 500);
    });
}

// ================================================
// TRANSACTIONS
// ================================================

async function loadRecentTransactions() {
    const container = document.getElementById('recent-transactions');

    try {
        const response = await fetch(`${API_BASE}?action=get_transactions&limit=5`);
        const data = await response.json();

        if (data.success) {
            if (data.transactions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>No transactions yet</p>
                    </div>
                `;
            } else {
                container.innerHTML = data.transactions.map(t => renderTransactionItem(t)).join('');
            }
        } else {
            container.innerHTML = `<p class="error-text">${data.error}</p>`;
        }
    } catch (error) {
        console.error('Transactions load error:', error);
        container.innerHTML = '<p class="error-text">Failed to load transactions</p>';
    }
}

async function loadAllTransactions() {
    const container = document.getElementById('allTransactions');
    const loadMoreBtn = document.getElementById('loadMoreBtn');

    try {
        const response = await fetch(`${API_BASE}?action=get_transactions&limit=${TRANSACTIONS_PER_PAGE}&offset=${transactionOffset}`);
        const data = await response.json();

        if (data.success) {
            if (transactionOffset === 0) {
                // First load
                if (data.transactions.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>No transactions yet</p>
                        </div>
                    `;
                    loadMoreBtn.classList.remove('show');
                } else {
                    container.innerHTML = data.transactions.map(t => renderTransactionItem(t)).join('');

                    // Show load more if there are more transactions
                    if (data.total_count > TRANSACTIONS_PER_PAGE) {
                        loadMoreBtn.classList.add('show');
                    }
                }
            } else {
                // Append more
                container.innerHTML += data.transactions.map(t => renderTransactionItem(t)).join('');

                // Hide load more if we've loaded all
                if (transactionOffset + TRANSACTIONS_PER_PAGE >= data.total_count) {
                    loadMoreBtn.classList.remove('show');
                }
            }
        }
    } catch (error) {
        console.error('All transactions load error:', error);
        if (transactionOffset === 0) {
            container.innerHTML = '<p class="error-text">Failed to load transactions</p>';
        }
    }
}

function loadMoreTransactions() {
    transactionOffset += TRANSACTIONS_PER_PAGE;
    loadAllTransactions();
}

function renderTransactionItem(transaction) {
    const isIncome = transaction.is_income;
    const icon = isIncome ? 'fa-arrow-down' : 'fa-arrow-up';
    const amountClass = isIncome ? 'income' : 'expense';
    const amountSign = isIncome ? '+' : '';

    // Format date
    const date = new Date(transaction.created_at);
    const dateStr = date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
    const timeStr = date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });

    // Transaction type label
    const typeLabels = {
        'transfer': isIncome ? `From ${transaction.other_party}` : `To ${transaction.other_party}`,
        'purchase': 'Purchase',
        'battle_reward': 'Battle Reward',
        'gacha': 'Gacha Roll',
        'shop': 'Shop Purchase',
        'sell_pet': 'Pet Sale',
        'daily_reward': 'Daily Reward',
        'admin_adjust': 'Admin Adjustment',
        'evolution': 'Pet Evolution'
    };

    const typeLabel = typeLabels[transaction.type] || transaction.type;

    return `
        <div class="transaction-item">
            <div class="transaction-left">
                <div class="transaction-icon ${amountClass}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="transaction-info">
                    <div class="transaction-type">${typeLabel}</div>
                    <div class="transaction-description">${transaction.description || '-'}</div>
                </div>
            </div>
            <div class="transaction-right">
                <div class="transaction-amount ${amountClass}">
                    ${amountSign}${Math.abs(transaction.amount).toLocaleString()}
                    <i class="fas fa-coins" style="font-size: 0.8rem; margin-left: 3px;"></i>
                </div>
                <div class="transaction-date">${dateStr} ${timeStr}</div>
            </div>
        </div>
    `;
}

// ================================================
// RECIPIENT SEARCH
// ================================================

// SECURITY FIX: Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Escape for use in JS string literals (onclick attributes)
function escapeJs(text) {
    return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

let searchTimeout;
async function searchRecipients(query) {
    const resultsContainer = document.getElementById('searchResults');

    if (query.length < 2) {
        resultsContainer.classList.remove('show');
        return;
    }

    try {
        const response = await fetch(`${API_BASE}?action=search_nethera&query=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data.success) {
            if (data.results.length === 0) {
                resultsContainer.innerHTML = '<div class="search-result-item">No users found</div>';
            } else {
                // SECURITY FIX: Escape user data before inserting into HTML
                resultsContainer.innerHTML = data.results.map(user => `
                    <div class="search-result-item" onclick="selectRecipient('${escapeJs(user.username)}', '${escapeJs(user.nama_lengkap)}')">
                        <div class="result-username">@${escapeHtml(user.username)}</div>
                        <div class="result-name">${escapeHtml(user.nama_lengkap)}</div>
                    </div>
                `).join('');
            }
            resultsContainer.classList.add('show');
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

function selectRecipient(username, fullName) {
    selectedRecipient = username;
    document.getElementById('recipient').value = username;
    document.getElementById('searchResults').classList.remove('show');
}

// ================================================
// TRANSFER
// ================================================

function handleTransferSubmit(e) {
    e.preventDefault();

    const recipient = document.getElementById('recipient').value.trim();
    const amount = parseInt(document.getElementById('amount').value);
    const description = document.getElementById('description').value.trim() || 'Gold transfer';

    // Validation
    if (!recipient) {
        showToast('Please select a recipient', 'error');
        return;
    }

    if (amount < 10) {
        showToast('Minimum transfer amount is 10 gold', 'error');
        return;
    }

    if (amount > 1000) {
        showToast('Maximum transfer amount is 1000 gold', 'error');
        return;
    }

    if (amount > currentBalance) {
        showToast('Insufficient funds', 'error');
        return;
    }

    // Store transfer data
    transferData = { recipient, amount, description };

    // Show confirmation
    document.getElementById('confirm-recipient').textContent = `@${recipient}`;
    document.getElementById('confirm-amount').textContent = `${amount.toLocaleString()} gold`;
    document.getElementById('confirm-description').textContent = description;

    closeTransferModal();
    openConfirmModal();
}

async function executeTransfer() {
    try {
        const response = await fetch(`${API_BASE}?action=transfer_gold`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recipient_username: transferData.recipient,
                amount: transferData.amount,
                description: transferData.description
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Transfer successful! ✅', 'success');
            closeConfirmModal();

            // Refresh data
            currentBalance = data.new_balance;
            document.getElementById('gold-balance').textContent = data.new_balance.toLocaleString();
            loadRecentTransactions();

            // Reset form
            document.getElementById('transferForm').reset();
            selectedRecipient = null;
        } else {
            showToast(data.error || 'Transfer failed', 'error');
            closeConfirmModal();
            openTransferModal();
        }
    } catch (error) {
        console.error('Transfer error:', error);
        showToast('Network error during transfer', 'error');
    }
}

// ================================================
// MODALS
// ================================================

function openTransferModal() {
    document.getElementById('transferModal').classList.add('show');
}

function closeTransferModal() {
    document.getElementById('transferModal').classList.remove('show');
    document.getElementById('searchResults').classList.remove('show');
}

function openConfirmModal() {
    document.getElementById('confirmModal').classList.add('show');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('show');
}

function showHistory() {
    transactionOffset = 0;
    document.getElementById('historyModal').classList.add('show');
    loadAllTransactions();
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.remove('show');
}

function closeAllModals() {
    closeTransferModal();
    closeConfirmModal();
    closeHistoryModal();
}

// ================================================
// TOAST NOTIFICATIONS
// ================================================

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
