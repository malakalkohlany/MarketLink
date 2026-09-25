<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('customer');

// ===============================
// Get Product ID
// ===============================

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

// ===============================
// Get Product + Category + Farmer
// ===============================

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.farmer_id,
        p.category_id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.stock_quantity,
        p.image,
        p.is_available,
        p.moderation_status,
        p.created_at,

        c.name AS category_name,

        f.stall_name AS farmer_name,
        f.contact_person AS farmer_contact,
        f.description AS farmer_description,
        f.address AS farmer_address

    FROM products p

    LEFT JOIN categories c
        ON p.category_id = c.id

    LEFT JOIN farmers f
        ON p.farmer_id = f.id

    WHERE p.id = ?
      AND p.is_available = 1
      AND p.moderation_status = 'approved'

    LIMIT 1
");

$stmt->bind_param("i", $productId);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

// ===============================
// Product Not Found
// ===============================

if (!$product) {
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Product Not Found - MarketLink</title>

        <style>

            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f5f6fa;
            }

            .message-card {
                width: 90%;
                max-width: 600px;
                margin: 100px auto;
                background: white;
                padding: 40px;
                text-align: center;
                border-radius: 15px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            }

            .message-card h2 {
                margin-bottom: 15px;
                color: #333;
            }

            .message-card p {
                color: #777;
                margin-bottom: 25px;
            }

            .back-button {
                display: inline-block;
                padding: 12px 25px;
                background: #27ae60;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }

            .back-button:hover {
                background: #219150;
            }

        </style>

    </head>

    <body>

        <div class="message-card">

            <h2>Product Not Found</h2>

            <p>
                This product is not available or no longer exists.
            </p>

            <a href="products.php" class="back-button">
                Back to Products
            </a>

        </div>

    </body>

    </html>

    <?php
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($product['name']); ?>
        - MarketLink
    </title>

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

        .details-container {
            width: 92%;
            max-width: 1100px;
            margin: 40px auto 60px;
        }

        /* ===============================
           Back Link
        =============================== */

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-size: 15px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* ===============================
           Main Card
        =============================== */

        .product-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
        }

        /* ===============================
           Top Section
        =============================== */

        .top-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 35px;
        }

        /* ===============================
           Image
        =============================== */

        .product-image-container {
            width: 100%;
            height: 420px;
            background: #eeeeee;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image {
            color: #999;
            font-size: 18px;
        }

        /* ===============================
           Product Information
        =============================== */

        .product-info {
            padding: 5px 0;
        }

        .category-badge {
            display: inline-block;
            background: #eaf8ef;
            color: #27ae60;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .product-name {
            font-size: 34px;
            margin: 0 0 15px;
            color: #222;
        }

        .product-description {
            color: #666;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 25px;
        }

        /* ===============================
           Price
        =============================== */

        .price {
            font-size: 31px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 5px;
        }

        .unit {
            color: #777;
            margin-bottom: 25px;
        }

        /* ===============================
           Product Information Box
        =============================== */

        .info-box {
            border-top: 1px solid #eeeeee;
            margin-top: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #eeeeee;
        }

        .info-label {
            font-weight: bold;
            color: #444;
        }

        .info-value {
            color: #666;
            text-align: right;
        }

        /* ===============================
           Availability
        =============================== */

        .stock-available {
            color: #27ae60;
            font-weight: bold;
        }

        .stock-unavailable {
            color: #e74c3c;
            font-weight: bold;
        }

        /* ===============================
           Farmer Section
        =============================== */

        .farmer-section {
            background: #f8f9fb;
            border-top: 1px solid #eeeeee;
            padding: 30px 35px;
        }

        .section-title {
            margin: 0 0 20px;
            font-size: 22px;
            color: #222;
        }

        .farmer-card {
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 22px;
        }

        .farmer-name {
            font-size: 22px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 15px;
        }

        .farmer-row {
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .farmer-label {
            font-weight: bold;
            color: #444;
        }

        .farmer-value {
            color: #666;
        }

        .farmer-description {
            color: #666;
            line-height: 1.7;
            margin-top: 15px;
        }

        /* ===============================
           Actions
        =============================== */

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .button {
            flex: 1;
            display: inline-block;
            padding: 13px 20px;
            border: none;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
        }

        .back-button {
            background: #7f8c8d;
            color: white;
        }

        .back-button:hover {
            background: #6c7a7b;
        }

        .cart-button {
            background: #27ae60;
            color: white;
        }

        .cart-button:hover {
            background: #219150;
        }

        /* ===============================
           Mobile
        =============================== */

        @media (max-width: 768px) {

            .top-section {
                grid-template-columns: 1fr;
                gap: 25px;
                padding: 25px;
            }

            .product-image-container {
                height: 300px;
            }

            .product-name {
                font-size: 28px;
            }

            .farmer-section {
                padding: 25px;
            }

            .actions {
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<div class="details-container">

    <!-- Back to Products -->

    <a
        href="products.php"
        class="back-link"
    >
        ← Back to Products
    </a>


    <!-- Main Product Card -->

    <div class="product-card">


        <!-- ===============================
             Product Top Section
        ================================ -->

        <div class="top-section">


            <!-- Product Image -->

            <div class="product-image-container">

                <?php if (!empty($product['image'])): ?>

                    <img
                        src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        class="product-image"
                    >

                <?php else: ?>

                    <div class="no-image">
                        No Image Available
                    </div>

                <?php endif; ?>

            </div>


            <!-- Product Information -->

            <div class="product-info">


                <!-- Category -->

                <?php if (!empty($product['category_name'])): ?>

                    <div class="category-badge">

                        <?php
                        echo htmlspecialchars(
                            $product['category_name']
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <!-- Product Name -->

                <h1 class="product-name">

                    <?php
                    echo htmlspecialchars(
                        $product['name']
                    );
                    ?>

                </h1>


                <!-- Description -->

                <div class="product-description">

                    <?php

                    echo nl2br(
                        htmlspecialchars(
                            $product['description']
                            ?? 'No description available.'
                        )
                    );

                    ?>

                </div>


                <!-- Price -->

                <div class="price">

                    $

                    <?php

                    echo number_format(
                        (float) $product['price'],
                        2
                    );

                    ?>

                </div>


                <!-- Unit -->

                <div class="unit">

                    Price per

                    <?php

                    echo htmlspecialchars(
                        $product['unit']
                    );

                    ?>

                </div>


                <!-- Product Information -->

                <div class="info-box">


                    <!-- Stock -->

                    <div class="info-row">

                        <span class="info-label">
                            Stock
                        </span>

                        <span class="info-value">

                            <?php

                            echo number_format(
                                (float) $product['stock_quantity'],
                                2
                            );

                            ?>

                            <?php
                            echo htmlspecialchars(
                                $product['unit']
                            );
                            ?>

                        </span>

                    </div>


                    <!-- Availability -->

                    <div class="info-row">

                        <span class="info-label">
                            Availability
                        </span>

                        <span class="info-value">

                            <?php if ($product['is_available']): ?>

                                <span class="stock-available">
                                    Available
                                </span>

                            <?php else: ?>

                                <span class="stock-unavailable">
                                    Not Available
                                </span>

                            <?php endif; ?>

                        </span>

                    </div>


                    <!-- Product ID -->

                    <div class="info-row">

                        <span class="info-label">
                            Product ID
                        </span>

                        <span class="info-value">

                            #

                            <?php
                            echo (int) $product['id'];
                            ?>

                        </span>

                    </div>


                    <!-- Category -->

                    <div class="info-row">

                        <span class="info-label">
                            Category
                        </span>

                        <span class="info-value">

                            <?php

                            echo htmlspecialchars(
                                $product['category_name']
                                ?? 'Not specified'
                            );

                            ?>

                        </span>

                    </div>


                    <!-- Added Date -->

                    <div class="info-row">

                        <span class="info-label">
                            Added
                        </span>

                        <span class="info-value">

                            <?php

                            echo date(
                                'M d, Y',
                                strtotime(
                                    $product['created_at']
                                )
                            );

                            ?>

                        </span>

                    </div>


                </div>


                <!-- Actions -->

                <div class="actions">


                    <a
                        href="products.php"
                        class="button back-button"
                    >
                        Back
                    </a>


                    <!-- Visual Button Only For Now -->

                    <a
                        href="#"
                        class="button cart-button"
                        onclick="return false;"
                    >
                        Add to Cart
                    </a>


                </div>

            </div>

        </div>


        <!-- ===============================
             Farmer Information
        ================================ -->

        <div class="farmer-section">

            <h2 class="section-title">
                Farmer Information
            </h2>


            <div class="farmer-card">


                <!-- Farmer Name -->

                <div class="farmer-name">

                    <?php

                    echo htmlspecialchars(
                        $product['farmer_name']
                        ?? 'Unknown Farmer'
                    );

                    ?>

                </div>


                <!-- Contact Person -->

                <div class="farmer-row">

                    <span class="farmer-label">
                        Contact Person:
                    </span>

                    <span class="farmer-value">

                        <?php

                        echo htmlspecialchars(
                            $product['farmer_contact']
                            ?? 'Not available'
                        );

                        ?>

                    </span>

                </div>


                <!-- Address -->

                <div class="farmer-row">

                    <span class="farmer-label">
                        Location:
                    </span>

                    <span class="farmer-value">

                        <?php

                        echo htmlspecialchars(
                            $product['farmer_address']
                            ?? 'Not available'
                        );

                        ?>

                    </span>

                </div>


                <!-- Farmer Description -->

                <?php if (!empty($product['farmer_description'])): ?>

                    <div class="farmer-description">

                        <span class="farmer-label">
                            About the Farmer:
                        </span>

                        <br>

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $product['farmer_description']
                            )
                        );

                        ?>

                    </div>

                <?php endif; ?>


            </div>

        </div>


    </div>

</div>

</body>

</html>