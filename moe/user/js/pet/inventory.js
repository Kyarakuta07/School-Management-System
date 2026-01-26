/**
 * Inventory & Item Usage Module
 * MOE Pet System
 * 
 * Handles inventory display, item usage, and revive system
 */

import { API_BASE, ASSETS_BASE } from './config.js';
import { state } from './state.js';
import { showToast, updateGoldDisplay } from './ui.js';
import { loadPets, loadActivePet, getPetImagePath } from './pets.js';
import { getItemIcon, getItemCategory } from './shop.js'; // Import icon mapping

// ================================================
// INVENTORY LOADING & DISPLAY
// ================================================

export async function loadInventory() {
    try {
        const response = await fetch(`${API_BASE}?action=get_inventory`);
        const data = await response.json();

        if (data.success) {
            state.userInventory = data.inventory;
            renderInventory();
            updateInventoryCount();
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
    }
}

export function renderInventory() {
    const grid = document.getElementById('inventory-grid');

    updateInventoryCount();

    if (state.userInventory.length === 0) {
        grid.innerHTML = '<p class="empty-message">No items yet</p>';
        return;
    }

    grid.innerHTML = state.userInventory.map(item => {
        let rarity = 'common';
        if (item.price) {
            if (item.price >= 1000) rarity = 'legendary';
            else if (item.price >= 500) rarity = 'epic';
            else if (item.price >= 200) rarity = 'rare';
            else if (item.price >= 100) rarity = 'uncommon';
        }

        const isDepleted = item.quantity === 0;

        // Get FA icon (XSS-safe: controlled mapping)
        const icon = getItemIcon(item.name, item.effect_type);

        return `
        <div class="inventory-item-card ${rarity} ${isDepleted ? 'depleted' : ''}" 
             onclick="${!isDepleted ? `handleInventoryClick(
                 ${item.item_id}, 
                 '${item.effect_type}', 
                 '${item.name.replace(/'/g, "\\'")}', 
                 '${item.description ? item.description.replace(/'/g, "\\'") : ''}', 
                 '${item.img_path}', 
                 ${item.quantity}
             )` : 'void(0)'}" 
             title="${item.name}${item.description ? ' - ' + item.description : ''}">
            <div class="inventory-item-icon-wrapper ${rarity}">
                <i class="fas ${icon}"></i>
            </div>
            <span class="inventory-qty-badge">${item.quantity}</span>
            <p class="inventory-item-name">${item.name}</p>
        </div>
    `;
    }).join('');
}

export function updateInventoryCount() {
    const countEl = document.getElementById('inventory-count');
    if (countEl) {
        const count = state.userInventory.length;
        countEl.textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
    }
}

// ================================================
// ITEM CLICK HANDLING
// ================================================

export async function handleInventoryClick(itemId, type, itemName, itemDesc, itemImg, maxQty) {
    // Gacha Ticket handling
    if (type === 'gacha_ticket') {
        if (confirm(`Apakah kamu ingin menetaskan ${itemName} sekarang?`)) {
            useItem(itemId, 0, 1);
        }
        return;
    }

    // Revive items handling
    if (type === 'revive') {
        openReviveModal(itemId, itemName, itemImg);
        return;
    }

    // Check for active pet
    if (!state.activePet) {
        showToast('Kamu butuh Active Pet untuk menggunakan item ini!', 'warning');
        return;
    }
    if (state.activePet.status === 'DEAD') {
        showToast('Pet mati. Hidupkan dulu dengan item Revive!', 'error');
        return;
    }

    // Open bulk use modal
    state.currentBulkItem = {
        id: itemId,
        max: maxQty,
        name: itemName
    };

    const modal = document.getElementById('bulk-use-modal');
    if (!modal) {
        if (confirm(`Gunakan ${itemName}?`)) useItem(itemId, state.activePet.id, 1);
        return;
    }

    // Get item from inventory for effect_type
    const inventoryItem = state.userInventory.find(i => i.item_id === itemId);
    const effect_type = inventoryItem ? inventoryItem.effect_type : 'special';

    // Set FontAwesome icon (XSS-safe: controlled function, no user input)
    const icon = getItemIcon(itemName, effect_type);
    const iconElement = document.getElementById('bulk-item-icon');
    iconElement.className = `fas ${icon}`; // Safe: icon from controlled mapping

    // Set text content (XSS-safe: textContent auto-escapes HTML)
    document.getElementById('bulk-modal-title').textContent = itemName;
    document.getElementById('bulk-item-desc').textContent = itemDesc;
    document.getElementById('bulk-item-qty').max = maxQty;
    document.getElementById('bulk-item-qty').value = 1;

    modal.classList.add('show');
}

// ================================================
// BULK USE MODAL CONTROLS
// ================================================

export function adjustQty(amount) {
    const input = document.getElementById('bulk-item-qty');
    let val = parseInt(input.value) + amount;

    if (val < 1) val = 1;
    if (val > state.currentBulkItem.max) val = state.currentBulkItem.max;

    input.value = val;
}

export function setMaxQty() {
    document.getElementById('bulk-item-qty').value = state.currentBulkItem.max;
}

export function closeBulkModal() {
    document.getElementById('bulk-use-modal').classList.remove('show');
    state.currentBulkItem = null;
}

export function confirmBulkUse() {
    if (!state.currentBulkItem || !state.activePet) return;

    const qty = parseInt(document.getElementById('bulk-item-qty').value);
    useItem(state.currentBulkItem.id, state.activePet.id, qty);
    closeBulkModal();
}

// ================================================
// ITEM USAGE API
// ================================================

export async function useItem(itemId, targetPetId = 0, quantity = 1) {
    try {
        const response = await fetch(`${API_BASE}?action=use_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pet_id: targetPetId,
                item_id: itemId,
                quantity: quantity
            })
        });

        const data = await response.json();

        if (data.success) {
            if (data.is_gacha && data.gacha_data) {
                document.dispatchEvent(new CustomEvent('showGachaResult', { detail: data.gacha_data }));
                showToast('Telur berhasil menetas!', 'success');
            } else {
                if (window.PetAnimations) {
                    if (data.effect_type === 'food' || data.hunger_restored) {
                        window.PetAnimations.food(quantity);
                        window.PetAnimations.jump();
                    } else if (data.effect_type === 'potion' || data.health_restored) {
                        window.PetAnimations.sparkles(6);
                        window.PetAnimations.lottie('healing', 1500);
                    } else if (data.effect_type === 'revive') {
                        window.PetAnimations.revive();
                        window.PetAnimations.lottie('sparkles', 2000);
                    }

                    if (data.exp_gained && data.exp_gained > 0) {
                        window.PetAnimations.showExp(data.exp_gained);
                    }
                }

                showToast(data.message, 'success');
            }

            if (document.getElementById('item-modal')) closeItemModal();

            loadActivePet();
            loadInventory();
            loadPets();
        } else {
            showToast(data.error || 'Failed to use item', 'error');
        }
    } catch (error) {
        console.error('Error using item:', error);
    }
}

// ================================================
// QUICK USE MODAL
// ================================================

export async function openItemModal(type) {
    if (!state.activePet) return;

    state.selectedItemType = type;
    const modal = document.getElementById('item-modal');
    const list = document.getElementById('item-list');

    // Show loading state first
    list.innerHTML = `<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading items...</div>`;
    modal.classList.add('show');

    // Always refresh inventory when opening modal to ensure fresh data
    try {
        const response = await fetch(`${API_BASE}?action=get_inventory`);
        const data = await response.json();
        if (data.success && data.inventory) {
            state.userInventory = data.inventory;
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
    }

    const items = state.userInventory.filter(item => item.effect_type === type);

    if (items.length === 0) {
        list.innerHTML = `<div class="empty-message">No items! Visit shop.</div>`;
    } else {
        list.innerHTML = items.map(item => {
            // Get FA icon (XSS-safe: controlled mapping)
            const icon = getItemIcon(item.name, item.effect_type);
            return `
            <div class="item-option" onclick="useItem(${item.item_id}, ${state.activePet.id}, 1)">
                <div class="item-option-icon-wrapper">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="item-option-name">${item.name}</div>
                <div class="item-option-qty">x${item.quantity}</div>
            </div>
        `}).join('');
    }
}

export function closeItemModal() {
    document.getElementById('item-modal').classList.remove('show');
    state.selectedItemType = null;
}

// ================================================
// REVIVE SYSTEM
// ================================================

export function openReviveModal(itemId, itemName, itemImg) {
    state.currentReviveItem = state.userInventory.find(i => i.item_id === itemId);
    if (!state.currentReviveItem) {
        showToast('Item not found', 'error');
        return;
    }

    const deadPets = state.userPets.filter(pet => pet.status === 'DEAD');

    if (deadPets.length === 0) {
        showToast('No dead pets to revive!', 'info');
        return;
    }

    const deadPetsList = document.getElementById('dead-pets-list');
    deadPetsList.innerHTML = deadPets.map(pet => {
        const imgPath = getPetImagePath(pet);
        const displayName = pet.nickname || pet.species_name;

        return `
            <div class="dead-pet-card" onclick="revivePet(${pet.id})">
                <img src="${imgPath}" alt="${displayName}" 
                     onerror="this.src='../assets/placeholder.png'">
                <div class="dead-overlay"></div>
                <p>${displayName}</p>
                <span class="rarity-badge ${pet.rarity.toLowerCase()}">${pet.rarity}</span>
            </div>
        `;
    }).join('');

    document.getElementById('revive-modal').classList.add('show');
}

export function closeReviveModal() {
    document.getElementById('revive-modal').classList.remove('show');
    state.currentReviveItem = null;
}

export async function revivePet(petId) {
    if (!state.currentReviveItem) return;

    await useItem(state.currentReviveItem.item_id, petId, 1);
    closeReviveModal();
}

// Listen for openItemModal event from pets module
document.addEventListener('openItemModal', (e) => {
    openItemModal(e.detail.type);
});
