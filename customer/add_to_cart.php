<?php
require_once __DIR__ . '/../includes/include.php';
requireRole(R_CUSTOMER);
$customerId = (int)getUserId();

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$productId) {
    $productId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );
}

if (!$productId) {
    redirect('customer/products.php');
}

$today = new DateTime();
$weekStart = clone $today;

if ($weekStart->format('N') != 1) {
    $weekStart->modify('monday this week');
}

$weekStartDate = $weekStart->format('Y-m-d');
$moderationStatus = M_APPROVED;
$farmerStatus = A_APPROVED;

$generateWeeklyStockStmt = $conn->prepare("
    INSERT INTO weekly_stock (
        farmer_id,
        product_id,
        week_start,
        planned_quantity,
        actual_quantity,
        status
    )
    SELECT
        wst.farmer_id,
        wst.product_id,
        ?,
        wst.default_quantity,
        wst.default_quantity,
        CASE
            WHEN wst.default_quantity > 0
                THEN 'available'
            ELSE 'sold_out'
        END
    FROM weekly_stock_templates wst
    INNER JOIN products p
        ON p.id = wst.product_id
        AND p.farmer_id = wst.farmer_id
    WHERE wst.is_active = 1
      AND p.is_available = 1
      AND p.moderation_status = ?
      AND NOT EXISTS (
          SELECT 1
          FROM weekly_stock ws
          WHERE ws.farmer_id = wst.farmer_id
            AND ws.product_id = wst.product_id
            AND ws.week_start = ?
      )
");

if ($generateWeeklyStockStmt) {
    $generateWeeklyStockStmt->bind_param(
        "sss",
        $weekStartDate,
        $moderationStatus,
        $weekStartDate
    );
    $generateWeeklyStockStmt->execute();
    $generateWeeklyStockStmt->close();
}

$productStmt = $conn->prepare(
    "
    SELECT
        p.id,
        p.farmer_id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        p.stock_quantity AS product_stock_quantity,
        ws.actual_quantity AS weekly_actual_quantity,
        ws.status AS weekly_status,
        f.stall_name AS farmer_name
    FROM products p
    INNER JOIN farmers f
        ON p.farmer_id = f.id
    LEFT JOIN weekly_stock ws
        ON ws.product_id = p.id
        AND ws.farmer_id = p.farmer_id
        AND ws.week_start = ?
    WHERE p.id = ?
      AND p.is_available = 1
      AND p.moderation_status = ?
      AND f.approval_status = ?
    LIMIT 1
    "
);

if (!$productStmt) {
    die(
    'Product query prepare failed.'
    );
}

$productStmt->bind_param(
    "siss",
    $weekStartDate,
    $productId,
    $moderationStatus,
    $farmerStatus
);

if (!$productStmt->execute()) {
    die(
    'Product query failed.'
    );
}

$productResult = $productStmt->get_result();
$product = $productResult->fetch_assoc();
$productStmt->close();

if (!$product) {
    die('Product not found or is no longer available.');
}

$productId = (int)$product['id'];
$farmerId = (int)$product['farmer_id'];
$productName = $product['name'];
$price = (float)$product['price'];
$unit = $product['unit'] ?? '';
$farmerName = $product['farmer_name'] ?? 'Unknown Farmer';

$productStock = (float)$product['product_stock_quantity'];

if ($product['weekly_actual_quantity'] !== null) {
    $stock = (float)$product['weekly_actual_quantity'];
    $stockStatus = $product['weekly_status'] ?? 'available';
} else {
    $stock = $productStock;
    $stockStatus = 'available';
}

$markets = [];

$marketStmt = $conn->prepare(
    "SELECT
        m.id,
        m.name,
        m.operating_days
     FROM market_farmer mf
     INNER JOIN markets m
        ON mf.market_id = m.id
     WHERE mf.farmer_id = ?
     AND m.status = 'active'
     ORDER BY m.name ASC"
);

if (!$marketStmt) {
   die(
    'Market query prepare failed.'
    );
}

$marketStmt->bind_param(
    "i",
    $farmerId
);

if (!$marketStmt->execute()) {
    die(
    'Market query failed.'
    );
}

$marketResult = $marketStmt->get_result();

while ($row = $marketResult->fetch_assoc()) {
    $markets[] = $row;
}

$marketStmt->close();

if (empty($markets)) {
    die(
        'This farmer is not currently assigned to any market.'
    );
}

$cartMarketId = null;

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart']) &&
    !empty($_SESSION['cart'])
) {
    foreach ($_SESSION['cart'] as $cartItem) {
        if (
            isset($cartItem['market_id']) &&
            (int)$cartItem['market_id'] > 0
        ) {
            $cartMarketId = (int)$cartItem['market_id'];
            break;
        }
    }
}

$cartMarketAllowed = true;

if ($cartMarketId !== null) {
    $cartMarketAllowed = false;

    foreach ($markets as $market) {
        if ((int)$market['id'] === $cartMarketId) {
            $cartMarketAllowed = true;
            break;
        }
    }
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Invalid CSRF token.';
    } else {

        $postedProductId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    $marketId = filter_input(
        INPUT_POST,
        'market_id',
        FILTER_VALIDATE_INT
    );

    if (!$marketId) {
        $marketId = filter_input(
            INPUT_GET,
            'market_id',
            FILTER_VALIDATE_INT
        );
    }

    $quantityRaw = trim(
        $_POST['quantity'] ?? ''
    );

    $quantity = (float)$quantityRaw;

    if (
        !$postedProductId ||
        $postedProductId !== $productId
    ) {
        $errorMessage = 'Invalid product.';
    } elseif (!$marketId) {
        $errorMessage = 'Please select a market.';
    } elseif ($quantity <= 0) {
        $errorMessage = 'Quantity must be greater than 0.';
    } elseif (
        abs(
            $quantity * 10
            - round($quantity * 10)
        ) > 0.000001
    ) {
        $errorMessage = 'Quantity must use increments of 0.1.';
    } elseif ($stockStatus === 'unavailable') {
        $errorMessage = 'This product is currently unavailable for this week.';
    } elseif ($stockStatus === 'sold_out') {
        $errorMessage = 'This product is sold out for this week.';
    } elseif ($stock <= 0) {
        $errorMessage = 'This product is currently out of stock.';
    } elseif ($quantity > $stock) {
        $errorMessage = 'The requested quantity is greater than the available stock.';
    }

    $selectedMarket = null;

    if ($errorMessage === '') {
        foreach ($markets as $market) {
            if (
                (int)$market['id']
                === $marketId
            ) {
                $selectedMarket = $market;
                break;
            }
        }

        if (!$selectedMarket) {
            $errorMessage = 'The selected market is not available for this product.';
        }
    }

    if (
        $errorMessage === ''
        && $cartMarketId !== null
        && $cartMarketId !== $marketId
    ) {
        $errorMessage =
            'Your cart is already assigned to another market. '
            . 'Please clear your cart before choosing a different market.';
    }

    if ($errorMessage === '') {
        if (
            !isset($_SESSION['cart'])
            || !is_array($_SESSION['cart'])
        ) {
            $_SESSION['cart'] = [];
        }

        if (
            isset(
                $_SESSION['cart'][$productId]
            )
        ) {
            $existingItem =
                $_SESSION['cart'][$productId];

            $existingMarketId =
                (int)(
                    $existingItem['market_id']
                    ?? 0
                );

            if (
                $existingMarketId !== $marketId
            ) {
                $errorMessage =
                    'This product is already in your cart '
                    . 'for another market.';
            } else {
                $newQuantity =
                    (float)$existingItem['quantity']
                    + $quantity;

                if ($newQuantity > $stock) {
                    $errorMessage =
                        'The total quantity in your cart '
                        . 'would exceed this week\'s available stock.';
                } else {
                    $_SESSION['cart'][$productId]['quantity'] =
                        round($newQuantity, 1);

                    $_SESSION['cart'][$productId]['subtotal'] =
                        round(
                            $newQuantity * $price,
                            2
                        );
                }
            }
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id' =>
                    $productId,
                'name' =>
                    $productName,
                'price' =>
                    $price,
                'unit' =>
                    $unit,
                'quantity' =>
                    round($quantity, 1),
                'image' =>
                    $product['image'] ?? '',
                'farmer_id' =>
                    $farmerId,
                'farmer_name' =>
                    $farmerName,
                'market_id' =>
                    $marketId,
                'market_name' =>
                    $selectedMarket['name'],
                'market_days' =>
                    $selectedMarket['operating_days'],
                'subtotal' =>
                    round(
                        $quantity * $price,
                        2
                    )
            ];
        }

     if ($errorMessage === '') {
    redirect('customer/products.php');
       }
    }
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
    <title>Add to Cart - MarketLink</title>

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

<main class="main-content customer-add-cart-page">

    <div class="customer-page-hero customer-add-cart-hero">
        <div class="customer-page-hero-copy">

            <span class="eyebrow">
                CUSTOMER / ADD TO CART
            </span>

            <h1>
                Choose where<br>
                to pick up <em>fresh.</em>
            </h1>

            <p>
                Select your market, choose how much you need, and add
                this product to your shopping cart.
            </p>

        </div>

        <div class="customer-page-hero-mark">
            05
        </div>
    </div>

    <section class="customer-add-cart-intro">

        <div class="customer-shopping-note">

            <span class="customer-shopping-note-icon">
                <i data-lucide=" fa-cart-plus"></i>
            </span>

            <div>

                <strong>
                    One market for every order.
                </strong>

                <span>
                    Choose where you want to collect your products.
                    Your cart can only be assigned to one market at a time.
                </span>

            </div>

        </div>

    </section>

    <section class="customer-add-cart-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    01 / PRODUCT
                </span>

                <h2>
                    Add this <em>product.</em>
                </h2>

            </div>

            <span class="customer-record-count">
                <?= e($farmerName) ?>
            </span>

        </div>

        <?php if ($errorMessage !== ''): ?>

            <div class="customer-add-cart-error">

                <span class="customer-add-cart-error-icon">
                    <i data-lucide=" fa-circle-exclamation"></i>
                </span>

                <span>
                    <?= e($errorMessage) ?>
                </span>

            </div>

        <?php endif; ?>

        <div class="customer-add-cart-card">

            <div class="customer-add-cart-product">

                <div class="customer-add-cart-image">

                    <?php if (!empty($product['image'])): ?>

                        <img
                            src="../<?= e($product['image']) ?>"
                            alt="<?= e($productName) ?>"
                        >

                    <?php else: ?>

                        <div class="customer-add-cart-image-placeholder">

                            <i data-lucide=" fa-image"></i>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="customer-add-cart-product-info">

                    <span class="customer-add-cart-product-label">
                        LOCAL PRODUCE
                    </span>

                    <h3>
                        <?= e($productName) ?>
                    </h3>

                    <div class="customer-add-cart-product-farmer">

                        <i data-lucide=" store"></i>

                        <span>
                            <?= e($farmerName) ?>
                        </span>

                    </div>

                    <div class="customer-add-cart-product-price">

                        <?= formatPrice($price) ?>

                        <?php if ($unit !== ''): ?>

                            <span>
                                / <?= e($unit) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <form
                method="POST"
                action="add_to_cart.php?id=<?= $productId ?>"
                class="customer-add-cart-form"
            >

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="product_id"
                    value="<?= $productId ?>"
                >

                <div class="customer-add-cart-field">

                    <div class="customer-add-cart-field-heading">

                        <label>
                            Choose market
                        </label>

                        <span>
                            <i data-lucide=" fa-location-dot"></i>
                            Pickup location
                        </span>

                    </div>

                    <div class="customer-add-cart-markets">

                        <?php foreach ($markets as $market): ?>

                            <?php

                            $marketId = (int)$market['id'];

                            $isCurrentCartMarket =
                                $cartMarketId !== null
                                && $cartMarketId === $marketId;

                            $isSelected =
                                $isCurrentCartMarket
                                || (
                                    isset($_POST['market_id'])
                                    && (int)$_POST['market_id'] === $marketId
                                );

                            $isDisabled =
                                $cartMarketId !== null
                                && !$isCurrentCartMarket;

                            ?>

                            <div class="customer-add-cart-market">

                                <input
                                    type="radio"
                                    id="market_<?= $marketId ?>"
                                    name="market_id"
                                    value="<?= $marketId ?>"
                                    <?= ($isSelected || count($markets) === 1) ? 'checked' : '' ?>
                                    <?= $isDisabled ? 'disabled' : '' ?>
                                    required
                                >

                                <label
                                    for="market_<?= $marketId ?>"
                                >

                                    <span class="customer-add-cart-market-main">

                                        <span class="customer-add-cart-market-name">

                                            <i data-lucide=" fa-location-dot"></i>

                                            <?= e($market['name']) ?>

                                        </span>

                                        <?php if (!empty($market['operating_days'])): ?>

                                            <span class="customer-add-cart-market-days">
                                                <?= e($market['operating_days']) ?>
                                            </span>

                                        <?php endif; ?>

                                    </span>

                                    <?php if ($isCurrentCartMarket): ?>

                                        <span class="customer-add-cart-current-market">

                                            <i data-lucide=" check"></i>

                                            Current cart market

                                        </span>

                                    <?php elseif ($isDisabled): ?>

                                        <span class="customer-add-cart-disabled-market">

                                            Cart assigned to another market

                                        </span>

                                    <?php endif; ?>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="customer-add-cart-field">

                    <div class="customer-add-cart-field-heading">

                        <label for="quantity">
                            Quantity
                        </label>

                        <span>
                            <i data-lucide=" fa-scale-balanced"></i>
                            In <?= e($unit) ?>
                        </span>

                    </div>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        class="customer-add-cart-quantity"
                        min="0.1"
                        max="<?= e($stock) ?>"
                        step="0.1"
                        value="<?= e(
                            $_POST['quantity'] ?? '1'
                        ) ?>"
                        required
                    >

                    <div class="customer-add-cart-stock">

                        <?php if ($stockStatus === 'unavailable'): ?>

                            <i data-lucide=" fa-circle-xmark"></i>

                            Currently unavailable this week.

                        <?php elseif ($stockStatus === 'sold_out'): ?>

                            <i data-lucide=" fa-circle-xmark"></i>

                            Sold out for this week.

                        <?php else: ?>

                            <i data-lucide=" fa-circle-check"></i>

                            Available this week:
                            <?= e($stock) ?>
                            <?= e($unit) ?>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="customer-add-cart-actions">

                    <a
                        href="customer/products.php"
                        class="customer-add-cart-cancel"
                    >
                        <i data-lucide=" arrow-left"></i>
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="customer-add-cart-submit"
                        <?= (
                            $stock <= 0 ||
                            $stockStatus !== 'available' ||
                            !$cartMarketAllowed
                        ) ? 'disabled' : '' ?>
                    >

                        <i data-lucide=" fa-cart-plus"></i>

                        <?php if (!$cartMarketAllowed): ?>

                            Cannot Add From This Market

                        <?php elseif ($stockStatus === 'unavailable'): ?>

                            Currently Unavailable

                        <?php elseif (
                            $stockStatus === 'sold_out' ||
                            $stock <= 0
                        ): ?>

                            Sold Out This Week

                        <?php else: ?>

                            Add to Cart

                        <?php endif; ?>

                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<script src="../assets/js/app.js"></script>
<script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>