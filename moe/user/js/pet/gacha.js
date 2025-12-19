/**
 * Gacha System Module
 * MOE Pet System
 * 
 * Handles gacha mechanics and result display
 */

import { API_BASE, ASSETS_BASE } from './config.js';
import { state } from './state.js';
import { showToast, updateGoldDisplay } from './ui.js';
import { loadPets } from './pets.js';

// ================================================
// GACHA ACTIONS
// ================================================

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

export function closeGachaModal() {
    const modal = document.getElementById('gacha-modal');
    modal?.classList.remove('show');
    loadPets();
}

// Listen for showGachaResult event from inventory module
document.addEventListener('showGachaResult', (e) => {
    showGachaResult(e.detail);
});
