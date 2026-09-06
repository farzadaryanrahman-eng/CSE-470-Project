<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kitty-pup-stuffs";

if (file_exists(__DIR__ . '/db_config.local.php')) {
    require __DIR__ . '/db_config.local.php';
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>
