/**
 * MOE Pet System - PixiJS Battle Effects
 * Premium visual effects for 3v3 battle arena
 * 
 * Features:
 * - Animated nebula background
 * - Element particle auras
 * - Damage spark effects
 * - Screen shake
 * - Victory/defeat effects
 */

// ================================================
// PIXI APPLICATION
// ================================================
let pixiApp = null;
let particles = [];
let backgroundSprites = [];

const COLORS = {
    fire: 0xe74c3c,
    water: 0x3498db,
    earth: 0x8B4513,
    air: 0x95a5a6,
    light: 0xf1c40f,
    dark: 0x2c3e50,
    neutral: 0xDAA520
};

// Initialize PixiJS when DOM is ready
function initPixiEffects() {
    const container = document.getElementById('pixi-container');
    if (!container) return;

    // Create PIXI Application
    pixiApp = new PIXI.Application({
        width: window.innerWidth,
        height: window.innerHeight,
        backgroundAlpha: 0,
        antialias: true,
        resolution: window.devicePixelRatio || 1,
        autoDensity: true
    });

    container.appendChild(pixiApp.view);
    pixiApp.view.style.position = 'absolute';
    pixiApp.view.style.top = '0';
    pixiApp.view.style.left = '0';
    pixiApp.view.style.pointerEvents = 'none';
    pixiApp.view.style.zIndex = '5';

    // Create background nebula
    createNebulaBackground();

    // Create ambient particles
    createAmbientParticles();

    // Start render loop
    pixiApp.ticker.add(updateEffects);

    // Handle resize
    window.addEventListener('resize', onResize);
}

// ================================================
// NEBULA BACKGROUND
// ================================================
function createNebulaBackground() {
    const graphics = new PIXI.Graphics();

    // Create multiple gradient blobs
    const blobs = [
        { x: 0.2, y: 0.3, radius: 300, color: 0x3498db, alpha: 0.15 },
        { x: 0.8, y: 0.2, radius: 350, color: 0xe74c3c, alpha: 0.12 },
        { x: 0.5, y: 0.7, radius: 400, color: 0x9b59b6, alpha: 0.1 },
        { x: 0.1, y: 0.8, radius: 250, color: 0xDAA520, alpha: 0.08 }
    ];

    blobs.forEach((blob, i) => {
        const sprite = new PIXI.Graphics();
        sprite.beginFill(blob.color, blob.alpha);
        sprite.drawCircle(0, 0, blob.radius);
        sprite.endFill();
        sprite.filters = [new PIXI.BlurFilter(80)];
        sprite.x = window.innerWidth * blob.x;
        sprite.y = window.innerHeight * blob.y;
        sprite._baseX = blob.x;
        sprite._baseY = blob.y;
        sprite._speed = 0.0005 + Math.random() * 0.0003;
        sprite._offset = Math.random() * Math.PI * 2;
        pixiApp.stage.addChild(sprite);
        backgroundSprites.push(sprite);
    });
}

// ================================================
// AMBIENT PARTICLES
// ================================================
function createAmbientParticles() {
    for (let i = 0; i < 30; i++) {
        createFloatingParticle();
    }
}

function createFloatingParticle() {
    const particle = new PIXI.Graphics();
    const size = 2 + Math.random() * 4;
    const colors = [0xffffff, 0xDAA520, 0x3498db, 0xe74c3c, 0x9b59b6];
    const color = colors[Math.floor(Math.random() * colors.length)];

    particle.beginFill(color, 0.3 + Math.random() * 0.4);
    particle.drawCircle(0, 0, size);
    particle.endFill();

    particle.x = Math.random() * window.innerWidth;
    particle.y = Math.random() * window.innerHeight;
    particle._vx = (Math.random() - 0.5) * 0.5;
    particle._vy = -0.3 - Math.random() * 0.5;
    particle._life = 1;
    particle._decay = 0.001 + Math.random() * 0.002;
    particle._type = 'ambient';

    pixiApp.stage.addChild(particle);
    particles.push(particle);
}

// ================================================
// ELEMENT AURA
// ================================================
function showElementAura(element, isPlayer = true) {
    const color = COLORS[element.toLowerCase()] || COLORS.neutral;
    const targetEl = isPlayer ?
        document.getElementById('player-sprite') :
        document.getElementById('enemy-sprite');

    if (!targetEl) return;

    const rect = targetEl.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;

    // Create aura ring
    for (let i = 0; i < 20; i++) {
        const particle = new PIXI.Graphics();
        const angle = (i / 20) * Math.PI * 2;
        const radius = 60 + Math.random() * 20;

        particle.beginFill(color, 0.6);
        particle.drawCircle(0, 0, 4 + Math.random() * 4);
        particle.endFill();

        particle.x = centerX + Math.cos(angle) * radius;
        particle.y = centerY + Math.sin(angle) * radius;
        particle._centerX = centerX;
        particle._centerY = centerY;
        particle._angle = angle;
        particle._radius = radius;
        particle._speed = 0.02 + Math.random() * 0.02;
        particle._life = 1;
        particle._decay = 0.02;
        particle._type = 'aura';

        pixiApp.stage.addChild(particle);
        particles.push(particle);
    }
}

// ================================================
// DAMAGE SPARKS
// ================================================
function showDamageSparks(isPlayer = false, isCritical = false) {
    const targetEl = isPlayer ?
        document.getElementById('player-sprite') :
        document.getElementById('enemy-sprite');

    if (!targetEl) return;

    const rect = targetEl.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;

    const sparkCount = isCritical ? 40 : 20;
    const color = isCritical ? 0xf1c40f : 0xe74c3c;

    for (let i = 0; i < sparkCount; i++) {
        const spark = new PIXI.Graphics();
        const size = isCritical ? (4 + Math.random() * 6) : (2 + Math.random() * 4);

        spark.beginFill(color, 0.8);
        spark.drawCircle(0, 0, size);
        spark.endFill();

        const angle = Math.random() * Math.PI * 2;
        const speed = 3 + Math.random() * (isCritical ? 8 : 5);

        spark.x = centerX + (Math.random() - 0.5) * 30;
        spark.y = centerY + (Math.random() - 0.5) * 30;
        spark._vx = Math.cos(angle) * speed;
        spark._vy = Math.sin(angle) * speed;
        spark._life = 1;
        spark._decay = 0.03;
        spark._gravity = 0.1;
        spark._type = 'spark';

        pixiApp.stage.addChild(spark);
        particles.push(spark);
    }

    // Screen shake for critical
    if (isCritical) {
        screenShake(15, 300);
    } else {
        screenShake(5, 150);
    }
}

// ================================================
// SCREEN SHAKE
// ================================================
function screenShake(intensity = 10, duration = 200) {
    const container = document.getElementById('battle-container');
    if (!container) return;

    const startTime = Date.now();
    const originalTransform = container.style.transform || '';

    function shake() {
        const elapsed = Date.now() - startTime;
        if (elapsed > duration) {
            container.style.transform = originalTransform;
            return;
        }

        const progress = elapsed / duration;
        const currentIntensity = intensity * (1 - progress);
        const x = (Math.random() - 0.5) * currentIntensity * 2;
        const y = (Math.random() - 0.5) * currentIntensity * 2;

        container.style.transform = `translate(${x}px, ${y}px)`;
        requestAnimationFrame(shake);
    }

    shake();
}

// ================================================
// VICTORY EFFECTS
// ================================================
function showVictoryEffects() {
    const colors = [0xDAA520, 0xf1c40f, 0xffffff, 0x27ae60];

    // Confetti burst
    for (let i = 0; i < 100; i++) {
        setTimeout(() => {
            const confetti = new PIXI.Graphics();
            const color = colors[Math.floor(Math.random() * colors.length)];
            const width = 8 + Math.random() * 8;
            const height = 4 + Math.random() * 6;

            confetti.beginFill(color, 0.9);
            confetti.drawRect(-width / 2, -height / 2, width, height);
            confetti.endFill();

            confetti.x = Math.random() * window.innerWidth;
            confetti.y = -20;
            confetti._vx = (Math.random() - 0.5) * 3;
            confetti._vy = 2 + Math.random() * 4;
            confetti._rotation = Math.random() * 0.2;
            confetti._life = 1;
            confetti._decay = 0.003;
            confetti._type = 'confetti';

            pixiApp.stage.addChild(confetti);
            particles.push(confetti);
        }, i * 20);
    }

    // Golden burst from center
    setTimeout(() => {
        for (let i = 0; i < 50; i++) {
            const particle = new PIXI.Graphics();
            particle.beginFill(0xDAA520, 0.8);
            particle.drawCircle(0, 0, 3 + Math.random() * 5);
            particle.endFill();

            const angle = (i / 50) * Math.PI * 2;
            const speed = 5 + Math.random() * 5;

            particle.x = window.innerWidth / 2;
            particle.y = window.innerHeight / 2;
            particle._vx = Math.cos(angle) * speed;
            particle._vy = Math.sin(angle) * speed;
            particle._life = 1;
            particle._decay = 0.015;
            particle._type = 'burst';

            pixiApp.stage.addChild(particle);
            particles.push(particle);
        }
    }, 500);
}

// ================================================
// DEFEAT EFFECTS
// ================================================
function showDefeatEffects() {
    // Darken and desaturate
    const container = document.getElementById('battle-container');
    if (container) {
        container.style.filter = 'saturate(0.3) brightness(0.7)';
        container.style.transition = 'filter 1s ease';
    }

    // Falling debris
    for (let i = 0; i < 30; i++) {
        setTimeout(() => {
            const debris = new PIXI.Graphics();
            debris.beginFill(0x333333, 0.6);
            debris.drawRect(0, 0, 5 + Math.random() * 10, 5 + Math.random() * 10);
            debris.endFill();

            debris.x = Math.random() * window.innerWidth;
            debris.y = -20;
            debris._vx = (Math.random() - 0.5) * 2;
            debris._vy = 3 + Math.random() * 3;
            debris._rotation = Math.random() * 0.1;
            debris._life = 1;
            debris._decay = 0.005;
            debris._type = 'debris';

            pixiApp.stage.addChild(debris);
            particles.push(debris);
        }, i * 50);
    }
}

// ================================================
// UPDATE LOOP
// ================================================
function updateEffects(delta) {
    const time = Date.now() * 0.001;

    // Update background blobs (slow drift)
    backgroundSprites.forEach(sprite => {
        const offsetX = Math.sin(time * sprite._speed + sprite._offset) * 50;
        const offsetY = Math.cos(time * sprite._speed * 1.3 + sprite._offset) * 30;
        sprite.x = window.innerWidth * sprite._baseX + offsetX;
        sprite.y = window.innerHeight * sprite._baseY + offsetY;
    });

    // Update particles
    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];

        if (p._type === 'ambient') {
            p.x += p._vx;
            p.y += p._vy;
            p._life -= p._decay;
            p.alpha = p._life * 0.5;

            // Respawn at bottom if goes off screen or dies
            if (p.y < -10 || p._life <= 0) {
                p.x = Math.random() * window.innerWidth;
                p.y = window.innerHeight + 10;
                p._life = 1;
            }
        } else if (p._type === 'aura') {
            p._angle += p._speed;
            p.x = p._centerX + Math.cos(p._angle) * p._radius;
            p.y = p._centerY + Math.sin(p._angle) * p._radius;
            p._life -= p._decay;
            p.alpha = p._life;

            if (p._life <= 0) {
                pixiApp.stage.removeChild(p);
                particles.splice(i, 1);
            }
        } else if (p._type === 'spark') {
            p.x += p._vx;
            p.y += p._vy;
            p._vy += p._gravity;
            p._vx *= 0.98;
            p._life -= p._decay;
            p.alpha = p._life;

            if (p._life <= 0) {
                pixiApp.stage.removeChild(p);
                particles.splice(i, 1);
            }
        } else if (p._type === 'confetti' || p._type === 'debris') {
            p.x += p._vx;
            p.y += p._vy;
            p.rotation += p._rotation;
            p._vy += 0.05;
            p._life -= p._decay;
            p.alpha = p._life;

            if (p.y > window.innerHeight + 20 || p._life <= 0) {
                pixiApp.stage.removeChild(p);
                particles.splice(i, 1);
            }
        } else if (p._type === 'burst') {
            p.x += p._vx;
            p.y += p._vy;
            p._vx *= 0.95;
            p._vy *= 0.95;
            p._life -= p._decay;
            p.alpha = p._life;
            p.scale.set(p._life);

            if (p._life <= 0) {
                pixiApp.stage.removeChild(p);
                particles.splice(i, 1);
            }
        }
    }
}

// ================================================
// RESIZE HANDLER
// ================================================
function onResize() {
    if (!pixiApp) return;

    pixiApp.renderer.resize(window.innerWidth, window.innerHeight);

    // Update background positions
    backgroundSprites.forEach(sprite => {
        sprite.x = window.innerWidth * sprite._baseX;
        sprite.y = window.innerHeight * sprite._baseY;
    });
}

// ================================================
// CLEANUP
// ================================================
function destroyPixiEffects() {
    if (pixiApp) {
        pixiApp.destroy(true, { children: true, texture: true });
        pixiApp = null;
    }
    particles = [];
    backgroundSprites = [];
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    // Wait a bit for PIXI to load from CDN
    setTimeout(initPixiEffects, 100);
});
