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

        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT pb.*, ps.name as pet_name, ps.element as pet_element,
                    ops.name as opponent_name, ops.element as opponent_element
             FROM pet_battles pb
             LEFT JOIN user_pets up ON pb.attacker_pet_id = up.id
             LEFT JOIN pet_species ps ON up.species_id = ps.id
             LEFT JOIN pet_species ops ON pb.defender_species_id = ops.id
             WHERE pb.user_id = ?
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
                             COUNT(CASE WHEN pb.won = 1 THEN 1 END) as wins,
                             COUNT(*) as total_battles
                      FROM pet_battles pb
                      JOIN nethera n ON pb.user_id = n.id_nethera
                      GROUP BY pb.user_id
                      ORDER BY wins DESC
                      LIMIT ?";
        } else {
            // Streak leaderboard
            $query = "SELECT n.username, n.nama_lengkap, 
                             MAX(pb.win_streak) as max_streak
                      FROM pet_battles pb
                      JOIN nethera n ON pb.user_id = n.id_nethera
                      GROUP BY pb.user_id
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
            "SELECT COUNT(*) as wins FROM pet_battles WHERE user_id = ? AND won = 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $this->success(['wins' => (int) $row['wins']]);
    }

    /**
     * GET: Get current streak
     */
    public function streak()
    {
        $this->requireGet();

        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT win_streak FROM pet_battles WHERE user_id = ? ORDER BY created_at DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $this->success(['streak' => $row ? (int) $row['win_streak'] : 0]);
    }
}
