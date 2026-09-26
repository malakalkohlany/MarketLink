<?php 

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$user_id = getUserId();
$user_name = getUserName();

$orders = [];

$stmt = $conn->prepare('SELECT
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
        LIMIT 5;');

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
    JOIN farmers f ON p.farmer_id = f.id
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

$stmt->bind_param("s", $approval_status);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <section class="welcome">

            <div>
                <h1>
                    Welcome,
                    <?= e($user_name) ?>!
                </h1>

                <p>
                    Discover fresh products from local farmers.
                </p>
            </div>

        </section>

        <section class="quick-actions">

            <a href="products.php" class="dashboard-action">
                <h3>Browse Products</h3>
                <p>Find fresh products from local farmers.</p>
            </a>

            <a href="farmers.php" class="dashboard-action">
                <h3>Find Farmers</h3>
                <p>Discover farmers and local stalls near you.</p>
            </a>

        </section>

        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Recent Orders</h2>
                <a href="orders.php">View all →</a>
            </div>

             <?php if (empty($orders)): ?>

                <div class="empty-state">

                    <h3>No orders yet</h3>

                    <p>
                        Browse products from local farmers and place your first order.
                    </p>

                    <a href="products.php">
                        Browse Products
                    </a>

                </div>

            <?php else: ?>

                <div class="orders-table-wrapper">

                    <table class="orders-table">

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
                                    <td>
                                        #<?= (int) $order['id'] ?>
                                    </td>

                                    <td>
                                        <?= e($order['stall_name']) ?>
                                    </td>

                                    <td>
                                        <?= formatDate($order['created_at']) ?>
                                    </td>

                                    <td>
                                        $<?= number_format($order['subtotal'], 2) ?>
                                    </td>

                                    <td>
                                        <?= e($order['market_name']) ?>
                                    </td>

                                    <td>
                                        <span class="order-status <?= e($order['status']) ?>">
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

        <section class="dashboard-section">
            
            <div class="section-heading">
                <h2>Featured Products</h2>
                <a href="products.php">View all →</a>
            </div>

            <?php if (empty($featured_products)): ?>

                <div class="dashboard-placeholder">
                    <p>No products are available yet.</p>
                </div>

            <?php else: ?>

                <div class="product-grid">

                    <?php foreach ($featured_products as $product): ?>

                        <a
                            href="product_details.php?id=<?= (int) $product['id'] ?>"
                            class="product-card"
                        >

                            <div class="product-image">

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="../uploads/products/<?= e($product['image']) ?>"
                                        alt="<?= e($product['name']) ?>"
                                    >

                                <?php else: ?>

                                    <span>No image</span>

                                <?php endif; ?>

                            </div>


                            <div class="product-info">

                                <h3>
                                    <?= e($product['name']) ?>
                                </h3>

                                <p class="product-farmer">
                                    <?= e($product['stall_name']) ?>
                                </p>

                                <div class="product-price">

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

        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Farmers Near You</h2>
                <a href="farmers.php">View all →</a>
            </div>

            <?php if (empty($farmers)): ?>

                <div class="dashboard-placeholder">
                    <p>No approved farmers are available yet.</p>
                </div>

            <?php else: ?>

                <div class="farmer-grid">

                    <?php foreach ($farmers as $farmer): ?>

                        <a
                            href="farmer_details.php?id=<?= (int) $farmer['id'] ?>"
                            class="farmer-card"
                        >

                            <div class="farmer-icon">
                                <?= strtoupper(
                                    substr($farmer['stall_name'], 0, 1)
                                ) ?>
                            </div>


                            <div class="farmer-info">

                                <h3>
                                    <?= e($farmer['stall_name']) ?>
                                </h3>

                                <?php if (!empty($farmer['address'])): ?>

                                    <p class="farmer-address">
                                        <?= e($farmer['address']) ?>
                                    </p>

                                <?php endif; ?>

                                <?php if (!empty($farmer['description'])): ?>

                                    <p class="farmer-description">
                                        <?= e(
                                            $farmer['description']
                                        ) ?>
                                    </p>

                                <?php endif; ?>

                            </div>

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