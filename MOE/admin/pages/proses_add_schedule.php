<?php
session_start();
include '../../connection.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil dan bersihkan data input (trim)
    $class_name = trim($_POST['class_name']);
    $hakaes_name = trim($_POST['hakaes_name']);
    $schedule_day = trim($_POST['schedule_day']);
    $schedule_time = trim($_POST['schedule_time']);
    $class_description = trim($_POST['class_description']);
    
    $image_path = NULL; // Default path NULL jika upload gagal atau tidak ada file

    // 2. HANDLE FILE UPLOAD (Menggunakan $_FILES)
    if (isset($_FILES['class_image']) && $_FILES['class_image']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp = $_FILES['class_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['class_image']['name'], PATHINFO_EXTENSION));
        
        // Target folder (Harus sudah dibuat di root proyek Anda)
        $upload_dir = '../../class_images/'; 
        
        // Buat nama file unik
        $new_file_name = uniqid('class_', true) . '.' . $file_ext;
        $target_file = $upload_dir . $new_file_name;
        
        // Pengecekan tipe file dasar (Opsional)
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_types) && move_uploaded_file($file_tmp, $target_file)) {
            // Jika berhasil diunggah, simpan path relatif ke DB
            $image_path = 'class_images/' . $new_file_name; 
        } else {
            // Jika tipe file salah atau upload gagal
            header("Location: add_schedule.php?error=upload_fail&msg=Invalid file type or upload error.");
            exit();
        }
    }
    
    // 3. SQL INSERT dengan Prepared Statements
    // Gunakan $image_path yang baru kita tentukan, bukan $_POST
    $sql = "INSERT INTO class_schedule (class_name, hakaes_name, schedule_day, schedule_time, class_image_url, class_description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssss", 
            $class_name,
            $hakaes_name,
            $schedule_day,
            $schedule_time,
            $image_path, // Variabel yang sudah diproses
            $class_description
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Berhasil: Redirect kembali ke manage classes
            header("Location: manage_classes.php?status=add_success");
            exit();
        } else {
            // Gagal Eksekusi SQL
            header("Location: add_schedule.php?error=db_error&msg=". urlencode(mysqli_error($conn)));
            exit();
        }
    } else {
        die("Query error: " . mysqli_error($conn));
    }

} else {
    // Jika diakses tanpa method POST
    header("Location: manage_classes.php");
    exit();
}
?>