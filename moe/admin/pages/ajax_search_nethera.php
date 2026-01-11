<?php
// Security: Must be authenticated admin
require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';

// Check admin authentication
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    http_response_code(403);
    echo '<tr><td colspan="7" style="color:red; text-align:center;">Unauthorized Access</td></tr>';
    exit();
}

// Database connection
include '../../config/connection.php';

// Get and prepare search data
$search_term = '';
if (isset($_POST['search'])) {
    $search_term = $_POST['search'];
}

// Persiapan untuk LIKE query. % dipasang di sini, bukan di string SQL
$search_param = "%{$search_term}%";


// 3. Query SQL dengan Prepared Statements (Paling Aman)
$sql = "SELECT n.id_nethera, n.no_registrasi, n.nama_lengkap, n.username, n.noHP, s.nama_sanctuary, n.periode_masuk, n.status_akun
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
                $status_class = $nethera['status_akun'];
                $initial = strtoupper(substr($nethera['nama_lengkap'], 0, 1));

                $output .= '<tr data-status="' . htmlspecialchars($nethera['status_akun']) . '">';

                // No. Registrasi with badge
                $output .= '<td><span class="reg-badge">' . htmlspecialchars($nethera['no_registrasi']) . '</span></td>';

                // Full Name with avatar
                $output .= '<td>
                    <div class="user-cell">
                        <div class="user-avatar">' . $initial . '</div>
                        <strong>' . htmlspecialchars($nethera['nama_lengkap']) . '</strong>
                    </div>
                </td>';

                // Username with code style
                $output .= '<td><code class="username-code">@' . htmlspecialchars($nethera['username']) . '</code></td>';

                // Phone
                $output .= '<td>' . htmlspecialchars($nethera['noHP'] ?? '-') . '</td>';

                // Sanctuary with badge
                $output .= '<td><span class="sanctuary-badge">' . htmlspecialchars($nethera['nama_sanctuary']) . '</span></td>';

                // Period
                $output .= '<td>' . htmlspecialchars($nethera['periode_masuk']) . '</td>';

                // Status badge
                $output .= '<td><span class="status-badge status-' . $status_class . '">' . htmlspecialchars($nethera['status_akun']) . '</span></td>';

                // Actions
                $output .= '<td style="white-space: nowrap;">
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
            $output = '<tr><td colspan="8" class="empty-state">
                        <div class="empty-icon"><i class="uil uil-search-alt"></i></div>
                        <h4>No Results Found</h4>
                        <p>Tidak ada hasil untuk "' . htmlspecialchars($search_term) . '"</p>
                       </td></tr>';
        }

    } else {
        // Error saat eksekusi - SECURITY FIX: Don't expose SQL errors
        error_log("Search query execute error: " . mysqli_stmt_error($stmt));
        $output = '<tr><td colspan="7" class="empty-state">
                    <div class="empty-icon"><i class="uil uil-exclamation-triangle"></i></div>
                    <h4>System Error</h4>
                    <p>An error occurred. Please try again later.</p>
                   </td></tr>';
    }

    mysqli_stmt_close($stmt);

} else {
    // Error saat prepare query - SECURITY FIX: Don't expose SQL errors
    error_log("Search query prepare error: " . mysqli_error($conn));
    $output = '<tr><td colspan="7" class="empty-state">
                <div class="empty-icon"><i class="uil uil-exclamation-triangle"></i></div>
                <h4>System Error</h4>
                <p>An error occurred. Please try again later.</p>
               </td></tr>';
}

echo $output;