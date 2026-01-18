/**
 * MOE Pet System - Sanctuary War JavaScript
 * Handles war status, battles, and UI updates
 */

// War state
let warState = {
    active: false,
    warId: null,
    yourSanctuary: null,
    ticketsRemaining: 3,
    standings: [],
    contribution: { points: 0, wins: 0, battles: 0 }
};

/**
 * Initialize Sanctuary War tab
 */
function initSanctuaryWar() {
    loadWarStatus();
}

/**
 * Load current war status from API
 */
async function loadWarStatus() {
    try {
        const response = await fetch('api/router.php?action=get_war_status');
        const data = await response.json();

        if (data.success) {
            updateWarUI(data.data);
        } else {
            console.error('Failed to load war status:', data.error);
        }
    } catch (error) {
        console.error('Error loading war status:', error);
    }
}

/**
 * Update war UI based on status
 */
function updateWarUI(data) {
    const inactiveEl = document.getElementById('war-inactive');
    const activeEl = document.getElementById('war-active');

    if (!data.war_active) {
        // War not active
        warState.active = false;
        inactiveEl.style.display = 'block';
        activeEl.style.display = 'none';

        document.getElementById('next-war-date').textContent = data.next_war;
        return;
    }

    // War is active
    warState.active = true;
    warState.warId = data.war_id;
    warState.yourSanctuary = data.your_sanctuary;
    warState.ticketsRemaining = data.tickets_remaining;
    warState.standings = data.standings;
    warState.contribution = data.your_contribution;

    inactiveEl.style.display = 'none';
    activeEl.style.display = 'block';

    // Update sanctuary name
    document.getElementById('your-sanctuary-name').textContent =
        warState.yourSanctuary?.nama_sanctuary || 'Unknown';

    // Update tickets
    updateTicketsDisplay(data.tickets_remaining, data.tickets_total);

    // Update standings
    updateStandings(data.standings);

    // Update contribution
    document.getElementById('your-points').textContent = data.your_contribution.points;
    document.getElementById('your-wins').textContent = data.your_contribution.wins;
    document.getElementById('your-battles').textContent = data.your_contribution.battles;

    // Update timer
    updateWarTimer(data.ends_at);

    // Update battle button
    const battleBtn = document.getElementById('war-battle-btn');
    if (data.tickets_remaining <= 0) {
        battleBtn.disabled = true;
        battleBtn.textContent = '😴 No Tickets Remaining';
    } else {
        battleBtn.disabled = false;
        battleBtn.innerHTML = '⚔️ FIGHT FOR YOUR SANCTUARY';
    }
}

/**
 * Update tickets display
 */
function updateTicketsDisplay(remaining, total) {
    const container = document.getElementById('tickets-display');
    let html = '';

    for (let i = 0; i < total; i++) {
        if (i < remaining) {
            html += '<span class="ticket">🎟️</span>';
        } else {
            html += '<span class="ticket used">🎟️</span>';
        }
    }

    container.innerHTML = html;
}

/**
 * Update standings list
 */
function updateStandings(standings) {
    const container = document.getElementById('standings-list');

    if (!standings || standings.length === 0) {
        container.innerHTML = '<p style="text-align:center;color:#666;">No scores yet</p>';
        return;
    }

    const icons = ['🥇', '🥈', '🥉', '#4'];

    container.innerHTML = standings.map((s, i) => `
        <div class="standing-row ${i < 3 ? 'rank-' + (i + 1) : ''}">
            <span class="rank">${icons[i] || '#' + (i + 1)}</span>
            <span class="sanctuary">${s.nama_sanctuary}</span>
            <span class="points">${s.total_points} pts</span>
        </div>
    `).join('');
}

/**
 * Update war timer
 */
function updateWarTimer(endsAt) {
    const timerEl = document.getElementById('war-timer');

    const updateTimer = () => {
        const now = new Date();
        const end = new Date(endsAt);
        const diff = end - now;

        if (diff <= 0) {
            timerEl.textContent = 'War Ended!';
            return;
        }

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        timerEl.textContent = `⏰ Ends in: ${hours}h ${minutes}m`;
    };

    updateTimer();
    setInterval(updateTimer, 60000); // Update every minute
}

/**
 * Start a war battle
 */
async function startWarBattle() {
    if (!warState.active || warState.ticketsRemaining <= 0) {
        showToast('No battle tickets remaining!', 'error');
        return;
    }

    const battleBtn = document.getElementById('war-battle-btn');
    battleBtn.disabled = true;
    battleBtn.textContent = '⚔️ Finding opponent...';

    try {
        const response = await fetch('api/router.php?action=war_battle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        if (data.success) {
            showWarResult(data.data);
        } else {
            showToast(data.error || 'Battle failed', 'error');
            battleBtn.disabled = false;
            battleBtn.innerHTML = '⚔️ FIGHT FOR YOUR SANCTUARY';
        }
    } catch (error) {
        console.error('Battle error:', error);
        showToast('Battle failed. Try again!', 'error');
        battleBtn.disabled = false;
        battleBtn.innerHTML = '⚔️ FIGHT FOR YOUR SANCTUARY';
    }
}

/**
 * Show war result modal
 */
function showWarResult(result) {
    const modal = document.getElementById('war-result-modal');
    const iconEl = document.getElementById('result-icon');
    const titleEl = document.getElementById('result-title');

    const winner = result.battle_result.winner;

    if (winner === 'user') {
        iconEl.textContent = '🎉';
        titleEl.textContent = 'Victory!';
        titleEl.className = 'victory';
    } else if (winner === 'opponent') {
        iconEl.textContent = '😞';
        titleEl.textContent = 'Defeat';
        titleEl.className = 'defeat';
    } else {
        iconEl.textContent = '🤝';
        titleEl.textContent = 'Draw!';
        titleEl.className = 'tie';
    }

    document.getElementById('result-opponent').textContent =
        result.opponent.pet_name + ' (' + result.opponent.name + ')';
    document.getElementById('result-opponent-sanctuary').textContent =
        result.opponent.sanctuary;
    document.getElementById('result-points').textContent = '+' + result.points_earned;
    document.getElementById('result-gold').textContent = '+' + result.gold_earned;

    modal.style.display = 'flex';

    // Update tickets remaining
    warState.ticketsRemaining = result.tickets_remaining;
}

/**
 * Close war result modal and refresh
 */
function closeWarResult() {
    document.getElementById('war-result-modal').style.display = 'none';
    loadWarStatus(); // Refresh war status
}

// Expose to global scope
window.startWarBattle = startWarBattle;
window.closeWarResult = closeWarResult;
window.initSanctuaryWar = initSanctuaryWar;
