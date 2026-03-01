/**
 * Collection Rendering Module
 * MOE Pet System
 * 
 * Handles the collection tab display
 */

import { state } from './state.js';
import { getPetImagePath, updatePetCountBadge } from './pets.js';

export function renderCollection() {

    const grid = document.getElementById('collection-grid');

    updatePetCountBadge();

    // Update stats panel if available
    if (typeof updateCollectionStats === 'function') {
        updateCollectionStats();
    }

    if (!grid || !state.userPets) { // Changed window.userPets to state.userPets
        console.warn('Collection grid or userPets not available');
        return;
    }

    // Get filtered pets (supports search/filter/sort)
    let displayPets = state.userPets || [];
    try {
        if (typeof window.getFilteredPets === 'function') {
            displayPets = window.getFilteredPets();
        }
    } catch (err) {
        console.error('Error filtering pets:', err);
        displayPets = state.userPets || [];
    }

    if (displayPets.length === 0) {
        grid.innerHTML = `
            <div class="empty-message">
                <p>No pets yet! Visit the Gacha tab to get your first companion.</p>
            </div>
        `;
        return;
    }

    if (displayPets.length === 0) {
        grid.innerHTML = `
            <div class="empty-message">
                <p>No pets match your search or filter.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = displayPets.map(pet => {
        const imgPath = getPetImagePath(pet);
        const displayName = pet.nickname || pet.species_name;
        // Fix: Explicit check against 1 or true because "0" is truthy in JS
        const isActive = pet.is_active == 1 || pet.is_active === true;
        const activeClass = isActive ? 'active' : '';
        const deadClass = pet.status === 'DEAD' ? 'dead' : '';
        // Tighten strict is_shiny check to prevent "fake" badges on non-shiny pets
        const isShiny = Number(pet.is_shiny) === 1 || pet.is_shiny === true || pet.is_shiny === '1';
        const shinyStyle = isShiny ? `filter: hue-rotate(${pet.shiny_hue}deg);` : '';

        // Use global ASSET_BASE
        const assetBase = (typeof window.ASSET_BASE !== 'undefined') ? window.ASSET_BASE : '/';

        const elementIcons = {
            'Fire': `<img src="${assetBase}assets/elements/fire.png" class="element-icon-img" alt="Fire">`,
            'Water': `<img src="${assetBase}assets/elements/water.png" class="element-icon-img" alt="Water">`,
            'Earth': `<img src="${assetBase}assets/elements/earth.png" class="element-icon-img" alt="Earth">`,
            'Air': `<img src="${assetBase}assets/elements/air.png" class="element-icon-img" alt="Air">`,
            'Light': `<img src="${assetBase}assets/elements/light.png" class="element-icon-img" alt="Light">`,
            'Dark': `<img src="${assetBase}assets/elements/dark.png" class="element-icon-img" alt="Dark">`
        };
        const elementIcon = elementIcons[pet.element] || '⭐';

        let actionButtonHTML = '';
        if (pet.status === 'SHELTER') {
            actionButtonHTML = `
                <button class="shop-buy-btn" onclick="safeToggleShelter(event, ${pet.id})">
                    <i class="fas fa-box-open"></i> Retrieve
                </button>
            `;
        } else if (pet.status === 'ALIVE') {
            // Determine if evolution is possible based on current stage and level
            // HARDCORE LEVELS: Egg -> 30, Baby -> 70
            const currentStage = pet.evolution_stage || 'egg';
            const canEvolve = (currentStage === 'egg' && pet.level >= 30) ||
                (currentStage === 'baby' && pet.level >= 70);

            actionButtonHTML = `
                <div class="pet-action-row" style="display: flex; gap: 4px; margin-top: 8px;">
                    ${!isActive ? `
                        <button class="pet-action-btn btn-sell" onclick="safeSellPet(event, ${pet.id})" title="Sell Pet" style="background: linear-gradient(135deg, #f39c12, #e67e22); border: none; border-radius: 6px; padding: 6px 10px; color: white; cursor: pointer;">
                            <i class="fas fa-coins"></i>
                        </button>
                    ` : ''}
                    ${canEvolve ? `
                        <button class="pet-action-btn btn-evolve" onclick="safeEvolve(event, ${pet.id})" title="Evolve to ${currentStage === 'egg' ? 'Baby' : 'Adult'}" style="background: linear-gradient(135deg, #9B59B6, #8E44AD); border: none; border-radius: 6px; padding: 6px 10px; color: white; cursor: pointer;">
                            <i class="fas fa-star"></i> Evolve
                        </button>
                    ` : ''}
                </div>
            `;
        }

        // Helper to create safe onclick string
        // We use a global helper or just standard onclick but without event.stopPropagation() if possible, 
        // or ensure event is handled safely. Best approach: standard onclick with return false to stop propagation 
        // OR better: rely on the called function to handle propagation if passed the event.

        // HOWEVER, simple fix for "action error":
        // The error likely comes from "event is not defined" in strict mode modules or strict contexts.
        // We will remove event.stopPropagation() from the HTML and handle bubbling logic in the specific functions if needed
        // OR pass 'event' explicitly if browser supports it (most do in onclick attributes).

        // SAFE APPROACH: Use a wrapper in window that handles propagation

        return `
            <div class="pet-card ${activeClass} ${deadClass}" onclick="selectPet(${pet.id})">
                <button class="pet-info-btn" onclick="openPetInfo(event, ${pet.id})" title="View Details">
                    <i class="fas fa-info"></i>
                </button>
                <span class="rarity-badge ${pet.rarity.toLowerCase()}">${pet.rarity}</span>
                <div class="pet-card-element ${pet.element.toLowerCase()}" title="${pet.element}">
                    ${elementIcon}
                </div>
                ${isShiny ? '<div class="shiny-badge-premium"><i class="fas fa-star"></i></div>' : ''}
                <img src="${imgPath}" alt="${pet.species_name}" class="pet-card-img" 
                     style="${shinyStyle}"
                     onerror="this.onerror=null; this.src='${(typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '../')}assets/placeholder.png'">
                <h3 class="pet-card-name">${displayName}</h3>
                <span class="pet-card-level">Lv.${pet.level}</span>
                ${actionButtonHTML}
            </div>
        `;
    }).join('');
}

// Global safe event stopper (if needed)
window.stopProp = function (e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (window.event) window.event.cancelBubble = true;
};

// Global safe info opener
window.openPetInfo = function (e, petId) {


    if (e && e.stopPropagation) {
        e.stopPropagation();

    }
    if (window.event) {
        window.event.cancelBubble = true;
    }



    if (window.openPetDetailById) {

        window.openPetDetailById(petId);
    } else {
        console.error('❌ openPetDetailById not found!');

    }
    return false;
};

// Safe wrappers for action buttons
window.safeSellPet = function (e, petId) {

    if (e && e.stopPropagation) e.stopPropagation();
    if (window.event) window.event.cancelBubble = true;

    if (typeof window.sellPet === 'function') {

        window.sellPet(petId);
    } else {
        // Inline fallback: open sell modal directly using module state
        console.warn('⚠️ window.sellPet not available, using inline fallback');
        const pets = state.userPets || window.userPets;
        if (!pets) {
            console.error('❌ No pet data available');
            if (typeof window.showToast === 'function') window.showToast('Pet data not loaded yet', 'error');
            return false;
        }
        const pet = pets.find(p => p.id == petId);
        if (!pet) {
            console.error('❌ Pet not found for ID:', petId);
            if (typeof window.showToast === 'function') window.showToast('Pet not found', 'error');
            return false;
        }

        // Calculate sell price (mirror of pet_hardcore.js formula)
        const basePrices = { 'Common': 1, 'Uncommon': 2, 'Rare': 3, 'Epic': 10, 'Legendary': 25 };
        const base = basePrices[pet.rarity] || 1;
        const levelMultiplier = Math.max(1, Math.floor(Math.sqrt(pet.level)));
        const sellPrice = base * levelMultiplier;

        // Get image path
        const imgPath = getPetImagePath(pet);
        const displayName = pet.nickname || pet.species_name;

        // Store pet id for confirm action
        window._pendingSellPetId = petId;

        // Populate modal
        const sellImg = document.getElementById('sell-pet-img');
        const sellName = document.getElementById('sell-pet-name');
        const sellLevel = document.getElementById('sell-pet-level');
        const sellPriceEl = document.getElementById('sell-price');
        const sellModal = document.getElementById('sell-modal');

        if (sellImg) sellImg.src = imgPath;
        if (sellName) sellName.textContent = displayName;
        if (sellLevel) sellLevel.textContent = `Lv.${pet.level}`;
        if (sellPriceEl) sellPriceEl.textContent = sellPrice.toLocaleString();

        if (sellModal) {
            sellModal.classList.add('show');

        } else {
            console.error('❌ sell-modal element not found in DOM!');
        }
    }
    return false;
};

window.safeEvolve = function (e, petId) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (window.event) window.event.cancelBubble = true;

    if (typeof window.openEvolutionModal === 'function') {
        try {
            window.openEvolutionModal(petId).catch(err => {
                console.error('❌ openEvolutionModal async error:', err);
                if (typeof window.showToast === 'function') window.showToast('Failed to open evolution modal', 'error');
            });
        } catch (err) {
            console.error('❌ openEvolutionModal sync error:', err);
            if (typeof window.showToast === 'function') window.showToast('Failed to open evolution modal', 'error');
        }
    } else {
        console.error('❌ openEvolutionModal not found! Type:', typeof window.openEvolutionModal);
    }
    return false;
};

window.safeToggleShelter = function (e, petId) {

    if (e && e.stopPropagation) e.stopPropagation();
    if (window.event) window.event.cancelBubble = true;

    if (typeof window.toggleShelter === 'function') {

        window.toggleShelter(petId);
    } else {
        console.error('❌ toggleShelter not found! Type:', typeof window.toggleShelter);
    }
    return false;
};
// Fallback: closeSellModal (if pet_hardcore.js didn't load)
if (typeof window.closeSellModal !== 'function') {
    window.closeSellModal = function () {
        const modal = document.getElementById('sell-modal');
        if (modal) modal.classList.remove('show');
        window._pendingSellPetId = null;
    };
}

// Fallback: confirmSellPet (if pet_hardcore.js didn't load)
if (typeof window.confirmSellPet !== 'function') {
    window.confirmSellPet = async function () {
        const petId = window._pendingSellPetId;
        if (!petId) return;

        const btn = document.getElementById('confirm-sell-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Selling...';
        }

        try {
            const API = (typeof window.API_BASE !== 'undefined') ? window.API_BASE : 'api/';
            const response = await fetchWithCsrf(`${API}pets/sell`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pet_id: petId })
            });
            const data = await response.json();

            if (data.success) {
                if (typeof window.showToast === 'function') window.showToast(data.message || 'Pet sold!', 'success');
                if (typeof window.updateGoldDisplay === 'function') window.updateGoldDisplay(data.remaining_gold);
                window.closeSellModal();
                if (typeof window.loadPets === 'function') window.loadPets();
            } else {
                if (typeof window.showToast === 'function') window.showToast(data.error || 'Failed to sell pet', 'error');
            }
        } catch (error) {
            console.error('Error selling pet:', error);
            if (typeof window.showToast === 'function') window.showToast('Network error', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Confirm Sell';
            }
        }
    };
}

// Listen for pets loaded event
document.addEventListener('petsLoaded', () => {
    // Initialize search once
    if (typeof window.initCollectionSearch === 'function') {
        window.initCollectionSearch();
    }

    // Only render if collection grid exists
    if (document.getElementById('collection-grid')) {
        renderCollection();
    }
});
