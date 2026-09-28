<?php
require_once __DIR__ . '/../includes/include.php';
requireRole(R_CUSTOMER);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['clear_cart'])
) {
    $_SESSION['cart'] = [];
    redirect('customer/cart.php');
}

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

$cartSubtotal = 0;

foreach ($cart as $item) {
    $quantity = (float)($item['quantity'] ?? 0);
    $price = (float)($item['price'] ?? 0);
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

    <link
        rel="stylesheet"
        href="../assets/css/customer.css"
    >
    <link
        rel="stylesheet"
        href="../assets/css/customer_n.css"
    >

    <link
        rel="stylesheet"
        href="../assets/fontawesome/css/all.min.css"
    >

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content customer-cart-page">

    <section class="customer-page-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                CUSTOMER / SHOPPING CART
            </span>

                <h1>
                    Manage your <em>cart.</em>
                </h1>

            </div>

            <div class="customer-page-hero-mark">
                06
            </div>

        </section>



    <section class="customer-cart-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    01 / REVIEW
                </span>

                <h2>
                    Your <em>cart.</em>
                </h2>

            </div>

            <span class="customer-record-count">

                <?= $totalItems ?>

                item<?= $totalItems != 1 ? 's' : '' ?>

            </span>

        </div>

        <?php if (empty($cart)): ?>

            <div class="customer-farmers-empty">

                <span class="customer-farmers-empty-mark">

                    <i data-lucide="shopping-basket"></i>

                </span>

                <strong>
                    Your cart is empty.
                </strong>

                <span>
                    You haven't added any products yet. Browse the marketplace
                    to find fresh produce from local farmers.
                </span>

                <a
                    href="products.php"
                    class="customer-farmer-details"
                >
                    Browse Products

                    <i data-lucide=" arrow-right"></i>
                </a>

            </div>

        <?php else: ?>

            <div class="cart-layout">

                <div class="cart-items">

                    <?php foreach ($cart as $item): ?>

                        <?php

                        $productId = (int)$item['product_id'] ?? 0;

                        $quantity = (float)$item['quantity'] ?? 0;

                        $price = (float)$item['price'] ?? 0;

                        $subtotal = $quantity * $price;

                        ?>

                        <article class="cart-item">

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

                            <div class="cart-item-subtotal">
                                <?= formatPrice($subtotal) ?>
                            </div>

                            <button
                                type="button"
                                class="remove-item"
                                onclick="removeItem(<?= $productId ?>)"
                                aria-label="Remove <?= e($item['name']) ?>"
                            >
                                <i data-lucide="check"></i>
                            </button>

                        </article>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

    <?php if (!empty($cart)): ?>
    <div class="customer-cart-floating-actions" id="cartFloatingActions">
        <a
            href="products.php"
            class="continue-shopping"
        >
            <i data-lucide=" arrow-left"></i>
            Continue Shopping
        </a>

        <a
            href="confirm_order.php"
            class="checkout-button"
        >
            Confirm Order
            <i data-lucide=" arrow-right"></i>
        </a>
    </div>

    <section class="customer-cart-summary-section" id="cartSummarySection">
        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    02 / CHECKOUT
                </span>
                <h2>
                    Order <em>summary.</em>
                </h2>
            </div>
        </div>

        <aside class="cart-summary">
            <div class="summary-row">
                <span>Items</span>
                <span><?= $totalItems ?></span>
            </div>

            <div class="summary-row summary-total">
                <span>Subtotal</span>
                <span><?= formatPrice($cartSubtotal) ?></span>
            </div>
        </aside>
    </section>
<?php endif; ?>

</main>

<script src="../assets/js/cart.js"></script>
<script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

</body>

</html>