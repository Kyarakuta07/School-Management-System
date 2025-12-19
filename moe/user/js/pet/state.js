/**
 * Application State Management
 * MOE Pet System
 */

export const state = {
    currentTab: 'my-pet',
    userPets: [],
    activePet: null,
    shopItems: [],
    userInventory: [],
    selectedItemType: null,
    currentBulkItem: null,
    currentReviveItem: null,
    currentShopItem: null,
    dailyRewardData: null,
    isGoldCompact: true
};

// Expose to window for compatibility with pixi_pet.js and HTML onclick handlers
if (typeof window !== 'undefined') {
    // Create reactive proxies that update window globals
    Object.defineProperty(window, 'userPets', {
        get: () => state.userPets,
        set: (value) => { state.userPets = value; }
    });

    Object.defineProperty(window, 'activePet', {
        get: () => state.activePet,
        set: (value) => { state.activePet = value; }
    });
}
