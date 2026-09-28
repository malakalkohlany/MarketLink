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
$farmer_id = $farmer['id'];

$stmt->close();

$items_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $items_per_page;

$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_products
    FROM products
    WHERE farmer_id = ?
");

$count_stmt->bind_param("i", $farmer_id);
$count_stmt->execute();

$count_result = $count_stmt->get_result();
$total_products = $count_result->fetch_assoc()['total_products'];

$count_stmt->close();

$total_pages = ceil($total_products / $items_per_page);

if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $items_per_page;
}

$product_stmt = $conn->prepare("
    SELECT
        products.id,
        products.name,
        products.description,
        products.price,
        products.unit,
        products.stock_quantity,
        products.is_available,
        products.moderation_status,
        categories.name AS category_name
    FROM products
    INNER JOIN categories
        ON products.category_id = categories.id
    WHERE products.farmer_id = ?
    ORDER BY products.created_at DESC
    LIMIT ? OFFSET ?
");

$product_stmt->bind_param("iii", $farmer_id, $items_per_page, $offset);
$product_stmt->execute();

$products = $product_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Inventory | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/farmer.css">
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content farmer-inventory-page">

    <section class="customer-page-hero">

        <div class="customer-page-hero-copy">

            <span class="eyebrow">
                FARMER / INVENTORY
            </span>

            <h1>
                Keep track of your <em>stock.</em>
            </h1>

            <p>
                Monitor your current stock, pricing, availability, and product status at a glance.
            </p>

        </div>

        <div class="customer-page-hero-mark">
            03
        </div>

    </section>

    <section class="farmer-inventory-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    01 / INVENTORY
                </span>

                <h2>
                    Product <em>stock.</em>
                </h2>

            </div>

            <span class="customer-record-count">
                <?= $total_products ?>
                <?= $total_products === 1 ? 'PRODUCT' : 'PRODUCTS' ?>
            </span>

        </div>

        <?php if ($total_products === 0): ?>

            <div class="customer-products-empty">

                <div class="customer-products-empty-mark">
                    ✦
                </div>

                <h3>
                    Your inventory is empty.
                </h3>

                <p>
                    Products you add will appear here so you can keep track of your stock and availability.
                </p>

            </div>

        <?php else: ?>

            <div class="farmer-inventory-table-wrapper">

                <table class="farmer-inventory-table">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Availability</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($product = $products->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <span class="farmer-inventory-product">
                                        <?= e($product['name']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($product['category_name']) ?>
                                </td>

                                <td>
                                    <span class="farmer-inventory-price">
                                        <?= e($product['price']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($product['unit']) ?>
                                </td>

                                <td>
                                    <span class="<?= (float) $product['stock_quantity'] > 0 ? 'inventory-stock-available' : 'inventory-stock-empty' ?>">
                                        <?= e($product['stock_quantity']) ?>
                                    </span>
                                </td>

                                <td>

                                    <span class="farmer-inventory-status status-<?= htmlspecialchars($product['moderation_status']) ?>">
                                        <?= e($product['moderation_status']) ?>
                                    </span>

                                </td>

                                <td>

                                    <?php if ($product['is_available']): ?>

                                        <span class="farmer-inventory-availability inventory-available">
                                            Available
                                        </span>

                                    <?php else: ?>

                                        <span class="farmer-inventory-availability inventory-unavailable">
                                            Unavailable
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

            <?php if ($total_pages > 1): ?>

                <div class="product-pagination">

                    <?php if ($page > 1): ?>

                        <a href="?page=<?= $page - 1 ?>">
                            Previous
                        </a>

                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>

                        <a
                            href="?page=<?= $i ?>"
                            class="<?= $i == $page ? 'active' : '' ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>

                        <a href="?page=<?= $page + 1 ?>">
                            Next
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </section>

</main>

<script src="../assets/js/app.js"></script>

</body>

</html>