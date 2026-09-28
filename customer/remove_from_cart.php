<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$productId = isset($_GET['product_id'])
    ? (int) $_GET['product_id']
    : 0;

if ($productId <= 0) {
    redirect('customer/cart.php');
}

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart']) &&
    isset($_SESSION['cart'][$productId])
) {

    unset($_SESSION['cart'][$productId]);
}

// --------------------------------------------------
// Return To Cart
// --------------------------------------------------
redirect('customer/cart.php');
