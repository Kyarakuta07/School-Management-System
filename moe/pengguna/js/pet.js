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
let userPets = [];
let activePet = null;
let shopItems = [];
let userInventory = [];
let selectedItemType = null;
let currentBulkItem = null; // State untuk modal bulk use

// ================================================
// INITIALIZATION
// ================================================
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initActionButtons();
    initShopTabs();
    initArenaTabs();
    loadPets();
    checkDailyReward(); // Check for daily login reward
});

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

    // Update content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.toggle('active', content.id === tab);
    });

    currentTab = tab;

    // Load tab-specific data
    switch (tab) {
        case 'my-pet':
            loadActivePet();
            break;
        case 'collection':
            loadPets();
            break;
        case 'shop':
            loadShop();
            loadInventory();
            break;
        case 'arena':
            loadOpponents();
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
            userPets = data.pets;
            renderCollection();

            // Find and display active pet
            activePet = userPets.find(p => p.is_active);
            if (activePet) {
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
        } else {
            showNoPetMessage();
        }
    } catch (error) {
        console.error('Error loading active pet:', error);
    }
}

function renderActivePet() {
    const stage = document.getElementById('pet-stage');
    const info = document.getElementById('pet-info');
    const actions = document.getElementById('action-buttons');

    if (!activePet) {
        showNoPetMessage();
        return;
    }

    // Build pet display
    const imgPath = getPetImagePath(activePet);
    const shinyClass = activePet.is_shiny ? 'shiny' : '';
    const shinyStyle = activePet.is_shiny ? `filter: hue-rotate(${activePet.shiny_hue}deg);` : '';

    stage.innerHTML = `
        <div class="pet-status-indicator ${activePet.status.toLowerCase()}">${activePet.status}</div>
        <div class="pet-glow"></div>
        <div class="pet-display">
            <img src="${imgPath}" alt="${activePet.species_name}" 
                 class="pet-image ${shinyClass}" 
                 style="${shinyStyle}"
                 onerror="this.src='../assets/placeholder.png'">
        </div>
    `;

    // Pet name and level
    const displayName = activePet.nickname || activePet.species_name;
    document.getElementById('pet-name').textContent = displayName;
    document.getElementById('pet-level').textContent = `Lv.${activePet.level}`;

    // Element and rarity badges
    const elementDiv = document.getElementById('pet-element');
    elementDiv.innerHTML = `
        <span class="element-badge ${activePet.element.toLowerCase()}">${activePet.element}</span>
        <span class="rarity-badge ${activePet.rarity.toLowerCase()}">${activePet.rarity}</span>
    `;

    // Status bars
    updateStatusBar('health', activePet.health);
    updateStatusBar('hunger', activePet.hunger);
    updateStatusBar('mood', activePet.mood);

    // EXP bar
    const expNeeded = Math.floor(100 * Math.pow(1.2, activePet.level - 1));
    const expPercent = (activePet.exp / expNeeded) * 100;
    document.getElementById('exp-bar').style.width = `${expPercent}%`;
    document.getElementById('exp-text').textContent = `${activePet.exp} / ${expNeeded}`;

    // Show elements
    info.style.display = 'block';
    actions.style.display = 'grid';

    // Update shelter button text
    const shelterBtn = document.getElementById('btn-shelter');
    if (activePet.status === 'SHELTER') {
        shelterBtn.querySelector('span').textContent = 'Retrieve';
        shelterBtn.querySelector('i').className = 'fas fa-door-open';
    } else {
        shelterBtn.querySelector('span').textContent = 'Shelter';
        shelterBtn.querySelector('i').className = 'fas fa-home';
    }
}

function updateStatusBar(type, value) {
    const bar = document.getElementById(`${type}-bar`);
    const valueEl = document.getElementById(`${type}-value`);

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
    const stage = pet.evolution_stage || getEvolutionStage(pet.level);
    let imgKey;

    switch (stage) {
        case 'egg': imgKey = 'img_egg'; break;
        case 'baby': imgKey = 'img_baby'; break;
        case 'adult': imgKey = 'img_adult'; break;
        default: imgKey = 'img_egg';
    }

    return ASSETS_BASE + (pet[imgKey] || pet.current_image || 'default/egg.png');
}

function getEvolutionStage(level) {
    if (level < 5) return 'egg';
    if (level < 15) return 'baby';
    return 'adult';
}

// ================================================
// COLLECTION TAB (With Retrieve Button)
// ================================================
function renderCollection() {
    const grid = document.getElementById('collection-grid');

    if (userPets.length === 0) {
        grid.innerHTML = `
            <div class="empty-message">
                <p>No pets yet! Visit the Gacha tab to get your first companion.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = userPets.map(pet => {
        const imgPath = getPetImagePath(pet);
        const displayName = pet.nickname || pet.species_name;
        const activeClass = pet.is_active ? 'active' : '';
        const deadClass = pet.status === 'DEAD' ? 'dead' : '';
        const shinyStyle = pet.is_shiny ? `filter: hue-rotate(${pet.shiny_hue}deg);` : '';

        // --- TOMBOL RETRIEVE ---
        let actionButtonHTML = '';
        if (pet.status === 'SHELTER') {
            actionButtonHTML = `
                <button class="shop-buy-btn" style="margin-top: 8px; background: linear-gradient(135deg, #3498db, #2980b9); color: white; width: 100%; border: none;">
                    <i class="fas fa-box-open"></i> Retrieve
                </button>
            `;
        }

        return `
            <div class="pet-card ${activeClass} ${deadClass}" onclick="selectPet(${pet.id})">
                <span class="rarity-badge ${pet.rarity.toLowerCase()}">${pet.rarity.charAt(0)}</span>
                <img src="${imgPath}" alt="${pet.species_name}" class="pet-card-img" 
                     style="${shinyStyle}"
                     onerror="this.src='../assets/placeholder.png'">
                <h3 class="pet-card-name">${displayName}</h3>
                <span class="pet-card-level">Lv.${pet.level} ${pet.is_shiny ? '✨' : ''}</span>
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
            loadPets();
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
    showToast('You played with ' + (activePet.nickname || activePet.species_name) + '! 🎵', 'success');
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

// 1. Render Inventory agar Bisa Diklik
function renderInventory() {
    const grid = document.getElementById('inventory-grid');

    if (userInventory.length === 0) {
        grid.innerHTML = '<p class="empty-message">No items yet</p>';
        return;
    }

    grid.innerHTML = userInventory.map(item => `
        <div class="inventory-item" title="${item.name}" 
             onclick="handleInventoryClick(
                 ${item.item_id}, 
                 '${item.effect_type}', 
                 '${item.name.replace(/'/g, "\\'")}', 
                 '${item.description ? item.description.replace(/'/g, "\\'") : ''}', 
                 '${item.img_path}', 
                 ${item.quantity}
             )"
             style="cursor: pointer;">
             
            <img src="${ASSETS_BASE}${item.img_path}" alt="${item.name}" class="inventory-item-img"
                 onerror="this.src='../assets/placeholder.png'">
            <span class="inventory-item-qty">${item.quantity}</span>
        </div>
    `).join('');
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

    // Cek Pet Aktif (Untuk item konsumsi)
    if (!activePet) {
        showToast('Kamu butuh Active Pet untuk menggunakan item ini!', 'warning');
        return;
    }
    if (type !== 'revive' && activePet.status === 'DEAD') {
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
    document.getElementById('bulk-item-name').textContent = itemName;
    document.getElementById('bulk-item-desc').textContent = itemDesc;
    document.getElementById('bulk-item-img').src = ASSETS_BASE + itemImg;
    document.getElementById('bulk-item-stock').textContent = maxQty;
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
                // Item Biasa
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

    try {
        const response = await fetch(`${API_BASE}?action=gacha`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: type })
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
    const species = data.species;

    document.getElementById('result-pet-img').src = ASSETS_BASE + (species.img_egg || 'default/egg.png');
    document.getElementById('result-name').textContent = species.name;

    const rarityBadge = document.getElementById('result-rarity');
    rarityBadge.textContent = data.rarity;
    rarityBadge.className = `result-rarity rarity-badge ${data.rarity.toLowerCase()}`;

    // Apply shiny
    if (data.is_shiny) {
        document.getElementById('result-pet-img').style.filter = `hue-rotate(${data.shiny_hue}deg)`;
        document.getElementById('result-shiny').style.display = 'block';
    } else {
        document.getElementById('result-pet-img').style.filter = '';
        document.getElementById('result-shiny').style.display = 'none';
    }

    const glow = document.getElementById('result-glow');
    const glowColors = {
        'Common': 'rgba(158, 158, 158, 0.5)',
        'Rare': 'rgba(33, 150, 243, 0.5)',
        'Epic': 'rgba(156, 39, 176, 0.5)',
        'Legendary': 'rgba(255, 152, 0, 0.5)'
    };
    glow.style.background = `radial-gradient(circle, ${glowColors[data.rarity] || glowColors.Common} 0%, transparent 70%)`;

    modal.classList.add('show');
}

function closeGachaModal() {
    document.getElementById('gacha-modal').classList.remove('show');
}

// ================================================
// SHOP SYSTEM
// ================================================
function initShopTabs() {
    document.querySelectorAll('.shop-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.shop-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderShopItems(tab.dataset.category);
        });
    });
}

async function loadShop() {
    try {
        const response = await fetch(`${API_BASE}?action=get_shop`);
        const data = await response.json();

        if (data.success) {
            shopItems = data.items;
            updateGoldDisplay(data.user_gold);
            renderShopItems('food');
        }
    } catch (error) {
        console.error('Error loading shop:', error);
    }
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
        grid.innerHTML = '<div class="empty-message">No items in this category</div>';
        return;
    }

    grid.innerHTML = filtered.map(item => `
        <div class="shop-item">
            <img src="${ASSETS_BASE}${item.img_path}" alt="${item.name}" class="shop-item-img"
                 onerror="this.src='../assets/placeholder.png'">
            <h4 class="shop-item-name">${item.name}</h4>
            <p class="shop-item-desc">${item.description}</p>
            <div class="shop-item-price">
                <i class="fas fa-coins"></i>
                <span>${item.price}</span>
            </div>
            <button class="shop-buy-btn" onclick="buyItem(${item.id})">Buy</button>
        </div>
    `).join('');
}

async function buyItem(itemId) {
    try {
        const response = await fetch(`${API_BASE}?action=buy_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: itemId, quantity: 1 })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            updateGoldDisplay(data.remaining_gold);
            loadInventory();
        } else {
            showToast(data.error || 'Purchase failed', 'error');
        }
    } catch (error) {
        console.error('Error buying item:', error);
    }
}

async function loadInventory() {
    try {
        const response = await fetch(`${API_BASE}?action=get_inventory`);
        const data = await response.json();

        if (data.success) {
            userInventory = data.inventory;
            renderInventory();
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
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
function updateGoldDisplay(gold) {
    if (gold !== undefined) {
        document.getElementById('user-gold').textContent = gold.toLocaleString();
    } else {
        fetch(`${API_BASE}?action=get_shop`)
            .then(r => r.json())
            .then(d => {
                if (d.user_gold !== undefined) {
                    document.getElementById('user-gold').textContent = d.user_gold.toLocaleString();
                }
            });
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = toast.querySelector('.toast-icon');
    const msg = toast.querySelector('.toast-message');

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-circle'
    };

    icon.className = `toast-icon fas ${icons[type] || icons.success}`;
    msg.textContent = message;
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
});

// ================================================
// MODAL BACKDROP CLOSE
// ================================================
document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', () => {
        backdrop.closest('.modal').classList.remove('show');
    });
});