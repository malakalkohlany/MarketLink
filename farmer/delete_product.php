
<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('farmer/products.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    die('Invalid CSRF token.');
}

$product_id = (int) ($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    die('Invalid product.');
}

$user_id = getUserId();

$stmt = $conn->prepare("
    SELECT id
    FROM farmers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$farmer) {
    die('Farmer not found.');
}

$farmer_id = (int) $farmer['id'];

$stmt = $conn->prepare("
    UPDATE products
    SET is_removed = 1
    WHERE id = ?
    AND farmer_id = ?
    AND is_removed = 0
");

$stmt->bind_param("ii", $product_id, $farmer_id);

if (!$stmt->execute()) {
    $stmt->close();
    die('Failed to remove product.');
}

$removed = $stmt->affected_rows;
$stmt->close();

if ($removed === 0) {
    die('Product not found or already removed.');
}

redirect('farmer/products.php');