<?php

require_once __DIR__ .'/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid order ID.");
}

$order_id = (int) $_GET['id'];

$user_id = getUserId();

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

if (!$farmer) {
    die("Farmer account not found.");
}

$farmer_id = $farmer['id'];

$stmt->close();
$order_stmt = $conn->prepare("
    SELECT
        orders.id,
        orders.status,
        orders.subtotal,
        orders.notes,
        orders.created_at,
        users.name AS customer_name,
        users.email AS customer_email,
        users.phone AS customer_phone,
        users.address AS customer_address
    FROM orders
    INNER JOIN users
        ON orders.customer_id = users.id
    WHERE orders.id = ?
      AND orders.farmer_id = ?
    LIMIT 1
");

$order_stmt->bind_param("ii", $order_id, $farmer_id);
$order_stmt->execute();

$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

$order_stmt->close();
$item_stmt = $conn->prepare("
    SELECT
        order_items.id,
        order_items.quantity,
        order_items.unit_price,
        order_items.subtotal,
        products.name AS product_name,
        products.unit
    FROM order_items
    INNER JOIN products
        ON order_items.product_id = products.id
    WHERE order_items.order_id = ?
    ORDER BY order_items.id ASC
");

$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();

$items = $item_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>Order Details</h1>
        <h2>Order Information</h2>

        <p><strong>Order ID:</strong><?= e($order['id']) ?></p>
        <p><strong>Status:</strong><?= e($order['status']) ?></p>
        <p><strong>Subtotal:</strong><?= e($order['subtotal']) ?></p>
        <p><strong>Notes:</strong><?= e($order['notes'] ?? '') ?></p>
        <p><strong>Date:</strong><?= formatDateTime($order['created_at']) ?></p>

        <h2>Customer Information</h2>
        <p><strong>Name:</strong><?= e($order['customer_name']) ?></p>
        <p><strong>Email:</strong><?= e($order['customer_email']) ?></p>
        <p><strong>Phone:</strong><?= e($order['customer_phone']) ?></p>
        <p><strong>Address:</strong><?= e($order['customer_address']) ?></p>

            <h2>Order Items</h2>

        <table border="1">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($item = $items->fetch_assoc()): ?>

                    <tr>

                        <td><?= e($item['product_name']) ?></td>

                        <td><?= e($item['unit']) ?></td>

                        <td><?= e($item['quantity']) ?></td>

                        <td><?= formatPrice($item['unit_price']) ?></td>

                        <td><?= formatPrice($item['subtotal']) ?></td>

                    </tr>

                <?php endwhile; ?>

            </tbody>
        </table>

        <br>

        <a href="orders.php">Back to Orders</a>
    </main>

</body>
</html>

</body>
</html>