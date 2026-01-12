<?php
/**
 * update_profile.php - Handles profile photo upload and fun fact update
 * Security: Session auth, CSRF protection, image validation
 */

require_once '../core/security_config.php';
session_start();
require_once '../core/csrf.php';
include '../config/connection.php';

// Authentication check - Allow both Nethera and Vasiki (admin)
if (!isset($_SESSION['status_login']) || ($_SESSION['role'] != 'Nethera' && $_SESSION['role'] != 'Vasiki')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id_user = $_SESSION['id_nethera'];
$response = ['success' => false, 'message' => 'Unknown error'];

// Validate CSRF for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $response['message'] = 'Invalid security token. Please refresh and try again.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// Handle Fun Fact Update
if (isset($_POST['action']) && $_POST['action'] === 'update_funfact') {
    $fun_fact = trim($_POST['fun_fact'] ?? '');

    // Validate length (max 500 chars)
    if (strlen($fun_fact) > 500) {
        $response['message'] = 'Fun fact terlalu panjang (max 500 karakter)';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE nethera SET fun_fact = ? WHERE id_nethera = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $fun_fact, $id_user);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Fun fact berhasil diupdate!';
                $response['fun_fact'] = htmlspecialchars($fun_fact);
            } else {
                $response['message'] = 'Gagal menyimpan ke database';
            }
            mysqli_stmt_close($stmt);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle Profile Photo Upload
if (isset($_POST['action']) && $_POST['action'] === 'upload_photo') {

    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Tidak ada file yang diupload';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    $file = $_FILES['profile_photo'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $targetSize = 150; // Target size in pixels

    // Validate file size
    if ($file['size'] > $maxSize) {
        $response['message'] = 'Ukuran file terlalu besar (max 2MB)';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $response['message'] = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Create upload directory if not exists
    $uploadDir = '../assets/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = 'profile_' . $id_user . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $newFilename;

    // Load and resize image
    $resized = false;

    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            $sourceImage = false;
    }

    if ($sourceImage) {
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Calculate crop dimensions for square
        $size = min($origWidth, $origHeight);
        $srcX = ($origWidth - $size) / 2;
        $srcY = ($origHeight - $size) / 2;

        // Create resized image
        $newImage = imagecreatetruecolor($targetSize, $targetSize);

        // Preserve transparency for PNG/GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparent);
        }

        // Resize and crop to square
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0,
            0,
            $srcX,
            $srcY,
            $targetSize,
            $targetSize,
            $size,
            $size
        );

        // Save resized image
        switch ($mimeType) {
            case 'image/jpeg':
                $resized = imagejpeg($newImage, $targetPath, 90);
                break;
            case 'image/png':
                $resized = imagepng($newImage, $targetPath, 9);
                break;
            case 'image/gif':
                $resized = imagegif($newImage, $targetPath);
                break;
            case 'image/webp':
                $resized = imagewebp($newImage, $targetPath, 90);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($newImage);
    }

    if (!$resized) {
        // Fallback: just move the file without resizing
        $resized = move_uploaded_file($file['tmp_name'], $targetPath);
    }

    if ($resized) {
        // Delete old profile photo if exists
        $stmt = mysqli_prepare($conn, "SELECT profile_photo FROM nethera WHERE id_nethera = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_user);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $oldPhoto = mysqli_fetch_assoc($result)['profile_photo'];
        mysqli_stmt_close($stmt);

        if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
            unlink($uploadDir . $oldPhoto);
        }

        // Update database with new filename
        $stmt = mysqli_prepare($conn, "UPDATE nethera SET profile_photo = ? WHERE id_nethera = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $newFilename, $id_user);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Foto profil berhasil diupdate!';
                $response['photo_url'] = 'assets/uploads/profiles/' . $newFilename;
            } else {
                $response['message'] = 'Gagal menyimpan ke database';
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $response['message'] = 'Gagal mengupload file';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Unknown action
$response['message'] = 'Action tidak valid';
header('Content-Type: application/json');
echo json_encode($response);
