<?php

include '../../connection.php';


$search_term = '';
if (isset($_POST['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_POST['search']);
}


$sql = "SELECT n.id_nethera, n.no_registrasi, n.nama_lengkap, n.nickname, s.nama_sanctuary, n.periode_masuk, n.status_akun
        FROM nethera n
        LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
        WHERE n.nama_lengkap LIKE '%$search_term%' 
          OR n.nickname LIKE '%$search_term%' 
          OR s.nama_sanctuary LIKE '%$search_term%'
        ORDER BY n.id_nethera ASC";

$result = mysqli_query($conn, $sql);


$output = '';

if ($result && mysqli_num_rows($result) > 0) {
    while ($nethera = mysqli_fetch_assoc($result)) {
        $status_class = strtolower(str_replace(' ', '-', $nethera['status_akun']));
        $output .= '<tr>';
        $output .= '<td>' . htmlspecialchars($nethera['no_registrasi']) . '</td>';
        $output .= '<td>' . htmlspecialchars($nethera['nama_lengkap']) . '</td>';
        $output .= '<td>' . htmlspecialchars($nethera['nickname']) . '</td>';
        $output .= '<td>' . htmlspecialchars($nethera['nama_sanctuary']) . '</td>';
        $output .= '<td>' . htmlspecialchars($nethera['periode_masuk']) . '</td>';
        $output .= '<td><span class="status-badge status-' . $status_class . '">' . htmlspecialchars($nethera['status_akun']) . '</span></td>';
        
        $output .= '<td>
                        <div class="action-buttons">
                            <a href="edit_nethera.php?id=' . $nethera['id_nethera'] . '" class="btn-edit">
                                <i class="uil uil-edit"></i>
                            </a>
                            <button class="btn-view"><i class="uil uil-eye"></i></button>
                        </div>
                    </td>';
        $output .= '</tr>';
    }
} else {
    $output = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Tidak ada data Nethera yang cocok dengan pencarian.</td></tr>';
}

echo $output;
?>
