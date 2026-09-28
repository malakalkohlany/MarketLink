<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = getUserId();

$ordersPerPage = 5;

$currentPage = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($currentPage < 1) {
    $currentPage = 1;
}

$countStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE customer_id = ?
");

$countStmt->bind_param("i", $customerId);
$countStmt->execute();

$countResult = $countStmt->get_result();
$totalOrdersRow = $countResult->fetch_assoc();

$totalOrders = (int) $totalOrdersRow['total'];

$countStmt->close();

$totalPages = max(
    1,
    (int) ceil($totalOrders / $ordersPerPage)
);

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = (
    $currentPage - 1
) * $ordersPerPage;

$stmt = $conn->prepare("
    SELECT
        o.id,
        o.status,
        o.subtotal,
        o.notes,
        o.created_at,
        o.updated_at
    FROM orders o
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");

$stmt->bind_param(
    "iii",
    $customerId,
    $ordersPerPage,
    $offset
);

$stmt->execute();

$ordersResult = $stmt->get_result();
$orders = [];

while ($order = $ordersResult->fetch_assoc()) {
    $orders[] = $order;
}

$stmt->close();

foreach ($orders as &$order) {
    $itemStmt = $conn->prepare("
        SELECT
            oi.product_id,
            oi.quantity,
            oi.unit_price,
            oi.subtotal,
            p.name AS product_name,
            p.image AS product_image,
            p.unit AS product_unit
        FROM order_items oi
        LEFT JOIN products p
            ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");

    $itemStmt->bind_param("i", $order['id']);
    $itemStmt->execute();

    $itemsResult = $itemStmt->get_result();
    $order['items'] = [];

    while ($item = $itemsResult->fetch_assoc()) {
        $order['items'][] = $item;
    }

    $itemStmt->close();
}

unset($order);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>My Orders - MarketLink</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/customer_n.css">

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content customer-orders-page">
    
    <section class="customer-page-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                    CUSTOMER / ORDER HISTORY
                </span>

                <h1>
                    Your order <em>history.</em>
                </h1>

            </div>

            <div class="customer-page-hero-mark">
                07
            </div>

        </section>

    <section class="customer-orders-section">
        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    01 / HISTORY
                </span>
                <h1>
                    Your <em>orders.</em>
                </h1>
            </div>

            <span class="customer-record-count">
                <?= $totalOrders ?>
                order<?= $totalOrders != 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if (empty($orders) && $totalOrders === 0): ?>

            <div class="customer-orders-empty">
                <span class="customer-orders-empty-mark">
                    <i data-lucide="receipt"></i>
                </span>

                <strong>
                    No orders yet.
                </strong>

                <span>
                    You haven't placed any orders yet. Browse the marketplace
                    to find fresh produce from local farmers.
                </span>

                <a
                    href="products.php"
                    class="customer-orders-browse"
                >
                    Browse Products
                    <i data-lucide=" arrow-right"></i>
                </a>
            </div>

        <?php else: ?>

            <div class="customer-orders-list">

                <?php foreach ($orders as $order): ?>

                    <?php
                    $status = strtolower($order['status']);
                    $statusClass = 'status-' . $status;
                    ?>

                    <article class="customer-order-card">

                        <div class="customer-order-header">

                            <div class="customer-order-heading">

                                <span class="customer-order-label">
                                    ORDER #<?= (int)$order['id'] ?>
                                </span>

                                <span class="customer-order-date">
                                    <?= date(
                                        'M d, Y · h:i A',
                                        strtotime($order['created_at'])
                                    ) ?>
                                </span>

                            </div>

                            <span class="customer-order-status <?= htmlspecialchars($statusClass) ?>">
                                <?= htmlspecialchars(ucfirst($status)) ?>
                            </span>

                        </div>

                        <div class="customer-order-items-heading">
                            <span>
                                Order items
                            </span>

                            <span>
                                <?= count($order['items']) ?>
                                item<?= count($order['items']) !== 1 ? 's' : '' ?>
                            </span>
                        </div>

                        <?php if (!empty($order['items'])): ?>

                            <div class="customer-order-items">

                                <?php foreach ($order['items'] as $item): ?>

                                    <div class="customer-order-item">

                                        <?php if (!empty($item['product_image'])): ?>

                                            <img
                                                src="../uploads/products/<?= htmlspecialchars($item['product_image']) ?>"
                                                alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>"
                                                class="customer-order-item-image"
                                            >

                                        <?php else: ?>

                                            <div class="customer-order-item-image customer-order-item-placeholder">
                                                <i data-lucide="leaf"></i>
                                            </div>

                                        <?php endif; ?>

                                        <div class="customer-order-item-info">

                                            <strong class="customer-order-item-name">
                                                <?= htmlspecialchars(
                                                    $item['product_name'] ?? 'Product'
                                                ) ?>
                                            </strong>

                                            <span class="customer-order-item-meta">
                                                <?= number_format(
                                                    (float)$item['quantity'],
                                                    2
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $item['product_unit'] ?? ''
                                                ) ?>

                                                ×

                                                $<?= number_format(
                                                    (float)$item['unit_price'],
                                                    2
                                                ) ?>
                                            </span>

                                        </div>

                                        <span class="customer-order-item-price">
                                            $<?= number_format(
                                                (float)$item['subtotal'],
                                                2
                                            ) ?>
                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="customer-order-no-items">
                                No items found for this order.
                            </div>

                        <?php endif; ?>

                        <?php if (!empty($order['notes'])): ?>

                            <div class="customer-order-notes">

                                <span class="customer-order-notes-label">
                                    Note
                                </span>

                                <span>
                                    <?= nl2br(
                                        htmlspecialchars($order['notes'])
                                    ) ?>
                                </span>

                            </div>

                        <?php endif; ?>

                        <div class="customer-order-footer">

                            <div class="customer-order-actions">

                                <?php if ($status === 'pending'): ?>

                                    <a
                                        href="<?= BASE_URL ?>customer/update_order.php?id=<?= (int)$order['id'] ?>"
                                        class="customer-order-action customer-order-edit"
                                    >
                                        Modify Order
                                        <i data-lucide=" arrow-right"></i>
                                    </a>

                                    <form
                                        method="POST"
                                        action="cancel_order.php"
                                        class="customer-order-cancel-form"
                                        onsubmit="return confirm('Are you sure you want to cancel this order?');"
                                    >

                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= (int)$order['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="customer-order-action customer-order-cancel"
                                        >
                                            Cancel Order
                                        </button>

                                    </form>

                                <?php elseif ($status === 'completed'): ?>

                                    <a
                                        href="<?= BASE_URL ?>customer/reorder.php?id=<?= (int)$order['id'] ?>"
                                        class="customer-order-action customer-order-reorder"
                                    >
                                        Reorder
                                        <i data-lucide="rotate-cw"></i>
                                    </a>

                                <?php endif; ?>

                            </div>

                            <div class="customer-order-total">

                                <span>
                                    Order total
                                </span>

                                <strong>
                                    $<?= number_format(
                                        (float)$order['subtotal'],
                                        2
                                    ) ?>
                                </strong>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <?php if ($totalPages > 1): ?>

                <div class="customer-orders-pagination">

                    <?php if ($currentPage > 1): ?>

                        <a
                            href="?page=<?= $currentPage - 1 ?>"
                            class="customer-orders-pagination-button"
                        >
                            <i data-lucide=" arrow-left"></i>
                            Previous
                        </a>

                    <?php else: ?>

                        <span class="customer-orders-pagination-button is-disabled">
                            <i data-lucide=" arrow-left"></i>
                            Previous
                        </span>

                    <?php endif; ?>

                    <div class="customer-orders-pagination-pages">

                        <?php for (
                            $page = 1;
                            $page <= $totalPages;
                            $page++
                        ): ?>

                            <?php if ($page === $currentPage): ?>

                                <span class="customer-orders-pagination-page is-active">
                                    <?= $page ?>
                                </span>

                            <?php else: ?>

                                <a
                                    href="?page=<?= $page ?>"
                                    class="customer-orders-pagination-page"
                                >
                                    <?= $page ?>
                                </a>

                            <?php endif; ?>

                        <?php endfor; ?>

                    </div>

                    <?php if ($currentPage < $totalPages): ?>

                        <a
                            href="?page=<?= $currentPage + 1 ?>"
                            class="customer-orders-pagination-button"
                        >
                            Next
                            <i data-lucide=" arrow-right"></i>
                        </a>

                    <?php else: ?>

                        <span class="customer-orders-pagination-button is-disabled">
                            Next
                            <i data-lucide=" arrow-right"></i>
                        </span>

                    <?php endif; ?>

                </div>

                <div class="customer-orders-pagination-info">
                    Page <?= $currentPage ?>
                    of <?= $totalPages ?>
                    ·
                    <?= $totalOrders ?>
                    total orders
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </section>
</main>

<script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>
