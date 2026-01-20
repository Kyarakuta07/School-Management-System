/**
 * Pet Detail Modal Module
 * Handles the pet detail view modal in collection
 * 
 * Dependencies: pet.js (for getPetImagePath, loadPets, switchTab, showToast, showConfirm, toggleShelter)
 */

// Current pet being viewed in detail modal
let currentDetailPetId = null;

/**
 * Set Active Pet (click on card)
 * @param {number} petId - Pet ID to set as active
 */
async function setActivePet(petId) {
    const pet = window.userPets.find(p => p.id === petId);
    if (!pet) return;

    if (pet.status === 'DEAD') {
        showToast('This pet is dead! Use a revival item first.', 'warning');
        return;
    }

    if (pet.status === 'SHELTER') {
        showConfirm(
            'Are you sure you want to retrieve ' + (pet.nickname || pet.species_name) + ' from the shelter?',
            'Retrieve from Shelter',
            'fa-box-open'
        ).then(confirmed => {
            if (confirmed) {
                toggleShelter(petId);
            }
        });
        return;
    }

    // If already active, just switch to my-pet tab
    if (pet.is_active) {
        switchTab('my-pet');
        return;
    }

    try {
        const response = await fetch(API_BASE + '?action=set_active', {
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
 * Open Pet Detail by ID
 * @param {number} petId - Pet ID to view details
 */
function openPetDetailById(petId) {
    const pet = window.userPets.find(p => p.id === petId);
    if (!pet) return;
    currentDetailPetId = petId;
    openPetDetail(pet);
}

/**
 * Open Pet Detail Modal (legacy support)
 * @param {number} petId - Pet ID
 */
function selectPet(petId) {
    openPetDetailById(petId);
}

/**
 * Open Pet Detail Modal with calculated stats
 * @param {Object} pet - Pet object
 */
function openPetDetail(pet) {
    const modal = document.getElementById('pet-detail-modal');
    if (!modal) {
        console.warn('Pet detail modal not found');
        return;
    }

    // Get image path
    const imgPath = getPetImagePath(pet);
    const shinyStyle = pet.is_shiny ? 'filter: hue-rotate(' + pet.shiny_hue + 'deg);' : '';

    // Populate Header
    document.getElementById('detail-pet-img').src = imgPath;
    document.getElementById('detail-pet-img').style.cssText = shinyStyle;
    document.getElementById('detail-pet-name').textContent = pet.nickname || pet.species_name;

    // Badges
    const elementBadge = document.getElementById('detail-element');
    elementBadge.textContent = pet.element;
    elementBadge.className = 'detail-badge element ' + pet.element.toLowerCase();

    const rarityBadge = document.getElementById('detail-rarity');
    rarityBadge.textContent = pet.rarity;
    rarityBadge.className = 'detail-badge rarity ' + pet.rarity.toLowerCase();

    document.getElementById('detail-level').textContent = 'Lv.' + pet.level;

    // Shiny tag
    document.getElementById('detail-shiny-tag').style.display = pet.is_shiny ? 'block' : 'none';

    // Evolution Stage
    const stageEl = document.getElementById('detail-stage');
    const stageIcons = { egg: 'fa-egg', baby: 'fa-dragon', adult: 'fa-crown' };
    const stageNames = { egg: 'Egg Stage', baby: 'Baby Stage', adult: 'Adult Stage' };
    const stage = pet.evolution_stage || 'egg';
    stageEl.innerHTML = '<i class="fas ' + stageIcons[stage] + '"></i> <span>' + stageNames[stage] + '</span>';

    // Calculate Battle Stats
    const baseHp = parseInt(pet.base_health) || 100;
    const baseAtk = parseInt(pet.base_attack) || 10;
    const baseDef = parseInt(pet.base_defense) || 10;
    const level = parseInt(pet.level) || 1;

    const calculatedHp = baseHp + (level * 10);
    const calculatedAtk = baseAtk + (level * 2);
    const calculatedPower = baseAtk + baseDef + (level * 3);

    // Display Battle Stats
    document.getElementById('detail-hp').textContent = calculatedHp;
    document.getElementById('detail-atk').textContent = calculatedAtk;
    document.getElementById('detail-power').textContent = calculatedPower;

    // Display Base Stats
    document.getElementById('detail-base-hp').textContent = baseHp;
    document.getElementById('detail-base-atk').textContent = baseAtk;
    document.getElementById('detail-base-def').textContent = baseDef;

    // Update action button based on pet status
    const actionBtn = document.getElementById('detail-set-active-btn');
    if (pet.status === 'DEAD') {
        actionBtn.innerHTML = '<i class="fas fa-skull"></i> Pet is Dead';
        actionBtn.disabled = true;
        actionBtn.className = 'detail-action-btn disabled';
    } else if (pet.status === 'SHELTER') {
        actionBtn.innerHTML = '<i class="fas fa-box-open"></i> Retrieve from Shelter';
        actionBtn.disabled = false;
        actionBtn.className = 'detail-action-btn secondary';
    } else if (pet.is_active) {
        actionBtn.innerHTML = '<i class="fas fa-check-circle"></i> Currently Active';
        actionBtn.disabled = true;
        actionBtn.className = 'detail-action-btn success';
    } else {
        actionBtn.innerHTML = '<i class="fas fa-star"></i> Set as Active';
        actionBtn.disabled = false;
        actionBtn.className = 'detail-action-btn primary';
    }

    // Show modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

/**
 * Close Pet Detail Modal
 */
function closePetDetail() {
    const modal = document.getElementById('pet-detail-modal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    currentDetailPetId = null;
}

/**
 * Set Active from Detail Modal
 */
async function setActiveFromDetail() {
    if (!currentDetailPetId) return;

    const pet = window.userPets.find(p => p.id === currentDetailPetId);
    if (!pet) return;

    // Handle shelter retrieve
    if (pet.status === 'SHELTER') {
        closePetDetail();
        showConfirm(
            'Are you sure you want to retrieve ' + (pet.nickname || pet.species_name) + ' from the shelter?',
            'Retrieve from Shelter',
            'fa-box-open'
        ).then(confirmed => {
            if (confirmed) {
                toggleShelter(currentDetailPetId);
            }
        });
        return;
    }

    // Set as active
    try {
        const response = await fetch(API_BASE + '?action=set_active', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pet_id: currentDetailPetId })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Pet is now active!', 'success');
            closePetDetail();
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

// ================================================
// GLOBAL EXPORTS
// ================================================
window.setActivePet = setActivePet;
window.openPetDetailById = openPetDetailById;
window.selectPet = selectPet;
window.openPetDetail = openPetDetail;
window.closePetDetail = closePetDetail;
window.setActiveFromDetail = setActiveFromDetail;
