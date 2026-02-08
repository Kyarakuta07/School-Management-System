<?php
require_once __DIR__ . '/moe/config/connection.php';
$res = mysqli_query($conn, "SHOW CREATE TABLE shop_items");
if ($row = mysqli_fetch_assoc($res)) {
    echo $row['Create Table'];
} else {
    echo "Table shop_items not found";
}
?>