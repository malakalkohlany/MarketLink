<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$user_id = getUserId();

$stmt = $conn->prepare("
    SELECT id
    FROM farmers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

if (!$farmer) {
    die("Farmer account not found.");
}

$farmer_id = $farmer['id'];

$stmt->close();
$product_stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_products,
        SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) AS available_products,
        SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) AS approved_products,
        SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) AS pending_products
    FROM products
    WHERE farmer_id = ?
");

$product_stmt->bind_param("i", $farmer_id);
$product_stmt->execute();

$product_stats = $product_stmt->get_result()->fetch_assoc();

$product_stmt->close();
$order_stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_orders,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) AS preparing_orders,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) AS ready_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN subtotal ELSE 0 END), 0) AS total_revenue
    FROM orders
    WHERE farmer_id = ?
");

$order_stmt->bind_param("i", $farmer_id);
$order_stmt->execute();

$order_stats = $order_stmt->get_result()->fetch_assoc();

$order_stmt->close();

$top_products_stmt = $conn->prepare("
    SELECT
        products.name,
        SUM(order_items.quantity) AS total_quantity,
        SUM(order_items.subtotal) AS total_sales
    FROM order_items
    INNER JOIN products
        ON order_items.product_id = products.id
    INNER JOIN orders
        ON order_items.order_id = orders.id
    WHERE products.farmer_id = ?
      AND orders.status = 'completed'
    GROUP BY products.id, products.name
    ORDER BY total_quantity DESC
    LIMIT 5
");

$top_products_stmt->bind_param("i", $farmer_id);
$top_products_stmt->execute();

$top_products = $top_products_stmt->get_result();

$sales_stmt = $conn->prepare("
    SELECT
        DATE(created_at) AS sale_date,
        SUM(subtotal) AS daily_sales
    FROM orders
    WHERE farmer_id = ?
      AND status = 'completed'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY sale_date ASC
");

$sales_stmt->bind_param("i", $farmer_id);
$sales_stmt->execute();

$daily_sales = $sales_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Analytics</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>Analytics</h1>
        <section>
            <h2>Product Overview</h2>

            <div>
                <h3>Total Products</h3>
                <p><?= e($product_stats['total_products'] ?? 0) ?></p>
            </div>

            <div>
                <h3>available Products</h3>
                <p><?= e($product_stats['available_products'] ?? 0) ?></p>
            </div>

            <div>
                <h3>Approved Products</h3>
                <p><?= e($product_stats['approved_products'] ?? 0) ?></p>
            </div>

            <div>
                <h3>Pending Products</h3>
                <p><?= e($product_stats['pending_products'] ?? 0) ?></p>
            </div>
        </section>

        <section>
            <h2>Order Overview</h2>

            <div>
                <h3>Total Orders</h3>
                <p><?= e($order_stats['total_orders'] ?? 0) ?></p>
            </div>

            <div>
                <h3>Pending</h3>
                <p><?= e($order_stats['pending_orders'] ?? 0) ?></p>
            </div>

            <div>
                <h3>Accepted</h3>
                <p><?= e($order_stats['accepted_orders'] ?? 0) ?></p>
            </div>
            
            <div>
                <h3>Preparing</h3>
                <p><?= e($order_stats['preparing_orders'] ?? 0) ?></p>
            </div>

            <div>
                <h3>Ready</h3>
                <p><?= e($order_stats['ready_orders'] ?? 0) ?></p>
            </div>

            <div>
                <h3>Completed</h3>
                <p><?= e($order_stats['completed_orders'] ?? 0) ?></p>
            </div>

            <div>
                <h3>Cancelled</h3>
                <p><?= e($order_stats['cancelled_orders'] ?? 0) ?></p>
            </div>
        </section>

        <section>
            <h2>Revenue</h2>
            <div>
                <h3>total Revenue</h3>
                <P><?= formatPrice($order_stats['total_revenue'] ?? 0) ?></P>
            </div>
        </section>
        
        <section>
            <h2>Top 5 Products</h2>
            <?php if ($top_products->num_rows === 0): ?>
                <p>No completed sales yet.</p>
            <?php else: ?>    
                
                <table border="1">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($product = $top_products->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($product['name']) ?></td>
                                <td><?= e($product['total_quantity']) ?></td>
                                <td><?= formatPrice($product['total_sales']) ?></td>
                            </tr>
                        <?php endwhile; ?>    
                    </tbody>
                </table>
            <?php endif; ?>    
        </section>

        <section>
            <h2>Sales - Last 7 Days</h2>

            <?php if ($daily_sales->num_rows === 0): ?>
                <p>No sales recorded in the last 7 days.</p>
            <?php else: ?>

            <table>
            <thead>
                        <tr>
                    <th>Date</th>
                    <th>Sales</th>
                        </tr>
            </thead>

            <tbody>
                <?php while ($sale = $daily_sales->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($sale['sale_date']) ?></td>
                        <td><?= formatPrice($sale['daily_sales']) ?></td>
                    </tr>
                <?php endwhile; ?>    
            </tbody>
            </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>