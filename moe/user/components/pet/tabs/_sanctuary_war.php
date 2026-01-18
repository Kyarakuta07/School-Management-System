<!-- Sanctuary War Tab -->
<div class="tab-content" id="tab-war" style="display: none;">
    <div class="war-container">

        <!-- War Status Header -->
        <div class="war-header">
            <div class="war-icon">⚔️</div>
            <h2>SANCTUARY WAR</h2>
            <div class="war-timer" id="war-timer"></div>
        </div>

        <!-- War Not Active State -->
        <div id="war-inactive" class="war-inactive">
            <div class="inactive-icon">🗓️</div>
            <h3>No War Active</h3>
            <p>Wars happen every Saturday!</p>
            <p class="next-war">Next war: <span id="next-war-date">-</span></p>
        </div>

        <!-- War Active State -->
        <div id="war-active" class="war-active" style="display: none;">

            <!-- Your Sanctuary -->
            <div class="your-sanctuary">
                <span class="label">Fighting for:</span>
                <span class="sanctuary-name" id="your-sanctuary-name">-</span>
            </div>

            <!-- Tickets -->
            <div class="tickets-display">
                <div class="tickets-label">Battle Tickets</div>
                <div class="tickets-icons" id="tickets-display">
                    <span class="ticket">🎟️</span>
                    <span class="ticket">🎟️</span>
                    <span class="ticket">🎟️</span>
                </div>
            </div>

            <!-- Standings -->
            <div class="war-standings">
                <h3>🏆 Current Standings</h3>
                <div class="standings-list" id="standings-list">
                    <!-- Populated by JS -->
                </div>
            </div>

            <!-- Your Contribution -->
            <div class="your-contribution">
                <h4>Your Contribution</h4>
                <div class="contribution-stats">
                    <div class="stat">
                        <span class="value" id="your-points">0</span>
                        <span class="label">Points</span>
                    </div>
                    <div class="stat">
                        <span class="value" id="your-wins">0</span>
                        <span class="label">Wins</span>
                    </div>
                    <div class="stat">
                        <span class="value" id="your-battles">0</span>
                        <span class="label">Battles</span>
                    </div>
                </div>
            </div>

            <!-- Battle Button -->
            <button class="war-battle-btn" id="war-battle-btn" onclick="startWarBattle()">
                <i class="fas fa-sword"></i>
                ⚔️ FIGHT FOR YOUR SANCTUARY
            </button>

        </div>

        <!-- Battle Result Modal -->
        <div class="war-result-modal" id="war-result-modal" style="display: none;">
            <div class="result-content">
                <div class="result-icon" id="result-icon">⚔️</div>
                <h2 id="result-title">Victory!</h2>
                <div class="result-details">
                    <p>vs <span id="result-opponent"></span></p>
                    <p class="sanctuary-badge" id="result-opponent-sanctuary"></p>
                </div>
                <div class="result-rewards">
                    <div class="reward">
                        <span class="icon">⭐</span>
                        <span id="result-points">+3</span> pts
                    </div>
                    <div class="reward">
                        <span class="icon">🪙</span>
                        <span id="result-gold">+5</span> gold
                    </div>
                </div>
                <button class="close-result-btn" onclick="closeWarResult()">Continue</button>
            </div>
        </div>

    </div>
</div>