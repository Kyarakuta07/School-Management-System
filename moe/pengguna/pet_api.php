<?php
/**
 * MOE Pet System - API Endpoints
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * AJAX endpoints for frontend communication
 * All responses are JSON formatted
 */

session_start();
header('Content-Type: application/json');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include '../connection.php';
include 'pet_logic.php';

// ================================================
// AUTHENTICATION CHECK
// ================================================

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Nethera') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit();
}

$user_id = $_SESSION['id_nethera'];

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
            methodNotAllowed('GET');
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
            methodNotAllowed('GET');
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
            methodNotAllowed('GET');
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
            methodNotAllowed('GET');
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
            methodNotAllowed('POST');
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
            echo json_encode([
                'success' => false,
                'error' => "Not enough gold! Need $cost, have $user_gold."
            ]);
            break;
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
            methodNotAllowed('POST');
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
            methodNotAllowed('POST');
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
            methodNotAllowed('POST');
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
            methodNotAllowed('POST');
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
            methodNotAllowed('POST');
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
            methodNotAllowed('GET');
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
            methodNotAllowed('POST');
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
            methodNotAllowed('GET');
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
            methodNotAllowed('GET');
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
    // Default: Unknown action
    // ============================================
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action. Available actions: get_pets, get_active_pet, get_shop, get_inventory, gacha, buy_item, use_item, set_active, rename, shelter, get_opponents, battle, battle_history, get_buff'
        ]);
        break;
}

// ================================================
// HELPER FUNCTIONS
// ================================================

function methodNotAllowed($expected)
{
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => "Method not allowed. Use $expected."
    ]);
    exit();
}
?>