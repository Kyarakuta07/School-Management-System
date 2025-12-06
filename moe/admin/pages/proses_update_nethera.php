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
    $username         = $_POST['username'];
    // $no_registrasi ambil dari POST, tapi nanti kita cek ulang
    $posted_no_reg    = $_POST['no_registrasi']; 
    $id_sanctuary     = $_POST['id_sanctuary'];
    $periode_masuk    = $_POST['periode_masuk'];
    $status_akun      = $_POST['status_akun'];

    // --- LOGIC AUTO NUMBER DIMULAI ---

    // 1. Cek data lama di database sebelum di-update
    $query_cek = mysqli_query($conn, "SELECT no_registrasi, status_akun FROM nethera WHERE id_nethera = '$id_nethera'");
    $data_lama = mysqli_fetch_assoc($query_cek);

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
        $q_sanc = mysqli_query($conn, "SELECT nama_sanctuary FROM sanctuary WHERE id_sanctuary = '$id_sanctuary'");
        $d_sanc = mysqli_fetch_assoc($q_sanc);
        
        if ($d_sanc) {
            $nama_sanctuary = strtoupper($d_sanc['nama_sanctuary']); // AMMIT
            
            // B. Buat Prefix Pencarian (AMMIT_4_)
            $prefix = $nama_sanctuary . "_" . $periode_masuk . "_";
            
            // C. Cari nomor urut terakhir di database yang mirip prefix tersebut
            // ORDER BY LENGTH penting agar _10 dianggap lebih besar dari _9
            $query_last = "SELECT no_registrasi FROM nethera 
                           WHERE no_registrasi LIKE '$prefix%' 
                           ORDER BY LENGTH(no_registrasi) DESC, no_registrasi DESC 
                           LIMIT 1";
            
            $result_last = mysqli_query($conn, $query_last);

            if (mysqli_num_rows($result_last) > 0) {
                // Jika sudah ada, ambil angka terakhir + 1
                $row = mysqli_fetch_assoc($result_last);
                $parts = explode("_", $row['no_registrasi']);
                $last_number = (int)end($parts); // Ambil pecahan terakhir
                $new_number = $last_number + 1;
            } else {
                // Jika belum ada sama sekali di sanctuary & periode itu
                $new_number = 1;
            }

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
        mysqli_stmt_bind_param($stmt, "sssissi", 
            $nama_lengkap, 
            $username, 
            $final_no_registrasi, 
            $id_sanctuary, 
            $periode_masuk, 
            $status_akun, 
            $id_nethera
        );

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