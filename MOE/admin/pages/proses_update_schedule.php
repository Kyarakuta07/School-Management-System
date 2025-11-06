<?php
session_start();
include '../../connection.php';

// Proteksi Halaman
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil semua data dari form
    $id_schedule = $_POST['id_schedule'];
    $class_name = $_POST['class_name'];
    $hakaes_name = $_POST['hakaes_name'];
    $schedule_day = $_POST['schedule_day'];
    $schedule_time = $_POST['schedule_time'];
    $class_image_url = $_POST['class_image_url'];
    $class_description = $_POST['class_description'];

    // Siapkan query SQL UPDATE yang aman
    $sql = "UPDATE class_schedule SET 
                class_name = ?, 
                hakaes_name = ?, 
                schedule_day = ?, 
                schedule_time = ?, 
                class_image_url = ?, 
                class_description = ? 
            WHERE 
                id_schedule = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssi", $class_name, $hakaes_name, $schedule_day, $schedule_time, $class_image_url, $class_description, $id_schedule);
        
        if (mysqli_stmt_execute($stmt)) {
            // Jika berhasil, redirect ke halaman manage_classes
            header("Location: manage_classes.php?status=update_sukses");
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
