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


// ===============================
// Error Message
// ===============================

$errorMessage = '';

if (isset($_GET['error'])) {

    if ($_GET['error'] === 'invalid_quantity') {
        $errorMessage = 'Please enter a valid quantity.';
    }

    if ($_GET['error'] === 'stock') {
        $errorMessage =
            'The selected quantity is greater than the available stock.';
    }
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

        .product-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
        }

        .top-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 35px;
        }

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

        .stock-available {
            color: #27ae60;
            font-weight: bold;
        }

        .stock-unavailable {
            color: #e74c3c;
            font-weight: bold;
        }

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
           Quantity Box
        =============================== */

        .quantity-box {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fb;
            border: 1px solid #e1e5e8;
            border-radius: 12px;
        }

        .quantity-box.show {
            display: block;
        }

        .quantity-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .quantity-label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #444;
        }

        .quantity-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
        }

        .quantity-input:focus {
            border-color: #27ae60;
        }

        .quantity-help {
            display: block;
            margin-top: 7px;
            color: #777;
            font-size: 13px;
        }

        .selected-total {
            margin-top: 15px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            font-size: 17px;
            font-weight: bold;
            color: #27ae60;
        }

        .confirm-button {
            width: 100%;
            margin-top: 15px;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #27ae60;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .confirm-button:hover {
            background: #219150;
        }

        .cancel-button {
            width: 100%;
            margin-top: 10px;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #95a5a6;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .cancel-button:hover {
            background: #7f8c8d;
        }

        .error-message {
            margin-bottom: 20px;
            padding: 12px 15px;
            background: #fdecea;
            border: 1px solid #f5c6cb;
            color: #c0392b;
            border-radius: 8px;
            font-size: 14px;
        }

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

    <a
        href="products.php"
        class="back-link"
    >
        ← Back to Products
    </a>

    <?php if ($errorMessage): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>

    <?php endif; ?>

    <div class="product-card">

        <div class="top-section">

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

            <div class="product-info">

                <?php if (!empty($product['category_name'])): ?>

                    <div class="category-badge">
                        <?php
                        echo htmlspecialchars(
                            $product['category_name']
                        );
                        ?>
                    </div>

                <?php endif; ?>

                <h1 class="product-name">
                    <?php
                    echo htmlspecialchars(
                        $product['name']
                    );
                    ?>
                </h1>

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

                <div class="price">

                    $
                    <?php
                    echo number_format(
                        (float) $product['price'],
                        2
                    );
                    ?>

                </div>

                <div class="unit">

                    Price per
                    <?php
                    echo htmlspecialchars(
                        $product['unit']
                    );
                    ?>

                </div>

                <div class="info-box">

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

                <div class="actions">

                    <a
                        href="products.php"
                        class="button back-button"
                    >
                        Back
                    </a>

                    <?php if ((float) $product['stock_quantity'] > 0): ?>

                        <a
                            href="add_to_cart.php?id=<?= (int) $product['id'] ?>"
                            class="button cart-button"
                        >
                            Add to Cart
                        </a>

                    <?php else: ?>

                        <button
                            type="button"
                            class="button"
                            style="background:#e74c3c;color:white;cursor:not-allowed;"
                            disabled
                        >
                            Out of Stock
                        </button>

                    <?php endif; ?>

                </div>

                

            </div>

        </div>

        <div class="farmer-section">

            <h2 class="section-title">
                Farmer Information
            </h2>

            <div class="farmer-card">

                <div class="farmer-name">

                    <?php
                    echo htmlspecialchars(
                        $product['farmer_name']
                        ?? 'Unknown Farmer'
                    );
                    ?>

                </div>

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

<script>

    const productPrice =
        <?php echo (float) $product['price']; ?>;

    const maxStock =
        <?php echo (float) $product['stock_quantity']; ?>;

    function showQuantityBox() {

        const box =
            document.getElementById('quantityBox');

        box.classList.add('show');

        const quantityInput =
            document.getElementById('quantity');

        quantityInput.focus();

        calculateTotal();
    }

    function hideQuantityBox() {

        const box =
            document.getElementById('quantityBox');

        box.classList.remove('show');
    }

    function calculateTotal() {

        const quantityInput =
            document.getElementById('quantity');

        const totalPrice =
            document.getElementById('totalPrice');

        let quantity =
            parseFloat(quantityInput.value);

        if (isNaN(quantity) || quantity < 0) {
            quantity = 0;
        }

        if (quantity > maxStock) {

            quantity = maxStock;

            quantityInput.value = maxStock;
        }

        const total =
            quantity * productPrice;

        totalPrice.textContent =
            total.toFixed(2);
    }

</script>

</body>

</html>