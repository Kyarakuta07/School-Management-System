/**
 * MOE Pet System - PixiJS Enhanced Renderer
 * GPU-Accelerated 2D WebGL Pet Display
 * 
 * Features:
 * - Smooth 60fps animations
 * - Particle effects (sparkles, hearts, stars)
 * - Mouse/touch pet following
 * - Glow and filter effects
 * - Interactive pet reactions
 */

// ================================================
// CONFIGURATION
// ================================================
const PIXI_CONFIG = {
    stageWidth: 360,
    stageHeight: 280,
    backgroundColor: 0x050505,
    antialias: true,
    resolution: window.devicePixelRatio || 1,
    autoDensity: true
};

// Pet behavior config
const PET_BEHAVIOR = {
    followSpeed: 0.05,          // How fast pet follows cursor (0-1)
    floatAmplitude: 15,         // Pixels of float movement
    floatSpeed: 0.02,           // Float animation speed
    idleWiggle: 0.03,           // Random wiggle amount
    maxOffset: 50,              // Max distance pet moves from center
    returnSpeed: 0.02           // Speed returning to center
};

// Particle config
const PARTICLE_CONFIG = {
    sparkle: {
        count: 20,
        lifetime: 60,           // Frames
        speed: 0.5,
        size: { min: 2, max: 6 },
        colors: [0xFFD700, 0xFFF8DC, 0xFFE4B5, 0xFFFFFF]
    },
    heart: {
        count: 8,
        lifetime: 90,
        speed: 1,
        size: 16
    },
    star: {
        count: 12,
        lifetime: 45,
        speed: 2,
        size: { min: 4, max: 10 }
    }
};

// ================================================
// GLOBAL STATE
// ================================================
let pixiApp = null;
let petSprite = null;
let petContainer = null;
let particleContainer = null;
let glowFilter = null;
let isPixiReady = false;
let mousePosition = { x: PIXI_CONFIG.stageWidth / 2, y: PIXI_CONFIG.stageHeight / 2 };
let petTargetPosition = { x: 0, y: 0 };
let floatPhase = 0;
let particles = [];
let currentPetData = null;

// Animated Sprite State
let isAnimatedSprite = false;
let currentAnimation = 'idle';
let animatedTextures = {};  // Cache for loaded animation textures

// ================================================
// INITIALIZATION
// ================================================
function initPixiPet() {
    // Check if PixiJS is loaded
    if (typeof PIXI === 'undefined') {
        console.warn('PixiJS not loaded, falling back to CSS');
        return false;
    }

    // Check if we're on pet page
    const stageContainer = document.getElementById('pet-stage');
    if (!stageContainer) {
        console.log('Pet stage not found, skipping PixiJS init');
        return false;
    }

    try {
        // Create PixiJS Application (v7 constructor API)
        pixiApp = new PIXI.Application({
            width: PIXI_CONFIG.stageWidth,
            height: PIXI_CONFIG.stageHeight,
            backgroundColor: PIXI_CONFIG.backgroundColor,
            antialias: PIXI_CONFIG.antialias,
            resolution: PIXI_CONFIG.resolution,
            autoDensity: PIXI_CONFIG.autoDensity,
            backgroundAlpha: 0
        });

        // Style the canvas (v7 uses 'view' not 'canvas')
        pixiApp.view.style.position = 'absolute';
        pixiApp.view.style.top = '50%';
        pixiApp.view.style.left = '50%';
        pixiApp.view.style.transform = 'translate(-50%, -50%)';
        pixiApp.view.style.borderRadius = '16px';
        pixiApp.view.style.zIndex = '5';

        // Create containers
        petContainer = new PIXI.Container();
        particleContainer = new PIXI.Container();

        pixiApp.stage.addChild(particleContainer);
        pixiApp.stage.addChild(petContainer);

        // Setup mouse tracking
        setupMouseTracking(stageContainer);

        // Start animation loop
        pixiApp.ticker.add(updatePixiPet);

        isPixiReady = true;
        console.log('✅ PixiJS Pet System initialized');

        return true;

    } catch (error) {
        console.error('PixiJS init failed:', error);
        return false;
    }
}

// ================================================
// MOUNT PIXI CANVAS TO PET STAGE
// ================================================
function mountPixiCanvas() {
    if (!pixiApp || !isPixiReady) return false;

    const stageContainer = document.getElementById('pet-stage');
    if (!stageContainer) return false;

    // Check if already mounted
    if (stageContainer.querySelector('canvas')) {
        return true;
    }

    // Clear the stage (remove CSS pet display)
    const existingDisplay = stageContainer.querySelector('.pet-display');
    if (existingDisplay) {
        existingDisplay.style.display = 'none';
    }

    // Append PixiJS canvas (v7 uses 'view')
    stageContainer.appendChild(pixiApp.view);

    return true;
}

// ================================================
// LOAD PET SPRITE INTO PIXI
// ================================================
function loadPixiPet(petData) {
    if (!isPixiReady || !pixiApp) {
        console.log('PixiJS not ready, skipping pet load');
        return;
    }

    currentPetData = petData;

    // Debug info
    console.log('🔍 [PixiPet] Loading pet:', {
        species_name: petData.species_name,
        evolution_stage: petData.evolution_stage,
        nickname: petData.nickname
    });

    // Check if this pet has animated sprites
    const spriteConfig = typeof getSpriteConfig === 'function' ? getSpriteConfig(petData.species_name) : null;

    console.log('🔍 [PixiPet] Sprite config lookup:', {
        functionExists: typeof getSpriteConfig === 'function',
        speciesName: petData.species_name,
        configFound: !!spriteConfig,
        isAdult: petData.evolution_stage === 'adult'
    });

    if (spriteConfig && petData.evolution_stage === 'adult') {
        // Use animated sprite for adult pets with animation data
        console.log('🎬 [PixiPet] Using ANIMATED sprite!');
        loadAnimatedPet(petData, spriteConfig);
        return;
    }

    // Fall back to static sprite
    console.log('📷 [PixiPet] Using STATIC sprite (no config or not adult)');
    loadStaticPet(petData);
}

// Load static (non-animated) pet sprite
function loadStaticPet(petData) {
    const imgPath = getPetImagePathForPixi(petData);

    try {
        // Load texture (PixiJS v7 uses Texture.from)
        const texture = PIXI.Texture.from(imgPath);

        // Remove old sprite
        if (petSprite) {
            if (petSprite.stop) petSprite.stop();  // Stop animation if AnimatedSprite
            petContainer.removeChild(petSprite);
            petSprite.destroy();
        }

        isAnimatedSprite = false;

        // Create new sprite
        petSprite = new PIXI.Sprite(texture);
        petSprite.anchor.set(0.5);
        petSprite.x = PIXI_CONFIG.stageWidth / 2;
        petSprite.y = PIXI_CONFIG.stageHeight / 2;

        // Wait for texture to load then scale
        texture.baseTexture.on('loaded', () => {
            const maxSize = 160;
            const scale = Math.min(maxSize / petSprite.width, maxSize / petSprite.height);
            petSprite.scale.set(scale);
        });

        // If already loaded, scale immediately
        if (texture.baseTexture.valid) {
            const maxSize = 160;
            const scale = Math.min(maxSize / petSprite.width, maxSize / petSprite.height);
            petSprite.scale.set(scale);
        }

        // Apply shiny effect if applicable
        if (petData.is_shiny && petData.shiny_hue) {
            try {
                const hueFilter = new PIXI.filters.ColorMatrixFilter();
                hueFilter.hue(petData.shiny_hue, false);
                petSprite.filters = [hueFilter];
            } catch (e) {
                // ColorMatrixFilter may not be available
            }
        }

        // Add glow effect for rare+ pets
        applyRarityGlow(petData.rarity);

        petContainer.addChild(petSprite);

        // Mount canvas if not already
        mountPixiCanvas();

        // Initial sparkle effect
        if (petData.is_shiny) {
            emitParticles('sparkle', 10);
        }

        console.log('✅ Pet static sprite loaded:', petData.nickname || petData.species_name);

    } catch (error) {
        console.error('Failed to load pet texture:', error);
        // Fallback - show CSS version
        const existingDisplay = document.querySelector('.pet-display');
        if (existingDisplay) {
            existingDisplay.style.display = 'block';
        }
    }
}

// ================================================
// ANIMATED SPRITE LOADER
// ================================================
async function loadAnimatedPet(petData, config) {
    const speciesKey = petData.species_name.toLowerCase().replace(/\s+/g, '');
    const element = petData.element || config.element || 'dark';

    console.log(`🎬 Loading animated sprite for ${speciesKey}...`);

    try {
        // Build path to idle animation spritesheet
        const idlePath = `/moe/assets/pets/${element}/${speciesKey}/idle.png`;

        // Load the spritesheet image
        const baseTexture = await PIXI.Assets.load(idlePath);

        // Extract frames from grid spritesheet
        const frames = extractFramesFromGrid(
            baseTexture,
            config.frameWidth,
            config.frameHeight,
            config.columns,
            config.rows,
            config.animations.idle.totalFrames
        );

        if (frames.length === 0) {
            console.warn('No frames extracted, falling back to static');
            loadStaticPet(petData);
            return;
        }

        // Remove old sprite
        if (petSprite) {
            if (petSprite.stop) petSprite.stop();
            petContainer.removeChild(petSprite);
            petSprite.destroy();
        }

        // Create AnimatedSprite
        petSprite = new PIXI.AnimatedSprite(frames);
        petSprite.anchor.set(0.5);
        petSprite.x = PIXI_CONFIG.stageWidth / 2;
        petSprite.y = PIXI_CONFIG.stageHeight / 2;

        // Scale to fit
        const maxSize = 160;
        const scale = Math.min(maxSize / config.frameWidth, maxSize / config.frameHeight);
        petSprite.scale.set(scale);

        // Animation settings
        petSprite.animationSpeed = config.animations.idle.speed;
        petSprite.loop = config.animations.idle.loop;
        petSprite.play();

        isAnimatedSprite = true;
        currentAnimation = 'idle';

        // Cache the idle textures
        animatedTextures.idle = frames;

        // Apply shiny effect if applicable
        if (petData.is_shiny && petData.shiny_hue) {
            try {
                const hueFilter = new PIXI.filters.ColorMatrixFilter();
                hueFilter.hue(petData.shiny_hue, false);
                petSprite.filters = [hueFilter];
            } catch (e) {
                // ColorMatrixFilter may not be available
            }
        }

        // Add glow effect for rare+ pets
        applyRarityGlow(petData.rarity);

        petContainer.addChild(petSprite);

        // Mount canvas if not already
        mountPixiCanvas();

        // Initial sparkle effect
        if (petData.is_shiny) {
            emitParticles('sparkle', 10);
        }

        console.log(`✅ Animated pet loaded: ${speciesKey} (${frames.length} frames)`);

    } catch (error) {
        console.error('Failed to load animated sprite:', error);
        // Fallback to static image
        loadStaticPet(petData);
    }
}

/**
 * Extract individual frame textures from a grid-based spritesheet
 * @param {PIXI.Texture} baseTexture - The loaded spritesheet texture
 * @param {number} frameWidth - Width of each frame in pixels
 * @param {number} frameHeight - Height of each frame in pixels
 * @param {number} columns - Number of columns in the grid
 * @param {number} rows - Number of rows in the grid
 * @param {number} totalFrames - Total number of frames to extract
 * @returns {PIXI.Texture[]} Array of frame textures
 */
function extractFramesFromGrid(baseTexture, frameWidth, frameHeight, columns, rows, totalFrames) {
    const frames = [];
    let frameCount = 0;

    for (let row = 0; row < rows && frameCount < totalFrames; row++) {
        for (let col = 0; col < columns && frameCount < totalFrames; col++) {
            const rect = new PIXI.Rectangle(
                col * frameWidth,
                row * frameHeight,
                frameWidth,
                frameHeight
            );

            const frameTexture = new PIXI.Texture(baseTexture.baseTexture || baseTexture, rect);
            frames.push(frameTexture);
            frameCount++;
        }
    }

    return frames;
}

/**
 * Switch the current animation (for future use in battles)
 * @param {string} animationName - Name of the animation ('idle', 'attack', 'summon')
 */
async function switchAnimation(animationName) {
    if (!isAnimatedSprite || !petSprite || !currentPetData) return;

    const config = getSpriteConfig(currentPetData.species_name);
    if (!config || !config.animations[animationName]) return;

    // Check if already cached
    if (animatedTextures[animationName]) {
        playAnimation(animationName, config.animations[animationName]);
        return;
    }

    // Load the animation spritesheet
    const speciesKey = currentPetData.species_name.toLowerCase().replace(/\s+/g, '');
    const element = currentPetData.element || config.element;
    const animPath = `/moe/assets/pets/${element}/${speciesKey}/${config.animations[animationName].file}`;

    try {
        const baseTexture = await PIXI.Assets.load(animPath);
        const frames = extractFramesFromGrid(
            baseTexture,
            config.frameWidth,
            config.frameHeight,
            config.columns,
            config.rows,
            config.animations[animationName].totalFrames
        );

        animatedTextures[animationName] = frames;
        playAnimation(animationName, config.animations[animationName]);

    } catch (error) {
        console.error(`Failed to load ${animationName} animation:`, error);
    }
}

/**
 * Play a cached animation
 */
function playAnimation(animationName, animConfig) {
    if (!petSprite || !animatedTextures[animationName]) return;

    petSprite.textures = animatedTextures[animationName];
    petSprite.animationSpeed = animConfig.speed;
    petSprite.loop = animConfig.loop;
    petSprite.gotoAndPlay(0);

    currentAnimation = animationName;

    // If not looping, return to idle when done
    if (!animConfig.loop) {
        petSprite.onComplete = () => {
            if (animatedTextures.idle) {
                playAnimation('idle', { speed: 0.15, loop: true });
            }
        };
    } else {
        petSprite.onComplete = null;
    }
}

// Get pet image path (mirror of getPetImagePath in pet.js)
function getPetImagePathForPixi(pet) {
    const stage = pet.evolution_stage || 'egg';
    // Use absolute path for PixiJS asset loading (relative paths don't work correctly)
    const ASSETS_BASE = '/moe/assets/pets/';

    let imgKeys;
    switch (stage) {
        case 'egg': imgKeys = ['img_egg', 'img_baby', 'img_adult']; break;
        case 'baby': imgKeys = ['img_baby', 'img_egg', 'img_adult']; break;
        case 'adult': imgKeys = ['img_adult', 'img_baby', 'img_egg']; break;
        default: imgKeys = ['img_egg', 'img_baby', 'img_adult'];
    }

    for (const key of imgKeys) {
        if (pet[key] && pet[key] !== '' && pet[key] !== null) {
            return ASSETS_BASE + pet[key];
        }
    }

    return ASSETS_BASE + (pet.current_image || 'default/egg.png');
}

// ================================================
// RARITY GLOW EFFECTS
// ================================================
function applyRarityGlow(rarity) {
    if (!petSprite) return;

    // NOTE: Blur filter causes image quality loss
    // Using CSS drop-shadow in HTML instead for rarity glow
    // Just ensure no blur filters are applied
    petSprite.filters = petSprite.filters?.filter(f => !(f instanceof PIXI.BlurFilter)) || [];
}

// ================================================
// MOUSE/TOUCH TRACKING
// ================================================
function setupMouseTracking(container) {
    container.addEventListener('mousemove', (e) => {
        const rect = container.getBoundingClientRect();
        mousePosition.x = e.clientX - rect.left;
        mousePosition.y = e.clientY - rect.top;
    });

    container.addEventListener('touchmove', (e) => {
        if (e.touches.length > 0) {
            const rect = container.getBoundingClientRect();
            mousePosition.x = e.touches[0].clientX - rect.left;
            mousePosition.y = e.touches[0].clientY - rect.top;
        }
    });

    container.addEventListener('click', handlePetClick);
    container.addEventListener('touchstart', handlePetClick);

    // Reset position when mouse leaves
    container.addEventListener('mouseleave', () => {
        mousePosition.x = PIXI_CONFIG.stageWidth / 2;
        mousePosition.y = PIXI_CONFIG.stageHeight / 2;
    });
}

function handlePetClick(e) {
    if (!petSprite) return;

    // Happy reaction
    emitParticles('heart', 5);

    // Jump animation
    petSprite.scale.set(petSprite.scale.x * 1.1);
    setTimeout(() => {
        if (petSprite) petSprite.scale.set(petSprite.scale.x / 1.1);
    }, 150);

    // Play sound if available
    if (typeof SoundManager !== 'undefined' && SoundManager.playHappy) {
        SoundManager.playHappy();
    }
}

// ================================================
// ANIMATION UPDATE LOOP
// ================================================
function updatePixiPet(ticker) {
    if (!petSprite) return;

    const delta = ticker.deltaTime;

    // Update float animation
    floatPhase += PET_BEHAVIOR.floatSpeed * delta;
    const floatY = Math.sin(floatPhase) * PET_BEHAVIOR.floatAmplitude;
    const floatX = Math.cos(floatPhase * 0.7) * (PET_BEHAVIOR.floatAmplitude * 0.3);

    // Calculate target position based on mouse
    const centerX = PIXI_CONFIG.stageWidth / 2;
    const centerY = PIXI_CONFIG.stageHeight / 2;

    const mouseOffsetX = (mousePosition.x - centerX) * 0.15;
    const mouseOffsetY = (mousePosition.y - centerY) * 0.15;

    // Clamp offset
    const clampedOffsetX = Math.max(-PET_BEHAVIOR.maxOffset, Math.min(PET_BEHAVIOR.maxOffset, mouseOffsetX));
    const clampedOffsetY = Math.max(-PET_BEHAVIOR.maxOffset, Math.min(PET_BEHAVIOR.maxOffset, mouseOffsetY));

    // Smooth follow
    petTargetPosition.x += (clampedOffsetX - petTargetPosition.x) * PET_BEHAVIOR.followSpeed * delta;
    petTargetPosition.y += (clampedOffsetY - petTargetPosition.y) * PET_BEHAVIOR.followSpeed * delta;

    // Apply position with float
    petSprite.x = centerX + petTargetPosition.x + floatX;
    petSprite.y = centerY + petTargetPosition.y + floatY;

    // Subtle rotation based on movement
    const targetRotation = petTargetPosition.x * 0.002;
    petSprite.rotation += (targetRotation - petSprite.rotation) * 0.1;

    // Update particles
    updateParticles(delta);

    // Random sparkle for shiny pets
    if (currentPetData?.is_shiny && Math.random() < 0.02) {
        emitParticles('sparkle', 1);
    }
}

// ================================================
// PARTICLE SYSTEM
// ================================================
function emitParticles(type, count) {
    if (!particleContainer) return;

    const config = PARTICLE_CONFIG[type];
    if (!config) return;

    const centerX = PIXI_CONFIG.stageWidth / 2;
    const centerY = PIXI_CONFIG.stageHeight / 2;

    for (let i = 0; i < count; i++) {
        let particle;

        switch (type) {
            case 'sparkle':
                particle = createSparkleParticle(config, centerX, centerY);
                break;
            case 'heart':
                particle = createHeartParticle(config, centerX, centerY);
                break;
            case 'star':
                particle = createStarParticle(config, centerX, centerY);
                break;
        }

        if (particle) {
            particleContainer.addChild(particle.graphics);
            particles.push(particle);
        }
    }
}

function createSparkleParticle(config, centerX, centerY) {
    const graphics = new PIXI.Graphics();
    const size = config.size.min + Math.random() * (config.size.max - config.size.min);
    const color = config.colors[Math.floor(Math.random() * config.colors.length)];

    // PixiJS v7 Graphics API
    graphics.beginFill(color, 1);
    graphics.drawCircle(0, 0, size);
    graphics.endFill();

    // Random position around pet
    graphics.x = centerX + (Math.random() - 0.5) * 100;
    graphics.y = centerY + (Math.random() - 0.5) * 100;

    return {
        graphics,
        vx: (Math.random() - 0.5) * config.speed * 2,
        vy: -Math.random() * config.speed - 0.5,
        life: config.lifetime,
        maxLife: config.lifetime,
        type: 'sparkle'
    };
}

function createHeartParticle(config, centerX, centerY) {
    const graphics = new PIXI.Graphics();
    const size = config.size;

    // Draw heart shape (PixiJS v7)
    graphics.beginFill(0xff6b9d, 1);
    graphics.moveTo(0, -size * 0.3);
    graphics.bezierCurveTo(-size * 0.5, -size * 0.8, -size, -size * 0.3, 0, size * 0.5);
    graphics.bezierCurveTo(size, -size * 0.3, size * 0.5, -size * 0.8, 0, -size * 0.3);
    graphics.endFill();

    graphics.x = centerX + (Math.random() - 0.5) * 60;
    graphics.y = centerY;

    return {
        graphics,
        vx: (Math.random() - 0.5) * 0.5,
        vy: -config.speed - Math.random(),
        life: config.lifetime,
        maxLife: config.lifetime,
        type: 'heart'
    };
}

function createStarParticle(config, centerX, centerY) {
    const graphics = new PIXI.Graphics();
    const size = config.size.min + Math.random() * (config.size.max - config.size.min);

    // Draw star
    drawStar(graphics, 0, 0, 5, size, size * 0.5, 0xffd700);

    graphics.x = centerX + (Math.random() - 0.5) * 80;
    graphics.y = centerY + (Math.random() - 0.5) * 80;

    return {
        graphics,
        vx: (Math.random() - 0.5) * config.speed,
        vy: (Math.random() - 0.5) * config.speed,
        rotationSpeed: (Math.random() - 0.5) * 0.1,
        life: config.lifetime,
        maxLife: config.lifetime,
        type: 'star'
    };
}

function drawStar(graphics, x, y, points, outerRadius, innerRadius, color) {
    const step = Math.PI / points;

    // PixiJS v7: beginFill before drawing
    graphics.beginFill(color, 1);
    graphics.moveTo(x, y - outerRadius);

    for (let i = 0; i < points * 2; i++) {
        const radius = i % 2 === 0 ? outerRadius : innerRadius;
        const angle = i * step - Math.PI / 2;
        graphics.lineTo(
            x + Math.cos(angle) * radius,
            y + Math.sin(angle) * radius
        );
    }

    graphics.closePath();
    graphics.endFill();
}

function updateParticles(delta) {
    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];

        p.graphics.x += p.vx * delta;
        p.graphics.y += p.vy * delta;

        if (p.rotationSpeed) {
            p.graphics.rotation += p.rotationSpeed * delta;
        }

        p.life -= delta;

        // Fade out
        const lifeRatio = p.life / p.maxLife;
        p.graphics.alpha = lifeRatio;

        // Scale down hearts as they rise
        if (p.type === 'heart') {
            p.graphics.scale.set(0.5 + lifeRatio * 0.5);
        }

        // Remove dead particles
        if (p.life <= 0) {
            particleContainer.removeChild(p.graphics);
            p.graphics.destroy();
            particles.splice(i, 1);
        }
    }
}

// ================================================
// PUBLIC API
// ================================================
window.PixiPet = {
    init: initPixiPet,
    load: loadPixiPet,
    mount: mountPixiCanvas,

    // Particle effects
    sparkle: (count = 10) => emitParticles('sparkle', count),
    hearts: (count = 5) => emitParticles('heart', count),
    stars: (count = 8) => emitParticles('star', count),

    // Animation control (for animated sprites)
    switchAnimation: switchAnimation,
    playAttack: () => switchAnimation('attack'),
    playSummon: () => switchAnimation('summon'),
    playIdle: () => switchAnimation('idle'),

    // State
    isReady: () => isPixiReady,
    isAnimated: () => isAnimatedSprite,
    getCurrentAnimation: () => currentAnimation,
    getApp: () => pixiApp
};

// Auto initialize when PixiJS loads
if (typeof PIXI !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        // Delay to ensure pet data is loaded first
        setTimeout(initPixiPet, 500);
    });
}

console.log('🎮 PixiJS Pet module loaded');
