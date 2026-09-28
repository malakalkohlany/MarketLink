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

$top_product_chart_labels = [];
$top_product_chart_values = [];

while ($product = $top_products->fetch_assoc()) {
    $top_product_chart_labels[] = $product['name'];
    $top_product_chart_values[] = (int)$product['total_quantity'];
}

$top_products_stmt->close();

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

$sales_chart_labels = [];
$sales_chart_values = [];

while ($sale = $daily_sales->fetch_assoc()) {
    $sales_chart_labels[] = date('M j', strtotime($sale['sale_date']));
    $sales_chart_values[] = (float)$sale['daily_sales'];
}

$sales_stmt->close();

$total_products = (int)($product_stats['total_products'] ?? 0);
$available_products = (int)($product_stats['available_products'] ?? 0);
$approved_products = (int)($product_stats['approved_products'] ?? 0);
$pending_products = (int)($product_stats['pending_products'] ?? 0);

$total_orders = (int)($order_stats['total_orders'] ?? 0);
$pending_orders = (int)($order_stats['pending_orders'] ?? 0);
$accepted_orders = (int)($order_stats['accepted_orders'] ?? 0);
$preparing_orders = (int)($order_stats['preparing_orders'] ?? 0);
$ready_orders = (int)($order_stats['ready_orders'] ?? 0);
$completed_orders = (int)($order_stats['completed_orders'] ?? 0);
$cancelled_orders = (int)($order_stats['cancelled_orders'] ?? 0);
$total_revenue = (float)($order_stats['total_revenue'] ?? 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Farmer Analytics</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/farmer.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content farmer-dashboard-page">

        <section class="farmer-dashboard-hero">

            <div class="farmer-dashboard-hero-copy">

                <span class="eyebrow">
                    FARMER / ANALYTICS
                </span>

                <h1>
                    Your farm, <em>at a glance.</em>
                </h1>

                <p>
                    Keep track of your products, orders, sales, and daily performance from one place.
                </p>

            </div>

            <div class="farmer-dashboard-hero-mark">
                01
            </div>

        </section>

        <section class="farmer-dashboard-overview">

            <div class="farmer-dashboard-stat-card">

                <div class="farmer-dashboard-stat-icon">
                    <i data-lucide="package-open"></i>
                </div>

                <div class="farmer-dashboard-stat-content">

                    <span class="farmer-dashboard-stat-label">
                        Total Products
                    </span>

                    <strong>
                        <?= e($total_products) ?>
                    </strong>

                    <span class="farmer-dashboard-stat-note">
                        <?= e($available_products) ?> currently available
                    </span>

                </div>

            </div>

            <div class="farmer-dashboard-stat-card">

                <div class="farmer-dashboard-stat-icon dashboard-stat-icon-sage">
                    <i data-lucide=" shopping-bag"></i>
                </div>

                <div class="farmer-dashboard-stat-content">

                    <span class="farmer-dashboard-stat-label">
                        Total Orders
                    </span>

                    <strong>
                        <?= e($total_orders) ?>
                    </strong>

                    <span class="farmer-dashboard-stat-note">
                        <?= e($pending_orders) ?> awaiting action
                    </span>

                </div>

            </div>

            <div class="farmer-dashboard-stat-card">

                <div class="farmer-dashboard-stat-icon dashboard-stat-icon-marigold">
                    <i data-lucide="chart-line"></i>
                </div>

                <div class="farmer-dashboard-stat-content">

                    <span class="farmer-dashboard-stat-label">
                        Completed Orders
                    </span>

                    <strong>
                        <?= e($completed_orders) ?>
                    </strong>

                    <span class="farmer-dashboard-stat-note">
                        Completed successfully
                    </span>

                </div>

            </div>

            <div class="farmer-dashboard-stat-card farmer-dashboard-revenue-card">

                <div class="farmer-dashboard-stat-icon dashboard-stat-icon-sage">
                    <i data-lucide="coins"></i>
                </div>

                <div class="farmer-dashboard-stat-content">

                    <span class="farmer-dashboard-stat-label">
                        Total Revenue
                    </span>

                    <strong>
                        <?= formatPrice($total_revenue) ?>
                    </strong>

                    <span class="farmer-dashboard-stat-note">
                        From completed orders
                    </span>

                </div>

            </div>

        </section>

        <section class="farmer-dashboard-main-grid">

            <div class="farmer-dashboard-chart-card farmer-dashboard-sales-card">

                <div class="farmer-dashboard-card-heading">

                    <div>
                        <span class="customer-section-number">
                            02 / SALES
                        </span>

                        <h2>
                            Sales <em>activity.</em>
                        </h2>
                    </div>

                    <span class="farmer-dashboard-card-period">
                        LAST 7 DAYS
                    </span>

                </div>

                <?php if (count($sales_chart_values) === 0): ?>

                    <div class="farmer-dashboard-chart-empty">
                        <div class="farmer-dashboard-empty-mark">
                            <i data-lucide="chart-line"></i>
                        </div>

                        <h3>
                            No sales yet.
                        </h3>

                        <p>
                            Completed sales from the last seven days will appear here.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="farmer-dashboard-chart">
                        <canvas id="salesChart"></canvas>
                    </div>

                <?php endif; ?>

            </div>

            <div class="farmer-dashboard-chart-card">

                <div class="farmer-dashboard-card-heading">

                    <div>
                        <span class="customer-section-number">
                            03 / PRODUCTS
                        </span>

                        <h2>
                            Top <em>products.</em>
                        </h2>
                    </div>

                    <span class="farmer-dashboard-card-period">
                        COMPLETED SALES
                    </span>

                </div>

                <?php if (count($top_product_chart_values) === 0): ?>

                    <div class="farmer-dashboard-chart-empty">
                        <div class="farmer-dashboard-empty-mark">
                            <i data-lucide=" sprout"></i>
                        </div>

                        <h3>
                            No product sales yet.
                        </h3>

                        <p>
                            Your best-selling products will appear here after completed orders.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="farmer-dashboard-chart farmer-dashboard-top-products-chart">
                        <canvas id="topProductsChart"></canvas>
                    </div>

                <?php endif; ?>

            </div>

        </section>

        <section class="farmer-dashboard-secondary-grid">

            <div class="farmer-dashboard-panel">

                <div class="farmer-dashboard-card-heading">

                    <div>
                        <span class="customer-section-number">
                            04 / ORDERS
                        </span>

                        <h2>
                            Order <em>status.</em>
                        </h2>
                    </div>

                </div>

                <div class="farmer-dashboard-order-status-list">

                    <div class="farmer-dashboard-order-status-row">
                        <span>Pending</span>
                        <strong><?= e($pending_orders) ?></strong>
                    </div>

                    <div class="farmer-dashboard-order-status-row">
                        <span>Accepted</span>
                        <strong><?= e($accepted_orders) ?></strong>
                    </div>

                    <div class="farmer-dashboard-order-status-row">
                        <span>Preparing</span>
                        <strong><?= e($preparing_orders) ?></strong>
                    </div>

                    <div class="farmer-dashboard-order-status-row">
                        <span>Ready</span>
                        <strong><?= e($ready_orders) ?></strong>
                    </div>

                    <div class="farmer-dashboard-order-status-row">
                        <span>Completed</span>
                        <strong><?= e($completed_orders) ?></strong>
                    </div>

                    <div class="farmer-dashboard-order-status-row">
                        <span>Cancelled</span>
                        <strong><?= e($cancelled_orders) ?></strong>
                    </div>

                </div>

            </div>

            <div class="farmer-dashboard-panel">

                <div class="farmer-dashboard-card-heading">

                    <div>
                        <span class="customer-section-number">
                            05 / PRODUCTS
                        </span>

                        <h2>
                            Product <em>health.</em>
                        </h2>
                    </div>

                </div>

                <div class="farmer-dashboard-product-health">

                    <div class="farmer-dashboard-health-item">

                        <div class="farmer-dashboard-health-header">
                            <span>Available</span>
                            <strong><?= e($available_products) ?></strong>
                        </div>

                        <div class="farmer-dashboard-progress">
                            <span
                                style="width: <?= $total_products > 0 ? min(100, ($available_products / $total_products) * 100) : 0 ?>%;"
                            ></span>
                        </div>

                    </div>

                    <div class="farmer-dashboard-health-item">

                        <div class="farmer-dashboard-health-header">
                            <span>Approved</span>
                            <strong><?= e($approved_products) ?></strong>
                        </div>

                        <div class="farmer-dashboard-progress dashboard-progress-sage">
                            <span
                                style="width: <?= $total_products > 0 ? min(100, ($approved_products / $total_products) * 100) : 0 ?>%;"
                            ></span>
                        </div>

                    </div>

                    <div class="farmer-dashboard-health-item">

                        <div class="farmer-dashboard-health-header">
                            <span>Pending Review</span>
                            <strong><?= e($pending_products) ?></strong>
                        </div>

                        <div class="farmer-dashboard-progress dashboard-progress-marigold">
                            <span
                                style="width: <?= $total_products > 0 ? min(100, ($pending_products / $total_products) * 100) : 0 ?>%;"
                            ></span>
                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>

    <script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const salesLabels = <?= json_encode($sales_chart_labels) ?>;
        const salesValues = <?= json_encode($sales_chart_values) ?>;

        const productLabels = <?= json_encode($top_product_chart_labels) ?>;
        const productValues = <?= json_encode($top_product_chart_values) ?>;

        const chartText = '#443223';
        const chartMuted = '#7C7960';
        const chartGrid = 'rgba(68, 50, 35, 0.08)';
        const chartTerracotta = '#A08670';
        const chartSage = '#6F7C59';

        const salesCanvas = document.getElementById('salesChart');

        if (salesCanvas) {
            new Chart(salesCanvas, {
                type: 'line',
                data: {
                    labels: salesLabels,
                    datasets: [{
                        label: 'Sales',
                        data: salesValues,
                        borderColor: chartSage,
                        backgroundColor: 'rgba(111, 124, 89, 0.10)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: chartSage,
                        pointBorderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Sales: ' + Number(context.raw).toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: chartMuted,
                                font: {
                                    family: 'inherit',
                                    size: 11
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: chartGrid
                            },
                            ticks: {
                                color: chartMuted,
                                font: {
                                    family: 'inherit',
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }

        const topProductsCanvas = document.getElementById('topProductsChart');

        if (topProductsCanvas) {
            new Chart(topProductsCanvas, {
                type: 'bar',
                data: {
                    labels: productLabels,
                    datasets: [{
                        label: 'Units Sold',
                        data: productValues,
                        backgroundColor: chartTerracotta,
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Units sold: ' + Number(context.raw).toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: chartGrid
                            },
                            ticks: {
                                color: chartMuted,
                                precision: 0,
                                font: {
                                    family: 'inherit',
                                    size: 11
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: chartText,
                                font: {
                                    family: 'inherit',
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>

</body>
</html>