/**
 * Pet Management Module
 * @module pet/pets
 * @description Handles pet loading, display, actions, and utility functions
 * 
 * @author MOE Development Team
 * @version 2.0.0
 */

import { API_BASE, ASSETS_BASE } from './config.js';
import { state } from './state.js';
import { showToast, switchTab } from './ui.js';

// ================================================
// PET LOADING
// ================================================

/**
 * Load all pets owned by the current user
 * @async
 * @fires petsLoaded - Custom event dispatched when pets are loaded
 * @returns {Promise<void>}
 */
export async function loadPets() {
    try {
        const response = await fetch(`${API_BASE}?action=get_pets`);
        const data = await response.json();

        if (data.success) {
            state.userPets = data.pets;
            window.userPets = state.userPets; // Backward compatibility

            updatePetCountBadge();

            // Emit event for collection rendering
            document.dispatchEvent(new CustomEvent('petsLoaded'));

            // Find and display active pet
            state.activePet = state.userPets.find(p => p.is_active);
            window.activePet = state.activePet; // Backward compatibility

            if (state.activePet) {
                renderActivePet();
            }
        }
    } catch (error) {
        console.error('Error loading pets:', error);
        showToast('Failed to load pets', 'error');
    }
}

/**
 * Load the user's currently active pet
 * @async
 * @returns {Promise<void>}
 */
export async function loadActivePet() {
    try {
        const response = await fetch(`${API_BASE}?action=get_active_pet`);
        const data = await response.json();

        if (data.success && data.pet) {
            state.activePet = data.pet;
            window.activePet = state.activePet;
            renderActivePet();

            // Load pet into PixiJS if available
            if (window.PixiPet && window.PixiPet.isReady()) {
                window.PixiPet.load(state.activePet);
            }
        } else {
            showNoPetMessage();
        }
    } catch (error) {
        console.error('Error loading active pet:', error);
    }
}

// ================================================
// PET DISPLAY
// ================================================

/**
 * Render the active pet in the My Pet tab
 * Updates pet image, stats, exp bar, and action buttons
 * @returns {void}
 */
export function renderActivePet() {
    const noPetMsg = document.getElementById('no-pet-message');
    const petZone = document.getElementById('pet-display-zone');
    const infoHeader = document.getElementById('pet-info-header');
    const statsContainer = document.getElementById('stats-container');
    const expCard = document.getElementById('exp-card');
    const actions = document.getElementById('action-buttons');

    if (!state.activePet) {
        showNoPetMessage();
        return;
    }

    noPetMsg.style.display = 'none';
    petZone.style.display = 'block';

    const imgPath = getPetImagePath(state.activePet);
    const shinyStyle = state.activePet.is_shiny ? `filter: hue-rotate(${state.activePet.shiny_hue}deg);` : '';

    const petContainer = document.getElementById('pet-img-container');
    petContainer.innerHTML = `
        <img src="${imgPath}" alt="${state.activePet.species_name}" 
             class="pet-image pet-anim-idle" 
             style="${shinyStyle}; width: 180px; height: 180px; object-fit: contain; filter: drop-shadow(0 8px 25px rgba(0,0,0,0.6));"
             onerror="this.src='../assets/placeholder.png'">
    `;

    const shinySparkles = document.getElementById('shiny-sparkles');
    shinySparkles.style.display = state.activePet.is_shiny ? 'block' : 'none';

    infoHeader.style.display = 'block';

    const displayName = state.activePet.nickname || state.activePet.species_name;
    document.getElementById('pet-name').textContent = displayName;
    document.getElementById('pet-level').textContent = `Lv.${state.activePet.level}`;

    const elementBadge = document.getElementById('pet-element-badge');
    elementBadge.textContent = state.activePet.element;
    elementBadge.className = `element-badge ${state.activePet.element.toLowerCase()}`;

    const rarityBadge = document.getElementById('pet-rarity-badge');
    rarityBadge.textContent = state.activePet.rarity;
    rarityBadge.className = `rarity-badge ${state.activePet.rarity.toLowerCase()}`;

    const shinyTag = document.getElementById('shiny-tag');
    shinyTag.style.display = state.activePet.is_shiny ? 'inline-flex' : 'none';

    statsContainer.style.display = 'grid';

    updateCircularProgress('health', state.activePet.health);
    updateCircularProgress('hunger', state.activePet.hunger);
    updateCircularProgress('mood', state.activePet.mood);

    expCard.style.display = 'block';
    const expNeeded = Math.floor(100 * Math.pow(1.2, state.activePet.level - 1));
    const expPercent = (state.activePet.exp / expNeeded) * 100;
    document.getElementById('exp-bar').style.width = `${expPercent}%`;
    document.getElementById('exp-text').textContent = `${state.activePet.exp} / ${expNeeded}`;

    actions.style.display = 'grid';

    const shelterBtn = document.getElementById('btn-shelter');
    if (shelterBtn) {
        const labelEl = shelterBtn.querySelector('.action-label');
        const iconEl = shelterBtn.querySelector('i');
        if (state.activePet.status === 'SHELTER') {
            if (labelEl) labelEl.textContent = 'Retrieve';
            if (iconEl) iconEl.className = 'fas fa-door-open';
        } else {
            if (labelEl) labelEl.textContent = 'Shelter';
            if (iconEl) iconEl.className = 'fas fa-home';
        }
    }
}

/**
 * Update a circular progress ring element
 * @param {'health'|'hunger'|'mood'} type - The stat type to update
 * @param {number} value - The current value (0-100)
 * @returns {void}
 */
export function updateCircularProgress(type, value) {
    const ring = document.getElementById(`${type}-ring`);
    const valueEl = document.getElementById(`${type}-value`);

    if (!ring || !valueEl) return;

    valueEl.textContent = Math.round(value);
    const circumference = 220;
    const offset = circumference - (value / 100) * circumference;
    ring.style.strokeDashoffset = offset;
}

/**
 * Show message when user has no active pet
 * @returns {void}
 */
export function showNoPetMessage() {
    const stage = document.getElementById('pet-stage');
    stage.innerHTML = `
        <div class="no-pet-message">
            <i class="fas fa-egg fa-3x"></i>
            <p>No active pet!</p>
            <button class="action-btn primary" onclick="switchTab('gacha')">
                Get Your First Pet
            </button>
        </div>
    `;

    document.getElementById('pet-info').style.display = 'none';
    document.getElementById('action-buttons').style.display = 'none';
}

// ================================================
// PET ACTIONS
// ================================================

/**
 * Select a pet to make it active
 * @async
 * @param {number} petId - The ID of the pet to select
 * @returns {Promise<void>}
 */
export async function selectPet(petId) {
    const pet = state.userPets.find(p => p.id === petId);
    if (!pet) return;

    if (pet.status === 'DEAD') {
        showToast('This pet is dead! Use a revival item first.', 'warning');
        return;
    }

    if (pet.status === 'SHELTER') {
        // Use custom confirm modal (falls back to native if not available)
        if (typeof showConfirm === 'function') {
            showConfirm(
                `Are you sure you want to retrieve ${pet.nickname || pet.species_name} from the shelter?`,
                'Retrieve from Shelter',
                'fa-box-open'
            ).then(confirmed => {
                if (confirmed) {
                    toggleShelter(petId);
                }
            });
        } else if (confirm(`Retrieve ${pet.nickname || pet.species_name} from shelter?`)) {
            toggleShelter(petId);
        }
        return;
    }

    try {
        const response = await fetch(`${API_BASE}?action=set_active`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pet_id: petId })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Pet is now active!', 'success');
            await loadPets();
            switchTab('my-pet');
        } else {
            showToast(data.error || 'Failed to set active pet', 'error');
        }
    } catch (error) {
        console.error('Error setting active pet:', error);
        showToast('Network error', 'error');
    }
}

/**
 * Initialize action button event listeners
 * @returns {void}
 */
export function initActionButtons() {
    document.getElementById('btn-feed')?.addEventListener('click', () => {
        document.dispatchEvent(new CustomEvent('openItemModal', { detail: { type: 'food' } }));
    });
    document.getElementById('btn-heal')?.addEventListener('click', () => {
        document.dispatchEvent(new CustomEvent('openItemModal', { detail: { type: 'potion' } }));
    });
    document.getElementById('btn-play')?.addEventListener('click', playWithPet);
    document.getElementById('btn-shelter')?.addEventListener('click', () => toggleShelter());
}

/**
 * Play with the active pet (triggers mini-game)
 * @async
 * @returns {Promise<void>}
 */
export async function playWithPet() {
    if (!state.activePet) return;

    if (state.activePet.status === 'DEAD') {
        showToast('This pet is dead! Use a revival item first.', 'warning');
        return;
    }

    // Get pet image path
    const petImg = getPetImagePath(state.activePet);

    // Redirect to rhythm game page directly
    window.location.href = `rhythm_game.php?pet_id=${state.activePet.id}&pet_img=${encodeURIComponent(petImg)}`;
}

/**
 * Toggle pet shelter status (protect from stat decay)
 * @async
 * @param {number|null} [targetPetId=null] - Pet ID to toggle, or null for active pet
 * @returns {Promise<void>}
 */
export async function toggleShelter(targetPetId = null) {
    const petId = targetPetId || (state.activePet ? state.activePet.id : null);
    if (!petId) return;

    try {
        const response = await fetch(`${API_BASE}?action=shelter`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pet_id: petId })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            loadActivePet();
            loadPets();
        } else {
            showToast(data.error || 'Failed to toggle shelter', 'error');
        }
    } catch (error) {
        console.error('Error toggling shelter:', error);
    }
}

// ================================================
// UTILITY FUNCTIONS
// ================================================

/**
 * Get the image path for a pet based on its evolution stage
 * @param {Object} pet - The pet object
 * @param {string} pet.evolution_stage - Current evolution stage ('egg', 'baby', 'adult')
 * @param {string} [pet.img_egg] - Egg stage image path
 * @param {string} [pet.img_baby] - Baby stage image path
 * @param {string} [pet.img_adult] - Adult stage image path
 * @returns {string} Full image URL path
 */
export function getPetImagePath(pet) {
    const stage = pet.evolution_stage || 'egg';

    let imgKeys;
    switch (stage) {
        case 'egg':
            imgKeys = ['img_egg', 'img_baby', 'img_adult'];
            break;
        case 'baby':
            imgKeys = ['img_baby', 'img_egg', 'img_adult'];
            break;
        case 'adult':
            imgKeys = ['img_adult', 'img_baby', 'img_egg'];
            break;
        default:
            imgKeys = ['img_egg', 'img_baby', 'img_adult'];
    }

    for (const key of imgKeys) {
        if (pet[key] && pet[key] !== '' && pet[key] !== null) {
            return ASSETS_BASE + pet[key];
        }
    }

    return ASSETS_BASE + (pet.current_image || 'default/egg.png');
}

/**
 * Get the evolution stage of a pet
 * @param {Object|*} pet - The pet object or value
 * @returns {'egg'|'baby'|'adult'} The evolution stage
 */
export function getEvolutionStage(pet) {
    if (typeof pet === 'object' && pet !== null) {
        return pet.evolution_stage || 'egg';
    }
    return 'egg';
}

/**
 * Update the pet count badge display
 * @returns {void}
 */
export function updatePetCountBadge() {
    const petCountBadge = document.getElementById('pet-count-badge');
    if (petCountBadge) {
        const petCount = state.userPets.length;
        petCountBadge.textContent = `${petCount} / 25`;

        if (petCount >= 25) {
            petCountBadge.style.background = 'linear-gradient(135deg, #E74C3C, #C0392B)';
        } else if (petCount >= 20) {
            petCountBadge.style.background = 'linear-gradient(135deg, #F39C12, #E67E22)';
        } else {
            petCountBadge.style.background = 'linear-gradient(135deg, var(--gold), var(--gold-dark))';
        }
    }
}
