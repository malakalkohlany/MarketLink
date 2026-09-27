<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$user_id = getUserId();
$user_name = getUserName();

$orders = [];

$stmt = $conn->prepare("
    SELECT
        orders.id,
        farmers.stall_name,
        markets.name AS market_name,
        orders.status,
        orders.subtotal,
        orders.created_at
    FROM orders
    JOIN farmers
        ON orders.farmer_id = farmers.id
    JOIN markets
        ON orders.market_id = markets.id
    WHERE orders.customer_id = ?
    ORDER BY orders.created_at DESC
    LIMIT 5
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();

$featured_products = [];

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.unit,
        p.image,
        f.id AS farmer_id,
        f.stall_name
    FROM products p
    JOIN farmers f
        ON p.farmer_id = f.id
    WHERE p.is_available = 1
      AND p.moderation_status = ?
      AND f.approval_status = ?
    ORDER BY p.created_at DESC
    LIMIT 4
");

$moderation_status = M_APPROVED;
$approval_status = A_APPROVED;

$stmt->bind_param(
    "ss",
    $moderation_status,
    $approval_status
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $featured_products[] = $row;
}

$stmt->close();

$farmers = [];

$stmt = $conn->prepare("
    SELECT
        id,
        stall_name,
        description,
        address
    FROM farmers
    WHERE approval_status = ?
    ORDER BY created_at DESC
    LIMIT 4
");

$approval_status = A_APPROVED;

$stmt->bind_param(
    "s",
    $approval_status
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $farmers[] = $row;
}

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

    <title>Dashboard | MarketLink</title>

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
        href="../assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/sidebar.css"
    >
</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content customer-dashboard">

        <section class="customer-hero">

            <div class="customer-hero-copy">

                <span class="eyebrow">
                    MARKETPLACE / DASHBOARD
                </span>

                <h1>
                    Welcome back,
                    <em><?= e($user_name) ?>.</em>
                </h1>

                <p>
                    Discover fresh products, local farmers,
                    and markets in your community.
                </p>

            </div>

            <div class="customer-hero-mark">
                <span>✦</span>
            </div>

        </section>

        <section class="customer-actions">

            <div class="customer-section-intro">

                <span class="eyebrow">
                    01 / Explore
                </span>

                <h2>
                    Start with what's
                    <em>fresh.</em>
                </h2>

            </div>

            <div class="customer-action-grid">

                <a
                    href="products.php"
                    class="customer-action-card action-products"
                >
                    <span class="action-number">01</span>

                    <div class="action-content">
                        <h3>Browse Products</h3>

                        <p>
                            Find fresh products from local farmers.
                        </p>
                    </div>

                    <span class="action-arrow">↗</span>
                </a>

                <a
                    href="farmers.php"
                    class="customer-action-card action-farmers"
                >
                    <span class="action-number">02</span>

                    <div class="action-content">
                        <h3>Find Farmers</h3>

                        <p>
                            Discover farmers and local stalls near you.
                        </p>
                    </div>

                    <span class="action-arrow">↗</span>
                </a>

            </div>

        </section>

        <section class="customer-section customer-orders-section">

            <div class="customer-section-heading">

                <div>
                    <span class="eyebrow">
                        02 / Your activity
                    </span>

                    <h2>
                        Recent <em>orders.</em>
                    </h2>
                </div>

                <a
                    href="orders.php"
                    class="section-link"
                >
                    View all →
                </a>

            </div>

            <?php if (empty($orders)): ?>

                <div class="customer-empty-state">

                    <span class="empty-mark">✦</span>

                    <div>
                        <h3>No orders yet</h3>

                        <p>
                            Browse products from local farmers
                            and place your first order.
                        </p>

                        <a
                            href="products.php"
                            class="btn btn-primary btn-sm"
                        >
                            Browse Products
                        </a>
                    </div>

                </div>

            <?php else: ?>

                <div class="customer-orders-table">

                    <table class="dashboard-table">

                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Farmer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Pickup</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($orders as $order): ?>

                                <tr>

                                    <td class="table-primary">
                                        #<?= (int) $order['id'] ?>
                                    </td>

                                    <td>
                                        <?= e($order['stall_name']) ?>
                                    </td>

                                    <td class="table-secondary">
                                        <?= formatDate($order['created_at']) ?>
                                    </td>

                                    <td class="table-total">
                                        $<?= number_format($order['subtotal'], 2) ?>
                                    </td>

                                    <td class="table-muted">
                                        <?= e($order['market_name']) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="table-status <?= e($order['status']) ?>"
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

        <section class="customer-section">

            <div class="customer-section-heading">

                <div>
                    <span class="eyebrow">
                        03 / Fresh from the market
                    </span>

                    <h2>
                        Featured <em>products.</em>
                    </h2>
                </div>

                <a
                    href="products.php"
                    class="section-link"
                >
                    View all →
                </a>

            </div>

            <?php if (empty($featured_products)): ?>

                <div class="customer-placeholder">
                    <p>
                        No products are available yet.
                    </p>
                </div>

            <?php else: ?>

                <div class="customer-product-grid">

                    <?php foreach ($featured_products as $product): ?>

                        <a
                            href="product_details.php?id=<?= (int) $product['id'] ?>&from=dashboard"
                            class="customer-product-card"
                        >

                            <div class="customer-product-image">

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="../uploads/products/<?= e($product['image']) ?>"
                                        alt="<?= e($product['name']) ?>"
                                    >

                                <?php else: ?>

                                    <span>No image</span>

                                <?php endif; ?>

                            </div>

                            <div class="customer-product-info">

                                <div class="product-card-topline">
                                    <span>LOCAL</span>
                                    <span>↗</span>
                                </div>

                                <h3>
                                    <?= e($product['name']) ?>
                                </h3>

                                <p class="customer-product-farmer">
                                    <?= e($product['stall_name']) ?>
                                </p>

                                <div class="customer-product-price">

                                    <strong>
                                        $<?= formatPrice($product['price']) ?>
                                    </strong>

                                    <span>
                                        / <?= e($product['unit']) ?>
                                    </span>

                                </div>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <section class="customer-section customer-farmers-section">

            <div class="customer-section-heading">

                <div>
                    <span class="eyebrow">
                        04 / Meet the growers
                    </span>

                    <h2>
                        Farmers <em>near you.</em>
                    </h2>
                </div>

                <a
                    href="farmers.php"
                    class="section-link"
                >
                    View all →
                </a>

            </div>

            <?php if (empty($farmers)): ?>

                <div class="customer-placeholder">
                    <p>
                        No approved farmers are available yet.
                    </p>
                </div>

            <?php else: ?>

                <div class="customer-farmer-grid">

                    <?php foreach ($farmers as $farmer): ?>

                        <a
                            href="farmer_details.php?id=<?= (int) $farmer['id'] ?>"
                            class="customer-farmer-card"
                        >

                            <div class="customer-farmer-icon">
                                <?= strtoupper(
                                    substr(
                                        $farmer['stall_name'],
                                        0,
                                        1
                                    )
                                ) ?>
                            </div>

                            <div class="customer-farmer-info">

                                <div class="farmer-card-label">
                                    LOCAL STALL
                                </div>

                                <h3>
                                    <?= e($farmer['stall_name']) ?>
                                </h3>

                                <?php if (!empty($farmer['address'])): ?>

                                    <p class="customer-farmer-address">
                                        <?= e($farmer['address']) ?>
                                    </p>

                                <?php endif; ?>

                                <?php if (!empty($farmer['description'])): ?>

                                    <p class="customer-farmer-description">
                                        <?= e($farmer['description']) ?>
                                    </p>

                                <?php endif; ?>

                            </div>

                            <span class="customer-farmer-arrow">
                                ↗
                            </span>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/dashboard.js"></script>

</body>
</html>