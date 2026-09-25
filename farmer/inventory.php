<?php

require_once __DIR__ . '/../includes/include.php';

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
");

$product_stmt->bind_param("i", $farmer_id);
$product_stmt->execute();

$products = $product_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <h1>My Inventory</h1>
        
        <table border="1">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Unit</th>
                    <th>Stock Quantity</th>
                    <th>Status</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <?php while ($product = $products->fetch_assoc()): ?>

        <tr>

            <td>
                <?php if (!empty($product['image'])): ?>
                    <img src="../<?= e($product['image']) ?>" width="80">
                <?php else: ?>
                    No Image
                <?php endif; ?>
            </td>

            <td><?= e($product['name']) ?></td>

            <td><?= e($product['category_name']) ?></td>

            <td><?= e($product['price']) ?></td>

            <td><?= e($product['unit']) ?></td>

            <td><?= e($product['stock_quantity']) ?></td>

            <td><?= e($product['moderation_status']) ?></td>

            <td>
                <?php if ($product['is_available']): ?>
                    Available
                <?php else: ?>
                    Unavailable
                <?php endif; ?>
            </td>

            <td>
                <a href="edit_product.php?id=<?=$product['id']?>">Edit</a>
            </td>

        </tr>
        <?php endwhile; ?>
        </table>
    </main>


</body>
</html>