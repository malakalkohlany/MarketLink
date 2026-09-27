<?php 

require_once __DIR__ . '/../includes/include.php';

requireRole('farmer');
requireApprovedFarmer();

$user_id = getUserId();

$farmer_id = $_SESSION['farmer_id'] ?? null;

if (!$farmer_id) {
    redirect('auth/logout.php');
}

$farmer = null;
// Dashboard Counts
$product_count = 0;
$order_count = 0;

// Count products
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_products
    FROM products
    WHERE farmer_id = ?
");
$count_stmt->bind_param("i", $farmer_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$product_count = $count_result->fetch_assoc()['total_products'] ?? 0;
$count_stmt->close();

// Count orders
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM orders
    WHERE farmer_id = ?
");
$count_stmt->bind_param("i", $farmer_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$order_count = $count_result->fetch_assoc()['total_orders'] ?? 0;
$count_stmt->close();

$stmt = $conn->prepare("
    SELECT
        id,
        stall_name,
        description,
        address,
        approval_status
    FROM farmers
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $farmer_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$stmt->close();


$orders = [];

$stmt = $conn->prepare("
    SELECT
        o.id,
        u.name AS customer_name,
        m.name AS market_name,
        o.status,
        o.subtotal,
        o.created_at
    FROM orders o
    JOIN users u
        ON o.customer_id = u.id
    JOIN markets m
        ON o.market_id = m.id
    WHERE o.farmer_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");

$stmt->bind_param("i", $farmer_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();

$products = [];

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.unit,
        p.stock_quantity,
        p.image,
        p.is_available,
        p.moderation_status,
        p.created_at
    FROM products p
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC
    LIMIT 4
");

$stmt->bind_param("i", $farmer_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
                    <?= e($_SESSION['name'] ?? 'Farmer') ?>
                </h1>

                <p>
                    Manage your stall, products, and customer orders.
                </p>

            </div>

        </section>
     
        <section class="dashboard-stats">

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Products</span>
            <strong class="stat-value">
                <?= (int)$product_count ?>
            </strong>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Orders</span>
            <strong class="stat-value">
                <?= (int)$order_count ?>
            </strong>
        </div>
    </div>

</section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>Recent Orders</h2>

                <a href="orders.php">
                    View all →
                </a>

            </div>


            <?php if (empty($orders)): ?>

                <div class="empty-state">

                    <h3>No orders yet</h3>

                    <p>
                        Customer orders will appear here once they place an order.
                    </p>

                </div>

            <?php else: ?>

                <div class="orders-table-wrapper">

                    <table class="orders-table">

                        <thead>

                            <tr>
                                <th>Customer</th>
                                <th>Market</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Pickup</th>
                                <th>Status</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($orders as $order): ?>

                                <tr>


                                    <td>
                                        <?= e(
                                            $order['customer_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $order['market_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= formatDate($order['created_at']) ?>
                                    </td>

                                    <td>
                                        $<?= formatPrice($order['subtotal']) ?>
                                    </td>

                                    <td>
                                        Pickup
                                    </td>

                                    <td>

                                        <span
                                            class="order-status <?= e(
                                                $order['status']
                                            ) ?>"
                                        >
                                            <?= 
                                                e(ucfirst(
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

                <h2>My Products</h2>

                <a href="products.php">
                    View all →
                </a>

            </div>


            <?php if (empty($products)): ?>

                <div class="empty-state">

                    <h3>No products yet</h3>

                    <p>
                        Add your first product to start selling.
                    </p>

                    <a href="add_product.php">
                        Add Product
                    </a>

                </div>

            <?php else: ?>

                <div class="product-grid">

                    <?php foreach ($products as $product): ?>

                        <a
                            href="edit_product.php?id=<?= (int) $product['id'] ?>"
                            class="product-card"
                        >

                            <div class="product-image">

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="../uploads/products/<?= e(
                                            $product['image']
                                        ) ?>"
                                        alt="<?= e(
                                            $product['name']
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <span>
                                        No image
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="product-info">

                                <h3>
                                    <?= e(
                                        $product['name']
                                    ) ?>
                                </h3>


                                <div class="product-price">

                                    <strong>
                                        $<?= formatPrice($product['price']) ?>
                                    </strong>

                                    <span>
                                        / <?= e(
                                            $product['unit']
                                        ) ?>
                                    </span>

                                </div>


                                <p class="product-stock">

                                    Stock:
                                    <?= number_format(
                                        (float) $product['stock_quantity'],
                                        2
                                    ) ?>

                                </p>


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

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>My Stall</h2>

                <a href="edit_profile.php">
                    Edit →
                </a>

            </div>


            <?php if ($farmer): ?>

                <div class="stall-summary">

                    <div class="farmer-icon">
                        <?= strtoupper(
                            substr(
                                $farmer['stall_name'],
                                0,
                                1
                            )
                        ) ?>
                    </div>


                    <div class="farmer-info">

                        <h3>
                            <?= e(
                                $farmer['stall_name']
                            ) ?>
                        </h3>


                        <?php if (!empty($farmer['address'])): ?>

                            <p class="farmer-address">
                                <?= e(
                                    $farmer['address']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <?php if (!empty($farmer['description'])): ?>

                            <p class="farmer-description">
                                <?= e(
                                    $farmer['description']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <span
                            class="farmer-approval <?= e(
                                $farmer['approval_status']
                            ) ?>"
                        >
                            Stall:
                            <?= 
                                e(ucfirst(
                                    $farmer['approval_status']
                                )
                            ) ?>
                        </span>

                    </div>

                </div>

            <?php endif; ?>

        </section>

    </main>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/dashboard.js"></script>

</body>
</html>