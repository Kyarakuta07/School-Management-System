/**
 * Gacha System Module
 * @module pet/gacha
 * @description Handles gacha/summoning mechanics and result display
 * 
 * @author MOE Development Team
 * @version 2.0.0
 */

import { API_BASE, ASSETS_BASE } from './config.js';
import { state } from './state.js';
import { showToast, updateGoldDisplay } from './ui.js';
import { loadPets } from './pets.js';

// ================================================
// GACHA ACTIONS
// ================================================

/**
 * Perform a gacha summon
 * @async
 * @param {'standard'|'premium'} type - The type of gacha to perform
 * @returns {Promise<void>}
 * @example
 * performGacha('standard');  // Costs 50 gold
 * performGacha('premium');   // Uses Premium Ticket, guaranteed Rare+
 */
export async function performGacha(type) {
    const egg = document.getElementById('gacha-egg');
    egg?.classList.add('hatching');

    let gachaType = 'standard';
    if (type === 'premium') {
        gachaType = 'premium';
    }

    try {
        const response = await fetch(`${API_BASE}?action=gacha`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: gachaType })
        });

        const data = await response.json();

        setTimeout(() => {
            egg?.classList.remove('hatching');

            if (data.success) {
                showGachaResult(data);
                updateGoldDisplay(data.remaining_gold);
                loadPets();
            } else {
                showToast(data.error || 'Gacha failed', 'error');
            }
        }, 500);

    } catch (error) {
        console.error('Error performing gacha:', error);
        egg?.classList.remove('hatching');
        showToast('Network error', 'error');
    }
}

/**
 * Display the gacha result in a modal
 * @param {Object} data - Gacha result data from API
 * @param {Object} data.species - The obtained pet species
 * @param {string} data.species.name - Species name
 * @param {string} data.species.img_egg - Egg stage image path
 * @param {string} data.species.element - Element type
 * @param {string} data.rarity - Rarity of obtained pet ('Common', 'Rare', 'Epic', 'Legendary')
 * @param {boolean} data.is_shiny - Whether the pet is shiny
 * @param {number} [data.shiny_hue] - Hue rotation for shiny pets (30-330)
 * @returns {void}
 */
export function showGachaResult(data) {
    const modal = document.getElementById('gacha-modal');
    const modalContent = modal?.querySelector('.gacha-result-modal');
    const species = data.species;

    if (!modal || !modalContent) return;

    modalContent.classList.remove('rarity-common', 'rarity-rare', 'rarity-epic', 'rarity-legendary');
    modalContent.classList.add(`rarity-${data.rarity.toLowerCase()}`);

    document.getElementById('result-pet-img').src = ASSETS_BASE + (species.img_egg || 'default/egg.png');
    document.getElementById('result-name').textContent = species.name;

    const rarityBadge = document.getElementById('result-rarity');
    rarityBadge.textContent = data.rarity;
    rarityBadge.className = `rarity-badge-large ${data.rarity.toLowerCase()}`;

    const elementEl = document.getElementById('result-element');
    if (elementEl && species.element) {
        elementEl.textContent = species.element;
    }

    const titleEl = document.getElementById('result-title');
    const titles = {
        'Common': 'New Pet!',
        'Rare': 'Nice Pull!',
        'Epic': 'Amazing!',
        'Legendary': '🎉 LEGENDARY! 🎉'
    };
    const titleText = titles[data.rarity] || 'Congratulations!';
    titleEl.innerHTML = `<i class="fas fa-sparkles"></i><span>${titleText}</span>`;

    const shinyBadge = document.getElementById('result-shiny');
    if (data.is_shiny) {
        document.getElementById('result-pet-img').style.filter = `hue-rotate(${data.shiny_hue}deg) drop-shadow(0 10px 40px rgba(0, 0, 0, 0.6))`;
        shinyBadge.style.display = 'flex';
    } else {
        document.getElementById('result-pet-img').style.filter = 'drop-shadow(0 10px 40px rgba(0, 0, 0, 0.6))';
        shinyBadge.style.display = 'none';
    }

    modal.classList.add('show');

    if (window.PetAnimations) {
        const resultPet = document.getElementById('result-pet-img');
        if (resultPet) {
            resultPet.style.animation = 'none';
            setTimeout(() => {
                resultPet.style.animation = 'pet-reveal 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            }, 10);
        }
    }
}

/**
 * Close the gacha result modal
 * @returns {void}
 */
export function closeGachaModal() {
    const modal = document.getElementById('gacha-modal');
    modal?.classList.remove('show');
    loadPets();
}

// Listen for showGachaResult event from inventory module
document.addEventListener('showGachaResult', (e) => {
    showGachaResult(e.detail);
});
