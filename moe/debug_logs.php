<?php
/**
 * DEBUG LOG VIEWER
 * View the latest error logs from the server.
 */

// Define log directory match monitoring.php
$logDir = __DIR__ . '/logs';
$date = date('Y-m-d');
$logFile = $logDir . '/app_' . $date . '.log';
$errorFile = $logDir . '/errors_' . $date . '.json';

echo "<h1>Debug Logs ($date)</h1>";

// 1. Show Error JSON (Structured)
if (file_exists($errorFile)) {
    echo "<h2>Latest Errors (JSON)</h2>";
    $errors = json_decode(file_get_contents($errorFile), true);
    $errors = array_reverse($errors); // Show newest first
    echo "<pre>";
    foreach (array_slice($errors, 0, 5) as $val) {
        print_r($val);
        echo "\n------------------------------------------------\n";
    }
    echo "</pre>";
} else {
    echo "<p>No error JSON log found at $errorFile</p>";
}

// 2. Show Raw Text Log
if (file_exists($logFile)) {
    echo "<h2>Raw Log File (Last 2000 chars)</h2>";
    $content = file_get_contents($logFile);
    $len = strlen($content);
    $start = max(0, $len - 2000);
    echo "<pre>" . htmlspecialchars(substr($content, $start)) . "</pre>";
} else {
    echo "<p>No text log found at $logFile</p>";
}

// 3. Check PHP Error Log (System)
echo "<h2>System PHP Error Log</h2>";
$sysLog = ini_get('error_log');
if ($sysLog && file_exists($sysLog)) {
    echo "<p>Log path: $sysLog</p>";
    // Attempt to read (might be restricted)
    echo "<pre>" . htmlspecialchars(shell_exec("tail -n 20 $sysLog 2>&1")) . "</pre>";
} else {
    echo "<p>System error log not accessible or not defined.</p>";
}
?>