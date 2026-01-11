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
        error_log("CSRF token validation failed for add schedule");
        header("Location: manage_classes.php?status=csrf_failed");
        exit();
    }

    // 1. Ambil dan bersihkan data input
    $class_name = trim($_POST['class_name']);
    $hakaes_name = trim($_POST['hakaes_name']);
    $schedule_day = trim($_POST['schedule_day']);
    $schedule_time = trim($_POST['schedule_time']);
    $class_description = trim($_POST['class_description']);

    $image_path = NULL;

    // 2. HANDLE FILE UPLOAD
    if (isset($_FILES['class_image']) && $_FILES['class_image']['error'] === UPLOAD_ERR_OK) {

        $file_tmp = $_FILES['class_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['class_image']['name'], PATHINFO_EXTENSION));

        $upload_dir = '../../class_images/';
        $new_file_name = uniqid('class_', true) . '.' . $file_ext;
        $target_file = $upload_dir . $new_file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_types) && move_uploaded_file($file_tmp, $target_file)) {
            $image_path = 'class_images/' . $new_file_name;
        } else {
            header("Location: add_schedule.php?error=upload_fail");
            exit();
        }
    }

    // 3. SQL INSERT
    $sql = "INSERT INTO class_schedule (class_name, hakaes_name, schedule_day, schedule_time, class_image_url, class_description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "ssssss",
            $class_name,
            $hakaes_name,
            $schedule_day,
            $schedule_time,
            $image_path,
            $class_description
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_classes.php?status=add_success");
            exit();
        } else {
            error_log("Error adding schedule: " . mysqli_stmt_error($stmt));
            header("Location: add_schedule.php?error=db_error");
            exit();
        }
    } else {
        error_log("Query prepare error for add schedule: " . mysqli_error($conn));
        header("Location: add_schedule.php?error=db_error");
        exit();
    }

} else {
    header("Location: manage_classes.php");
    exit();
}