/**
 * Achievements System Module
 * @module pet/achievements
 * @description Handles achievements/badges display and reward claiming
 * 
 * @author MOE Development Team
 * @version 2.0.0
 */

import { API_BASE, ASSETS_BASE } from './config.js';
import { showToast, updateGoldDisplay } from './ui.js';

// ================================================
// ACHIEVEMENTS LOADING
// ================================================

/**
 * Load achievements/badges from API and render them
 * @async
 * @returns {Promise<void>}
 */
export async function loadAchievements() {
    const container = document.getElementById('achievements-grid');
    if (!container) {
        console.error('achievements-grid element not found');
        return;
    }

    // Hide empty state while loading
    const emptyState = document.getElementById('achievements-empty');
    if (emptyState) emptyState.style.display = 'none';

    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading achievements...</p></div>';

    try {
        const response = await fetch(`${API_BASE}?action=get_achievements`);
        const data = await response.json();

        if (data.success && data.achievements && data.achievements.length > 0) {
            // Get progress data from API
            const progressData = data.progress || {};

            container.innerHTML = data.achievements.map(ach => {
                // Map progress based on requirement_type
                const reqType = ach.requirement_type;
                const rawProgress = progressData[reqType] || 0;
                const targetValue = parseInt(ach.requirement_value) || 1;
                // Cap progress display to target value
                const currentProgress = Math.min(rawProgress, targetValue);
                const percentage = Math.min((rawProgress / targetValue) * 100, 100);
                const isComplete = ach.unlocked || rawProgress >= targetValue;

                // Determine status class
                const statusClass = isComplete ? 'unlocked' : 'locked';

                // Get icon - use emoji or font awesome
                const iconDisplay = ach.icon && ach.icon.length <= 4
                    ? `<span class="emoji-icon">${ach.icon}</span>`
                    : `<i class="fas ${ach.icon || 'fa-trophy'}"></i>`;

                return `
                    <div class="achievement-card ${statusClass}">
                        <div class="achievement-icon">
                            ${iconDisplay}
                        </div>
                        <div class="achievement-info">
                            <div class="achievement-name">${ach.name}</div>
                            <div class="achievement-desc">${ach.description}</div>
                            ${!isComplete ? `
                            <div class="achievement-progress">
                                <div class="achievement-progress-fill" style="width: ${percentage}%"></div>
                            </div>
                            <div class="achievement-progress-text">${currentProgress} / ${targetValue}</div>
                            ` : ''}
                            ${isComplete && !ach.claimed ? `<button class="ach-claim-btn" onclick="claimAchievement(${ach.id})"><i class="fas fa-gift"></i> Claim ${ach.reward_gold}g</button>` : ''}
                            ${ach.claimed ? `<div class="achievement-reward claimed"><i class="fas fa-check"></i> Claimed</div>` : (!isComplete && ach.reward_gold ? `<div class="achievement-reward"><i class="fas fa-coins"></i> ${ach.reward_gold}</div>` : '')}
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            // Show empty state
            container.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading achievements:', error);
        container.innerHTML = `
            <div class="achievements-empty" style="grid-column: 1/-1;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Failed to load achievements. Please try again.</p>
            </div>
        `;
    }
}

// ================================================
// ACHIEVEMENT CLAIMING
// ================================================

/**
 * Claim an achievement reward
 * @async
 * @param {number} achievementId - The ID of the achievement to claim
 * @returns {Promise<void>}
 */
export async function claimAchievement(achievementId) {
    try {
        const response = await fetch(`${API_BASE}?action=claim_achievement`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ achievement_id: achievementId })
        });
        const data = await response.json();

        if (data.success) {
            showToast(data.message || `Claimed ${data.gold_earned} gold!`, 'success');
            updateGoldDisplay(data.new_balance);
            // Reload achievements to update UI
            loadAchievements();
        } else {
            showToast(data.error || 'Failed to claim', 'error');
        }
    } catch (error) {
        console.error('Error claiming achievement:', error);
        showToast('Network error', 'error');
    }
}

// ================================================
// TAB INITIALIZATION
// ================================================

/**
 * Initialize achievements tab listeners
 * @returns {void}
 */
export function initAchievementsTabs() {
    // Load achievements when tab is clicked
    const achievementsTab = document.querySelector('[data-tab="achievements"]');
    if (achievementsTab) {
        achievementsTab.addEventListener('click', () => {
            setTimeout(loadAchievements, 100);
        });
    }

    // Also load achievements if already on that tab
    const achievementsContent = document.getElementById('achievements');
    if (achievementsContent && achievementsContent.classList.contains('active')) {
        loadAchievements();
    }
}

// Listen for visibility changes to refresh achievements
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        const achievementsContent = document.getElementById('achievements');
        if (achievementsContent && achievementsContent.classList.contains('active')) {
            loadAchievements();
        }
    }
});
