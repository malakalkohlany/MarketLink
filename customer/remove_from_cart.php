<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('customer');


// --------------------------------------------------
// Get Product ID
// --------------------------------------------------

$productId = isset($_GET['product_id'])
    ? (int) $_GET['product_id']
    : 0;


// --------------------------------------------------
// Validate Product ID
// --------------------------------------------------

if ($productId <= 0) {
    header('Location: cart.php');
    exit;
}


// --------------------------------------------------
// Check Cart
// --------------------------------------------------

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart']) &&
    isset($_SESSION['cart'][$productId])
) {

    // Remove product from cart
    unset($_SESSION['cart'][$productId]);
}


// --------------------------------------------------
// Return To Cart
// --------------------------------------------------

header('Location: cart.php');
exit;