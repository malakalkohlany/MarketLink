<?php

require_once '../includes/include.php';

/*Customers*/

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$total_customers = (int) $stmt->fetchColumn();

/*Farmers*/

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM farmers
");

$total_farmers = (int) $stmt->fetchColumn();

/*Markets*/

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM markets
");

$total_markets = (int) $stmt->fetchColumn();

/*Products */

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM products
");

$total_products = (int) $stmt->fetchColumn();

/*Orders */

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM orders
");

$total_orders = (int) $stmt->fetchColumn();

/* Completed Orders*/

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'completed'
");

$completed_orders = (int) $stmt->fetchColumn();

/*Total Sales */

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(subtotal), 0)
    FROM orders
    WHERE status = 'completed'
");

$total_sales = (float) $stmt->fetchColumn();

/*Recent Orders */

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

$recent_orders = $stmt->fetchAll();

/*Top Farmers */

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

$top_farmers = $stmt->fetchAll();

?>

<div class="page-header">

    <h1>
        Reports
    </h1>

    <p>
        View system statistics and reports.
    </p>

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
                            #<?= $order['id'] ?>
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
                            <?= htmlspecialchars(
                                $order['status']
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                $order['subtotal'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?= date(
                                'Y-m-d H:i',
                                strtotime(
                                    $order['created_at']
                                )
                            ) ?>
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

</section>


<section class="table-section">

    <div class="section-header">

        <h2>
            Farmers by Orders
        </h2>

    </div>
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
                            <?= $farmer['id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $farmer['stall_name']
                            ) ?>
                        </td>

                        <td>
                            <?= $farmer['orders_count'] ?>
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

</section>
