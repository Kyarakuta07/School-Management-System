<?php

include '../../connection.php';
session_start();

$search_term = '';
if (isset($_POST['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_POST['search']);
}

$sql = "SELECT n.nama_lengkap, n.nickname, s.nama_sanctuary, cg.*
        FROM class_grades cg
        JOIN nethera n ON cg.id_nethera = n.id_nethera
        LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
        WHERE n.nama_lengkap LIKE '%$search_term%' 
           OR s.nama_sanctuary LIKE '%$search_term%'
           OR cg.class_name LIKE '%$search_term%'
        ORDER BY cg.id_grade DESC";

$result = mysqli_query($conn, $sql);

$output = '';

if ($result && mysqli_num_rows($result) > 0) {
    while ($grade = mysqli_fetch_assoc($result)) {
        $output .= '<tr>';
        $output .= '<td>' . htmlspecialchars($grade['nama_lengkap']) . '</td>';
        $output .= '<td>' . htmlspecialchars($grade['nama_sanctuary']) . '</td>';
        $output .= '<td>' . htmlspecialchars($grade['class_name']) . '</td>';
        $output .= '<td>' . htmlspecialchars($grade['english']) . '</td>';
        $output .= '<td>' . htmlspecialchars($grade['herbology']) . '</td>';
        $output .= '<td>' . htmlspecialchars($grade['oceanology']) . '</td>';
        $output .= '<td>' . htmlspecialchars($grade['astronomy']) . '</td>';
        $output .= '<td><strong>' . htmlspecialchars($grade['total_pp']) . '</strong></td>';
        $output .= '<td>
                        <div class="action-buttons">
                            <a href="edit_grade.php?id=' . $grade['id_grade'] . '" class="btn-edit">
                                <i class="uil uil-edit"></i>
                            </a>
                            <button class="btn-view"><i class="uil uil-eye"></i></button>
                        </div>
                    </td>';
        $output .= '</tr>';
    }
} else {

    $output = '<tr><td colspan="9" style="text-align: center; padding: 20px;">Tidak ada data nilai yang cocok dengan pencarian.</td></tr>';
}

echo $output;
?>
