/**
 * MOE Pet System - Background Particle Effects
 * Floating ambient particles using PixiJS
 */

// Configuration
const BG_CONFIG = {
    particleCount: 50,
    particleMinSize: 1,
    particleMaxSize: 4,
    particleColors: [0xDAA520, 0xF4D03F, 0xFFFFFF, 0xB8860B],
    particleSpeed: 0.3,
    particleAlphaMin: 0.2,
    particleAlphaMax: 0.6
};

// Global state
let bgApp = null;
let bgParticles = [];
let bgInitialized = false;

/**
 * Initialize background particles
 */
function initBackgroundParticles() {
    if (typeof PIXI === 'undefined') {
        console.log('PixiJS not available, skipping background particles');
        return false;
    }

    const container = document.getElementById('pixi-bg-container');
    if (!container) {
        console.log('Background container not found');
        return false;
    }

    try {
        // PixiJS v7 uses constructor, not async init
        bgApp = new PIXI.Application({
            width: window.innerWidth,
            height: window.innerHeight,
            backgroundAlpha: 0,
            antialias: true,
            resolution: Math.min(window.devicePixelRatio, 2),
            autoDensity: true
        });

        bgApp.view.style.position = 'fixed';
        bgApp.view.style.top = '0';
        bgApp.view.style.left = '0';
        bgApp.view.style.pointerEvents = 'none';

        container.appendChild(bgApp.view);

        // Create particles
        createBackgroundParticles();

        // Start animation
        bgApp.ticker.add(updateBackgroundParticles);

        // Handle resize
        window.addEventListener('resize', handleBgResize);

        bgInitialized = true;
        console.log('✨ Background particles initialized');

        return true;

    } catch (error) {
        console.error('Background particles init failed:', error);
        return false;
    }
}

/**
 * Create floating particles
 */
function createBackgroundParticles() {
    if (!bgApp) return;

    for (let i = 0; i < BG_CONFIG.particleCount; i++) {
        const particle = createParticle();
        bgApp.stage.addChild(particle.sprite);
        bgParticles.push(particle);
    }
}

/**
 * Create a single particle
 */
function createParticle() {
    const graphics = new PIXI.Graphics();
    const size = BG_CONFIG.particleMinSize + Math.random() * (BG_CONFIG.particleMaxSize - BG_CONFIG.particleMinSize);
    const color = BG_CONFIG.particleColors[Math.floor(Math.random() * BG_CONFIG.particleColors.length)];

    // PixiJS v7 Graphics API
    graphics.beginFill(color, 1);
    graphics.drawCircle(0, 0, size);
    graphics.endFill();

    const texture = bgApp.renderer.generateTexture(graphics);
    const sprite = new PIXI.Sprite(texture);

    sprite.anchor.set(0.5);
    sprite.x = Math.random() * window.innerWidth;
    sprite.y = Math.random() * window.innerHeight;
    sprite.alpha = BG_CONFIG.particleAlphaMin + Math.random() * (BG_CONFIG.particleAlphaMax - BG_CONFIG.particleAlphaMin);

    return {
        sprite,
        vx: (Math.random() - 0.5) * BG_CONFIG.particleSpeed,
        vy: -Math.random() * BG_CONFIG.particleSpeed - 0.1,
        wobblePhase: Math.random() * Math.PI * 2,
        wobbleSpeed: 0.01 + Math.random() * 0.02,
        originalAlpha: sprite.alpha,
        pulsePhase: Math.random() * Math.PI * 2,
        pulseSpeed: 0.02 + Math.random() * 0.03
    };
}

/**
 * Update particles each frame
 */
function updateBackgroundParticles(ticker) {
    if (!bgApp || bgParticles.length === 0) return;

    const delta = ticker.deltaTime;

    for (const particle of bgParticles) {
        // Update position
        particle.sprite.x += particle.vx * delta;
        particle.sprite.y += particle.vy * delta;

        // Wobble side-to-side
        particle.wobblePhase += particle.wobbleSpeed * delta;
        particle.sprite.x += Math.sin(particle.wobblePhase) * 0.3;

        // Pulse alpha
        particle.pulsePhase += particle.pulseSpeed * delta;
        particle.sprite.alpha = particle.originalAlpha + Math.sin(particle.pulsePhase) * 0.1;

        // Wrap around screen
        if (particle.sprite.y < -20) {
            particle.sprite.y = window.innerHeight + 20;
            particle.sprite.x = Math.random() * window.innerWidth;
        }
        if (particle.sprite.x < -20) {
            particle.sprite.x = window.innerWidth + 20;
        }
        if (particle.sprite.x > window.innerWidth + 20) {
            particle.sprite.x = -20;
        }
    }
}

/**
 * Handle window resize
 */
function handleBgResize() {
    if (!bgApp) return;

    bgApp.renderer.resize(window.innerWidth, window.innerHeight);
}

/**
 * Set particle color based on element
 */
function setParticleElement(element) {
    if (!bgInitialized) return;

    const elementColors = {
        'Fire': [0xFF6B35, 0xFF4500, 0xFFD700],
        'Water': [0x4ECDC4, 0x00BCD4, 0x81D4FA],
        'Earth': [0xC4A77D, 0x8D6E63, 0xA1887F],
        'Air': [0xA8DADC, 0x81D4FA, 0xE0F7FA],
        'Dark': [0x6C5CE7, 0x512DA8, 0x9B59B6],
        'Light': [0xFFD93D, 0xFFF176, 0xFFFFFF]
    };

    const colors = elementColors[element] || BG_CONFIG.particleColors;

    // Update existing particles
    for (const particle of bgParticles) {
        const newColor = colors[Math.floor(Math.random() * colors.length)];
        particle.sprite.tint = newColor;
    }
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Small delay to let other components initialize first
    setTimeout(initBackgroundParticles, 300);
});

// Export for external use
window.BgParticles = {
    init: initBackgroundParticles,
    setElement: setParticleElement
};

console.log('🌟 Background Particles module loaded');
