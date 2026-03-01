<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Config/Paths.php';
$paths = new Config\Paths();
require_once __DIR__ . '/../system/Test/bootstrap.php';

$db = \Config\Database::connect();
$rewardService = new \App\Services\RewardService($db);

$userId = 1; // Assuming user 1 exists
try {
    $status = $rewardService->getDailyStatus($userId);
    echo "Status reported: " . json_encode($status) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
