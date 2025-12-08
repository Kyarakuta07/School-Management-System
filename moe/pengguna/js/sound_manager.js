/**
 * MOE Sound Manager
 * Web Audio API-based sound effects
 * No external audio files needed!
 */

const SoundManager = (() => {
    let audioContext = null;
    let enabled = true;

    // Initialize audio context on first user interaction
    function init() {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        return audioContext;
    }

    // Enable/disable sounds
    function setEnabled(value) {
        enabled = value;
    }

    function isEnabled() {
        return enabled;
    }

    // ================================================
    // CORE SOUND GENERATORS
    // ================================================

    // Simple beep/ding sound
    function playTone(frequency, duration, type = 'sine', volume = 0.3) {
        if (!enabled) return;
        const ctx = init();

        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);

        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, ctx.currentTime);

        gainNode.gain.setValueAtTime(volume, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + duration);

        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + duration);
    }

    // Noise burst (for swoosh/impact)
    function playNoise(duration, volume = 0.2) {
        if (!enabled) return;
        const ctx = init();

        const bufferSize = ctx.sampleRate * duration;
        const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
        const data = buffer.getChannelData(0);

        for (let i = 0; i < bufferSize; i++) {
            data[i] = Math.random() * 2 - 1;
        }

        const noise = ctx.createBufferSource();
        const gainNode = ctx.createGain();
        const filter = ctx.createBiquadFilter();

        noise.buffer = buffer;
        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(1000, ctx.currentTime);
        filter.frequency.exponentialRampToValueAtTime(100, ctx.currentTime + duration);

        noise.connect(filter);
        filter.connect(gainNode);
        gainNode.connect(ctx.destination);

        gainNode.gain.setValueAtTime(volume, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + duration);

        noise.start();
        noise.stop(ctx.currentTime + duration);
    }

    // ================================================
    // RHYTHM GAME SOUNDS
    // ================================================

    function hitPerfect() {
        playTone(1200, 0.1, 'sine', 0.4);
        setTimeout(() => playTone(1500, 0.1, 'sine', 0.3), 50);
    }

    function hitGood() {
        playTone(800, 0.15, 'sine', 0.3);
    }

    function hitMiss() {
        playTone(200, 0.2, 'sawtooth', 0.2);
    }

    function combo() {
        // Quick ascending notes
        playTone(600, 0.08, 'sine', 0.2);
        setTimeout(() => playTone(800, 0.08, 'sine', 0.2), 60);
        setTimeout(() => playTone(1000, 0.08, 'sine', 0.2), 120);
    }

    // ================================================
    // BATTLE ARENA SOUNDS
    // ================================================

    function attack() {
        // Swoosh
        playNoise(0.15, 0.3);
    }

    function damage() {
        // Impact thud
        playTone(100, 0.15, 'sine', 0.4);
        playNoise(0.08, 0.2);
    }

    function critical() {
        // Big impact
        playTone(80, 0.2, 'sine', 0.5);
        playNoise(0.12, 0.3);
        setTimeout(() => playTone(150, 0.1, 'sine', 0.3), 100);
    }

    function victory() {
        // Fanfare ascending
        playTone(523, 0.2, 'sine', 0.3); // C5
        setTimeout(() => playTone(659, 0.2, 'sine', 0.3), 150); // E5
        setTimeout(() => playTone(784, 0.2, 'sine', 0.3), 300); // G5
        setTimeout(() => playTone(1047, 0.4, 'sine', 0.4), 450); // C6
    }

    function defeat() {
        // Sad descending
        playTone(400, 0.3, 'sine', 0.3);
        setTimeout(() => playTone(300, 0.3, 'sine', 0.25), 200);
        setTimeout(() => playTone(200, 0.5, 'sine', 0.2), 400);
    }

    // ================================================
    // UI SOUNDS
    // ================================================

    function click() {
        playTone(600, 0.05, 'sine', 0.1);
    }

    function success() {
        playTone(800, 0.1, 'sine', 0.2);
        setTimeout(() => playTone(1000, 0.15, 'sine', 0.25), 80);
    }

    function error() {
        playTone(300, 0.15, 'square', 0.2);
    }

    // ================================================
    // PUBLIC API
    // ================================================

    return {
        init,
        setEnabled,
        isEnabled,
        // Rhythm game
        hitPerfect,
        hitGood,
        hitMiss,
        combo,
        // Battle
        attack,
        damage,
        critical,
        victory,
        defeat,
        // UI
        click,
        success,
        error
    };
})();

// Auto-init on first click/touch
document.addEventListener('click', () => SoundManager.init(), { once: true });
document.addEventListener('touchstart', () => SoundManager.init(), { once: true });
