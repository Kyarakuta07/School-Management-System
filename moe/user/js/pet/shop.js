/**
 * Shop System Module
 * @module pet/shop
 * @description Handles shop display, item purchasing, and shop category tabs
 * 
 * @author MOE Development Team
 * @version 2.0.0
 */

import { API_BASE, ASSETS_BASE } from './config.js';
import { state } from './state.js';
import { showToast, updateGoldDisplay } from './ui.js';
import { loadInventory } from './inventory.js';

// ================================================
// SHOP INITIALIZATION
// ================================================

/**
 * Initialize shop tab navigation
 * @returns {void}
 */
export function initShopTabs() {
    document.querySelectorAll('.shop-tab-pill').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.shop-tab-pill').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderShopItems(tab.dataset.shop);
        });
    });
}

// ================================================
// SHOP LOADING
// ================================================

/**
 * Load shop items from the API
 * @async
 * @returns {Promise<void>}
 */
export async function loadShop() {
    try {
        const response = await fetch(`${API_BASE}?action=get_shop`);
        const data = await response.json();

        if (data.success) {
            state.shopItems = data.items || [];
            console.log('Shop loaded:', state.shopItems.length, 'items');
            updateGoldDisplay(data.user_gold);
            renderShopItems('food');
        } else {
            console.error('Shop load failed:', data.error);
        }
    } catch (error) {
        console.error('Error loading shop:', error);
    }
}

// ================================================
// SHOP RENDERING
// ================================================

/**
 * Get the category for an item based on its effect type
 * @param {string} effectType - The item's effect type
 * @returns {'food'|'potion'|'special'} The item category
 */
export function getItemCategory(effectType) {
    if (effectType === 'food') return 'food';
    if (effectType === 'potion' || effectType === 'revive') return 'potion';
    return 'special';
}

/**
 * Get the Font Awesome icon class for an item
 * @param {string} itemName - The item name
 * @param {string} effectType - The item's effect type
 * @returns {string} Font Awesome icon class (e.g., 'fa-drumstick-bite')
 */
export function getItemIcon(itemName, effectType) {
    const name = itemName.toLowerCase();

    // Food items
    if (name.includes('kibble')) return 'fa-drumstick-bite';
    if (name.includes('feast')) return 'fa-turkey';
    if (name.includes('banquet')) return 'fa-crown';
    if (name.includes('fish')) return 'fa-fish';
    if (name.includes('meat')) return 'fa-bacon';

    // Potions
    if (name.includes('elixir')) return 'fa-flask';
    if (name.includes('vitality')) return 'fa-heart-pulse';
    if (name.includes('phoenix')) return 'fa-fire-flame-curved';
    if (name.includes('tears')) return 'fa-droplet';
    if (name.includes('health')) return 'fa-flask-vial';

    // Special items
    if (name.includes('exp') || name.includes('boost')) return 'fa-arrow-up';
    if (name.includes('gacha') || name.includes('ticket')) return 'fa-ticket';
    if (name.includes('shield')) return 'fa-shield-halved';
    if (name.includes('star')) return 'fa-star';
    if (name.includes('scroll')) return 'fa-scroll';
    if (name.includes('soul')) return 'fa-ghost';
    if (name.includes('ankh')) return 'fa-ankh';
    if (name.includes('wisdom')) return 'fa-book';

    // Category fallbacks
    const category = getItemCategory(effectType);
    if (category === 'food') return 'fa-drumstick-bite';
    if (category === 'potion') return 'fa-flask';
    return 'fa-star';
}

/**
 * Render shop items filtered by category
 * @param {'food'|'potion'|'special'} category - The category to filter by
 * @returns {void}
 */
export function renderShopItems(category) {
    const grid = document.getElementById('shop-grid');

    let filtered = state.shopItems;
    if (category === 'food') {
        filtered = state.shopItems.filter(i => i.effect_type === 'food');
    } else if (category === 'potion') {
        filtered = state.shopItems.filter(i => i.effect_type === 'potion' || i.effect_type === 'revive');
    } else if (category === 'special') {
        filtered = state.shopItems.filter(i => i.effect_type === 'exp_boost' || i.effect_type === 'gacha_ticket' || i.effect_type === 'shield');
    }

    if (filtered.length === 0) {
        grid.innerHTML = '<div class="shop-empty"><i class="fas fa-box-open"></i><br>No items in this category</div>';
        return;
    }

    grid.innerHTML = filtered.map(item => {
        const icon = getItemIcon(item.name, item.effect_type);

        let rarity = 'common';
        if (item.price >= 1000) rarity = 'legendary';
        else if (item.price >= 500) rarity = 'epic';
        else if (item.price >= 200) rarity = 'rare';
        else if (item.price >= 100) rarity = 'uncommon';

        const isNew = item.is_new || false;

        return `
            <div class="shop-item-card ${rarity}" onclick="buyItem(${item.id})">
                ${isNew ? '<span class="new-badge">NEW</span>' : ''}
                <div class="shop-item-icon-wrapper ${rarity}">
                    <i class="fas ${icon}"></i>
                </div>
                <h4 class="shop-item-name">${item.name}</h4>
                <p class="shop-item-desc">${item.description || ''}</p>
                <div class="shop-item-footer">
                    <div class="shop-item-price">
                        <i class="fas fa-coins"></i>
                        <span>${item.price}</span>
                    </div>
                    <button class="shop-buy-btn" onclick="event.stopPropagation(); buyItem(${item.id})">
                        <i class="fas fa-cart-plus"></i>
                        Buy
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// ================================================
// PURCHASE MODAL
// ================================================

/**
 * Open the purchase modal for an item
 * @param {number} itemId - The ID of the item to purchase
 * @returns {void}
 */
export function buyItem(itemId) {
    const searchId = parseInt(itemId);
    const item = state.shopItems.find(i => parseInt(i.id) === searchId);
    if (!item) {
        console.log('shopItems:', state.shopItems, 'searching for:', searchId);
        showToast('Item not found. Please refresh the page.', 'error');
        return;
    }

    state.currentShopItem = item;

    document.getElementById('shop-modal-img').src = ASSETS_BASE + (item.img_path || 'placeholder.png');
    document.getElementById('shop-modal-name').textContent = item.name;
    document.getElementById('shop-modal-desc').textContent = item.description;
    document.getElementById('shop-unit-price').textContent = item.price;
    document.getElementById('shop-qty-input').value = 1;
    updateShopTotal();

    document.getElementById('shop-purchase-modal').classList.add('show');
}

/**
 * Close the shop purchase modal
 * @returns {void}
 */
export function closeShopPurchaseModal() {
    document.getElementById('shop-purchase-modal').classList.remove('show');
    state.currentShopItem = null;
}

/**
 * Adjust the quantity in the shop purchase modal
 * @param {number} amount - Amount to adjust (positive or negative)
 * @returns {void}
 */
export function adjustShopQty(amount) {
    const input = document.getElementById('shop-qty-input');
    let value = parseInt(input.value) || 1;
    value = Math.max(1, Math.min(99, value + amount));
    input.value = value;
    updateShopTotal();
}

/**
 * Update the total price display in the purchase modal
 * @returns {void}
 */
export function updateShopTotal() {
    if (!state.currentShopItem) return;

    const qty = parseInt(document.getElementById('shop-qty-input').value) || 1;
    const total = qty * state.currentShopItem.price;
    document.getElementById('shop-total-price').textContent = total.toLocaleString();
}

/**
 * Confirm and process the shop purchase
 * @async
 * @returns {Promise<void>}
 */
export async function confirmShopPurchase() {
    if (!state.currentShopItem) return;

    const quantity = parseInt(document.getElementById('shop-qty-input').value) || 1;

    try {
        const response = await fetch(`${API_BASE}?action=buy_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: state.currentShopItem.id, quantity: quantity })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message || `Purchased ${quantity}x ${state.currentShopItem.name}!`, 'success');
            updateGoldDisplay(data.remaining_gold);
            loadInventory();
            closeShopPurchaseModal();
        } else {
            showToast(data.error || 'Purchase failed', 'error');
        }
    } catch (error) {
        console.error('Error buying item:', error);
        showToast('Network error', 'error');
    }
}
