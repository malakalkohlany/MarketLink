<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('farmer/products.php');
}


if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    die("Invalid CSRF token.");
}


$product_id = (int) ($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    die("Invalid product.");
}


$user_id = getUserId();


// Get farmer id
$stmt = $conn->prepare("
    SELECT id
    FROM farmers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$stmt->close();


if (!$farmer) {
    die("Farmer not found.");
}


$farmer_id = (int) $farmer['id'];


// Get image before deleting product
$stmt = $conn->prepare("
    SELECT image
    FROM products
    WHERE id = ?
    AND farmer_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $product_id,
    $farmer_id
);

$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();


if (!$product) {
    die("Product not found.");
}


// Delete image file
if (!empty($product['image'])) {

    $image_path = __DIR__ . '/../' . $product['image'];

    if (file_exists($image_path)) {
        unlink($image_path);
    }
}


// Delete product from database
$stmt = $conn->prepare("
    DELETE FROM products
    WHERE id = ?
    AND farmer_id = ?
");

$stmt->bind_param(
    "ii",
    $product_id,
    $farmer_id
);


if (!$stmt->execute()) {
    die("Failed to delete product.");
}

$stmt->close();


redirect('farmer/products.php');