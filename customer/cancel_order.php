<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = getUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('customer/orders.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    redirect('customer/orders.php');
}

$orderId = isset($_POST['order_id'])
    ? (int) $_POST['order_id']
    : 0;

if ($orderId <= 0) {
    redirect('customer/orders.php');
}

/*
 * Only the customer who owns the order can cancel it,
 * and only while the order is still pending.
 */
$stmt = $conn->prepare("
    UPDATE orders
    SET status = 'cancelled',
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
      AND customer_id = ?
      AND status = 'pending'
    LIMIT 1
");

$stmt->bind_param(
    'ii',
    $orderId,
    $customerId
);

$stmt->execute();

if ($stmt->affected_rows === 1) {

    $stmt->close();

    redirect('customer/orders.php?cancelled=1');
}

$stmt->close();

redirect('customer/orders.php?cancel_error=1');