<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);


// ==========================================================
// Clear Customer Cart
// ==========================================================

unset($_SESSION['cart']);


// ==========================================================
// Redirect Back
// ==========================================================

$returnTo = $_POST['return_to'] ?? '../customer/products.php';


// ----------------------------------------------------------
// Only allow known internal destinations
// ----------------------------------------------------------

$allowedPages = [
    '../customer/products.php',
    '../customer/cart.php'
];

if (!in_array($returnTo, $allowedPages, true)) {
    $returnTo = '../customer/products.php';
}

redirect($returnTo);