<?php
/**
 * MOE Pet System - Battle Controller
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles battle-related endpoints:
 * - get_opponents: Get available opponents
 * - battle: Initiate battle
 * - battle_result: Get battle result
 * - battle_history: Get battle history
 * - get_buff: Get buff from active pet
 * - play_finish: Handle play session completion
 * - get_leaderboard: Get battle leaderboard
 */

require_once __DIR__ . '/../BaseController.php';

class BattleController extends BaseController
{
    /**
     * GET: Get available opponents
     */
    public function getOpponents()
    {
        $this->requireGet();

        $result = getOpponents($this->conn, $this->user_id);
        echo json_encode($result);
    }

    /**
     * POST: Initiate battle
     */
    public function battle()
    {
        $this->requirePost();

        $input = $this->getInput();
        $opponent_id = isset($input['opponent_id']) ? (int) $input['opponent_id'] : 0;

        if (!$opponent_id) {
            $this->error('Opponent ID required');
            return;
        }

        // Rate limit - 20 battles per hour
        $this->checkRateLimit('battle', 20, 60);

        $result = initiateBattle($this->conn, $this->user_id, $opponent_id);
        echo json_encode($result);
    }

    /**
     * POST: Get battle result
     */
    public function battleResult()
    {
        $this->requirePost();

        $input = $this->getInput();
        $battle_id = isset($input['battle_id']) ? (int) $input['battle_id'] : 0;
        $won = isset($input['won']) ? (bool) $input['won'] : false;

        if (!$battle_id) {
            $this->error('Battle ID required');
            return;
        }

        $result = processBattleResult($this->conn, $this->user_id, $battle_id, $won);
        echo json_encode($result);
    }

    /**
     * GET: Get battle history
     */
    public function battleHistory()
    {
        $this->requireGet();

        $limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 10;

        // Get battles where user's pet was the attacker
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT pb.id, pb.attacker_pet_id, pb.defender_pet_id, pb.winner_pet_id, 
                    pb.battle_type, pb.attacker_exp_gained, pb.gold_reward, pb.created_at,
                    ps.name as pet_name, ps.element as pet_element,
                    def_ps.name as opponent_name, def_ps.element as opponent_element,
                    (pb.winner_pet_id = pb.attacker_pet_id) as won
             FROM pet_battles pb
             JOIN user_pets up ON pb.attacker_pet_id = up.id
             JOIN pet_species ps ON up.species_id = ps.id
             LEFT JOIN user_pets def_up ON pb.defender_pet_id = def_up.id
             LEFT JOIN pet_species def_ps ON def_up.species_id = def_ps.id
             WHERE up.user_id = ?
             ORDER BY pb.created_at DESC
             LIMIT ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $this->user_id, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $battles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $battles[] = $row;
        }
        mysqli_stmt_close($stmt);

        $this->success(['history' => $battles]);
    }

    /**
     * GET: Get buff from active pet
     */
    public function getBuff()
    {
        $this->requireGet();

        $result = getActivePetBuff($this->conn, $this->user_id);
        echo json_encode($result);
    }

    /**
     * POST: Handle play session completion
     */
    public function playFinish()
    {
        $this->requirePost();

        $input = $this->getInput();
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;
        $play_type = isset($input['play_type']) ? $input['play_type'] : 'ball';
        $duration = isset($input['duration']) ? (int) $input['duration'] : 30;

        if (!$pet_id) {
            $this->error('Pet ID required');
            return;
        }

        $result = finishPlaySession($this->conn, $this->user_id, $pet_id, $play_type, $duration);
        echo json_encode($result);
    }

    /**
     * GET: Get leaderboard
     */
    public function getLeaderboard()
    {
        $this->requireGet();

        $type = isset($_GET['type']) ? $_GET['type'] : 'wins';
        $limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 20;

        if ($type === 'wins') {
            $query = "SELECT n.username, n.nama_lengkap, 
                             COUNT(CASE WHEN pb.winner_pet_id = pb.attacker_pet_id THEN 1 END) as wins,
                             COUNT(*) as total_battles
                      FROM pet_battles pb
                      JOIN user_pets up ON pb.attacker_pet_id = up.id
                      JOIN nethera n ON up.user_id = n.id_nethera
                      GROUP BY up.user_id
                      ORDER BY wins DESC
                      LIMIT ?";
        } else {
            // Just return wins leaderboard for streak too (no streak column exists)
            $query = "SELECT n.username, n.nama_lengkap, 
                             COUNT(CASE WHEN pb.winner_pet_id = pb.attacker_pet_id THEN 1 END) as max_streak
                      FROM pet_battles pb
                      JOIN user_pets up ON pb.attacker_pet_id = up.id
                      JOIN nethera n ON up.user_id = n.id_nethera
                      GROUP BY up.user_id
                      ORDER BY max_streak DESC
                      LIMIT ?";
        }

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $leaderboard = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $leaderboard[] = $row;
        }
        mysqli_stmt_close($stmt);

        $this->success([
            'type' => $type,
            'leaderboard' => $leaderboard
        ]);
    }

    /**
     * GET: Get battle wins count
     */
    public function battleWins()
    {
        $this->requireGet();

        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as wins 
             FROM pet_battles pb
             JOIN user_pets up ON pb.attacker_pet_id = up.id
             WHERE up.user_id = ? AND pb.winner_pet_id = pb.attacker_pet_id"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $this->success(['wins' => (int) ($row['wins'] ?? 0)]);
    }

    /**
     * GET: Get current streak
     */
    public function streak()
    {
        $this->requireGet();

        // Count consecutive wins (simplified - just count recent wins)
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as streak 
             FROM pet_battles pb
             JOIN user_pets up ON pb.attacker_pet_id = up.id
             WHERE up.user_id = ? AND pb.winner_pet_id = pb.attacker_pet_id
             AND pb.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $this->success(['streak' => (int) ($row['streak'] ?? 0)]);
    }

    // ================================================
    // 3v3 BATTLE SYSTEM
    // ================================================

    /**
     * POST: Start a 3v3 battle
     * Input: { pet_ids: [1,2,3], opponent_user_id: 5 }
     */
    public function startBattle3v3()
    {
        $this->requirePost();

        // Rate limit - 5 battles per hour
        $this->checkRateLimit('battle_3v3', 5, 60);

        $input = $this->getInput();
        $pet_ids = isset($input['pet_ids']) ? $input['pet_ids'] : [];
        $opponent_user_id = isset($input['opponent_user_id']) ? (int) $input['opponent_user_id'] : 0;

        // Validate pet_ids
        if (!is_array($pet_ids) || count($pet_ids) !== 3) {
            $this->error('You must select exactly 3 pets');
            return;
        }

        if (!$opponent_user_id) {
            $this->error('Opponent user ID required');
            return;
        }

        if ($opponent_user_id === $this->user_id) {
            $this->error('Cannot battle yourself');
            return;
        }

        // Load battle engine and state manager
        require_once __DIR__ . '/../../pet/logic/BattleEngine.php';
        require_once __DIR__ . '/../../pet/logic/BattleStateManager.php';

        $engine = new BattleEngine($this->conn);
        $state_manager = new BattleStateManager();

        // Get player's pets
        $player_pets = [];
        foreach ($pet_ids as $pet_id) {
            $pet = $engine->getPetForBattle((int) $pet_id);
            if (!$pet) {
                $this->error("Pet ID {$pet_id} not found or dead");
                return;
            }
            // Verify ownership
            if ((int) $pet['user_id'] !== $this->user_id) {
                $this->error("Pet ID {$pet_id} does not belong to you");
                return;
            }
            $player_pets[] = $pet;
        }

        // Get opponent's pets (top 3 by level)
        $query = "SELECT up.*, ps.name as species_name, ps.element, 
                         ps.base_attack, ps.base_defense, ps.base_speed, ps.img_adult, ps.rarity
                  FROM user_pets up
                  JOIN pet_species ps ON up.species_id = ps.id
                  WHERE up.user_id = ? AND up.status = 'ALIVE'
                  ORDER BY up.level DESC
                  LIMIT 3";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $opponent_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $enemy_pets = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $enemy_pets[] = $row;
        }
        mysqli_stmt_close($stmt);

        if (count($enemy_pets) < 3) {
            $this->error('Opponent does not have enough pets');
            return;
        }

        // Initialize battle
        $battle_state = $state_manager->initBattle($this->user_id, $player_pets, $enemy_pets);

        // Get skills for active pets
        $player_skills = $engine->getPetSkills((int) $player_pets[0]['species_id']);

        $this->success([
            'battle_id' => $battle_state['battle_id'],
            'player_pets' => $battle_state['player_pets'],
            'enemy_pets' => $battle_state['enemy_pets'],
            'active_player_index' => 0,
            'active_enemy_index' => 0,
            'player_skills' => $player_skills,
            'current_turn' => 'player',
            'logs' => $battle_state['logs']
        ]);
    }

    /**
     * POST: Execute attack in 3v3 battle
     * Input: { battle_id: "xxx", skill_id: 5, target_index: 0 }
     */
    public function attack()
    {
        $this->requirePost();

        $input = $this->getInput();
        $battle_id = isset($input['battle_id']) ? $input['battle_id'] : '';
        $skill_id = isset($input['skill_id']) ? (int) $input['skill_id'] : 0;
        $target_index = isset($input['target_index']) ? (int) $input['target_index'] : 0;

        if (empty($battle_id)) {
            $this->error('Battle ID required');
            return;
        }

        // Load modules
        require_once __DIR__ . '/../../pet/logic/BattleEngine.php';
        require_once __DIR__ . '/../../pet/logic/BattleStateManager.php';

        $engine = new BattleEngine($this->conn);
        $state_manager = new BattleStateManager();

        // Get battle state
        $state = $state_manager->getState($battle_id);
        if (!$state) {
            $this->error('Battle not found or expired');
            return;
        }

        // Verify ownership
        if (!$state_manager->validateOwnership($battle_id, $this->user_id)) {
            $this->error('This is not your battle');
            return;
        }

        // Check if battle is still active
        if ($state['status'] !== 'active') {
            $this->error('Battle has already ended');
            return;
        }

        // Check if it's player's turn
        if ($state['current_turn'] !== 'player') {
            $this->error('Not your turn!');
            return;
        }

        // Validate target
        if ($target_index < 0 || $target_index > 2 || $state['enemy_pets'][$target_index]['is_fainted']) {
            $this->error('Invalid target');
            return;
        }

        // Get attacker and defender
        $attacker = $state['player_pets'][$state['active_player_index']];
        $defender = $state['enemy_pets'][$target_index];

        // Get skill (or default if skill_id = 0)
        $skills = $engine->getPetSkills((int) $attacker['species_id']);
        $skill = null;
        foreach ($skills as $s) {
            if ((int) $s['id'] === $skill_id || ($skill_id === 0 && (int) $s['skill_slot'] === 1)) {
                $skill = $s;
                break;
            }
        }
        if (!$skill) {
            $skill = $skills[0] ?? ['skill_name' => 'Attack', 'base_damage' => 25, 'skill_element' => $attacker['element']];
        }

        // Calculate damage (SERVER-SIDE - SECURE)
        $damage_result = $engine->calculateDamage($attacker, $skill, $defender);

        // Apply damage
        $state = $state_manager->applyDamage($battle_id, 'enemy', $target_index, $damage_result['damage_dealt']);

        // Add logs
        foreach ($damage_result['logs'] as $log) {
            $state_manager->addLog($battle_id, $log);
        }

        // Switch turn to enemy
        $state = $state_manager->switchTurn($battle_id);

        // Get updated state
        $state = $state_manager->getState($battle_id);

        // Prepare response
        $response = [
            'damage_dealt' => $damage_result['damage_dealt'],
            'is_critical' => $damage_result['is_critical'],
            'element_advantage' => $damage_result['element_advantage'],
            'new_enemy_hp' => $state['enemy_pets'][$target_index]['hp'],
            'is_fainted' => $state['enemy_pets'][$target_index]['is_fainted'],
            'logs' => $damage_result['logs'],
            'battle_state' => [
                'current_turn' => $state['current_turn'],
                'turn_count' => $state['turn_count'],
                'status' => $state['status'],
                'active_player_index' => $state['active_player_index'],
                'active_enemy_index' => $state['active_enemy_index'],
                'player_pets' => $state['player_pets'],
                'enemy_pets' => $state['enemy_pets']
            ]
        ];

        $this->success($response);
    }

    /**
     * GET: Get current battle state
     */
    public function getBattleState()
    {
        $this->requireGet();

        $battle_id = isset($_GET['battle_id']) ? $_GET['battle_id'] : '';

        if (empty($battle_id)) {
            $this->error('Battle ID required');
            return;
        }

        require_once __DIR__ . '/../../pet/logic/BattleStateManager.php';
        $state_manager = new BattleStateManager();

        $state = $state_manager->getState($battle_id);
        if (!$state) {
            $this->error('Battle not found or expired');
            return;
        }

        if (!$state_manager->validateOwnership($battle_id, $this->user_id)) {
            $this->error('This is not your battle');
            return;
        }

        $this->success(['battle_state' => $state]);
    }

    /**
     * POST: Switch active pet in 3v3 battle
     * Input: { battle_id: "xxx", new_pet_index: 1 }
     */
    public function switchPet()
    {
        $this->requirePost();

        $input = $this->getInput();
        $battle_id = isset($input['battle_id']) ? $input['battle_id'] : '';
        $new_index = isset($input['new_pet_index']) ? (int) $input['new_pet_index'] : -1;

        if (empty($battle_id)) {
            $this->error('Battle ID required');
            return;
        }

        if ($new_index < 0 || $new_index > 2) {
            $this->error('Invalid pet index');
            return;
        }

        require_once __DIR__ . '/../../pet/logic/BattleEngine.php';
        require_once __DIR__ . '/../../pet/logic/BattleStateManager.php';

        $engine = new BattleEngine($this->conn);
        $state_manager = new BattleStateManager();

        $state = $state_manager->getState($battle_id);
        if (!$state) {
            $this->error('Battle not found or expired');
            return;
        }

        if (!$state_manager->validateOwnership($battle_id, $this->user_id)) {
            $this->error('This is not your battle');
            return;
        }

        if ($state['current_turn'] !== 'player') {
            $this->error('Not your turn!');
            return;
        }

        // Switch pet
        $state = $state_manager->switchActivePet($battle_id, 'player', $new_index);

        if (isset($state['error'])) {
            $this->error($state['error']);
            return;
        }

        // Get skills for new active pet
        $new_skills = $engine->getPetSkills((int) $state['player_pets'][$new_index]['species_id']);

        // Switching uses a turn
        $state = $state_manager->switchTurn($battle_id);
        $state = $state_manager->getState($battle_id);

        $this->success([
            'message' => 'Pet switched!',
            'new_skills' => $new_skills,
            'battle_state' => [
                'current_turn' => $state['current_turn'],
                'turn_count' => $state['turn_count'],
                'active_player_index' => $state['active_player_index'],
                'player_pets' => $state['player_pets'],
                'enemy_pets' => $state['enemy_pets']
            ]
        ]);
    }

    /**
     * GET: Get opponents for 3v3 battle (users with 3+ pets)
     */
    public function getOpponents3v3()
    {
        $this->requireGet();

        $query = "SELECT n.id_nethera as user_id, n.nama_lengkap as name, n.username,
                         COUNT(up.id) as pet_count,
                         MAX(up.level) as max_level
                  FROM nethera n
                  JOIN user_pets up ON up.user_id = n.id_nethera
                  WHERE n.id_nethera != ? AND up.status = 'ALIVE'
                  GROUP BY n.id_nethera
                  HAVING pet_count >= 3
                  ORDER BY RAND()
                  LIMIT 10";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $opponents = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Get their top 3 pets preview
            $pet_query = mysqli_prepare(
                $this->conn,
                "SELECT ps.name as species_name, ps.element, ps.img_adult, up.level
                 FROM user_pets up
                 JOIN pet_species ps ON up.species_id = ps.id
                 WHERE up.user_id = ? AND up.status = 'ALIVE'
                 ORDER BY up.level DESC LIMIT 3"
            );
            mysqli_stmt_bind_param($pet_query, "i", $row['user_id']);
            mysqli_stmt_execute($pet_query);
            $pet_result = mysqli_stmt_get_result($pet_query);

            $row['pets'] = [];
            while ($pet = mysqli_fetch_assoc($pet_result)) {
                $row['pets'][] = $pet;
            }
            mysqli_stmt_close($pet_query);

            $opponents[] = $row;
        }
        mysqli_stmt_close($stmt);

        $this->success(['opponents' => $opponents]);
    }
}
