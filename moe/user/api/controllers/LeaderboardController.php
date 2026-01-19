<?php
/**
 * MOE Pet System - Leaderboard Controller
 * Handles pet rankings and leaderboard data
 */

require_once __DIR__ . '/../BaseController.php';

class LeaderboardController extends BaseController
{
    /**
     * GET: Get pet leaderboard
     * Params: sort (level|wins|power), element (fire|water|etc), limit (default 10)
     */
    public function getPetLeaderboard()
    {
        $this->requireGet();

        try {
            $sort = $_GET['sort'] ?? 'level';
            $element = $_GET['element'] ?? null;
            $period = $_GET['period'] ?? 'monthly'; // 'monthly' (default) or 'alltime'
            $limit = min(50, max(5, (int) ($_GET['limit'] ?? 10)));

            // Build query based on sort type and period
            $useMonthlyFilter = ($period === 'monthly');

            switch ($sort) {
                case 'wins':
                    if ($useMonthlyFilter) {
                        // Monthly: count wins from pet_battles this month
                        $orderBy = 'battle_wins DESC, up.level DESC';
                    } else {
                        // All-time: use persistent total_wins column
                        $orderBy = 'up.total_wins DESC, up.level DESC';
                    }
                    break;
                case 'power':
                    $orderBy = 'power_score DESC';
                    break;
                default:
                    $orderBy = 'up.level DESC, up.exp DESC';
            }

            $elementFilter = '';
            $params = [];
            $types = '';

            if ($element && $element !== 'all') {
                $elementFilter = 'AND ps.element = ?';
                $params[] = $element;
                $types .= 's';
            }

            // Different subquery for wins based on period
            if ($sort === 'wins' && $useMonthlyFilter) {
                // Monthly wins: count from pet_battles where created_at is this month
                $winsSubquery = "(SELECT COUNT(*) FROM pet_battles pb 
                                  WHERE pb.winner_pet_id = up.id 
                                  AND pb.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) as battle_wins";
            } else {
                // All time or non-wins sort: use pet_battles count or total_wins
                $winsSubquery = "COALESCE(up.total_wins, 0) as battle_wins";
            }

            $sql = "SELECT 
                        up.id as pet_id,
                        up.nickname,
                        up.level,
                        up.exp,
                        up.is_shiny,
                        up.evolution_stage,
                        COALESCE(up.total_wins, 0) as total_wins,
                        ps.name as species_name,
                        ps.element,
                        ps.rarity,
                        ps.base_attack,
                        ps.base_defense,
                        ps.img_egg, ps.img_baby, ps.img_adult,
                        n.nama_lengkap as owner_name,
                        (ps.base_attack + ps.base_defense + up.level * 3) as power_score,
                        $winsSubquery
                    FROM user_pets up
                    JOIN pet_species ps ON ps.id = up.species_id
                    JOIN nethera n ON n.id_nethera = up.user_id
                    WHERE up.status = 'ALIVE'
                    $elementFilter
                    ORDER BY $orderBy
                    LIMIT ?";

            $params[] = $limit;
            $types .= 'i';

            $stmt = mysqli_prepare($this->conn, $sql);

            if (!$stmt) {
                $this->error('Database error: ' . mysqli_error($this->conn));
                return;
            }

            if (!empty($types)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }

            if (!mysqli_stmt_execute($stmt)) {
                $this->error('Query failed: ' . mysqli_stmt_error($stmt));
                return;
            }

            $result = mysqli_stmt_get_result($stmt);

            $leaderboard = [];
            $rank = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $row['rank'] = $rank++;
                $row['current_image'] = $this->getPetImage($row);
                $leaderboard[] = $row;
            }
            mysqli_stmt_close($stmt);

            // Get available elements for filter
            $elements = $this->getAvailableElements();

            // Get current month name for UI
            $currentMonth = date('F Y');

            $this->success([
                'leaderboard' => $leaderboard,
                'sort' => $sort,
                'period' => $period,
                'current_month' => $currentMonth,
                'element_filter' => $element,
                'total_count' => count($leaderboard),
                'available_elements' => $elements
            ]);

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage());
        }
    }

    /**
     * GET: Get top contributors for Sanctuary War
     */
    public function getWarLeaderboard()
    {
        $this->requireGet();

        $limit = min(20, max(5, (int) ($_GET['limit'] ?? 10)));

        // Get current or last war
        $war_query = mysqli_query(
            $this->conn,
            "SELECT id FROM sanctuary_wars ORDER BY war_date DESC LIMIT 1"
        );
        $war = mysqli_fetch_assoc($war_query);

        if (!$war) {
            $this->success(['leaderboard' => [], 'message' => 'No wars yet']);
            return;
        }

        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT 
                n.nama_lengkap as player_name,
                s.nama_sanctuary as sanctuary_name,
                SUM(wb.points_earned) as total_points,
                SUM(CASE WHEN wb.winner_user_id = wb.user_id THEN 1 ELSE 0 END) as wins,
                COUNT(*) as battles
             FROM war_battles wb
             JOIN nethera n ON n.id_nethera = wb.user_id
             LEFT JOIN sanctuary s ON s.id_sanctuary = n.id_sanctuary
             WHERE wb.war_id = ?
             GROUP BY wb.user_id
             ORDER BY total_points DESC
             LIMIT ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $war['id'], $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $leaderboard = [];
        $rank = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $row['rank'] = $rank++;
            $leaderboard[] = $row;
        }
        mysqli_stmt_close($stmt);

        $this->success([
            'leaderboard' => $leaderboard,
            'war_id' => $war['id']
        ]);
    }

    // Helper: Get pet image based on evolution stage
    private function getPetImage($pet)
    {
        $stage = $pet['evolution_stage'] ?? 'egg';
        switch ($stage) {
            case 'adult':
                return $pet['img_adult'] ?: $pet['img_baby'] ?: $pet['img_egg'];
            case 'baby':
                return $pet['img_baby'] ?: $pet['img_egg'];
            default:
                return $pet['img_egg'];
        }
    }

    // Helper: Get available elements for filter
    private function getAvailableElements()
    {
        $result = mysqli_query(
            $this->conn,
            "SELECT DISTINCT element FROM pet_species WHERE element IS NOT NULL ORDER BY element"
        );
        $elements = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $elements[] = $row['element'];
        }
        return $elements;
    }
}
