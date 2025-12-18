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
$id_grade_to_delete = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: manage_classes.php?status=csrf_failed");
        exit();
    }
    $id_grade_to_delete = isset($_POST['id']) ? (int) $_POST['id'] : 0;
} else {
    $id_grade_to_delete = isset($_GET['id']) ? (int) $_GET['id'] : 0;
}

if ($id_grade_to_delete == 0) {
    header("Location: manage_classes.php");
    exit();
}

// Fetch grade info before delete for logging
$fetch_stmt = mysqli_prepare($conn, "SELECT cg.*, n.username FROM class_grades cg LEFT JOIN nethera n ON cg.id_nethera = n.id_nethera WHERE cg.id_grade = ?");
mysqli_stmt_bind_param($fetch_stmt, "i", $id_grade_to_delete);
mysqli_stmt_execute($fetch_stmt);
$grade_data = mysqli_fetch_assoc(mysqli_stmt_get_result($fetch_stmt));
mysqli_stmt_close($fetch_stmt);

// SQL DELETE dengan Prepared Statements
$sql_delete = "DELETE FROM class_grades WHERE id_grade = ?";
$stmt_delete = mysqli_prepare($conn, $sql_delete);

if ($stmt_delete) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_grade_to_delete);

    if (mysqli_stmt_execute($stmt_delete)) {
        // Log the delete action
        log_delete(
            $conn,
            'grade',
            $id_grade_to_delete,
            'Deleted grade for user: ' . ($grade_data['username'] ?? 'Unknown'),
            $grade_data
        );

        header("Location: manage_classes.php?status=delete_grade_success");
        exit();
    } else {
        die("Error deleting record: " . mysqli_error($conn));
    }
} else {
    die("Error preparing delete statement: " . mysqli_error($conn));
}
?>