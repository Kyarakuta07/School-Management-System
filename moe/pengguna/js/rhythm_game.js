/**
 * MOE Pet System - Rhythm Game Engine
 * Fullscreen rhythm game with 4-lane notes
 */

// ================================================
// GAME STATE
// ================================================
const GameState = {
    score: 0,
    combo: 0,
    maxCombo: 0,
    perfectHits: 0,
    timeLeft: 30,
    isPlaying: false,
    notes: [],
    noteIdCounter: 0,
    timerInterval: null,
    spawnInterval: null,
    fallDuration: 2000 // ms for note to fall
};

// Lane colors
const LANE_COLORS = ['#ff6b6b', '#4ecdc4', '#ffd93d', '#a78bfa'];
const LANE_KEYS = ['KeyD', 'KeyF', 'KeyJ', 'KeyK'];

// ================================================
// DOM ELEMENTS
// ================================================
const DOM = {
    scoreDisplay: null,
    comboDisplay: null,
    timerDisplay: null,
    gameArea: null,
    dancingPet: null,
    startOverlay: null,
    resultOverlay: null,
    lanes: []
};

// Initialize DOM references
document.addEventListener('DOMContentLoaded', () => {
    DOM.scoreDisplay = document.getElementById('score-display');
    DOM.comboDisplay = document.getElementById('combo-display');
    DOM.timerDisplay = document.getElementById('timer-display');
    DOM.gameArea = document.getElementById('game-area');
    DOM.dancingPet = document.getElementById('dancing-pet');
    DOM.startOverlay = document.getElementById('start-overlay');
    DOM.resultOverlay = document.getElementById('result-overlay');
    DOM.lanes = document.querySelectorAll('.lane');

    // Setup input handlers
    setupKeyboardInput();
    setupTouchInput();
});

// ================================================
// INPUT HANDLERS
// ================================================
// Track pressed keys to prevent auto-repeat
const keysPressed = {};

function setupKeyboardInput() {
    document.addEventListener('keydown', (e) => {
        if (!GameState.isPlaying) return;

        // Prevent key repeat
        if (e.repeat || keysPressed[e.code]) return;

        const laneIndex = LANE_KEYS.indexOf(e.code);
        if (laneIndex !== -1) {
            e.preventDefault();
            keysPressed[e.code] = true;
            hitLane(laneIndex);
        }
    });

    document.addEventListener('keyup', (e) => {
        keysPressed[e.code] = false;
        const laneIndex = LANE_KEYS.indexOf(e.code);
        if (laneIndex !== -1) {
            const lanes = document.querySelectorAll('.lane');
            lanes[laneIndex]?.classList.remove('active');
        }
    });
}

function setupTouchInput() {
    const touchLanes = document.querySelectorAll('.touch-lane');

    touchLanes.forEach((touchLane, index) => {
        touchLane.addEventListener('touchstart', (e) => {
            e.preventDefault();
            if (GameState.isPlaying) {
                hitLane(index);
            }
        });

        touchLane.addEventListener('touchend', () => {
            DOM.lanes[index]?.classList.remove('active');
        });
    });
}

// ================================================
// GAME CONTROL
// ================================================
function startGame() {
    console.log('startGame called');

    // Reset state
    GameState.score = 0;
    GameState.combo = 0;
    GameState.maxCombo = 0;
    GameState.perfectHits = 0;
    GameState.timeLeft = 30;
    GameState.isPlaying = true;
    GameState.notes = [];
    GameState.noteIdCounter = 0;

    // Hide start overlay
    const startOverlay = document.getElementById('start-overlay');
    if (startOverlay) {
        startOverlay.classList.add('hidden');
        console.log('Start overlay hidden');
    } else {
        console.error('Start overlay not found');
    }

    // Update display
    updateDisplay();

    // Start timer
    GameState.timerInterval = setInterval(() => {
        GameState.timeLeft--;
        document.getElementById('timer-display').textContent = GameState.timeLeft;

        // Warning animation when low
        if (GameState.timeLeft <= 5) {
            document.querySelector('.timer-box').classList.add('warning');
        }

        if (GameState.timeLeft <= 0) {
            endGame();
        }
    }, 1000);

    // Start spawning notes
    GameState.spawnInterval = setInterval(() => {
        if (GameState.isPlaying) {
            spawnNote();
        }
    }, 500); // Spawn every 500ms
}

function endGame() {
    GameState.isPlaying = false;

    // Clear intervals
    clearInterval(GameState.timerInterval);
    clearInterval(GameState.spawnInterval);

    // Clear remaining notes
    GameState.notes.forEach(note => note.element?.remove());
    GameState.notes = [];

    // Submit score to backend
    submitScore();
}

function exitGame() {
    if (GameState.isPlaying) {
        if (!confirm('Exit game? Your progress will be lost.')) {
            return;
        }
        GameState.isPlaying = false;
        clearInterval(GameState.timerInterval);
        clearInterval(GameState.spawnInterval);
    }

    window.location.href = 'pet.php';
}

function returnToPet() {
    window.location.href = 'pet.php';
}

// ================================================
// NOTE SYSTEM
// ================================================
function spawnNote() {
    // Random lane
    const laneIndex = Math.floor(Math.random() * 4);
    const lanes = document.querySelectorAll('.lane');
    const lane = lanes[laneIndex];

    // Create note element
    const note = document.createElement('div');
    note.className = 'note';
    note.style.setProperty('--note-color', LANE_COLORS[laneIndex]);
    note.style.setProperty('--fall-duration', `${GameState.fallDuration}ms`);
    note.dataset.noteId = GameState.noteIdCounter++;
    note.dataset.lane = laneIndex;

    // Add to lane
    lane.appendChild(note);

    // Track note
    const noteData = {
        id: note.dataset.noteId,
        lane: laneIndex,
        element: note,
        spawnTime: Date.now()
    };
    GameState.notes.push(noteData);

    // Remove note after fall animation
    setTimeout(() => {
        if (note.parentNode && !note.classList.contains('hit')) {
            // Missed!
            note.classList.add('miss');
            GameState.combo = 0;
            updateDisplay();
            showHitText(note, 'MISS', 'miss');
            SoundManager.hitMiss();

            setTimeout(() => note.remove(), 300);

            // Remove from tracking
            const idx = GameState.notes.findIndex(n => n.id === noteData.id);
            if (idx > -1) GameState.notes.splice(idx, 1);
        }
    }, GameState.fallDuration);
}

// ================================================
// HIT DETECTION
// ================================================
// Lane cooldown to prevent double hits on stacked notes
const laneCooldown = [0, 0, 0, 0]; // Last hit time per lane
const COOLDOWN_MS = 80; // Minimum ms between hits on same lane

function hitLane(laneIndex) {
    const now = Date.now();

    // Check cooldown
    if (now - laneCooldown[laneIndex] < COOLDOWN_MS) {
        return;
    }

    // Visual feedback
    const lanes = document.querySelectorAll('.lane');
    lanes[laneIndex]?.classList.add('active');

    // Find notes in this lane (exclude already hit notes)
    const laneNotes = GameState.notes.filter(n =>
        n.lane === laneIndex &&
        !n.isHit &&
        !n.isProcessing
    );

    if (laneNotes.length === 0) return;

    // Find the note closest to hit zone (only the ONE best note)
    let bestNote = null;
    let bestTiming = Infinity;

    const hitZoneTime = GameState.fallDuration * 0.85;
    const hitWindow = 180; // Slightly tighter window

    for (let i = 0; i < laneNotes.length; i++) {
        const noteData = laneNotes[i];
        const elapsed = now - noteData.spawnTime;
        const diff = Math.abs(elapsed - hitZoneTime);

        if (diff < hitWindow && diff < bestTiming) {
            bestTiming = diff;
            bestNote = noteData;
        }
    }

    if (bestNote) {
        // IMMEDIATELY mark as processing and hit
        bestNote.isProcessing = true;
        bestNote.isHit = true;
        laneCooldown[laneIndex] = now;

        // Process the hit
        processHit(bestNote, bestTiming);

        // IMMEDIATELY remove from notes array
        const idx = GameState.notes.indexOf(bestNote);
        if (idx > -1) {
            GameState.notes.splice(idx, 1);
        }
    }
}

function processHit(noteData, timing) {
    const note = noteData.element;

    // Safety check
    if (!note || !note.parentNode) return;

    // Determine hit quality
    let quality, points;
    if (timing < 40) {
        quality = 'PERFECT';
        points = 15;
        GameState.perfectHits++;
        SoundManager.hitPerfect();
    } else if (timing < 90) {
        quality = 'GREAT';
        points = 10;
        SoundManager.hitGood();
    } else {
        quality = 'GOOD';
        points = 5;
        SoundManager.hitGood();
    }

    // Update score and combo
    GameState.score += points * (1 + Math.floor(GameState.combo / 10) * 0.1);
    GameState.combo++;
    GameState.maxCombo = Math.max(GameState.maxCombo, GameState.combo);

    // Check combo milestones
    if (GameState.combo > 0 && GameState.combo % 10 === 0) {
        showComboPopup(GameState.combo);
        SoundManager.combo();
    }

    // Visual feedback
    note.classList.add('hit');
    showHitEffect(note);
    showHitText(note, quality, quality.toLowerCase());

    // Remove note
    setTimeout(() => note.remove(), 300);

    // Remove from tracking
    const idx = GameState.notes.findIndex(n => n.id === noteData.id);
    if (idx > -1) GameState.notes.splice(idx, 1);

    // Update display
    updateDisplay();
}

// ================================================
// VISUAL EFFECTS
// ================================================
function showHitEffect(note) {
    const rect = note.getBoundingClientRect();
    const effect = document.createElement('div');
    effect.className = 'hit-effect';
    effect.style.left = rect.left + rect.width / 2 + 'px';
    effect.style.top = rect.top + rect.height / 2 + 'px';
    document.body.appendChild(effect);

    setTimeout(() => effect.remove(), 400);
}

function showHitText(note, text, className) {
    const rect = note.getBoundingClientRect();
    const textEl = document.createElement('div');
    textEl.className = `hit-text ${className}`;
    textEl.textContent = text;
    textEl.style.left = rect.left + rect.width / 2 + 'px';
    textEl.style.top = rect.top + 'px';
    document.body.appendChild(textEl);

    setTimeout(() => textEl.remove(), 500);
}

function showComboPopup(combo) {
    const popup = document.createElement('div');
    popup.className = 'combo-popup';
    popup.textContent = `${combo} COMBO!`;
    document.body.appendChild(popup);

    setTimeout(() => popup.remove(), 600);
}

function updateDisplay() {
    document.getElementById('score-display').textContent = Math.floor(GameState.score);
    document.getElementById('combo-display').textContent = GameState.combo;
}

// ================================================
// SCORE SUBMISSION
// ================================================
async function submitScore() {
    const finalScore = Math.floor(GameState.score);

    try {
        const response = await fetch(`${API_BASE}?action=play_finish`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pet_id: PET_ID,
                score: finalScore
            })
        });

        const data = await response.json();

        // Show results
        showResults(data);

    } catch (error) {
        console.error('Error submitting score:', error);
        showResults({ success: false, rewards: { mood: 0, exp: 0 } });
    }
}

function showResults(data) {
    const finalScore = Math.floor(GameState.score);

    // Determine title based on score
    let title = '🎉 Great!';
    if (finalScore >= 200) title = '🏆 AMAZING!';
    else if (finalScore >= 150) title = '⭐ Excellent!';
    else if (finalScore >= 100) title = '🎉 Great!';
    else if (finalScore >= 50) title = '👍 Good Job!';
    else title = '🎵 Keep Trying!';

    // Update result screen
    document.getElementById('result-title').textContent = title;
    document.getElementById('final-score').textContent = finalScore;
    document.getElementById('max-combo').textContent = GameState.maxCombo;
    document.getElementById('perfect-hits').textContent = GameState.perfectHits;

    if (data.success && data.rewards) {
        document.getElementById('reward-mood').textContent = `+${data.rewards.mood}`;
        document.getElementById('reward-exp').textContent = `+${data.rewards.exp}`;
    }

    // Show result overlay
    document.getElementById('result-overlay').classList.remove('hidden');
}
