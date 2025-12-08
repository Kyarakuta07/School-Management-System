<?php
/**
 * Delete Nethera - Secure Version
 * Uses POST method with CSRF protection
 */

require_once '../../includes/security_config.php';
session_start();
require_once '../../includes/csrf.php';
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

// Delete record from database
$sql_delete = "DELETE FROM nethera WHERE id_nethera = ?";
$stmt_delete = mysqli_prepare($conn, $sql_delete);

if ($stmt_delete) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_nethera_to_delete);

    if (mysqli_stmt_execute($stmt_delete)) {
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