/**
 * MOE Pet Animation Controller
 * Handles CSS sprite animations and Lottie integration
 * 
 * Features:
 * 1. CSS Sprite Animation Control (walk, attack, jump, dance)
 * 2. Lottie Animation Player
 * 3. Particle Effect System
 * 4. Animation State Machine
 */

// ================================================
// CONFIGURATION
// ================================================
const ANIMATION_CONFIG = {
    // Lottie CDN (lightweight, using dotlottie-player)
    lottiePlayerCDN: 'https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs',

    // Pre-made Lottie animations (free from LottieFiles)
    lottieAnimations: {
        confetti: 'https://lottie.host/ef460b2c-8e60-4e4c-b0f6-1c45cd9d0e38/w8qYxZPZvU.lottie',
        sparkles: 'https://lottie.host/60fd30cf-a37e-4c77-96ea-cb8f95f65e0a/sR7lCnZpBD.lottie',
        hearts: 'https://lottie.host/21a458c9-a0d7-4f2e-a8b8-81c2fa4a8eb5/fGLVPxEOO0.lottie',
        levelUp: 'https://lottie.host/fb3e7c1f-4a5e-4efe-9776-6eaa8d4e7c0e/vcZ3NMKF2R.lottie',
        healing: 'https://lottie.host/da40d58b-4a6a-4c51-9ced-86f2b1da6bf8/5gKKLY7hJz.lottie',
        coins: 'https://lottie.host/c3a67b8b-01ef-4a3c-b39a-0d785ff03f59/KSPxplv1x5.lottie',
        fire: 'https://lottie.host/f58d1a1c-d4ab-4be5-8e0c-a9e94584e3c3/mJRSoQDIw8.lottie',
        water: 'https://lottie.host/9d3e1f6a-7b5c-4d8e-9a2f-1c6b3d7e8f0a/waterSplash.lottie',
        star: 'https://lottie.host/2e6f3a8c-9b7d-4e1f-8c3a-5d9e2f7b4a6c/starBurst.lottie'
    },

    // Animation durations (ms)
    durations: {
        walk: 2000,
        attack: 600,
        jump: 800,
        dance: 500,
        hurt: 500,
        victory: 1500,
        death: 1200,
        revive: 1500,
        idle: 4000
    }
};

// Animation state
let currentPetAnimation = 'idle';
let animationQueue = [];
let isAnimating = false;
let lottiePlayerLoaded = false;

// ================================================
// INITIALIZATION
// ================================================
function initPetAnimations() {
    // Load Lottie player dynamically (lightweight approach)
    loadLottiePlayer();

    // Add click listener to pet for interaction feedback
    setupPetInteraction();

    console.log('🎮 Pet Animation System initialized');
}

// Load Lottie player script dynamically
async function loadLottiePlayer() {
    try {
        // Dynamic import of dotlottie-player
        await import(ANIMATION_CONFIG.lottiePlayerCDN);
        lottiePlayerLoaded = true;
        console.log('✅ Lottie player loaded');
    } catch (error) {
        console.warn('⚠️ Lottie player failed to load, using CSS fallback:', error);
        lottiePlayerLoaded = false;
    }
}

// ================================================
// ANIMATION STATE MACHINE
// ================================================
function playPetAnimation(animationType, options = {}) {
    const petImage = document.querySelector('.pet-image');
    if (!petImage) return;

    const {
        duration = ANIMATION_CONFIG.durations[animationType] || 1000,
        callback = null,
        loop = false
    } = options;

    // Remove all animation classes
    removeAllAnimationClasses(petImage);

    // Set current animation
    currentPetAnimation = animationType;

    // Add animation class
    petImage.classList.add(`pet-anim-${animationType}`);

    // If not looping, remove after duration
    if (!loop) {
        setTimeout(() => {
            petImage.classList.remove(`pet-anim-${animationType}`);

            // Return to idle
            if (currentPetAnimation === animationType) {
                petImage.classList.add('pet-anim-idle');
                currentPetAnimation = 'idle';
            }

            if (callback) callback();
        }, duration);
    }

    return { animationType, duration };
}

function removeAllAnimationClasses(element) {
    const animClasses = [
        'pet-anim-idle', 'pet-anim-walk', 'pet-anim-walk-bounce',
        'pet-anim-attack', 'pet-anim-jump', 'pet-anim-dance',
        'pet-anim-dance-flash', 'pet-anim-hurt', 'pet-anim-victory',
        'pet-anim-death', 'pet-anim-revive', 'tapped'
    ];
    animClasses.forEach(cls => element.classList.remove(cls));
}

// ================================================
// SPECIFIC ANIMATION TRIGGERS
// ================================================

// Walking animation (for collection browse, etc)
function animatePetWalk(duration = 2000) {
    return playPetAnimation('walk-bounce', { duration, loop: false });
}

// Attack animation (for battle)
function animatePetAttack(callback) {
    // Show slash effect
    showAttackSlash();

    return playPetAnimation('attack', {
        duration: 600,
        callback
    });
}

// Show attack slash overlay
function showAttackSlash() {
    const stage = document.getElementById('pet-stage');
    if (!stage) return;

    // Remove existing
    const existing = stage.querySelector('.pet-attack-slash');
    if (existing) existing.remove();

    const slash = document.createElement('div');
    slash.className = 'pet-attack-slash active';
    stage.appendChild(slash);

    setTimeout(() => slash.remove(), 300);
}

// Jump animation (for play, happiness)
function animatePetJump(callback) {
    return playPetAnimation('jump', {
        duration: 800,
        callback
    });
}

// Dance animation (for rhythm game)
function animatePetDance(withColorFlash = false) {
    const petImage = document.querySelector('.pet-image');
    if (!petImage) return;

    removeAllAnimationClasses(petImage);

    if (withColorFlash) {
        petImage.classList.add('pet-anim-dance-flash');
    } else {
        petImage.classList.add('pet-anim-dance');
    }

    currentPetAnimation = 'dance';
}

// Stop dancing and return to idle
function stopPetDance() {
    const petImage = document.querySelector('.pet-image');
    if (!petImage) return;

    petImage.classList.remove('pet-anim-dance', 'pet-anim-dance-flash');
    petImage.classList.add('pet-anim-idle');
    currentPetAnimation = 'idle';
}

// Hurt animation (when hit in battle)
function animatePetHurt(callback) {
    return playPetAnimation('hurt', {
        duration: 500,
        callback
    });
}

// Victory animation (after winning battle)
function animatePetVictory(callback) {
    // Play victory animation
    playPetAnimation('victory', { duration: 1500, callback });

    // Also show sparkle particles
    showSparkleParticles();

    // And Lottie confetti if available
    playLottieEffect('confetti', 2000);
}

// Death animation
function animatePetDeath(callback) {
    return playPetAnimation('death', {
        duration: 1200,
        callback
    });
}

// Revive animation
function animatePetRevive(callback) {
    // Play Lottie healing effect
    playLottieEffect('healing', 1500);

    return playPetAnimation('revive', {
        duration: 1500,
        callback
    });
}

// ================================================
// PARTICLE EFFECTS
// ================================================

// Show sparkle particles around pet
function showSparkleParticles(count = 6) {
    const stage = document.getElementById('pet-stage');
    if (!stage) return;

    // Create container
    let container = stage.querySelector('.pet-sparkles');
    if (!container) {
        container = document.createElement('div');
        container.className = 'pet-sparkles';
        stage.appendChild(container);
    }

    // Clear existing
    container.innerHTML = '';

    // Add sparkles
    for (let i = 0; i < count; i++) {
        const sparkle = document.createElement('div');
        sparkle.className = 'sparkle';
        sparkle.style.left = `${Math.random() * 100}%`;
        sparkle.style.top = `${Math.random() * 100}%`;
        sparkle.style.animationDelay = `${Math.random() * 1.5}s`;
        container.appendChild(sparkle);
    }

    // Remove after animation
    setTimeout(() => container.remove(), 3000);
}

// Show heart particles (for mood increase)
function showHeartParticles(count = 5) {
    const stage = document.getElementById('pet-stage');
    if (!stage) return;

    const hearts = ['❤️', '💕', '💗', '💖', '💝'];

    for (let i = 0; i < count; i++) {
        const heart = document.createElement('div');
        heart.className = 'heart-particle';
        heart.textContent = hearts[Math.floor(Math.random() * hearts.length)];
        heart.style.left = `${30 + Math.random() * 40}%`;
        heart.style.top = `${40 + Math.random() * 20}%`;
        heart.style.animationDelay = `${i * 0.2}s`;
        stage.appendChild(heart);

        setTimeout(() => heart.remove(), 2000 + (i * 200));
    }

    // Also play Lottie hearts if available
    playLottieEffect('hearts', 2000);
}

// Show food particles (for hunger fill)
function showFoodParticles(count = 3) {
    const stage = document.getElementById('pet-stage');
    if (!stage) return;

    const foods = ['🍖', '🍗', '🥩', '🍕', '🍔', '🌮'];

    for (let i = 0; i < count; i++) {
        const food = document.createElement('div');
        food.className = 'food-particle';
        food.textContent = foods[Math.floor(Math.random() * foods.length)];
        food.style.left = `${20 + Math.random() * 60}%`;
        food.style.top = `${30 + Math.random() * 30}%`;
        food.style.animationDelay = `${i * 0.15}s`;
        stage.appendChild(food);

        setTimeout(() => food.remove(), 1000 + (i * 150));
    }
}

// Show EXP gain indicator
function showExpGain(amount) {
    const stage = document.getElementById('pet-stage');
    if (!stage) return;

    const particle = document.createElement('div');
    particle.className = 'exp-particle';
    particle.innerHTML = `+${amount} EXP`;
    particle.style.left = '50%';
    particle.style.top = '30%';
    particle.style.transform = 'translateX(-50%)';
    stage.appendChild(particle);

    setTimeout(() => particle.remove(), 1500);
}

// Show gold gain indicator
function showGoldGain(amount) {
    const stage = document.getElementById('pet-stage');
    if (!stage) return;

    const particle = document.createElement('div');
    particle.className = 'gold-particle';
    particle.innerHTML = `<i class="fas fa-coins"></i> +${amount}`;
    particle.style.left = '50%';
    particle.style.top = '25%';
    particle.style.transform = 'translateX(-50%)';
    stage.appendChild(particle);

    setTimeout(() => particle.remove(), 1500);

    // Also play Lottie coins effect
    playLottieEffect('coins', 1500);
}

// ================================================
// LOTTIE ANIMATION PLAYER
// ================================================

function playLottieEffect(effectName, duration = 2000, container = null) {
    if (!lottiePlayerLoaded) {
        console.log('Lottie not loaded, skipping effect:', effectName);
        return;
    }

    const animationUrl = ANIMATION_CONFIG.lottieAnimations[effectName];
    if (!animationUrl) {
        console.warn('Unknown Lottie effect:', effectName);
        return;
    }

    // Use provided container or create one in pet-stage
    const targetContainer = container || document.getElementById('pet-stage');
    if (!targetContainer) return;

    // Create lottie container
    const lottieDiv = document.createElement('div');
    lottieDiv.className = `lottie-container lottie-${effectName}`;

    // Create player element
    lottieDiv.innerHTML = `
        <dotlottie-player 
            src="${animationUrl}"
            background="transparent"
            speed="1"
            style="width: 100%; height: 100%;"
            autoplay
        ></dotlottie-player>
    `;

    targetContainer.appendChild(lottieDiv);

    // Remove after duration
    setTimeout(() => {
        lottieDiv.style.opacity = '0';
        lottieDiv.style.transition = 'opacity 0.3s ease';
        setTimeout(() => lottieDiv.remove(), 300);
    }, duration);
}

// Play level up effect (special combo)
function playLevelUpEffect() {
    const stage = document.getElementById('pet-stage');
    if (!stage) return;

    // Show sparkles
    showSparkleParticles(10);

    // Play jump animation
    animatePetJump();

    // Play Lottie level up effect
    playLottieEffect('levelUp', 2500);

    // Show EXP indicator
    showExpGain('LEVEL UP!');
}

// ================================================
// PET INTERACTION (TAP FEEDBACK)
// ================================================

function setupPetInteraction() {
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('pet-image')) {
            handlePetTap(e.target);
        }
    });
}

function handlePetTap(petElement) {
    // Only respond if idle
    if (currentPetAnimation !== 'idle') return;

    // Quick tap feedback
    petElement.classList.add('tapped');
    setTimeout(() => petElement.classList.remove('tapped'), 300);

    // Random reaction
    const reactions = ['jump', 'walk-bounce'];
    const randomReaction = reactions[Math.floor(Math.random() * reactions.length)];

    playPetAnimation(randomReaction, { duration: 800 });

    // Small mood particle
    const stage = document.getElementById('pet-stage');
    if (stage) {
        const emoji = document.createElement('div');
        emoji.className = 'heart-particle';
        emoji.textContent = ['😊', '🎵', '✨', '💫'][Math.floor(Math.random() * 4)];
        emoji.style.left = '50%';
        emoji.style.top = '40%';
        stage.appendChild(emoji);
        setTimeout(() => emoji.remove(), 2000);
    }
}

// ================================================
// BATTLE ANIMATION HELPERS
// ================================================

// Animate attack sequence in battle
async function battleAttackAnimation(isAttacker, damage) {
    return new Promise(resolve => {
        if (isAttacker) {
            animatePetAttack(() => {
                showExpGain(`-${damage} HP`);
                resolve();
            });
        } else {
            animatePetHurt(() => resolve());
        }
    });
}

// Animate battle result
function battleResultAnimation(didWin) {
    if (didWin) {
        animatePetVictory();
    } else {
        // Check if pet died
        if (activePet && activePet.health <= 0) {
            animatePetDeath();
        } else {
            animatePetHurt();
        }
    }
}

// ================================================
// ENHANCED GACHA ANIMATION
// ================================================

// Override/enhance gacha result display
function enhanceGachaReveal(resultElement) {
    if (!resultElement) return;

    // Add reveal animation class
    resultElement.classList.add('gacha-result-reveal');

    // Play confetti Lottie
    const modal = document.querySelector('.gacha-result');
    if (modal) {
        playLottieEffect('confetti', 3000, modal);
    }
}

// ================================================
// RHYTHM GAME INTEGRATION
// ================================================

// Start dance mode for rhythm game
function startRhythmDanceMode() {
    animatePetDance(true);
}

// Rhythm hit feedback
function rhythmHitFeedback(score) {
    const stage = document.getElementById('pet-stage');
    if (!stage) return;

    // Flash color based on score
    const petImage = document.querySelector('.pet-image');
    if (petImage) {
        petImage.style.filter = 'brightness(1.5) hue-rotate(30deg)';
        setTimeout(() => {
            petImage.style.filter = '';
        }, 100);
    }

    // Show score particle
    const scoreParticle = document.createElement('div');
    scoreParticle.className = 'exp-particle';
    scoreParticle.textContent = `+${score}`;
    scoreParticle.style.left = `${30 + Math.random() * 40}%`;
    scoreParticle.style.top = '35%';
    stage.appendChild(scoreParticle);
    setTimeout(() => scoreParticle.remove(), 800);
}

// End rhythm game
function endRhythmDanceMode() {
    stopPetDance();
}

// ================================================
// EXPORT FOR GLOBAL USE
// ================================================

// Make functions globally available
window.PetAnimations = {
    init: initPetAnimations,
    play: playPetAnimation,
    walk: animatePetWalk,
    attack: animatePetAttack,
    jump: animatePetJump,
    dance: animatePetDance,
    stopDance: stopPetDance,
    hurt: animatePetHurt,
    victory: animatePetVictory,
    death: animatePetDeath,
    revive: animatePetRevive,

    // Particles
    sparkles: showSparkleParticles,
    hearts: showHeartParticles,
    food: showFoodParticles,
    showExp: showExpGain,
    showGold: showGoldGain,
    levelUp: playLevelUpEffect,

    // Lottie
    lottie: playLottieEffect,

    // Battle
    battleAttack: battleAttackAnimation,
    battleResult: battleResultAnimation,

    // Gacha
    gachaReveal: enhanceGachaReveal,

    // Rhythm
    rhythmStart: startRhythmDanceMode,
    rhythmHit: rhythmHitFeedback,
    rhythmEnd: endRhythmDanceMode
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Delay init slightly to ensure pet.js loads first
    setTimeout(initPetAnimations, 100);
});

console.log('🎬 Pet Animations module loaded');
