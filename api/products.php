<?php

header('Content-Type: application/json');

require_once '../config/database.php';
require_once 'response.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET' && $method !== 'POST') {
    sendResponse(false, "Method not allowed", null, 405);
}

if ($method === 'POST') {

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        sendResponse(false, "Invalid JSON data", null, 400);
    }

    $farmer_id = $input['farmer_id'] ?? null;
    $category_id = $input['category_id'] ?? null;
    $name = $input['name'] ?? null;
    $description = $input['description'] ?? null;
    $price = $input['price'] ?? null;
    $unit = $input['unit'] ?? null;
    $stock_quantity = $input['stock_quantity'] ?? 0;

    if (!$farmer_id || !$category_id || !$name || $price === null || !$unit) {
        sendResponse(false, "Missing required product fields", null, 400);
    }

    $sql = "INSERT INTO products
            (farmer_id, category_id, name, description, price, unit, stock_quantity)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iissdsd",
        $farmer_id,
        $category_id,
        $name,
        $description,
        $price,
        $unit,
        $stock_quantity
    );

    if (!mysqli_stmt_execute($stmt)) {
        sendResponse(false, "Failed to create product", null, 500);
    }

    $product_id = mysqli_insert_id($conn);

    sendResponse(true, "Product created successfully", [
        "id" => $product_id
    ], 201);
}

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