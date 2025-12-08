<?php
/**
 * MOE Pet System - API Endpoints
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * AJAX endpoints for frontend communication
 * All responses are JSON formatted using standardized response helper
 */

require_once '../includes/security_config.php';
session_start();
header('Content-Type: application/json');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include '../connection.php';
include 'pet/pet_loader.php';
require_once '../includes/rate_limiter.php';
require_once '../includes/api_response.php';

// ================================================
// AUTHENTICATION CHECK
// ================================================

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Nethera') {
    api_unauthorized();
}

$user_id = $_SESSION['id_nethera'];

// Initialize rate limiter for API endpoints
$api_limiter = new RateLimiter($conn);

// ================================================
// ROUTING
// ================================================

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {

    // ============================================
    // GET: Fetch user's pets
    // ============================================
    case 'get_pets':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $pets = getUserPetsWithStats($conn, $user_id);
        echo json_encode([
            'success' => true,
            'pets' => $pets,
            'count' => count($pets)
        ]);
        break;

    // ============================================
    // GET: Get active pet with updated stats
    // ============================================
    case 'get_active_pet':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $pets = getUserPetsWithStats($conn, $user_id);
        $active_pet = null;

        foreach ($pets as $pet) {
            if ($pet['is_active']) {
                $active_pet = $pet;
                break;
            }
        }

        echo json_encode([
            'success' => true,
            'pet' => $active_pet
        ]);
        break;

    // ============================================
    // GET: Get shop items
    // ============================================
    case 'get_shop':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $result = mysqli_query($conn, "SELECT * FROM shop_items WHERE is_available = 1 ORDER BY effect_type, price");
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }

        // Get user's gold (assuming column exists in nethera table)
        $gold_stmt = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
        mysqli_stmt_bind_param($gold_stmt, "i", $user_id);
        mysqli_stmt_execute($gold_stmt);
        $gold_result = mysqli_stmt_get_result($gold_stmt);
        $user_gold = 0;
        if ($gold_row = mysqli_fetch_assoc($gold_result)) {
            $user_gold = $gold_row['gold'] ?? 0;
        }
        mysqli_stmt_close($gold_stmt);

        echo json_encode([
            'success' => true,
            'items' => $items,
            'user_gold' => $user_gold
        ]);
        break;

    // ============================================
    // GET: Get user's inventory
    // ============================================
    case 'get_inventory':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $query = "SELECT ui.*, si.name, si.description, si.effect_type, si.effect_value, si.img_path
                  FROM user_inventory ui
                  JOIN shop_items si ON ui.item_id = si.id
                  WHERE ui.user_id = ? AND ui.quantity > 0
                  ORDER BY si.effect_type";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $inventory = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $inventory[] = $row;
        }
        mysqli_stmt_close($stmt);

        echo json_encode([
            'success' => true,
            'inventory' => $inventory
        ]);
        break;

    // ============================================
    // POST: Perform gacha roll
    // ============================================
    case 'gacha':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        // Rate limiting - 20 gacha rolls per hour per user
        $gacha_limit = $api_limiter->checkLimit($user_id, 'gacha', 20, 60);
        if (!$gacha_limit['allowed']) {
            api_rate_limited($gacha_limit['locked_until']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $gacha_type = isset($input['type']) ? (int) $input['type'] : 1;

        // Determine cost
        $cost = GACHA_COST_NORMAL;
        if ($gacha_type == 2)
            $cost = 150;
        if ($gacha_type == 3)
            $cost = 500;

        // Check user has enough gold
        $gold_check = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
        mysqli_stmt_bind_param($gold_check, "i", $user_id);
        mysqli_stmt_execute($gold_check);
        $gold_result = mysqli_stmt_get_result($gold_check);
        $user_gold = 0;
        if ($gold_row = mysqli_fetch_assoc($gold_result)) {
            $user_gold = $gold_row['gold'] ?? 0;
        }
        mysqli_stmt_close($gold_check);

        if ($user_gold < $cost) {
            api_insufficient_funds($cost, $user_gold);
        }

        // Deduct gold
        $deduct_stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold - ? WHERE id_nethera = ?");
        mysqli_stmt_bind_param($deduct_stmt, "ii", $cost, $user_id);
        mysqli_stmt_execute($deduct_stmt);
        mysqli_stmt_close($deduct_stmt);

        // Perform gacha
        $result = performGacha($conn, $user_id, $gacha_type);
        $result['cost'] = $cost;
        $result['remaining_gold'] = $user_gold - $cost;

        echo json_encode($result);
        break;

    // ============================================
    // POST: Buy item from shop
    // ============================================
    case 'buy_item':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $item_id = isset($input['item_id']) ? (int) $input['item_id'] : 0;
        $quantity = isset($input['quantity']) ? max(1, (int) $input['quantity']) : 1;

        if (!$item_id) {
            echo json_encode(['success' => false, 'error' => 'Item ID required']);
            break;
        }

        // Get item price
        $item_check = mysqli_prepare($conn, "SELECT * FROM shop_items WHERE id = ? AND is_available = 1");
        mysqli_stmt_bind_param($item_check, "i", $item_id);
        mysqli_stmt_execute($item_check);
        $item_result = mysqli_stmt_get_result($item_check);
        $item = mysqli_fetch_assoc($item_result);
        mysqli_stmt_close($item_check);

        if (!$item) {
            echo json_encode(['success' => false, 'error' => 'Item not found or unavailable']);
            break;
        }

        $total_cost = $item['price'] * $quantity;

        // Check user gold
        $gold_check = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
        mysqli_stmt_bind_param($gold_check, "i", $user_id);
        mysqli_stmt_execute($gold_check);
        $gold_result = mysqli_stmt_get_result($gold_check);
        $user_gold = 0;
        if ($gold_row = mysqli_fetch_assoc($gold_result)) {
            $user_gold = $gold_row['gold'] ?? 0;
        }
        mysqli_stmt_close($gold_check);

        if ($user_gold < $total_cost) {
            echo json_encode([
                'success' => false,
                'error' => "Not enough gold! Need $total_cost, have $user_gold."
            ]);
            break;
        }

        // Deduct gold
        $deduct_stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold - ? WHERE id_nethera = ?");
        mysqli_stmt_bind_param($deduct_stmt, "ii", $total_cost, $user_id);
        mysqli_stmt_execute($deduct_stmt);
        mysqli_stmt_close($deduct_stmt);

        // Add to inventory (use INSERT ... ON DUPLICATE KEY UPDATE)
        $inv_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO user_inventory (user_id, item_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + ?"
        );
        mysqli_stmt_bind_param($inv_stmt, "iiii", $user_id, $item_id, $quantity, $quantity);
        mysqli_stmt_execute($inv_stmt);
        mysqli_stmt_close($inv_stmt);

        echo json_encode([
            'success' => true,
            'message' => "Purchased {$quantity}x {$item['name']}!",
            'remaining_gold' => $user_gold - $total_cost
        ]);
        break;

    // ============================================
    // POST: Use item on pet (Updated for Bulk)
    // ============================================
    case 'use_item':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;
        $item_id = isset($input['item_id']) ? (int) $input['item_id'] : 0;

        // AMBIL QUANTITY (Default 1 jika tidak ada)
        $quantity = isset($input['quantity']) ? max(1, (int) $input['quantity']) : 1;

        if (!$item_id) { // Pet ID boleh 0 kalau gacha ticket
            echo json_encode(['success' => false, 'error' => 'Item ID required']);
            break;
        }

        // Kirim quantity ke logic
        $result = useItemOnPet($conn, $user_id, $pet_id, $item_id, $quantity);
        echo json_encode($result);
        break;

    // ============================================
    // POST: Set active pet
    // ============================================
    case 'set_active':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;

        if (!$pet_id) {
            echo json_encode(['success' => false, 'error' => 'Pet ID required']);
            break;
        }

        $result = setActivePet($conn, $user_id, $pet_id);
        echo json_encode($result);
        break;

    // ============================================
    // POST: Rename pet
    // ============================================
    case 'rename':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;
        $nickname = isset($input['nickname']) ? trim($input['nickname']) : '';

        if (!$pet_id) {
            echo json_encode(['success' => false, 'error' => 'Pet ID required']);
            break;
        }

        // Sanitize nickname
        $nickname = htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8');
        if (strlen($nickname) > 50) {
            $nickname = substr($nickname, 0, 50);
        }

        // Verify ownership
        $stmt = mysqli_prepare($conn, "UPDATE user_pets SET nickname = ? WHERE id = ? AND user_id = ?");
        $nickname_value = empty($nickname) ? null : $nickname;
        mysqli_stmt_bind_param($stmt, "sii", $nickname_value, $pet_id, $user_id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($conn);
        mysqli_stmt_close($stmt);

        if ($affected > 0) {
            echo json_encode(['success' => true, 'message' => 'Pet renamed successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Pet not found or not yours']);
        }
        break;

    // ============================================
    // POST: Toggle shelter mode
    // ============================================
    case 'shelter':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;

        if (!$pet_id) {
            echo json_encode(['success' => false, 'error' => 'Pet ID required']);
            break;
        }

        $result = toggleShelter($conn, $user_id, $pet_id);
        echo json_encode($result);
        break;

    // ============================================
    // GET: Get battle opponents (other users' active pets)
    // ============================================
    case 'get_opponents':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $query = "SELECT up.id as pet_id, up.level, up.nickname,
                         ps.name as species_name, ps.element, ps.rarity, ps.img_adult,
                         n.nama_lengkap as owner_name
                  FROM user_pets up
                  JOIN pet_species ps ON up.species_id = ps.id
                  JOIN nethera n ON up.user_id = n.id_nethera
                  WHERE up.user_id != ? AND up.is_active = 1 AND up.status = 'ALIVE'
                  ORDER BY RAND()
                  LIMIT 10";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $opponents = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['display_name'] = $row['nickname'] ?? $row['species_name'];
            $opponents[] = $row;
        }
        mysqli_stmt_close($stmt);

        echo json_encode([
            'success' => true,
            'opponents' => $opponents
        ]);
        break;

    // ============================================
    // POST: Initiate battle
    // ============================================
    case 'battle':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        // Rate limiting - 3 battles per day per user
        $battle_limit = $api_limiter->checkLimit($user_id, 'battle', 3, 1440);
        if (!$battle_limit['allowed']) {
            echo json_encode([
                'success' => false,
                'error' => 'Too many battles. Please wait before battling again.',
                'wait_until' => $battle_limit['locked_until']
            ]);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $defender_pet_id = isset($input['defender_pet_id']) ? (int) $input['defender_pet_id'] : 0;

        if (!$defender_pet_id) {
            echo json_encode(['success' => false, 'error' => 'Defender pet ID required']);
            break;
        }

        // Get user's active pet
        $active_check = mysqli_prepare(
            $conn,
            "SELECT id FROM user_pets WHERE user_id = ? AND is_active = 1 AND status = 'ALIVE'"
        );
        mysqli_stmt_bind_param($active_check, "i", $user_id);
        mysqli_stmt_execute($active_check);
        $active_result = mysqli_stmt_get_result($active_check);
        $active_pet = mysqli_fetch_assoc($active_result);
        mysqli_stmt_close($active_check);

        if (!$active_pet) {
            echo json_encode(['success' => false, 'error' => 'You need an active, alive pet to battle!']);
            break;
        }

        $attacker_pet_id = $active_pet['id'];

        // Verify defender exists and isn't yours
        $defender_check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM user_pets WHERE id = ? AND status = 'ALIVE'"
        );
        mysqli_stmt_bind_param($defender_check, "i", $defender_pet_id);
        mysqli_stmt_execute($defender_check);
        $defender_result = mysqli_stmt_get_result($defender_check);
        $defender = mysqli_fetch_assoc($defender_result);
        mysqli_stmt_close($defender_check);

        if (!$defender) {
            echo json_encode(['success' => false, 'error' => 'Opponent pet not found or is dead']);
            break;
        }

        if ($defender['user_id'] == $user_id) {
            echo json_encode(['success' => false, 'error' => 'Cannot battle your own pet!']);
            break;
        }

        // Perform battle
        $result = performBattle($conn, $attacker_pet_id, $defender_pet_id);

        // If user won, add gold reward
        if ($result['success'] && $result['winner_pet_id'] == $attacker_pet_id) {
            $gold_reward = $result['rewards']['gold'];
            $reward_stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
            mysqli_stmt_bind_param($reward_stmt, "ii", $gold_reward, $user_id);
            mysqli_stmt_execute($reward_stmt);
            mysqli_stmt_close($reward_stmt);
        }

        echo json_encode($result);
        break;

    // ============================================
    // GET: Get battle history
    // ============================================
    case 'battle_history':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        // Get battles where user's pets were involved
        $query = "SELECT pb.*,
                         up_atk.nickname as atk_nickname, ps_atk.name as atk_species,
                         up_def.nickname as def_nickname, ps_def.name as def_species,
                         n_atk.nama_lengkap as atk_owner, n_def.nama_lengkap as def_owner
                  FROM pet_battles pb
                  JOIN user_pets up_atk ON pb.attacker_pet_id = up_atk.id
                  JOIN user_pets up_def ON pb.defender_pet_id = up_def.id
                  JOIN pet_species ps_atk ON up_atk.species_id = ps_atk.id
                  JOIN pet_species ps_def ON up_def.species_id = ps_def.id
                  JOIN nethera n_atk ON up_atk.user_id = n_atk.id_nethera
                  JOIN nethera n_def ON up_def.user_id = n_def.id_nethera
                  WHERE up_atk.user_id = ? OR up_def.user_id = ?
                  ORDER BY pb.created_at DESC
                  LIMIT 20";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $battles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['atk_display'] = $row['atk_nickname'] ?? $row['atk_species'];
            $row['def_display'] = $row['def_nickname'] ?? $row['def_species'];
            $row['battle_log'] = json_decode($row['battle_log'], true);
            $battles[] = $row;
        }
        mysqli_stmt_close($stmt);

        echo json_encode([
            'success' => true,
            'battles' => $battles
        ]);
        break;

    // ============================================
    // GET: Get pet buff for activity
    // ============================================
    case 'get_buff':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $activity = isset($_GET['activity']) ? $_GET['activity'] : '';

        if (empty($activity)) {
            echo json_encode(['success' => false, 'error' => 'Activity type required']);
            break;
        }

        $buff = getActivePetBuff($conn, $user_id, $activity);
        $buff['success'] = true;
        echo json_encode($buff);
        break;

    // ============================================
    // POST: Finish rhythm game and collect rewards
    // ============================================
    case 'play_finish':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $score = isset($input['score']) ? (int) $input['score'] : 0;
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;

        if (!$pet_id) {
            echo json_encode(['success' => false, 'error' => 'Pet ID required']);
            break;
        }

        // Verify pet ownership
        $pet_check = mysqli_prepare($conn, "SELECT id, status FROM user_pets WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($pet_check, "ii", $pet_id, $user_id);
        mysqli_stmt_execute($pet_check);
        $pet_result = mysqli_stmt_get_result($pet_check);
        $pet = mysqli_fetch_assoc($pet_result);
        mysqli_stmt_close($pet_check);

        if (!$pet) {
            echo json_encode(['success' => false, 'error' => 'Pet not found']);
            break;
        }

        if ($pet['status'] === 'DEAD') {
            echo json_encode(['success' => false, 'error' => 'Cannot play with a dead pet']);
            break;
        }

        // Convert score to rewards
        $rewards = convertRhythmScore($score);

        // Update pet mood and EXP
        $new_mood = min(100, $rewards['mood']);
        $update_stmt = mysqli_prepare(
            $conn,
            "UPDATE user_pets SET mood = mood + ?, last_update_timestamp = ? WHERE id = ?"
        );
        $current_time = time();
        mysqli_stmt_bind_param($update_stmt, "iii", $new_mood, $current_time, $pet_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        // Add EXP
        $exp_result = addExpToPet($conn, $pet_id, $rewards['exp']);

        echo json_encode([
            'success' => true,
            'score' => $score,
            'rewards' => $rewards,
            'level_up' => $exp_result['level_ups'] > 0,
            'evolved' => $exp_result['evolved'] ?? false
        ]);
        break;

    // ============================================
    // GET: Get evolution candidates (fodder pets)
    // ============================================
    case 'get_evolution_candidates':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $main_pet_id = isset($_GET['main_pet_id']) ? (int) $_GET['main_pet_id'] : 0;

        if (!$main_pet_id) {
            echo json_encode(['success' => false, 'error' => 'Main pet ID required']);
            break;
        }

        // Get main pet rarity
        $main_stmt = mysqli_prepare(
            $conn,
            "SELECT ps.rarity FROM user_pets up
             JOIN pet_species ps ON up.species_id = ps.id
             WHERE up.id = ? AND up.user_id = ?"
        );
        mysqli_stmt_bind_param($main_stmt, "ii", $main_pet_id, $user_id);
        mysqli_stmt_execute($main_stmt);
        $main_result = mysqli_stmt_get_result($main_stmt);
        $main_pet = mysqli_fetch_assoc($main_result);
        mysqli_stmt_close($main_stmt);

        if (!$main_pet) {
            echo json_encode(['success' => false, 'error' => 'Main pet not found']);
            break;
        }

        // Get all user pets with matching rarity (excluding main pet and active pets)
        $rarity = $main_pet['rarity'];
        $candidates_stmt = mysqli_prepare(
            $conn,
            "SELECT up.*, ps.name as species_name, ps.rarity, ps.img_baby, ps.img_adult
             FROM user_pets up
             JOIN pet_species ps ON up.species_id = ps.id
             WHERE up.user_id = ? AND up.id != ? AND up.is_active = 0
               AND ps.rarity = ? AND up.status = 'ALIVE'
             ORDER BY up.level ASC"
        );
        mysqli_stmt_bind_param($candidates_stmt, "iis", $user_id, $main_pet_id, $rarity);
        mysqli_stmt_execute($candidates_stmt);
        $candidates_result = mysqli_stmt_get_result($candidates_stmt);

        $candidates = [];
        while ($row = mysqli_fetch_assoc($candidates_result)) {
            $candidates[] = $row;
        }
        mysqli_stmt_close($candidates_stmt);

        echo json_encode([
            'success' => true,
            'candidates' => $candidates,
            'required_count' => 3,
            'required_rarity' => $rarity
        ]);
        break;

    // ============================================
    // POST: Manual evolution (sacrifice system)
    // ============================================
    case 'evolve_manual':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $main_pet_id = isset($input['main_pet_id']) ? (int) $input['main_pet_id'] : 0;
        $fodder_ids = isset($input['fodder_ids']) ? $input['fodder_ids'] : [];

        if (!$main_pet_id || empty($fodder_ids)) {
            echo json_encode(['success' => false, 'error' => 'Main pet ID and fodder IDs required']);
            break;
        }

        // Convert to integers
        $fodder_ids = array_map('intval', $fodder_ids);

        // Call evolution function
        $result = evolvePetManual($conn, $user_id, $main_pet_id, $fodder_ids);

        // Update user gold display if successful
        if ($result['success']) {
            $gold_stmt = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
            mysqli_stmt_bind_param($gold_stmt, "i", $user_id);
            mysqli_stmt_execute($gold_stmt);
            $gold_result = mysqli_stmt_get_result($gold_stmt);
            $gold_row = mysqli_fetch_assoc($gold_result);
            $result['remaining_gold'] = $gold_row ? $gold_row['gold'] : 0;
            mysqli_stmt_close($gold_stmt);
        }

        echo json_encode($result);
        break;

    // ============================================
    // POST: Sell pet for gold
    // ============================================
    case 'sell_pet':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;

        if (!$pet_id) {
            echo json_encode(['success' => false, 'error' => 'Pet ID required']);
            break;
        }

        // Get pet data
        $pet_stmt = mysqli_prepare(
            $conn,
            "SELECT up.*, ps.rarity FROM user_pets up
             JOIN pet_species ps ON up.species_id = ps.id
             WHERE up.id = ? AND up.user_id = ?"
        );
        mysqli_stmt_bind_param($pet_stmt, "ii", $pet_id, $user_id);
        mysqli_stmt_execute($pet_stmt);
        $pet_result = mysqli_stmt_get_result($pet_stmt);
        $pet = mysqli_fetch_assoc($pet_result);
        mysqli_stmt_close($pet_stmt);

        if (!$pet) {
            echo json_encode(['success' => false, 'error' => 'Pet not found or not owned']);
            break;
        }

        // Cannot sell active pet
        if ($pet['is_active']) {
            echo json_encode(['success' => false, 'error' => 'Cannot sell your active pet! Set another pet as active first.']);
            break;
        }

        // Calculate sell price based on rarity and level
        $base_prices = [
            'Common' => 50,
            'Rare' => 100,
            'Epic' => 200,
            'Legendary' => 500
        ];
        $base_price = $base_prices[$pet['rarity']] ?? 50;
        $sell_price = $base_price + ($pet['level'] * 10);

        // Delete pet and add gold
        mysqli_begin_transaction($conn);

        try {
            // Delete pet
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM user_pets WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "ii", $pet_id, $user_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);

            // Add gold
            $gold_stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
            mysqli_stmt_bind_param($gold_stmt, "ii", $sell_price, $user_id);
            mysqli_stmt_execute($gold_stmt);
            mysqli_stmt_close($gold_stmt);

            mysqli_commit($conn);

            // Get new gold balance
            $balance_stmt = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
            mysqli_stmt_bind_param($balance_stmt, "i", $user_id);
            mysqli_stmt_execute($balance_stmt);
            $balance_result = mysqli_stmt_get_result($balance_stmt);
            $balance_row = mysqli_fetch_assoc($balance_result);
            $new_gold = $balance_row ? $balance_row['gold'] : 0;
            mysqli_stmt_close($balance_stmt);

            echo json_encode([
                'success' => true,
                'message' => "Pet sold for {$sell_price} gold!",
                'gold_earned' => $sell_price,
                'remaining_gold' => $new_gold
            ]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'error' => 'Failed to sell pet: ' . $e->getMessage()]);
        }
        break;

    // ============================================
    // POST: Submit turn-based battle result
    // ============================================
    case 'battle_result':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $attacker_pet_id = isset($input['attacker_pet_id']) ? (int) $input['attacker_pet_id'] : 0;
        $defender_pet_id = isset($input['defender_pet_id']) ? (int) $input['defender_pet_id'] : 0;
        $winner = isset($input['winner']) ? $input['winner'] : '';
        $gold_reward = isset($input['gold_reward']) ? (int) $input['gold_reward'] : 0;
        $exp_reward = isset($input['exp_reward']) ? (int) $input['exp_reward'] : 0;

        if (!$attacker_pet_id || !$defender_pet_id) {
            echo json_encode(['success' => false, 'error' => 'Missing pet IDs']);
            break;
        }

        // Verify attacker is user's pet
        $verify_stmt = mysqli_prepare($conn, "SELECT id FROM user_pets WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($verify_stmt, "ii", $attacker_pet_id, $user_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        if (!mysqli_fetch_assoc($verify_result)) {
            echo json_encode(['success' => false, 'error' => 'Invalid attacker pet']);
            mysqli_stmt_close($verify_stmt);
            break;
        }
        mysqli_stmt_close($verify_stmt);

        $playerWon = ($winner === 'attacker');

        // Apply rewards if player won
        if ($playerWon && $gold_reward > 0) {
            $gold_stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
            mysqli_stmt_bind_param($gold_stmt, "ii", $gold_reward, $user_id);
            mysqli_stmt_execute($gold_stmt);
            mysqli_stmt_close($gold_stmt);
        }

        if ($playerWon && $exp_reward > 0) {
            addExpToPet($conn, $attacker_pet_id, $exp_reward);
        }

        // Record battle in history
        $winner_pet_id = $playerWon ? $attacker_pet_id : $defender_pet_id;
        $battle_log = json_encode(['type' => 'arena', 'turns' => 'turn-based']);

        $record_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO pet_battles (attacker_pet_id, defender_pet_id, winner_pet_id, battle_log) VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($record_stmt, "iiis", $attacker_pet_id, $defender_pet_id, $winner_pet_id, $battle_log);
        mysqli_stmt_execute($record_stmt);
        mysqli_stmt_close($record_stmt);

        echo json_encode([
            'success' => true,
            'player_won' => $playerWon,
            'gold_earned' => $playerWon ? $gold_reward : 0,
            'exp_earned' => $playerWon ? $exp_reward : 0
        ]);
        break;

    // ============================================
    // GET: Check daily login reward
    // ============================================
    case 'get_daily_reward':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        // Define 30-day rewards (day => [gold, item_id or null])
        // Item IDs: 1=Food, 4=Potion, 7=Revive, 9=EXP Boost, 11=Gacha Ticket, 16=Divine Shield
        $rewards = [
            1 => ['gold' => 50, 'item_id' => null],
            2 => ['gold' => 100, 'item_id' => null],
            3 => ['gold' => 0, 'item_id' => 1], // Basic Kibble (Food)
            4 => ['gold' => 50, 'item_id' => null],
            5 => ['gold' => 0, 'item_id' => 4], // Health Elixir (Potion)
            6 => ['gold' => 100, 'item_id' => null],
            7 => ['gold' => 0, 'item_id' => 9], // Wisdom Scroll (EXP Boost - special)
            8 => ['gold' => 50, 'item_id' => null],
            9 => ['gold' => 100, 'item_id' => null],
            10 => ['gold' => 0, 'item_id' => 1], // Basic Kibble (Food)
            11 => ['gold' => 50, 'item_id' => null],
            12 => ['gold' => 0, 'item_id' => 4], // Health Elixir (Potion)
            13 => ['gold' => 100, 'item_id' => null],
            14 => ['gold' => 0, 'item_id' => 11], // Bronze Egg (Gacha - special)
            15 => ['gold' => 50, 'item_id' => null],
            16 => ['gold' => 50, 'item_id' => null],
            17 => ['gold' => 100, 'item_id' => null],
            18 => ['gold' => 0, 'item_id' => 1], // Basic Kibble (Food)
            19 => ['gold' => 50, 'item_id' => null],
            20 => ['gold' => 0, 'item_id' => 4], // Health Elixir (Potion)
            21 => ['gold' => 0, 'item_id' => 11], // Bronze Egg (Gacha - special)
            22 => ['gold' => 50, 'item_id' => null],
            23 => ['gold' => 100, 'item_id' => null],
            24 => ['gold' => 0, 'item_id' => 7], // Soul Fragment (Revive)
            25 => ['gold' => 50, 'item_id' => null],
            26 => ['gold' => 0, 'item_id' => 1], // Basic Kibble (Food)
            27 => ['gold' => 100, 'item_id' => null],
            28 => ['gold' => 0, 'item_id' => 16], // Divine Shield (special)
            29 => ['gold' => 50, 'item_id' => null],
            30 => ['gold' => 100, 'item_id' => 11], // Bronze Egg + Gold (jackpot)
        ];

        // Get user's streak data
        $streak_stmt = mysqli_prepare($conn, "SELECT * FROM daily_login_streak WHERE user_id = ?");
        mysqli_stmt_bind_param($streak_stmt, "i", $user_id);
        mysqli_stmt_execute($streak_stmt);
        $streak_result = mysqli_stmt_get_result($streak_stmt);
        $streak = mysqli_fetch_assoc($streak_result);
        mysqli_stmt_close($streak_stmt);

        $today = date('Y-m-d');
        $current_day = 1;
        $total_logins = 0;
        $can_claim = true;

        if ($streak) {
            $current_day = $streak['current_day'];
            $total_logins = $streak['total_logins'];

            if ($streak['last_claim_date'] === $today) {
                $can_claim = false;
            }
        }

        // Get item name if reward includes item
        $item_name = null;
        $reward = $rewards[$current_day];
        if ($reward['item_id']) {
            $item_stmt = mysqli_prepare($conn, "SELECT name FROM shop_items WHERE id = ?");
            mysqli_stmt_bind_param($item_stmt, "i", $reward['item_id']);
            mysqli_stmt_execute($item_stmt);
            $item_result = mysqli_stmt_get_result($item_stmt);
            if ($item_row = mysqli_fetch_assoc($item_result)) {
                $item_name = $item_row['name'];
            }
            mysqli_stmt_close($item_stmt);
        }

        echo json_encode([
            'success' => true,
            'can_claim' => $can_claim,
            'current_day' => $current_day,
            'total_logins' => $total_logins,
            'reward_gold' => $reward['gold'],
            'reward_item_id' => $reward['item_id'],
            'reward_item_name' => $item_name,
            'rewards_map' => $rewards
        ]);
        break;

    // ============================================
    // POST: Claim daily login reward
    // ============================================
    case 'claim_daily_reward':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        // Same rewards definition (IDs: 1=Food, 4=Potion, 7=Revive, 9=EXP Boost, 11=Gacha, 16=Shield)
        $rewards = [
            1 => ['gold' => 50, 'item_id' => null],
            2 => ['gold' => 100, 'item_id' => null],
            3 => ['gold' => 0, 'item_id' => 1],
            4 => ['gold' => 50, 'item_id' => null],
            5 => ['gold' => 0, 'item_id' => 4],
            6 => ['gold' => 100, 'item_id' => null],
            7 => ['gold' => 0, 'item_id' => 9],
            8 => ['gold' => 50, 'item_id' => null],
            9 => ['gold' => 100, 'item_id' => null],
            10 => ['gold' => 0, 'item_id' => 1],
            11 => ['gold' => 50, 'item_id' => null],
            12 => ['gold' => 0, 'item_id' => 4],
            13 => ['gold' => 100, 'item_id' => null],
            14 => ['gold' => 0, 'item_id' => 11],
            15 => ['gold' => 50, 'item_id' => null],
            16 => ['gold' => 50, 'item_id' => null],
            17 => ['gold' => 100, 'item_id' => null],
            18 => ['gold' => 0, 'item_id' => 1],
            19 => ['gold' => 50, 'item_id' => null],
            20 => ['gold' => 0, 'item_id' => 4],
            21 => ['gold' => 0, 'item_id' => 11],
            22 => ['gold' => 50, 'item_id' => null],
            23 => ['gold' => 100, 'item_id' => null],
            24 => ['gold' => 0, 'item_id' => 7],
            25 => ['gold' => 50, 'item_id' => null],
            26 => ['gold' => 0, 'item_id' => 1],
            27 => ['gold' => 100, 'item_id' => null],
            28 => ['gold' => 0, 'item_id' => 16],
            29 => ['gold' => 50, 'item_id' => null],
            30 => ['gold' => 100, 'item_id' => 11],
        ];

        $today = date('Y-m-d');

        // Get current streak
        $streak_stmt = mysqli_prepare($conn, "SELECT * FROM daily_login_streak WHERE user_id = ?");
        mysqli_stmt_bind_param($streak_stmt, "i", $user_id);
        mysqli_stmt_execute($streak_stmt);
        $streak_result = mysqli_stmt_get_result($streak_stmt);
        $streak = mysqli_fetch_assoc($streak_result);
        mysqli_stmt_close($streak_stmt);

        if ($streak && $streak['last_claim_date'] === $today) {
            echo json_encode(['success' => false, 'error' => 'Already claimed today!']);
            break;
        }

        $current_day = $streak ? $streak['current_day'] : 1;
        $total_logins = $streak ? $streak['total_logins'] : 0;
        $reward = $rewards[$current_day];

        // Grant gold
        if ($reward['gold'] > 0) {
            $gold_stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
            mysqli_stmt_bind_param($gold_stmt, "ii", $reward['gold'], $user_id);
            mysqli_stmt_execute($gold_stmt);
            mysqli_stmt_close($gold_stmt);
        }

        // Grant item
        $item_name = null;
        if ($reward['item_id']) {
            $inv_stmt = mysqli_prepare(
                $conn,
                "INSERT INTO user_inventory (user_id, item_id, quantity) VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE quantity = quantity + 1"
            );
            mysqli_stmt_bind_param($inv_stmt, "ii", $user_id, $reward['item_id']);
            mysqli_stmt_execute($inv_stmt);
            mysqli_stmt_close($inv_stmt);

            // Get item name
            $item_stmt = mysqli_prepare($conn, "SELECT name FROM shop_items WHERE id = ?");
            mysqli_stmt_bind_param($item_stmt, "i", $reward['item_id']);
            mysqli_stmt_execute($item_stmt);
            $item_result = mysqli_stmt_get_result($item_stmt);
            if ($item_row = mysqli_fetch_assoc($item_result)) {
                $item_name = $item_row['name'];
            }
            mysqli_stmt_close($item_stmt);
        }

        // Update streak
        $next_day = ($current_day >= 30) ? 1 : $current_day + 1;
        $new_total = $total_logins + 1;

        if ($streak) {
            $update_stmt = mysqli_prepare(
                $conn,
                "UPDATE daily_login_streak SET current_day = ?, last_claim_date = ?, total_logins = ? WHERE user_id = ?"
            );
            mysqli_stmt_bind_param($update_stmt, "isii", $next_day, $today, $new_total, $user_id);
        } else {
            $update_stmt = mysqli_prepare(
                $conn,
                "INSERT INTO daily_login_streak (user_id, current_day, last_claim_date, total_logins) VALUES (?, ?, ?, ?)"
            );
            $next_day = 2; // After first claim, move to day 2
            $new_total = 1;
            mysqli_stmt_bind_param($update_stmt, "iisi", $user_id, $next_day, $today, $new_total);
        }
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        echo json_encode([
            'success' => true,
            'claimed_day' => $current_day,
            'gold_received' => $reward['gold'],
            'item_received' => $item_name,
            'next_day' => $next_day,
            'total_logins' => $new_total
        ]);
        break;

    // ============================================
    // GET: Leaderboard rankings
    // ============================================
    case 'get_leaderboard':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $category = isset($_GET['category']) ? $_GET['category'] : 'top_level';
        $limit = 10;

        try {
            switch ($category) {
                case 'battle_wins':
                    // Top battle winners - simplified query
                    $query = "SELECT 
                        up.id as pet_id,
                        up.nickname,
                        up.level,
                        ps.name as species_name,
                        ps.rarity,
                        ps.element,
                        n.nama_lengkap as owner_name,
                        COALESCE(s.nama_sanctuary, 'Unknown') as sanctuary,
                        (SELECT COUNT(*) FROM pet_battles WHERE winner_pet_id = up.id) as wins
                    FROM user_pets up
                    JOIN pet_species ps ON up.species_id = ps.id
                    JOIN nethera n ON up.user_id = n.id_nethera
                    LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
                    WHERE up.status != 'DEAD'
                    ORDER BY wins DESC, up.level DESC
                    LIMIT $limit";
                    break;

                case 'streak':
                    // Top daily login streak
                    $query = "SELECT 
                        up.id as pet_id,
                        up.nickname,
                        up.level,
                        ps.name as species_name,
                        ps.rarity,
                        ps.element,
                        n.nama_lengkap as owner_name,
                        COALESCE(s.nama_sanctuary, 'Unknown') as sanctuary,
                        COALESCE(dls.total_logins, 0) as streak
                    FROM user_pets up
                    JOIN pet_species ps ON up.species_id = ps.id
                    JOIN nethera n ON up.user_id = n.id_nethera
                    LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
                    LEFT JOIN daily_login_streak dls ON up.user_id = dls.user_id
                    WHERE up.is_active = 1 AND up.status != 'DEAD'
                    ORDER BY streak DESC, up.level DESC
                    LIMIT $limit";
                    break;

                default: // top_level
                    $query = "SELECT 
                        up.id as pet_id,
                        up.nickname,
                        up.level,
                        ps.name as species_name,
                        ps.rarity,
                        ps.element,
                        n.nama_lengkap as owner_name,
                        COALESCE(s.nama_sanctuary, 'Unknown') as sanctuary
                    FROM user_pets up
                    JOIN pet_species ps ON up.species_id = ps.id
                    JOIN nethera n ON up.user_id = n.id_nethera
                    LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
                    WHERE up.status != 'DEAD'
                    ORDER BY up.level DESC, up.exp DESC
                    LIMIT $limit";
                    break;
            }

            $result = mysqli_query($conn, $query);

            if (!$result) {
                throw new Exception(mysqli_error($conn));
            }

            $leaderboard = [];
            $rank = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $row['rank'] = $rank++;
                $row['display_name'] = !empty($row['nickname']) ? $row['nickname'] : $row['species_name'];
                $leaderboard[] = $row;
            }

            echo json_encode([
                'success' => true,
                'category' => $category,
                'leaderboard' => $leaderboard
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    // ============================================
    // GET: Achievements list with user progress
    // ============================================
    case 'get_achievements':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        try {
            // Get all achievements
            $achievements_query = "SELECT * FROM achievements ORDER BY category, rarity DESC, id";
            $achievements_result = mysqli_query($conn, $achievements_query);

            if (!$achievements_result) {
                throw new Exception(mysqli_error($conn));
            }

            // Get user's unlocked achievements
            $unlocked_query = "SELECT achievement_id FROM user_achievements WHERE user_id = ?";
            $unlocked_stmt = mysqli_prepare($conn, $unlocked_query);
            mysqli_stmt_bind_param($unlocked_stmt, "i", $user_id);
            mysqli_stmt_execute($unlocked_stmt);
            $unlocked_result = mysqli_stmt_get_result($unlocked_stmt);

            $unlocked_ids = [];
            while ($row = mysqli_fetch_assoc($unlocked_result)) {
                $unlocked_ids[] = $row['achievement_id'];
            }
            mysqli_stmt_close($unlocked_stmt);

            // Get user stats for progress calculation
            $stats = [];

            // Pets owned
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM user_pets WHERE user_id = $user_id AND status != 'DEAD'");
            $stats['pets_owned'] = mysqli_fetch_assoc($q)['cnt'];

            // Shiny pets
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM user_pets WHERE user_id = $user_id AND is_shiny = 1");
            $stats['shiny_pets'] = mysqli_fetch_assoc($q)['cnt'];

            // Legendary pets
            $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM user_pets up JOIN pet_species ps ON up.species_id = ps.id WHERE up.user_id = $user_id AND ps.rarity = 'legendary'");
            $stats['legendary_pets'] = mysqli_fetch_assoc($q)['cnt'];

            // Battle wins
            $q = mysqli_query($conn, "SELECT wins FROM user_pets WHERE user_id = $user_id ORDER BY wins DESC LIMIT 1");
            $row = mysqli_fetch_assoc($q);
            $stats['battle_wins'] = $row ? $row['wins'] : 0;

            // Max pet level
            $q = mysqli_query($conn, "SELECT MAX(level) as max_lvl FROM user_pets WHERE user_id = $user_id");
            $row = mysqli_fetch_assoc($q);
            $stats['max_pet_level'] = $row ? $row['max_lvl'] : 0;

            // Login streak
            $q = mysqli_query($conn, "SELECT total_logins FROM daily_login_streak WHERE user_id = $user_id");
            $row = mysqli_fetch_assoc($q);
            $stats['login_streak'] = $row ? $row['total_logins'] : 0;

            // AUTO-UNLOCK: Check and unlock achievements based on progress
            $newly_unlocked = [];
            mysqli_data_seek($achievements_result, 0); // Reset result pointer
            while ($ach = mysqli_fetch_assoc($achievements_result)) {
                // Skip if already unlocked
                if (in_array($ach['id'], $unlocked_ids)) {
                    continue;
                }

                // Check if requirement is met
                $current = isset($stats[$ach['requirement_type']]) ? (int) $stats[$ach['requirement_type']] : 0;
                $required = (int) $ach['requirement_value'];

                if ($current >= $required) {
                    // Unlock this achievement!
                    $unlock_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
                    mysqli_stmt_bind_param($unlock_stmt, "ii", $user_id, $ach['id']);
                    mysqli_stmt_execute($unlock_stmt);
                    mysqli_stmt_close($unlock_stmt);

                    $unlocked_ids[] = $ach['id'];
                    $newly_unlocked[] = $ach;

                    // Give gold reward if any
                    if ($ach['reward_gold'] > 0) {
                        mysqli_query($conn, "UPDATE nethera SET gold = gold + {$ach['reward_gold']} WHERE id_nethera = $user_id");
                    }
                }
            }

            // Build achievements list with progress
            mysqli_data_seek($achievements_result, 0); // Reset again
            $achievements = [];
            while ($ach = mysqli_fetch_assoc($achievements_result)) {
                $ach['unlocked'] = in_array($ach['id'], $unlocked_ids);
                $ach['current_progress'] = isset($stats[$ach['requirement_type']]) ? $stats[$ach['requirement_type']] : 0;
                $achievements[] = $ach;
            }

            // Count stats
            $total = count($achievements);
            $unlocked_count = count($unlocked_ids);

            echo json_encode([
                'success' => true,
                'total' => $total,
                'unlocked' => $unlocked_count,
                'newly_unlocked' => $newly_unlocked,
                'achievements' => $achievements
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    // ============================================
    // Default: Unknown action
    // ============================================
    default:
        api_not_found('Unknown action. Available: get_pets, get_active_pet, get_shop, get_inventory, gacha, buy_item, use_item, set_active, rename, shelter, get_opponents, battle, battle_history, get_buff, play_finish, get_evolution_candidates, evolve_manual, sell_pet, battle_result, get_daily_reward, claim_daily_reward, get_leaderboard, get_achievements');
}
?>