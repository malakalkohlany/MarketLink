<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

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
// Pagination
$items_per_page = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $items_per_page;
// Count total orders
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM orders
    WHERE farmer_id = ?
");

$count_stmt->bind_param("i", $farmer_id);
$count_stmt->execute();

$count_result = $count_stmt->get_result();
$total_orders = $count_result->fetch_assoc()['total_orders'];

$count_stmt->close();

$total_pages = ceil($total_orders / $items_per_page);

if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $items_per_page;
}

$order_stmt = $conn->prepare("
    SELECT
        orders.id,
        orders.status,
        orders.subtotal,
        orders.notes,
        orders.created_at,
        users.name AS customer_name
    FROM orders
    INNER JOIN users
        ON orders.customer_id = users.id
    WHERE orders.farmer_id = ?
    ORDER BY orders.created_at DESC
    LIMIT ? OFFSET ?
");

$order_stmt->bind_param("iii", $farmer_id, $items_per_page, $offset);
$order_stmt->execute();

$orders = $order_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>My Orders</h1>
        <table border="1">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Subtotal</th>
                    <th>Notes</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php while ($order = $orders->fetch_assoc()): ?>

                    <tr>

                        <td><?= e($order['id']) ?></td>

                        <td><?= e($order['customer_name']) ?></td>

                        <td><?= e($order['status']) ?></td>

                        <td><?= formatPrice($order['subtotal']) ?></td>

                        <td><?= e($order['notes'] ?? '') ?></td>

                        <td><?= formatDateTime($order['created_at']) ?></td>

                        <td>
                            <a href="order_details.php?id=<?= $order['id'] ?>">
                                View Details
                            </a>
                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>
        <?php if ($total_pages > 1): ?>

    <div class="pagination">

        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>"
               <?= $i == $page ? 'class="active"' : '' ?>>
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>">Next</a>
        <?php endif; ?>

    </div>

    <?php endif; ?>
    </main>
</body>
</html>