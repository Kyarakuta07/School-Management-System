/**
 * Main Entry Point
 * MOE Pet System
 * 
 * Initializes the pet system and exposes functions to window for HTML onclick handlers
 */

// Import all modules
import { initTabs, switchTab, checkUrlErrors, checkDailyReward, initGoldToggle, showToast, updateGoldDisplay, claimDailyReward, closeDailyModal, initBottomSheet, openBattleSheet, closeBattleSheet } from './ui.js';
import { loadPets, loadActivePet, selectPet, playWithPet, toggleShelter, initActionButtons, renderActivePet, getPetImagePath } from './pets.js';
import { renderCollection } from './collection.js';
import { loadInventory, renderInventory, handleInventoryClick, adjustQty, setMaxQty, closeBulkModal, confirmBulkUse, useItem, openItemModal, closeItemModal, openReviveModal, closeReviveModal, revivePet } from './inventory.js';
import { performGacha, showGachaResult, closeGachaModal } from './gacha.js';
import { initShopTabs, loadShop, renderShopItems, buyItem, closeShopPurchaseModal, adjustShopQty, updateShopTotal, confirmShopPurchase } from './shop.js';
import { initArenaTabs, loadOpponents, startBattle, showBattleResult, closeBattleModal, loadBattleHistory, loadArenaStats, loadTeamSelection, toggle3v3Selection, removePetFromSlot, start3v3Battle, useArenaTicket } from './arena_v2.js';
import { loadAchievements, claimAchievement, initAchievementsTabs } from './achievements.js';
import { initLeaderboard } from './leaderboard.js';
import { state } from './state.js';



// ================================================
// INITIALIZATION
// ================================================

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initial Data Load
    loadPets();
    loadInventory();

    // 2. Component Initialization
    initTabs();
    initActionButtons();
    initShopTabs();
    initArenaTabs();
    initAchievementsTabs();
    initLeaderboard();
    initBottomSheet();
    initGoldToggle();

    // 3. Status Checks
    checkDailyReward();
    checkUrlErrors();
});

// ================================================
// TAB CHANGE HANDLER
// ================================================

document.addEventListener('tabChanged', (e) => {
    const tab = e.detail.tab;

    switch (tab) {
        case 'my-pet':
            loadInventory(); // Refresh inventory context for Feed/Heal buttons
            // Guard: Only load if state is empty to prevent double-render on startup
            if (!state.activePet && (!state.userPets || !state.userPets.length)) {
                loadActivePet();
            } else if (state.activePet) {
                renderActivePet();
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
            loadInventory(); // Ensure tickets are loaded for reset quota button
            setTimeout(() => loadOpponents(), 200);
            setTimeout(() => loadArenaStats(), 400);
            break;
        case 'arena3v3':
            loadTeamSelection();
            break;
        case 'war':
            // sanctuary_war.js is loaded as a classic script, call via window
            if (typeof window.initSanctuaryWar === 'function') {
                window.initSanctuaryWar();
            }
            break;
        case 'history':
            loadBattleHistory();
            break;
        case 'achievements':
            loadAchievements();
            break;
        case 'leaderboard':
            initLeaderboard();
            break;
    }
});

// ================================================
// EXPOSE FUNCTIONS TO WINDOW FOR HTML ONCLICK
// ================================================

// UI Functions
window.showToast = showToast;
window.switchTab = switchTab;
window.switchToTab = switchTab;
window.openBattleSheet = openBattleSheet;
window.closeBattleSheet = closeBattleSheet;
window.updateGoldDisplay = updateGoldDisplay;
window.claimDailyReward = claimDailyReward;
window.closeDailyModal = closeDailyModal;

// Pet Functions
window.selectPet = selectPet;
window.setActivePet = selectPet; // Alias for consistency
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
window.loadArenaStats = loadArenaStats;
window.loadTeamSelection = loadTeamSelection;
window.toggle3v3Selection = toggle3v3Selection;
window.removePetFromSlot = removePetFromSlot;
window.start3v3Battle = start3v3Battle;
window.useArenaTicket = useArenaTicket;

// Achievements Functions
window.loadAchievements = loadAchievements;
window.claimAchievement = claimAchievement;

// Leaderboard Functions
window.initLeaderboard = initLeaderboard;
