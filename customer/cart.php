<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);


// --------------------------------------------------
// Get Cart
// --------------------------------------------------

$cart = [];

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart'])
) {
    $cart = $_SESSION['cart'];
}

$totalItems = 0;

foreach ($cart as $item) {
    $totalItems += (float)($item['quantity'] ?? 0);
}


// --------------------------------------------------
// Calculate Cart Total
// --------------------------------------------------

$cartSubtotal = 0;

foreach ($cart as $item) {

    $quantity = (float) ($item['quantity'] ?? 0);
    $price = (float) ($item['price'] ?? 0);

    $cartSubtotal += $quantity * $price;
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

    <title>My Cart - MarketLink</title>

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


    <style>

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .cart-header {
            margin-bottom: 30px;
        }

        .cart-header h1 {
            margin: 0 0 8px;
        }

        .cart-header p {
            margin: 0;
            color: #777;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 25px;
            align-items: start;
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e0da;
        }

        .cart-item-image {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f3eee8;
        }

        .cart-item-image-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            background: #f3eee8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8a8178;
            flex-shrink: 0;
        }

        .cart-item-info {
            flex: 1;
            min-width: 0;
        }

        .cart-item-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .cart-item-price {
            color: #72583E;
            margin-bottom: 4px;
        }

        .cart-item-unit {
            color: #888;
            font-size: 14px;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quantity-button {
            width: 34px;
            height: 34px;
            border: 1px solid #d7cec4;
            background: #fff;
            border-radius: 7px;
            cursor: pointer;
            font-size: 18px;
        }

        .quantity-button:hover {
            background: #f5f0eb;
        }

        .quantity-value {
            min-width: 45px;
            text-align: center;
            font-weight: 600;
        }

        .cart-item-subtotal {
            min-width: 100px;
            text-align: right;
            font-weight: 600;
        }

        .remove-item {
            border: none;
            background: none;
            color: #8a5a5a;
            cursor: pointer;
            font-size: 14px;
        }

        .remove-item:hover {
            text-decoration: underline;
        }

        .cart-summary {
            background: #fff;
            border: 1px solid #e5e0da;
            border-radius: 12px;
            padding: 25px;
            position: sticky;
            top: 95px;
        }

        .cart-summary h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .summary-total {
            border-top: 1px solid #e5e0da;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 19px;
            font-weight: 600;
        }

        .checkout-button {
            display: block;
            width: 100%;
            margin-top: 20px;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #72583E;
            color: #fff;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        .checkout-button:hover {
            background: #5f4833;
        }

        .continue-shopping {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #72583E;
            text-decoration: none;
        }

        .empty-cart {
            background: #fff;
            border: 1px solid #e5e0da;
            border-radius: 12px;
            padding: 60px 30px;
            text-align: center;
        }

        .empty-cart-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .empty-cart h2 {
            margin-bottom: 10px;
        }

        .empty-cart p {
            color: #777;
            margin-bottom: 25px;
        }

        .browse-products-button {
            display: inline-block;
            padding: 12px 22px;
            background: #72583E;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
        }


        @media (max-width: 900px) {

            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

        }


        @media (max-width: 650px) {

            .cart-item {
                flex-wrap: wrap;
            }

            .cart-item-info {
                width: calc(100% - 120px);
            }

            .cart-item-subtotal {
                margin-left: auto;
            }

        }

    </style>

</head>

<body>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">

    <div class="cart-container">


        <!-- Cart Header -->

        <div class="cart-header">

            <h1>My Cart</h1>

            <p>
                Review the products you want to order.
            </p>

        </div>


        <?php if (empty($cart)): ?>

            <!-- Empty Cart -->

            <div class="empty-cart">

                <div class="empty-cart-icon">
                    🛒
                </div>

                <h2>Your cart is empty</h2>

                <p>
                    You haven't added any products yet.
                </p>

                <a
                    href="products.php"
                    class="browse-products-button"
                >
                    Browse Products
                </a>

            </div>


        <?php else: ?>


            <!-- Cart -->

            <div class="cart-layout">


                <!-- Cart Items -->

                <div class="cart-items">

                    <?php foreach ($cart as $item): ?>

                        <?php

                        $productId = (int) $item['product_id'] ?? 0;

                        $quantity = (float) $item['quantity'] ?? 0;

                        $price = (float) $item['price'] ?? 0;

                        $subtotal = $quantity * $price;

                        ?>

                        <div class="cart-item">


                            <!-- Image -->

                            <?php if (!empty($item['image'])): ?>

                                <img
                                    src="../uploads/products/<?= e($item['image']) ?>"
                                    alt="<?= e($item['name']) ?>"
                                    class="cart-item-image"
                                >

                            <?php else: ?>

                                <div class="cart-item-image-placeholder">
                                    No Image
                                </div>

                            <?php endif; ?>


                            <!-- Product Info -->

                            <div class="cart-item-info">

                                <div class="cart-item-name">

                                    <?= e($item['name']) ?>

                                </div>

                                <div class="cart-item-price">

                                    <?= formatPrice($price) ?>

                                </div>

                                <div class="cart-item-unit">

                                    per
                                    <?= e($item['unit']) ?>

                                </div>

                            </div>


                            <!-- Quantity -->

                            <div class="cart-item-quantity">

                                <button
                                    type="button"
                                    class="quantity-button"
                                    onclick="updateQuantity(
                                        <?= $productId ?>,
                                        'decrease'
                                    )"
                                >
                                    −
                                </button>

                                <span class="quantity-value">

                                    <?= $quantity ?>

                                </span>

                                <button
                                    type="button"
                                    class="quantity-button"
                                    onclick="updateQuantity(
                                        <?= $productId ?>,
                                        'increase'
                                    )"
                                >
                                    +
                                </button>

                            </div>


                            <!-- Subtotal -->

                            <div class="cart-item-subtotal">

                                <?= formatPrice($subtotal) ?>

                            </div>


                            <!-- Remove -->

                            <button
                                type="button"
                                class="remove-item"
                                onclick="removeItem(<?= $productId ?>)"
                            >
                                Remove
                            </button>


                        </div>

                    <?php endforeach; ?>

                </div>


                <!-- Cart Summary -->

                <aside class="cart-summary">

                    <h2>Order Summary</h2>


                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <span>
                            <?= $totalItems ?>
                        </span>

                    </div>


                    <div class="summary-row summary-total">

                        <span>
                            Subtotal
                        </span>

                        <span>
                            <?= formatPrice($cartSubtotal) ?>
                        </span>

                    </div>


                    <!-- We'll connect this to confirm_order.php later -->

                    <a
                        href="#"
                        class="checkout-button"
                    >
                        Confirm Order
                    </a>


                    <a
                        href="products.php"
                        class="continue-shopping"
                    >
                        ← Continue Shopping
                    </a>

                </aside>


            </div>

        <?php endif; ?>


    </div>

</main>


<script src="../assets/js/cart.js"></script>

</body>

</html>