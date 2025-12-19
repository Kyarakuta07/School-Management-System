/**
 * Configuration & Constants
 * @module pet/config
 * @description Central configuration file for the pet system
 * 
 * @author MOE Development Team
 * @version 2.0.0
 */

/**
 * Base URL for API requests
 * @constant {string}
 */
export const API_BASE = 'api/router.php';

/**
 * Base path for pet asset images
 * @constant {string}
 */
export const ASSETS_BASE = '../assets/pets/';

/**
 * Default setting for compact gold display mode
 * @constant {boolean}
 */
export const GOLD_COMPACT_MODE = true;

/**
 * Maximum number of pets a user can own
 * @constant {number}
 */
export const MAX_PETS = 25;

/**
 * Gacha costs in gold
 * @constant {Object}
 * @property {number} standard - Cost for standard gacha
 * @property {number} premium - Cost for premium gacha (uses ticket instead)
 */
export const GACHA_COSTS = {
    standard: 50,
    premium: 0
};

/**
 * Stat decay rates per hour
 * @constant {Object}
 * @property {number} hunger - Hunger decay per hour
 * @property {number} mood - Mood decay per hour
 * @property {number} health - Health decay per hour (when hunger = 0)
 */
export const DECAY_RATES = {
    hunger: 5,
    mood: 3,
    health: 8
};
