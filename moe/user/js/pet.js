/**
 * MOE Pet System - Frontend JavaScript
 * Mediterranean of Egypt Virtual Pet Companion
 * Handles all UI interactions and API communication
 */

// ================================================
// CONFIGURATION
// ================================================
const API_BASE = 'pet_api.php';
const ASSETS_BASE = '../assets/pets/';

// State
let currentTab = 'my-pet';
window.userPets = []; // Exposed to window for arena module
window.activePet = null; // Exposed to window for arena module
let shopItems = [];
let userInventory = [];
let selectedItemType = null;
let currentBulkItem = null; // State untuk modal bulk use

// Create local aliases for convenience
let userPets = window.userPets;
let activePet = window.activePet;

// ================================================
// INITIALIZATION
// ================================================
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initActionButtons();
    initShopTabs();
    initArenaTabs();
    initGoldToggle(); // Initialize gold toggle
    loadPets();
    checkDailyReward(); // Check for daily login reward
    checkUrlErrors(); // Check for error messages from redirects
});

// Check URL for error parameters
function checkUrlErrors() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');

    if (error === 'battle_limit') {
        showToast('You have used all 3 daily battles! Come back tomorrow.', 'warning');
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (error === 'missing_pets') {
        showToast('Invalid battle parameters', 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// ================================================
// DAILY LOGIN REWARD SYSTEM
// ================================================
let dailyRewardData = null;

async function checkDailyReward() {
    try {
        const response = await fetch(`${API_BASE}?action=get_daily_reward`);
        const data = await response.json();

        if (data.success && data.can_claim) {
            dailyRewardData = data;
            showDailyRewardModal(data);
        }
    } catch (error) {
        console.error('Error checking daily reward:', error);
    }
}

function showDailyRewardModal(data) {
    const modal = document.getElementById('daily-login-modal');
    const calendar = document.getElementById('daily-calendar');
    const currentDayEl = document.getElementById('daily-current-day');
    const rewardText = document.getElementById('daily-reward-text');
    const totalLogins = document.getElementById('daily-total-logins');

    currentDayEl.textContent = data.current_day;
    totalLogins.textContent = data.total_logins;

    // Build reward text
    let rewardStr = '';
    if (data.reward_gold > 0) {
        rewardStr += `${data.reward_gold} Gold`;
    }
    if (data.reward_item_name) {
        rewardStr += (rewardStr ? ' + ' : '') + `1 ${data.reward_item_name}`;
    }
    rewardText.textContent = rewardStr;

    // Generate calendar
    let calendarHTML = '';
    const specialDays = [7, 14, 21, 28, 30];

    for (let day = 1; day <= 30; day++) {
        let classes = 'calendar-day';
        let icon = '💰';

        if (day < data.current_day) {
            classes += ' claimed';
            icon = '✓';
        } else if (day === data.current_day) {
            classes += ' current';
            icon = '🎁';
        }

        if (specialDays.includes(day)) {
            classes += ' special';
            if (day !== data.current_day && day > data.current_day) {
                icon = '⭐';
            }
        }

        calendarHTML += `
            <div class="${classes}">
                <span class="day-num">${day}</span>
                <span class="day-icon">${icon}</span>
            </div>
        `;
    }
    calendar.innerHTML = calendarHTML;

    modal.classList.add('show');
}

async function claimDailyReward() {
    const btn = document.getElementById('claim-reward-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Claiming...';

    try {
        const response = await fetch(`${API_BASE}?action=claim_daily_reward`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        if (data.success) {
            let message = `Day ${data.claimed_day} reward claimed!`;
            if (data.gold_received > 0) {
                message += ` +${data.gold_received} Gold`;
            }
            if (data.item_received) {
                message += ` +1 ${data.item_received}`;
            }

            showToast(message, 'success');
            closeDailyModal();
            updateGoldDisplay(); // Refresh gold
            loadInventory(); // Refresh inventory
        } else {
            showToast(data.error || 'Failed to claim reward', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-gift"></i> Claim Reward!';
        }
    } catch (error) {
        console.error('Error claiming daily reward:', error);
        showToast('Network error', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-gift"></i> Claim Reward!';
    }
}

function closeDailyModal() {
    document.getElementById('daily-login-modal').classList.remove('show');
}

// ================================================
// TAB NAVIGATION
// ================================================
function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            switchTab(tab);
        });
    });
}

function switchTab(tab) {
    // Update buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    // Update content - support both .tab-content and .tab-panel
    document.querySelectorAll('.tab-content, .tab-panel').forEach(content => {
        content.classList.toggle('active', content.id === tab);
    });

    currentTab = tab;

    // Load tab-specific data
    switch (tab) {
        case 'my-pet':
            // Only render, don't fetch again (activePet already set by loadPets)
            if (activePet) {
                renderActivePet();
            } else {
                loadActivePet(); // Fallback if no active pet loaded yet
            }
            break;
        case 'collection':
            loadPets();
            break;
        case 'gacha':
            // Gacha tab - no data loading needed
            break;
        case 'shop':
            loadShop();
            loadInventory();
            break;
        case 'arena':
            loadActivePet(); // Refresh HP after battles
            loadOpponents();
            break;
        case 'arena3v3':
            // 3v3 arena - load team selection if needed
            break;
        case 'achievements':
            loadAchievements();
            break;
    }
}

// ================================================
// PET LOADING & DISPLAY
// ================================================
async function loadPets() {
    try {
        const response = await fetch(`${API_BASE}?action=get_pets`);
        const data = await response.json();

        if (data.success) {
            window.userPets = data.pets;

            // Update pet count badge (always, regardless of tab)
            updatePetCountBadge();

            renderCollection();

            // Find and display active pet
            window.activePet = window.userPets.find(p => p.is_active);
            if (window.activePet) {
                renderActivePet();
            }
        }
    } catch (error) {
        console.error('Error loading pets:', error);
        showToast('Failed to load pets', 'error');
    }
}

async function loadActivePet() {
    try {
        const response = await fetch(`${API_BASE}?action=get_active_pet`);
        const data = await response.json();

        if (data.success && data.pet) {
            activePet = data.pet;
            renderActivePet();

            // Load pet into PixiJS for GPU-accelerated rendering
            if (window.PixiPet && window.PixiPet.isReady()) {
                window.PixiPet.load(activePet);
            }
        } else {
            showNoPetMessage();
        }
    } catch (error) {
        console.error('Error loading active pet:', error);
    }
}

function renderActivePet() {
    // New elements
    const noPetMsg = document.getElementById('no-pet-message');
    const petZone = document.getElementById('pet-display-zone');
    const infoHeader = document.getElementById('pet-info-header');
    const statsContainer = document.getElementById('stats-container');
    const expCard = document.getElementById('exp-card');
    const actions = document.getElementById('action-buttons');

    if (!activePet) {
        showNoPetMessage();
        return;
    }

    // Hide no-pet message, show pet zone
    noPetMsg.style.display = 'none';
    petZone.style.display = 'block';

    // Build pet display
    const imgPath = getPetImagePath(activePet);
    const shinyStyle = activePet.is_shiny ? `filter: hue-rotate(${activePet.shiny_hue}deg);` : '';

    // Render pet image in container
    const petContainer = document.getElementById('pet-img-container');
    petContainer.innerHTML = `
        <img src="${imgPath}" alt="${activePet.species_name}" 
             class="pet-image pet-anim-idle" 
             style="${shinyStyle}; width: 180px; height: 180px; object-fit: contain; filter: drop-shadow(0 8px 25px rgba(0,0,0,0.6));"
             onerror="this.src='../assets/placeholder.png'">
    `;

    // Show shiny sparkles if applicable
    const shinySparkles = document.getElementById('shiny-sparkles');
    shinySparkles.style.display = activePet.is_shiny ? 'block' : 'none';

    // === INFO HEADER ===
    infoHeader.style.display = 'block';

    // Pet name
    const displayName = activePet.nickname || activePet.species_name;
    document.getElementById('pet-name').textContent = displayName;

    // Level badge
    document.getElementById('pet-level').textContent = `Lv.${activePet.level}`;

    // Element badge
    const elementBadge = document.getElementById('pet-element-badge');
    elementBadge.textContent = activePet.element;
    elementBadge.className = `element-badge ${activePet.element.toLowerCase()}`;

    // Rarity badge
    const rarityBadge = document.getElementById('pet-rarity-badge');
    rarityBadge.textContent = activePet.rarity;
    rarityBadge.className = `rarity-badge ${activePet.rarity.toLowerCase()}`;

    // Shiny tag
    const shinyTag = document.getElementById('shiny-tag');
    shinyTag.style.display = activePet.is_shiny ? 'inline-flex' : 'none';

    // === STAT CARDS with Circular Progress ===
    statsContainer.style.display = 'grid';

    // Update circular progress rings
    updateCircularProgress('health', activePet.health);
    updateCircularProgress('hunger', activePet.hunger);
    updateCircularProgress('mood', activePet.mood);

    // === EXP CARD ===
    expCard.style.display = 'block';
    const expNeeded = Math.floor(100 * Math.pow(1.2, activePet.level - 1));
    const expPercent = (activePet.exp / expNeeded) * 100;
    document.getElementById('exp-bar').style.width = `${expPercent}%`;
    document.getElementById('exp-text').textContent = `${activePet.exp} / ${expNeeded}`;

    // === ACTION BUTTONS ===
    actions.style.display = 'grid';

    // Update shelter button text
    const shelterBtn = document.getElementById('btn-shelter');
    if (shelterBtn) {
        const labelEl = shelterBtn.querySelector('.action-label');
        const iconEl = shelterBtn.querySelector('i');
        if (activePet.status === 'SHELTER') {
            if (labelEl) labelEl.textContent = 'Retrieve';
            if (iconEl) iconEl.className = 'fas fa-door-open';
        } else {
            if (labelEl) labelEl.textContent = 'Shelter';
            if (iconEl) iconEl.className = 'fas fa-home';
        }
    }
}

/**
 * Update circular progress ring
 * @param {string} type - 'health', 'hunger', or 'mood'
 * @param {number} value - 0-100
 */
function updateCircularProgress(type, value) {
    const ring = document.getElementById(`${type}-ring`);
    const valueEl = document.getElementById(`${type}-value`);

    if (!ring || !valueEl) return;

    // Update value display
    valueEl.textContent = Math.round(value);

    // Calculate stroke-dashoffset for circular progress
    // Circumference = 2 * PI * radius = 2 * 3.14159 * 35 ≈ 220
    const circumference = 220;
    const offset = circumference - (value / 100) * circumference;

    ring.style.strokeDashoffset = offset;
}

function updateStatusBar(type, value) {
    const bar = document.getElementById(`${type}-bar`);
    const valueEl = document.getElementById(`${type}-value`);

    if (!bar || !valueEl) return; // Safety check

    bar.style.width = `${value}%`;
    valueEl.textContent = Math.round(value);

    // Color coding based on value
    if (value <= 20) {
        bar.style.opacity = '1';
        bar.classList.add('critical');
    } else {
        bar.classList.remove('critical');
    }
}

function showNoPetMessage() {
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

function getPetImagePath(pet) {
    // Use stored evolution_stage from database, not level-based
    const stage = pet.evolution_stage || 'egg';

    // Priority order based on evolution stage
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

    // Try each image key until we find a valid one
    for (const key of imgKeys) {
        if (pet[key] && pet[key] !== '' && pet[key] !== null) {
            return ASSETS_BASE + pet[key];
        }
    }

    // Last resort: current_image or default
    return ASSETS_BASE + (pet.current_image || 'default/egg.png');
}

// Get evolution stage from pet data (stored in database, not calculated)
function getEvolutionStage(pet) {
    // If pet is an object, use stored stage
    if (typeof pet === 'object' && pet !== null) {
        return pet.evolution_stage || 'egg';
    }
    // Legacy fallback
    return 'egg';
}

// ================================================
// UPDATE PET COUNT BADGE
// ================================================
function updatePetCountBadge() {
    const petCountBadge = document.getElementById('pet-count-badge');
    if (petCountBadge) {
        const petCount = userPets.length;
        petCountBadge.textContent = `${petCount} / 25`;

        // Color code based on capacity
        if (petCount >= 25) {
            petCountBadge.style.background = 'linear-gradient(135deg, #E74C3C, #C0392B)';
        } else if (petCount >= 20) {
            petCountBadge.style.background = 'linear-gradient(135deg, #F39C12, #E67E22)';
        } else {
            petCountBadge.style.background = 'linear-gradient(135deg, var(--gold), var(--gold-dark))';
        }
    }
}

// ================================================
// COLLECTION TAB (Premium Enhanced + Phase 2)
// ================================================
function renderCollection() {
    const grid = document.getElementById('collection-grid');

    // Update pet count badge
    updatePetCountBadge();

    // Update stats panel (Phase 2)
    if (typeof updateCollectionStats === 'function') {
        updateCollectionStats();
    }

    if (userPets.length === 0) {
        grid.innerHTML = `
            <div class="empty-message">
                <p>No pets yet! Visit the Gacha tab to get your first companion.</p>
            </div>
        `;
        return;
    }

    // Get filtered and sorted pets (Phase 2)
    const displayPets = typeof getFilteredPets === 'function' ? getFilteredPets() : userPets;

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

        // Element icon mapping
        const elementIcons = {
            'Fire': '🔥',
            'Water': '💧',
            'Earth': '🌿',
            'Air': '💨'
        };
        const elementIcon = elementIcons[pet.element] || '⭐';

        // Retrieve button for sheltered pets
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
                <!-- Rarity Badge -->
                <span class="rarity-badge ${pet.rarity.toLowerCase()}">${pet.rarity}</span>
                
                <!-- Element Badge -->
                <div class="pet-card-element ${pet.element.toLowerCase()}" title="${pet.element}">
                    ${elementIcon}
                </div>
                
                <!-- Shiny Indicator -->
                ${pet.is_shiny ? '<div class="pet-card-shiny">✨</div>' : ''}
                
                <!-- Pet Image -->
                <img src="${imgPath}" alt="${pet.species_name}" class="pet-card-img" 
                     style="${shinyStyle}"
                     onerror="this.src='../assets/placeholder.png'">
                
                <!-- Pet Info -->
                <h3 class="pet-card-name">${displayName}</h3>
                <span class="pet-card-level">Lv.${pet.level}</span>
                
                <!-- Retrieve Button (if applicable) -->
                ${actionButtonHTML}
            </div>
        `;
    }).join('');
}

// ================================================
// ACTION LOGIC (Action Buttons)
// ================================================
async function selectPet(petId) {
    const pet = userPets.find(p => p.id === petId);
    if (!pet) return;

    if (pet.status === 'DEAD') {
        showToast('This pet is dead! Use a revival item first.', 'warning');
        return;
    }

    // --- LOGIKA SHELTER RETRIEVE ---
    if (pet.status === 'SHELTER') {
        if (confirm(`Retrieve ${pet.nickname || pet.species_name} from shelter?`)) {
            toggleShelter(petId);
        }
        return;
    }

    // Set Active
    try {
        const response = await fetch(`${API_BASE}?action=set_active`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pet_id: petId })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Pet is now active!', 'success');
            await loadPets(); // Wait for pets to reload before switching tab
            switchTab('my-pet');
        } else {
            showToast(data.error || 'Failed to set active pet', 'error');
        }
    } catch (error) {
        console.error('Error setting active pet:', error);
        showToast('Network error', 'error');
    }
}

function initActionButtons() {
    // Tombol di dashboard utama
    document.getElementById('btn-feed').addEventListener('click', () => openItemModal('food'));
    document.getElementById('btn-heal').addEventListener('click', () => openItemModal('potion'));
    document.getElementById('btn-play').addEventListener('click', playWithPet);
    document.getElementById('btn-shelter').addEventListener('click', () => toggleShelter());
}

async function playWithPet() {
    if (!activePet) return;

    // Check if pet is alive
    if (activePet.status === 'DEAD') {
        showToast('This pet is dead! Use a revival item first.', 'warning');
        return;
    }

    // Play jump animation with hearts
    if (window.PetAnimations) {
        window.PetAnimations.jump();
        window.PetAnimations.hearts(3);
    }

    showToast('You played with ' + (activePet.nickname || activePet.species_name) + '! 🎵', 'success');

    // Open rhythm game modal after short delay
    setTimeout(() => {
        const rhythmModal = document.getElementById('rhythm-modal');
        if (rhythmModal) {
            rhythmModal.classList.add('show');
            // Start rhythm game if function exists
            if (typeof startRhythmGame === 'function') {
                startRhythmGame();
            }
        }
    }, 500);
}

async function toggleShelter(targetPetId = null) {
    const petId = targetPetId || (activePet ? activePet.id : null);
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
// INVENTORY & BULK USE SYSTEM (CORE UPDATE)
// ================================================

// Render Inventory with Premium Cards
function renderInventory() {
    const grid = document.getElementById('inventory-grid');

    // Update inventory count
    updateInventoryCount();

    if (userInventory.length === 0) {
        grid.innerHTML = '<p class="empty-message">No items yet</p>';
        return;
    }

    grid.innerHTML = userInventory.map(item => {
        // Determine rarity based on price (if available) or effect type
        let rarity = 'common';
        if (item.price) {
            if (item.price >= 1000) rarity = 'legendary';
            else if (item.price >= 500) rarity = 'epic';
            else if (item.price >= 200) rarity = 'rare';
            else if (item.price >= 100) rarity = 'uncommon';
        }

        const isDepleted = item.quantity === 0;

        return `
        <div class="inventory-item-card ${rarity} ${isDepleted ? 'depleted' : ''}" 
             onclick="${!isDepleted ? `handleInventoryClick(
                 ${item.item_id}, 
                 '${item.effect_type}', 
                 '${item.name.replace(/'/g, "\\'")}', 
                 '${item.description ? item.description.replace(/'/g, "\\'") : ''}', 
                 '${item.img_path}', 
                 ${item.quantity}
             )` : 'void(0)'}" 
             title="${item.name}${item.description ? ' - ' + item.description : ''}">
            <div class="inventory-item-img-wrapper">
                <img src="${ASSETS_BASE}${item.img_path}" alt="${item.name}"
                     onerror="this.src='../assets/placeholder.png'">
            </div>
            <span class="inventory-qty-badge">${item.quantity}</span>
            <p class="inventory-item-name">${item.name}</p>
        </div>
    `;
    }).join('');
}

// 2. Handle Klik Item (Buka Modal / Gacha)
async function handleInventoryClick(itemId, type, itemName, itemDesc, itemImg, maxQty) {

    // Gacha Ticket: Langsung eksekusi (tanpa bulk, demi animasi)
    if (type === 'gacha_ticket') {
        if (confirm(`Apakah kamu ingin menetaskan ${itemName} sekarang?`)) {
            useItem(itemId, 0, 1);
        }
        return;
    }

    // REVIVE ITEMS: Special handling - show dead pets to select
    if (type === 'revive') {
        openReviveModal(itemId, itemName, itemImg);
        return;
    }

    // Cek Pet Aktif (Untuk item konsumsi)
    if (!activePet) {
        showToast('Kamu butuh Active Pet untuk menggunakan item ini!', 'warning');
        return;
    }
    if (activePet.status === 'DEAD') {
        showToast('Pet mati. Hidupkan dulu dengan item Revive!', 'error');
        return;
    }

    // --- BUKA MODAL BULK USE ---
    currentBulkItem = {
        id: itemId,
        max: maxQty,
        name: itemName
    };

    // Pastikan elemen modal ada (dari file pet.php yang sudah diupdate HTML-nya)
    const modal = document.getElementById('bulk-use-modal');
    if (!modal) {
        // Fallback jika lupa update HTML: Pakai cara lama (confirm 1 item)
        if (confirm(`Gunakan ${itemName}?`)) useItem(itemId, activePet.id, 1);
        return;
    }

    // Update UI Modal
    document.getElementById('bulk-modal-title').textContent = itemName;
    document.getElementById('bulk-item-desc').textContent = itemDesc;
    document.getElementById('bulk-item-img').src = ASSETS_BASE + itemImg;
    document.getElementById('bulk-item-qty').max = maxQty; // Set max attribute
    document.getElementById('bulk-item-qty').value = 1; // Reset ke 1

    modal.classList.add('show');
}

// 3. Logic Kontrol Quantity Modal
function adjustQty(amount) {
    const input = document.getElementById('bulk-item-qty');
    let val = parseInt(input.value) + amount;

    if (val < 1) val = 1;
    if (val > currentBulkItem.max) val = currentBulkItem.max;

    input.value = val;
}

function setMaxQty() {
    document.getElementById('bulk-item-qty').value = currentBulkItem.max;
}

function closeBulkModal() {
    document.getElementById('bulk-use-modal').classList.remove('show');
    currentBulkItem = null;
}

function confirmBulkUse() {
    if (!currentBulkItem || !activePet) return;

    const qty = parseInt(document.getElementById('bulk-item-qty').value);

    // Eksekusi pakai item dengan Quantity
    useItem(currentBulkItem.id, activePet.id, qty);
    closeBulkModal();
}

// 4. Function Use Item (Kirim ke API)
async function useItem(itemId, targetPetId = 0, quantity = 1) {
    try {
        const response = await fetch(`${API_BASE}?action=use_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pet_id: targetPetId,
                item_id: itemId,
                quantity: quantity
            })
        });

        const data = await response.json();

        if (data.success) {
            // Cek apakah ini Gacha (Egg)
            if (data.is_gacha && data.gacha_data) {
                showGachaResult(data.gacha_data);
                showToast('Telur berhasil menetas!', 'success');
            } else {
                // Item Biasa - Show particle effects based on item type
                if (window.PetAnimations) {
                    // Determine effect type from item
                    if (data.effect_type === 'food' || data.hunger_restored) {
                        window.PetAnimations.food(quantity);
                        window.PetAnimations.jump();
                    } else if (data.effect_type === 'potion' || data.health_restored) {
                        window.PetAnimations.sparkles(6);
                        window.PetAnimations.lottie('healing', 1500);
                    } else if (data.effect_type === 'revive') {
                        window.PetAnimations.revive();
                        window.PetAnimations.lottie('sparkles', 2000);
                    }

                    // Show EXP if gained
                    if (data.exp_gained && data.exp_gained > 0) {
                        window.PetAnimations.showExp(data.exp_gained);
                    }
                }

                showToast(data.message, 'success');
            }

            // Tutup modal jika terbuka (termasuk modal quick use lama)
            if (document.getElementById('item-modal')) closeItemModal();

            // Refresh Data
            loadActivePet();
            loadInventory();
            loadPets();
        } else {
            showToast(data.error || 'Failed to use item', 'error');
        }
    } catch (error) {
        console.error('Error using item:', error);
    }
}

// ================================================
// REVIVE PET MODAL SYSTEM
// ================================================
let currentReviveItem = null;

function openReviveModal(itemId, itemName, itemImg) {
    // Filter only dead pets
    const deadPets = userPets.filter(pet => pet.status === 'DEAD');

    if (deadPets.length === 0) {
        showToast('Tidak ada pet yang mati!', 'info');
        return;
    }

    currentReviveItem = { id: itemId, name: itemName };

    // Build modal content
    const modal = document.getElementById('revive-modal');
    if (!modal) {
        // Fallback if modal doesn't exist
        showToast('Revive modal not found', 'error');
        return;
    }

    const grid = document.getElementById('revive-pet-grid');
    grid.innerHTML = deadPets.map(pet => `
        <div class="revive-pet-card" onclick="revivePet(${pet.id})">
            <img src="${getPetImagePath(pet)}" alt="${pet.nickname || pet.species_name}" 
                 onerror="this.src='../assets/placeholder.png'">
            <span class="revive-pet-name">${pet.nickname || pet.species_name}</span>
            <span class="revive-pet-level">Lv.${pet.level}</span>
            <span class="revive-status">💀 DEAD</span>
        </div>
    `).join('');

    document.getElementById('revive-item-name').textContent = itemName;
    modal.classList.add('show');
}

function closeReviveModal() {
    document.getElementById('revive-modal').classList.remove('show');
    currentReviveItem = null;
}

async function revivePet(petId) {
    if (!currentReviveItem) return;

    // Use the revive item on the selected dead pet
    await useItem(currentReviveItem.id, petId, 1);
    closeReviveModal();
}

// ================================================
// QUICK USE MODAL (Dari Dashboard Button)
// ================================================
function openItemModal(type) {
    if (!activePet) return;

    selectedItemType = type;
    const modal = document.getElementById('item-modal');
    const list = document.getElementById('item-list');

    const items = userInventory.filter(item => item.effect_type === type);

    if (items.length === 0) {
        list.innerHTML = `<div class="empty-message">No items! Visit shop.</div>`;
    } else {
        list.innerHTML = items.map(item => `
            <div class="item-option" onclick="useItem(${item.item_id}, ${activePet.id}, 1)">
                <img src="${ASSETS_BASE}${item.img_path}" onerror="this.src='../assets/placeholder.png'">
                <div class="item-option-name">${item.name}</div>
                <div class="item-option-qty">x${item.quantity}</div>
            </div>
        `).join('');
    }

    modal.classList.add('show');
}

function closeItemModal() {
    document.getElementById('item-modal').classList.remove('show');
    selectedItemType = null;
}

// ================================================
// GACHA SYSTEM
// ================================================
async function performGacha(type) {
    const egg = document.getElementById('gacha-egg');
    egg.classList.add('hatching');

    // Convert string type to numeric gacha type
    // 1 = Normal (all rarities), 2 = Rare+ guaranteed, 3 = Epic+ guaranteed (Premium)
    let gachaType = 1; // default to normal
    if (type === 'premium') {
        gachaType = 3; // Premium = Epic+ guaranteed
    } else if (type === 'rare') {
        gachaType = 2; // Rare+ guaranteed (if you want to add a middle tier)
    } else if (type === 'normal') {
        gachaType = 1; // Normal gacha
    }

    try {
        const response = await fetch(`${API_BASE}?action=gacha`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: gachaType })
        });

        const data = await response.json();

        setTimeout(() => {
            egg.classList.remove('hatching');

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
        egg.classList.remove('hatching');
        showToast('Network error', 'error');
    }
}

function showGachaResult(data) {
    const modal = document.getElementById('gacha-modal');
    const modalContent = modal.querySelector('.gacha-result-modal');
    const species = data.species;

    // Remove previous rarity classes
    modalContent.classList.remove('rarity-common', 'rarity-rare', 'rarity-epic', 'rarity-legendary');

    // Add current rarity class for styling
    modalContent.classList.add(`rarity-${data.rarity.toLowerCase()}`);

    // Update pet image
    document.getElementById('result-pet-img').src = ASSETS_BASE + (species.img_egg || 'default/egg.png');

    // Update pet name
    document.getElementById('result-name').textContent = species.name;

    // Update rarity badge
    const rarityBadge = document.getElementById('result-rarity');
    rarityBadge.textContent = data.rarity;
    rarityBadge.className = `rarity-badge-large ${data.rarity.toLowerCase()}`;

    // Update element display
    const elementEl = document.getElementById('result-element');
    if (elementEl && species.element) {
        elementEl.textContent = species.element;
    }

    // Update title based on rarity
    const titleEl = document.getElementById('result-title');
    const titles = {
        'Common': 'New Pet!',
        'Rare': 'Nice Pull!',
        'Epic': 'Amazing!',
        'Legendary': '🎉 LEGENDARY! 🎉'
    };
    const titleText = titles[data.rarity] || 'Congratulations!';
    titleEl.innerHTML = `<i class="fas fa-sparkles"></i><span>${titleText}</span>`;

    // Apply shiny
    const shinyBadge = document.getElementById('result-shiny');
    if (data.is_shiny) {
        document.getElementById('result-pet-img').style.filter = `hue-rotate(${data.shiny_hue}deg) drop-shadow(0 10px 40px rgba(0, 0, 0, 0.6))`;
        shinyBadge.style.display = 'flex';
    } else {
        document.getElementById('result-pet-img').style.filter = 'drop-shadow(0 10px 40px rgba(0, 0, 0, 0.6))';
        shinyBadge.style.display = 'none';
    }

    modal.classList.add('show');

    // Enhanced animation effects
    if (window.PetAnimations) {
        // Add reveal animation to pet image
        const resultPet = document.getElementById('result-pet-img');
        if (resultPet) {
            resultPet.style.animation = 'none';
            setTimeout(() => {
                resultPet.style.animation = 'pet-reveal 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            }, 10);
        }

        // NOTE: Lottie effects disabled to prevent modal resizing
        // Play confetti for rare+ pulls
        // const rarityLevel = { 'Common': 1, 'Rare': 2, 'Epic': 3, 'Legendary': 4 };
        // if (rarityLevel[data.rarity] >= 2) {
        //     window.PetAnimations.lottie('confetti', 3000, modalContent);
        // }
        // if (rarityLevel[data.rarity] >= 4) {
        //     window.PetAnimations.lottie('sparkles', 2500, modalContent);
        // }
    }
}

function closeGachaModal() {
    const modal = document.getElementById('gacha-modal');
    modal.classList.remove('show');

    // Reload pets to show new pet in collection
    loadPets();
}

// ================================================
// SHOP SYSTEM (Premium Enhanced)
// ================================================
function initShopTabs() {
    document.querySelectorAll('.shop-tab-pill').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.shop-tab-pill').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderShopItems(tab.dataset.shop);
        });
    });
}

// Initialize arena tabs (stub for now)
function initArenaTabs() {
    // TODO: Implement arena tab switching when arena modes are added
    console.log(' Arena tabs initialized (placeholder)');
}

async function loadShop() {
    try {
        const response = await fetch(`${API_BASE}?action=get_shop`);
        const data = await response.json();

        if (data.success) {
            shopItems = data.items || [];
            console.log('Shop loaded:', shopItems.length, 'items');
            updateGoldDisplay(data.user_gold);
            renderShopItems('food');
        } else {
            console.error('Shop load failed:', data.error);
        }
    } catch (error) {
        console.error('Error loading shop:', error);
    }
}

// Helper: Get category from effect type
function getItemCategory(effectType) {
    if (effectType === 'food') return 'food';
    if (effectType === 'potion' || effectType === 'revive') return 'potion';
    return 'special';
}

// Helper: Get icon for item based on name and type
function getItemIcon(itemName, effectType) {
    const name = itemName.toLowerCase();

    // Food items
    if (name.includes('kibble')) return 'fa-drumstick-bite';
    if (name.includes('feast')) return 'fa-turkey';
    if (name.includes('banquet')) return 'fa-crown';
    if (name.includes('fish')) return 'fa-fish';
    if (name.includes('meat')) return 'fa-bacon';

    // Potions
    if (name.includes('elixir')) return 'fa-flask';
    if (name.includes('vitality')) return 'fa-heart-pulse';
    if (name.includes('phoenix')) return 'fa-fire-flame-curved';
    if (name.includes('tears')) return 'fa-droplet';
    if (name.includes('health')) return 'fa-flask-vial';

    // Special items
    if (name.includes('exp') || name.includes('boost')) return 'fa-arrow-up';
    if (name.includes('gacha') || name.includes('ticket')) return 'fa-ticket';
    if (name.includes('shield')) return 'fa-shield-halved';
    if (name.includes('star')) return 'fa-star';
    if (name.includes('scroll')) return 'fa-scroll';
    if (name.includes('soul')) return 'fa-ghost';
    if (name.includes('ankh')) return 'fa-ankh';
    if (name.includes('wisdom')) return 'fa-book';

    // Category fallbacks
    const category = getItemCategory(effectType);
    if (category === 'food') return 'fa-drumstick-bite';
    if (category === 'potion') return 'fa-flask';
    return 'fa-star';
}

function renderShopItems(category) {
    const grid = document.getElementById('shop-grid');

    let filtered = shopItems;
    if (category === 'food') {
        filtered = shopItems.filter(i => i.effect_type === 'food');
    } else if (category === 'potion') {
        filtered = shopItems.filter(i => i.effect_type === 'potion' || i.effect_type === 'revive');
    } else if (category === 'special') {
        filtered = shopItems.filter(i => i.effect_type === 'exp_boost' || i.effect_type === 'gacha_ticket' || i.effect_type === 'shield');
    }

    if (filtered.length === 0) {
        grid.innerHTML = '<div class="shop-empty"><i class="fas fa-box-open"></i><br>No items in this category</div>';
        return;
    }

    grid.innerHTML = filtered.map(item => {
        const itemCategory = getItemCategory(item.effect_type);
        const icon = getItemIcon(item.name, item.effect_type);

        // Determine rarity based on price (simple heuristic)
        let rarity = 'common';
        if (item.price >= 1000) rarity = 'legendary';
        else if (item.price >= 500) rarity = 'epic';
        else if (item.price >= 200) rarity = 'rare';
        else if (item.price >= 100) rarity = 'uncommon';

        // Check if item is new (optional - add is_new field to DB if needed)
        const isNew = item.is_new || false;

        return `
            <div class="shop-item-card ${rarity}" onclick="buyItem(${item.id})">
                ${isNew ? '<span class="new-badge">NEW</span>' : ''}
                <div class="shop-item-icon-wrapper ${rarity}">
                    <i class="fas ${icon}"></i>
                </div>
                <h4 class="shop-item-name">${item.name}</h4>
                <p class="shop-item-desc">${item.description || ''}</p>
                <div class="shop-item-footer">
                    <div class="shop-item-price">
                        <i class="fas fa-coins"></i>
                        <span>${item.price}</span>
                    </div>
                    <button class="shop-buy-btn" onclick="event.stopPropagation(); buyItem(${item.id})">
                        <i class="fas fa-cart-plus"></i>
                        Buy
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// ================================================
// SHOP PURCHASE MODAL SYSTEM
// ================================================
let currentShopItem = null;

function buyItem(itemId) {
    // Find item in shopItems (convert to number for comparison)
    const searchId = parseInt(itemId);
    const item = shopItems.find(i => parseInt(i.id) === searchId);
    if (!item) {
        console.log('shopItems:', shopItems, 'searching for:', searchId);
        showToast('Item not found. Please refresh the page.', 'error');
        return;
    }

    currentShopItem = item;

    // Populate modal
    document.getElementById('shop-modal-img').src = ASSETS_BASE + (item.img_path || 'placeholder.png');
    document.getElementById('shop-modal-name').textContent = item.name;
    document.getElementById('shop-modal-desc').textContent = item.description;
    document.getElementById('shop-unit-price').textContent = item.price;
    document.getElementById('shop-qty-input').value = 1;
    updateShopTotal();

    // Show modal
    document.getElementById('shop-purchase-modal').classList.add('show');
}

function closeShopPurchaseModal() {
    document.getElementById('shop-purchase-modal').classList.remove('show');
    currentShopItem = null;
}

function adjustShopQty(amount) {
    const input = document.getElementById('shop-qty-input');
    let value = parseInt(input.value) || 1;
    value = Math.max(1, Math.min(99, value + amount));
    input.value = value;
    updateShopTotal();
}

function updateShopTotal() {
    if (!currentShopItem) return;

    const qty = parseInt(document.getElementById('shop-qty-input').value) || 1;
    const total = qty * currentShopItem.price;
    document.getElementById('shop-total-price').textContent = total.toLocaleString();
}

async function confirmShopPurchase() {
    if (!currentShopItem) return;

    const quantity = parseInt(document.getElementById('shop-qty-input').value) || 1;

    try {
        const response = await fetch(`${API_BASE}?action=buy_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: currentShopItem.id, quantity: quantity })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message || `Purchased ${quantity}x ${currentShopItem.name}!`, 'success');
            updateGoldDisplay(data.remaining_gold);
            loadInventory();
            closeShopPurchaseModal();
        } else {
            showToast(data.error || 'Purchase failed', 'error');
        }
    } catch (error) {
        console.error('Error buying item:', error);
        showToast('Network error', 'error');
    }
}

async function loadInventory() {
    try {
        const response = await fetch(`${API_BASE}?action=get_inventory`);
        const data = await response.json();

        if (data.success) {
            userInventory = data.inventory;
            renderInventory();
            updateInventoryCount(); // Update count display
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
    }
}

// Update inventory count display
function updateInventoryCount() {
    const countEl = document.getElementById('inventory-count');
    if (countEl) {
        const count = userInventory.length;
        countEl.textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
    }
}

// ================================================
// ARENA / BATTLE SYSTEM
// ================================================
function initArenaTabs() {
    document.querySelectorAll('.arena-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.arena-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const view = tab.dataset.view;
            document.getElementById('arena-opponents').style.display = view === 'opponents' ? 'block' : 'none';
            document.getElementById('arena-history').style.display = view === 'history' ? 'block' : 'none';

            if (view === 'opponents') {
                loadOpponents();
            } else {
                loadBattleHistory();
            }
        });
    });
}

async function loadOpponents() {
    const container = document.getElementById('arena-opponents');
    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Finding opponents...</p></div>';

    try {
        const response = await fetch(`${API_BASE}?action=get_opponents`);
        const data = await response.json();

        if (data.success && data.opponents.length > 0) {
            container.innerHTML = data.opponents.map(opp => `
                <div class="opponent-card">
                    <img src="${ASSETS_BASE}${opp.img_adult}" alt="${opp.species_name}" class="opponent-img"
                         onerror="this.src='../assets/placeholder.png'">
                    <div class="opponent-info">
                        <h3 class="opponent-name">${opp.display_name}</h3>
                        <p class="opponent-owner">Owner: ${opp.owner_name}</p>
                        <div class="opponent-stats">
                            <span class="element-badge ${opp.element.toLowerCase()}">${opp.element}</span>
                            <span class="pet-level">Lv.${opp.level}</span>
                        </div>
                    </div>
                    <button class="battle-btn" onclick="startBattle(${opp.pet_id})">
                        <i class="fas fa-bolt"></i> Fight
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-message">No opponents available right now. Check back later!</div>';
        }
    } catch (error) {
        console.error('Error loading opponents:', error);
        container.innerHTML = '<div class="empty-message">Failed to load opponents</div>';
    }
}

// Start turn-based battle - redirects to battle arena page
function startBattle(defenderPetId) {
    if (!activePet) {
        showToast('You need an active pet to battle!', 'warning');
        return;
    }

    if (activePet.status === 'DEAD') {
        showToast('Cannot battle with a dead pet!', 'error');
        return;
    }

    // Redirect to battle arena page
    window.location.href = `battle_arena.php?attacker_id=${activePet.id}&defender_id=${defenderPetId}`;
}

function showBattleResult(data) {
    const modal = document.getElementById('battle-modal');

    // Set title based on winner
    const title = document.getElementById('battle-title');
    if (data.winner_pet_id === data.attacker.pet_id) {
        title.textContent = '👑 Victory!';
        title.style.color = '#2ecc71';
    } else if (data.winner_pet_id === data.defender.pet_id) {
        title.textContent = '💀 Defeat';
        title.style.color = '#e74c3c';
    } else {
        title.textContent = '⚖️ Draw';
        title.style.color = '#f39c12';
    }

    // Set pet names and HP
    document.getElementById('battle-atk-name').textContent = data.attacker.name;
    document.getElementById('battle-atk-hp').textContent = data.attacker.final_hp;
    document.getElementById('battle-def-name').textContent = data.defender.name;
    document.getElementById('battle-def-hp').textContent = data.defender.final_hp;

    // Render battle log
    const logEl = document.getElementById('battle-log');
    logEl.innerHTML = data.battle_log.map(entry => `
        <div class="battle-log-entry">
            Round ${entry.round}: <strong>${entry.actor}</strong> ${entry.action}s for <span style="color: #e74c3c">${entry.damage}</span> damage!
        </div>
    `).join('');

    // Show rewards if won
    const rewardsEl = document.getElementById('battle-rewards');
    if (data.winner_pet_id === data.attacker.pet_id && data.rewards) {
        document.getElementById('reward-gold').textContent = data.rewards.gold;
        document.getElementById('reward-exp').textContent = data.rewards.exp;
        rewardsEl.style.display = 'flex';
    } else {
        rewardsEl.style.display = 'none';
    }

    modal.classList.add('show');
}

function closeBattleModal() {
    document.getElementById('battle-modal').classList.remove('show');
}

async function loadBattleHistory() {
    const container = document.getElementById('battle-history');

    try {
        const response = await fetch(`${API_BASE}?action=battle_history`);
        const data = await response.json();

        if (data.success && data.battles.length > 0) {
            container.innerHTML = data.battles.map(battle => {
                const date = new Date(battle.created_at).toLocaleDateString();
                // Logic winner/loser check simplified
                return `
                    <div class="battle-history-item">
                        <div class="battle-header">
                            <span class="battle-date">${date}</span>
                        </div>
                        <div class="battle-participants">
                            <span>${battle.atk_display}</span>
                            <span class="battle-vs">VS</span>
                            <span>${battle.def_display}</span>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<div class="empty-message">No battles yet. Challenge someone!</div>';
        }
    } catch (error) {
        console.error('Error loading battle history:', error);
    }
}

// ================================================
// UTILITY FUNCTIONS
// ================================================

// Gold formatting state (compact or full)
let isGoldCompact = true; // Default to compact on mobile

// Format gold with compact notation for mobile
function formatGold(amount, compact = true) {
    if (!compact) {
        return amount.toLocaleString();
    }

    // Compact formatting for large numbers
    if (amount >= 1000000) {
        return (amount / 1000000).toFixed(1) + 'M';
    } else if (amount >= 1000) {
        return (amount / 1000).toFixed(1) + 'K';
    }
    return amount.toLocaleString();
}

function updateGoldDisplay(gold) {
    const goldEl = document.getElementById('user-gold');
    if (!goldEl) return;

    if (gold !== undefined) {
        goldEl.textContent = formatGold(gold, isGoldCompact);
        goldEl.dataset.fullAmount = gold; // Store full amount
    } else {
        fetch(`${API_BASE}?action=get_shop`)
            .then(r => r.json())
            .then(d => {
                if (d.user_gold !== undefined) {
                    goldEl.textContent = formatGold(d.user_gold, isGoldCompact);
                    goldEl.dataset.fullAmount = d.user_gold;
                }
            });
    }
}

// Toggle gold display format on click
function initGoldToggle() {
    const goldEl = document.getElementById('user-gold');
    if (goldEl) {
        goldEl.style.cursor = 'pointer';
        goldEl.title = 'Click to toggle format';

        goldEl.addEventListener('click', (e) => {
            e.stopPropagation();
            isGoldCompact = !isGoldCompact;

            const amount = parseInt(goldEl.dataset.fullAmount || 0);
            goldEl.textContent = formatGold(amount, isGoldCompact);

            // Brief animation
            goldEl.style.transform = 'scale(1.1)';
            setTimeout(() => {
                goldEl.style.transform = 'scale(1)';
            }, 150);
        });
    }
}

function showToast(message, type = 'success') {
    // Get or create toast element
    let toast = document.getElementById('toast');

    if (!toast) {
        // Create toast dynamically if it doesn't exist
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        toast.innerHTML = '<i class="toast-icon fas"></i><span class="toast-message"></span>';
        document.body.appendChild(toast);
    }

    const icon = toast.querySelector('.toast-icon');
    const msg = toast.querySelector('.toast-message');

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-circle'
    };

    if (icon) icon.className = `toast-icon fas ${icons[type] || icons.success}`;
    if (msg) msg.textContent = message;

    toast.className = `toast ${type}`;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// ================================================
// LEADERBOARD
// ================================================
let currentLeaderboardCategory = 'top_level';

function initLeaderboardTabs() {
    document.querySelectorAll('.lb-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentLeaderboardCategory = tab.dataset.category;
            loadLeaderboard(currentLeaderboardCategory);
        });
    });

    // Also handle arena tab switching to show leaderboard
    document.querySelectorAll('.arena-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const view = tab.dataset.view;
            document.querySelectorAll('.arena-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            document.getElementById('arena-opponents').style.display = view === 'opponents' ? 'block' : 'none';
            document.getElementById('arena-history').style.display = view === 'history' ? 'block' : 'none';
            document.getElementById('arena-leaderboard').style.display = view === 'leaderboard' ? 'block' : 'none';

            if (view === 'leaderboard') {
                loadLeaderboard(currentLeaderboardCategory);
            }
        });
    });
}

async function loadLeaderboard(category = 'top_level') {
    const container = document.getElementById('leaderboard-list');
    container.innerHTML = `<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading...</p></div>`;

    try {
        const response = await fetch(`${API_BASE}?action=get_leaderboard&category=${category}`);
        const data = await response.json();

        if (data.success && data.leaderboard.length > 0) {
            renderLeaderboard(data.leaderboard, category);
        } else {
            container.innerHTML = '<div class="empty-message">No data available yet</div>';
        }
    } catch (error) {
        console.error('Error loading leaderboard:', error);
        container.innerHTML = '<div class="empty-message">Failed to load leaderboard</div>';
    }
}

function renderLeaderboard(entries, category) {
    const container = document.getElementById('leaderboard-list');

    const getRankIcon = (rank) => {
        if (rank === 1) return '👑';
        if (rank === 2) return '🥈';
        if (rank === 3) return '🥉';
        return `#${rank}`;
    };

    const getStatLabel = () => {
        switch (category) {
            case 'battle_wins': return 'Wins';
            case 'streak': return 'Logins';
            default: return 'Level';
        }
    };

    const getStatValue = (entry) => {
        switch (category) {
            case 'battle_wins': return entry.wins || 0;
            case 'streak': return entry.streak || 0;
            default: return entry.level;
        }
    };

    container.innerHTML = entries.map(entry => `
        <div class="lb-entry${entry.rank <= 3 ? ' rank-' + entry.rank : ''}">
            <div class="lb-rank${entry.rank <= 3 ? ' rank-' + entry.rank : ''}">${getRankIcon(entry.rank)}</div>
            <div class="lb-info">
                <div class="lb-pet-name">
                    ${entry.display_name}
                    <span class="rarity-badge ${entry.rarity}">${entry.rarity}</span>
                </div>
                <div class="lb-owner">
                    ${entry.owner_name} • <span class="sanctuary">${entry.sanctuary}</span>
                </div>
            </div>
            <div class="lb-stat">
                <div class="lb-stat-value">${getStatValue(entry)}</div>
                <div class="lb-stat-label">${getStatLabel()}</div>
            </div>
        </div>
    `).join('');
}

// Initialize leaderboard tabs when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initLeaderboardTabs();
    initAchievementTabs();
});

// ================================================
// ACHIEVEMENTS SYSTEM
// ================================================
let allAchievements = [];
let currentAchievementCategory = 'all';

function initAchievementTabs() {
    document.querySelectorAll('.ach-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.ach-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentAchievementCategory = tab.dataset.category;
            renderAchievements(allAchievements, currentAchievementCategory);
        });
    });
}

async function loadAchievements() {
    const container = document.getElementById('achievements-list');
    container.innerHTML = `<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading...</p></div>`;

    try {
        const response = await fetch(`${API_BASE}?action=get_achievements`);
        const data = await response.json();

        if (data.success) {
            allAchievements = data.achievements;
            document.getElementById('ach-unlocked').textContent = data.unlocked;
            document.getElementById('ach-total').textContent = data.total;
            renderAchievements(allAchievements, currentAchievementCategory);
        } else {
            container.innerHTML = `<div class="empty-message">${data.error || 'Failed to load achievements'}</div>`;
        }
    } catch (error) {
        console.error('Error loading achievements:', error);
        container.innerHTML = '<div class="empty-message">Failed to load achievements</div>';
    }
}

function renderAchievements(achievements, category = 'all') {
    const container = document.getElementById('achievements-list');

    // Filter by category
    const filtered = category === 'all'
        ? achievements
        : achievements.filter(a => a.category === category);

    if (filtered.length === 0) {
        container.innerHTML = '<div class="empty-message">No achievements in this category</div>';
        return;
    }

    // Sort: unlocked first, then by rarity
    const rarityOrder = { platinum: 0, gold: 1, silver: 2, bronze: 3 };
    filtered.sort((a, b) => {
        if (a.unlocked !== b.unlocked) return b.unlocked - a.unlocked;
        return rarityOrder[a.rarity] - rarityOrder[b.rarity];
    });

    container.innerHTML = filtered.map(ach => {
        const progress = Math.min(100, (ach.current_progress / ach.requirement_value) * 100);
        const statusClass = ach.unlocked ? 'unlocked' : 'locked';
        const checkIcon = ach.unlocked ? '✓' : '🔒';

        return `
        <div class="ach-card ${statusClass}">
            <div class="ach-icon">${ach.icon}</div>
            <div class="ach-info">
                <div class="ach-name">${ach.name}</div>
                <div class="ach-desc">${ach.description}</div>
                ${!ach.unlocked ? `
                <div class="ach-progress-bar">
                    <div class="ach-progress-fill" style="width: ${progress}%"></div>
                </div>
                ` : ''}
            </div>
            <div class="ach-rarity ${ach.rarity}">${ach.rarity}</div>
            <div class="ach-check">${checkIcon}</div>
        </div>
        `;
    }).join('');
}

// ================================================
// MODAL BACKDROP CLOSE
// ================================================
document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', () => {
        backdrop.closest('.modal').classList.remove('show');
    });
});

// ================================================
// ARENA 3v3 TEAM SELECTION
// ================================================
let selectedTeam3v3 = []; // Array of pet objects [pet1, pet2, pet3]
const MAX_TEAM_SIZE = 3;

/**
 * Load pets for 3v3 team selection
 */
async function loadPetsFor3v3() {
    const pool = document.getElementById('pet-pool-3v3');
    if (!pool) return;

    try {
        const response = await fetch(`${API_BASE}?action=get_pets`);
        const data = await response.json();

        if (data.success && data.pets.length > 0) {
            // Filter only alive pets
            const alivePets = data.pets.filter(p => p.status === 'ALIVE');

            if (alivePets.length < 3) {
                pool.innerHTML = `<div class="empty-message" style="grid-column: 1/-1;">
                    You need at least 3 alive pets for 3v3 battle.<br>
                    Currently: ${alivePets.length} alive pets.
                </div>`;
                return;
            }

            pool.innerHTML = alivePets.map(pet => `
                <div class="pet-pool-item" 
                     data-pet-id="${pet.id}" 
                     onclick="togglePetSelection(${pet.id}, this)">
                    <img src="${getPetImagePath(pet)}" 
                         alt="${pet.nickname || pet.species_name}"
                         onerror="this.src='../assets/placeholder.png'">
                    <div class="pet-level-badge">Lv.${pet.level}</div>
                </div>
            `).join('');

        } else {
            pool.innerHTML = '<div class="empty-message" style="grid-column: 1/-1;">No pets available</div>';
        }
    } catch (error) {
        console.error('Error loading pets for 3v3:', error);
    }
}

/**
 * Toggle pet selection for 3v3 team
 */
function togglePetSelection(petId, element) {
    const index = selectedTeam3v3.findIndex(p => p.id === petId);

    if (index > -1) {
        // Already selected, deselect
        selectedTeam3v3.splice(index, 1);
        element.classList.remove('selected');
    } else {
        // Not selected
        if (selectedTeam3v3.length >= MAX_TEAM_SIZE) {
            showToast('You can only select 3 pets!', 'warning');
            return;
        }

        // Find pet data from userPets array
        const pet = userPets.find(p => p.id === petId);
        if (pet) {
            selectedTeam3v3.push(pet);
            element.classList.add('selected');
        }
    }

    updateTeamSlots();
}

/**
 * Remove pet from team (click on slot)
 */
function removePetFromTeam(slotIndex) {
    if (selectedTeam3v3[slotIndex]) {
        const petId = selectedTeam3v3[slotIndex].id;

        // Remove from array
        selectedTeam3v3.splice(slotIndex, 1);

        // Update pool item
        const poolItem = document.querySelector(`.pet-pool-item[data-pet-id="${petId}"]`);
        if (poolItem) poolItem.classList.remove('selected');

        updateTeamSlots();
    }
}

/**
 * Update team slots display
 */
function updateTeamSlots() {
    const slots = document.querySelectorAll('.team-slot');

    slots.forEach((slot, i) => {
        if (selectedTeam3v3[i]) {
            const pet = selectedTeam3v3[i];
            slot.innerHTML = `
                <img src="${getPetImagePath(pet)}" alt="${pet.nickname || pet.species_name}"
                     onerror="this.src='../assets/placeholder.png'">
                <button class="remove-btn" onclick="event.stopPropagation(); removePetFromTeam(${i})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            slot.classList.remove('empty');
            slot.classList.add('filled');
            slot.onclick = () => removePetFromTeam(i);
        } else {
            slot.innerHTML = '<i class="fas fa-plus"></i>';
            slot.classList.add('empty');
            slot.classList.remove('filled');
            slot.onclick = null;
        }
    });

    // Show opponent selection if team is full
    const opponentSection = document.getElementById('opponent-selection-3v3');
    if (opponentSection) {
        if (selectedTeam3v3.length === MAX_TEAM_SIZE) {
            opponentSection.style.display = 'block';
            loadOpponents3v3();
        } else {
            opponentSection.style.display = 'none';
        }
    }
}

/**
 * Load opponents for 3v3 battle
 */
async function loadOpponents3v3() {
    const container = document.getElementById('opponent-list-3v3');
    if (!container) return;

    container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Finding opponents...</p></div>';

    try {
        const response = await fetch(`${API_BASE}?action=get_opponents_3v3`);
        const data = await response.json();

        if (data.success && data.opponents && data.opponents.length > 0) {
            container.innerHTML = data.opponents.map(opp => `
                <div class="opponent-row">
                    <div class="opponent-info">
                        <div class="opponent-name">${opp.name || opp.username}</div>
                        <div class="opponent-pets">
                            ${opp.pets.map(p => `
                                <img src="../assets/pets/${p.img_adult}" 
                                     alt="${p.species_name}"
                                     onerror="this.src='../assets/placeholder.png'">
                            `).join('')}
                        </div>
                    </div>
                    <button class="battle-3v3-btn" onclick="startBattle3v3(${opp.user_id})">
                        <i class="fas fa-dragon"></i> Battle!
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-message">No opponents with 3+ pets found. Try again later!</div>';
        }
    } catch (error) {
        console.error('Error loading 3v3 opponents:', error);
        container.innerHTML = '<div class="empty-message">Failed to load opponents</div>';
    }
}

/**
 * Start 3v3 battle
 */
async function startBattle3v3(opponentUserId) {
    if (selectedTeam3v3.length !== 3) {
        showToast('Select exactly 3 pets first!', 'warning');
        return;
    }

    const petIds = selectedTeam3v3.map(p => p.id);

    try {
        const response = await fetch(`${API_BASE}?action=start_battle_3v3`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pet_ids: petIds,
                opponent_user_id: opponentUserId
            })
        });

        const data = await response.json();

        if (data.success) {
            // Redirect to battle page
            window.location.href = `battle_3v3.php?battle_id=${data.battle_id}`;
        } else {
            showToast(data.error || 'Failed to start battle', 'error');
        }
    } catch (error) {
        console.error('Error starting 3v3 battle:', error);
        showToast('Network error', 'error');
    }
}

// Load 3v3 pets when arena3v3 tab is activated
document.addEventListener('DOMContentLoaded', () => {
    const arena3v3Tab = document.querySelector('.tab-btn[data-tab="arena3v3"]');
    if (arena3v3Tab) {
        arena3v3Tab.addEventListener('click', () => {
            selectedTeam3v3 = [];
            loadPetsFor3v3();
            updateTeamSlots();
        });
    }
});
// ================================================
// REVIVE MODAL FUNCTIONS
// ================================================

function openReviveModal(itemId, itemName, itemImg) {
    // Find revive item
    currentReviveItem = userInventory.find(i => i.item_id === itemId);
    if (!currentReviveItem) {
        showToast('Item not found', 'error');
        return;
    }

    // Get dead pets
    const deadPets = userPets.filter(pet => pet.status === 'DEAD');

    if (deadPets.length === 0) {
        showToast('No dead pets to revive!', 'info');
        return;
    }

    // Populate modal with dead pets
    const deadPetsList = document.getElementById('dead-pets-list');
    deadPetsList.innerHTML = deadPets.map(pet => {
        const imgPath = getPetImagePath(pet);
        const displayName = pet.nickname || pet.species_name;

        return `
            <div class="dead-pet-card" onclick="revivePet(${pet.id})">
                <img src="${imgPath}" alt="${displayName}" 
                     onerror="this.src=''../assets/placeholder.png''">
                <div class="dead-overlay"></div>
                <p>${displayName}</p>
                <span class="rarity-badge ${pet.rarity.toLowerCase()}">${pet.rarity}</span>
            </div>
        `;
    }).join('');

    // Show modal
    document.getElementById('revive-modal').classList.add('show');
}

function closeReviveModal() {
    document.getElementById('revive-modal').classList.remove('show');
    currentReviveItem = null;
}

async function revivePet(petId) {
    if (!currentReviveItem) return;

    // Use the revive item on the selected dead pet
    await useItem(currentReviveItem.item_id, petId, 1);
    closeReviveModal();
}
