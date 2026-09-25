<?php

$server = 'localhost';
$user = 'root';
$password = 'root';
$db = 'marketlink';

$conn = mysqli_connect($server, $user, $password, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");

?>
