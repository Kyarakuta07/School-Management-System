<?php
// Security: Must be authenticated admin
require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';

// Check admin authentication
if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['Vasiki', 'Hakaes'])) {
    http_response_code(403);
    echo '<tr><td colspan="9" style="color:red; text-align:center;">Unauthorized Access</td></tr>';
    exit();
}

// Database connection
include '../../config/connection.php';

// Get and prepare search data
$search_term = '';
if (isset($_POST['search'])) {
    $search_term = $_POST['search'];
}

// Prepare for LIKE query - use prepared statements
$search_param = "%{$search_term}%";

$sql = "SELECT n.nama_lengkap, n.username, s.nama_sanctuary, cg.*
        FROM class_grades cg
        JOIN nethera n ON cg.id_nethera = n.id_nethera
        LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
        WHERE n.nama_lengkap LIKE ? 
           OR s.nama_sanctuary LIKE ?
           OR cg.class_name LIKE ?
        ORDER BY cg.id_grade DESC";

$stmt = mysqli_prepare($conn, $sql);
$output = '';

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
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
    } else {
        error_log("Grade search execute failed: " . mysqli_stmt_error($stmt));
        $output = '<tr><td colspan="9" style="text-align: center; padding: 20px; color: orange;">Search error. Please try again.</td></tr>';
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Grade search prepare failed: " . mysqli_error($conn));
    $output = '<tr><td colspan="9" style="text-align: center; padding: 20px; color: orange;">Search error. Please try again.</td></tr>';
}

echo $output;
?>