<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);


// --------------------------------------------------
// Get Request Data
// --------------------------------------------------

$productId = isset($_GET['product_id'])
    ? (int) $_GET['product_id']
    : 0;

$action = $_GET['action'] ?? '';


// --------------------------------------------------
// Validate Request
// --------------------------------------------------

if ($productId <= 0) {
    header('Location: cart.php');
    exit;
}

if (!in_array($action, ['increase', 'decrease'], true)) {
    header('Location: cart.php');
    exit;
}


// --------------------------------------------------
// Check Cart
// --------------------------------------------------

if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    !isset($_SESSION['cart'][$productId])
) {
    header('Location: cart.php');
    exit;
}


// --------------------------------------------------
// Get Current Product From Database
// --------------------------------------------------

$product_stmt = $conn->prepare("
    SELECT
        id,
        price,
        unit,
        stock_quantity,
        image,
        farmer_id,
        name
    FROM products
    WHERE id = ?
      AND is_available = 1
      AND moderation_status = 'approved'
    LIMIT 1
");

$product_stmt->bind_param("i", $productId);
$product_stmt->execute();

$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

$product_stmt->close();


// --------------------------------------------------
// Product No Longer Available
// --------------------------------------------------

if (!$product) {

    unset($_SESSION['cart'][$productId]);

    header('Location: cart.php');
    exit;
}


// --------------------------------------------------
// Current Quantity
// --------------------------------------------------

$currentQuantity = (float)
    $_SESSION['cart'][$productId]['quantity'];


// --------------------------------------------------
// Update Quantity
// --------------------------------------------------

if ($action === 'increase') {

    $newQuantity = $currentQuantity + 1;

    $stockQuantity = (float) $product['stock_quantity'];

    // Do not allow quantity above available stock
    if ($newQuantity <= $stockQuantity) {

        $_SESSION['cart'][$productId]['quantity'] =
            $newQuantity;

        $_SESSION['cart'][$productId]['subtotal'] =
            $newQuantity * (float) $product['price'];
    }

}


if ($action === 'decrease') {

    $newQuantity = $currentQuantity - 1;

    // Remove item if quantity reaches zero
    if ($newQuantity <= 0) {

        unset($_SESSION['cart'][$productId]);

    } else {

        $_SESSION['cart'][$productId]['quantity'] =
            $newQuantity;

        $_SESSION['cart'][$productId]['subtotal'] =
            $newQuantity * (float) $product['price'];
    }
}


// --------------------------------------------------
// Return To Cart
// --------------------------------------------------

header('Location: cart.php');
exit;