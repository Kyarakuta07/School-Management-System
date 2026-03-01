/**
 * Sanctuary War V3 - Supreme Glassmorphism Edition
 */

let warState = {
    active: false,
    warId: null,
    ticketsRemaining: 0,
    ticketsTotal: 3
};

/**
 * Initialize Sanctuary War tab
 */
function initSanctuaryWar() {
    console.log('[SanctuaryWar V3] Initiating Supreme Experience...');
    loadWarStatus();

    // Check for battle result in URL
    const params = new URLSearchParams(window.location.search);
    if (params.has('battle_won')) {
        const won = params.get('battle_won') === 'true';
        const points = params.get('points') || (won ? 3 : 0);
        showWarResult({
            won: won,
            points: points,
            gold_earned: won ? 25 : 5
        });

        // Clean URL
        const newUrl = window.location.pathname + window.location.search.replace(/&?battle_won=[^&]*/, '').replace(/&?points=[^&]*/, '');
        window.history.replaceState({}, document.title, newUrl);
    }
}

/**
 * Load current war status from API
 */
async function loadWarStatus() {
    try {
        const apiBase = (typeof window.API_BASE !== 'undefined') ? window.API_BASE : '/api/';
        const response = await fetch(apiBase + 'war/status');
        const data = await response.json();

        if (data && data.success) {
            updateWarUI(data);
            warState.active = data.is_active || false;
            warState.warId = data.war_id || null;
            warState.ticketsRemaining = data.tickets_remaining || 0;
        } else {
            console.error('[SanctuaryWar V3] Sync Failed:', data ? data.error : 'Void');
        }
    } catch (error) {
        console.error('[SanctuaryWar V3] Error:', error);
    }
}

/**
 * Update UI with war data (V3)
 */
function updateWarUI(data) {
    if (!data) return;

    const inactiveContainer = document.getElementById('war-inactive-container');
    const activeContainer = document.getElementById('war-active-container');
    const loadingOverlay = document.getElementById('war-v3-loading');
    const contentInner = document.getElementById('war-v3-content');

    if (data.is_active) {
        if (inactiveContainer) inactiveContainer.style.display = 'none';
        if (activeContainer) activeContainer.style.display = 'block';

        // Champion
        const champEl = document.getElementById('champ-name');
        if (champEl && data.champion) {
            champEl.textContent = data.champion.nama_sanctuary;
        }

        // Tickets V3
        updateTicketsDisplay(data.tickets_remaining || 0, 3);

        // Timer V3
        if (data.ends_at) {
            initWarTimer(data.ends_at);
        }

        // Standings V3
        updateStandings(data.standings || []);

        // Stats V3
        const ptsEl = document.getElementById('your-points');
        const winsEl = document.getElementById('your-wins');
        const battlesEl = document.getElementById('your-battles');

        if (data.your_contribution) {
            if (ptsEl) ptsEl.textContent = data.your_contribution.points || 0;
            if (winsEl) winsEl.textContent = data.your_contribution.wins || 0;
            if (battlesEl) battlesEl.textContent = data.your_contribution.total_battles || 0;
        }

        const sancNameEl = document.getElementById('your-sanctuary-name');
        if (sancNameEl && data.your_sanctuary) {
            sancNameEl.textContent = data.your_sanctuary.nama_sanctuary;
        }

        // Button V3
        const battleBtn = document.getElementById('war-battle-btn');
        if (battleBtn) {
            if (data.tickets_remaining <= 0) {
                battleBtn.disabled = true;
                battleBtn.textContent = 'QUOTA DEPLETED';
            } else {
                battleBtn.disabled = false;
                battleBtn.textContent = 'ENTER BATTLEFIELD';
            }
        }

    } else {
        if (inactiveContainer) inactiveContainer.style.display = 'block';
        if (activeContainer) activeContainer.style.display = 'none';

        const nextWarEl = document.getElementById('next-war-date');
        if (nextWarEl && data.next_war) {
            nextWarEl.textContent = `Next Alignment: ${data.next_war}`;
        }

        // Recap V3
        if (data.has_recap && data.recap) {
            renderLastWarRecap(data.recap);
        }
    }

    // Reveal UI
    if (loadingOverlay) {
        loadingOverlay.style.opacity = '0';
        setTimeout(() => {
            loadingOverlay.style.visibility = 'hidden';
            if (contentInner) contentInner.style.opacity = '1';
        }, 500);
    } else if (contentInner) {
        contentInner.style.opacity = '1';
    }
}

/**
 * Update ticket icons (V3)
 */
function updateTicketsDisplay(remaining, total) {
    const container = document.getElementById('tickets-display');
    if (!container) return;

    let html = '';
    for (let i = 0; i < total; i++) {
        const isUsed = i >= remaining;
        html += `<div class="v3-token ${isUsed ? 'used' : ''}">🎫</div>`;
    }
    container.innerHTML = html;
}

/**
 * Render standings list (V3)
 */
function updateStandings(standings) {
    const container = document.getElementById('standings-list');
    if (!container) return;

    if (!standings || standings.length === 0) {
        container.innerHTML = '<div class="v3-empty-state">No historical skirmishes found.</div>';
        return;
    }

    container.innerHTML = standings.map((s, i) => `
        <div class="v3-row ${i === 0 ? 'rank-1' : ''} v3-fade-up v3-row-animated" style="--d: ${i * 0.1}s">
            <div class="v3-row-rank">${i + 1}</div>
            <div class="v3-row-name">${s.nama_sanctuary}</div>
            <div class="v3-row-pts">
                <span class="val">${s.total_points}</span>
                <span class="lbl">POINTS</span>
            </div>
        </div>
    `).join('');
}

/**
 * Timer logic (V3)
 */
function initWarTimer(endsAt) {
    const timerEl = document.getElementById('war-timer-val');
    if (!timerEl) return;

    const safeEndsAt = endsAt.replace(/-/g, "/");
    const targetDate = new Date(safeEndsAt).getTime();

    if (isNaN(targetDate)) return;

    if (window.warTimerInterval) clearInterval(window.warTimerInterval);

    const updateTimer = () => {
        const now = new Date().getTime();
        const diff = targetDate - now;

        if (diff <= 0) {
            timerEl.textContent = '⏰ WAR CONCLUDED';
            if (window.warTimerInterval) clearInterval(window.warTimerInterval);
            return;
        }

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        timerEl.textContent = `⏰ CLOSING IN: ${hours}h ${minutes}m ${seconds}s`;
    };

    updateTimer();
    window.warTimerInterval = setInterval(updateTimer, 1000);
}

/**
 * Start a war battle (V3)
 */
async function startWarBattle() {
    if (!warState.active) {
        window.showToast('Gate is sealed.', 'error');
        return;
    }
    if (warState.ticketsRemaining <= 0) {
        window.showToast('Your energy is depleted.', 'info');
        return;
    }

    const btn = document.getElementById('war-battle-btn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'MATCHING...';
    }

    try {
        const apiBase = (typeof window.API_BASE !== 'undefined') ? window.API_BASE : '/api/';
        const response = await fetchWithCsrf(apiBase + 'war/start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        if (data && data.success) {
            const attackerId = data.attacker_pet.id;
            const defenderId = data.defender_pet.id;
            const warId = data.war_id;
            const assetRoot = (typeof window.ASSET_BASE !== 'undefined' ? window.ASSET_BASE : '/');
            window.location.href = `${assetRoot}battle-war?attacker_id=${attackerId}&defender_id=${defenderId}&war_id=${warId}`;
        } else {
            window.showToast(data ? data.error : 'Failed to breach', 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'ENTER BATTLEFIELD';
            }
        }
    } catch (error) {
        console.error('[SanctuaryWar V3] Error:', error);
        window.showToast('Celestial connection failure.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'ENTER BATTLEFIELD';
        }
    }
}

/**
 * Result Modal (V3)
 */
function showWarResult(result) {
    if (!result) return;
    const modal = document.getElementById('war-result-modal');
    if (!modal) return;

    const iconEl = document.getElementById('result-icon');
    const titleEl = document.getElementById('result-title');

    if (result.won) {
        if (iconEl) iconEl.textContent = '👑';
        if (titleEl) {
            titleEl.textContent = 'VICTORY';
            titleEl.classList.add('won');
        }
    } else {
        if (iconEl) iconEl.textContent = '🛡️';
        if (titleEl) {
            titleEl.textContent = 'DEFEAT';
            titleEl.classList.add('lost');
        }
    }

    const ptsEl = document.getElementById('result-points');
    const goldEl = document.getElementById('result-gold');
    if (ptsEl) ptsEl.textContent = (result.won ? '+' : '') + (result.points || 0);
    if (goldEl) goldEl.textContent = '+' + (result.gold_earned || 0);

    modal.style.display = 'flex';
}

/**
 * Close V3 Result
 */
function closeWarResult() {
    const modal = document.getElementById('war-result-modal');
    if (modal) modal.style.display = 'none';
    loadWarStatus();
}

/**
 * Render V3 Recap
 */
function renderLastWarRecap(recap) {
    if (!recap) return;
    const recapEl = document.getElementById('last-war-recap');
    if (!recapEl) return;

    recapEl.style.display = 'grid'; // V3 recap grid

    if (recap.champion) {
        const nameEl = document.getElementById('recap-champion-name');
        const scoreEl = document.getElementById('recap-champion-score');
        if (nameEl) nameEl.textContent = recap.champion.nama_sanctuary;
        if (scoreEl) scoreEl.textContent = recap.champion.total_points + ' PTS';
    }

    const listEl = document.getElementById('recap-standings-list');
    if (listEl && recap.standings) {
        listEl.innerHTML = recap.standings.map((s, i) => `
            <div class="mini-row-v2">
                <span class="name">#${i + 1} ${s.nama_sanctuary}</span>
                <span class="pts">${s.total_points}</span>
            </div>
        `).join('');
    }
}

// Expose
window.startWarBattle = startWarBattle;
window.closeWarResult = closeWarResult;
window.initSanctuaryWar = initSanctuaryWar;
