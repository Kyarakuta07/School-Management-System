<?php
session_start();
include '../../config/connection.php'; 

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil dan bersihkan data
    $id_nethera = (int)$_POST['id_nethera'];
    $class_name = trim($_POST['class_name']);
    
    // Ambil nilai mata pelajaran (Pastikan input type="number" di HTML)
    $english = (int)$_POST['english'];
    $herbology = (int)$_POST['herbology'];
    $oceanology = (int)$_POST['oceanology'];
    $astronomy = (int)$_POST['astronomy'];
    
    // 2. KALKULASI: Hitung Total Poin Prestasi (PP)
    $total_pp = $english + $herbology + $oceanology + $astronomy;
    
    // 3. SQL INSERT dengan Prepared Statements
    // Kolom: id_nethera, class_name, english, herbology, oceanology, astronomy, total_pp
    $sql = "INSERT INTO class_grades 
            (id_nethera, class_name, english, herbology, oceanology, astronomy, total_pp) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind Parameter: i, s, i, i, i, i, i (1 integer ID, 1 string name, 5 integers for scores/total)
        mysqli_stmt_bind_param($stmt, "isiisii", 
            $id_nethera,
            $class_name,
            $english,
            $herbology,
            $oceanology,
            $astronomy,
            $total_pp // Hasil kalkulasi
        );

        if (mysqli_stmt_execute($stmt)) {
            // Berhasil: Redirect kembali ke halaman manage classes
            header("Location: manage_classes.php?status=add_grade_success");
            exit();
        } else {
            // Gagal Eksekusi SQL
            header("Location: add_grade.php?error=db_error");
            exit();
        }
    } else {
        die("Query error: " . mysqli_error($conn));
    }

} else {
    header("Location: manage_classes.php");
    exit();
}
?>