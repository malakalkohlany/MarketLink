<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/include.php';

$farmer_id = $_SESSION['farmer_id'];

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
");

$stmt->bind_param("i", $farmer_id);
$stmt->execute();

$products = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stall</title>
</head>
<body>
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
</body>
</html>