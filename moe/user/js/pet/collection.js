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

    if (state.userPets.length === 0) {
        grid.innerHTML = `
            <div class="empty-message">
                <p>No pets yet! Visit the Gacha tab to get your first companion.</p>
            </div>
        `;
        return;
    }

    // Get filtered pets if filter function exists (from collection_phase2.js global)
    console.log('collection.js renderCollection: window.getFilteredPets available?', typeof window.getFilteredPets === 'function');
    const displayPets = typeof window.getFilteredPets === 'function' ? window.getFilteredPets() : state.userPets;
    console.log('collection.js displayPets:', displayPets.length);

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
        const activeClass = pet.is_active ? 'active' : '';
        const deadClass = pet.status === 'DEAD' ? 'dead' : '';
        const shinyStyle = pet.is_shiny ? `filter: hue-rotate(${pet.shiny_hue}deg);` : '';

        const elementIcons = {
            'Fire': '🔥',
            'Water': '💧',
            'Earth': '🌿',
            'Air': '💨'
        };
        const elementIcon = elementIcons[pet.element] || '⭐';

        let actionButtonHTML = '';
        if (pet.status === 'SHELTER') {
            actionButtonHTML = `
                <button class="shop-buy-btn" onclick="event.stopPropagation(); selectPet(${pet.id})">
                    <i class="fas fa-box-open"></i> Retrieve
                </button>
            `;
        }

        return `
            <div class="pet-card ${activeClass} ${deadClass}" onclick="selectPet(${pet.id})">
                <span class="rarity-badge ${pet.rarity.toLowerCase()}">${pet.rarity}</span>
                <div class="pet-card-element ${pet.element.toLowerCase()}" title="${pet.element}">
                    ${elementIcon}
                </div>
                ${pet.is_shiny ? '<div class="pet-card-shiny">✨</div>' : ''}
                <img src="${imgPath}" alt="${pet.species_name}" class="pet-card-img" 
                     style="${shinyStyle}"
                     onerror="this.src='../assets/placeholder.png'">
                <h3 class="pet-card-name">${displayName}</h3>
                <span class="pet-card-level">Lv.${pet.level}</span>
                ${actionButtonHTML}
            </div>
        `;
    }).join('');
}

// Listen for pets loaded event
document.addEventListener('petsLoaded', () => {
    renderCollection();
});
