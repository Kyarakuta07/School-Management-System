<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Include Files...</h2>";

echo "<p>1. Testing security_config.php...</p>";
try {
    require_once 'includes/security_config.php';
    echo "✅ security_config.php OK<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

echo "<p>2. Testing config.php...</p>";
try {
    require_once 'includes/config.php';
    echo "✅ config.php OK<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

echo "<p>3. Testing csrf.php...</p>";
session_start();
try {
    require_once 'includes/csrf.php';
    $token = generate_csrf_token();
    echo "✅ csrf.php OK (Token: " . substr($token, 0, 20) . "...)<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

echo "<p>4. Testing sanitization.php...</p>";
try {
    require_once 'includes/sanitization.php';
    $test = validate_email('test@example.com');
    echo "✅ sanitization.php OK (Email test: $test)<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

echo "<p>5. Testing rate_limiter.php...</p>";
try {
    include 'connection.php';
    require_once 'includes/rate_limiter.php';
    $limiter = new RateLimiter($conn);
    echo "✅ rate_limiter.php OK<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

echo "<hr><p><strong>All tests completed!</strong></p>";
?>