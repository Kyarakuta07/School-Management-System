<?php
/**
 * MOE Pet System - Daily Reward Logic
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles daily login rewards and streak tracking
 * Uses table: daily_login_streak
 */

/**
 * Get daily reward status for user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Status info with can_claim, reward details
 */
function getDailyRewardStatus($conn, $user_id)
{
    $today = date('Y-m-d');

    // Get or create user streak record
    $stmt = mysqli_prepare($conn, "SELECT * FROM daily_login_streak WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $streak = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Create new record if doesn't exist
    if (!$streak) {
        $stmt = mysqli_prepare($conn, "INSERT INTO daily_login_streak (user_id, current_day, last_claim_date, total_logins) VALUES (?, 1, NULL, 0)");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Fetch the newly created record
        $stmt = mysqli_prepare($conn, "SELECT * FROM daily_login_streak WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $streak = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    // Check if already claimed today
    if ($streak['last_claim_date'] === $today) {
        return [
            'success' => true,
            'can_claim' => false,
            'message' => 'Already claimed reward today! Come back tomorrow.',
            'current_day' => $streak['current_day'],
            'total_logins' => $streak['total_logins']
        ];
    }

    // Calculate current day (reset to 1 after day 30)
    $currentDay = $streak['current_day'];

    // Check if streak was broken (more than 1 day gap)
    if ($streak['last_claim_date']) {
        $lastClaim = strtotime($streak['last_claim_date']);
        $todayTimestamp = strtotime($today);
        $dayDiff = ($todayTimestamp - $lastClaim) / 86400;

        if ($dayDiff > 1) {
            // Streak broken, reset to day 1
            $currentDay = 1;
        }
    }

    // Calculate reward for current day
    $reward = calculateDailyReward($currentDay);

    return [
        'success' => true,
        'can_claim' => true,
        'current_day' => $currentDay,
        'total_logins' => $streak['total_logins'],
        'reward_gold' => $reward['gold'],
        'reward_item_id' => $reward['item_id'] ?? null,
        'reward_item_name' => $reward['item_name'] ?? null
    ];
}

/**
 * Claim daily reward for user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Result with claimed rewards
 */
function claimDailyReward($conn, $user_id)
{
    $today = date('Y-m-d');

    // Check if can claim
    $status = getDailyRewardStatus($conn, $user_id);
    if (!$status['can_claim']) {
        return [
            'success' => false,
            'error' => $status['message'] ?? 'Cannot claim reward'
        ];
    }

    mysqli_begin_transaction($conn);

    try {
        $currentDay = $status['current_day'];
        $nextDay = ($currentDay >= 30) ? 1 : ($currentDay + 1);

        // Update streak record
        $stmt = mysqli_prepare($conn, "
            UPDATE daily_login_streak 
            SET current_day = ?, 
                last_claim_date = ?, 
                total_logins = total_logins + 1 
            WHERE user_id = ?
        ");
        mysqli_stmt_bind_param($stmt, "isi", $nextDay, $today, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Give gold reward
        $goldReceived = 0;
        if ($status['reward_gold'] > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
            mysqli_stmt_bind_param($stmt, "ii", $status['reward_gold'], $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $goldReceived = $status['reward_gold'];
        }

        // Give item reward (if applicable)
        $itemReceived = null;
        if ($status['reward_item_id']) {
            $stmt = mysqli_prepare($conn, "
                INSERT INTO user_inventory (user_id, item_id, quantity) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE quantity = quantity + 1
            ");
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $status['reward_item_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $itemReceived = $status['reward_item_name'];
        }

        mysqli_commit($conn);

        return [
            'success' => true,
            'claimed_day' => $currentDay,
            'gold_received' => $goldReceived,
            'item_received' => $itemReceived,
            'next_day' => $nextDay,
            'total_logins' => $status['total_logins'] + 1
        ];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return [
            'success' => false,
            'error' => 'Failed to claim reward: ' . $e->getMessage()
        ];
    }
}

/**
 * Calculate reward for specific day
 * 
 * @param int $day Day number (1-30)
 * @return array Reward details
 */
function calculateDailyReward($day)
{
    // Base gold reward
    $goldReward = 50 + ($day * 5); // Increases with day

    // Special rewards on milestone days
    $itemReward = null;
    $itemName = null;

    switch ($day) {
        case 7:  // Week 1
            $goldReward = 200;
            $itemReward = getItemIdByName('Basic Food Pack');
            $itemName = 'Basic Food Pack';
            break;
        case 14: // Week 2
            $goldReward = 400;
            $itemReward = getItemIdByName('Health Potion');
            $itemName = 'Health Potion';
            break;
        case 21: // Week 3
            $goldReward = 600;
            $itemReward = getItemIdByName('Evolution Stone');
            $itemName = 'Evolution Stone';
            break;
        case 28: // Week 4
            $goldReward = 800;
            $itemReward = getItemIdByName('Rare Gacha Ticket');
            $itemName = 'Rare Gacha Ticket';
            break;
        case 30: // Month complete
            $goldReward = 1000;
            $itemReward = getItemIdByName('Epic Gacha Ticket');
            $itemName = 'Epic Gacha Ticket';
            break;
    }

    return [
        'gold' => $goldReward,
        'item_id' => $itemReward,
        'item_name' => $itemName
    ];
}

/**
 * Helper: Get item ID by name (for reward system)
 * 
 * @param string $itemName Item name
 * @return int|null Item ID or null if not found
 */
function getItemIdByName($itemName)
{
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT id FROM shop_items WHERE name = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $itemName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return (int) $row['id'];
    }

    mysqli_stmt_close($stmt);
    return null;
}
