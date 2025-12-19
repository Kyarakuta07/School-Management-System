<?php
/**
 * Health Check API Endpoint
 * Mediterranean of Egypt - School Management System
 * 
 * Returns application health status in JSON format.
 * 
 * Usage: GET /moe/api/health.php
 * 
 * @author MOE Development Team
 */

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/json');

// Load dependencies
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../../core/monitoring.php';

// Run health checks
$results = HealthCheck::runAll();

// Set appropriate HTTP status
$status_code = $results['_overall'] ? 200 : 503;
http_response_code($status_code);

// Output results
echo json_encode([
    'status' => $results['_overall'] ? 'healthy' : 'unhealthy',
    'timestamp' => $results['_timestamp'],
    'checks' => array_filter($results, function ($key) {
        return $key[0] !== '_';
    }, ARRAY_FILTER_USE_KEY),
    'version' => '1.0.0'
], JSON_PRETTY_PRINT);
