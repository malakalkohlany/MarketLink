<?php

header('Content-Type: application/json');

require_once '../config/database.php';

$sql = "SELECT id, name, description, price, unit, stock_quantity, image, is_available
        FROM products";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve products"
    ]);
    exit;
}

$products = [];

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

echo json_encode([
    "success" => true,
    "message" => "Products retrieved successfully",
    "data" => $products
]);

?>