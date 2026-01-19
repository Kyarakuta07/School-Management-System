<?php
/**
 * MOE Pet System - Sanctuary War Controller
 * Handles war battles, matchmaking, and rewards
 */

require_once __DIR__ . '/../BaseController.php';

class SanctuaryWarController extends BaseController
{
    private $maxTickets = 3; // 3 battles per war per user

    /**
     * GET: Get current war status
     */
    public function getWarStatus()
    {
        $this->requireGet();

        // Get or create today's war (only on Saturday)
        $war = $this->getCurrentWar();

        if (!$war) {
            $this->success([
                'war_active' => false,
                'next_war' => $this->getNextWarDate(),
                'message' => 'No war active. Wars happen every Saturday!'
            ]);
            return;
        }

        // Get user's sanctuary
        $user_sanctuary = $this->getUserSanctuary();
        if (!$user_sanctuary) {
            $this->error('You must belong to a sanctuary to participate');
            return;
        }

        // Get user's tickets
        $tickets_used = $this->getTicketsUsed($war['id']);
        $tickets_remaining = $this->maxTickets - $tickets_used;

        // Get all sanctuary scores
        $scores = $this->getWarScores($war['id']);

        // Get user's contribution
        $contribution = $this->getUserContribution($war['id']);

        $this->success([
            'war_active' => true,
            'war_id' => $war['id'],
            'war_date' => $war['war_date'],
            'ends_at' => $war['war_date'] . ' 23:59:59',
            'your_sanctuary' => $user_sanctuary,
            'tickets_remaining' => $tickets_remaining,
            'tickets_total' => $this->maxTickets,
            'your_contribution' => $contribution,
            'standings' => $scores
        ]);
    }

    /**
     * POST: Start a war battle
     */
    public function startBattle()
    {
        $this->requirePost();

        // Check war is active
        $war = $this->getCurrentWar();
        if (!$war) {
            $this->error('No war is currently active');
            return;
        }

        // Check user has sanctuary
        $user_sanctuary = $this->getUserSanctuary();
        if (!$user_sanctuary) {
            $this->error('You must belong to a sanctuary');
            return;
        }

        // Check tickets
        $tickets_used = $this->getTicketsUsed($war['id']);
        if ($tickets_used >= $this->maxTickets) {
            $this->error('No battle tickets remaining today!');
            return;
        }

        // Get user's active pet
        $user_pet = $this->getActivePet();
        if (!$user_pet) {
            $this->error('You need an active pet to battle');
            return;
        }

        if ($user_pet['status'] === 'DEAD') {
            $this->error('Your pet is dead! Revive it first');
            return;
        }

        // Find opponent from different sanctuary
        $opponent = $this->findOpponent($user_sanctuary['id_sanctuary']);
        if (!$opponent) {
            $this->error('No opponents available. Try again later!');
            return;
        }

        // Simulate battle (using existing battle logic)
        $battle_result = $this->simulateBattle($user_pet, $opponent['pet']);

        // Calculate rewards
        $points = 0;
        $gold = 0;
        if ($battle_result['winner'] === 'user') {
            $points = 3;
            $gold = 5;
        } elseif ($battle_result['winner'] === 'opponent') {
            $points = 1;
            $gold = 2;
        } else {
            $points = 1;
            $gold = 3;
        }

        // Record battle
        $this->recordBattle(
            $war['id'],
            $opponent,
            $user_pet['id'],
            $opponent['pet']['id'],
            $battle_result['winner'] === 'user' ? $this->user_id : $opponent['user_id'],
            $points,
            $gold
        );

        // Update sanctuary score
        $this->updateSanctuaryScore(
            $war['id'],
            $user_sanctuary['id_sanctuary'],
            $battle_result['winner'] === 'user',
            $battle_result['winner'] === 'opponent',
            $battle_result['winner'] === 'tie'
        );

        // Give gold reward
        $this->addGold($gold);

        // Update tickets
        $this->useTicket($war['id']);

        $this->success([
            'battle_result' => $battle_result,
            'opponent' => [
                'name' => $opponent['nama_lengkap'],
                'sanctuary' => $opponent['sanctuary_name'],
                'pet_name' => $opponent['pet']['nickname'] ?? $opponent['pet']['species_name']
            ],
            'points_earned' => $points,
            'gold_earned' => $gold,
            'tickets_remaining' => $this->maxTickets - $tickets_used - 1
        ]);
    }

    /**
     * GET: Get last war results (recap for when no war is active)
     */
    public function getLastWarResults()
    {
        $this->requireGet();

        // Get the most recent completed war (not today's if it exists)
        $today = date('Y-m-d');
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT * FROM sanctuary_wars 
             WHERE war_date < ? 
             ORDER BY war_date DESC 
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "s", $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $lastWar = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$lastWar) {
            $this->success([
                'has_last_war' => false,
                'message' => 'No previous wars found'
            ]);
            return;
        }

        // Get final standings
        $standings = $this->getWarScores($lastWar['id']);

        // Determine winner (highest points)
        $winner = null;
        if (!empty($standings)) {
            $winner = $standings[0]; // Already sorted by total_points DESC
        }

        // Get MVP (top contributor across all sanctuaries)
        $mvp = $this->getWarMVP($lastWar['id']);

        // Get total participation stats
        $stats = $this->getWarStats($lastWar['id']);

        $this->success([
            'has_last_war' => true,
            'war_date' => $lastWar['war_date'],
            'war_date_formatted' => date('l, d M Y', strtotime($lastWar['war_date'])),
            'winner' => $winner,
            'standings' => $standings,
            'mvp' => $mvp,
            'stats' => $stats
        ]);
    }

    /**
     * Get MVP (top contributor) for a war
     */
    private function getWarMVP($war_id)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT 
                n.id_nethera as user_id,
                n.nama_lengkap as name,
                n.foto_profil as avatar,
                s.nama_sanctuary as sanctuary_name,
                SUM(wb.points_earned) as total_points,
                SUM(CASE WHEN wb.winner_user_id = n.id_nethera THEN 1 ELSE 0 END) as wins,
                COUNT(*) as battles
             FROM war_battles wb
             JOIN nethera n ON n.id_nethera = wb.user_id
             LEFT JOIN sanctuary s ON s.id_sanctuary = n.id_sanctuary
             WHERE wb.war_id = ?
             GROUP BY n.id_nethera
             ORDER BY total_points DESC, wins DESC
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $war_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $mvp = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($mvp) {
            $mvp['total_points'] = (int) $mvp['total_points'];
            $mvp['wins'] = (int) $mvp['wins'];
            $mvp['battles'] = (int) $mvp['battles'];
        }

        return $mvp;
    }

    /**
     * Get overall war statistics
     */
    private function getWarStats($war_id)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT 
                COUNT(DISTINCT user_id) as total_participants,
                COUNT(*) as total_battles,
                SUM(gold_earned) as total_gold_distributed
             FROM war_battles 
             WHERE war_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $war_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return [
            'participants' => (int) ($stats['total_participants'] ?? 0),
            'battles' => (int) ($stats['total_battles'] ?? 0),
            'gold_distributed' => (int) ($stats['total_gold_distributed'] ?? 0)
        ];
    }

    // ================================================
    // HELPER METHODS
    // ================================================

    private function getCurrentWar()
    {
        // Check if today is Saturday
        $today = date('Y-m-d');
        $dayOfWeek = date('w'); // 6 = Saturday

        if ($dayOfWeek != 6) {
            return null;
        }

        // Get or create war for today
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT * FROM sanctuary_wars WHERE war_date = ? AND status = 'active'"
        );
        mysqli_stmt_bind_param($stmt, "s", $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $war = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$war) {
            // Create new war
            $stmt = mysqli_prepare(
                $this->conn,
                "INSERT INTO sanctuary_wars (war_date, status) VALUES (?, 'active')"
            );
            mysqli_stmt_bind_param($stmt, "s", $today);
            mysqli_stmt_execute($stmt);
            $war_id = mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);

            // Initialize scores for all sanctuaries
            $this->initializeWarScores($war_id);

            return ['id' => $war_id, 'war_date' => $today, 'status' => 'active'];
        }

        return $war;
    }

    private function getNextWarDate()
    {
        $dayOfWeek = date('w');
        $daysUntilSaturday = (6 - $dayOfWeek + 7) % 7;
        if ($daysUntilSaturday == 0)
            $daysUntilSaturday = 7;
        return date('Y-m-d', strtotime("+$daysUntilSaturday days"));
    }

    private function getUserSanctuary()
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT s.* FROM sanctuary s 
             JOIN nethera n ON n.id_sanctuary = s.id_sanctuary 
             WHERE n.id_nethera = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $sanctuary = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $sanctuary;
    }

    private function getTicketsUsed($war_id)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT tickets_used FROM war_user_tickets WHERE war_id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $war_id, $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['tickets_used'] : 0;
    }

    private function useTicket($war_id)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO war_user_tickets (war_id, user_id, tickets_used) 
             VALUES (?, ?, 1) 
             ON DUPLICATE KEY UPDATE tickets_used = tickets_used + 1"
        );
        mysqli_stmt_bind_param($stmt, "ii", $war_id, $this->user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    private function initializeWarScores($war_id)
    {
        $sanctuaries = mysqli_query($this->conn, "SELECT id_sanctuary FROM sanctuary");
        while ($s = mysqli_fetch_assoc($sanctuaries)) {
            $stmt = mysqli_prepare(
                $this->conn,
                "INSERT IGNORE INTO sanctuary_war_scores (war_id, sanctuary_id) VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ii", $war_id, $s['id_sanctuary']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    private function getWarScores($war_id)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT s.nama_sanctuary, sws.total_points, sws.wins, sws.losses, sws.ties
             FROM sanctuary_war_scores sws
             JOIN sanctuary s ON s.id_sanctuary = sws.sanctuary_id
             WHERE sws.war_id = ?
             ORDER BY sws.total_points DESC"
        );
        mysqli_stmt_bind_param($stmt, "i", $war_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $scores = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $scores[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $scores;
    }

    private function getUserContribution($war_id)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT SUM(points_earned) as total_points, 
                    SUM(CASE WHEN winner_user_id = ? THEN 1 ELSE 0 END) as wins,
                    COUNT(*) as battles
             FROM war_battles 
             WHERE war_id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "iii", $this->user_id, $war_id, $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return [
            'points' => (int) ($row['total_points'] ?? 0),
            'wins' => (int) ($row['wins'] ?? 0),
            'battles' => (int) ($row['battles'] ?? 0)
        ];
    }

    private function findOpponent($exclude_sanctuary_id)
    {
        // Find random user from different sanctuary with active pet
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT n.id_nethera as user_id, n.nama_lengkap, s.nama_sanctuary as sanctuary_name,
                    s.id_sanctuary as sanctuary_id,
                    up.id as pet_id, up.nickname, up.level, up.health, up.species_id,
                    ps.name as species_name, ps.element, ps.base_attack, ps.base_defense
             FROM nethera n
             JOIN sanctuary s ON s.id_sanctuary = n.id_sanctuary
             JOIN user_pets up ON up.user_id = n.id_nethera AND up.is_active = 1 AND up.status = 'ALIVE'
             JOIN pet_species ps ON ps.id = up.species_id
             WHERE n.id_sanctuary != ? AND n.id_nethera != ? AND n.status_akun = 'Aktif'
             ORDER BY RAND()
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "ii", $exclude_sanctuary_id, $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $opponent = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$opponent)
            return null;

        return [
            'user_id' => $opponent['user_id'],
            'nama_lengkap' => $opponent['nama_lengkap'],
            'sanctuary_name' => $opponent['sanctuary_name'],
            'sanctuary_id' => $opponent['sanctuary_id'] ?? 0,
            'pet' => [
                'id' => $opponent['pet_id'],
                'nickname' => $opponent['nickname'],
                'species_name' => $opponent['species_name'],
                'level' => $opponent['level'],
                'health' => $opponent['health'],
                'element' => $opponent['element'],
                'attack' => $opponent['base_attack'] + ($opponent['level'] * 2),
                'defense' => $opponent['base_defense'] + ($opponent['level'] * 1)
            ]
        ];
    }

    private function getActivePet()
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT up.*, ps.name as species_name, ps.element, ps.base_attack, ps.base_defense
             FROM user_pets up
             JOIN pet_species ps ON ps.id = up.species_id
             WHERE up.user_id = ? AND up.is_active = 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $pet = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($pet) {
            $pet['attack'] = $pet['base_attack'] + ($pet['level'] * 2);
            $pet['defense'] = $pet['base_defense'] + ($pet['level'] * 1);
        }

        return $pet;
    }

    private function simulateBattle($user_pet, $opponent_pet)
    {
        // Simple battle simulation
        $user_power = $user_pet['attack'] + $user_pet['defense'] + $user_pet['level'];
        $opponent_power = $opponent_pet['attack'] + $opponent_pet['defense'] + $opponent_pet['level'];

        // Add randomness (±20%)
        $user_power *= (0.8 + (mt_rand(0, 40) / 100));
        $opponent_power *= (0.8 + (mt_rand(0, 40) / 100));

        if (abs($user_power - $opponent_power) < 5) {
            return ['winner' => 'tie', 'user_power' => $user_power, 'opponent_power' => $opponent_power];
        }

        return [
            'winner' => $user_power > $opponent_power ? 'user' : 'opponent',
            'user_power' => round($user_power),
            'opponent_power' => round($opponent_power)
        ];
    }

    private function recordBattle($war_id, $opponent, $user_pet_id, $opponent_pet_id, $winner_id, $points, $gold)
    {
        $user_sanctuary = $this->getUserSanctuary();

        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO war_battles (war_id, user_id, opponent_id, user_sanctuary_id, opponent_sanctuary_id, 
             user_pet_id, opponent_pet_id, winner_user_id, points_earned, gold_earned)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $opponent_sanctuary_id = $opponent['sanctuary_id'] ?? 0;
        $user_sanctuary_id = $user_sanctuary['id_sanctuary'];

        mysqli_stmt_bind_param(
            $stmt,
            "iiiiiiiiii",
            $war_id,
            $this->user_id,
            $opponent['user_id'],
            $user_sanctuary_id,
            $opponent_sanctuary_id,
            $user_pet_id,
            $opponent_pet_id,
            $winner_id,
            $points,
            $gold
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    private function updateSanctuaryScore($war_id, $sanctuary_id, $won, $lost, $tied)
    {
        $points = $won ? 3 : ($tied ? 1 : 1);
        $wins = $won ? 1 : 0;
        $losses = $lost ? 1 : 0;
        $ties = $tied ? 1 : 0;

        $stmt = mysqli_prepare(
            $this->conn,
            "UPDATE sanctuary_war_scores 
             SET total_points = total_points + ?, wins = wins + ?, losses = losses + ?, ties = ties + ?
             WHERE war_id = ? AND sanctuary_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "iiiiii", $points, $wins, $losses, $ties, $war_id, $sanctuary_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
