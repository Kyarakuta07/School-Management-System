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

    // 2. Ambil dan bersihkan data
    $id_grade = (int)$_POST['id_grade']; // Harus integer
    $class_name = trim($_POST['class_name']);
    
    // Ambil nilai mata pelajaran (Wajib dikonversi ke INT)
    $english = (int)$_POST['english'];
    $herbology = (int)$_POST['herbology'];
    $oceanology = (int)$_POST['oceanology'];
    $astronomy = (int)$_POST['astronomy'];
    
    // 3. KALKULASI KRITIS: Hitung ulang Total PP
    $new_total_pp = $english + $herbology + $oceanology + $astronomy;

    // 4. Siapkan query SQL UPDATE yang aman
    // Kolom yang di-update: class_name, 4 scores, dan total_pp
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
        // 5. Ikat parameter ke query (s = string, 6x i = integer, 1x i = ID)
        // Format String: s, i, i, i, i, i, i (Total 7 parameter)
        // NOTE: Kita tidak menggunakan $total_pp dari $_POST, tapi menggunakan $new_total_pp
        mysqli_stmt_bind_param($stmt, "siiiiii", 
            $class_name,
            $english,
            $herbology,
            $oceanology,
            $astronomy,
            $new_total_pp, // <-- Hasil Kalkulasi Baru
            $id_grade
        );

        // 6. Eksekusi query
        if (mysqli_stmt_execute($stmt)) {
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
    header("Location: manage_classes.php");
    exit();
}
?>