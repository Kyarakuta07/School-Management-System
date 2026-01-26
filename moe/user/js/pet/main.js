/**
 * Main Entry Point
 * MOE Pet System
 * 
 * Initializes the pet system and exposes functions to window for HTML onclick handlers
 */

// Import all modules
import { initTabs, switchTab, checkUrlErrors, checkDailyReward, initGoldToggle, showToast, updateGoldDisplay, claimDailyReward, closeDailyModal } from './ui.js';
import { loadPets, loadActivePet, selectPet, playWithPet, toggleShelter, initActionButtons, renderActivePet, getPetImagePath } from './pets.js';
import { renderCollection } from './collection.js';
import { loadInventory, renderInventory, handleInventoryClick, adjustQty, setMaxQty, closeBulkModal, confirmBulkUse, useItem, openItemModal, closeItemModal, openReviveModal, closeReviveModal, revivePet } from './inventory.js';
import { performGacha, showGachaResult, closeGachaModal } from './gacha.js';
import { initShopTabs, loadShop, renderShopItems, buyItem, closeShopPurchaseModal, adjustShopQty, updateShopTotal, confirmShopPurchase } from './shop.js';
import { initArenaTabs, loadOpponents, startBattle, showBattleResult, closeBattleModal, loadBattleHistory } from './arena.js';
import { loadAchievements, claimAchievement, initAchievementsTabs } from './achievements.js';
import { state } from './state.js';

console.log('🐾 MOE Pet System - ES6 Modules Loaded');

// ================================================
// INITIALIZATION
// ================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Initializing Pet System...');

    initTabs();
    initActionButtons();
    initShopTabs();
    initArenaTabs();
    initAchievementsTabs();
    initGoldToggle();
    loadPets();
    checkDailyReward();
    checkUrlErrors();

    console.log('✅ Pet System Ready!');
});

// ================================================
// TAB CHANGE HANDLER
// ================================================

document.addEventListener('tabChanged', (e) => {
    const tab = e.detail.tab;

    switch (tab) {
        case 'my-pet':
            if (state.activePet) {
                renderActivePet();
            } else {
                loadActivePet();
            }
            break;
        case 'collection':
            loadPets();
            break;
        case 'gacha':
            // Gacha tab - no data loading needed
            break;
        case 'shop':
            loadShop();
            loadInventory();
            break;
        case 'arena':
            loadActivePet();
            loadOpponents();
            // FIX: Auto-load arena stats on tab open (prevents zero state after battle)
            if (typeof window.loadArenaStats === 'function') {
                window.loadArenaStats();
            }
            break;
        case 'achievements':
            loadAchievements();
            break;
    }
});

// ================================================
// EXPOSE FUNCTIONS TO WINDOW FOR HTML ONCLICK
// ================================================

// UI Functions
window.showToast = showToast;
window.switchTab = switchTab;
window.updateGoldDisplay = updateGoldDisplay;
window.claimDailyReward = claimDailyReward;
window.closeDailyModal = closeDailyModal;

// Pet Functions
window.selectPet = selectPet;
window.playWithPet = playWithPet;
window.toggleShelter = toggleShelter;
window.renderActivePet = renderActivePet;
window.loadPets = loadPets;
window.loadActivePet = loadActivePet;
window.getPetImagePath = getPetImagePath;

// Collection Functions
window.renderCollection = renderCollection;

// Inventory Functions
window.handleInventoryClick = handleInventoryClick;
window.adjustQty = adjustQty;
window.setMaxQty = setMaxQty;
window.closeBulkModal = closeBulkModal;
window.confirmBulkUse = confirmBulkUse;
window.useItem = useItem;
window.openItemModal = openItemModal;
window.closeItemModal = closeItemModal;
window.openReviveModal = openReviveModal;
window.closeReviveModal = closeReviveModal;
window.revivePet = revivePet;
window.loadInventory = loadInventory;

// Gacha Functions
window.performGacha = performGacha;
window.showGachaResult = showGachaResult;
window.closeGachaModal = closeGachaModal;

// Shop Functions
window.buyItem = buyItem;
window.closeShopPurchaseModal = closeShopPurchaseModal;
window.adjustShopQty = adjustShopQty;
window.updateShopTotal = updateShopTotal;
window.confirmShopPurchase = confirmShopPurchase;
window.loadShop = loadShop;

// Arena Functions
window.startBattle = startBattle;
window.showBattleResult = showBattleResult;
window.closeBattleModal = closeBattleModal;
window.loadOpponents = loadOpponents;
window.loadBattleHistory = loadBattleHistory;

// Achievements Functions
window.loadAchievements = loadAchievements;
window.claimAchievement = claimAchievement;
