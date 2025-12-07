<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Login Debug Test</h2>";

echo "<p>1. Loading includes...</p>";
require_once 'includes/security_config.php';
session_start();
require_once 'includes/csrf.php';
require_once 'includes/rate_limiter.php';
include 'connection.php';

echo "✅ All includes loaded<br>";

echo "<p>2. Database connection...</p>";
if ($conn) {
    echo "✅ Database connected<br>";
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
    exit();
}

echo "<p>3. Test RateLimiter...</p>";
try {
    $limiter = new RateLimiter($conn);
    echo "✅ RateLimiter initialized<br>";
} catch (Exception $e) {
    echo "❌ RateLimiter error: " . $e->getMessage() . "<br>";
}

echo "<p>4. Test CSRF Token...</p>";
$token = generate_csrf_token();
echo "✅ CSRF Token: " . substr($token, 0, 30) . "...<br>";

echo "<p>5. Test Query...</p>";
$username_test = 'MATTHEW';
$sql = "SELECT id_nethera, nama_lengkap, username, role, status_akun, password
        FROM nethera
        WHERE username = ?";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username_test);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    if ($data) {
        echo "✅ User found: " . $data['username'] . "<br>";
        echo "- Role: " . $data['role'] . "<br>";
        echo "- Status: " . $data['status_akun'] . "<br>";
        echo "- Password hash: " . substr($data['password'], 0, 30) . "...<br>";

        // Test password verify
        $test_password = 'moe123';
        if (password_verify($test_password, $data['password'])) {
            echo "✅ Password 'moe123' MATCH!<br>";
        } else {
            echo "❌ Password 'moe123' NOT MATCH! Hash di database mungkin salah.<br>";
        }
    } else {
        echo "❌ User MATTHEW not found!<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "❌ Query prepare failed: " . mysqli_error($conn) . "<br>";
}

echo "<hr><p><strong>Debug complete!</strong></p>";
?>