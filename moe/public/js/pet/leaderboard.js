import { API_BASE } from './config.js';
import { showToast } from './ui.js';
const ASSET_BASE = window.ASSET_BASE || '/School-Management-System/ci4_poc/public/';

/**
 * Pet Leaderboard Module
 * Handles loading, rendering, filtering, and pagination for pet champions
 */

// Global State for Leaderboard
let lbCurrentSort = 'rank';
let lbCurrentElement = 'all';
let lbCurrentPeriod = 'monthly';
let lbSearchQuery = '';
let lbOffset = 0;
const LB_LIMIT = 20;
let lbTotalCount = 0;
let lbIsLoading = false;
let lbCachedData = [];

/**
 * Initialize Leaderboard
 */
export function initLeaderboard() {
    const list = document.getElementById('leaderboard-list');
    if (!list) return;

    setupLBTabs();
    setupLBSearch();
    initSeasonTimer();
    loadPetLeaderboard(false);
    renderHallOfFame();
}

function setupLBTabs() {
    // Sort Tabs
    document.querySelectorAll('.lb-tab').forEach(tab => {
        tab.onclick = () => {
            document.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            lbCurrentSort = tab.dataset.sort;
            lbOffset = 0; // Reset pagination
            loadPetLeaderboard(false);
        };
    });

    // Element Pills
    const pillContainer = document.getElementById('element-pills');
    if (pillContainer) {
        pillContainer.onclick = (e) => {
            if (e.target.classList.contains('element-pill')) {
                document.querySelectorAll('.element-pill').forEach(p => p.classList.remove('active'));
                e.target.classList.add('active');
                lbCurrentElement = e.target.dataset.element;
                lbOffset = 0; // Reset pagination
                loadPetLeaderboard(false);
            }
        };
    }
}

function setupLBSearch() {
    const input = document.getElementById('lb-search');
    if (!input) return;

    input.addEventListener('input', (e) => {
        lbSearchQuery = e.target.value.toLowerCase();
        // Client-side filtering of current page
        filterAndRender();
    });
}

function initSeasonTimer() {
    const el = document.getElementById('season-countdown');
    if (!el) return;

    const now = new Date();
    const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);

    const updateTimer = () => {
        const diff = endOfMonth - new Date();
        if (diff <= 0) {
            el.textContent = "ENDED";
            return;
        }
        const d = Math.floor(diff / (1000 * 60 * 60 * 24));
        const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        el.textContent = `${d}d ${h}h ${m}m`;
    };

    updateTimer();
    setInterval(updateTimer, 60000);
}

/**
 * Load Leaderboard Data from API
 * @param {boolean} append - Whether to append data (Load More) or replace
 */
export async function loadPetLeaderboard(append = false) {
    if (lbIsLoading) return;
    lbIsLoading = true;

    const list = document.getElementById('leaderboard-list');
    const loadMoreContainer = document.getElementById('lb-load-more-container');

    if (!append) {
        list.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Summoning Champions...</span></div>';
        if (loadMoreContainer) loadMoreContainer.style.display = 'none';
    } else {
        const btn = document.querySelector('.lb-load-more-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        }
    }

    try {
        const url = `${API_BASE}leaderboard?sort=${lbCurrentSort}&element=${lbCurrentElement}&limit=${LB_LIMIT}&offset=${lbOffset}&t=${Date.now()}`;
        const response = await fetch(url);
        const data = await response.json();

        lbIsLoading = false;

        if (data.success) {
            const newData = data.leaderboard || [];
            lbTotalCount = data.total_count || 0;

            if (append) {
                lbCachedData = [...lbCachedData, ...newData];
            } else {
                lbCachedData = newData;
            }

            renderLB_Results(append);
            updateLoadMoreButton();

            // Populate element pills if not already done
            if (!append && data.elements) {
                renderElementPills(data.elements);
            }

        } else {
            if (!append) list.innerHTML = `<div class="empty-state">${data.error || 'Failed to load'}</div>`;
            showToast(data.error || 'Leaderboard error', 'error');
        }
    } catch (error) {
        lbIsLoading = false;
        console.error('Leaderboard Fetch Error:', error);
        if (!append) list.innerHTML = '<div class="empty-state">Connection Failed</div>';
    }
}

function renderElementPills(elements) {
    const container = document.getElementById('element-pills');
    if (!container) return;

    // Keep "All"
    const allPill = container.querySelector('[data-element="all"]');
    container.innerHTML = '';
    if (allPill) container.appendChild(allPill);

    elements.forEach(el => {
        const btn = document.createElement('button');
        btn.className = `element-pill ${lbCurrentElement === el ? 'active' : ''}`;
        btn.dataset.element = el;
        btn.textContent = el;
        container.appendChild(btn);
    });
}

function updateLoadMoreButton() {
    const loadMoreContainer = document.getElementById('lb-load-more-container');
    if (!loadMoreContainer) return;

    if (lbCachedData.length < lbTotalCount) {
        loadMoreContainer.style.display = 'block';
        loadMoreContainer.innerHTML = `<button class="lb-load-more-btn shimmer-active" onclick="loadMoreLeaderboard()">
            <i class="fas fa-plus-circle"></i>
            Summon More Champions (${lbTotalCount - lbCachedData.length} remaining)
        </button>`;
    } else {
        loadMoreContainer.style.display = 'none';
    }
}

window.loadMoreLeaderboard = function () {
    lbOffset += LB_LIMIT;
    loadPetLeaderboard(true);
};

function filterAndRender() {
    let filtered = lbCachedData;
    if (lbSearchQuery) {
        filtered = lbCachedData.filter(p =>
            (p.nickname && p.nickname.toLowerCase().includes(lbSearchQuery)) ||
            (p.owner_name && p.owner_name.toLowerCase().includes(lbSearchQuery)) ||
            (p.species_name && p.species_name.toLowerCase().includes(lbSearchQuery))
        );
    }

    renderLB_Podium(filtered.slice(0, 3));
    renderLB_List(filtered.slice(3), false); // Always replace when filtering
}

function renderLB_Results(append) {
    // Top 3 for Podium
    const podiumData = lbCachedData.slice(0, 3);
    const listData = lbCachedData.slice(3);

    if (!append) {
        renderLB_Podium(podiumData);
        renderLB_List(listData, false);
    } else {
        // If we appended, we only update the list, not the podium
        renderLB_List(listData, true);
    }
}

function renderLB_Podium(top3) {
    if (!top3 || top3.length === 0) return;

    // Map ranks to their podium spot IDs
    const rankMap = {
        1: 'podium-rank-1',
        2: 'podium-rank-2',
        3: 'podium-rank-3'
    };

    top3.forEach((pet, i) => {
        const rank = i + 1;
        const containerId = rankMap[rank];
        const container = document.getElementById(containerId);
        if (!container) return;

        const name = pet.nickname || pet.species_name;
        const img = ASSET_BASE + 'assets/pets/' + (pet.current_image || 'egg.png');
        const rp = pet.rank_points || 1000;
        const tierObj = pet.tier || { name: 'Bronze', color: '#CD7F32' };
        const tierImg = ASSET_BASE + 'assets/Tier/' + tierObj.name.toLowerCase() + '.png';

        let statValue, statLabel;
        if (lbCurrentSort === 'wins') {
            statValue = pet.total_wins || 0;
            statLabel = 'Wins';
        } else if (lbCurrentSort === 'level') {
            statValue = 'Lv.' + (pet.level || 1);
            statLabel = '';
        } else {
            statValue = rp;
            statLabel = 'RP';
        }

        container.innerHTML = `
            <div class="podium-member" onclick="openLeaderboardPetDetail(${pet.pet_id})">
                <div class="podium-avatar">
                    ${rank === 1 ? '<div class="podium-crown"></div>' : ''}
                    <img class="podium-img" src="${img}" onerror="this.onerror=null; this.src='${ASSET_BASE}assets/placeholder.png'">
                    <div class="tier-halo tier-${tierObj.name.toLowerCase()}"></div>
                </div>
                <div class="podium-info-box">
                    <div class="podium-name">${name}</div>
                    <div class="podium-owner">${pet.owner_name}</div>
                    <div class="podium-stat-badge">
                        <span class="val">${statValue}</span>
                        <span class="lab">${statLabel}</span>
                    </div>
                </div>
                <div class="podium-stand">
                    <img src="${tierImg}" alt="${tierObj.name}" class="tier-icon-small">
                    <span>${tierObj.name}</span>
                </div>
            </div>
        `;
    });
}

function renderLB_List(rest, append) {
    const container = document.getElementById('leaderboard-list');
    if (!container) return;

    if (rest.length === 0) {
        if (!append) container.innerHTML = '<div class="empty-state">No Champions Found</div>';
        return;
    }

    const html = rest.map((pet, i) => {
        const rank = i + 4;
        const name = pet.nickname || pet.species_name;
        const img = ASSET_BASE + 'assets/pets/' + (pet.current_image || 'egg.png');
        const elClass = (pet.element || '').toLowerCase();
        const rp = pet.rank_points || 1000;
        const tierObj = pet.tier || { name: 'Bronze', color: '#CD7F32' };
        const tierImg = ASSET_BASE + 'assets/Tier/' + tierObj.name.toLowerCase() + '.png';
        const tierClass = 'tier-' + tierObj.name.toLowerCase();

        let statValue, statLabel;
        if (lbCurrentSort === 'wins') {
            statValue = pet.total_wins || 0;
            statLabel = 'Wins';
        } else if (lbCurrentSort === 'level') {
            statValue = pet.level || 1;
            statLabel = 'Lv';
        } else {
            statValue = rp;
            statLabel = 'RP';
        }

        return `
            <div class="lb-pet-card ${tierClass}" onclick="openLeaderboardPetDetail(${pet.pet_id})">
                <div class="rank-section">
                    <img src="${tierImg}" alt="${tierObj.name}" class="tier-icon-medium">
                    <div class="rank">#${rank}</div>
                </div>
                <img class="pet-img" src="${img}" onerror="this.onerror=null; this.src='${ASSET_BASE}assets/placeholder.png'">
                
                <div class="pet-info">
                    <div class="pet-name ${pet.is_shiny ? 'shiny' : ''}">${name}</div>
                    <div class="pet-meta">
                        <span class="element-badge ${elClass}">${pet.element}</span>
                        <span class="owner">${pet.owner_name}</span>
                    </div>
                </div>
                
                <div class="pet-stats">
                    <div class="stat-main">${statValue}</div>
                    <div class="stat-label">${statLabel}</div>
                </div>
            </div>
        `;
    }).join('');

    if (append) {
        // If appending, we need a way to insert without overwriting. 
        // We use a temporary div or just append to innerHTML
        const temp = document.createElement('div');
        temp.innerHTML = html;
        while (temp.firstChild) {
            container.appendChild(temp.firstChild);
        }
    } else {
        container.innerHTML = html;
    }
}

/**
 * Handle Hall of Fame rendering
 */
export function renderHallOfFame() {
    const container = document.getElementById('hof-list');
    if (!container) return;

    container.innerHTML = '<div class="loading-spinner" style="padding:10px"><div class="spinner small"></div></div>';

    fetch(API_BASE + `leaderboard/fame?limit=6&t=${Date.now()}`)
        .then(res => res.json())
        .then(data => {
            const winners = data.hall_of_fame || [];
            if (winners.length === 0) {
                container.innerHTML = '<div style="padding:10px;color:#666;font-size:0.75rem">Coming Soon</div>';
            } else {
                container.innerHTML = winners.map(w => `
                    <div class="hof-item">
                        <img src="${ASSET_BASE}assets/pets/${w.current_image}" onerror="this.onerror=null; this.src='${ASSET_BASE}assets/placeholder.png'">
                        <div class="hof-month">${w.month_year || 'Past'}</div>
                        <div class="hof-name">${w.nickname || w.species_name}</div>
                    </div>
                `).join('');
            }
        })
        .catch(() => {
            container.innerHTML = '<div style="padding:10px;color:#666;font-size:0.75rem">Coming Soon</div>';
        });
}

/**
 * Toggle Hall of Fame Visibility
 */
window.toggleHallOfFame = function () {
    const content = document.getElementById('hof-content');
    const icon = document.getElementById('hof-toggle-icon');
    if (!content) return;

    if (content.style.display === 'none') {
        content.style.display = 'block';
        if (icon) icon.className = 'fas fa-chevron-up';
        renderHallOfFame();
    } else {
        content.style.display = 'none';
        if (icon) icon.className = 'fas fa-chevron-down';
    }
};

/**
 * Detail Modal Integration
 */
window.openLeaderboardPetDetail = function (petId) {
    const pet = lbCachedData.find(p => p.pet_id == petId || p.id == petId);
    if (!pet) return;

    const modalPet = {
        id: pet.pet_id,
        nickname: pet.nickname,
        species_name: pet.species_name,
        element: pet.element,
        rarity: pet.rarity,
        level: pet.level,
        is_shiny: pet.is_shiny,
        shiny_hue: pet.shiny_hue || 0,
        evolution_stage: pet.evolution_stage,
        current_image: pet.current_image,
        base_health: pet.base_health || 120,
        base_attack: pet.base_attack,
        base_defense: pet.base_defense,
        status: 'ALIVE',
        is_active: false
    };

    if (window.openPetDetail) {
        window.openPetDetail(modalPet);
        setTimeout(() => {
            const btn = document.getElementById('detail-set-active-btn');
            if (btn) btn.style.display = 'none';
        }, 50);
    }
};

window.initLeaderboard = initLeaderboard;
window.loadPetLeaderboard = loadPetLeaderboard;
