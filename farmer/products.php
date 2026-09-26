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
// Products Pagination
$products_per_page = 10;

$products_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($products_page < 1) {
    $products_page = 1;
}

$products_offset = ($products_page - 1) * $products_per_page;

// Count total products
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

$product_stmt->bind_param("iii",$farmer_id,$products_per_page,$products_offset);
$product_stmt->execute();

$products = $product_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>My Products</h1>
        <?php if ($products->num_rows === 0): ?>
            <p>No Products Found.</p>
        <?php else: ?>    

            <table border="1">
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Unit</th>
                    <th>Stock</th>
                    <th>Availability</th>
                    <th>Moderation Status</th>
                </tr>

            <tbody>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img
                                        src="<?= e($product['image']) ?>"
                                        alt="<?= e($product['name']) ?>"
                                        width="80">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>                        
                        </td>
                        <td><?= e($product['name']) ?></td>
                        <td><?= e($product['category_name']) ?></td>
                        <td><?= e($product['description'] ?? '') ?></td>
                        <td><?= formatPrice($product['price']) ?></td>
                        <td><?= e($product['unit']) ?></td>
                        <td><?= e($product['stock_quantity']) ?></td>
                        <td>
                                <?php if ($product['is_available']): ?>
                                    Available
                                <?php else: ?>
                                    Unavailable
                                <?php endif; ?>                        
                        </td>
                        <td><?= e(ucfirst($product['moderation_status'])) ?></td>
                    </tr>
                <?php endwhile; ?>    
            </tbody>
        </table>

<?php if ($total_products_pages > 1): ?>

    <div class="pagination">

        <?php if ($products_page > 1): ?>
            <a href="?page=<?= $products_page - 1 ?>">
                Previous
            </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_products_pages; $i++): ?>

            <a href="?page=<?= $i ?>"
               <?= $i == $products_page ? 'class="active"' : '' ?>>
                <?= $i ?>
            </a>

        <?php endfor; ?>

        <?php if ($products_page < $total_products_pages): ?>
            <a href="?page=<?= $products_page + 1 ?>">
                Next
            </a>
        <?php endif; ?>

    </div>

<?php endif; ?>
        <?php endif; ?>
    </main>    
</body>
</html>