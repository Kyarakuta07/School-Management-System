<?php
session_start();
include '../../connection.php'; // Naik 2 level ke root

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

$id_nethera_to_delete = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_nethera_to_delete == 0) {
    header("Location: manage_nethera.php");
    exit();
}

// Hapus record dari database
$sql_delete = "DELETE FROM nethera WHERE id_nethera = ?";
$stmt_delete = mysqli_prepare($conn, $sql_delete);

if ($stmt_delete) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_nethera_to_delete);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        // Berhasil: Redirect kembali ke manage nethera
        header("Location: manage_nethera.php?status=delete_success");
        exit();
    } else {
        die("Error deleting record: " . mysqli_error($conn));
    }
} else {
    die("Error preparing delete statement: " . mysqli_error($conn));
}
?>