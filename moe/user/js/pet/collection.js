/**
 * Collection Rendering Module
 * MOE Pet System
 * 
 * Handles the collection tab display
 */

import { state } from './state.js';
import { getPetImagePath, updatePetCountBadge } from './pets.js';

export function renderCollection() {
    console.log('=== MODULE collection.js renderCollection CALLED ===');
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
        } else if (pet.status === 'ALIVE') {
            // Determine if evolution is possible based on current stage and level
            const currentStage = pet.evolution_stage || 'egg';
            const canEvolve = (currentStage === 'egg' && pet.level >= 10) ||
                (currentStage === 'baby' && pet.level >= 20);

            actionButtonHTML = `
                <div class="pet-action-row" style="display: flex; gap: 4px; margin-top: 8px;">
                    ${!pet.is_active ? `
                        <button class="pet-action-btn btn-sell" onclick="event.stopPropagation(); sellPet(${pet.id})" title="Sell Pet" style="background: linear-gradient(135deg, #f39c12, #e67e22); border: none; border-radius: 6px; padding: 6px 10px; color: white; cursor: pointer;">
                            <i class="fas fa-coins"></i>
                        </button>
                    ` : ''}
                    ${canEvolve ? `
                        <button class="pet-action-btn btn-evolve" onclick="event.stopPropagation(); openEvolutionModal(${pet.id})" title="Evolve to ${currentStage === 'egg' ? 'Baby' : 'Adult'}" style="background: linear-gradient(135deg, #9B59B6, #8E44AD); border: none; border-radius: 6px; padding: 6px 10px; color: white; cursor: pointer;">
                            <i class="fas fa-star"></i> Evolve
                        </button>
                    ` : ''}
                </div>
            `;
        }

        return `
            <div class="pet-card ${activeClass} ${deadClass}" onclick="selectPet(${pet.id})">
                <button class="pet-info-btn" onclick="event.stopPropagation(); openPetDetailById(${pet.id})" title="View Details">
                    <i class="fas fa-info"></i>
                </button>
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
