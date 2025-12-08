<?php
/**
 * Delete Nethera - Secure Version
 * Uses POST method with CSRF protection
 */

require_once '../../includes/security_config.php';
session_start();
require_once '../../includes/csrf.php';
require_once '../../includes/activity_logger.php';
include '../../connection.php';

// Role check
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// Must be POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_nethera.php?status=invalid_method");
    exit();
}

// CSRF validation
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    error_log("CSRF token validation failed for delete nethera attempt");
    header("Location: manage_nethera.php?status=csrf_failed");
    exit();
}

$id_nethera_to_delete = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id_nethera_to_delete == 0) {
    header("Location: manage_nethera.php?status=invalid_id");
    exit();
}

// Prevent deleting self
if ($id_nethera_to_delete == $_SESSION['id_nethera']) {
    header("Location: manage_nethera.php?status=cannot_delete_self");
    exit();
}

// Fetch user info before delete for logging
$info_stmt = mysqli_prepare($conn, "SELECT username, nama_lengkap, email FROM nethera WHERE id_nethera = ?");
mysqli_stmt_bind_param($info_stmt, "i", $id_nethera_to_delete);
mysqli_stmt_execute($info_stmt);
$info_result = mysqli_stmt_get_result($info_stmt);
$user_info = mysqli_fetch_assoc($info_result);
mysqli_stmt_close($info_stmt);

// Delete record from database
$sql_delete = "DELETE FROM nethera WHERE id_nethera = ?";
$stmt_delete = mysqli_prepare($conn, $sql_delete);

if ($stmt_delete) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_nethera_to_delete);

    if (mysqli_stmt_execute($stmt_delete)) {
        // Log the delete action
        log_delete(
            $conn,
            'nethera',
            $id_nethera_to_delete,
            'Deleted user: ' . ($user_info['username'] ?? 'Unknown') . ' (' . ($user_info['nama_lengkap'] ?? '') . ')',
            $user_info
        );

        // Regenerate CSRF token after successful action
        regenerate_csrf_token();

        // Success: Redirect back to manage nethera
        header("Location: manage_nethera.php?status=delete_success");
        exit();
    } else {
        error_log("Error deleting nethera ID $id_nethera_to_delete: " . mysqli_error($conn));
        mysqli_stmt_close($stmt_delete);
        header("Location: manage_nethera.php?status=delete_failed");
        exit();
    }
} else {
    error_log("Error preparing delete statement: " . mysqli_error($conn));
    header("Location: manage_nethera.php?status=db_error");
    exit();
}
?>