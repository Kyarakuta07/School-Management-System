// ================================================
// HARDCORE & RHYTHM GAME UPDATE - NEW FUNCTIONS
// ================================================

// ================================================
// RHYTHM GAME ENGINE
// ================================================

let rhythmGame = null;

class RhythmGame {
    constructor(petId, petImg) {
        this.petId = petId;
        this.petImg = petImg;
        this.score = 0;
        this.timeLeft = 30;
        this.notes = [];
        this.noteIdCounter = 0;
        this.isPlaying = false;
        this.timerInterval = null;
        this.spawnInterval = null;
    }

    start() {
        this.isPlaying = true;
        this.score = 0;
        this.timeLeft = 30;

        // Set pet image
        document.getElementById('rhythm-pet-img').src = this.petImg;

        // Show modal
        document.getElementById('rhythm-modal').classList.add('show');

        // Start timer countdown
        this.timerInterval = setInterval(() => {
            this.timeLeft--;
            document.getElementById('rhythm-timer').textContent = `${this.timeLeft}s`;

            if (this.timeLeft <= 0) {
                this.end();
            }
        }, 1000);

        // Spawn notes every 0.6 seconds
        this.spawnInterval = setInterval(() => {
            if (this.isPlaying) {
                this.spawnNote();
            }
        }, 600);

        // Update score display
        this.updateScoreDisplay();
    }

    spawnNote() {
        const container = document.getElementById('rhythm-notes-container');
        const note = document.createElement('div');
        note.className = 'rhythm-note';
        note.dataset.noteId = this.noteIdCounter++;

        // Random horizontal position (4 lanes)
        const lane = Math.floor(Math.random() * 4);
        const laneWidth = 25; // 100% / 4 lanes
        note.style.left = `${lane * laneWidth + 10}%`;

        // Random note color/type
        const colors = ['#ff6b35', '#4ecdc4', '#ffd93d', '#a8dadc'];
        note.style.background = colors[lane];
        note.innerHTML = '♪';

        container.appendChild(note);
        this.notes.push(note);

        // Animate fall (CSS handles this)
        setTimeout(() => {
            note.classList.add('falling');
        }, 10);

        // Remove after falling (2.5s animation + buffer)
        setTimeout(() => {
            if (note.parentNode) {
                note.remove();
                const index = this.notes.indexOf(note);
                if (index > -1) this.notes.splice(index, 1);
            }
        }, 3000);
    }

    checkHit(clickY) {
        // Hit zone is at bottom 20% of game area
        const gameArea = document.getElementById('rhythm-game-area');
        const hitZoneY = gameArea.offsetHeight * 0.75;
        const hitTolerance = 60;

        if (clickY >= hitZoneY - hitTolerance && clickY <= hitZoneY + hitTolerance) {
            // Find closest note to hit zone
            let closestNote = null;
            let closestDist = Infinity;

            this.notes.forEach(note => {
                const noteRect = note.getBoundingClientRect();
                const noteY = noteRect.top + noteRect.height / 2;
                const dist = Math.abs(noteY - hitZoneY);

                if (dist < closestDist && dist < hitTolerance) {
                    closestDist = dist;
                    closestNote = note;
                }
            });

            if (closestNote) {
                // HIT!
                this.score += 10;
                closestNote.classList.add('hit');
                closestNote.remove();
                const index = this.notes.indexOf(closestNote);
                if (index > -1) this.notes.splice(index, 1);

                this.updateScoreDisplay();
                return true;
            }
        }
        return false;
    }

    updateScoreDisplay() {
        document.getElementById('rhythm-score-display').textContent = this.score;
    }

    end() {
        this.isPlaying = false;
        clearInterval(this.timerInterval);
        clearInterval(this.spawnInterval);

        // Clear remaining notes
        document.getElementById('rhythm-notes-container').innerHTML = '';

        // Send score to backend
        this.submitScore();
    }

    async submitScore() {
        try {
            const response = await fetch(`${API_BASE}?action=play_finish`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pet_id: this.petId,
                    score: this.score
                })
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById('rhythm-modal').classList.remove('show');

                let message = `Game Over! Score: ${this.score}\\n`;
                message += `Rewards: +${data.rewards.mood} Mood, +${data.rewards.exp} EXP`;

                if (data.level_up) {
                    message += '\\n🎉 Level Up!';
                }
                if (data.evolved) {
                    message += '\\n✨ Your pet evolved!';
                }

                showToast(message, 'success');
                loadActivePet();
            } else {
                showToast(data.error || 'Failed to save score', 'error');
            }
        } catch (error) {
            console.error('Error submitting score:', error);
            showToast('Network error', 'error');
        }
    }
}

// Open rhythm game
function openRhythmGame() {
    if (!activePet) {
        showToast('No active pet!', 'warning');
        return;
    }

    if (activePet.status === 'DEAD') {
        showToast('Cannot play with a dead pet!', 'error');
        return;
    }

    const petImg = getPetImagePath(activePet);
    rhythmGame = new RhythmGame(activePet.id, petImg);
    rhythmGame.start();
}

function closeRhythmGame() {
    if (rhythmGame && rhythmGame.isPlaying) {
        rhythmGame.end();
    }
    document.getElementById('rhythm-modal').classList.remove('show');
}

// Add click/tap handler for rhythm game
document.addEventListener('DOMContentLoaded', () => {
    const gameArea = document.getElementById('rhythm-game-area');
    if (gameArea) {
        gameArea.addEventListener('click', (e) => {
            if (rhythmGame && rhythmGame.isPlaying) {
                const rect = gameArea.getBoundingClientRect();
                const clickY = e.clientY - rect.top;
                const hit = rhythmGame.checkHit(clickY);

                if (hit) {
                    // Visual feedback
                    const ripple = document.createElement('div');
                    ripple.className = 'hit-ripple';
                    ripple.style.left = e.clientX - rect.left + 'px';
                    ripple.style.top = clickY + 'px';
                    gameArea.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 500);
                }
            }
        });
    }
});

// ================================================
// MANUAL EVOLUTION SYSTEM
// ================================================

let evolutionState = {
    mainPetId: null,
    selectedFodder: [],
    candidates: []
};

async function openEvolutionModal(mainPetId) {
    // Find the main pet in collection
    const mainPet = userPets.find(p => p.id === mainPetId);

    if (!mainPet) {
        showToast('Pet not found', 'error');
        return;
    }

    // Check level requirement
    if (mainPet.level < 20) {
        showToast('Pet must be level 20 or higher to evolve!', 'warning');
        return;
    }

    evolutionState.mainPetId = mainPetId;
    evolutionState.selectedFodder = [];

    // Show modal
    document.getElementById('evolution-modal').classList.add('show');

    // Load candidates
    try {
        const response = await fetch(`${API_BASE}?action=get_evolution_candidates&main_pet_id=${mainPetId}`);
        const data = await response.json();

        if (data.success) {
            evolutionState.candidates = data.candidates;
            document.getElementById('evo-required-rarity').textContent = data.required_rarity;
            renderFodderGrid(data.candidates);
        } else {
            showToast(data.error || 'Failed to load candidates', 'error');
            closeEvolutionModal();
        }
    } catch (error) {
        console.error('Error loading evolution candidates:', error);
        showToast('Network error', 'error');
        closeEvolutionModal();
    }
}

function renderFodderGrid(candidates) {
    const grid = document.getElementById('fodder-grid');

    if (candidates.length === 0) {
        grid.innerHTML = `
            <div class="empty-message">
                <p>No eligible fodder pets found!</p>
                <p style="font-size: 0.85rem; color: #888;">You need 3 pets of the same rarity that are not active.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = candidates.map(pet => {
        const img = getPetImagePath(pet);
        const displayName = pet.nickname || pet.species_name;

        return `
            <div class="fodder-card" data-pet-id="${pet.id}">
                <input type="checkbox" 
                       id="fodder-${pet.id}" 
                       class="fodder-checkbox" 
                       onchange="toggleFodderSelection(${pet.id})">
                <label for="fodder-${pet.id}" class="fodder-label">
                    <img src="${img}" alt="${displayName}" class="fodder-img">
                    <div class="fodder-info">
                        <span class="fodder-name">${displayName}</span>
                        <span class="fodder-level">Lv.${pet.level}</span>
                    </div>
                </label>
            </div>
        `;
    }).join('');
}

function toggleFodderSelection(petId) {
    const checkbox = document.getElementById(`fodder-${petId}`);

    if (checkbox.checked) {
        if (evolutionState.selectedFodder.length >= 3) {
            // Uncheck if trying to select more than 3
            checkbox.checked = false;
            showToast('You can only select 3 pets!', 'warning');
            return;
        }
        evolutionState.selectedFodder.push(petId);
    } else {
        const index = evolutionState.selectedFodder.indexOf(petId);
        if (index > -1) {
            evolutionState.selectedFodder.splice(index, 1);
        }
    }

    // Update counter
    document.getElementById('evo-selected-count').textContent = evolutionState.selectedFodder.length;

    // Enable/disable evolution button
    const confirmBtn = document.getElementById('confirm-evolution-btn');
    confirmBtn.disabled = evolutionState.selectedFodder.length !== 3;
}

async function confirmEvolution() {
    if (evolutionState.selectedFodder.length !== 3) {
        showToast('Please select exactly 3 fodder pets', 'warning');
        return;
    }

    if (!confirm('⚠️ This will permanently sacrifice the 3 selected pets and cost 500 Gold. Continue?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}?action=evolve_manual`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                main_pet_id: evolutionState.mainPetId,
                fodder_ids: evolutionState.selectedFodder
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            updateGoldDisplay(data.remaining_gold);
            closeEvolutionModal();
            loadPets(); // Refresh collection
            loadActivePet(); // Refresh active pet if it was evolved
        } else {
            showToast(data.error || 'Evolution failed', 'error');
        }
    } catch (error) {
        console.error('Error during evolution:', error);
        showToast('Network error', 'error');
    }
}

function closeEvolutionModal() {
    document.getElementById('evolution-modal').classList.remove('show');
    evolutionState = {
        mainPetId: null,
        selectedFodder: [],
        candidates: []
    };
}

// ================================================
// PET ECONOMY: SELL & RENAME
// ================================================

async function sellPet(petId) {
    const pet = userPets.find(p => p.id === petId);
    if (!pet) return;

    const displayName = pet.nickname || pet.species_name;

    if (!confirm(`Sell ${displayName}? This cannot be undone!`)) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}?action=sell_pet`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pet_id: petId })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            updateGoldDisplay(data.remaining_gold);
            loadPets(); // Refresh collection
        } else {
            showToast(data.error || 'Failed to sell pet', 'error');
        }
    } catch (error) {
        console.error('Error selling pet:', error);
        showToast('Network error', 'error');
    }
}

let renamingPetId = null;

function openRenameModal() {
    if (!activePet) return;

    renamingPetId = activePet.id;
    const currentName = activePet.nickname || activePet.species_name;

    document.getElementById('rename-input').value = activePet.nickname || '';
    document.getElementById('rename-input').placeholder = activePet.species_name;
    document.getElementById('rename-modal').classList.add('show');

    // Focus input
    setTimeout(() => document.getElementById('rename-input').focus(), 100);
}

function closeRenameModal() {
    document.getElementById('rename-modal').classList.remove('show');
    renamingPetId = null;
}

async function confirmRename() {
    const newNickname = document.getElementById('rename-input').value.trim();

    if (!renamingPetId) return;

    try {
        const response = await fetch(`${API_BASE}?action=rename`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pet_id: renamingPetId,
                nickname: newNickname
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Pet renamed successfully!', 'success');
            closeRenameModal();
            loadActivePet();
            loadPets();
        } else {
            showToast(data.error || 'Failed to rename', 'error');
        }
    } catch (error) {
        console.error('Error renaming pet:', error);
        showToast('Network error', 'error');
    }
}

// ================================================
// UPDATE COLLECTION RENDERING (ADD SELL & EVOLVE BUTTONS)
// ================================================

// Override renderCollection to add new buttons
const originalRenderCollection = renderCollection;
renderCollection = function () {
    const grid = document.getElementById('collection-grid');

    if (userPets.length === 0) {
        grid.innerHTML = `
            <div class="empty-message">
                <p>No pets yet! Visit the Gacha tab to get your first companion.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = userPets.map(pet => {
        const imgPath = getPetImagePath(pet);
        const displayName = pet.nickname || pet.species_name;
        const activeClass = pet.is_active ? 'active' : '';
        const deadClass = pet.status === 'DEAD' ? 'dead' : '';
        const shinyStyle = pet.is_shiny ? `filter: hue-rotate(${pet.shiny_hue}deg);` : '';

        // Action buttons based on status
        let actionButtons = '';

        if (pet.status === 'SHELTER') {
            actionButtons = `
                <button class="pet-action-btn btn-retrieve" onclick="event.stopPropagation(); toggleShelter(${pet.id})">
                    <i class="fas fa-box-open"></i> Retrieve
                </button>
            `;
        } else if (pet.status === 'ALIVE' && !pet.is_active) {
            actionButtons = `
                <div class="pet-action-row">
                    <button class="pet-action-btn btn-sell" onclick="event.stopPropagation(); sellPet(${pet.id})" title="Sell Pet">
                        <i class="fas fa-coins"></i>
                    </button>
                    ${pet.level >= 20 ? `
                        <button class="pet-action-btn btn-evolve" onclick="event.stopPropagation(); openEvolutionModal(${pet.id})" title="Manual Evolution">
                            <i class="fas fa-star"></i>
                        </button>
                    ` : ''}
                </div>
            `;
        }

        return `
            <div class="pet-card ${activeClass} ${deadClass}" onclick="selectPet(${pet.id})">
                <span class="rarity-badge ${pet.rarity.toLowerCase()}">${pet.rarity.charAt(0)}</span>
                <img src="${imgPath}" alt="${pet.species_name}" class="pet-card-img" 
                     style="${shinyStyle}"
                     onerror="this.src='../assets/placeholder.png'">
                <h3 class="pet-card-name">${displayName}</h3>
                <span class="pet-card-level">Lv.${pet.level} ${pet.is_shiny ? '✨' : ''}</span>
                ${actionButtons}
            </div>
        `;
    }).join('');
};

// ================================================
// UPDATE playWithPet TO USE RHYTHM GAME
// ================================================

playWithPet = function () {
    openRhythmGame();
};

// ================================================
// HELP / TUTORIAL SYSTEM
// ================================================

function openHelpModal() {
    document.getElementById('help-modal').classList.add('show');
    // Reset to overview tab
    switchHelpTab('overview');
}

function closeHelpModal() {
    document.getElementById('help-modal').classList.remove('show');
}

function switchHelpTab(tabName) {
    // Hide all content
    document.querySelectorAll('.help-content').forEach(content => {
        content.style.display = 'none';
    });

    // Remove active class from all tabs
    document.querySelectorAll('.help-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Show selected content
    document.getElementById('help-' + tabName).style.display = 'block';

    // Add active class to clicked tab
    event.target.classList.add('active');
}
