<?php
/**
 * PRODUCTION DATABASE FIXER
 * 
 * Run this script by visiting: https://moegypt.com/moe/fix_production_database.php (or your domain)
 * This script bypasses MySQL version issues by checking and adding columns in PHP.
 */

require_once __DIR__ . '/config/connection.php';

// Disable error display to avoid breaking JSON output if desired, but here we want visible output
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Production Database Update</h1>";
echo "<pre>";

if (!isset($conn)) {
    die("❌ Database connection failed. Check config/connection.php");
}

function addColumn($table, $column, $definition)
{
    global $conn;
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($conn, $sql)) {
            echo "✅ Added column `$column` to `$table`\n";
        } else {
            echo "❌ Error adding `$column` to `$table`: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "ℹ️ Column `$column` already exists in `$table` (Skipped)\n";
    }
}

function createTable($table, $sql)
{
    global $conn;
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) == 0) {
        if (mysqli_query($conn, $sql)) {
            echo "✅ Created table `$table`\n";
        } else {
            echo "❌ Error creating table `$table`: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "ℹ️ Table `$table` already exists (Skipped)\n";
    }
}

function addIndex($table, $indexName, $column)
{
    global $conn;
    // Check if index exists
    $check = mysqli_query($conn, "SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "CREATE INDEX `$indexName` ON `$table`($column)";
        if (mysqli_query($conn, $sql)) {
            echo "✅ Added index `$indexName` to `$table`\n";
        } else {
            echo "❌ Error adding index `$indexName`: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "ℹ️ Index `$indexName` already exists on `$table` (Skipped)\n";
    }
}

// 1. Leaderboard Maintenance
echo "\n--- 1. Leaderboard Maintenance ---\n";
addColumn('user_pets', 'total_wins', 'INT DEFAULT 0');
addIndex('pet_battles', 'idx_pet_battles_created_at', 'created_at');
addIndex('pet_battles', 'idx_pet_battles_winner', 'winner_pet_id');

// 2. Leaderboard History
echo "\n--- 2. Leaderboard History ---\n";
$sql_history = "CREATE TABLE leaderboard_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_year VARCHAR(7) NOT NULL,
    `rank` INT NOT NULL,
    pet_id INT NOT NULL,
    sort_type VARCHAR(20) DEFAULT 'rank',
    score BIGINT DEFAULT 0,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_history_month (month_year)
)";
createTable('leaderboard_history', $sql_history);

addColumn('user_pets', 'current_streak', 'INT DEFAULT 0');
addColumn('user_pets', 'total_losses', 'INT DEFAULT 0');
addColumn('pet_species', 'base_health', 'INT DEFAULT 100');

// 3. Sanctuary Expansion
echo "\n--- 3. Sanctuary Expansion ---\n";
addColumn('sanctuary', 'gold', 'INT DEFAULT 0');

$sql_upgrades = "CREATE TABLE sanctuary_upgrades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sanctuary_id INT NOT NULL,
    upgrade_type VARCHAR(50) NOT NULL,
    level INT DEFAULT 1,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sanctuary_id) REFERENCES sanctuary(id_sanctuary) ON DELETE CASCADE
)";
createTable('sanctuary_upgrades', $sql_upgrades);

$sql_claims = "CREATE TABLE sanctuary_daily_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sanctuary_id INT NOT NULL,
    last_claim DATETIME DEFAULT NULL,
    UNIQUE KEY unique_claim (user_id, sanctuary_id)
)";
createTable('sanctuary_daily_claims', $sql_claims);

// Update Potions
echo "\n--- Updating Items ---\n";
// Check if 'items' table exists, if not try 'shop_items'
$checkItems = mysqli_query($conn, "SHOW TABLES LIKE 'items'");
if (mysqli_num_rows($checkItems) > 0) {
    mysqli_query($conn, "UPDATE items SET effect_value = 200 WHERE item_name LIKE '%Potion%'");
    echo "✅ Updated Potion values in `items` table\n";
} else {
    // Try shop_items
    $checkShopItems = mysqli_query($conn, "SHOW TABLES LIKE 'shop_items'");
    if (mysqli_num_rows($checkShopItems) > 0) {
        // Check if effect_value column exists in shop_items
        $checkCol = mysqli_query($conn, "SHOW COLUMNS FROM `shop_items` LIKE 'effect_value'");
        if (mysqli_num_rows($checkCol) > 0) {
            mysqli_query($conn, "UPDATE shop_items SET effect_value = 200 WHERE name LIKE '%Potion%'");
            echo "✅ Updated Potion values in `shop_items` table\n";
        } else {
            echo "ℹ️ Column `effect_value` not found in `shop_items` (Skipped)\n";
        }
    } else {
        echo "ℹ️ Neither `items` nor `shop_items` table found (Skipped)\n";
    }
}

// 4. Leaderboard Rank Points
echo "\n--- 4. Leaderboard Rank Points ---\n";
addColumn('user_pets', 'rank_points', 'INT DEFAULT 1000 AFTER total_losses');
addIndex('user_pets', 'idx_rank_points', 'rank_points');

// 5. Class Subjects Update (from 012)
echo "\n--- 5. Class Subjects Update ---\n";
addColumn('class_grades', 'pop_culture', 'INT DEFAULT 0');
addColumn('class_grades', 'mythology', 'INT DEFAULT 0');
addColumn('class_grades', 'history_of_egypt', 'INT DEFAULT 0');

// Recalculate total_pp
$sql_update_pp = "UPDATE class_grades 
SET total_pp = COALESCE(history, 0) + COALESCE(oceanology, 0) + COALESCE(astronomy, 0) 
             + COALESCE(pop_culture, 0) + COALESCE(mythology, 0) + COALESCE(history_of_egypt, 0)";
if (mysqli_query($conn, $sql_update_pp)) {
    echo "✅ Recalculated total_pp for all students\n";
} else {
    echo "❌ Error updating total_pp: " . mysqli_error($conn) . "\n";
}


// 6. Nethera Roles Update (sanctuary_role)
echo "\n--- 6. Nethera Roles Update ---\n";
addColumn('nethera', 'sanctuary_role', "ENUM('member', 'vizier', 'hosa') DEFAULT 'member'");
addIndex('nethera', 'idx_sanctuary_role', 'sanctuary_role');

echo "\n\n=== UPDATE COMPLETE ===\n";
echo "You can now delete this file from your server.";
echo "</pre>";
?>