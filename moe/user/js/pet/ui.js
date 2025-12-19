/**
 * UI Utilities Module
 * MOE Pet System
 * 
 * Handles UI interactions, toasts, modals, tabs, gold display, and daily rewards
 */

import { API_BASE } from './config.js';
import { state } from './state.js';

// ================================================
// TOAST NOTIFICATIONS
// ================================================

export function showToast(message, type = 'success') {
    let toast = document.getElementById('toast');

    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        toast.innerHTML = '<i class="toast-icon fas"></i><span class="toast-message"></span>';
        document.body.appendChild(toast);
    }

    const icon = toast.querySelector('.toast-icon');
    const msg = toast.querySelector('.toast-message');

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-circle',
        info: 'fa-info-circle'
    };

    if (icon) icon.className = `toast-icon fas ${icons[type] || icons.success}`;
    if (msg) msg.textContent = message;

    toast.className = `toast ${type}`;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// ================================================
// TAB NAVIGATION
// ================================================

export function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            switchTab(tab);
        });
    });
}

export function switchTab(tab) {
    // Update buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    // Update content
    document.querySelectorAll('.tab-content, .tab-panel').forEach(content => {
        content.classList.toggle('active', content.id === tab);
    });

    state.currentTab = tab;

    // Emit custom event for tab-specific loading
    const event = new CustomEvent('tabChanged', { detail: { tab } });
    document.dispatchEvent(event);
}

// ================================================
// GOLD DISPLAY
// ================================================

export function formatGold(amount, compact = true) {
    if (!compact) {
        return amount.toLocaleString();
    }

    if (amount >= 1000000) {
        return (amount / 1000000).toFixed(1) + 'M';
    } else if (amount >= 1000) {
        return (amount / 1000).toFixed(1) + 'K';
    }
    return amount.toLocaleString();
}

export function updateGoldDisplay(gold) {
    const goldEl = document.getElementById('user-gold');
    if (!goldEl) return;

    if (gold !== undefined) {
        goldEl.textContent = formatGold(gold, state.isGoldCompact);
        goldEl.dataset.fullAmount = gold;
    } else {
        fetch(`${API_BASE}?action=get_shop`)
            .then(r => r.json())
            .then(d => {
                if (d.user_gold !== undefined) {
                    goldEl.textContent = formatGold(d.user_gold, state.isGoldCompact);
                    goldEl.dataset.fullAmount = d.user_gold;
                }
            });
    }
}

export function initGoldToggle() {
    const goldEl = document.getElementById('user-gold');
    if (goldEl) {
        goldEl.style.cursor = 'pointer';
        goldEl.title = 'Click to toggle format';

        goldEl.addEventListener('click', (e) => {
            e.stopPropagation();
            state.isGoldCompact = !state.isGoldCompact;

            const amount = parseInt(goldEl.dataset.fullAmount || 0);
            goldEl.textContent = formatGold(amount, state.isGoldCompact);

            goldEl.style.transform = 'scale(1.1)';
            setTimeout(() => {
                goldEl.style.transform = 'scale(1)';
            }, 150);
        });
    }
}

// ================================================
// URL ERROR CHECKING
// ================================================

export function checkUrlErrors() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');

    if (error === 'battle_limit') {
        showToast('You have used all 3 daily battles! Come back tomorrow.', 'warning');
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (error === 'missing_pets') {
        showToast('Invalid battle parameters', 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// ================================================
// DAILY LOGIN REWARD SYSTEM
// ================================================

export async function checkDailyReward() {
    try {
        const response = await fetch(`${API_BASE}?action=get_daily_reward`);
        const data = await response.json();

        if (data.success && data.can_claim) {
            state.dailyRewardData = data;
            showDailyRewardModal(data);
        }
    } catch (error) {
        console.error('Error checking daily reward:', error);
    }
}

export function showDailyRewardModal(data) {
    const modal = document.getElementById('daily-login-modal');
    const calendar = document.getElementById('daily-calendar');
    const currentDayEl = document.getElementById('daily-current-day');
    const rewardText = document.getElementById('daily-reward-text');
    const totalLogins = document.getElementById('daily-total-logins');

    if (!modal || !calendar) return;

    currentDayEl.textContent = data.current_day;
    totalLogins.textContent = data.total_logins;

    let rewardStr = '';
    if (data.reward_gold > 0) {
        rewardStr += `${data.reward_gold} Gold`;
    }
    if (data.reward_item_name) {
        rewardStr += (rewardStr ? ' + ' : '') + `1 ${data.reward_item_name}`;
    }
    rewardText.textContent = rewardStr;

    let calendarHTML = '';
    const specialDays = [7, 14, 21, 28, 30];

    for (let day = 1; day <= 30; day++) {
        let classes = 'calendar-day';
        let icon = '💰';

        if (day < data.current_day) {
            classes += ' claimed';
            icon = '✓';
        } else if (day === data.current_day) {
            classes += ' current';
            icon = '🎁';
        }

        if (specialDays.includes(day)) {
            classes += ' special';
            if (day !== data.current_day && day > data.current_day) {
                icon = '⭐';
            }
        }

        calendarHTML += `
            <div class="${classes}">
                <span class="day-num">${day}</span>
                <span class="day-icon">${icon}</span>
            </div>
        `;
    }
    calendar.innerHTML = calendarHTML;

    modal.classList.add('show');
}

export async function claimDailyReward() {
    const btn = document.getElementById('claim-reward-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Claiming...';

    try {
        const response = await fetch(`${API_BASE}?action=claim_daily_reward`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        if (data.success) {
            let message = `Day ${data.claimed_day} reward claimed!`;
            if (data.gold_received > 0) {
                message += ` +${data.gold_received} Gold`;
            }
            if (data.item_received) {
                message += ` +1 ${data.item_received}`;
            }

            showToast(message, 'success');
            closeDailyModal();
            updateGoldDisplay();
        } else {
            showToast(data.error || 'Failed to claim reward', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-gift"></i> Claim Reward!';
        }
    } catch (error) {
        console.error('Error claiming daily reward:', error);
        showToast('Network error', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-gift"></i> Claim Reward!';
    }
}

export function closeDailyModal() {
    document.getElementById('daily-login-modal').classList.remove('show');
}
