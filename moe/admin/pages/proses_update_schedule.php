<?php
require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';
include '../../config/connection.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        error_log("CSRF token validation failed for update schedule");
        header("Location: manage_classes.php?status=csrf_failed");
        exit();
    }

    $id_schedule = (int) $_POST['id_schedule'];
    $class_name = trim($_POST['class_name']);
    $hakaes_name = trim($_POST['hakaes_name']);
    $schedule_day = trim($_POST['schedule_day']);
    $schedule_time = trim($_POST['schedule_time']);
    $class_description = trim($_POST['class_description']);
    $old_image_path_db = $_POST['old_image_path'] ?? '';

    $new_image_path = NULL;
    $upload_dir_root = '../../class_images/';

    // Handle file upload
    if (isset($_FILES['class_image']) && $_FILES['class_image']['error'] === UPLOAD_ERR_OK) {

        $file_tmp = $_FILES['class_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['class_image']['name'], PATHINFO_EXTENSION));
        $new_file_name = uniqid('class_', true) . '.' . $file_ext;
        $target_file = $upload_dir_root . $new_file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_types)) {
            if (move_uploaded_file($file_tmp, $target_file)) {
                $new_image_path = 'class_images/' . $new_file_name;

                // Delete old file
                if (!empty($old_image_path_db)) {
                    $file_to_delete = '../../' . $old_image_path_db;
                    if (file_exists($file_to_delete) && is_file($file_to_delete)) {
                        unlink($file_to_delete);
                    }
                }
            } else {
                header("Location: edit_schedule.php?id=$id_schedule&error=upload_fail");
                exit();
            }
        } else {
            header("Location: edit_schedule.php?id=$id_schedule&error=file_type");
            exit();
        }
    } else {
        $new_image_path = $old_image_path_db;
    }

    $sql = "UPDATE class_schedule SET 
                class_name = ?, 
                hakaes_name = ?, 
                schedule_day = ?, 
                schedule_time = ?, 
                class_description = ?, 
                class_image_url = ? 
            WHERE id_schedule = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssi",
            $class_name,
            $hakaes_name,
            $schedule_day,
            $schedule_time,
            $class_description,
            $new_image_path,
            $id_schedule
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_classes.php?status=update_success");
            exit();
        } else {
            error_log("Error updating schedule: " . mysqli_stmt_error($stmt));
            header("Location: manage_classes.php?status=update_error");
            exit();
        }
    } else {
        error_log("Query prepare error for update schedule: " . mysqli_error($conn));
        header("Location: manage_classes.php?status=db_error");
        exit();
    }

} else {
    header("Location: manage_classes.php");
    exit();
}