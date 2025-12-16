<?php
/**
 * MOE Pet System - Battle State Manager
 * Dragon City-style 3v3 turn-based combat
 * 
 * Manages battle state in PHP sessions.
 * Does NOT persist to database during battle (too heavy).
 * Only saves final result to database.
 */

// Load constants
require_once __DIR__ . '/constants.php';

class BattleStateManager
{
    /**
     * Initialize a new 3v3 battle
     * 
     * @param int $user_id Current user ID
     * @param array $player_pets Array of 3 pet data arrays
     * @param array $enemy_pets Array of 3 enemy pet data arrays
     * @return array Battle state with battle_id
     */
    public function initBattle(int $user_id, array $player_pets, array $enemy_pets): array
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate unique battle ID
        $battle_id = uniqid('battle_', true);

        // Calculate max HP for each pet (100 + level * 5)
        $player_team = [];
        foreach ($player_pets as $index => $pet) {
            $max_hp = 100 + ((int) ($pet['level'] ?? 1) * 5);
            $player_team[] = [
                'pet_id' => (int) $pet['id'],
                'species_id' => (int) $pet['species_id'],
                'species_name' => $pet['species_name'] ?? 'Unknown',
                'nickname' => $pet['nickname'] ?? $pet['species_name'],
                'element' => $pet['element'] ?? 'Fire',
                'level' => (int) ($pet['level'] ?? 1),
                'base_attack' => (int) ($pet['base_attack'] ?? 10),
                'base_defense' => (int) ($pet['base_defense'] ?? 10),
                'img_adult' => $pet['img_adult'] ?? 'default.png',
                'hp' => $max_hp,
                'max_hp' => $max_hp,
                'is_fainted' => false
            ];
        }

        $enemy_team = [];
        foreach ($enemy_pets as $index => $pet) {
            $max_hp = 100 + ((int) ($pet['level'] ?? 1) * 5);
            $enemy_team[] = [
                'pet_id' => (int) $pet['id'],
                'species_id' => (int) $pet['species_id'],
                'species_name' => $pet['species_name'] ?? 'Unknown',
                'nickname' => $pet['nickname'] ?? $pet['species_name'],
                'element' => $pet['element'] ?? 'Fire',
                'level' => (int) ($pet['level'] ?? 1),
                'base_attack' => (int) ($pet['base_attack'] ?? 10),
                'base_defense' => (int) ($pet['base_defense'] ?? 10),
                'img_adult' => $pet['img_adult'] ?? 'default.png',
                'hp' => $max_hp,
                'max_hp' => $max_hp,
                'is_fainted' => false
            ];
        }

        // Initialize battle state in session
        $battle_state = [
            'battle_id' => $battle_id,
            'user_id' => $user_id,
            'player_pets' => $player_team,
            'enemy_pets' => $enemy_team,
            'current_turn' => 'player',
            'turn_count' => 1,
            'active_player_index' => 0,
            'active_enemy_index' => 0,
            'status' => 'active',
            'created_at' => time(),
            'logs' => ['Battle started! Choose your attack!']
        ];

        // Store in session
        if (!isset($_SESSION['battles'])) {
            $_SESSION['battles'] = [];
        }
        $_SESSION['battles'][$battle_id] = $battle_state;

        return $battle_state;
    }

    /**
     * Get current battle state
     * 
     * @param string $battle_id Battle ID
     * @return array|null Battle state or null if not found/expired
     */
    public function getState(string $battle_id): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['battles'][$battle_id])) {
            return null;
        }

        $state = $_SESSION['battles'][$battle_id];

        // Check if battle expired
        if (time() - $state['created_at'] > BATTLE_3V3_SESSION_TIMEOUT) {
            $this->endBattle($battle_id);
            return null;
        }

        return $state;
    }

    /**
     * Apply damage to a pet
     * 
     * @param string $battle_id Battle ID
     * @param string $target_team 'player' or 'enemy'
     * @param int $target_index Index in team (0-2)
     * @param int $damage Damage amount
     * @return array Updated battle state
     */
    public function applyDamage(string $battle_id, string $target_team, int $target_index, int $damage): array
    {
        $state = $this->getState($battle_id);
        if (!$state) {
            return ['error' => 'Battle not found'];
        }

        $team_key = $target_team === 'player' ? 'player_pets' : 'enemy_pets';

        if (!isset($state[$team_key][$target_index])) {
            return ['error' => 'Invalid target index'];
        }

        // Apply damage
        $state[$team_key][$target_index]['hp'] -= $damage;

        // Check if fainted
        if ($state[$team_key][$target_index]['hp'] <= 0) {
            $state[$team_key][$target_index]['hp'] = 0;
            $state[$team_key][$target_index]['is_fainted'] = true;

            // Auto-switch to next alive pet
            $this->autoSwitchFaintedPet($state, $target_team);
        }

        // Check for battle end
        $this->checkBattleEnd($state);

        // Update session
        $_SESSION['battles'][$battle_id] = $state;

        return $state;
    }

    /**
     * Switch turn
     * 
     * @param string $battle_id Battle ID
     * @return array Updated battle state
     */
    public function switchTurn(string $battle_id): array
    {
        $state = $this->getState($battle_id);
        if (!$state) {
            return ['error' => 'Battle not found'];
        }

        // Toggle turn
        $state['current_turn'] = $state['current_turn'] === 'player' ? 'enemy' : 'player';

        // Increment turn count when it's player's turn again
        if ($state['current_turn'] === 'player') {
            $state['turn_count']++;
        }

        // Check max turns
        if ($state['turn_count'] > BATTLE_3V3_MAX_TURNS) {
            $state['status'] = 'draw';
        }

        // Update session
        $_SESSION['battles'][$battle_id] = $state;

        return $state;
    }

    /**
     * Switch active pet
     * 
     * @param string $battle_id Battle ID
     * @param string $team 'player' or 'enemy'
     * @param int $new_index New pet index
     * @return array Updated battle state
     */
    public function switchActivePet(string $battle_id, string $team, int $new_index): array
    {
        $state = $this->getState($battle_id);
        if (!$state) {
            return ['error' => 'Battle not found'];
        }

        $team_key = $team === 'player' ? 'player_pets' : 'enemy_pets';
        $index_key = $team === 'player' ? 'active_player_index' : 'active_enemy_index';

        // Validate new index
        if (!isset($state[$team_key][$new_index])) {
            return ['error' => 'Invalid pet index'];
        }

        // Check if pet is alive
        if ($state[$team_key][$new_index]['is_fainted']) {
            return ['error' => 'Cannot switch to fainted pet'];
        }

        // Switch
        $state[$index_key] = $new_index;

        // Log
        $pet_name = $state[$team_key][$new_index]['nickname'] ?? $state[$team_key][$new_index]['species_name'];
        $state['logs'][] = ($team === 'player' ? 'You' : 'Enemy') . " switched to {$pet_name}!";

        // Update session
        $_SESSION['battles'][$battle_id] = $state;

        return $state;
    }

    /**
     * Auto-switch when pet faints
     */
    private function autoSwitchFaintedPet(array &$state, string $team): void
    {
        $team_key = $team === 'player' ? 'player_pets' : 'enemy_pets';
        $index_key = $team === 'player' ? 'active_player_index' : 'active_enemy_index';

        // Find next alive pet
        for ($i = 0; $i < 3; $i++) {
            if (!$state[$team_key][$i]['is_fainted']) {
                $state[$index_key] = $i;
                return;
            }
        }
    }

    /**
     * Check if battle has ended
     */
    private function checkBattleEnd(array &$state): void
    {
        // Check if all player pets fainted
        $player_alive = false;
        foreach ($state['player_pets'] as $pet) {
            if (!$pet['is_fainted']) {
                $player_alive = true;
                break;
            }
        }

        // Check if all enemy pets fainted
        $enemy_alive = false;
        foreach ($state['enemy_pets'] as $pet) {
            if (!$pet['is_fainted']) {
                $enemy_alive = true;
                break;
            }
        }

        if (!$player_alive) {
            $state['status'] = 'defeat';
            $state['logs'][] = 'All your pets have fainted! You lost the battle.';
        } elseif (!$enemy_alive) {
            $state['status'] = 'victory';
            $state['logs'][] = 'All enemy pets have fainted! You won the battle!';
        }
    }

    /**
     * Get active pets for both teams
     */
    public function getActivePets(string $battle_id): ?array
    {
        $state = $this->getState($battle_id);
        if (!$state) {
            return null;
        }

        return [
            'player' => $state['player_pets'][$state['active_player_index']],
            'enemy' => $state['enemy_pets'][$state['active_enemy_index']]
        ];
    }

    /**
     * Add log entry
     */
    public function addLog(string $battle_id, string $message): void
    {
        if (isset($_SESSION['battles'][$battle_id])) {
            $_SESSION['battles'][$battle_id]['logs'][] = $message;
        }
    }

    /**
     * End battle and clean up session
     * 
     * @param string $battle_id Battle ID
     */
    public function endBattle(string $battle_id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['battles'][$battle_id])) {
            unset($_SESSION['battles'][$battle_id]);
        }
    }

    /**
     * Validate that user owns the battle
     */
    public function validateOwnership(string $battle_id, int $user_id): bool
    {
        $state = $this->getState($battle_id);
        if (!$state) {
            return false;
        }

        return $state['user_id'] === $user_id;
    }
}
