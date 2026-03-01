/**
 * UI Utilities Module
 * @module pet/ui
 * @description Handles UI interactions, toasts, modals, tabs, gold display, and daily rewards
 * 
 * @author MOE Development Team
 * @version 2.0.0
 */

import { API_BASE } from './config.js';
import { state } from './state.js';

// ================================================
// TOAST NOTIFICATIONS
// ================================================

/**
 * Display a toast notification message
 * @param {string} message - The message to display
 * @param {'success'|'error'|'warning'|'info'} [type='success'] - Toast type/style
 * @returns {void}
 * @example
 * showToast('Pet fed successfully!', 'success');
 * showToast('Not enough gold', 'error');
 */
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

/**
 * Initialize tab navigation click handlers and read tab from URL
 * @returns {void}
 */
export function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const tab = btn.dataset.tab;

            // Special handling for Battle button (opens bottom sheet)
            if (tab === 'battle') {
                e.preventDefault();
                e.stopPropagation();
                openBattleSheet();
                return;
            }

            switchTab(tab);
        });
    });

    // Read tab from URL parameter and switch to it
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    if (tabFromUrl) {
        // Validate that the tab exists before switching
        const tabExists = document.getElementById(tabFromUrl) ||
            document.querySelector(`[data-tab="${tabFromUrl}"]`);
        if (tabExists) {
            switchTab(tabFromUrl);
        }
    } else {
        // Default to my-pet to ensure styles and state are initialized
        switchTab('my-pet');
    }
}

/**
 * Switch to a specific tab and update UI
 * @param {string} tab - The tab ID to switch to (e.g., 'my-pet', 'collection', 'gacha')
 * @fires tabChanged - Custom event dispatched when tab changes
 * @returns {void}
 */
export function switchTab(tab) {
    // Battle submenu items should highlight the Battle button
    const battleTabs = ['arena', 'arena3v3', 'war', 'leaderboard', 'history'];
    const isBattleSubTab = battleTabs.includes(tab);

    // Update buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isMatch = btn.dataset.tab === tab || (isBattleSubTab && btn.dataset.tab === 'battle');
        btn.classList.toggle('active', isMatch);
    });

    // Update content
    // Only switch content if it's NOT a menu trigger
    if (tab !== 'battle') {
        document.querySelectorAll('.tab-content, .tab-panel').forEach(content => {
            const isMatch = content.id === tab || content.id === `tab-${tab}`;
            content.classList.toggle('active', isMatch);
            content.style.display = isMatch ? 'block' : 'none';
        });
    }

    if (isBattleSubTab) {
        closeBattleSheet();
    }

    // Lazy-load CSS for specific tabs
    const tabStyles = {
        'my-pet': ['pet/my_pet_premium.css'],
        'collection': ['pet/collection_premium.css'],
        'shop': ['pet/shop_premium.css'],
        'gacha': ['pet/gacha_premium.css', 'pet/gacha_result_modal.css'],
        'arena': ['battle/arena_premium.css'],
        'arena3v3': ['battle/arena_premium.css'],
        'achievements': ['pet/achievements_premium.css'],
        'leaderboard': ['pet/leaderboard.css'],
        'history': ['user/history_premium.css'],
        'war': ['battle/sanctuary_war.css'],
    };

    if (tabStyles[tab]) {
        tabStyles[tab].forEach(cssFile => loadStyle(cssFile));
    }

    state.currentTab = tab;

    // Emit custom event for tab-specific loading
    const event = new CustomEvent('tabChanged', { detail: { tab } });
    document.dispatchEvent(event);
}

/**
 * Dynamically load a CSS file if not already present
 * @param {string} filename - The CSS filename in assets/css/
 * @returns {void}
 */
export function loadStyle(filename) {
    const id = `css-${filename.replace(/\./g, '-')}`;
    if (document.getElementById(id)) return;

    const link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    const assetRoot = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../');
    // Using a version timestamp to force-refresh styles (cache busting)
    const version = Date.now();
    link.href = `${assetRoot}css/${filename}?v=${version}`;
    document.head.appendChild(link);
}

// ================================================
// BATTLE BOTTOM SHEET
// ================================================

/**
 * Open the battle mode bottom sheet
 * @returns {void}
 */
export function openBattleSheet() {
    const sheet = document.getElementById('battle-sheet');
    const overlay = document.getElementById('battle-sheet-overlay');
    if (sheet && overlay) {
        sheet.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close the battle mode bottom sheet
 * @returns {void}
 */
export function closeBattleSheet() {
    const sheet = document.getElementById('battle-sheet');
    const overlay = document.getElementById('battle-sheet-overlay');
    if (sheet && overlay) {
        sheet.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }
}

/**
 * Initialize bottom sheet event listeners
 */
export function initBottomSheet() {
    const sheet = document.getElementById('battle-sheet');
    const overlay = document.getElementById('battle-sheet-overlay');
    if (!sheet) return;

    if (overlay) {
        overlay.addEventListener('click', closeBattleSheet);
    }

    // Sheet options
    document.querySelectorAll('.sheet-option').forEach(option => {
        option.addEventListener('click', () => {
            const tab = option.dataset.tab;
            if (tab) {
                switchTab(tab);
            }
        });
    });

    // Handle Battle tab button specifically
    const battleBtn = document.getElementById('battle-tab-btn');
    if (battleBtn) {
        battleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openBattleSheet();
        });
    }

    // Swipe down to close
    let startY = 0;
    sheet.addEventListener('touchstart', (e) => {
        startY = e.touches[0].clientY;
    });

    sheet.addEventListener('touchmove', (e) => {
        const currentY = e.touches[0].clientY;
        const diff = currentY - startY;
        if (diff > 80) { // Increased threshold for better feel
            closeBattleSheet();
        }
    });
}

/**
 * Format gold amount for display
 * @param {number} amount - The gold amount to format
 * @param {boolean} [compact=true] - Whether to use compact notation (1K, 1M)
 * @returns {string} Formatted gold string
 * @example
 * formatGold(1500, true);  // Returns "1.5K"
 * formatGold(1500, false); // Returns "1,500"
 */
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

/**
 * Update the gold display in the UI
 * @param {number} [gold] - Optional gold amount. If not provided, fetches from server
 * @returns {void}
 */
export function updateGoldDisplay(gold) {
    const goldEl = document.getElementById('user-gold');
    if (!goldEl) return;

    if (gold !== undefined) {
        goldEl.textContent = formatGold(gold, state.isGoldCompact);
        goldEl.dataset.fullAmount = gold;
    } else {
        fetchWithCsrf(`${API_BASE}shop`)
            .then(r => r.json())
            .then(d => {
                if (d.user_gold !== undefined) {
                    goldEl.textContent = formatGold(d.user_gold, state.isGoldCompact);
                    goldEl.dataset.fullAmount = d.user_gold;
                }
            });
    }
}

/**
 * Initialize gold display toggle (compact/full format on click)
 * @returns {void}
 */
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

/**
 * Check URL parameters for error messages and display appropriate toasts
 * @returns {void}
 */
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

/**
 * Check if daily reward is available and show modal if claimable
 * @async
 * @returns {Promise<void>}
 */
export async function checkDailyReward() {
    try {
        const response = await fetchWithCsrf(`${API_BASE}rewards/daily`);
        const data = await response.json();

        if (data.success && data.can_claim) {
            loadStyle('shared/daily_login.css');
            state.dailyRewardData = data;
            showDailyRewardModal(data);
        }
    } catch (error) {
        console.error('Error checking daily reward:', error);
    }
}

/**
 * Display the daily login reward modal
 * @param {Object} data - Daily reward data from API
 * @param {number} data.current_day - Current streak day (1-30)
 * @param {number} data.total_logins - Total login count
 * @param {number} data.reward_gold - Gold reward amount
 * @param {string} [data.reward_item_name] - Optional item reward name
 * @returns {void}
 */
export function showDailyRewardModal(data) {
    const modal = document.getElementById('daily-login-modal');
    const calendar = document.getElementById('daily-calendar');
    const currentDayLabel = document.getElementById('daily-current-day-label');
    const rewardText = document.getElementById('daily-reward-text');
    const totalLogins = document.getElementById('daily-total-logins');
    const progressFill = document.getElementById('daily-progress-fill');

    if (!modal || !calendar) return;

    currentDayLabel.textContent = `Day ${data.current_day} of 30`;
    totalLogins.textContent = data.total_logins;

    // Update progress bar
    const progress = (data.current_day / 30) * 100;
    if (progressFill) progressFill.style.width = `${progress}%`;

    let rewardStr = '';
    if (data.reward_gold > 0) {
        rewardStr += `${data.reward_gold} Gold`;
    }
    if (data.reward_item_name) {
        rewardStr += (rewardStr ? ' + ' : '') + `1 ${data.reward_item_name}`;
    }
    rewardText.textContent = rewardStr || 'Special Reward';

    let calendarHTML = '';
    const milestoneDays = [7, 14, 21, 28, 30];

    for (let day = 1; day <= 30; day++) {
        let classes = 'calendar-day';
        let iconClass = 'fas fa-coins';
        let isMilestone = milestoneDays.includes(day);

        // Icon logic
        if (isMilestone) {
            classes += ' special';
            iconClass = (day === 30) ? 'fas fa-crown' : 'fas fa-gem';
        }

        if (day < data.current_day) {
            classes += ' claimed';
            iconClass = 'fas fa-check-circle';
        } else if (day === data.current_day) {
            classes += ' current';
            iconClass = isMilestone ? iconClass : 'fas fa-gift';
        }

        calendarHTML += `
            <div class="${classes}" title="Day ${day} Reward">
                <span class="day-num">${day}</span>
                <span class="day-icon"><i class="${iconClass}"></i></span>
            </div>
        `;
    }
    calendar.innerHTML = calendarHTML;

    modal.classList.add('show');
}

/**
 * Claim the daily login reward
 * @async
 * @returns {Promise<void>}
 */
export async function claimDailyReward() {
    const btn = document.getElementById('claim-reward-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Claiming...';

    try {
        const response = await fetchWithCsrf(`${API_BASE}rewards/claim-daily`, {
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

            // Animation trigger
            const card = document.querySelector('.daily-login-card');
            if (card) {
                card.style.transform = 'scale(0.9) translateY(-10px)';
                card.style.opacity = '0';
                card.style.transition = 'all 0.4s ease';
            }

            setTimeout(() => {
                showToast(message, 'success');
                closeDailyModal();
                updateGoldDisplay();
                // Reset card style for future opens
                if (card) {
                    card.style.transform = '';
                    card.style.opacity = '';
                    card.style.transition = '';
                }
            }, 300);
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

/**
 * Close the daily login reward modal
 * @returns {void}
 */
export function closeDailyModal() {
    document.getElementById('daily-login-modal').classList.remove('show');
}
