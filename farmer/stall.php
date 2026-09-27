<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$farmer_id = $_SESSION['farmer_id'];
$products_per_page = 10;

$products_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($products_page < 1) {
    $products_page = 1;
}

$products_offset = ($products_page - 1) * $products_per_page;

// Count total available products
$count_products_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_products
    FROM products
    WHERE farmer_id = ?
      AND stock_quantity > 0
      AND is_available = 1
      AND moderation_status = 'approved'
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

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        description,
        price,
        unit,
        stock_quantity,
        image,
        is_available
    FROM products
    WHERE farmer_id = ?
      AND stock_quantity > 0
      AND is_available = 1
      AND moderation_status = 'approved'
    ORDER BY name ASC
    LIMIT ? OFFSET ?
");

$stmt->bind_param("iii",$farmer_id,$products_per_page,$products_offset);
$stmt->execute();

$products = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stall</title>

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
        href="../assets/css/sidebar.css"
    >
</head>
<body>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>My Stall</h1>
        <h2>Available Products</h2>

        <table border="1">
            <thead>
                <tr>
                    <th>Product Name<</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Unit</th>
                    <th>Remaining Stock</th>
                    <th>Image</th>
                    <th>Availability</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($product['name']) ?></td>
                        <td><?= e($product['description']) ?></td>
                        <td><?= e($product['price']) ?></td>
                        <td><?= e($product['unit']) ?></td>
                        <td><?= e($product['stock_quantity']) ?></td>
                        <td>
                            <?php if (!empty($product['image'])): ?>
                            <img
                                src="../<?= e($product['image']) ?>"
                                alt="<?= e($product['name']) ?>"
                                width="100">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>  
                                            <td>
                            <?php if ($product['is_available']): ?>
                                Available
                            <?php else: ?>
                                Unavailable
                            <?php endif; ?>
                        </td>             
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
    </main>
</body>
</html>