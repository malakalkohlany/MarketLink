<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

// Get approved and available products
$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        p.stock_quantity,
        p.farmer_id
    FROM products p
    INNER JOIN farmers f ON p.farmer_id = f.id
    WHERE p.is_available = 1
      AND p.moderation_status = ?
      AND f.approval_status = ?
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Database Error: ' . $conn->error);
}

$moderation_status = M_APPROVED;
$farmer_status = A_APPROVED;

$stmt->bind_param(
    'ss',
    $moderation_status,
    $farmer_status
);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products - MarketLink</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .products-container {
            width: 92%;
            max-width: 1200px;
            margin: 40px auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .page-header p {
            margin: 0;
            color: #777;
        }

        .products-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fill, minmax(240px, 1fr));

            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);

            transition: 0.2s;
        }

        .product-card:hover {
            transform: translateY(-4px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #eee;
        }

        .no-image {
            width: 100%;
            height: 200px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #eee;
            color: #999;
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .product-description {
            color: #777;
            font-size: 14px;
            line-height: 1.5;

            min-height: 42px;
            margin-bottom: 15px;
        }

        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
        }

        .product-unit {
            color: #777;
            font-size: 13px;
            margin-top: 4px;
        }

        .product-stock {
            margin-top: 10px;
            font-size: 14px;
            color: #555;
        }

        .view-button {
            display: block;

            margin-top: 18px;
            padding: 11px;

            background: #3498db;
            color: white;

            text-align: center;
            text-decoration: none;

            border-radius: 8px;
        }

        .view-button:hover {
            background: #2980b9;
        }

        .empty-products {
            background: white;

            padding: 60px 30px;

            border-radius: 14px;

            text-align: center;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .empty-products h2 {
            margin-bottom: 10px;
        }

        .empty-products p {
            color: #777;
            margin: 0;
        }

    </style>

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="products-container">

            <div class="page-header">

                <h1>Products</h1>

                <p>
                    Browse fresh products available on MarketLink.
                </p>

            </div>


            <?php if ($result->num_rows > 0): ?>

                <div class="products-grid">

                    <?php while ($product = $result->fetch_assoc()): ?>

                        <div class="product-card">

                            <?php if (!empty($product['image'])): ?>

                                <img
                                    src="../uploads/products/<?php
                                        echo e($product['image']);
                                    ?>"
                                    alt="<?php
                                        echo e($product['name']);
                                    ?>"
                                    class="product-image"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    No Image
                                </div>

                            <?php endif; ?>


                            <div class="product-info">

                                <div class="product-name">

                                    <?php
                                    echo e($product['name']);
                                    ?>

                                </div>


                                <div class="product-description">

                                    <?php

                                    echo e(
                                        $product['description']
                                        ?? 'No description available.'
                                    );

                                    ?>

                                </div>


                                <div class="product-price">

                                    $

                                    <?php
                                    echo formatPrice(
                                        (float)$product['price']);
                                    ?>

                                </div>


                                <?php if (!empty($product['unit'])): ?>

                                    <div class="product-unit">

                                        Per
                                        <?php
                                        echo e(
                                            $product['unit']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>


                                <div class="product-stock">
                                    <?php if ((int)$product['stock_quantity'] > 0): ?>
                                        Stock:
                                        <?php echo (int)$product['stock_quantity']; ?>
                                    <?php else: ?>
                                        Out of stock
                                    <?php endif; ?>
                                </div>


                                <a
                                    href="product_details.php?id=<?php
                                        echo (int)$product['id'];
                                    ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>


            <?php else: ?>

                <div class="empty-products">

                    <h2>No Products Available</h2>

                    <p>
                        Products will appear here once farmers add
                        and publish them.
                    </p>

                </div>

            <?php endif; ?>

        </div>
    </main>

</body>

</html>
