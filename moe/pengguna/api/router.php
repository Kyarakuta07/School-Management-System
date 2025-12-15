<?php
/**
 * MOE Pet System - API Router
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Central entry point for all API requests.
 * Routes requests to appropriate controllers.
 * 
 * Usage: api/router.php?action=get_pets
 * 
 * This is the NEW modular version. The old pet_api.php is kept for 
 * backward compatibility but this is the recommended entry point.
 */

// ==================================================
// SETUP & AUTH
// ==================================================

require_once '../../includes/bootstrap.php';
require_once '../pet/pet_loader.php'; // Load pet logic functions

// API-specific response helpers
require_once '../../includes/api_response.php';

// Require Nethera auth (returns JSON error if not authenticated)
Auth::requireNetheraApi();

$user_id = Auth::id();
$conn = DB::getConnection();

// Initialize rate limiter
$rate_limiter = load_rate_limiter();

// ==================================================
// LOAD CONTROLLERS
// ==================================================

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/controllers/PetController.php';
require_once __DIR__ . '/controllers/ShopController.php';
require_once __DIR__ . '/controllers/GachaController.php';
require_once __DIR__ . '/controllers/BattleController.php';
require_once __DIR__ . '/controllers/EvolutionController.php';
require_once __DIR__ . '/controllers/RewardController.php';
require_once __DIR__ . '/controllers/TrapezaController.php';

// ==================================================
// ROUTE MAPPING
// ==================================================

$routes = [
    // Pet Controller
    'get_pets' => ['PetController', 'getPets'],
    'get_active_pet' => ['PetController', 'getActivePet'],
    'set_active' => ['PetController', 'setActive'],
    'rename' => ['PetController', 'rename'],
    'shelter' => ['PetController', 'shelter'],
    'sell_pet' => ['PetController', 'sellPet'],

    // Shop Controller
    'get_shop' => ['ShopController', 'getShop'],
    'get_inventory' => ['ShopController', 'getInventory'],
    'buy_item' => ['ShopController', 'buyItem'],
    'use_item' => ['ShopController', 'useItem'],

    // Gacha Controller
    'gacha' => ['GachaController', 'gacha'],

    // Battle Controller
    'get_opponents' => ['BattleController', 'getOpponents'],
    'battle' => ['BattleController', 'battle'],
    'battle_result' => ['BattleController', 'battleResult'],
    'battle_history' => ['BattleController', 'battleHistory'],
    'get_buff' => ['BattleController', 'getBuff'],
    'play_finish' => ['BattleController', 'playFinish'],
    'get_leaderboard' => ['BattleController', 'getLeaderboard'],
    'battle_wins' => ['BattleController', 'battleWins'],
    'streak' => ['BattleController', 'streak'],

    // Evolution Controller
    'get_evolution_candidates' => ['EvolutionController', 'getEvolutionCandidates'],
    'evolve_manual' => ['EvolutionController', 'evolveManual'],

    // Reward Controller
    'get_daily_reward' => ['RewardController', 'getDailyReward'],
    'claim_daily_reward' => ['RewardController', 'claimDailyReward'],
    'get_achievements' => ['RewardController', 'getAchievements'],

    // Trapeza Controller
    'get_balance' => ['TrapezaController', 'getBalance'],
    'get_transactions' => ['TrapezaController', 'getTransactions'],
    'transfer_gold' => ['TrapezaController', 'transferGold'],
    'search_nethera' => ['TrapezaController', 'searchNethera'],
];

// ==================================================
// ROUTING LOGIC
// ==================================================

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if (empty($action)) {
    api_error('Action parameter required', 'MISSING_ACTION', HTTP_BAD_REQUEST);
    exit;
}

if (!isset($routes[$action])) {
    $available = implode(', ', array_keys($routes));
    api_error("Unknown action: {$action}. Available actions: {$available}", 'UNKNOWN_ACTION', HTTP_NOT_FOUND);
    exit;
}

// Get controller and method
list($controller_name, $method) = $routes[$action];

// Instantiate controller
$controller = new $controller_name($conn, $user_id, $rate_limiter);

// Call method
try {
    $controller->$method();
} catch (Exception $e) {
    error_log("API Error [{$action}]: " . $e->getMessage());
    api_error('An error occurred: ' . $e->getMessage(), 'INTERNAL_ERROR', HTTP_INTERNAL_ERROR);
}
