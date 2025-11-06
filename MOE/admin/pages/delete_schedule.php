<?php
session_start();
include '../../connection.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_classes.php");
    exit();
}
$id_schedule_to_delete = $_GET['id'];

$sql = "DELETE FROM class_schedule WHERE id_schedule = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_schedule_to_delete);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: manage_classes.php?status=delete_sukses");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    die("Query error: " . mysqli_error($conn));
}
?>
