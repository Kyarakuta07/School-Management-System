<?php
session_start();
include '../../connection.php';

// Proteksi Halaman
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_classes.php");
    exit();
}
$id_schedule_to_delete = (int)$_GET['id']; // Pastikan sebagai integer

// --- 1. AMBIL PATH GAMBAR LAMA SEBELUM MENGHAPUS RECORD ---
$sql_fetch_img = "SELECT class_image_url FROM class_schedule WHERE id_schedule = ?";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch_img);

if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $id_schedule_to_delete);
    mysqli_stmt_execute($stmt_fetch);
    $result_img = mysqli_stmt_get_result($stmt_fetch);
    $data_img = mysqli_fetch_assoc($result_img);
    $image_path_to_delete = $data_img['class_image_url'] ?? null; // Ambil URL gambar

    mysqli_stmt_close($stmt_fetch);

    // --- 2. HAPUS FILE GAMBAR DARI SERVER (Jika ada) ---
    if (!empty($image_path_to_delete)) {
        // Path absolut (naik 2 level ke root, lalu masuk folder gambar)
        $file_to_delete = '../../' . $image_path_to_delete; 
        
        // Cek apakah file ada dan hapus
        if (file_exists($file_to_delete) && is_file($file_to_delete)) {
            unlink($file_to_delete);
        }
    }

    // --- 3. HAPUS RECORD DARI DATABASE ---
    $sql_delete = "DELETE FROM class_schedule WHERE id_schedule = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete);

    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id_schedule_to_delete);
        
        if (mysqli_stmt_execute($stmt_delete)) {
            // Berhasil: Redirect
            header("Location: manage_classes.php?status=delete_sukses");
            exit();
        } else {
            die("Error deleting record: " . mysqli_error($conn));
        }
    } else {
        die("Error preparing delete statement: " . mysqli_error($conn));
    }

} else {
    die("Error fetching image path: " . mysqli_error($conn));
}
?>