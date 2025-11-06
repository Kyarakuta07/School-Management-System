<?php
session_start();
include '../../connection.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_name = $_POST['class_name'];
    $hakaes_name = $_POST['hakaes_name'];
    $schedule_day = $_POST['schedule_day'];
    $schedule_time = $_POST['schedule_time'];
    $class_image_url = $_POST['class_image_url'];
    $class_description = $_POST['class_description'];

    $sql = "INSERT INTO class_schedule (class_name, hakaes_name, schedule_day, schedule_time, class_image_url, class_description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssss", $class_name, $hakaes_name, $schedule_day, $schedule_time, $class_image_url, $class_description);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_classes.php?status=add_sukses");
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        die("Query error: " . mysqli_error($conn));
    }

} else {
    header("Location: manage_classes.php");
    exit();
}
?>
