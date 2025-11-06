<?php
// Wajib ada di paling atas
session_start();
include '../../connection.php'; 

// Proteksi Halaman
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// 1. Pastikan data dikirim melalui method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Ambil semua data dari form
    $id_grade     = $_POST['id_grade'];
    $class_name   = $_POST['class_name'];
    $english      = $_POST['english'];
    $herbology    = $_POST['herbology'];
    $oceanology   = $_POST['oceanology'];
    $astronomy    = $_POST['astronomy'];
    $total_pp     = $_POST['total_pp'];

    // 3. Siapkan query SQL UPDATE yang aman
    $sql = "UPDATE class_grades SET 
                class_name = ?, 
                english = ?, 
                herbology = ?, 
                oceanology = ?, 
                astronomy = ?, 
                total_pp = ? 
            WHERE 
                id_grade = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // 4. Ikat parameter ke query (s = string, i = integer)
        mysqli_stmt_bind_param($stmt, "siiiiii", $class_name, $english, $herbology, $oceanology, $astronomy, $total_pp, $id_grade);

        // 5. Eksekusi query
        if (mysqli_stmt_execute($stmt)) {
            // Jika berhasil, kembalikan ke halaman manage_classes dengan pesan sukses
            header("Location: manage_classes.php?status=update_sukses");
            exit();
        } else {
            // Jika gagal
            echo "Error updating record: " . mysqli_error($conn);
        }
    } else {
        die("Query error: " . mysqli_error($conn));
    }

} else {
    // Jika halaman ini diakses langsung tanpa mengirim form, kembalikan
    header("Location: manage_classes.php");
    exit();
}
?>
