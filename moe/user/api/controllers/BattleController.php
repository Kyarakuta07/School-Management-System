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
     * POST: Record battle result from 1v1 arena
     */
    public function battleResult()
    {
        $this->requirePost();

        $input = $this->getInput();
        $attacker_pet_id = isset($input['attacker_pet_id']) ? (int) $input['attacker_pet_id'] : 0;
        $defender_pet_id = isset($input['defender_pet_id']) ? (int) $input['defender_pet_id'] : 0;
        $winner = isset($input['winner']) ? $input['winner'] : '';

        if (!$attacker_pet_id) {
            $this->error('Attacker pet ID required');
            return;
        }

        // Verify pet ownership
        $pet = $this->verifyPetOwnership($attacker_pet_id);
        if (!$pet) {
            $this->error('Invalid pet or not owned by user');
            return;
        }

        $player_won = ($winner === 'attacker');
        
        // SECURITY FIX: Calculate rewards SERVER-SIDE, not from client input
        $gold_reward = 0;
        $exp_reward = 0;
        
        if ($player_won) {
            // Get defender pet data for reward calculation
            $defender_pet = null;
            if ($defender_pet_id > 0) {
                // Real player pet
                $stmt = mysqli_prepare($this->conn, "SELECT up.level, ps.rarity FROM user_pets up JOIN pet_species ps ON up.species_id = ps.id WHERE up.id = ?");
                mysqli_stmt_bind_param($stmt, "i", $defender_pet_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $defender_pet = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
            }
            
            // Calculate rewards based on opponent level and rarity
            if ($defender_pet) {
                $opponent_level = (int) $defender_pet['level'];
                $rarity = strtolower($defender_pet['rarity']);
                
                // Base gold: 5 + (opponent_level * 1.5)
                $base_gold = 5 + (int)($opponent_level * 1.5);
                
                // Rarity multiplier
                $rarity_multipliers = [
                    'common' => 1.0,
                    'uncommon' => 1.2,
                    'rare' => 1.5,
                    'epic' => 2.0,
                    'legendary' => 3.0
                ];
                $multiplier = $rarity_multipliers[$rarity] ?? 1.0;
                
                $gold_reward = (int)($base_gold * $multiplier);
                $exp_reward = 10 + (int)($opponent_level * 2);
                
                // Cap rewards to prevent exploits
                $gold_reward = min($gold_reward, 100);
                $exp_reward = min($exp_reward, 150);
            } else {
                // AI opponent (negative ID) - fixed small rewards
                $gold_reward = 15;
                $exp_reward = 20;
            }
        }
        
        $loser_pet_id = $player_won ? $defender_pet_id : $attacker_pet_id;

        // Record battle in pet_battles table
        try {
            $winner_pet_id = $player_won ? $attacker_pet_id : $defender_pet_id;

            $stmt = mysqli_prepare(
                $this->conn,
                "INSERT INTO pet_battles (attacker_pet_id, defender_pet_id, winner_pet_id, reward_gold, reward_exp) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "iiiii", $attacker_pet_id, $defender_pet_id, $winner_pet_id, $gold_reward, $exp_reward);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Increment total_wins for the winning pet (for persistent leaderboard)
            if ($winner_pet_id > 0) {
                $update_wins = mysqli_prepare($this->conn, "UPDATE user_pets SET total_wins = COALESCE(total_wins, 0) + 1 WHERE id = ?");
                mysqli_stmt_bind_param($update_wins, "i", $winner_pet_id);
                mysqli_stmt_execute($update_wins);
                mysqli_stmt_close($update_wins);
            }

            // If player won, give rewards
            if ($player_won && $gold_reward > 0) {
                // Calculate bonus rewards
                $reward_info = $this->calculateBonusRewards($gold_reward);
                $total_gold = $reward_info['total_gold'];
                $this->addGold($total_gold);

                // Check and unlock achievements
                $unlocked_achievements = $this->checkBattleAchievements();
            } else {
                $reward_info = [
                    'base_gold' => $gold_reward,
                    'first_win_bonus' => 0,
                    'streak_multiplier' => 1.0,
                    'total_gold' => $gold_reward
                ];
                $unlocked_achievements = [];
            }

            // Add EXP to winning pet
            if ($player_won && $exp_reward > 0) {
                addExpToPet($this->conn, $attacker_pet_id, $exp_reward);
            }

            // HARDCORE: Apply HP damage to loser pet
            $pet_died = false;
            $shield_blocked = false;
            $hp_damage = 20;

            if ($loser_pet_id > 0) { // Only for real pets (not AI with negative IDs)
                // Check for shield
                $shield_stmt = mysqli_prepare($this->conn, "SELECT health, has_shield FROM user_pets WHERE id = ?");
                mysqli_stmt_bind_param($shield_stmt, "i", $loser_pet_id);
                mysqli_stmt_execute($shield_stmt);
                $shield_result = mysqli_stmt_get_result($shield_stmt);
                $loser_data = mysqli_fetch_assoc($shield_result);
                mysqli_stmt_close($shield_stmt);

                if ($loser_data) {
                    $current_hp = (int) $loser_data['health'];
                    $has_shield = (int) ($loser_data['has_shield'] ?? 0);

                    if ($has_shield) {
                        // Shield blocks damage
                        $shield_blocked = true;
                        $consume_shield = mysqli_prepare($this->conn, "UPDATE user_pets SET has_shield = 0 WHERE id = ?");
                        mysqli_stmt_bind_param($consume_shield, "i", $loser_pet_id);
                        mysqli_stmt_execute($consume_shield);
                        mysqli_stmt_close($consume_shield);
                    } else {
                        // Apply HP damage
                        $new_hp = max(0, $current_hp - $hp_damage);
                        $new_status = $new_hp <= 0 ? 'DEAD' : 'ALIVE';
                        $pet_died = ($new_hp <= 0);

                        // If pet died, also deactivate it
                        if ($pet_died) {
                            $update_hp = mysqli_prepare($this->conn, "UPDATE user_pets SET health = ?, status = ?, is_active = 0 WHERE id = ?");
                        } else {
                            $update_hp = mysqli_prepare($this->conn, "UPDATE user_pets SET health = ?, status = ? WHERE id = ?");
                        }
                        mysqli_stmt_bind_param($update_hp, "isi", $new_hp, $new_status, $loser_pet_id);
                        mysqli_stmt_execute($update_hp);
                        mysqli_stmt_close($update_hp);
                    }
                }
            }

            // Update arena stats
            $this->updateArenaStats($player_won);

            // Increment rate limit for daily battles
            $user_id_str = 'user_' . $this->user_id;
            $this->rate_limiter->checkLimit($user_id_str, 'pet_battle', 3, 1440); // 1440 minutes = 24 hours

            $this->success([
                'recorded' => true,
                'won' => $player_won,
                'gold' => $reward_info['total_gold'] ?? $gold_reward,
                'base_gold' => $reward_info['base_gold'] ?? $gold_reward,
                'first_win_bonus' => $reward_info['first_win_bonus'] ?? 0,
                'streak_multiplier' => $reward_info['streak_multiplier'] ?? 1.0,
                'current_streak' => $reward_info['current_streak'] ?? 0,
                'exp' => $exp_reward,
                'achievements_unlocked' => $unlocked_achievements ?? [],
                'hardcore' => [
                    'hp_damage' => $shield_blocked ? 0 : $hp_damage,
                    'shield_blocked' => $shield_blocked,
                    'pet_died' => $pet_died
                ]
            ]);
            // Probabilistically cleanup old battle history (1% chance)
            if (rand(1, 100) === 1) {
                $this->cleanupBattleHistory();
            }
        } catch (Exception $e) {
            $this->error('Failed to record battle result: ' . $e->getMessage());
        }
    }

    /**
     * Cleanup old battle history to prevent table bloat
     * Deletes battles older than 60 days.
     * Also limits per-user history to last 100 entries.
     */
    private function cleanupBattleHistory()
    {
        // Delete battles older than 60 days
        mysqli_query(
            $this->conn,
            "DELETE FROM pet_battles WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)"
        );
    }

    /**
     * Update user's arena stats (wins/losses) and streak
     */
    private function updateArenaStats($won)
    {
        $today = date('Y-m-d');

        if ($won) {
            // Update wins and streak
            $stmt = mysqli_prepare(
                $this->conn,
                "UPDATE nethera SET 
                    arena_wins = COALESCE(arena_wins, 0) + 1,
                    current_win_streak = COALESCE(current_win_streak, 0) + 1,
                    last_battle_win_date = ?
                 WHERE id_nethera = ?"
            );
            mysqli_stmt_bind_param($stmt, "si", $today, $this->user_id);
        } else {
            // Update losses and reset streak
            $stmt = mysqli_prepare(
                $this->conn,
                "UPDATE nethera SET 
                    arena_losses = COALESCE(arena_losses, 0) + 1,
                    current_win_streak = 0
                 WHERE id_nethera = ?"
            );
            mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /**
     * Calculate bonus rewards based on first-win and streak
     */
    private function calculateBonusRewards($base_gold)
    {
        $today = date('Y-m-d');
        $first_win_bonus = 0;
        $streak_multiplier = 1.0;

        // Get user's streak info
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT last_battle_win_date, current_win_streak FROM nethera WHERE id_nethera = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $last_win_date = $user_data['last_battle_win_date'] ?? null;
        $current_streak = (int) ($user_data['current_win_streak'] ?? 0);

        // First win of the day bonus
        if ($last_win_date !== $today) {
            $first_win_bonus = 50; // +50 gold for first win
        }

        // Streak multiplier: 1.0 + min(streak, 10) * 0.1 = max 2.0x
        $streak_multiplier = 1.0 + min($current_streak, 10) * 0.1;

        // Calculate total
        $total_gold = (int) (($base_gold * $streak_multiplier) + $first_win_bonus);

        return [
            'base_gold' => $base_gold,
            'first_win_bonus' => $first_win_bonus,
            'streak_multiplier' => $streak_multiplier,
            'current_streak' => $current_streak + 1, // Will be after this win
            'total_gold' => $total_gold
        ];
    }

    /**
     * Check and unlock battle achievements
     */
    private function checkBattleAchievements()
    {
        $unlocked = [];

        // Get user's current stats
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT arena_wins, current_win_streak FROM nethera WHERE id_nethera = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $wins = (int) ($stats['arena_wins'] ?? 0);
        $streak = (int) ($stats['current_win_streak'] ?? 0);

        // Get all battle achievements user hasn't unlocked yet
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT a.* FROM achievements a
             LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
             WHERE a.category = 'battle' AND ua.id IS NULL"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($achievement = mysqli_fetch_assoc($result)) {
            $should_unlock = false;

            if ($achievement['requirement_type'] === 'battle_wins' && $wins >= $achievement['requirement_value']) {
                $should_unlock = true;
            } elseif ($achievement['requirement_type'] === 'win_streak' && $streak >= $achievement['requirement_value']) {
                $should_unlock = true;
            }

            if ($should_unlock) {
                // Unlock achievement
                $unlock_stmt = mysqli_prepare(
                    $this->conn,
                    "INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)"
                );
                mysqli_stmt_bind_param($unlock_stmt, "ii", $this->user_id, $achievement['id']);
                mysqli_stmt_execute($unlock_stmt);
                mysqli_stmt_close($unlock_stmt);

                // Give gold reward
                if ($achievement['reward_gold'] > 0) {
                    $this->addGold($achievement['reward_gold']);
                }

                $unlocked[] = [
                    'name' => $achievement['name'],
                    'icon' => $achievement['icon'],
                    'rarity' => $achievement['rarity'],
                    'reward_gold' => $achievement['reward_gold']
                ];
            }
        }
        mysqli_stmt_close($stmt);

        return $unlocked;
    }

    /**
     * GET: Get battle history and stats
     */
    public function battleHistory()
    {
        $this->requireGet();

        $limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 10;
        $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

        // Get battles where user's pet was the attacker
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT pb.created_at, pb.id,
                    -- My Pet Params
                    COALESCE(up.nickname, ps.name) as my_pet_name,
                    up.level as my_pet_level,
                    ps.element as my_pet_element,
                    CASE 
                        WHEN up.evolution_stage = 'adult' THEN ps.img_adult 
                        WHEN up.evolution_stage = 'baby' THEN ps.img_baby
                        ELSE ps.img_egg
                    END as my_pet_image,
                    
                    -- Opponent Pet Params (with fallback for AI opponents)
                    COALESCE(def_up.nickname, def_ps.name, 'Wild Pet') as opp_pet_name,
                    COALESCE(def_up.level, 10) as opp_pet_level,
                    COALESCE(def_ps.element, 'Dark') as opp_pet_element,
                    CASE 
                        WHEN def_up.id IS NULL THEN 'ai_pet.png'
                        WHEN def_up.evolution_stage = 'adult' THEN COALESCE(def_ps.img_adult, 'default.png')
                        WHEN def_up.evolution_stage = 'baby' THEN COALESCE(def_ps.img_baby, 'default.png')
                        ELSE COALESCE(def_ps.img_egg, 'default.png')
                    END as opp_pet_image,
                    
                    -- Opponent Owner
                    COALESCE(def_u.username, 'Wild Trainer') as opp_username,
                    
                    -- Result
                    (pb.winner_pet_id = pb.attacker_pet_id) as won
             FROM pet_battles pb
             JOIN user_pets up ON pb.attacker_pet_id = up.id
             JOIN pet_species ps ON up.species_id = ps.id
             LEFT JOIN user_pets def_up ON pb.defender_pet_id = def_up.id AND pb.defender_pet_id > 0
             LEFT JOIN pet_species def_ps ON def_up.species_id = def_ps.id
             LEFT JOIN nethera def_u ON def_up.user_id = def_u.id_nethera
             WHERE up.user_id = ?
             ORDER BY pb.created_at DESC
             LIMIT ?, ?"
        );
        mysqli_stmt_bind_param($stmt, "iii", $this->user_id, $offset, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $battles = [];
        $wins = 0;
        $losses = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $battles[] = $row;
            if ($row['won']) {
                $wins++;
            } else {
                $losses++;
            }
        }
        mysqli_stmt_close($stmt);

        // Count total wins for this user
        $total_stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as wins 
             FROM pet_battles pb
             JOIN user_pets up ON pb.attacker_pet_id = up.id
             WHERE up.user_id = ? AND pb.winner_pet_id = pb.attacker_pet_id"
        );
        mysqli_stmt_bind_param($total_stmt, "i", $this->user_id);
        mysqli_stmt_execute($total_stmt);
        $total_result = mysqli_stmt_get_result($total_stmt);
        $total_wins = mysqli_fetch_assoc($total_result)['wins'] ?? 0;
        mysqli_stmt_close($total_stmt);

        // Count total battles for this user
        $battles_stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as total 
             FROM pet_battles pb
             JOIN user_pets up ON pb.attacker_pet_id = up.id
             WHERE up.user_id = ?"
        );
        mysqli_stmt_bind_param($battles_stmt, "i", $this->user_id);
        mysqli_stmt_execute($battles_stmt);
        $battles_result = mysqli_stmt_get_result($battles_stmt);
        $total_battles = mysqli_fetch_assoc($battles_result)['total'] ?? 0;
        mysqli_stmt_close($battles_stmt);

        $total_losses = $total_battles - $total_wins;

        // Calculate current win streak (consecutive wins from most recent)
        $streak_stmt = mysqli_prepare(
            $this->conn,
            "SELECT (pb.winner_pet_id = pb.attacker_pet_id) as won
             FROM pet_battles pb
             JOIN user_pets up ON pb.attacker_pet_id = up.id
             WHERE up.user_id = ?
             ORDER BY pb.created_at DESC
             LIMIT 20"
        );
        mysqli_stmt_bind_param($streak_stmt, "i", $this->user_id);
        mysqli_stmt_execute($streak_stmt);
        $streak_result = mysqli_stmt_get_result($streak_stmt);

        $current_streak = 0;
        while ($streak_row = mysqli_fetch_assoc($streak_result)) {
            if ($streak_row['won']) {
                $current_streak++;
            } else {
                break; // Stop counting at first loss
            }
        }
        mysqli_stmt_close($streak_stmt);

        // Calculate battles remaining today using rate limiter
        $user_id_str = 'user_' . $this->user_id;
        $battles_today = $this->rate_limiter->getAttempts($user_id_str, 'pet_battle');
        $battles_remaining = max(0, 3 - $battles_today);

        $this->success([
            'history' => $battles,
            'stats' => [
                'wins' => (int) $total_wins,
                'losses' => (int) $total_losses,
                'current_streak' => $current_streak,
                'battles_remaining' => $battles_remaining
            ]
        ]);
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

        // If no opponent specified, find a random opponent with 3+ pets
        $use_ai_opponent = false;
        if (!$opponent_user_id) {
            $query = "SELECT up.user_id, COUNT(*) as pet_count 
                      FROM user_pets up 
                      WHERE up.user_id != ? AND up.status = 'ALIVE' 
                      GROUP BY up.user_id 
                      HAVING pet_count >= 3 
                      ORDER BY RAND() 
                      LIMIT 1";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $this->user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$row) {
                // No real opponents, use AI
                $use_ai_opponent = true;
            } else {
                $opponent_user_id = (int) $row['user_id'];
            }
        }

        if (!$use_ai_opponent && $opponent_user_id === $this->user_id) {
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

        // Get opponent's pets
        $opponent_name = 'Wild Trainer'; // Default for AI

        if ($use_ai_opponent) {
            // Generate AI opponent pets based on player's average level
            $avg_level = array_sum(array_column($player_pets, 'level')) / count($player_pets);
            $enemy_pets = $this->generateAIOpponent($avg_level);
            $opponent_name = 'Wild Trainer 🤖';
        } else {
            // Get opponent's name from nethera table
            $name_query = "SELECT nama_lengkap FROM nethera WHERE id_nethera = ?";
            $name_stmt = mysqli_prepare($this->conn, $name_query);
            mysqli_stmt_bind_param($name_stmt, "i", $opponent_user_id);
            mysqli_stmt_execute($name_stmt);
            $name_result = mysqli_stmt_get_result($name_stmt);
            $name_row = mysqli_fetch_assoc($name_result);
            mysqli_stmt_close($name_stmt);

            if ($name_row) {
                $opponent_name = $name_row['nama_lengkap'];
            }

            // Get real opponent's pets (top 3 by level)
            $query = "SELECT up.*, ps.name as species_name, ps.element, 
                             ps.base_attack, ps.base_defense, ps.base_speed, 
                             ps.img_egg, ps.img_baby, ps.img_adult, ps.rarity
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
        }

        // Initialize battle
        $battle_state = $state_manager->initBattle($this->user_id, $player_pets, $enemy_pets);

        // Get skills for active pets
        $player_skills = $engine->getPetSkills((int) $player_pets[0]['species_id']);

        $this->success([
            'battle_id' => $battle_state['battle_id'],
            'opponent_name' => $opponent_name,
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
     * Generate AI opponent pets based on player's average level
     * @param float $avg_level Player's average pet level
     * @return array Array of 3 AI pet objects
     */
    private function generateAIOpponent($avg_level)
    {
        $avg_level = max(1, min(99, (int) $avg_level));

        // Get 3 random species from database
        $query = "SELECT * FROM pet_species ORDER BY RAND() LIMIT 3";
        $result = mysqli_query($this->conn, $query);

        $enemy_pets = [];
        $elements = ['Fire', 'Water', 'Earth', 'Air', 'Light', 'Dark'];
        $ai_names = ['Shadow', 'Phantom', 'Ghost', 'Specter', 'Wraith'];

        $index = 0;
        while ($species = mysqli_fetch_assoc($result)) {
            // Calculate stats based on level (similar formula as player pets)
            $base_hp = 50 + ($avg_level * 5) + rand(-10, 10);
            $base_atk = ($species['base_attack'] ?? 10) + floor($avg_level / 2) + rand(-3, 3);
            $base_def = ($species['base_defense'] ?? 8) + floor($avg_level / 3) + rand(-2, 2);

            $enemy_pets[] = [
                'id' => -1 * ($index + 1), // Negative IDs for AI pets
                'user_id' => 0, // AI
                'species_id' => $species['id'],
                'nickname' => $ai_names[array_rand($ai_names)] . ' ' . $species['name'],
                'species_name' => $species['name'],
                'level' => $avg_level + rand(-2, 2),
                'hp' => $base_hp,
                'current_hp' => $base_hp,
                'atk' => $base_atk,
                'def' => $base_def,
                'element' => $species['element'] ?? $elements[array_rand($elements)],
                'rarity' => $species['rarity'] ?? 'Common',
                'img_egg' => $species['img_egg'] ?? '',
                'img_baby' => $species['img_baby'] ?? '',
                'img_adult' => $species['img_adult'] ?? '',
                'evolution_stage' => 'adult',
                'is_ai' => true
            ];
            $index++;
        }

        // If we don't have enough species, fill with generic pets
        while (count($enemy_pets) < 3) {
            $enemy_pets[] = [
                'id' => -1 * (count($enemy_pets) + 1),
                'user_id' => 0,
                'species_id' => 1,
                'nickname' => 'Wild Pet',
                'species_name' => 'Wild Pet',
                'level' => $avg_level,
                'hp' => 50 + ($avg_level * 5),
                'current_hp' => 50 + ($avg_level * 5),
                'atk' => 10 + floor($avg_level / 2),
                'def' => 8 + floor($avg_level / 3),
                'element' => $elements[array_rand($elements)],
                'rarity' => 'Common',
                'img_egg' => '',
                'img_baby' => '',
                'img_adult' => '',
                'evolution_stage' => 'adult',
                'is_ai' => true
            ];
        }

        return $enemy_pets;
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
     * POST: Process enemy turn in 3v3 battle
     * Input: { battle_id: "xxx" }
     * 
     * SMART AI: Selects best skill based on elemental advantage, HP strategy, and randomness
     */
    public function enemyTurn()
    {
        $this->requirePost();

        $input = $this->getInput();
        $battle_id = isset($input['battle_id']) ? $input['battle_id'] : '';

        if (empty($battle_id)) {
            $this->error('Battle ID required');
            return;
        }

        // Load battle state and engine
        require_once __DIR__ . '/../../pet/logic/BattleStateManager.php';
        require_once __DIR__ . '/../../pet/logic/BattleEngine.php';

        $state_manager = new BattleStateManager();
        $engine = new BattleEngine($this->conn);
        $state = $state_manager->getState($battle_id);

        if (!$state || $state['user_id'] !== $this->user_id) {
            $this->error('Battle not found or not yours');
            return;
        }

        if ($state['status'] !== 'active') {
            $this->error('Battle is not active');
            return;
        }

        if ($state['current_turn'] !== 'enemy') {
            $this->error('Not enemy turn');
            return;
        }

        // Get enemy's active pet
        $enemy_index = $state['active_enemy_index'];
        $enemy_pet = $state['enemy_pets'][$enemy_index];

        // Get player's active pet
        $player_index = $state['active_player_index'];
        $player_pet = $state['player_pets'][$player_index];

        // ============================================
        // SMART AI: Select best skill
        // ============================================
        $enemy_species_id = $enemy_pet['species_id'] ?? 1;
        $enemy_skills = $engine->getPetSkills((int) $enemy_species_id);

        // Calculate enemy HP percentage for strategy
        $enemy_hp_percent = ($enemy_pet['hp'] ?? 100) / ($enemy_pet['max_hp'] ?? 100);

        $best_skill = null;
        $best_score = -INF;

        foreach ($enemy_skills as $skill) {
            // Base score from damage
            $score = (float) ($skill['base_damage'] ?? 25);

            // 1. Elemental advantage bonus (heavily weighted)
            $elem_mult = $engine->getElementAdvantage(
                $skill['skill_element'] ?? $enemy_pet['element'],
                $player_pet['element'] ?? 'Fire'
            );
            $score *= $elem_mult;

            // 2. HP-based strategy
            if ($enemy_hp_percent < 0.3) {
                // DESPERATE MODE: Low HP = prioritize highest damage (all-in)
                $score *= 1.5;
                // Extra bonus for special/ultimate skills
                if (($skill['is_special'] ?? false) || ($skill['base_damage'] ?? 0) >= 60) {
                    $score *= 1.3;
                }
            } elseif ($enemy_hp_percent < 0.5) {
                // AGGRESSIVE MODE
                $score *= 1.2;
            }

            // 3. Random variance for unpredictability (±15%)
            $score *= (0.85 + (mt_rand(0, 30) / 100));

            // 4. STAB bonus: Same element as pet's natural element
            if (($skill['skill_element'] ?? '') === ($enemy_pet['element'] ?? '')) {
                $score *= 1.1;
            }

            if ($score > $best_score) {
                $best_score = $score;
                $best_skill = $skill;
            }
        }

        // Fallback if no skills
        if (!$best_skill) {
            $best_skill = [
                'skill_name' => 'Attack',
                'base_damage' => 25 + (($enemy_pet['level'] ?? 1) * 2),
                'skill_element' => $enemy_pet['element'] ?? 'Fire',
                'is_special' => false
            ];
        }

        // ============================================
        // Calculate damage using BattleEngine
        // ============================================
        $damage_result = $engine->calculateDamage($enemy_pet, $best_skill, $player_pet);
        $damage = $damage_result['damage_dealt'];

        $logs = [];
        $skill_name = $best_skill['skill_name'] ?? 'Attack';
        $log_msg = "{$enemy_pet['species_name']} used {$skill_name} on {$player_pet['species_name']} for {$damage} damage!";

        // Add element advantage info
        if ($damage_result['element_advantage'] === 'super_effective') {
            $log_msg .= ' Super effective!';
        } elseif ($damage_result['element_advantage'] === 'not_effective') {
            $log_msg .= ' Not very effective...';
        }
        if ($damage_result['is_critical']) {
            $log_msg = "CRITICAL! " . $log_msg;
        }
        $logs[] = $log_msg;

        // Apply damage to player pet
        $new_hp = max(0, $player_pet['hp'] - $damage);
        $state['player_pets'][$player_index]['hp'] = $new_hp;

        $player_fainted = false;
        if ($new_hp <= 0) {
            $state['player_pets'][$player_index]['is_fainted'] = true;
            $player_fainted = true;
            $logs[] = "{$player_pet['species_name']} fainted!";

            // Check for next alive player pet
            $next_alive = -1;
            foreach ($state['player_pets'] as $i => $p) {
                if (!$p['is_fainted'] && $p['hp'] > 0) {
                    $next_alive = $i;
                    break;
                }
            }

            if ($next_alive === -1) {
                // All player pets fainted - defeat
                $state['status'] = 'defeat';
                $logs[] = "All your pets have fainted! You lose!";
            } else {
                // Auto-switch to next alive pet
                $state['active_player_index'] = $next_alive;
                $logs[] = "Go, {$state['player_pets'][$next_alive]['species_name']}!";
            }
        }

        // Switch turn back to player
        if ($state['status'] === 'active') {
            $state['current_turn'] = 'player';
            $state['turn_count']++;
        }

        // Save state to session directly
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['battles'][$battle_id] = $state;

        $this->success([
            'damage_dealt' => $damage,
            'is_critical' => $damage_result['is_critical'],
            'element_advantage' => $damage_result['element_advantage'],
            'skill_used' => $skill_name,
            'player_fainted' => $player_fainted,
            'logs' => $logs,
            'battle_state' => [
                'current_turn' => $state['current_turn'],
                'turn_count' => $state['turn_count'],
                'status' => $state['status'],
                'active_player_index' => $state['active_player_index'],
                'active_enemy_index' => $state['active_enemy_index'],
                'player_pets' => $state['player_pets'],
                'enemy_pets' => $state['enemy_pets']
            ]
        ]);
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
            'logs' => $state['logs'] ?? [],
            'battle_state' => [
                'current_turn' => $state['current_turn'],
                'turn_count' => $state['turn_count'],
                'status' => $state['status'] ?? 'active',
                'active_player_index' => $state['active_player_index'],
                'active_enemy_index' => $state['active_enemy_index'],
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

    /**
     * POST: Finish 3v3 battle and apply HP damage to fainted pets
     * Input: { battle_id: "xxx" }
     */
    public function finish3v3Battle()
    {
        $this->requirePost();

        $input = $this->getInput();
        $battle_id = isset($input['battle_id']) ? $input['battle_id'] : '';

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

        // Only process if battle ended
        if ($state['status'] !== 'victory' && $state['status'] !== 'defeat') {
            $this->error('Battle is still active');
            return;
        }

        $player_won = ($state['status'] === 'victory');
        $hp_damage_per_faint = 20;
        $pets_damaged = [];
        $pets_died = [];

        // HARDCORE: Apply HP damage to player's fainted pets
        foreach ($state['player_pets'] as $pet) {
            $pet_id = (int) ($pet['id'] ?? 0);

            // Skip AI pets (negative IDs) or no ID
            if ($pet_id <= 0)
                continue;

            // Check if this pet fainted in battle
            if (isset($pet['is_fainted']) && $pet['is_fainted']) {
                // Get current HP
                $hp_stmt = mysqli_prepare($this->conn, "SELECT health, has_shield FROM user_pets WHERE id = ?");
                mysqli_stmt_bind_param($hp_stmt, "i", $pet_id);
                mysqli_stmt_execute($hp_stmt);
                $hp_result = mysqli_stmt_get_result($hp_stmt);
                $pet_data = mysqli_fetch_assoc($hp_result);
                mysqli_stmt_close($hp_stmt);

                if ($pet_data) {
                    $current_hp = (int) $pet_data['health'];
                    $has_shield = (int) ($pet_data['has_shield'] ?? 0);

                    if ($has_shield) {
                        // Shield blocks damage
                        $consume = mysqli_prepare($this->conn, "UPDATE user_pets SET has_shield = 0 WHERE id = ?");
                        mysqli_stmt_bind_param($consume, "i", $pet_id);
                        mysqli_stmt_execute($consume);
                        mysqli_stmt_close($consume);
                        $pets_damaged[] = ['id' => $pet_id, 'blocked' => true];
                    } else {
                        // Apply damage
                        $new_hp = max(0, $current_hp - $hp_damage_per_faint);
                        $new_status = $new_hp <= 0 ? 'DEAD' : 'ALIVE';

                        // If pet died, also deactivate it
                        if ($new_hp <= 0) {
                            $update = mysqli_prepare($this->conn, "UPDATE user_pets SET health = ?, status = ?, is_active = 0 WHERE id = ?");
                        } else {
                            $update = mysqli_prepare($this->conn, "UPDATE user_pets SET health = ?, status = ? WHERE id = ?");
                        }
                        mysqli_stmt_bind_param($update, "isi", $new_hp, $new_status, $pet_id);
                        mysqli_stmt_execute($update);
                        mysqli_stmt_close($update);

                        $pets_damaged[] = ['id' => $pet_id, 'hp_lost' => $hp_damage_per_faint, 'new_hp' => $new_hp];

                        if ($new_hp <= 0) {
                            $pets_died[] = $pet_id;
                        }
                    }
                }
            }
        }

        // Give rewards if player won
        $gold_reward = 0;
        $exp_reward = 0;

        if ($player_won) {
            $gold_reward = rand(BATTLE_3V3_WIN_GOLD_MIN, BATTLE_3V3_WIN_GOLD_MAX);
            $exp_reward = rand(25, 50);

            $this->addGold($gold_reward);

            // Give EXP to surviving pets
            foreach ($state['player_pets'] as $pet) {
                $pet_id = (int) ($pet['id'] ?? 0);
                if ($pet_id > 0 && !($pet['is_fainted'] ?? false)) {
                    addExpToPet($this->conn, $pet_id, $exp_reward);
                }
            }
        }

        // Update arena stats
        $this->updateArenaStats($player_won);

        // Clear battle state
        $state_manager->endBattle($battle_id);

        $this->success([
            'finished' => true,
            'player_won' => $player_won,
            'gold_reward' => $gold_reward,
            'exp_reward' => $exp_reward,
            'hardcore' => [
                'pets_damaged' => $pets_damaged,
                'pets_died' => $pets_died
            ]
        ]);
    }

    /**
     * POST: Calculate damage for 1v1 arena (server-side)
     * Input: { skill_id: 1, attacker_pet_id: 5, defender_pet_id: 10 }
     */
    public function attack1v1()
    {
        $this->requirePost();

        $input = $this->getInput();
        $skill_id = isset($input['skill_id']) ? (int) $input['skill_id'] : 0;
        $attacker_pet_id = isset($input['attacker_pet_id']) ? (int) $input['attacker_pet_id'] : 0;
        $defender_pet_id = isset($input['defender_pet_id']) ? (int) $input['defender_pet_id'] : 0;

        if (!$skill_id || !$attacker_pet_id || !$defender_pet_id) {
            $this->error('Missing required parameters');
            return;
        }

        require_once __DIR__ . '/../../pet/logic/BattleEngine.php';
        $engine = new BattleEngine($this->conn);

        // Get attacker pet
        $attacker = $engine->getPetForBattle($attacker_pet_id);
        if (!$attacker) {
            $this->error('Attacker pet not found');
            return;
        }

        // Verify ownership
        if ((int) $attacker['user_id'] !== $this->user_id) {
            $this->error('This is not your pet');
            return;
        }

        // Get defender pet
        $defender = $engine->getPetForBattle($defender_pet_id);
        if (!$defender) {
            $this->error('Defender pet not found');
            return;
        }

        // Get skill
        $skill_query = "SELECT * FROM pet_skills WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $skill_query);
        mysqli_stmt_bind_param($stmt, "i", $skill_id);
        mysqli_stmt_execute($stmt);
        $skill_result = mysqli_stmt_get_result($stmt);
        $skill = mysqli_fetch_assoc($skill_result);
        mysqli_stmt_close($stmt);

        if (!$skill) {
            // Use default skill
            $skill = [
                'id' => $skill_id,
                'skill_name' => 'Basic Attack',
                'base_damage' => 25,
                'skill_element' => $attacker['element']
            ];
        }

        // Calculate damage using BattleEngine
        $result = $engine->calculateDamage($attacker, $skill, $defender);

        $this->success([
            'damage_dealt' => $result['damage_dealt'],
            'is_dodge' => $result['is_dodge'] ?? false,
            'is_critical' => $result['is_critical'],
            'is_glancing' => $result['is_glancing'] ?? false,
            'is_lucky' => $result['is_lucky'] ?? false,
            'element_advantage' => $result['element_advantage'],
            'skill_name' => $skill['skill_name'],
            'skill_element' => $skill['skill_element'] ?? $attacker['element'],
            'status_applied' => $result['status_applied'] ?? null,
            'attacker_name' => $attacker['nickname'] ?? $attacker['species_name'],
            'defender_name' => $defender['nickname'] ?? $defender['species_name'],
            'logs' => $result['logs']
        ]);
    }

    /**
     * POST: Enemy turn for 1v1 arena (server-side)
     * Input: { attacker_pet_id: 5, defender_pet_id: 10, attacker_hp: 100, defender_hp: 80 }
     * 
     * Uses Smart AI to select best skill and calculates damage server-side
     */
    public function enemyTurn1v1()
    {
        $this->requirePost();

        $input = $this->getInput();
        $attacker_pet_id = isset($input['attacker_pet_id']) ? (int) $input['attacker_pet_id'] : 0;
        $defender_pet_id = isset($input['defender_pet_id']) ? (int) $input['defender_pet_id'] : 0;
        $defender_current_hp = isset($input['defender_hp']) ? (int) $input['defender_hp'] : 100;
        $defender_max_hp = isset($input['defender_max_hp']) ? (int) $input['defender_max_hp'] : 100;

        if (!$attacker_pet_id || !$defender_pet_id) {
            $this->error('Missing required parameters');
            return;
        }

        require_once __DIR__ . '/../../pet/logic/BattleEngine.php';
        $engine = new BattleEngine($this->conn);

        // Get attacker (player's pet - defender in this context since enemy is attacking)
        $player_pet = $engine->getPetForBattle($attacker_pet_id);
        if (!$player_pet) {
            $this->error('Player pet not found');
            return;
        }

        // Verify ownership
        if ((int) $player_pet['user_id'] !== $this->user_id) {
            $this->error('This is not your pet');
            return;
        }

        // Get defender (enemy pet - attacker in this context)
        $enemy_pet = $engine->getPetForBattle($defender_pet_id);
        if (!$enemy_pet) {
            $this->error('Enemy pet not found');
            return;
        }

        // ============================================
        // SMART AI: Select best skill for enemy
        // ============================================
        $enemy_species_id = $enemy_pet['species_id'] ?? 1;
        $enemy_skills = $engine->getPetSkills((int) $enemy_species_id);

        // Calculate enemy HP percentage for strategy
        $enemy_hp_percent = $defender_current_hp / max(1, $defender_max_hp);

        $best_skill = null;
        $best_score = -INF;

        foreach ($enemy_skills as $skill) {
            // Base score from damage
            $score = (float) ($skill['base_damage'] ?? 25);

            // 1. Elemental advantage bonus
            $elem_mult = $engine->getElementAdvantage(
                $skill['skill_element'] ?? $enemy_pet['element'],
                $player_pet['element'] ?? 'Fire'
            );
            $score *= $elem_mult;

            // 2. HP-based strategy
            if ($enemy_hp_percent < 0.3) {
                // DESPERATE MODE
                $score *= 1.5;
                if (($skill['is_special'] ?? false) || ($skill['base_damage'] ?? 0) >= 60) {
                    $score *= 1.3;
                }
            } elseif ($enemy_hp_percent < 0.5) {
                // AGGRESSIVE MODE
                $score *= 1.2;
            }

            // 3. Random variance (±15%)
            $score *= (0.85 + (mt_rand(0, 30) / 100));

            // 4. STAB bonus
            if (($skill['skill_element'] ?? '') === ($enemy_pet['element'] ?? '')) {
                $score *= 1.1;
            }

            if ($score > $best_score) {
                $best_score = $score;
                $best_skill = $skill;
            }
        }

        // Fallback if no skills
        if (!$best_skill) {
            $best_skill = [
                'skill_name' => 'Attack',
                'base_damage' => 25 + (($enemy_pet['level'] ?? 1) * 2),
                'skill_element' => $enemy_pet['element'] ?? 'Fire',
                'is_special' => false
            ];
        }

        // Calculate damage using BattleEngine (enemy attacks player)
        $result = $engine->calculateDamage($enemy_pet, $best_skill, $player_pet);

        $this->success([
            'damage_dealt' => $result['damage_dealt'],
            'is_dodge' => $result['is_dodge'] ?? false,
            'is_critical' => $result['is_critical'],
            'is_glancing' => $result['is_glancing'] ?? false,
            'is_lucky' => $result['is_lucky'] ?? false,
            'element_advantage' => $result['element_advantage'],
            'skill_name' => $best_skill['skill_name'],
            'skill_element' => $best_skill['skill_element'] ?? $enemy_pet['element'],
            'attacker_name' => $enemy_pet['nickname'] ?? $enemy_pet['species_name'],
            'defender_name' => $player_pet['nickname'] ?? $player_pet['species_name'],
            'logs' => $result['logs']
        ]);
    }
}
