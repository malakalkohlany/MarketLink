<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_customers = (int) $row[0];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM farmers
");

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_farmers = (int) $row[0];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM markets
");

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_markets = (int) $row[0];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM products
");

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_products = (int) $row[0];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM orders
");

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_orders = (int) $row[0];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'completed'
");

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$completed_orders = (int) $row[0];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(subtotal), 0)
    FROM orders
    WHERE status = 'completed'
");

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_sales = (float) $row[0];
$stmt->close();

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
$stmt->close();

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
$stmt->close();

$stmt = $conn->prepare("
    SELECT
        m.id,
        m.name AS market_name,
        COALESCE(SUM(o.subtotal), 0) AS revenue
    FROM markets m
    LEFT JOIN orders o
        ON o.market_id = m.id
        AND o.status = 'completed'
    GROUP BY
        m.id,
        m.name
    ORDER BY revenue DESC
");

$stmt->execute();
$result = $stmt->get_result();
$market_revenue = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/admin_ann.css">
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-reports-page">

    <section class="customer-page-hero admin-customers-hero">
        <div class="customer-page-hero-copy">
            <span class="eyebrow">
                ADMIN / REPORTS
            </span>

            <h1>
                MarketLink <em>overview.</em>
            </h1>

            <p>
                View system activity, order performance,
                farmer activity, and market revenue.
            </p>
        </div>

        <div class="customer-page-hero-mark">
            10
        </div>
    </section>

    <section class="admin-report-overview-section">

        <div class="admin-section-heading">
            <div>
                <span class="admin-section-number">
                    01 / OVERVIEW
                </span>

                <h2>
                    System <em>statistics.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                Live totals
            </span>
        </div>

        <div class="admin-report-stat-grid">

            <div class="admin-report-stat-card admin-report-stat-customers">
                <span class="admin-report-stat-label">
                    Total customers
                </span>

                <strong>
                    <?= $total_customers ?>
                </strong>

                <span class="admin-report-stat-note">
                    Registered accounts
                </span>
            </div>

            <div class="admin-report-stat-card admin-report-stat-farmers">
                <span class="admin-report-stat-label">
                    Total farmers
                </span>

                <strong>
                    <?= $total_farmers ?>
                </strong>

                <span class="admin-report-stat-note">
                    Local farmer stalls
                </span>
            </div>

            <div class="admin-report-stat-card admin-report-stat-markets">
                <span class="admin-report-stat-label">
                    Total markets
                </span>

                <strong>
                    <?= $total_markets ?>
                </strong>

                <span class="admin-report-stat-note">
                    Market locations
                </span>
            </div>

            <div class="admin-report-stat-card admin-report-stat-products">
                <span class="admin-report-stat-label">
                    Total products
                </span>

                <strong>
                    <?= $total_products ?>
                </strong>

                <span class="admin-report-stat-note">
                    Listed produce
                </span>
            </div>

            <div class="admin-report-stat-card admin-report-stat-orders">
                <span class="admin-report-stat-label">
                    Total orders
                </span>

                <strong>
                    <?= $total_orders ?>
                </strong>

                <span class="admin-report-stat-note">
                    All submitted orders
                </span>
            </div>

            <div class="admin-report-stat-card admin-report-stat-completed">
                <span class="admin-report-stat-label">
                    Completed orders
                </span>

                <strong>
                    <?= $completed_orders ?>
                </strong>

                <span class="admin-report-stat-note">
                    Successfully completed
                </span>
            </div>

            <div class="admin-report-stat-card admin-report-stat-sales">
                <span class="admin-report-stat-label">
                    Total sales
                </span>

                <strong>
                    <?= number_format($total_sales, 2) ?>
                </strong>

                <span class="admin-report-stat-note">
                    From completed orders
                </span>
            </div>

        </div>

    </section>

    <section class="admin-report-section">

        <div class="admin-section-heading">
            <div>
                <span class="admin-section-number">
                    02 / RECENT ORDERS
                </span>

                <h2>
                    Recent <em>orders.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                Latest 10
            </span>
        </div>

        <div class="admin-reports-table">

            <table>

                <thead>
                    <tr>
                        <th class="admin-report-order-id">
                            Order
                        </th>

                        <th class="admin-report-order-customer">
                            Customer
                        </th>

                        <th class="admin-report-order-market">
                            Market
                        </th>

                        <th class="admin-report-order-status">
                            Status
                        </th>

                        <th class="admin-report-order-total">
                            Subtotal
                        </th>

                        <th class="admin-report-order-date">
                            Date
                        </th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($recent_orders)): ?>

                    <?php foreach ($recent_orders as $order): ?>

                        <?php
                        $status = $order['status'] ?? 'unknown';

                        $statusClass = match ($status) {
                            'completed' => 'admin-status-active',
                            'pending' => 'admin-status-pending',
                            'processing' => 'admin-status-processing',
                            'cancelled' => 'admin-status-rejected',
                            'rejected' => 'admin-status-rejected',
                            default => 'admin-status-default'
                        };
                        ?>

                        <tr>

                            <td>
                                <span class="admin-report-primary">
                                    #<?= (int) $order['id'] ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-report-primary">
                                    <?= htmlspecialchars(
                                        $order['customer_name'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-report-secondary">
                                    <?= htmlspecialchars(
                                        $order['market_name'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-status <?= $statusClass ?>">
                                    <span class="admin-status-dot"></span>
                                    <?= htmlspecialchars(
                                        ucfirst($status),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-report-total">
                                    <?= number_format(
                                        (float) $order['subtotal'],
                                        2
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($order['created_at'])): ?>

                                    <div class="admin-report-date-wrap">
                                        <span class="admin-report-date">
                                            <?= date(
                                                'Y-m-d',
                                                strtotime($order['created_at'])
                                            ) ?>
                                        </span>

                                        <span class="admin-report-time">
                                            <?= date(
                                                'H:i',
                                                strtotime($order['created_at'])
                                            ) ?>
                                        </span>
                                    </div>

                                <?php else: ?>

                                    <span class="admin-table-muted">
                                        N/A
                                    </span>

                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td
                            colspan="6"
                            class="admin-table-empty"
                        >
                            <div class="admin-empty-state">

                                <span class="admin-empty-mark">
                                    ✦
                                </span>

                                <strong>
                                    No orders found.
                                </strong>

                                <span>
                                    Order activity will appear here
                                    once customers place orders.
                                </span>

                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <section class="admin-report-section">

        <div class="admin-section-heading">
            <div>
                <span class="admin-section-number">
                    03 / FARMER ACTIVITY
                </span>

                <h2>
                    Farmers by <em>orders.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                Top 10
            </span>
        </div>

        <div class="admin-reports-table">

            <table>

                <thead>
                    <tr>
                        <th class="admin-report-ranking-id">
                            ID
                        </th>

                        <th class="admin-report-ranking-name">
                            Farmer
                        </th>

                        <th class="admin-report-ranking-orders">
                            Orders
                        </th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($top_farmers)): ?>

                    <?php foreach ($top_farmers as $index => $farmer): ?>

                        <tr>

                            <td>
                                <span class="admin-report-rank">
                                    <?= $index + 1 ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-report-primary">
                                    <?= htmlspecialchars(
                                        $farmer['stall_name'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-report-total">
                                    <?= (int) $farmer['orders_count'] ?>
                                </span>

                                <span class="admin-report-inline-label">
                                    <?= (int) $farmer['orders_count'] === 1
                                        ? 'order'
                                        : 'orders' ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td
                            colspan="3"
                            class="admin-table-empty"
                        >
                            <div class="admin-empty-state">

                                <span class="admin-empty-mark">
                                    ✦
                                </span>

                                <strong>
                                    No farmers found.
                                </strong>

                                <span>
                                    Farmer activity will appear here
                                    once orders are placed.
                                </span>

                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <section class="admin-report-section">

        <div class="admin-section-heading">
            <div>
                <span class="admin-section-number">
                    04 / MARKET REVENUE
                </span>

                <h2>
                    Revenue by <em>market.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                All markets
            </span>
        </div>

        <div class="admin-reports-table">

            <table>

                <thead>
                    <tr>
                        <th class="admin-report-ranking-id">
                            ID
                        </th>

                        <th class="admin-report-ranking-name">
                            Market
                        </th>

                        <th class="admin-report-ranking-revenue">
                            Revenue
                        </th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($market_revenue)): ?>

                    <?php foreach ($market_revenue as $index => $market): ?>

                        <tr>

                            <td>
                                <span class="admin-report-rank">
                                    <?= $index + 1 ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-report-primary">
                                    <?= htmlspecialchars(
                                        $market['market_name'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-report-total">
                                    <?= number_format(
                                        (float) $market['revenue'],
                                        2
                                    ) ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td
                            colspan="3"
                            class="admin-table-empty"
                        >
                            <div class="admin-empty-state">

                                <span class="admin-empty-mark">
                                    ✦
                                </span>

                                <strong>
                                    No market revenue found.
                                </strong>

                                <span>
                                    Completed order revenue will appear
                                    here by market.
                                </span>

                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>