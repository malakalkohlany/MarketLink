<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$productId = isset($_GET['product_id'])
    ? (int) $_GET['product_id']
    : 0;

if ($productId <= 0) {
    header('Location: cart.php');
    exit;
}

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart']) &&
    isset($_SESSION['cart'][$productId])
) {

    unset($_SESSION['cart'][$productId]);
}

header('Location: cart.php');
exit;