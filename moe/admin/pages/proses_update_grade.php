<?php
require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';
include '../../config/connection.php';

if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['Vasiki', 'Hakaes'])) {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        error_log("CSRF token validation failed for update grade");
        header("Location: manage_classes.php?status=csrf_failed");
        exit();
    }

    $id_grade = (int) $_POST['id_grade'];
    $class_name = trim($_POST['class_name']);

    $history = (int) $_POST['history'];
    $pop_culture = (int) $_POST['pop_culture'];
    $mythology = (int) $_POST['mythology'];
    $history_of_egypt = (int) $_POST['history_of_egypt'];
    $oceanology = (int) $_POST['oceanology'];
    $astronomy = (int) $_POST['astronomy'];

    $new_total_pp = $history + $pop_culture + $mythology + $history_of_egypt + $oceanology + $astronomy;

    $sql = "UPDATE class_grades SET 
                class_name = ?, 
                history = ?, 
                pop_culture = ?,
                mythology = ?,
                history_of_egypt = ?,
                oceanology = ?, 
                astronomy = ?, 
                total_pp = ? 
            WHERE 
                id_grade = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "siiiiiiii",
            $class_name,
            $history,
            $pop_culture,
            $mythology,
            $history_of_egypt,
            $oceanology,
            $astronomy,
            $new_total_pp,
            $id_grade
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_classes.php?status=update_sukses");
            exit();
        } else {
            error_log("Error updating grade: " . mysqli_stmt_error($stmt));
            header("Location: manage_classes.php?status=update_error");
            exit();
        }
    } else {
        error_log("Query prepare error for update grade: " . mysqli_error($conn));
        header("Location: manage_classes.php?status=db_error");
        exit();
    }

} else {
    header("Location: manage_classes.php");
    exit();
}