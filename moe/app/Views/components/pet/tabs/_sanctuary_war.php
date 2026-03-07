<!-- Sanctuary War Tab V3 - Supreme Glassmorphism -->
<div class="tab-content" id="tab-war" style="display: none;">

    <!-- Atmospheric Layers -->
    <div class="war-v3-backdrop" aria-hidden="true">
        <div class="nebula-cloud"></div>
        <div class="nebula-cloud nebula-cloud-2"></div>
        <div class="stardust-layer"></div>
    </div>

    <main class="war-v3-container">

        <!-- V3 Loading Shield -->
        <div id="war-v3-loading" class="v3-loading-overlay">
            <div class="v3-loader-ring"></div>
            <div class="v3-loader-text">SYNCING COSMIC RESONANCE...</div>
        </div>

        <div id="war-v3-content" class="war-v3-inner" style="opacity: 0; transition: opacity 0.4s ease;">

            <!-- 🟢 ACTIVE WAR STATE (V3 DASHBOARD) -->
            <section id="war-active-container" class="war-view-state" style="display: none;">

                <!-- V3 Champion Hero Module -->
                <header class="glass-tile-v3 v3-champ-hero v3-fade-up">
                    <span class="v3-prestige-label">LEGENDARY CHAMPION SANCTUARY</span>
                    <h2 id="champ-name" class="v3-champ-name">...</h2>
                    <div class="v3-timer-display" id="war-timer-val">⏰ SYNCHRONIZING...</div>
                </header>

                <div class="v3-war-dashboard">

                    <!-- Left: Majestic Rankings Module -->
                    <article class="glass-tile-v3 v3-rankings-module v3-fade-up">
                        <header class="v3-module-header">
                            <h3>🏆 Sanctuary League</h3>
                            <div class="allegiance-pill">
                                ALIGNED: <span id="your-sanctuary-name">...</span>
                            </div>
                        </header>
                        <div class="v3-standings-scroll" id="standings-list">
                            <!-- JS renders .v3-row here -->
                        </div>
                    </article>

                    <!-- Right: Combat Command Center -->
                    <div class="v3-stats-stack">

                        <!-- Battle Readiness Module -->
                        <article class="glass-tile-v3 v3-stat-tile v3-battle-ready v3-fade-up">
                            <span class="v3-prestige-label">BATTLE READINESS</span>
                            <div class="v3-ticket-row" id="tickets-display">
                                <!-- JS renders .v3-token icons -->
                            </div>
                            <button id="war-battle-btn" class="v3-btn-battle" onclick="startWarBattle()">
                                ENTER BATTLEFIELD
                            </button>
                        </article>

                        <!-- Personal Contribution Module -->
                        <article class="glass-tile-v3 v3-stat-tile v3-contribution v3-fade-up">
                            <span class="v3-prestige-label">YOUR CONTRIBUTION</span>
                            <div class="contrib-grid-v2">
                                <div class="contrib-item-v2">
                                    <span class="num" id="your-points">0</span>
                                    <span class="lbl">TOTAL PTS</span>
                                </div>
                                <div class="contrib-item-v2">
                                    <span class="num" id="your-wins">0</span>
                                    <span class="lbl">VICTORIES</span>
                                </div>
                                <div class="contrib-item-v2">
                                    <span class="num" id="your-battles">0</span>
                                    <span class="lbl">BATTLES</span>
                                </div>
                            </div>
                        </article>

                    </div>
                </div>
            </section>

            <!-- 🔴 INACTIVE WAR STATE (V3 COSMIC ALIGNMENT) -->
            <section id="war-inactive-container" class="war-view-state" style="display: none;">
                <div class="glass-tile-v3 v3-inactive-hero v3-fade-up">

                    <div class="v3-portal-visual">
                        <div class="v3-portal-ring"></div>
                        <div class="v3-portal-ring"></div>
                        <div class="v3-portal-ring"></div>
                        <div class="v3-portal-core">
                            <img src="<?= asset_v('assets/Tier/master.png') ?>" alt="Cosmic Gate">
                        </div>
                    </div>

                    <h2>Sanctuary War</h2>
                    <p class="v3-inactive-desc">
                        The cosmic ley lines are currently dormant. Steel your resolve and strengthen your bonds as we
                        await
                        the next celestial alignment.
                    </p>

                    <div class="v3-next-alignment" id="next-war-date">Next Alignment: Loading...</div>

                    <!-- V3 Recap Dashboard -->
                    <div id="last-war-recap" class="v3-recap-grid" style="display: none;">
                        <article class="glass-tile-v3 recap-card-v2">
                            <span class="recap-badge">SUPREME VICTOR</span>
                            <div class="recap-hero">
                                <div class="crown">👑</div>
                                <div class="name" id="recap-champion-name">...</div>
                                <div class="recap-score" id="recap-champion-score">0 PTS</div>
                            </div>
                        </article>

                        <article class="glass-tile-v3 standings-panel-v2">
                            <h3 class="v3-legacy-title">LEAGUE LEGACY</h3>
                            <div id="recap-standings-list">
                                <!-- JS renders .mini-row-v2 -->
                            </div>
                        </article>
                    </div>

                </div>
            </section>

        </div>

        <!-- V3 Result Modal (Supreme Glass) -->
        <div class="war-result-modal" id="war-result-modal" style="display: none;">
            <div class="glass-tile-v3 v3-result-content v3-fade-up">
                <div id="result-icon" class="v3-result-icon">🎉</div>
                <h2 id="result-title" class="v3-result-modal-title">VICTORY</h2>

                <div class="v3-result-rewards">
                    <div class="glass-tile-v3 reward-item">
                        <span class="reward-val" id="result-points">+3</span>
                        <span class="reward-lbl">Sanctuary Points</span>
                    </div>
                    <div class="glass-tile-v3 reward-item">
                        <span class="reward-val" id="result-gold">+25</span>
                        <span class="reward-lbl">Gold Earned</span>
                    </div>
                </div>

                <button class="v3-btn-battle v3-btn-continue" onclick="closeWarResult()">CONTINUE ASCENSION</button>
            </div>
        </div>

    </main>
</div>