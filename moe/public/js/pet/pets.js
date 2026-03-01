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
        const response = await fetchWithCsrf(`${API_BASE}pets?t=${Date.now()}`);
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
                // Load pet into PixiJS if available
                if (window.PixiPet) {
                    window.PixiPet.load(state.activePet);
                }
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
        const response = await fetchWithCsrf(`${API_BASE}pets/active?t=${Date.now()}`);
        const data = await response.json();

        if (data.success && data.pet) {
            state.activePet = data.pet;
            window.activePet = state.activePet;
            renderActivePet();

            // Load pet into PixiJS if available
            if (window.PixiPet) {
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
    const pet = state.activePet;
    const container = document.getElementById('my-pet');
    if (!pet || !container) return;

    // Guard: Prevent full innerHTML wipe if pet hasn't changed and Pixi is running
    // This stops the "flicker" on refresh
    const currentId = container.dataset.petId;
    const isPixiRunning = window.PixiPet && window.PixiPet.isReady();

    if (currentId === String(pet.id) && isPixiRunning) {
        // Just update data/stats without wiping UI
        updateActivePetStatsUI(pet);
        return;
    }

    container.dataset.petId = pet.id;
    const noPetMsg = document.getElementById('no-pet-message');
    const petZone = document.getElementById('pet-display-zone');
    const infoHeader = document.getElementById('pet-info-header');
    const statsContainer = document.getElementById('stats-container');
    const expCard = document.getElementById('exp-card');
    const actions = document.getElementById('action-buttons');
    const reviveCta = document.getElementById('revive-cta-container');

    // Check if required elements exist (they may not exist on collection/other tabs)
    if (!noPetMsg || !petZone || !infoHeader || !statsContainer || !expCard || !actions) {
        return;
    }

    if (!state.activePet) {
        showNoPetMessage();
        return;
    }

    // Hide No Pet Message, Show Pet Content
    noPetMsg.style.display = 'none';
    petZone.style.display = 'block';
    infoHeader.style.display = 'block';
    statsContainer.style.display = 'flex';
    expCard.style.display = 'block';
    actions.style.display = 'grid';
    if (reviveCta) reviveCta.style.display = 'none';

    const imgPath = getPetImagePath(state.activePet);
    const isShiny = state.activePet.is_shiny == 1 || state.activePet.is_shiny === true;
    const shinyStyle = isShiny ? `filter: hue-rotate(${state.activePet.shiny_hue}deg);` : '';

    const petContainer = document.getElementById('pet-img-container');
    petContainer.innerHTML = `
        <img src="${imgPath}" alt="${state.activePet.species_name}" 
             class="pet-image pet-anim-idle" 
             style="${shinyStyle}; width: 180px; height: 180px; object-fit: contain; filter: drop-shadow(0 8px 25px rgba(0,0,0,0.6));"
             onerror="this.onerror=null; this.src='${(typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../')}assets/placeholder.png';">
    `;

    const shinySparkles = document.getElementById('shiny-sparkles');
    shinySparkles.style.display = isShiny ? 'block' : 'none';

    infoHeader.style.display = 'block';

    const displayName = state.activePet.nickname || state.activePet.species_name;
    document.getElementById('pet-name').textContent = displayName;
    document.getElementById('pet-level').textContent = `Lv.${state.activePet.level}`;

    const elementBadge = document.getElementById('pet-element-badge');
    const assetBase = (typeof window.ASSET_BASE !== 'undefined') ? window.ASSET_BASE : '/';
    elementBadge.innerHTML = `
        <img src="${assetBase}assets/elements/${state.activePet.element.toLowerCase()}.png" 
             class="element-badge-img" alt="${state.activePet.element}">
        <span>${state.activePet.element}</span>
    `;
    elementBadge.className = `element-badge ${state.activePet.element.toLowerCase()}`;

    const rarityBadge = document.getElementById('pet-rarity-badge');
    rarityBadge.textContent = state.activePet.rarity;
    rarityBadge.className = `rarity-badge ${state.activePet.rarity.toLowerCase()}`;

    const shinyTag = document.getElementById('shiny-tag');
    shinyTag.style.display = isShiny ? 'inline-flex' : 'none';

    statsContainer.style.display = '';

    const hpCurrent = state.activePet.hp ?? state.activePet.health ?? 100;
    const hpMax = state.activePet.health > 0 ? state.activePet.health : 100;
    const hpPct = Math.min(100, (hpCurrent / hpMax) * 100);
    updateCircularProgress('health', hpPct, hpCurrent);
    updateCircularProgress('hunger', state.activePet.hunger);
    updateCircularProgress('mood', state.activePet.mood);

    expCard.style.display = '';
    const expNeeded = Math.floor(100 * Math.pow(1.2, state.activePet.level - 1));
    const expPercent = (state.activePet.exp / expNeeded) * 100;
    document.getElementById('exp-bar').style.width = `${expPercent}%`;
    document.getElementById('exp-text').textContent = `${state.activePet.exp} / ${expNeeded}`;

    if (state.activePet.status === 'DEAD') {
        actions.style.display = 'none';
        if (reviveCta) reviveCta.style.display = '';
    } else {
        actions.style.display = '';
        if (reviveCta) reviveCta.style.display = 'none';

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

        // Disable feed/heal buttons if no items available
        const hasFood = state.userInventory.some(i => i.effect_type === 'food' && i.quantity > 0);
        const hasPotion = state.userInventory.some(i => i.effect_type === 'potion' && i.quantity > 0);

        const btnFeed = document.getElementById('btn-feed');
        if (btnFeed) {
            btnFeed.style.opacity = hasFood ? '1' : '0.5';
            btnFeed.classList.toggle('disabled', !hasFood);
        }

        const btnHeal = document.getElementById('btn-heal');
        if (btnHeal) {
            btnHeal.style.opacity = hasPotion ? '1' : '0.5';
            btnHeal.classList.toggle('disabled', !hasPotion);
        }
    }
}

/**
 * Update only the statistical/data elements of the pet UI
 * Does not re-render the image or layout
 * @param {Object} pet - Active pet data
 */
export function updateActivePetStatsUI(pet) {
    if (!pet) return;

    const displayName = pet.nickname || pet.species_name;
    const petNameEl = document.getElementById('pet-name');
    const petLvEl = document.getElementById('pet-level');

    if (petNameEl) petNameEl.textContent = displayName;
    if (petLvEl) petLvEl.textContent = `Lv.${pet.level}`;

    const hpCurrent = pet.hp ?? pet.health ?? 100;
    const hpMax = pet.health > 0 ? pet.health : 100;
    const hpPct = Math.min(100, (hpCurrent / hpMax) * 100);
    updateCircularProgress('health', hpPct, hpCurrent);
    updateCircularProgress('hunger', pet.hunger);
    updateCircularProgress('mood', pet.mood);

    const expBar = document.getElementById('exp-bar');
    const expText = document.getElementById('exp-text');
    if (expBar && expText) {
        const expNeeded = Math.floor(100 * Math.pow(1.2, pet.level - 1));
        const expPercent = (pet.exp / expNeeded) * 100;
        expBar.style.width = `${expPercent}%`;
        expText.textContent = `${pet.exp} / ${expNeeded}`;
    }

    // Update Action Buttons State (Inventory check)
    if (state.userInventory) {
        const hasFood = state.userInventory.some(i => i.effect_type === 'food' && i.quantity > 0);
        const hasPotion = state.userInventory.some(i => i.effect_type === 'potion' && i.quantity > 0);

        const btnFeed = document.getElementById('btn-feed');
        if (btnFeed) {
            btnFeed.style.opacity = hasFood ? '1' : '0.5';
            btnFeed.classList.toggle('disabled', !hasFood);
        }

        const btnHeal = document.getElementById('btn-heal');
        if (btnHeal) {
            btnHeal.style.opacity = hasPotion ? '1' : '0.5';
            btnHeal.classList.toggle('disabled', !hasPotion);
        }
    }
}

/**
 * Update a circular progress ring element
 * @param {'health'|'hunger'|'mood'} type - The stat type to update
 * @param {number} value - The current value (0-100)
 * @returns {void}
 */
export function updateCircularProgress(type, value, displayValue = null) {
    const ring = document.getElementById(`${type}-ring`);
    const valueEl = document.getElementById(`${type}-value`);

    if (!ring || !valueEl) return;

    // displayValue: show actual number (e.g. current HP); value: controls the ring arc (0-100%)
    valueEl.textContent = displayValue !== null ? Math.round(displayValue) : Math.round(value);
    const circumference = 220;
    const offset = circumference - (value / 100) * circumference;
    ring.style.strokeDashoffset = offset;
}

/**
 * Show message when user has no active pet
 * @returns {void}
 */
export function showNoPetMessage() {
    const noPetMsg = document.getElementById('no-pet-message');
    const petZone = document.getElementById('pet-display-zone');
    const infoHeader = document.getElementById('pet-info-header');
    const statsContainer = document.getElementById('stats-container');
    const expCard = document.getElementById('exp-card');
    const actions = document.getElementById('action-buttons');
    const reviveCta = document.getElementById('revive-cta-container');

    // Show message
    if (noPetMsg) noPetMsg.style.display = 'flex';

    // Hide everything else
    if (petZone) petZone.style.display = 'none';
    if (infoHeader) infoHeader.style.display = 'none';
    if (statsContainer) statsContainer.style.display = 'none';
    if (expCard) expCard.style.display = 'none';
    if (actions) actions.style.display = 'none';
    if (reviveCta) reviveCta.style.display = 'none';
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
    const pet = state.userPets.find(p => p.id == petId);
    if (!pet) return;

    if (pet.status === 'DEAD') {
        showToast('This pet is dead! Use a revival item first.', 'warning');
        return;
    }

    if (pet.status === 'SHELTER') {
        // Use custom confirm modal (falls back to native if not available)
        if (typeof showConfirm === 'function') {
            showConfirm(
                `Are you sure you want to retrieve ${pet.nickname || pet.species_name} from the shelter ? `,
                'Retrieve from Shelter',
                'fa-box-open'
            ).then(confirmed => {
                if (confirmed) {
                    toggleShelter(petId);
                }
            });
        } else if (confirm(`Retrieve ${pet.nickname || pet.species_name} from shelter ? `)) {
            toggleShelter(petId);
        }
        return;
    }

    try {
        const response = await fetchWithCsrf(`${API_BASE}pets/activate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pet_id: petId })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Pet is now active!', 'success');
            // Redirect to my-pet tab with page refresh for consistent behavior
            window.location.href = (typeof ASSET_BASE !== 'undefined' ? ASSET_BASE : '/') + 'pet?tab=my-pet';
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
    document.getElementById('btn-feed')?.addEventListener('click', (e) => {
        if (e.currentTarget.classList.contains('disabled')) return showToast('No food available!', 'warning');
        document.dispatchEvent(new CustomEvent('openItemModal', { detail: { type: 'food' } }));
    });
    document.getElementById('btn-heal')?.addEventListener('click', (e) => {
        if (e.currentTarget.classList.contains('disabled')) return showToast('No healing items available!', 'warning');
        document.dispatchEvent(new CustomEvent('openItemModal', { detail: { type: 'potion' } }));
    });
    document.getElementById('btn-play')?.addEventListener('click', playWithPet);
    document.getElementById('btn-shelter')?.addEventListener('click', () => toggleShelter());

    // Re-render pet layout when inventory is fully loaded to bind states
    document.addEventListener('inventoryLoaded', () => {
        if (state.activePet) renderActivePet();
    });
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
    window.location.href = `rhythm?pet_id=${state.activePet.id}&pet_img=${encodeURIComponent(petImg)}`;
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

    // Determine action based on current status
    let pet = state.activePet;
    if (targetPetId && targetPetId != state.activePet?.id) {
        pet = state.userPets.find(p => p.id == targetPetId);
    }

    if (!pet) return;

    const action = (pet.status === 'SHELTER') ? 'retrieve' : 'shelter';

    try {
        const response = await fetchWithCsrf(`${API_BASE}pets/shelter`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pet_id: petId, action: action })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');

            // If pet was retrieved from shelter, redirect to my-pet tab with page refresh
            // This is the most reliable way to ensure fresh data is loaded
            if (data.new_status === 'ALIVE') {
                window.location.href = (typeof ASSET_BASE !== 'undefined' ? ASSET_BASE : '/') + 'pet?tab=my-pet';
                return; // Stop execution, page will reload
            }

            // For sheltering (not retrieve), just reload data normally
            await loadPets();
            await loadActivePet();
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

    return (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../') + 'assets/placeholder.png';
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
