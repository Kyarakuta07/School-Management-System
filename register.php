<?php
// Pastikan koneksi database tersedia untuk mengambil daftar sanctuary
include 'connection.php'; 

// Ambil daftar Sanctuary untuk dropdown
$sanctuaries_query = mysqli_query($conn, "SELECT id_sanctuary, nama_sanctuary FROM sanctuary ORDER BY nama_sanctuary ASC");

// Tambahkan pengecekan ini:
if (!$sanctuaries_query) {
    // Jika query gagal, atur pesan error yang jelas dan hentikan script
    die("Error fetching Sanctuaries: " . mysqli_error($conn));
}
// Logic untuk menampilkan pesan error jika ada redirect dari proses_register.php
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'db_error') {
        $error_message = 'Pendaftaran gagal. Terjadi kesalahan pada database.';
    } else if ($_GET['error'] == 'email_fail') {
        $error_message = 'Pendaftaran berhasil, tetapi gagal mengirimkan kode verifikasi. Coba lagi.';
    } else if ($_GET['error'] == 'sql_prepare') {
        $error_message = 'Sistem sedang sibuk. Coba beberapa saat lagi.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mediterranean Of Egypt</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css" /> 
    <style> 
        /* Sedikit pelebaran agar form banyak inputnya tetap nyaman di desktop */
        .login-container { max-width: 500px !important; } 
    </style> 
</head>
<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-logo"><img src="assets/landing/logo.png" alt="MOE Logo"></div>
        <h1>REGISTRASI ANGGOTA BARU</h1>
        <p class="subtitle">Join the Guardians of the Mediterranean</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> 
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="proses_register.php" method="POST">
            
            <div class="input-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap" required autocomplete="off">
                <i class="fa-solid fa-signature input-icon"></i>
            </div>

            <div class="input-group">
                <label for="nickname">Nickname</label>
                <input type="text" name="nickname" class="form-control" placeholder="Nama Panggilan" required autocomplete="off">
                <i class="fa-solid fa-user-tag input-icon"></i>
            </div>
            
            <div class="input-group">
                <label for="email">Alamat Email</label>
                <input type="email" name="email" class="form-control" placeholder="Email aktif untuk verifikasi" required autocomplete="email">
                <i class="fa-solid fa-envelope input-icon"></i>
            </div>

            <div class="input-group">
                <label for="noHP">Nomor HP/WA</label>
                <input type="text" name="noHP" class="form-control" placeholder="Nomor kontak aktif" required autocomplete="tel">
                <i class="fa-solid fa-phone input-icon"></i>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password yang aman" required autocomplete="new-password">
                <i class="fa-solid fa-lock input-icon"></i>
            </div>

            <div class="input-group">
                <label for="id_sanctuary">Pilih Sanctuary</label>
                <select name="id_sanctuary" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Sanctuary --</option>
                    <?php mysqli_data_seek($sanctuaries_query, 0); // Reset pointer ?>
                    <?php while($sanctuary = mysqli_fetch_assoc($sanctuaries_query)): ?>
                        <option value="<?php echo $sanctuary['id_sanctuary']; ?>">
                            <?php echo htmlspecialchars($sanctuary['nama_sanctuary']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <i class="fa-solid fa-church input-icon" style="top: 38px;"></i>
            </div>
            
            <div class="input-group">
                <label for="periode_masuk">Periode Masuk</label>
                <input type="number" name="periode_masuk" class="form-control" value="1" required min="1">
                <i class="fa-solid fa-calendar-alt input-icon" style="top: 38px;"></i>
            </div>

            <button type="submit" class="btn-login" style="margin-top: 1.5rem;">Daftar & Verifikasi Email</button>
        </form>

        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
        </a>
    </div>
</body>
</html>