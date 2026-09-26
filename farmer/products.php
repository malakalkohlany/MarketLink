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

if (!$farmer) {
    die("Farmer account not found.");
}

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
    <title>My Products</title>
</head>
<body>
    <h1>My Products</h1>
    <?php if ($products->num_rows === 0): ?>
        <p>No Products Found.</p>
    <?php else: ?>    

        <table>
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
     <?php endif; ?>    
</body>
</html>