<!-- Pet Leaderboard Tab -->
<div class="tab-content" id="tab-leaderboard" style="display: none;">
    <div class="leaderboard-container">

        <!-- Header -->
        <div class="leaderboard-header">
            <div class="lb-icon">🏆</div>
            <h2>PET LEADERBOARD</h2>
        </div>

        <!-- Filters -->
        <div class="leaderboard-filters">
            <select id="lb-sort" onchange="loadPetLeaderboard()">
                <option value="level">🏅 By Level</option>
                <option value="wins">⚔️ By Battle Wins</option>
                <option value="power">💪 By Power</option>
            </select>
            <select id="lb-element" onchange="loadPetLeaderboard()">
                <option value="all">🌈 All Elements</option>
                <!-- Populated by JS -->
            </select>
        </div>

        <!-- Leaderboard List -->
        <div class="leaderboard-list" id="leaderboard-list">
            <div class="loading-spinner">Loading...</div>
        </div>

    </div>
</div>