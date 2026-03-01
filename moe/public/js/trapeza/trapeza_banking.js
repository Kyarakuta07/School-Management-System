/**
 * TRAPEZA - MOE Banking System Client (CI4)
 * Handles balance, transfers, and transaction history
 */

document.addEventListener('DOMContentLoaded', () => {
    loadBalance();
    loadRecentTransactions();
    setupEventListeners();
});

let currentBalance = 0;
let selectedRecipient = null;
let transferData = {};
let transactionOffset = 0;
const TRANSACTIONS_PER_PAGE = 20;

function setupEventListeners() {
    const transferForm = document.getElementById('transferForm');
    if (transferForm) {
        transferForm.addEventListener('submit', handleTransferSubmit);
    }

    const recipientInput = document.getElementById('recipientInput');
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
        const response = await fetch(API_BASE + 'bank/balance');
        const data = await response.json();
        if (data.success) {
            currentBalance = data.data?.balance ?? data.balance ?? 0;
            const balanceEl = document.getElementById('gold-balance');
            if (balanceEl) balanceEl.textContent = currentBalance.toLocaleString();
        } else {
            showToast(data.error || 'Failed to load balance', 'error');
        }
    } catch (error) {
        console.error('Balance load error:', error);
        showToast('Network error while loading balance', 'error');
    }
}

window.refreshBalance = function () {
    const refreshBtn = document.querySelector('.refresh-btn i');
    if (refreshBtn) refreshBtn.classList.add('fa-spin');
    loadBalance().finally(() => {
        setTimeout(() => { if (refreshBtn) refreshBtn.classList.remove('fa-spin'); }, 500);
    });
};

// ================================================
// TRANSACTIONS
// ================================================

async function loadRecentTransactions() {
    const container = document.getElementById('recent-transactions');
    if (!container) return;
    try {
        const response = await fetch(API_BASE + 'bank/transactions?limit=5');
        const data = await response.json();
        if (data.success) {
            const txns = data.data?.transactions ?? data.transactions ?? [];
            if (txns.length === 0) {
                container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No transactions yet</p>
                </div>`;
            } else {
                container.innerHTML = txns.map(t => renderTransactionItem(t)).join('');
            }
        } else {
            container.innerHTML = `<p class="error-text">${data.error || 'Failed to load'}</p>`;
        }
    } catch (error) {
        console.error('Transactions load error:', error);
        container.innerHTML = '<p class="error-text">Failed to load transactions</p>';
    }
}

async function loadAllTransactions() {
    const container = document.getElementById('allTransactions');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (!container) return;

    try {
        const response = await fetch(API_BASE + `bank/transactions?limit=${TRANSACTIONS_PER_PAGE}&offset=${transactionOffset}`);
        const data = await response.json();
        if (data.success) {
            const txns = data.data?.transactions ?? data.transactions ?? [];
            const totalCount = data.data?.total_count ?? data.total_count ?? 0;
            if (transactionOffset === 0) {
                if (txns.length === 0) {
                    container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>No transactions yet</p>
                    </div>`;
                    if (loadMoreBtn) loadMoreBtn.classList.remove('show');
                } else {
                    container.innerHTML = txns.map(t => renderTransactionItem(t)).join('');
                    if (loadMoreBtn && totalCount > TRANSACTIONS_PER_PAGE) {
                        loadMoreBtn.classList.add('show');
                    }
                }
            } else {
                container.innerHTML += txns.map(t => renderTransactionItem(t)).join('');
                if (loadMoreBtn && transactionOffset + TRANSACTIONS_PER_PAGE >= totalCount) {
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

window.loadMoreTransactions = function () {
    transactionOffset += TRANSACTIONS_PER_PAGE;
    loadAllTransactions();
};

function renderTransactionItem(transaction) {
    const isIncome = transaction.is_income;
    const icon = isIncome ? 'fa-arrow-down' : 'fa-arrow-up';
    const amountClass = isIncome ? 'income' : 'expense';
    const amountSign = isIncome ? '+' : '';

    const date = new Date(transaction.created_at);
    const dateStr = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const timeStr = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

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
                <div class="transaction-type">${escapeHtml(typeLabel)}</div>
                <div class="transaction-description">${escapeHtml(transaction.description || '-')}</div>
            </div>
        </div>
        <div class="transaction-right">
            <div class="transaction-amount ${amountClass}">
                ${amountSign}${Math.abs(transaction.amount).toLocaleString()}
                <i class="fas fa-coins" style="font-size: 0.8rem; margin-left: 3px;"></i>
            </div>
            <div class="transaction-date">${dateStr} ${timeStr}</div>
        </div>
    </div>`;
}

// ================================================
// RECIPIENT SEARCH
// ================================================

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJs(text) {
    return text.replaceAll('\\', '\\\\').replaceAll("'", "\\'").replaceAll('"', '\\"');
}

async function searchRecipients(query) {
    const resultsContainer = document.getElementById('searchResults');
    if (!resultsContainer) return;

    if (query.length < 2) {
        resultsContainer.classList.remove('show');
        return;
    }
    try {
        const response = await fetch(API_BASE + 'bank/search?query=' + encodeURIComponent(query));
        const data = await response.json();
        if (data.success) {
            const results = data.data?.results ?? data.results ?? [];
            if (results.length === 0) {
                resultsContainer.innerHTML = '<div class="search-result-item">No users found</div>';
            } else {
                resultsContainer.innerHTML = results.map(user => `
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

window.selectRecipient = function (username, fullName) {
    selectedRecipient = username;
    const input = document.getElementById('recipientInput');
    if (input) input.value = username;
    const results = document.getElementById('searchResults');
    if (results) results.classList.remove('show');
};

// ================================================
// TRANSFER
// ================================================

function handleTransferSubmit(e) {
    e.preventDefault();
    const recipient = document.getElementById('recipientInput').value.trim();
    const amountEl = document.getElementById('amount');
    const amount = amountEl ? parseInt(amountEl.value) : 0;
    const descEl = document.getElementById('description');
    const description = (descEl ? descEl.value.trim() : '') || 'Gold transfer';

    if (!recipient) { showToast('Please select a recipient', 'error'); return; }
    if (amount < 10) { showToast('Minimum transfer amount is 10 gold', 'error'); return; }
    if (amount > 10000) { showToast('Maximum transfer amount is 10,000 gold', 'error'); return; }
    if (amount > currentBalance) { showToast('Insufficient funds', 'error'); return; }

    transferData = { recipient, amount, description };

    const confirmRecipient = document.getElementById('confirm-recipient');
    if (confirmRecipient) confirmRecipient.textContent = `@${recipient}`;
    const confirmAmount = document.getElementById('confirm-amount');
    if (confirmAmount) confirmAmount.textContent = `${amount.toLocaleString()} gold`;
    const confirmDesc = document.getElementById('confirm-description');
    if (confirmDesc) confirmDesc.textContent = description;

    closeTransferModal();
    openConfirmModal();
}

window.executeTransfer = async function () {
    try {
        const response = await fetch(API_BASE + 'bank/transfer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
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
            currentBalance = data.data?.new_balance ?? data.new_balance ?? currentBalance - transferData.amount;
            const balanceEl = document.getElementById('gold-balance');
            if (balanceEl) balanceEl.textContent = currentBalance.toLocaleString();
            loadRecentTransactions();
            const form = document.getElementById('transferForm');
            if (form) form.reset();
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
};

// ================================================
// MODALS
// ================================================

window.openTransferModal = function () {
    document.getElementById('transferModal')?.classList.add('show');
};

window.closeTransferModal = function () {
    document.getElementById('transferModal')?.classList.remove('show');
    document.getElementById('searchResults')?.classList.remove('show');
};

window.openConfirmModal = function () {
    document.getElementById('confirmModal')?.classList.add('show');
};

window.closeConfirmModal = function () {
    document.getElementById('confirmModal')?.classList.remove('show');
};

window.showHistory = function () {
    transactionOffset = 0;
    document.getElementById('historyModal')?.classList.add('show');
    loadAllTransactions();
};

window.closeHistoryModal = function () {
    document.getElementById('historyModal')?.classList.remove('show');
};

window.closeAllModals = function () {
    closeTransferModal();
    closeConfirmModal();
    closeHistoryModal();
};

// ================================================
// TOAST NOTIFICATIONS
// ================================================

window.showToast = function (message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    setTimeout(() => { toast.classList.remove('show'); }, 3000);
};
