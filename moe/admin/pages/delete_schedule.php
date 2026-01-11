<?php
require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';
require_once '../../core/activity_logger.php';
include '../../config/connection.php';

// Proteksi Halaman
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// Support both GET (legacy) and POST (secure)
$id_schedule_to_delete = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation for POST
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: manage_classes.php?status=csrf_failed");
        exit();
    }
    $id_schedule_to_delete = isset($_POST['id']) ? (int) $_POST['id'] : 0;
} else {
    // SECURITY: Reject GET requests - require POST with CSRF
    header("Location: manage_classes.php?status=invalid_method");
    exit();
}

if ($id_schedule_to_delete == 0) {
    header("Location: manage_classes.php");
    exit();
}

// --- 1. AMBIL DATA SEBELUM MENGHAPUS UNTUK LOGGING ---
$sql_fetch = "SELECT class_name, schedule_day, schedule_time, class_image_url FROM class_schedule WHERE id_schedule = ?";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);

if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $id_schedule_to_delete);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    $schedule_data = mysqli_fetch_assoc($result);
    $image_path_to_delete = $schedule_data['class_image_url'] ?? null;

    mysqli_stmt_close($stmt_fetch);

    // --- 2. HAPUS FILE GAMBAR DARI SERVER (Jika ada) ---
    if (!empty($image_path_to_delete)) {
        $file_to_delete = '../../' . $image_path_to_delete;
        if (file_exists($file_to_delete) && is_file($file_to_delete)) {
            unlink($file_to_delete);
        }
    }

    // --- 3. HAPUS RECORD DARI DATABASE ---
    $sql_delete = "DELETE FROM class_schedule WHERE id_schedule = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete);

    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id_schedule_to_delete);

        if (mysqli_stmt_execute($stmt_delete)) {
            // Log the delete action
            log_delete(
                $conn,
                'schedule',
                $id_schedule_to_delete,
                'Deleted schedule: ' . ($schedule_data['class_name'] ?? 'Unknown') . ' (' . ($schedule_data['schedule_day'] ?? '') . ')',
                $schedule_data
            );

            header("Location: manage_classes.php?status=delete_sukses");
            exit();
        } else {
            error_log("Error deleting schedule: " . mysqli_error($conn));
            header("Location: manage_classes.php?status=delete_error");
            exit();
        }
    } else {
        error_log("Error preparing delete schedule statement: " . mysqli_error($conn));
        header("Location: manage_classes.php?status=db_error");
        exit();
    }

} else {
    error_log("Error fetching schedule data: " . mysqli_error($conn));
    header("Location: manage_classes.php?status=db_error");
    exit();
}
?>