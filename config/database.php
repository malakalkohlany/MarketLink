<?php

$server = 'localhost';
$user = 'root';
$password = 'root';
$db = 'marketlink';
$port = 8585;

$conn = mysqli_connect($server, $user, $password, $db, $port);

if (!$conn) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

