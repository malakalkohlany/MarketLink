<?php

$server = 'localhost';
$user = 'root';
$password = 'root';
$db = 'marketlink';
$port = 3306;

$conn = mysqli_connect($server, $user, $password, $db, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");

?>
