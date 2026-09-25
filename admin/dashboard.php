<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('admin');



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


$pending_farmers = [];

$stmt = $conn->prepare("
    SELECT
        f.id,
        f.stall_name,
        f.address,
        f.created_at,
        u.name AS owner_name,
        u.email
    FROM farmers f
    JOIN users u
        ON f.user_id = u.id
    WHERE f.approval_status = 'pending'
    ORDER BY f.created_at ASC
    LIMIT 5
");

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pending_farmers[] = $row;
}

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


$recent_products = [];

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.unit,
        p.moderation_status,
        p.created_at,
        f.stall_name
    FROM products p
    JOIN farmers f
        ON p.farmer_id = f.id
    ORDER BY p.created_at DESC
    LIMIT 5
");

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recent_products[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

</head>


<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">


        <section class="welcome">

            <div>

                <h1>
                    Welcome,
                    <?= e($_SESSION['name']) ?>!
                </h1>

                <p>
                    Manage MarketLink and keep the marketplace running smoothly.
                </p>

            </div>

        </section>


        <section class="quick-actions">

            <a href="farmers.php" class="dashboard-action">

                <h3>
                    Manage Farmers
                </h3>

                <p>
                    Review, approve, and manage farmer accounts.
                </p>

            </a>


            <a href="products.php" class="dashboard-action">

                <h3>
                    Manage Products
                </h3>

                <p>
                    Review products and moderation requests.
                </p>

            </a>


            <a href="orders.php" class="dashboard-action">

                <h3>
                    Manage Orders
                </h3>

                <p>
                    View and monitor marketplace orders.
                </p>

            </a>


            <a href="users.php" class="dashboard-action">

                <h3>
                    Manage Users
                </h3>

                <p>
                    View customer and farmer accounts.
                </p>

            </a>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>
                    Platform Overview
                </h2>

            </div>


            <div class="admin-stats-grid">


                <div class="admin-stat-card">

                    <span class="admin-stat-label">
                        Customers
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['customers'] ?>
                    </strong>

                </div>


                <div class="admin-stat-card">

                    <span class="admin-stat-label">
                        Farmers
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['farmers'] ?>
                    </strong>

                </div>


                <div class="admin-stat-card">

                    <span class="admin-stat-label">
                        Products
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['products'] ?>
                    </strong>

                </div>


                <div class="admin-stat-card">

                    <span class="admin-stat-label">
                        Orders
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['orders'] ?>
                    </strong>

                </div>


                <div class="admin-stat-card admin-stat-attention">

                    <span class="admin-stat-label">
                        Pending Farmers
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['pending_farmers'] ?>
                    </strong>

                </div>


                <div class="admin-stat-card admin-stat-attention">

                    <span class="admin-stat-label">
                        Pending Products
                    </span>

                    <strong class="admin-stat-value">
                        <?= (int) $stats['pending_products'] ?>
                    </strong>

                </div>


            </div>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>
                    Pending Farmers
                </h2>

                <a href="farmers.php">
                    View all →
                </a>

            </div>


            <?php if (empty($pending_farmers)): ?>

                <div class="empty-state">

                    <h3>
                        No pending farmers
                    </h3>

                    <p>
                        There are currently no farmer applications waiting for review.
                    </p>

                </div>

            <?php else: ?>

                <div class="orders-table-wrapper">

                    <table class="orders-table">

                        <thead>

                            <tr>

                                <th>
                                    Stall
                                </th>

                                <th>
                                    Owner
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Address
                                </th>

                                <th>
                                    Submitted
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($pending_farmers as $farmer): ?>

                                <tr>

                                    <td>
                                        <?= e(
                                            $farmer['stall_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $farmer['owner_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $farmer['email']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $farmer['address'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= 
                                        formatDate($farmer['created_at'])
                                         ?>
                                    </td>

                                    <td>

                                        <a
                                            href="farmer_details.php?id=<?= (int) $farmer['id'] ?>"
                                            class="table-action"
                                        >
                                            Review
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>
                    Recent Orders
                </h2>

                <a href="orders.php">
                    View all →
                </a>

            </div>


            <?php if (empty($recent_orders)): ?>

                <div class="dashboard-placeholder">

                    <p>
                        No orders have been placed yet.
                    </p>

                </div>

            <?php else: ?>

                <div class="orders-table-wrapper">

                    <table class="orders-table">

                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Farmer
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($recent_orders as $order): ?>

                                <tr>

                                    <td>
                                        #<?= (int) $order['id'] ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $order['customer_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $order['stall_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= 
                                        formatDate($order['created_at'])
                                         ?>
                                    </td>

                                    <td>
                                        $<?= 
                                         formatPrice($order['subtotal'])
                                           ?>
                                    </td>

                                    <td>

                                        <span
                                            class="order-status <?= e(
                                                $order['status']
                                            ) ?>"
                                        >
                                            <?= 
                                                e(
                                                    ucfirst(
                                                    $order['status']
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>
                    Recently Added Products
                </h2>

                <a href="products.php">
                    View all →
                </a>

            </div>


            <?php if (empty($recent_products)): ?>

                <div class="dashboard-placeholder">

                    <p>
                        No products have been added yet.
                    </p>

                </div>

            <?php else: ?>

                <div class="admin-products-table-wrapper">

                    <table class="orders-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Farmer
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Added
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($recent_products as $product): ?>

                                <tr>

                                    <td>
                                        <?= e(
                                            $product['name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $product['stall_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        $<?= 
                                        formatPrice($product['price']) 
                                        ?>

                                        /
                                        <?= e(
                                            $product['unit']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= formatDate($product['created_at'])
                                         ?>
                                    </td>

                                    <td>

                                        <span
                                            class="product-status <?= e(
                                                $product['moderation_status']
                                            ) ?>"
                                        >
                                            <?= 
                                                e(
                                                    ucfirst(
                                                    $product['moderation_status']
                                                )
                                            ) ?>
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