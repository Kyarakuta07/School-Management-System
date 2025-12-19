/**
 * Application State Management
 * @module pet/state
 * @description Centralized state management for the pet system
 * 
 * @author MOE Development Team
 * @version 2.0.0
 */

/**
 * Global application state object
 * @type {Object}
 * @property {string} currentTab - Currently active tab ID
 * @property {Array<Object>} userPets - Array of user's pets
 * @property {Object|null} activePet - Currently active pet object
 * @property {Array<Object>} shopItems - Available shop items
 * @property {Array<Object>} userInventory - User's inventory items
 * @property {string|null} selectedItemType - Selected item type for filtering
 * @property {Object|null} currentBulkItem - Item being used in bulk modal
 * @property {Object|null} currentReviveItem - Item being used for revival
 * @property {Object|null} currentShopItem - Item being purchased
 * @property {Object|null} dailyRewardData - Daily login reward data
 * @property {boolean} isGoldCompact - Whether gold display is in compact format
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
    /**
     * Create reactive proxies that synchronize state with window globals
     * This ensures backward compatibility with legacy code
     */
    Object.defineProperty(window, 'userPets', {
        get: () => state.userPets,
        set: (value) => { state.userPets = value; }
    });

    Object.defineProperty(window, 'activePet', {
        get: () => state.activePet,
        set: (value) => { state.activePet = value; }
    });
}
