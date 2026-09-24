<?php 

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../includes/session.php';

requireRole('customer');

$user_id = getUserId();

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


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="main-content">

        <section class="welcome">

            <div>
                <h1>
                    Welcome,
                    <?= htmlspecialchars($_SESSION['name']) ?>!
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
                                        <?= htmlspecialchars($order['stall_name']) ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            'M j, Y',
                                            strtotime($order['created_at'])
                                        ) ?>
                                    </td>

                                    <td>
                                        $<?= number_format($order['subtotal'], 2) ?>
                                    </td>

                                    <td>
                                        Pickup
                                    </td>

                                    <td>
                                        <span class="order-status <?= htmlspecialchars($order['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($order['status'])) ?>
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

            <div class="dashboard-grid">
                <div class="product-card"></div>
            </div>
        
        </section>

        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Farmers Near You</h2>
                <a href="farmers.php">View all →</a>
            </div>

            <div class="dashboard-grid">
                <div class="farmer-card"></div>
            </div>

        </section>

        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Recently Added</h2>
                <a href="products.php">View all →</a>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-grid"></div>
            </div>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/dashboard.js"></script>

</body>
</html>