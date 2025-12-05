<?php

// 1. Path Koneksi Database
// Pastikan path ini benar (dari pages/ ke root/connection.php)
include '../../connection.php';
// (Keluar dari pages/ -> Keluar dari admin/ -> Sampai di connection.php)


// 2. Ambil dan Persiapkan Data Pencarian
$search_term = '';
if (isset($_POST['search'])) {
    // Ambil data yang dikirimkan melalui AJAX
    $search_term = $_POST['search'];
}

// Persiapan untuk LIKE query. % dipasang di sini, bukan di string SQL
$search_param = "%{$search_term}%"; 


// 3. Query SQL dengan Prepared Statements (Paling Aman)
$sql = "SELECT n.id_nethera, n.no_registrasi, n.nama_lengkap, n.username, s.nama_sanctuary, n.periode_masuk, n.status_akun
        FROM nethera n
        LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
        WHERE n.nama_lengkap LIKE ? 
          OR n.username LIKE ? 
          OR s.nama_sanctuary LIKE ?
        ORDER BY n.id_nethera ASC";

$stmt = mysqli_prepare($conn, $sql);

$output = '';

// 4. Proses Eksekusi Query
if ($stmt) {
    // Bind parameter untuk 3 kondisi LIKE ('sss' = tiga parameter string)
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    
    // Coba eksekusi
    if (mysqli_stmt_execute($stmt)) {
        
        $result = mysqli_stmt_get_result($stmt);

        // 5. Proses Hasil
        if (mysqli_num_rows($result) > 0) {
            while ($nethera = mysqli_fetch_assoc($result)) {
                // Status badge logic
                $status_class = strtolower($nethera['status_akun']);
                $status_class = str_replace(' ', '-', $status_class);
                
                $output .= '<tr>';
                $output .= '<td>' . htmlspecialchars($nethera['no_registrasi']) . '</td>';
                $output .= '<td>' . htmlspecialchars($nethera['nama_lengkap']) . '</td>';
                $output .= '<td>' . htmlspecialchars($nethera['username']) . '</td>';
                $output .= '<td>' . htmlspecialchars($nethera['nama_sanctuary']) . '</td>';
                $output .= '<td>' . htmlspecialchars($nethera['periode_masuk']) . '</td>';
                $output .= '<td><span class="status-badge status-' . $status_class . '">' . htmlspecialchars($nethera['status_akun']) . '</span></td>';
                
$output .= '<td>
    <div class="action-buttons">
        
        <a href="edit_nethera.php?id=' . $nethera['id_nethera'] . '" class="btn-edit" title="Edit">
            <i class="uil uil-edit"></i>
        </a>
        
        <button class="btn-delete" title="Delete" onclick="confirmDelete(' . $nethera['id_nethera'] . ')">
            <i class="uil uil-trash-alt"></i>
        </button>
    </div>
</td>';
$output .= '</tr>';
            }
        } else {
            // Jika tidak ditemukan hasil
            $output = '<tr><td colspan="7" style="text-align: center; padding: 20px;">
                        <i class="uil uil-search-alt" style="font-size: 1.2rem; margin-right: 5px;"></i> 
                        No results found for "'. htmlspecialchars($search_term) .'".
                       </td></tr>';
        }
        
    } else {
        // Error saat eksekusi
        $output = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: red;">
                    SQL Execution Error: ' . mysqli_stmt_error($stmt) . '
                   </td></tr>';
    }

    mysqli_stmt_close($stmt);

} else {
    // Error saat prepare query (misalnya salah nama kolom)
    $output = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: red;">
                SQL Prepare Error: ' . mysqli_error($conn) . '
               </td></tr>';
}

echo $output;
?>