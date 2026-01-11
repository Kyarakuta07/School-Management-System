<?php
require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';
require_once '../../core/activity_logger.php';
include '../../config/connection.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        error_log("CSRF token validation failed for update nethera");
        header("Location: manage_nethera.php?status=csrf_failed");
        exit();
    }

    $id_nethera = (int) $_POST['id_nethera'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    // $no_registrasi ambil dari POST, tapi nanti kita cek ulang
    $posted_no_reg = $_POST['no_registrasi'];
    $id_sanctuary = $_POST['id_sanctuary'];
    $periode_masuk = $_POST['periode_masuk'];
    $status_akun = $_POST['status_akun'];

    // --- LOGIC AUTO NUMBER DIMULAI ---

    // 1. Cek data lama di database sebelum di-update
    $query_cek = mysqli_prepare($conn, "SELECT no_registrasi, status_akun, nama_lengkap, username, id_sanctuary, periode_masuk FROM nethera WHERE id_nethera = ?");
    mysqli_stmt_bind_param($query_cek, "i", $id_nethera);
    mysqli_stmt_execute($query_cek);
    $result_cek = mysqli_stmt_get_result($query_cek);
    $data_lama = mysqli_fetch_assoc($result_cek);
    mysqli_stmt_close($query_cek);

    // Variabel final yang akan masuk DB
    $final_no_registrasi = $posted_no_reg;

    // 2. KONDISI TRIGGER: 
    // Jika Status berubah jadi 'Aktif' DAN (Nomor Registrasi masih kosong ATAU NULL)
    if ($status_akun == 'Aktif' && (empty($data_lama['no_registrasi']) || $data_lama['no_registrasi'] == '')) {

        // Validasi: Admin harus sudah memilih Sanctuary
        if (empty($id_sanctuary)) {
            // Redirect error jika sanctuary belum dipilih tapi status dipaksa aktif
            header("Location: edit_nethera.php?id=$id_nethera&error=wajib_pilih_sanctuary");
            exit();
        }

        // A. Ambil Nama Sanctuary (misal: AMMIT)
        $q_sanc = mysqli_prepare($conn, "SELECT nama_sanctuary FROM sanctuary WHERE id_sanctuary = ?");
        mysqli_stmt_bind_param($q_sanc, "i", $id_sanctuary);
        mysqli_stmt_execute($q_sanc);
        $result_sanc = mysqli_stmt_get_result($q_sanc);
        $d_sanc = mysqli_fetch_assoc($result_sanc);
        mysqli_stmt_close($q_sanc);

        if ($d_sanc) {
            $nama_sanctuary = strtoupper($d_sanc['nama_sanctuary']); // AMMIT

            // B. Buat Prefix Pencarian (AMMIT_4_)
            $prefix = $nama_sanctuary . "_" . $periode_masuk . "_";

            // C. Cari nomor urut terakhir di database yang mirip prefix tersebut
            // ORDER BY LENGTH penting agar _10 dianggap lebih besar dari _9
            $prefix_pattern = $prefix . "%";
            $query_last = "SELECT no_registrasi FROM nethera 
                           WHERE no_registrasi LIKE ? 
                           ORDER BY LENGTH(no_registrasi) DESC, no_registrasi DESC 
                           LIMIT 1";

            $stmt_last = mysqli_prepare($conn, $query_last);
            mysqli_stmt_bind_param($stmt_last, "s", $prefix_pattern);
            mysqli_stmt_execute($stmt_last);
            $result_last = mysqli_stmt_get_result($stmt_last);

            if (mysqli_num_rows($result_last) > 0) {
                // Jika sudah ada, ambil angka terakhir + 1
                $row = mysqli_fetch_assoc($result_last);
                $parts = explode("_", $row['no_registrasi']);
                $last_number = (int) end($parts); // Ambil pecahan terakhir
                $new_number = $last_number + 1;
            } else {
                // Jika belum ada sama sekali di sanctuary & periode itu
                $new_number = 1;
            }

            mysqli_stmt_close($stmt_last);

            // D. Set Nomor Registrasi Baru
            $final_no_registrasi = $prefix . $new_number; // Contoh: AMMIT_4_7
        }
    }
    // --- LOGIC AUTO NUMBER SELESAI ---

    // Update Query menggunakan $final_no_registrasi
    $sql = "UPDATE nethera SET 
                nama_lengkap = ?, 
                username = ?, 
                no_registrasi = ?, 
                id_sanctuary = ?, 
                periode_masuk = ?, 
                status_akun = ? 
            WHERE 
                id_nethera = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Perhatikan parameter ke-3 menggunakan $final_no_registrasi
        mysqli_stmt_bind_param(
            $stmt,
            "sssissi",
            $nama_lengkap,
            $username,
            $final_no_registrasi,
            $id_sanctuary,
            $periode_masuk,
            $status_akun,
            $id_nethera
        );

        if (mysqli_stmt_execute($stmt)) {
            // Log the update action
            $new_data = [
                'nama_lengkap' => $nama_lengkap,
                'username' => $username,
                'no_registrasi' => $final_no_registrasi,
                'id_sanctuary' => $id_sanctuary,
                'periode_masuk' => $periode_masuk,
                'status_akun' => $status_akun
            ];

            log_update(
                $conn,
                'nethera',
                $id_nethera,
                'Updated user: ' . $username,
                $data_lama,
                $new_data
            );

            header("Location: manage_nethera.php?status=update_sukses");
            exit();
        } else {
            error_log("Error updating nethera record: " . mysqli_error($conn));
            header("Location: manage_nethera.php?status=update_error");
            exit();
        }
    } else {
        error_log("Query prepare error for nethera update: " . mysqli_error($conn));
        header("Location: manage_nethera.php?status=db_error");
        exit();
    }

} else {
    header("Location: manage_nethera.php");
    exit();
}
?>