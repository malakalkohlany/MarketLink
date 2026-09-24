<?php

header('Content-Type: application/json');

require_once '../config/database.php';
require_once 'response.php';

$sql = "SELECT id, name, description, price, unit, stock_quantity, image, is_available
        FROM products";

$result = mysqli_query($conn, $sql);

if (!$result) {
    sendResponse(false, "Failed to retrieve products", null, 500);
}

$products = [];

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

sendResponse(true, "Products retrieved successfully", $products);

?>