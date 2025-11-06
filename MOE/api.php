<?php
/**
 * Tema: Web Service Sederhana (Key-Value Store)
 * File: api.php
 * Dijalankan di dalam project MOE (XAMPP)
 *
 * Endpoint:
 * POST http://localhost/MOE/api.php?action=set
 * GET  http://localhost/MOE/api.php?action=get&key=<nama_key>
 */

// --- Pengaturan Dasar ---
header("Content-Type: application/json");

// Tentukan nama file yang akan kita gunakan sebagai "database" sederhana
$storageFile = 'database.json';

// --- Inisialisasi Penyimpanan ---
// Jika file database.json belum ada, buat file kosong dengan format JSON {}
if (!file_exists($storageFile)) {
    file_put_contents($storageFile, json_encode([]));
}

// --- Fungsi Bantuan (Helper Functions) ---

/**
 * Membaca seluruh data dari file database.json
 * @return array Data yang tersimpan
 */
function getDataStore() {
    global $storageFile;
    $data = file_get_contents($storageFile);
    return json_decode($data, true); // true untuk mengubahnya jadi array asosiatif
}

/**
 * Menulis data baru ke file database.json
 * @param array $data Data yang akan disimpan
 */
function writeDataStore($data) {
    global $storageFile;
    // JSON_PRETTY_PRINT agar file-nya mudah dibaca manusia
    file_put_contents($storageFile, json_encode($data, JSON_PRETTY_PRINT));
}

// --- Logika Routing Utama ---

$method = $_SERVER['REQUEST_METHOD'];
// Kita ambil 'action' dari query string URL (contoh: ?action=set)
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {

    // --- KASUS 1: ?action=set ---
    case 'set':
        if ($method === 'POST') {
            // 1. Ambil data JSON mentah dari body request
            $input = json_decode(file_get_contents('php://input'), true);

            // 2. Validasi input
            if (isset($input['key']) && isset($input['value'])) {
                $key = $input['key'];
                $value = $input['value'];
                
                // 3. Baca, modifikasi, dan tulis data
                $dataStore = getDataStore();
                $dataStore[$key] = $value;
                writeDataStore($dataStore);
                
                // 4. Beri respons sukses
                echo json_encode([
                    'status' => 'success',
                    'message' => "Data dengan key '$key' berhasil disimpan."
                ]);
            } else {
                http_response_code(400); // 400 Bad Request
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Body JSON tidak valid. Harus mengandung "key" dan "value".'
                ]);
            }
        } else {
            // Jika user mencoba akses ?action=set pakai method GET
            http_response_code(405); // 405 Method Not Allowed
            echo json_encode(['status' => 'error', 'message' => 'Gunakan method POST untuk action=set.']);
        }
        break;

    // --- KASUS 2: ?action=get ---
    case 'get':
        if ($method === 'GET') {
            // 1. Ambil 'key' dari query string URL (contoh: ?action=get&key=nama)
            $key = isset($_GET['key']) ? $_GET['key'] : '';

            if ($key) {
                // 2. Baca data store
                $dataStore = getDataStore();
                
                // 3. Cek apakah key ada
                if (array_key_exists($key, $dataStore)) {
                    $value = $dataStore[$key];
                    
                    // 4. Kembalikan data sesuai format
                    echo json_encode([
                        'key' => $key,
                        'value' => $value
                    ]);
                } else {
                    // Jika key tidak ditemukan
                    http_response_code(404); // 404 Not Found
                    echo json_encode([
                        'status' => 'error',
                        'message' => "Key '$key' tidak ditemukan."
                    ]);
                }
            } else {
                // Jika user lupa ?key=...
                http_response_code(400); // 400 Bad Request
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Parameter "key" dibutuhkan. (Contoh: ?action=get&key=nama)'
                ]);
            }
        } else {
            // Jika user mencoba akses ?action=get pakai method POST
            http_response_code(405); // 405 Method Not Allowed
            echo json_encode(['status' => 'error', 'message' => 'Gunakan method GET untuk action=get.']);
        }
        break;

    // --- KASUS 3: Action tidak dikenal ---
    default:
        http_response_code(404); // 404 Not Found
        echo json_encode([
            'status' => 'error',
            'message' => 'Action tidak ditemukan. Gunakan ?action=set atau ?action=get.'
        ]);
        break;
}