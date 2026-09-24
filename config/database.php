<?php

$server = 'localhost';
$user = 'root';
$password = 'root';
$db = 'marketlink';

$conn = mysqli_connect($server, $user, $password, $db);
if($conn->connect_error){
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>