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

            <!-- Last War Recap (Hidden by default) -->
            <div id="last-war-recap" class="last-war-recap" style="display: none;">
                <div class="recap-divider"></div>

                <div class="recap-header">
                    <span class="recap-badge">LAST WAR RESULT</span>
                    <span class="recap-date" id="recap-date">-</span>
                </div>

                <!-- Champion -->
                <div class="champion-card rank-1">
                    <div class="champion-glow"></div>
                    <div class="crown-icon">🏆</div>
                    <div class="champion-label">VICTORIOUS SANCTUARY</div>
                    <div class="champion-name" id="recap-champion-name">-</div>
                    <div class="champion-score"><span id="recap-champion-score">0</span> Points</div>
                </div>

                <!-- MVP -->
                <div class="mvp-section">
                    <div class="section-title">🎖️ WAR MVP</div>
                    <div class="mvp-card">
                        <div class="mvp-avatar-container">
                            <img src="" onerror="this.src='../assets/img/defaults/profile_default.png'"
                                class="mvp-avatar" id="recap-mvp-avatar">
                            <div class="mvp-badge">MVP</div>
                        </div>
                        <div class="mvp-info">
                            <div class="mvp-name" id="recap-mvp-name">-</div>
                            <div class="mvp-sanctuary" id="recap-mvp-sanctuary">-</div>
                            <div class="mvp-stats-row">
                                <div class="mvp-stat">
                                    <span class="icon">⚔️</span>
                                    <span class="val" id="recap-mvp-wins">0</span>
                                    <span class="lbl">Wins</span>
                                </div>
                                <div class="mvp-stat">
                                    <span class="icon">⭐</span>
                                    <span class="val" id="recap-mvp-points">0</span>
                                    <span class="lbl">Pts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="war-stats-grid">
                    <div class="mini-stat">
                        <span class="val" id="recap-total-participants">0</span>
                        <span class="lbl">Participants</span>
                    </div>
                    <div class="mini-stat">
                        <span class="val" id="recap-total-battles">0</span>
                        <span class="lbl">Battles</span>
                    </div>
                    <div class="mini-stat">
                        <span class="val" id="recap-total-gold">0</span>
                        <span class="lbl">Gold Looted</span>
                    </div>
                </div>

                <!-- Full Standings -->
                <div class="recap-standings">
                    <div class="section-title">FINAL STANDINGS</div>
                    <div class="standings-list" id="recap-standings-list">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
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