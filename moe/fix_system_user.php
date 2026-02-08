<?php
/**
 * Fix System User (ID 0) - v2 Improved
 * 
 * Creates a "System" user with ID 0.
 * Handles cases where ID 0 is missing but email 'system@moe.local' already exists.
 */

require_once __DIR__ . '/config/connection.php';

echo "<h1>System User Fix (v2)</h1>";
echo "<pre>";

// Enable ID 0 insertion/updates
mysqli_query($conn, "SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");

// 1. Check if ID 0 exists
$check_id = mysqli_query($conn, "SELECT id_nethera FROM nethera WHERE id_nethera = 0");

if (mysqli_num_rows($check_id) > 0) {
    echo "✅ System User (ID 0) already exists. No action needed.\n";
} else {
    echo "⚠️ ID 0 not found. Checking for existing email...\n";

    // 2. Check if Email exists (but with wrong ID)
    $check_email = mysqli_query($conn, "SELECT id_nethera FROM nethera WHERE email = 'system@moe.local'");

    if (mysqli_num_rows($check_email) > 0) {
        // Email exists, update the ID to 0
        $row = mysqli_fetch_assoc($check_email);
        $old_id = $row['id_nethera'];

        echo "⚠️ Found 'system@moe.local' with ID $old_id. Updating to ID 0...\n";

        // Disable FK checks momentarily just in case
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");

        $update = "UPDATE nethera SET id_nethera = 0 WHERE id_nethera = $old_id";
        if (mysqli_query($conn, $update)) {
            echo "✅ Successfully updated User $old_id to ID 0.\n";
        } else {
            echo "❌ Failed to update ID: " . mysqli_error($conn) . "\n";
        }

        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

    } else {
        // 3. Create new user
        echo "⚠️ Creating new System User...\n";
        $pass = password_hash('SYSTEM_ACCOUNT_LOCKED', PASSWORD_DEFAULT);
        $sql = "INSERT INTO nethera (id_nethera, nama_lengkap, username, password, email, role, status_akun, gold) 
                VALUES (0, 'System Administrator', 'System', '$pass', 'system@moe.local', 'Vasiki', 'Aktif', 999999)";

        if (mysqli_query($conn, $sql)) {
            echo "✅ Successfully created System User (ID 0).\n";
        } else {
            echo "❌ Failed to create System User: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\nDone. You can now try claiming daily rewards.";
echo "</pre>";
?>