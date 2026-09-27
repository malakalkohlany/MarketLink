<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$stats = [
    'customers' => 0,
    'farmers' => 0,
    'pending_farmers' => 0,
    'products' => 0,
    'pending_products' => 0,
    'orders' => 0
];

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");
$stmt->execute();
$stmt->bind_result($stats['customers']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM farmers
    WHERE approval_status = 'approved'
");
$stmt->execute();
$stmt->bind_result($stats['farmers']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM farmers
    WHERE approval_status = 'pending'
");
$stmt->execute();
$stmt->bind_result($stats['pending_farmers']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE moderation_status = 'approved'
");
$stmt->execute();
$stmt->bind_result($stats['products']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE moderation_status = 'pending'
");
$stmt->execute();
$stmt->bind_result($stats['pending_products']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM orders
");
$stmt->execute();
$stmt->bind_result($stats['orders']);
$stmt->fetch();
$stmt->close();

$recent_orders = [];

$stmt = $conn->prepare("
    SELECT
        o.id,
        u.name AS customer_name,
        f.stall_name,
        o.status,
        o.subtotal,
        o.created_at
    FROM orders o
    JOIN users u
        ON o.customer_id = u.id
    JOIN farmers f
        ON o.farmer_id = f.id
    ORDER BY o.created_at DESC
    LIMIT 5
");

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recent_orders[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="eyebrow">Admin / Overview</span>

                <h1>
                    Welcome back,
                    <em><?= e($_SESSION['name']) ?></em>.
                </h1>

                <p>
                    Here's what's happening across MarketLink.
                </p>
            </div>

            <span class="dashboard-mark">✦</span>
        </header>

        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Platform overview</h2>
            </div>

            <div class="admin-stats-grid">

                <div class="admin-stat-card sage">
                    <span class="admin-stat-label">
                        Customers
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['customers'] ?>
                    </strong>
                </div>

                <div class="admin-stat-card terracotta">
                    <span class="admin-stat-label">
                        Farmers
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['farmers'] ?>
                    </strong>
                </div>

                <div class="admin-stat-card marigold">
                    <span class="admin-stat-label">
                        Products
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['products'] ?>
                    </strong>
                </div>

                <div class="admin-stat-card dark">
                    <span class="admin-stat-label">
                        Orders
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['orders'] ?>
                    </strong>
                </div>

            </div>

        </section>

        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Needs your attention</h2>
            </div>

            <div class="attention-grid">

                <a href="farmers.php" class="attention-card terracotta">

                    <div class="attention-number">
                        <?= (int) $stats['pending_farmers'] ?>
                    </div>

                    <div class="attention-content">
                        <span class="attention-label">
                            Farmers awaiting review
                        </span>

                        <span class="attention-link">
                            Review farmers ↗
                        </span>
                    </div>

                </a>

                <a href="products.php" class="attention-card marigold">

                    <div class="attention-number">
                        <?= (int) $stats['pending_products'] ?>
                    </div>

                    <div class="attention-content">
                        <span class="attention-label">
                            Products awaiting review
                        </span>

                        <span class="attention-link">
                            Review products ↗
                        </span>
                    </div>

                </a>

            </div>

        </section>

        <section class="dashboard-section">

            <div class="section-heading">

                <div>
                    <span class="section-eyebrow">
                        Latest activity
                    </span>

                    <h2>Recent orders</h2>
                </div>

                <a href="orders.php">
                    View all ↗
                </a>

            </div>

            <?php if (empty($recent_orders)): ?>

                <div class="dashboard-placeholder">

                    <span class="empty-mark">✦</span>

                    <p>
                        No orders have been placed yet.
                    </p>

                </div>

            <?php else: ?>

                <div class="orders-table-wrapper">

                    <table class="orders-table">

                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Farmer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($recent_orders as $order): ?>

                                <tr>

                                    <td class="order-id">
                                        #<?= (int) $order['id'] ?>
                                    </td>

                                    <td>
                                        <?= e($order['customer_name']) ?>
                                    </td>

                                    <td>
                                        <?= e($order['stall_name']) ?>
                                    </td>

                                    <td>
                                        <?= formatDate($order['created_at']) ?>
                                    </td>

                                    <td class="order-total">
                                        $<?= formatPrice($order['subtotal']) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="order-status <?= e($order['status']) ?>"
                                        >
                                            <?= e(ucfirst($order['status'])) ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/dashboard.js"></script>

</body>
</html>