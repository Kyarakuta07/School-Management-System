<?php

$servername = "localhost";
$username = "akevyrfm_Matthew";
$password = "#Mandala1968";
$dbname = "akevyrfm_moe_live";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>