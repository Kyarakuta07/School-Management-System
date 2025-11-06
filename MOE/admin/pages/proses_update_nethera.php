<?php

session_start();
include '../../connection.php'; 

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_nethera       = $_POST['id_nethera'];
    $nama_lengkap     = $_POST['nama_lengkap'];
    $nickname         = $_POST['nickname'];
    $no_registrasi    = $_POST['no_registrasi'];
    $id_sanctuary     = $_POST['id_sanctuary'];
    $periode_masuk    = $_POST['periode_masuk'];
    $status_akun      = $_POST['status_akun'];

    $sql = "UPDATE nethera SET 
                nama_lengkap = ?, 
                nickname = ?, 
                no_registrasi = ?, 
                id_sanctuary = ?, 
                periode_masuk = ?, 
                status_akun = ? 
            WHERE 
                id_nethera = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssissi", $nama_lengkap, $nickname, $no_registrasi, $id_sanctuary, $periode_masuk, $status_akun, $id_nethera);


        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_nethera.php?status=update_sukses");
            exit();
        } else {
            echo "Error updating record: " . mysqli_error($conn);
        }
    } else {
        die("Query error: " . mysqli_error($conn)); 
    }

} else {

    header("Location: manage_nethera.php");
    exit();
}
?>

