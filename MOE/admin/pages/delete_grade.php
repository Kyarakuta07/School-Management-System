<?php
session_start();
include '../../connection.php'; 

// Proteksi Halaman
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// 1. Ambil ID dari URL dan sanitasi
$id_grade_to_delete = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_grade_to_delete == 0) {
    header("Location: manage_classes.php");
    exit();
}

// 2. SQL DELETE dengan Prepared Statements
$sql_delete = "DELETE FROM class_grades WHERE id_grade = ?";
$stmt_delete = mysqli_prepare($conn, $sql_delete);

if ($stmt_delete) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_grade_to_delete);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        // Berhasil: Redirect kembali ke halaman manage classes
        header("Location: manage_classes.php?status=delete_grade_success");
        exit();
    } else {
        // Gagal Eksekusi DELETE
        die("Error deleting record: " . mysqli_error($conn));
    }
} else {
    die("Error preparing delete statement: " . mysqli_error($conn));
}
?>