<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$orders = [];

$items_per_page = 10;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $items_per_page;

$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM orders
    INNER JOIN users
        ON orders.customer_id = users.id
    WHERE users.role = 'customer'
");

if ($count_stmt) {

    if ($count_stmt->execute()) {

        $count_result = $count_stmt->get_result();

        $total_orders = (int) $count_result
            ->fetch_assoc()['total_orders'];

    } else {

        $total_orders = 0;

        $errors[] = 'Failed to count orders.';
    }

    $count_stmt->close();

} else {

    $total_orders = 0;

    $errors[] = 'Failed to prepare count query.';
}

$total_pages = $total_orders > 0
    ? (int) ceil($total_orders / $items_per_page)
    : 0;

if ($total_pages > 0 && $page > $total_pages) {

    $page = $total_pages;

    $offset = ($page - 1) * $items_per_page;
}

$stmt = $conn->prepare("
    SELECT
        orders.id,
        orders.status,
        orders.subtotal,
        orders.created_at,
        users.name AS customer_name,
        users.email AS customer_email,
        users.phone AS customer_phone,
        farmers.stall_name AS farmer_name,
        markets.name AS market_name
    FROM orders
    INNER JOIN users
        ON orders.customer_id = users.id
    LEFT JOIN farmers
        ON orders.farmer_id = farmers.id
    LEFT JOIN markets
        ON orders.market_id = markets.id
    WHERE users.role = 'customer'
    ORDER BY orders.created_at DESC
    LIMIT ? OFFSET ?
");

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $items_per_page,
        $offset
    );

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        $orders = $result->fetch_all(MYSQLI_ASSOC);

    } else {

        $errors[] = 'Failed to load orders.';
    }

    $stmt->close();

} else {

    $errors[] = 'Failed to prepare order query.';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Orders | MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/navbar.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/customer.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/admin_orders.css"
    >

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content admin-orders-page">

        <section class="customer-page-hero admin-orders-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                    ADMIN / ORDERS
                </span>

                <h1>
                    Track customer <em>orders.</em>
                </h1>

                <p>
                    Review customer orders, connected farmers and markets,
                    order totals, statuses, and recent activity.
                </p>

            </div>

            <div class="customer-page-hero-mark">
                05
            </div>

        </section>

        <section class="admin-orders-section">

            <div class="customer-section-heading">

                <div>

                    <span class="customer-section-number">
                        01 / ORDER DIRECTORY
                    </span>

                    <h2>
                        Customer <em>orders.</em>
                    </h2>

                </div>

                <span class="customer-record-count">
                    <?= $total_orders ?> ORDERS
                </span>

            </div>

            <?php if (!empty($errors)): ?>

                <div class="admin-orders-alert">

                    <div class="admin-orders-alert-icon">

                        <i data-lucide="circle-alert"></i>

                    </div>

                    <div>

                        <?php foreach ($errors as $error): ?>

                            <p>
                                <?= htmlspecialchars($error) ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

            <div class="admin-orders-table-card">

                <div class="admin-orders-table-header">

                    <div>

                        <span class="admin-orders-table-kicker">
                            RECENT ACTIVITY
                        </span>

                        <h3>
                            All customer orders.
                        </h3>

                    </div>

                    <span class="admin-orders-table-count">
                        <?= $total_orders ?> TOTAL
                    </span>

                </div>

                <div class="admin-orders-table-wrap">

                    <table class="admin-orders-table">

                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Contact
                                </th>

                                <th>
                                    Farmer
                                </th>

                                <th>
                                    Market
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (!empty($orders)): ?>

                                <?php foreach ($orders as $order): ?>

                                    <?php

                                    $status = strtolower(
                                        trim(
                                            $order['status'] ?? 'pending'
                                        )
                                    );

                                    ?>

                                    <tr>

                                        <td>

                                            <span class="admin-orders-id">
                                                #<?= (int) $order['id'] ?>
                                            </span>

                                        </td>

                                        <td>

                                            <div class="admin-orders-customer">

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $order['customer_name']
                                                            ?? 'N/A'
                                                    ) ?>
                                                </strong>

                                                <span>
                                                    <?= htmlspecialchars(
                                                        $order['customer_email']
                                                            ?? 'N/A'
                                                    ) ?>
                                                </span>

                                            </div>

                                        </td>

                                        <td>

                                            <span class="admin-orders-phone">
                                                <?= htmlspecialchars(
                                                    $order['customer_phone']
                                                        ?? 'N/A'
                                                ) ?>
                                            </span>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['farmer_name']
                                                    ?? 'N/A'
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['market_name']
                                                    ?? 'N/A'
                                            ) ?>

                                        </td>

                                        <td>

                                            <strong class="admin-orders-total">
                                                $<?= number_format(
                                                    (float) (
                                                        $order['subtotal']
                                                            ?? 0
                                                    ),
                                                    2
                                                ) ?>
                                            </strong>

                                        </td>

                                        <td>

                                            <span
                                                class="admin-orders-status admin-orders-status-<?= htmlspecialchars(
                                                    $status
                                                ) ?>"
                                            >
                                                <?= htmlspecialchars(
                                                    ucfirst($status)
                                                ) ?>
                                            </span>

                                        </td>

                                        <td>

                                            <span class="admin-orders-date">

                                                <?= !empty($order['created_at'])
                                                    ? date(
                                                        'Y-m-d',
                                                        strtotime(
                                                            $order['created_at']
                                                        )
                                                    )
                                                    : 'N/A'
                                                ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="8"
                                        class="admin-orders-empty"
                                    >

                                        <div class="admin-orders-empty-mark">

                                            <i data-lucide="receipt"></i>

                                        </div>

                                        <strong>
                                            No orders found.
                                        </strong>

                                        <span>
                                            Customer orders will appear here once they are placed.
                                        </span>

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <?php if ($total_pages > 1): ?>

                <div class="admin-orders-pagination">

                    <?php if ($page > 1): ?>

                        <a
                            href="?page=<?= $page - 1 ?>"
                            aria-label="Previous page"
                        >

                            <i data-lucide="chevron-left"></i>

                        </a>

                    <?php endif; ?>

                    <?php for (
                        $i = 1;
                        $i <= $total_pages;
                        $i++
                    ): ?>

                        <a
                            href="?page=<?= $i ?>"
                            class="<?= $i === $page ? 'active' : '' ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>

                        <a
                            href="?page=<?= $page + 1 ?>"
                            aria-label="Next page"
                        >

                            <i data-lucide="chevron-right"></i>

                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

    <script src="../assets/js/lucide.js"></script>

    <script>
        lucide.createIcons();
    </script>

</body>

</html>