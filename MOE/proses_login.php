<?php
// Mengaktifkan session PHP, ini wajib ada di paling atas
session_start();

// Menghubungkan ke file koneksi database
include 'connection.php';

// --- BAGIAN INI SUDAH DISESUAIKAN DENGAN DATABASE ANDA ---

// 1. Ambil data dari form login yang dikirimkan (method POST)
$username_input = $_POST['username'];
$password_input = $_POST['password'];

$sql = "SELECT id_nethera, nama_lengkap, nickname, role,
password FROM nethera WHERE (nama_lengkap = ? OR nickname = ?) AND password = ?";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sss", $username_input, $username_input, $password_input);
    mysqli_stmt_execute($stmt);

    // Ambil hasilnya
    $result = mysqli_stmt_get_result($stmt);

    // 4. Cek apakah pengguna ditemukan (jika ada 1 baris data yang cocok)
    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);

        // 5. Simpan data pengguna yang penting ke dalam session
        $_SESSION['id_nethera'] = $data['id_nethera'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['status_login'] = "berhasil"; // Penanda bahwa sudah login

        // 6. Arahkan (redirect) pengguna berdasarkan rolenya
        if ($data['role'] == 'Vasiki') {
            // Jika rolenya 'Vasiki', arahkan ke folder admin
            header("Location: admin/index.php");
            exit();
        } else {
            // Jika rolenya 'Nethera', arahkan ke folder pengguna
            header("Location: pengguna/beranda.php");
            exit();
        }

    } else {
        // Jika tidak ada data yang cocok (login gagal)
        // Arahkan kembali ke halaman login dengan pesan error
        header("Location: index.php?pesan=gagal");
        exit();
    }
} else {
    // Jika query SQL sendiri gagal dipersiapkan (misalnya karena salah ketik nama kolom)
    die("Error pada persiapan query: " . mysqli_error($conn));
}
?>