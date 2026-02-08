<?php
/**
 * Leaderboard Season Reset Cron Script
 * 
 * Run this script on the 1st of each month (via Windows Task Scheduler or manual trigger)
 * Command: php "C:\xampp\htdocs\School-Management-System\moe\cron\leaderboard_season_reset.php"
 * 
 * Functions:
 * 1. Archive Top 3 to leaderboard_history (Hall of Fame)
 * 2. Distribute Gold rewards to all active participants
 * 3. Reset monthly rank_points to base ELO (1000)
 * 4. Log all transactions to trapeza_transactions
 */

// CLI or Web execution check
$isCLI = (php_sapi_name() === 'cli');
if (!$isCLI) {
    // Web execution requires secret key for security
    $providedSecret = $_GET['secret'] ?? '';
    $expectedSecret = 'moe_season_reset_2025'; // Change this to a secure value
    if ($providedSecret !== $expectedSecret) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Unauthorized']));
    }
}

// Load database connection
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../core/database.php';

// Initialize DB wrapper (optional but good practice)
if (class_exists('DB') && isset($conn)) {
    DB::init($conn);
}

// Configuration
define('BASE_ELO', 1000);
define('REWARD_RANK_1', 500);
define('REWARD_RANK_2', 300);
define('REWARD_RANK_3', 150);
define('REWARD_PARTICIPATION', 50);

// Logging function
function logMessage($message)
{
    global $isCLI;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] $message\n";

    if ($isCLI) {
        echo $logLine;
    }

    // Also write to log file
    $logFile = __DIR__ . '/logs/season_reset_' . date('Y-m') . '.log';
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

// Helper to get valid System Sender ID (handles missing User 0)
function getSystemSenderId($conn)
{
    // 1. Try generic ID 0 (if exists)
    $res = mysqli_query($conn, "SELECT id_nethera FROM nethera WHERE id_nethera = 0");
    if (mysqli_num_rows($res) > 0)
        return 0;

    // 2. Try to find 'System' user
    $res = mysqli_query($conn, "SELECT id_nethera FROM nethera WHERE username = 'System'");
    if ($row = mysqli_fetch_assoc($res))
        return $row['id_nethera'];

    // 3. Create 'System' user if missing
    // We let auto-increment decide index to avoid conflicts
    $pass = password_hash('SYSTEM_ACCOUNT_' . time(), PASSWORD_DEFAULT);
    $insert = mysqli_query($conn, "INSERT INTO nethera (nama_lengkap, username, password, email, role, status_akun, gold) 
                                   VALUES ('System Account', 'System', '$pass', 'system@moe.local', 'Vasiki', 'Aktif', 999999)");
    if ($insert) {
        return mysqli_insert_id($conn);
    }

    // 4. Fallback to first admin
    $res = mysqli_query($conn, "SELECT id_nethera FROM nethera WHERE role = 'admin' LIMIT 1");
    if ($row = mysqli_fetch_assoc($res))
        return $row['id_nethera'];

    // 5. Fallback to ID 1 (unsafe but last resort)
    return 1;
}

// Main execution
try {
    $conn = $GLOBALS['conn'];
    $currentMonth = date('Y-m');
    $monthLabel = date('F Y'); // e.g., "February 2026"

    logMessage("=== Season Reset Started for $monthLabel ===");

    // Get valid System Sender ID for trapeza transactions
    $systemSenderId = getSystemSenderId($conn);
    logMessage("Using System Sender ID: $systemSenderId");

    // 1. Check if reset already done this month
    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) as count FROM leaderboard_history WHERE month_year = ?"
    );
    mysqli_stmt_bind_param($checkStmt, "s", $currentMonth);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existingReset = mysqli_fetch_assoc($checkResult)['count'];
    mysqli_stmt_close($checkStmt);

    if ($existingReset > 0) {
        logMessage("⚠️ Season already reset for $currentMonth. Aborting to prevent duplicate rewards.");
        if (!$isCLI) {
            echo json_encode(['success' => false, 'error' => 'Season already reset for this month']);
        }
        exit(0);
    }

    // 2. Get all active participants (rank_points > BASE_ELO or has battles this month)
    $currentMonthStart = date('Y-m-01');
    $participantsQuery = "
        SELECT 
            up.id as pet_id,
            up.user_id,
            up.nickname,
            up.rank_points,
            ps.name as species_name,
            (SELECT COUNT(*) FROM pet_battles pb 
             WHERE (pb.attacker_pet_id = up.id OR pb.defender_pet_id = up.id) 
             AND pb.created_at >= ?) as monthly_battles
        FROM user_pets up
        JOIN pet_species ps ON ps.id = up.species_id
        WHERE up.status = 'ALIVE'
        AND (up.rank_points > ? OR EXISTS (
            SELECT 1 FROM pet_battles pb2 
            WHERE (pb2.attacker_pet_id = up.id OR pb2.defender_pet_id = up.id) 
            AND pb2.created_at >= ?
        ))
        ORDER BY up.rank_points DESC
    ";

    $stmt = mysqli_prepare($conn, $participantsQuery);
    $baseElo = BASE_ELO; // Variable needed for bind_param (pass by reference)
    mysqli_stmt_bind_param($stmt, "sis", $currentMonthStart, $baseElo, $currentMonthStart);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $participants = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $participants[] = $row;
    }
    mysqli_stmt_close($stmt);

    $totalParticipants = count($participants);
    logMessage("Found $totalParticipants active participants");

    if ($totalParticipants === 0) {
        logMessage("⚠️ No active participants found. Nothing to process.");
        if (!$isCLI) {
            echo json_encode(['success' => true, 'message' => 'No participants to reward']);
        }
        exit(0);
    }

    // 3. Process rewards
    $totalGoldDistributed = 0;
    $rewardsGiven = 0;

    mysqli_begin_transaction($conn);

    foreach ($participants as $rank => $participant) {
        $actualRank = $rank + 1; // 1-indexed
        $userId = $participant['user_id'];
        $petId = $participant['pet_id'];
        $petName = $participant['nickname'] ?: $participant['species_name'];
        $rankPoints = $participant['rank_points'];

        // Determine reward amount
        switch ($actualRank) {
            case 1:
                $reward = REWARD_RANK_1;
                break;
            case 2:
                $reward = REWARD_RANK_2;
                break;
            case 3:
                $reward = REWARD_RANK_3;
                break;
            default:
                $reward = REWARD_PARTICIPATION;
                break;
        }

        // 3a. Add gold to user
        $updateGold = mysqli_prepare($conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
        mysqli_stmt_bind_param($updateGold, "ii", $reward, $userId);
        mysqli_stmt_execute($updateGold);
        mysqli_stmt_close($updateGold);

        // 3b. Log to trapeza_transactions
        $description = "Season Reward: Rank #$actualRank ($petName)";
        $insertTrapeza = mysqli_prepare(
            $conn,
            "INSERT INTO trapeza_transactions (sender_id, receiver_id, amount, transaction_type, description, status) 
             VALUES (?, ?, ?, 'season_reward', ?, 'completed')"
        );
        mysqli_stmt_bind_param($insertTrapeza, "iiis", $systemSenderId, $userId, $reward, $description);
        mysqli_stmt_execute($insertTrapeza);
        mysqli_stmt_close($insertTrapeza);

        // 3c. Archive Top 3 to Hall of Fame
        if ($actualRank <= 3) {
            $insertHistory = mysqli_prepare(
                $conn,
                "INSERT INTO leaderboard_history (month_year, `rank`, pet_id, sort_type, score) 
                 VALUES (?, ?, ?, 'rank', ?)"
            );
            mysqli_stmt_bind_param($insertHistory, "siid", $currentMonth, $actualRank, $petId, $rankPoints);
            mysqli_stmt_execute($insertHistory);
            mysqli_stmt_close($insertHistory);

            logMessage("🏆 Rank #$actualRank: $petName (User $userId) -> +$reward G [Hall of Fame]");
        } else {
            logMessage("   Rank #$actualRank: $petName (User $userId) -> +$reward G");
        }

        $totalGoldDistributed += $reward;
        $rewardsGiven++;
    }

    // 4. Reset all rank_points to BASE_ELO
    $baseEloReset = BASE_ELO; // Variable needed for bind_param
    $resetStmt = mysqli_prepare($conn, "UPDATE user_pets SET rank_points = ? WHERE status = 'ALIVE'");
    mysqli_stmt_bind_param($resetStmt, "i", $baseEloReset);
    mysqli_stmt_execute($resetStmt);
    $resetCount = mysqli_affected_rows($conn);
    mysqli_stmt_close($resetStmt);

    logMessage("🔄 Reset $resetCount pets to base ELO ($BASE_ELO)");

    mysqli_commit($conn);

    logMessage("=== Season Reset Complete ===");
    logMessage("Total Rewards: $rewardsGiven players, $totalGoldDistributed Gold distributed");

    if (!$isCLI) {
        echo json_encode([
            'success' => true,
            'message' => 'Season reset complete',
            'rewards_given' => $rewardsGiven,
            'total_gold' => $totalGoldDistributed,
            'month' => $monthLabel
        ]);
    }

} catch (Exception $e) {
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    logMessage("❌ ERROR: " . $e->getMessage());

    if (!$isCLI) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit(1);
}
