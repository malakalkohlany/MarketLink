<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('admin');

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_row();

$total_customers = (int) $row[0];


$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM farmers
");

$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_row();

$total_farmers = (int) $row[0];


$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM markets
");

$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_row();

$total_markets = (int) $row[0];


$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM products
");

$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_row();

$total_products = (int) $row[0];


$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM orders
");

$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_row();

$total_orders = (int) $row[0];


$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'completed'
");

$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_row();

$completed_orders = (int) $row[0];


$stmt = $conn->prepare("
    SELECT COALESCE(SUM(subtotal), 0)
    FROM orders
    WHERE status = 'completed'
");

$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_row();

$total_sales = (float) $row[0];
$stmt = $conn->prepare("
    SELECT
        o.id,
        o.status,
        o.subtotal,
        o.created_at,
        u.name AS customer_name,
        m.name AS market_name
    FROM orders o
    LEFT JOIN users u
        ON o.customer_id = u.id
    LEFT JOIN markets m
        ON o.market_id = m.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

$stmt->execute();

$result = $stmt->get_result();
$recent_orders = $result->fetch_all(MYSQLI_ASSOC);


$stmt = $conn->prepare("
    SELECT
        f.id,
        f.stall_name,
        COUNT(o.id) AS orders_count
    FROM farmers f
    LEFT JOIN orders o
        ON o.farmer_id = f.id
    GROUP BY
        f.id,
        f.stall_name
    ORDER BY orders_count DESC
    LIMIT 10
");

$stmt->execute();

$result = $stmt->get_result();
$top_farmers = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reports | MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<div class="admin-container">

    <aside class="sidebar">

        <div class="logo">
            MarketLink
        </div>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="markets.php">
                Markets
            </a>

            <a href="add_market.php">
                Add Market
            </a>

            <a href="categories.php">
                Produce Categories
            </a>

            <a href="farmers.php">
                Farmers
            </a>

            <a href="products.php">
                Produce
            </a>

            <a href="users.php">
                Users
            </a>

            <a href="orders.php">
                Orders
            </a>

            <a href="reviews.php">
                Reviews
            </a>

            <a href="announcements.php">
                Announcements
            </a>

            <a href="notifications.php">
                Notifications
            </a>

            <a href="reports.php" class="active">
                Reports
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </nav>

    </aside>
    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>
                    Reports
                </h1>

                <p>
                    View system statistics and reports.
                </p>

            </div>

        </div>


        <section class="details-grid">


            <div class="form-group">

                <label>
                    Total Customers
                </label>

                <p>
                    <?= $total_customers ?>
                </p>

            </div>


            <div class="form-group">

                <label>
                    Total Farmers
                </label>

                <p>
                    <?= $total_farmers ?>
                </p>

            </div>


            <div class="form-group">

                <label>
                    Total Markets
                </label>

                <p>
                    <?= $total_markets ?>
                </p>

            </div>


            <div class="form-group">

                <label>
                    Total Products
                </label>

                <p>
                    <?= $total_products ?>
                </p>

            </div>


            <div class="form-group">

                <label>
                    Total Orders
                </label>

                <p>
                    <?= $total_orders ?>
                </p>

            </div>
            <div class="form-group">

                <label>
                    Completed Orders
                </label>

                <p>
                    <?= $completed_orders ?>
                </p>

            </div>


            <div class="form-group">

                <label>
                    Total Sales
                </label>

                <p>
                    <?= number_format(
                        $total_sales,
                        2
                    ) ?>
                </p>

            </div>


        </section>


        <section class="table-section">

            <div class="section-header">

                <h2>
                    Recent Orders
                </h2>

            </div>


            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                Order ID
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Market
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Subtotal
                            </th>

                            <th>
                                Date
                            </th>

                        </tr>

                    </thead>
                    <tbody>

                        <?php if (!empty($recent_orders)): ?>

                            <?php foreach ($recent_orders as $order): ?>

                                <tr>

                                    <td>
                                        #<?= (int)$order['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $order['customer_name'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $order['market_name'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $order['status'] ?? ''
                                            ) ?>"
                                        >
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $order['status'] ?? 'N/A'
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float)$order['subtotal'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if (!empty($order['created_at'])): ?>

                                            <?= date(
                                                'Y-m-d H:i',
                                                strtotime(
                                                    $order['created_at']
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            N/A

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6">
                                    No orders found.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <section class="table-section">

            <div class="section-header">

                <h2>
                    Farmers by Orders
                </h2>

            </div>


            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Farmer
                            </th>

                            <th>
                                Orders
                            </th>

                        </tr>

                    </thead>
                     <tbody>

                        <?php if (!empty($top_farmers)): ?>

                            <?php foreach ($top_farmers as $farmer): ?>

                                <tr>

                                    <td>
                                        <?= (int)$farmer['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $farmer['stall_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= (int)$farmer['orders_count'] ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="3">
                                    No farmers found.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>

</html>
