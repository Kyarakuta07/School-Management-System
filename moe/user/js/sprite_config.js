/**
 * Sprite Animation Configuration
 * Defines frame data for animated pet spritesheets
 * 
 * Each pet can have multiple animations:
 * - idle: Default animation shown on My Pet page
 * - attack: Attack animation for Arena battles
 * - summon: Summoning animation (optional)
 */

const SPRITE_ANIMATIONS = {
    // Shadow Wolf/Fox - Dark Element
    shadowfox: {
        element: 'dark',
        // Frame dimensions from Ludo.ai export
        frameWidth: 437,
        frameHeight: 528,
        // Grid layout (6 columns x 6 rows = 36 frames)
        columns: 6,
        rows: 6,
        animations: {
            idle: {
                file: 'idle.png',
                totalFrames: 36,
                // Animation speed (lower = slower)
                speed: 0.15,
                // Loop the animation
                loop: true
            },
            attack: {
                file: 'attack.png',
                totalFrames: 36,
                speed: 0.25,
                loop: false  // Attack plays once
            },
            summon: {
                file: 'summon.png',
                totalFrames: 36,
                speed: 0.2,
                loop: false  // Summon plays once then transitions to idle
            }
        }
    }

    // Add more pets here as you create their spritesheets:
    // anubis: { ... },
    // phoenix: { ... },
};

/**
 * Get sprite config for a pet species
 * @param {string} speciesName - Name of the pet species (case-insensitive)
 * @returns {Object|null} Sprite configuration or null if not found
 */
function getSpriteConfig(speciesName) {
    if (!speciesName) return null;

    // Normalize: lowercase, remove spaces, remove special chars
    const key = speciesName.toLowerCase().replace(/[\s\-_]+/g, '');

    console.log('🔎 [SpriteConfig] Looking up:', speciesName, '-> normalized key:', key);

    // Direct match
    if (SPRITE_ANIMATIONS[key]) {
        console.log('✅ [SpriteConfig] Found direct match for:', key);
        return SPRITE_ANIMATIONS[key];
    }

    // Try partial match (e.g., "Shadow Fox" might stored as "shadowfox")
    for (const configKey of Object.keys(SPRITE_ANIMATIONS)) {
        if (key.includes(configKey) || configKey.includes(key)) {
            console.log('✅ [SpriteConfig] Found partial match:', key, '<->', configKey);
            return SPRITE_ANIMATIONS[configKey];
        }
    }

    console.log('❌ [SpriteConfig] No match found for:', key);
    return null;
}

/**
 * Check if a pet has animated sprites available
 * @param {string} speciesName - Name of the pet species
 * @returns {boolean}
 */
function hasAnimatedSprite(speciesName) {
    return getSpriteConfig(speciesName) !== null;
}

// Export for use in other modules
window.SPRITE_ANIMATIONS = SPRITE_ANIMATIONS;
window.getSpriteConfig = getSpriteConfig;
window.hasAnimatedSprite = hasAnimatedSprite;

console.log('🎬 Sprite Animation Config loaded');
