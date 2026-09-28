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

$products_per_page = 10;
$products_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($products_page < 1) {
    $products_page = 1;
}

$products_offset = ($products_page - 1) * $products_per_page;

$count_products_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_products
    FROM products
    WHERE farmer_id = ?
");
$count_products_stmt->bind_param("i", $farmer_id);
$count_products_stmt->execute();

$count_products_result = $count_products_stmt->get_result();
$total_products = $count_products_result->fetch_assoc()['total_products'];

$count_products_stmt->close();

$total_products_pages = ceil($total_products / $products_per_page);

if ($total_products_pages > 0 && $products_page > $total_products_pages) {
    $products_page = $total_products_pages;
    $products_offset = ($products_page - 1) * $products_per_page;
}

$product_stmt = $conn->prepare("
    SELECT
        products.id,
        products.name,
        products.description,
        products.price,
        products.unit,
        products.stock_quantity,
        products.image,
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

$product_stmt->bind_param(
    "iii",
    $farmer_id,
    $products_per_page,
    $products_offset
);

$product_stmt->execute();
$products = $product_stmt->get_result();

$record_label = $total_products === 1 ? 'product' : 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products | MarketLink</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/farmer.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content farmer-products-page">

    <section class="customer-page-hero">
        <div class="customer-page-hero-copy">
            <span class="eyebrow">FARMER / PRODUCTS</span>

            <h1>Your local <em>produce.</em></h1>

            <p>
                Manage the products you offer through MarketLink and keep your
                listings, stock, and availability up to date.
            </p>
        </div>

        <div class="customer-page-hero-mark">03</div>
    </section>

    <section class="customer-products-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">01 / INVENTORY</span>

                <h2>My <em>products.</em></h2>
            </div>

            <div class="farmer-products-heading-actions">
                <span class="customer-record-count">
                    <?= $total_products ?> <?= e($record_label) ?>
                </span>

                <a href="add_product.php" class="farmer-add-product">
                    <i data-lucide="plus"></i>
                    Add Product
                </a>
            </div>
        </div>

        <?php if ($products->num_rows === 0): ?>

            <div class="customer-products-empty">
                <div class="customer-products-empty-mark">
                    ✦
                </div>

                <strong>No products yet.</strong>

                <span>
                    Add your first product to start building your MarketLink inventory.
                </span>

                <a href="add_product.php" class="farmer-empty-add-product">
                    <i data-lucide="plus"></i>
                    Add Product
                </a>
            </div>

        <?php else: ?>

            <div class="customer-products-grid">

                <?php while ($product = $products->fetch_assoc()): ?>

                    <article class="customer-product-card">

                        <div class="customer-product-image">

                            <?php if (!empty($product['image'])): ?>

                                <img
                                    src="../<?= e($product['image']) ?>"
                                    alt="<?= e($product['name']) ?>"
                                >

                            <?php else: ?>

                                <div class="customer-product-image-empty">
                                    <span>✦</span>
                                    <small>No image</small>
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="customer-product-content">

                            <div class="customer-product-category">
                                <?= e($product['category_name']) ?>
                            </div>

                            <h3 class="customer-product-name">
                                <?= e($product['name']) ?>
                            </h3>

                            <p class="customer-product-description">
                                <?= e($product['description'] ?? '') ?>
                            </p>

                            <div class="customer-product-meta">

                                <div class="customer-product-price">
                                    <strong>
                                        <?= formatPrice($product['price']) ?>
                                    </strong>

                                    <span>
                                        / <?= e($product['unit']) ?>
                                    </span>
                                </div>

                                <div class="customer-product-stock">

                                    <?php if (!$product['is_available']): ?>

                                        <span class="stock-unavailable">
                                            Unavailable
                                        </span>

                                    <?php elseif ((float)$product['stock_quantity'] <= 0): ?>

                                        <span class="stock-sold-out">
                                            Out of stock
                                        </span>

                                    <?php else: ?>

                                        <span class="stock-available">
                                            <?= e($product['stock_quantity']) ?>
                                            <?= e($product['unit']) ?> available
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="farmer-product-status">

                                <span class="farmer-product-status-label">
                                    MODERATION
                                </span>

                                <span class="farmer-product-status-value status-<?= e(strtolower($product['moderation_status'])) ?>">
                                    <?= e(ucfirst($product['moderation_status'])) ?>
                                </span>

                            </div>

                            <div class="farmer-product-actions">

                                <a
                                    href="edit_product.php?id=<?= (int)$product['id'] ?>"
                                    class="farmer-product-edit"
                                >
                                    <i data-lucide="pen"></i>
                                    Edit Product
                                </a>


                                

                            </div>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

            <?php if ($total_products_pages > 1): ?>

                <div class="product-pagination">

                    <?php if ($products_page > 1): ?>

                        <a
                            href="?page=<?= $products_page - 1 ?>"
                            class="product-pagination-button product-pagination-arrow"
                        >
                            Previous
                        </a>

                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_products_pages; $i++): ?>

                        <a
                            href="?page=<?= $i ?>"
                            class="product-pagination-button <?= $i === $products_page ? 'active' : '' ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($products_page < $total_products_pages): ?>

                        <a
                            href="?page=<?= $products_page + 1 ?>"
                            class="product-pagination-button product-pagination-arrow"
                        >
                            Next
                        </a>

                    <?php endif; ?>

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