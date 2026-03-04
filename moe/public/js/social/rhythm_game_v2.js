/**
 * MOE Pet System - Rhythm Game Engine V2 (Refactored)
 * 
 * FIXES APPLIED:
 * - Time-based hit detection (ms windows, not pixel-based)
 * - Web Audio API for music playback (sample-accurate sync)
 * - GPU-accelerated note movement via CSS transform
 * - Symmetric hit windows in milliseconds
 * - CSRF-protected score submission
 * - Dedicated effects container (no document.body pollution)
 * - Cached DOM references
 * - GOOD hit tier added
 * - Wrong tap = combo break only (no life loss)
 * - Letter grade calculation & display
 */

// ================================================
// CONSTANTS
// ================================================
const HIT_WINDOWS = {
    PERFECT_PLUS: 20,   // ±20ms
    PERFECT: 50,        // ±50ms
    GREAT: 100,         // ±100ms
    GOOD: 150,          // ±150ms
    MISS_THRESHOLD: 200 // >200ms past = auto-miss
};

const LANE_KEYS = ['KeyD', 'KeyF', 'KeyJ', 'KeyK'];
const LANE_COLORS = ['#ff6b6b', '#4ecdc4', '#ffd93d', '#a78bfa'];

// ================================================
// GAME STATE
// ================================================
const GameState = {
    score: 0,
    combo: 0,
    maxCombo: 0,
    perfectPlusHits: 0,
    perfectHits: 0,
    greatHits: 0,
    goodHits: 0,
    missHits: 0,
    lives: 3,
    maxLives: 3,
    isPlaying: false,

    // Song & Beatmap
    currentSong: null,
    beatmap: [],
    beatmapIndex: 0,

    // Timing
    noteSpeed: 400, // pixels per second
    audioStartTime: 0, // audioContext.currentTime when playback began

    // Active notes on screen
    activeNotes: [],
    noteIdCounter: 0,

    // Hit zone position (calculated from DOM once)
    hitZoneY: 0,
    gameAreaHeight: 0,

    // Input queue (processed in game loop)
    inputQueue: [],

    // Audio (hybrid: Web Audio API primary, HTML5 Audio fallback)
    audioContext: null,
    audioSource: null,
    audioBuffer: null,
    audioGainNode: null,
    isAudioPlaying: false,
    useWebAudio: false, // true if Web Audio decode succeeded
    htmlAudio: null,    // HTML5 Audio fallback element
    songDuration: 0,    // song length in ms
    gameClockStart: 0,  // performance.now() fallback timer
    isPaused: false,
    pauseStartTime: 0,
    totalPauseTime: 0,
    finalDuration: 0
};

// ================================================
// DOM ELEMENTS (cached once)
// ================================================
const DOM = {
    songSelect: null,
    songsContainer: null,
    gameContainer: null,
    scoreDisplay: null,
    comboDisplay: null,
    livesDisplay: null,
    gameArea: null,
    hitZone: null,
    countdownOverlay: null,
    resultOverlay: null,
    lanes: [],
    effectsContainer: null,
    heartCache: [] // cached heart elements
};

// ================================================
// INITIALIZATION
// ================================================
document.addEventListener('DOMContentLoaded', () => {
    DOM.songSelect = document.getElementById('hub-screen');
    DOM.songsContainer = document.getElementById('songs-container');
    DOM.gameContainer = document.getElementById('game-screen');
    DOM.scoreDisplay = document.getElementById('score-display');
    DOM.comboDisplay = document.getElementById('combo-display');
    DOM.livesDisplay = document.getElementById('lives-display');
    DOM.gameArea = document.getElementById('track-area');
    DOM.hitZone = document.querySelector('.hit-horizon');
    DOM.countdownOverlay = document.getElementById('countdown-overlay');
    DOM.resultOverlay = document.getElementById('result-overlay');
    DOM.lanes = document.querySelectorAll('.track-lane');
    DOM.effectsContainer = document.getElementById('effects-container');

    // Setup input handlers
    setupKeyboardInput();
    setupTouchInput();

    // Load songs
    loadSongs();
});

// ================================================
// UTILITIES
// ================================================
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Get current audio time in milliseconds.
 * Uses Web Audio API context timing if available, HTML5 Audio fallback otherwise.
 */
function getAudioTimeMs() {
    if (!GameState.isAudioPlaying) return 0;

    if (GameState.useWebAudio && GameState.audioContext) {
        return (GameState.audioContext.currentTime - GameState.audioStartTime) * 1000;
    }

    // HTML5 Audio fallback
    if (GameState.htmlAudio) {
        return GameState.htmlAudio.currentTime * 1000;
    }

    // Silent mode: use performance.now()
    if (GameState.gameClockStart > 0) {
        return performance.now() - GameState.gameClockStart;
    }

    return 0;
}

// ================================================
// AUDIO SYSTEM (Hybrid: Web Audio API + HTML5 Audio fallback)
// ================================================
function getAudioContext() {
    if (!GameState.audioContext) {
        GameState.audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (GameState.audioContext.state === 'suspended') {
        GameState.audioContext.resume();
    }
    return GameState.audioContext;
}

/**
 * Load audio for a song. Tries Web Audio API first, falls back to HTML5 Audio.
 * Returns { mode: 'webaudio'|'html5', duration: number_in_ms }
 */
async function loadAudioForSong(url) {
    // Use HTML5 Audio first (supports streaming — no full download needed)
    return new Promise((resolve, reject) => {
        const audio = new Audio();
        audio.preload = 'auto';

        let resolved = false;

        function onReady() {
            if (resolved) return;
            resolved = true;
            GameState.htmlAudio = audio;
            GameState.useWebAudio = false;
            GameState.songDuration = (audio.duration && isFinite(audio.duration))
                ? audio.duration * 1000
                : estimateSongDuration();
            console.log('[Audio] HTML5 Audio ready (streaming), duration:', GameState.songDuration, 'ms');
            resolve({ mode: 'html5', duration: GameState.songDuration });
        }

        audio.addEventListener('canplaythrough', onReady, { once: true });
        audio.addEventListener('loadedmetadata', () => {
            // Start as soon as metadata is available (don't wait for full download)
            setTimeout(onReady, 300);
        }, { once: true });

        audio.addEventListener('error', (e) => {
            if (resolved) return;
            resolved = true;
            console.warn('[Audio] HTML5 Audio failed, trying Web Audio API:', e);

            // Fallback: Web Audio API (requires full download)
            loadAudioWebAPI(url).then(resolve).catch(() => {
                // Last resort: play without audio
                GameState.useWebAudio = false;
                GameState.htmlAudio = null;
                GameState.songDuration = estimateSongDuration();
                console.log('[Audio] No audio available, using beatmap-estimated duration:', GameState.songDuration, 'ms');
                resolve({ mode: 'silent', duration: GameState.songDuration });
            });
        }, { once: true });

        // Timeout after 5 seconds — proceed with what we have
        setTimeout(() => {
            if (!resolved) {
                resolved = true;
                GameState.htmlAudio = audio;
                GameState.useWebAudio = false;
                GameState.songDuration = (audio.duration && isFinite(audio.duration))
                    ? audio.duration * 1000
                    : estimateSongDuration();
                console.log('[Audio] Timeout, proceeding with streaming. Duration:', GameState.songDuration, 'ms');
                resolve({ mode: 'html5', duration: GameState.songDuration });
            }
        }, 5000);

        audio.src = url;
        audio.load();
    });
}

/**
 * Web Audio API fallback (requires downloading entire file first).
 */
async function loadAudioWebAPI(url) {
    const ctx = getAudioContext();
    const response = await fetch(url);
    const arrayBuffer = await response.arrayBuffer();
    const audioBuffer = await ctx.decodeAudioData(arrayBuffer);

    GameState.audioBuffer = audioBuffer;
    GameState.useWebAudio = true;
    GameState.songDuration = audioBuffer.duration * 1000;
    console.log('[Audio] Web Audio API decode success, duration:', GameState.songDuration, 'ms');
    return { mode: 'webaudio', duration: GameState.songDuration };
}

/**
 * Estimate song duration from beatmap data (last note time + 3 seconds buffer).
 */
function estimateSongDuration() {
    if (GameState.beatmap && GameState.beatmap.length > 0) {
        const lastNote = GameState.beatmap[GameState.beatmap.length - 1];
        return lastNote.time + (lastNote.duration || 0) + 3000;
    }
    return 60000; // default 1 minute
}

/**
 * Start audio playback (works with both modes).
 */
function playAudio() {
    GameState.gameClockStart = performance.now(); // Always set fallback clock

    if (GameState.useWebAudio && GameState.audioBuffer) {
        const ctx = getAudioContext();

        GameState.audioGainNode = ctx.createGain();
        GameState.audioGainNode.gain.value = 1.0;
        GameState.audioGainNode.connect(ctx.destination);

        GameState.audioSource = ctx.createBufferSource();
        GameState.audioSource.buffer = GameState.audioBuffer;
        GameState.audioSource.connect(GameState.audioGainNode);

        GameState.audioStartTime = ctx.currentTime;
        GameState.audioSource.start(0);
        GameState.isAudioPlaying = true;

        GameState.audioSource.onended = () => {
            GameState.isAudioPlaying = false;
        };
    } else if (GameState.htmlAudio) {
        GameState.htmlAudio.currentTime = 0;
        GameState.htmlAudio.play().then(() => {
            GameState.isAudioPlaying = true;
        }).catch(e => {
            console.error('[Audio] HTML5 play failed:', e);
        });

        GameState.htmlAudio.onended = () => {
            GameState.isAudioPlaying = false;
        };

        GameState.isAudioPlaying = true;
    } else {
        // Silent mode — game runs on performance.now() clock
        console.log('[Audio] Playing in silent mode (no audio)');
        GameState.isAudioPlaying = true;
    }
}

function stopAudio() {
    if (GameState.useWebAudio && GameState.audioSource) {
        try { GameState.audioSource.stop(); } catch (e) { /* already stopped */ }
        GameState.audioSource = null;
    }
    if (GameState.htmlAudio) {
        GameState.htmlAudio.pause();
        GameState.htmlAudio.currentTime = 0;
    }
    GameState.isAudioPlaying = false;
}

// ================================================
// SONG LOADING
// ================================================
let cachedSongs = [];
let cachedHsMap = {};

async function loadSongs() {
    try {
        const response = await fetch(`${API_BASE}rhythm/songs`);
        const data = await response.json();

        if (data.success && data.songs.length > 0) {
            cachedSongs = data.songs;

            // Fetch highscores for all songs in parallel
            const highscorePromises = data.songs.map(song =>
                fetch(`${API_BASE}rhythm/highscore?song_id=${song.id}`)
                    .then(r => r.json())
                    .then(d => ({ songId: song.id, hs: d.highscore }))
                    .catch(() => ({ songId: song.id, hs: null }))
            );
            const highscores = await Promise.all(highscorePromises);
            cachedHsMap = {};
            highscores.forEach(h => { cachedHsMap[h.songId] = h.hs; });

            renderSongCards(cachedSongs, cachedHsMap);
            updateSongCount(cachedSongs.length, cachedSongs.length);

            // Setup search
            const searchInput = document.getElementById('song-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const q = e.target.value.toLowerCase().trim();
                    if (!q) {
                        renderSongCards(cachedSongs, cachedHsMap);
                        updateSongCount(cachedSongs.length, cachedSongs.length);
                        return;
                    }
                    const filtered = cachedSongs.filter(s =>
                        s.title.toLowerCase().includes(q) ||
                        s.artist.toLowerCase().includes(q)
                    );
                    renderSongCards(filtered, cachedHsMap);
                    updateSongCount(filtered.length, cachedSongs.length);
                });
            }
        } else {
            DOM.songsContainer.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.5);padding:2rem;">No songs available yet.</p>';
        }
    } catch (error) {
        console.error('Error loading songs:', error);
        DOM.songsContainer.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.5);padding:2rem;">Failed to load songs.</p>';
    }
}

function updateSongCount(shown, total) {
    const el = document.getElementById('song-count');
    if (el) {
        el.textContent = shown === total ? `${total} songs` : `${shown}/${total}`;
    }
}

const GRADE_COLORS = { S: '#FFD700', A: '#4ecdc4', B: '#a78bfa', C: '#ffd93d', D: '#ff6b6b', F: '#666' };

function renderSongCards(songs, hsMap = {}) {
    if (songs.length === 0) {
        DOM.songsContainer.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.35);padding:2rem;"><i class="fas fa-search"></i> No songs found</p>';
        return;
    }

    DOM.songsContainer.innerHTML = songs.map(song => {
        const hs = hsMap[song.id];
        const grade = hs ? (hs.rank_grade || hs.rank || '-') : null;
        const gradeColor = grade ? (GRADE_COLORS[grade.toUpperCase()] || '#888') : '#888';

        const hsBadge = hs
            ? `<div class="hs-badge-row">
                    <span class="grade-circle" style="background:${gradeColor};color:#000;">${grade}</span>
                    <span class="hs-score">${hs.score.toLocaleString()}</span>
               </div>`
            : `<div class="hs-badge-row no-record">
                    <span class="grade-circle" style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.3);">-</span>
                    <span class="hs-score" style="opacity:0.3;">No record</span>
               </div>`;

        return `
        <div class="song-card" onclick="selectSong(${parseInt(song.id)})">
            <div class="play-overlay">
                <div class="play-icon">▶</div>
            </div>
            <div class="card-content">
                <div class="song-info">
                    <h2>${escapeHtml(song.title)}</h2>
                    <div class="song-meta-row">
                        <span class="artist-name">${escapeHtml(song.artist)}</span>
                        <span class="bpm-tag"><i class="fas fa-drum"></i> ${parseInt(song.bpm)}</span>
                        <span class="difficulty-pill">${escapeHtml(song.difficulty)}</span>
                    </div>
                </div>
                ${hsBadge}
            </div>
        </div>
    `;
    }).join('');
}

// ================================================
// BEATMAP POST-PROCESSING
// Merge consecutive same-lane taps into hold notes (Piano Tiles style)
// ================================================

/**
 * Converts rapid consecutive same-lane taps into hold notes.
 * This makes osu! standard mode beatmaps look like Piano Tiles with long bars.
 */
function postProcessBeatmap(notes, bpm = 120) {
    if (!notes || notes.length < 2) return notes || [];

    // BPM-aware threshold: half a beat interval (faster songs = shorter merge window)
    const beatInterval = 60000 / Math.max(bpm, 60);
    const mergeThreshold = Math.min(beatInterval * 0.6, 400); // cap at 400ms
    console.log(`[Beatmap] Using merge threshold: ${mergeThreshold.toFixed(0)}ms (BPM: ${bpm})`);

    // Sort by time first
    notes.sort((a, b) => a.time - b.time);

    // Already has hold notes from the import? Skip processing.
    const existingHolds = notes.filter(n => n.type === 'hold' && n.duration > 0);
    if (existingHolds.length > 0) {
        console.log(`[Beatmap] ${existingHolds.length} hold notes already present, skipping merge`);
        return notes;
    }

    const processed = [];
    let i = 0;

    while (i < notes.length) {
        const current = notes[i];

        // Look ahead: find consecutive taps in the same lane
        let mergeEnd = i;
        for (let j = i + 1; j < notes.length; j++) {
            const next = notes[j];
            const prev = notes[mergeEnd];

            // Same lane AND close enough in time?
            if (next.lane === current.lane && (next.time - prev.time) <= mergeThreshold) {
                mergeEnd = j;
            } else {
                break;
            }
        }

        const mergedCount = mergeEnd - i + 1;

        if (mergedCount >= 3) {
            // 3+ consecutive same-lane taps → merge into a hold note
            const lastNote = notes[mergeEnd];
            processed.push({
                time: current.time,
                lane: current.lane,
                type: 'hold',
                duration: lastNote.time - current.time
            });
        } else {
            // 1-2 taps: keep as individual tap notes
            for (let k = i; k <= mergeEnd; k++) {
                processed.push({
                    time: notes[k].time,
                    lane: notes[k].lane,
                    type: 'tap',
                    duration: 0
                });
            }
        }

        i = mergeEnd + 1;
    }

    const holdCount = processed.filter(n => n.type === 'hold').length;
    console.log(`[Beatmap] Post-processed: ${notes.length} → ${processed.length} notes (${holdCount} holds merged)`);
    return processed;
}

// ================================================
// SONG SELECTION & GAME START
// ================================================
async function selectSong(songId) {
    try {
        // Show loading state
        DOM.songsContainer.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.5);padding:2rem;"><i class="fas fa-spinner fa-spin"></i> Loading beatmap & audio...</p>';

        const response = await fetch(`${API_BASE}rhythm/beatmap?song_id=${songId}`);
        const data = await response.json();

        if (!data.success) {
            alert('Failed to load beatmap');
            loadSongs(); // Re-render song list
            return;
        }

        GameState.currentSong = data.song;
        GameState.beatmap = postProcessBeatmap(data.beatmap, data.bpm || 120);

        // Load audio (hybrid: Web Audio API → HTML5 Audio fallback)
        const audioUrl = MUSIC_PATH + data.song.audio_file;
        await loadAudioForSong(audioUrl);

        // Switch to game view
        DOM.songSelect.classList.add('hidden');
        DOM.gameContainer.classList.remove('hidden');

        // Calculate hit zone position after DOM is visible
        requestAnimationFrame(() => {
            calculateHitZonePosition();
            startCountdown();
        });

    } catch (error) {
        console.error('Error selecting song:', error);
        alert('Failed to start game. Check audio file.');
        loadSongs();
    }
}

function calculateHitZonePosition() {
    const gameAreaRect = DOM.gameArea.getBoundingClientRect();
    const hitZoneRect = DOM.hitZone.getBoundingClientRect();

    GameState.gameAreaHeight = gameAreaRect.height;
    GameState.hitZoneY = hitZoneRect.top - gameAreaRect.top + (hitZoneRect.height / 2);
}

function startCountdown() {
    DOM.countdownOverlay.classList.remove('hidden');
    const countdownEl = document.getElementById('countdown-number');
    let count = 3;

    countdownEl.textContent = count;

    const countdownInterval = setInterval(() => {
        count--;
        if (count > 0) {
            countdownEl.textContent = count;
        } else if (count === 0) {
            countdownEl.textContent = 'GO!';
        } else {
            clearInterval(countdownInterval);
            DOM.countdownOverlay.classList.add('hidden');
            startGame();
        }
    }, 1000);
}

// ================================================
// GAME CONTROL
// ================================================
function startGame() {
    // Reset state
    GameState.score = 0;
    GameState.combo = 0;
    GameState.maxCombo = 0;
    GameState.perfectPlusHits = 0;
    GameState.perfectHits = 0;
    GameState.greatHits = 0;
    GameState.goodHits = 0;
    GameState.missHits = 0;
    GameState.lives = GameState.maxLives;
    GameState.isPlaying = true;
    GameState.beatmapIndex = 0;
    GameState.activeNotes = [];
    GameState.noteIdCounter = 0;
    GameState.inputQueue = [];
    GameState.isPaused = false;
    GameState.pauseStartTime = 0;
    GameState.totalPauseTime = 0;

    // Clear any existing notes
    DOM.lanes.forEach(lane => {
        lane.querySelectorAll('.note').forEach(n => n.remove());
    });

    // Clear effects
    if (DOM.effectsContainer) {
        DOM.effectsContainer.innerHTML = '';
    }

    // Update UI
    updateDisplay();
    initLivesDisplay();

    // Start audio (hybrid: Works with both Web Audio and HTML5)
    playAudio();

    // Start game loop
    requestAnimationFrame(gameLoop);
}

function gameLoop(timestamp) {
    if (!GameState.isPlaying) return;
    if (GameState.isPaused) {
        requestAnimationFrame(gameLoop);
        return;
    }

    // Get current audio time in milliseconds (Web Audio API — sample accurate)
    const audioTime = getAudioTimeMs();

    // Calculate how long notes take to fall to hit zone
    const fallTime = (GameState.hitZoneY / GameState.noteSpeed) * 1000;

    // Spawn notes based on audio time
    while (GameState.beatmapIndex < GameState.beatmap.length) {
        const note = GameState.beatmap[GameState.beatmapIndex];
        const spawnTime = note.time - fallTime;

        if (audioTime >= spawnTime) {
            spawnNote(note.lane, note.time, note.type || 'tap', note.duration || 0);
            GameState.beatmapIndex++;
        } else {
            break;
        }
    }

    // Process queued inputs (time-based hit detection)
    processInputQueue(audioTime);

    // Update notes positions and check for misses
    updateNotes(audioTime);

    // Check if song ended (guard: don't end if songDuration is 0 or unknown)
    const allNotesPlayed = GameState.beatmapIndex >= GameState.beatmap.length && GameState.activeNotes.length === 0;
    if (GameState.songDuration > 0 && audioTime >= GameState.songDuration) {
        endGame(false);
        return;
    }
    if (allNotesPlayed && audioTime > 2000) {
        // All notes done and at least 2 seconds of play
        endGame(false);
        return;
    }

    requestAnimationFrame(gameLoop);
}

// ================================================
// NOTE SPAWNING
// ================================================
function spawnNote(lane, targetTime, type = 'tap', duration = 0) {
    const laneElement = DOM.lanes[lane];
    if (!laneElement) return;

    const note = document.createElement('div');
    note.className = 'note';
    note.dataset.noteId = GameState.noteIdCounter++;

    // Set note color based on lane
    note.style.setProperty('--note-color', LANE_COLORS[lane] || '#fff');

    // Calculate height: tap notes are short (Piano Tiles style)
    let noteHeight = 60; // Piano Tiles: short tiles
    if (type === 'hold' && duration > 0) {
        noteHeight = 60 + (duration / 1000) * GameState.noteSpeed;
        note.classList.add('hold-note');
        note.style.height = noteHeight + 'px';

        note.innerHTML = `
            <span class="hold-head"></span>
            <span class="hold-body-label">HOLD</span>
            <span class="hold-tail"></span>
        `;
    }

    // Start off-screen (via transform — no top mutation)
    note.style.transform = `translateY(-${noteHeight}px)`;

    laneElement.appendChild(note);

    GameState.activeNotes.push({
        id: parseInt(note.dataset.noteId),
        lane: lane,
        element: note,
        targetTime: targetTime,
        type: type,
        duration: duration,
        height: noteHeight,
        isHit: false,
        isHolding: false,
        holdStartTime: 0,
        currentY: -noteHeight // track numeric position
    });
}

// ================================================
// NOTE UPDATE (every frame)
// ================================================
function updateNotes(audioTime) {
    GameState.activeNotes = GameState.activeNotes.filter(noteData => {
        if (noteData.isHit) return false;

        // TIME-BASED POSITION: calculate where the note should be
        const timeToTarget = noteData.targetTime - audioTime;
        const distanceToHitZone = (timeToTarget / 1000) * GameState.noteSpeed;

        // noteBottom at hitZoneY when distance = 0
        const noteBottom = GameState.hitZoneY - distanceToHitZone;
        const noteY = noteBottom - noteData.height;

        // Store numeric Y for effects positioning
        noteData.currentY = noteY;

        // GPU-accelerated position update via transform
        noteData.element.style.transform = `translateY(${noteY}px)`;

        // Hold note completion check
        if (noteData.type === 'hold' && noteData.isHolding) {
            const holdElapsed = audioTime - noteData.holdStartTime;
            if (holdElapsed >= noteData.duration) {
                processHoldComplete(noteData);
                return false;
            }
        }

        // MISS DETECTION: time-based (>200ms past target)
        const timePastTarget = audioTime - noteData.targetTime;
        if (timePastTarget > HIT_WINDOWS.MISS_THRESHOLD && !noteData.isHolding) {
            handleMiss(noteData);
            return false;
        }

        return true;
    });
}

// ================================================
// INPUT HANDLERS
// ================================================
const keysPressed = {};

function setupKeyboardInput() {
    document.addEventListener('keydown', (e) => {
        // Escape = pause/resume
        if (e.code === 'Escape' && GameState.isPlaying) {
            e.preventDefault();
            togglePause();
            return;
        }

        if (!GameState.isPlaying || GameState.isPaused) return;
        if (e.repeat || keysPressed[e.code]) return;

        const laneIndex = LANE_KEYS.indexOf(e.code);
        if (laneIndex !== -1) {
            e.preventDefault();
            keysPressed[e.code] = true;

            // Queue input with timestamp for time-based detection
            GameState.inputQueue.push({
                type: 'hit',
                lane: laneIndex,
                time: getAudioTimeMs()
            });

            // Visual feedback immediately
            DOM.lanes[laneIndex]?.classList.add('active');
        }
    });

    document.addEventListener('keyup', (e) => {
        keysPressed[e.code] = false;
        const laneIndex = LANE_KEYS.indexOf(e.code);
        if (laneIndex !== -1) {
            DOM.lanes[laneIndex]?.classList.remove('active');
            if (GameState.isPlaying) {
                GameState.inputQueue.push({
                    type: 'release',
                    lane: laneIndex,
                    time: getAudioTimeMs()
                });
            }
        }
    });
}

function setupTouchInput() {
    const touchLanes = document.querySelectorAll('.touch-lane');

    touchLanes.forEach((touchLane, index) => {
        touchLane.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            if (GameState.isPlaying) {
                GameState.inputQueue.push({
                    type: 'hit',
                    lane: index,
                    time: getAudioTimeMs()
                });
                DOM.lanes[index]?.classList.add('active');
            }
        }, { passive: false });

        touchLane.addEventListener('pointerup', () => {
            DOM.lanes[index]?.classList.remove('active');
            if (GameState.isPlaying) {
                GameState.inputQueue.push({
                    type: 'release',
                    lane: index,
                    time: getAudioTimeMs()
                });
            }
        });
    });
}

// ================================================
// INPUT PROCESSING (inside game loop)
// ================================================
function processInputQueue(audioTime) {
    while (GameState.inputQueue.length > 0) {
        const input = GameState.inputQueue.shift();

        if (input.type === 'hit') {
            hitLane(input.lane, input.time);
        } else if (input.type === 'release') {
            handleHoldRelease(input.lane, input.time);
        }
    }
}

// ================================================
// HIT DETECTION — TIME-BASED
// ================================================
function hitLane(laneIndex, inputTime) {
    // Find unhit notes in this lane
    const laneNotes = GameState.activeNotes.filter(n =>
        n.lane === laneIndex && !n.isHit && !n.isHolding
    );

    if (laneNotes.length === 0) {
        handleWrongTap(laneIndex);
        return;
    }

    // Find the note closest in TIME to the input
    let bestNote = null;
    let bestTimeDiff = Infinity;

    for (const noteData of laneNotes) {
        const timeDiff = Math.abs(inputTime - noteData.targetTime);

        // Only consider notes within the GOOD window (±150ms)
        if (timeDiff <= HIT_WINDOWS.GOOD && timeDiff < bestTimeDiff) {
            bestTimeDiff = timeDiff;
            bestNote = noteData;
        }
    }

    if (bestNote) {
        if (bestNote.type === 'hold') {
            startHold(bestNote, bestTimeDiff);
        } else {
            processHit(bestNote, bestTimeDiff);
        }
    } else {
        // Check if notes are coming (future notes > GOOD window)
        const upcomingNotes = laneNotes.filter(n => n.targetTime - inputTime > HIT_WINDOWS.GOOD);

        if (upcomingNotes.length > 0) {
            // Too early — just flash
            const lane = DOM.lanes[laneIndex];
            lane.style.background = 'rgba(255, 165, 0, 0.2)';
            setTimeout(() => { lane.style.background = ''; }, 100);
        } else {
            handleWrongTap(laneIndex);
        }
    }
}

function handleWrongTap(laneIndex) {
    // Wrong tap = combo break only (no life loss — A2 fix)
    GameState.combo = 0;

    const lane = DOM.lanes[laneIndex];
    lane.style.background = 'rgba(231, 76, 60, 0.2)';
    setTimeout(() => { lane.style.background = ''; }, 200);

    updateDisplay();
}

// ================================================
// HIT PROCESSING — TIME-BASED GRADING
// ================================================
function processHit(noteData, timeDiff) {
    noteData.isHit = true;
    const note = noteData.element;

    // Determine hit quality based on TIME difference (ms)
    let quality, points;
    if (timeDiff <= HIT_WINDOWS.PERFECT_PLUS) {
        quality = 'PERFECT+';
        points = 150;
        GameState.perfectPlusHits++;
    } else if (timeDiff <= HIT_WINDOWS.PERFECT) {
        quality = 'PERFECT';
        points = 100;
        GameState.perfectHits++;
    } else if (timeDiff <= HIT_WINDOWS.GREAT) {
        quality = 'GREAT';
        points = 75;
        GameState.greatHits++;
    } else {
        quality = 'GOOD';
        points = 50;
        GameState.goodHits++;
    }

    // Update score and combo
    GameState.combo++;
    GameState.maxCombo = Math.max(GameState.maxCombo, GameState.combo);

    const comboMultiplier = 1 + Math.floor(GameState.combo / 10) * 0.1;
    GameState.score += Math.floor(points * comboMultiplier);

    // Visual feedback
    note.style.setProperty('--note-y', `${noteData.currentY}px`);
    note.classList.add('hit');
    showHitEffect(noteData);
    showHitText(noteData, quality, quality === 'PERFECT+' ? 'perfect-plus' : quality.toLowerCase());

    // Sound feedback removed — let the music play without distracting beeps

    setTimeout(() => note.remove(), 200);

    updateDisplay();

    // Combo milestones
    if (GameState.combo > 0 && GameState.combo % 10 === 0) {
        showComboPopup(GameState.combo);
    }
}

// ================================================
// HOLD NOTE FUNCTIONS
// ================================================
function startHold(noteData, timeDiff) {
    const audioTime = getAudioTimeMs();

    noteData.isHolding = true;
    noteData.holdStartTime = audioTime;
    noteData.element.classList.add('holding');

    // Award initial hit points
    let points;
    if (timeDiff <= HIT_WINDOWS.PERFECT_PLUS) {
        points = 75;
        GameState.perfectPlusHits++;
    } else if (timeDiff <= HIT_WINDOWS.PERFECT) {
        points = 50;
        GameState.perfectHits++;
    } else if (timeDiff <= HIT_WINDOWS.GREAT) {
        points = 35;
        GameState.greatHits++;
    } else {
        points = 25;
        GameState.goodHits++;
    }

    GameState.combo++;
    GameState.maxCombo = Math.max(GameState.maxCombo, GameState.combo);
    GameState.score += Math.floor(points * (1 + GameState.combo * 0.1));

    showHitText(noteData, 'HOLD', 'hold');
    // Sound feedback removed
    updateDisplay();
}

function processHoldComplete(noteData) {
    noteData.isHit = true;
    noteData.element.classList.remove('holding');
    noteData.element.style.setProperty('--note-y', `${noteData.currentY}px`);
    noteData.element.classList.add('hit');

    const bonusPoints = 100;
    GameState.combo++;
    GameState.maxCombo = Math.max(GameState.maxCombo, GameState.combo);
    GameState.score += Math.floor(bonusPoints * (1 + GameState.combo * 0.1));
    GameState.perfectHits++;

    showHitText(noteData, 'PERFECT', 'perfect');
    // Sound feedback removed
    updateDisplay();

    setTimeout(() => noteData.element.remove(), 300);
}

function handleHoldRelease(laneIndex, releaseTime) {
    const holdingNote = GameState.activeNotes.find(n =>
        n.lane === laneIndex && n.isHolding && !n.isHit
    );

    if (holdingNote) {
        const holdElapsed = releaseTime - holdingNote.holdStartTime;
        const holdPercentage = holdElapsed / holdingNote.duration;

        if (holdPercentage >= 0.8) {
            processHoldComplete(holdingNote);
        } else {
            holdingNote.isHit = true;
            holdingNote.element.classList.remove('holding');
            holdingNote.element.style.setProperty('--note-y', `${holdingNote.currentY}px`);
            holdingNote.element.classList.add('miss');

            GameState.combo = 0;
            GameState.goodHits++;

            showHitText(holdingNote, 'EARLY', 'miss');
            updateDisplay();

            setTimeout(() => holdingNote.element.remove(), 300);
        }
    }
}

// ================================================
// MISS HANDLING
// ================================================
function handleMiss(noteData) {
    GameState.combo = 0;
    GameState.missHits++;
    GameState.lives--;

    noteData.element.style.setProperty('--note-y', `${noteData.currentY}px`);
    noteData.element.classList.add('miss');
    showHitText(noteData, 'MISS', 'miss');
    // Sound feedback removed

    setTimeout(() => noteData.element.remove(), 300);

    updateDisplay();
    updateLivesDisplay();

    if (GameState.lives <= 0) {
        endGame(true);
    }
}

// ================================================
// PAUSE / RESUME
// ================================================
function togglePause() {
    if (!GameState.isPlaying) return;

    if (GameState.isPaused) {
        // Resume
        GameState.isPaused = false;
        GameState.totalPauseTime += performance.now() - GameState.pauseStartTime;

        // Resume audio
        if (GameState.htmlAudio) {
            GameState.htmlAudio.play().catch(() => { });
        } else if (GameState.useWebAudio && GameState.audioContext?.state === 'suspended') {
            GameState.audioContext.resume();
        }

        // Hide pause overlay
        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.add('hidden');

        console.log('[Game] Resumed');
    } else {
        // Pause
        GameState.isPaused = true;
        GameState.pauseStartTime = performance.now();

        // Pause audio
        if (GameState.htmlAudio) {
            GameState.htmlAudio.pause();
        } else if (GameState.useWebAudio && GameState.audioContext?.state === 'running') {
            GameState.audioContext.suspend();
        }

        // Show pause overlay
        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.remove('hidden');

        console.log('[Game] Paused');
    }
}

// ================================================
// GAME END
// ================================================
function endGame(isFail) {
    // Capture duration BEFORE stopping audio (getAudioTimeMs needs isAudioPlaying=true)
    GameState.finalDuration = getAudioTimeMs();
    GameState.isPlaying = false;
    stopAudio();

    GameState.activeNotes.forEach(n => n.element?.remove());
    GameState.activeNotes = [];

    submitScore(isFail);
}

function exitGame() {
    if (GameState.isPlaying && !GameState.isPaused) {
        // First tap: pause the game and show pause menu
        togglePause();
        return;
    }

    if (GameState.isPaused) {
        // From pause menu: forfeit — submit score as fail and show results
        GameState.isPaused = false;
        const pauseOverlay = document.getElementById('pause-overlay');
        if (pauseOverlay) pauseOverlay.classList.add('hidden');

        endGame(true); // Forfeit = fail, shows result screen
        return;
    }

    // Not playing (e.g. on result screen) — just go back
    returnToSongSelect();
}

function returnToSongSelect() {
    DOM.lanes.forEach(lane => {
        lane.querySelectorAll('.note').forEach(n => n.remove());
    });

    DOM.resultOverlay.classList.add('hidden');
    DOM.resultOverlay.classList.remove('visible');
    DOM.countdownOverlay.classList.add('hidden');

    // Also hide pause overlay
    const pauseOverlay = document.getElementById('pause-overlay');
    if (pauseOverlay) pauseOverlay.classList.add('hidden');

    DOM.gameContainer.classList.add('hidden');
    DOM.songSelect.classList.remove('hidden');

    // Re-render songs (refreshes highscores too)
    loadSongs();
}

function returnToPet() {
    window.location.href = window.location.origin +
        window.location.pathname.replace(/\/rhythm.*$/, '/pet');
}

// ================================================
// VISUAL EFFECTS — appended to dedicated container
// ================================================
function showHitEffect(noteData) {
    if (!DOM.effectsContainer) return;

    const laneEl = DOM.lanes[noteData.lane];
    if (!laneEl) return;

    const laneRect = laneEl.getBoundingClientRect();
    const effect = document.createElement('div');
    effect.className = 'hit-effect';
    effect.style.left = (laneRect.left + laneRect.width / 2) + 'px';

    // Use hitZoneY relative to viewport
    const gameRect = DOM.gameArea.getBoundingClientRect();
    effect.style.top = (gameRect.top + GameState.hitZoneY) + 'px';

    DOM.effectsContainer.appendChild(effect);
    setTimeout(() => effect.remove(), 400);
}

function showHitText(noteData, text, className) {
    if (!DOM.effectsContainer) return;

    const laneEl = DOM.lanes[noteData.lane];
    if (!laneEl) return;

    const laneRect = laneEl.getBoundingClientRect();
    const textEl = document.createElement('div');
    textEl.className = `hit-text ${className}`;
    textEl.textContent = text;
    textEl.style.left = (laneRect.left + laneRect.width / 2) + 'px';

    const gameRect = DOM.gameArea.getBoundingClientRect();
    textEl.style.top = (gameRect.top + GameState.hitZoneY - 40) + 'px';

    DOM.effectsContainer.appendChild(textEl);
    setTimeout(() => textEl.remove(), 500);
}

function showComboPopup(combo) {
    if (!DOM.effectsContainer) return;

    const popup = document.createElement('div');
    popup.className = 'combo-popup';
    popup.textContent = `${combo} COMBO!`;
    DOM.effectsContainer.appendChild(popup);
    setTimeout(() => popup.remove(), 600);
}

// ================================================
// UI UPDATES
// ================================================
function updateDisplay() {
    if (DOM.scoreDisplay) DOM.scoreDisplay.textContent = Math.floor(GameState.score);
    if (DOM.comboDisplay) DOM.comboDisplay.textContent = GameState.combo;
}

function initLivesDisplay() {
    if (!DOM.livesDisplay) return;

    // Clear and rebuild
    DOM.livesDisplay.innerHTML = '';
    DOM.heartCache = [];

    for (let i = 0; i < GameState.maxLives; i++) {
        const heart = document.createElement('div');
        heart.className = 'life-heart';
        heart.innerHTML = '<i class="fas fa-heart"></i>';
        DOM.livesDisplay.appendChild(heart);
        DOM.heartCache.push(heart);
    }
}

function updateLivesDisplay() {
    for (let i = 0; i < DOM.heartCache.length; i++) {
        if (i < GameState.lives) {
            DOM.heartCache[i].classList.remove('lost');
        } else {
            DOM.heartCache[i].classList.add('lost');
        }
    }
}

// ================================================
// GRADE CALCULATION
// ================================================
function calculateGrade() {
    const totalHits = GameState.perfectPlusHits + GameState.perfectHits +
        GameState.greatHits + GameState.goodHits + GameState.missHits;
    if (totalHits === 0) return 'F';

    // Weights: PERFECT+ and PERFECT = 100%, GREAT = 80%, GOOD = 50%, MISS = 0%
    const accuracy = ((GameState.perfectPlusHits * 100 + GameState.perfectHits * 100 +
        GameState.greatHits * 80 + GameState.goodHits * 50) /
        (totalHits * 100)) * 100;

    if (accuracy >= 95 && GameState.missHits === 0) return 'S';
    if (accuracy >= 90) return 'A';
    if (accuracy >= 80) return 'B';
    if (accuracy >= 70) return 'C';
    if (accuracy >= 60) return 'D';
    return 'F';
}

// ================================================
// SCORE SUBMISSION — with CSRF
// ================================================
async function submitScore(isFail) {
    try {
        const grade = calculateGrade();
        const gameDuration = GameState.finalDuration || 0; // Captured before audio stop

        const payload = {
            song_id: GameState.currentSong.id,
            score: Math.floor(GameState.score),
            max_combo: GameState.maxCombo,
            perfect_plus_hits: GameState.perfectPlusHits,
            perfect_hits: GameState.perfectHits,
            great_hits: GameState.greatHits,
            good_hits: GameState.goodHits,
            miss_hits: GameState.missHits,
            rank: grade,
            is_fail: isFail ? 1 : 0,
            game_duration: Math.floor(gameDuration)
        };

        // Use fetchWithCsrf if available, otherwise fallback
        const fetchFn = typeof fetchWithCsrf === 'function' ? fetchWithCsrf : fetch;

        const response = await fetchFn(`${API_BASE}rhythm/score`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        showResults(data, isFail, grade);

    } catch (error) {
        console.error('Error submitting score:', error);
        showResults({ success: false }, isFail, calculateGrade());
    }
}

function showResults(data, isFail, grade) {
    DOM.resultOverlay.classList.remove('hidden');
    DOM.resultOverlay.classList.add('visible');

    document.getElementById('result-title').textContent = isFail ? '💀 Game Over' : '🎵 Song Complete!';
    document.getElementById('final-score').textContent = Math.floor(GameState.score).toLocaleString();
    document.getElementById('max-combo').textContent = GameState.maxCombo;
    document.getElementById('perfect-hits').textContent =
        (GameState.perfectPlusHits + GameState.perfectHits);

    // Show grade
    const gradeEl = document.getElementById('result-grade');
    if (gradeEl) {
        gradeEl.textContent = grade;
        gradeEl.className = `result-grade grade-${grade.toLowerCase()}`;
    }

    // Show rewards
    if (data.success) {
        const goldEl = document.getElementById('reward-gold');
        const expEl = document.getElementById('reward-exp');
        const moodEl = document.getElementById('reward-mood');
        if (goldEl) goldEl.textContent = `+${data.gold_earned || 0}`;
        if (expEl) expEl.textContent = `+${data.exp_earned || 0}`;
        if (moodEl) moodEl.textContent = `+${data.mood_earned || 0}`;

        // Show daily plays remaining
        if (data.remaining_rewarded_plays !== undefined) {
            const infoEl = document.getElementById('reward-info');
            if (infoEl) {
                if (data.remaining_rewarded_plays <= 0) {
                    infoEl.textContent = '⚠ Daily reward limit reached';
                    infoEl.style.color = '#ff6b6b';
                } else {
                    infoEl.textContent = `${data.remaining_rewarded_plays} rewarded plays left today`;
                    infoEl.style.color = 'rgba(255,255,255,0.4)';
                }
            }
        }
    }
}
