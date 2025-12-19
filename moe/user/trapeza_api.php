<?php
/**
 * ⚠️ DEPRECATED - Trapeza API has been migrated
 * 
 * This monolithic API file (332 lines, 4 endpoints) has been  
 * refactored into a modern modular architecture.
 * 
 * NEW API LOCATION: api/router.php
 * 
 * All endpoints now route through: api/controllers/TrapezaController.php
 * - get_balance
 * - get_transactions
 * - transfer_gold
 * - search_nethera
 * 
 * Migration Date: 2025-12-19
 * 
 * This file will be deleted after monitoring period.
 * If you see this message, please update your code to use: api/router.php
 */

// Log any attempts to use old API (for monitoring)
error_log("DEPRECATED: trapeza_api.php accessed - Redirecting to api/router.php");

// Redirect to new API
$query_string = $_SERVER['QUERY_STRING'] ?? '';
header("Location: api/router.php?" . $query_string);
exit;
