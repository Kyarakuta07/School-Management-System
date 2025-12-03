<?php
session_start();
include '../../connection.php'; // Path ke file koneksi

// Proteksi Halaman
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil Data dari Form
    $id_schedule = (int)$_POST['id_schedule'];
    $class_name = trim($_POST['class_name']);
    $hakaes_name = trim($_POST['hakaes_name']);
    $schedule_day = trim($_POST['schedule_day']);
    $schedule_time = trim($_POST['schedule_time']);
    $class_description = trim($_POST['class_description']);
    $old_image_path_db = $_POST['old_image_path']; // Data dari hidden input
    
    $new_image_path = NULL; // Default path baru adalah NULL
    
    // Direktori Upload (Naik 2 level ke root, lalu masuk folder 'class_images')
    $upload_dir_root = '../../class_images/'; 
    
    // 2. HANDLE FILE UPLOAD BARU (jika ada file di-upload)
    if (isset($_FILES['class_image']) && $_FILES['class_image']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp = $_FILES['class_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['class_image']['name'], PATHINFO_EXTENSION));
        $new_file_name = uniqid('class_', true) . '.' . $file_ext;
        $target_file = $upload_dir_root . $new_file_name;
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_types)) {
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Sukses upload. Tentukan path baru untuk DB (relatif dari root proyek)
                $new_image_path = 'class_images/' . $new_file_name;
                
                // 3. HAPUS FILE LAMA DARI SERVER
                if (!empty($old_image_path_db)) {
                    $file_to_delete = '../../' . $old_image_path_db; 
                    if (file_exists($file_to_delete) && is_file($file_to_delete)) {
                        unlink($file_to_delete);
                    }
                }
            } else {
                 // Gagal pindah file
                 header("Location: edit_schedule.php?id=$id_schedule&error=upload_fail");
                 exit();
            }
        } else {
             // Gagal karena tipe file salah
             header("Location: edit_schedule.php?id=$id_schedule&error=file_type");
             exit();
        }
    } else {
        // 4. Jika TIDAK ADA file baru diupload, pertahankan path gambar lama di DB.
        $new_image_path = $old_image_path_db;
    }

    // 5. PREPARE QUERY UPDATE (Menggunakan variabel $new_image_path yang sudah diputuskan)
    $sql = "UPDATE class_schedule SET 
                class_name = ?, 
                hakaes_name = ?, 
                schedule_day = ?, 
                schedule_time = ?, 
                class_description = ?, 
                class_image_url = ? 
            WHERE id_schedule = ?";
    
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind Parameter: 6 strings + 1 integer (id)
        mysqli_stmt_bind_param($stmt, "ssssssi", 
            $class_name,
            $hakaes_name,
            $schedule_day,
            $schedule_time,
            $class_description,
            $new_image_path, // Path baru atau Path lama jika tidak ada upload
            $id_schedule
        );

        if (mysqli_stmt_execute($stmt)) {
            // Berhasil: Redirect kembali ke halaman manage classes
            header("Location: manage_classes.php?status=update_success");
            exit();
        } else {
            // Gagal Eksekusi SQL
            die("Error SQL: " . mysqli_error($conn));
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