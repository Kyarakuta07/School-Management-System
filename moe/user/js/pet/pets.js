/**
 * Pet Management Module
 * MOE Pet System
 * 
 * Handles pet loading, display, and actions
 */

import { API_BASE, ASSETS_BASE } from './config.js';
import { state } from './state.js';
import { showToast, switchTab } from './ui.js';

// ================================================
// PET LOADING
// ================================================

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

export function updateCircularProgress(type, value) {
    const ring = document.getElementById(`${type}-ring`);
    const valueEl = document.getElementById(`${type}-value`);

    if (!ring || !valueEl) return;

    valueEl.textContent = Math.round(value);
    const circumference = 220;
    const offset = circumference - (value / 100) * circumference;
    ring.style.strokeDashoffset = offset;
}

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

export async function selectPet(petId) {
    const pet = state.userPets.find(p => p.id === petId);
    if (!pet) return;

    if (pet.status === 'DEAD') {
        showToast('This pet is dead! Use a revival item first.', 'warning');
        return;
    }

    if (pet.status === 'SHELTER') {
        if (confirm(`Retrieve ${pet.nickname || pet.species_name} from shelter?`)) {
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

export async function playWithPet() {
    if (!state.activePet) return;

    if (state.activePet.status === 'DEAD') {
        showToast('This pet is dead! Use a revival item first.', 'warning');
        return;
    }

    if (window.PetAnimations) {
        window.PetAnimations.jump();
        window.PetAnimations.hearts(3);
    }

    showToast('You played with ' + (state.activePet.nickname || state.activePet.species_name) + '! 🎵', 'success');

    setTimeout(() => {
        const rhythmModal = document.getElementById('rhythm-modal');
        if (rhythmModal) {
            rhythmModal.classList.add('show');
            if (typeof startRhythmGame === 'function') {
                startRhythmGame();
            }
        }
    }, 500);
}

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

export function getEvolutionStage(pet) {
    if (typeof pet === 'object' && pet !== null) {
        return pet.evolution_stage || 'egg';
    }
    return 'egg';
}

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
