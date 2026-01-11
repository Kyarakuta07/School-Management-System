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
        error_log("CSRF token validation failed for add grade");
        header("Location: manage_classes.php?status=csrf_failed");
        exit();
    }

    // 1. Ambil dan bersihkan data
    $id_nethera = (int) $_POST['id_nethera'];
    $class_name = trim($_POST['class_name']);

    // Ambil nilai mata pelajaran (Pastikan input type="number" di HTML)
    $english = (int) $_POST['english'];
    $herbology = (int) $_POST['herbology'];
    $oceanology = (int) $_POST['oceanology'];
    $astronomy = (int) $_POST['astronomy'];

    // 2. KALKULASI: Hitung Total Poin Prestasi (PP)
    $total_pp = $english + $herbology + $oceanology + $astronomy;

    // 3. SQL INSERT dengan Prepared Statements
    $sql = "INSERT INTO class_grades 
            (id_nethera, class_name, english, herbology, oceanology, astronomy, total_pp) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "isiisii",
            $id_nethera,
            $class_name,
            $english,
            $herbology,
            $oceanology,
            $astronomy,
            $total_pp
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_classes.php?status=add_grade_success");
            exit();
        } else {
            error_log("Error adding grade: " . mysqli_stmt_error($stmt));
            header("Location: add_grade.php?error=db_error");
            exit();
        }
    } else {
        error_log("Query prepare error for add grade: " . mysqli_error($conn));
        header("Location: add_grade.php?error=db_error");
        exit();
    }

} else {
    header("Location: manage_classes.php");
    exit();
}