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
    redirect('products.php');
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
    redirect('products.php');
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
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
    <style>
        .add-cart-page {
            max-width: 850px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .page-header p {
            margin: 0;
            color: #666;
        }

        .add-cart-card {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 16px;
            padding: 25px;
            box-shadow:
                0 6px 18px
                rgba(0, 0, 0, 0.05);
        }

        .product-preview {
            background: #f5f5f5;
            border-radius: 12px;
            overflow: hidden;
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-preview img {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }

        .product-preview-placeholder {
            color: #bbb;
            font-size: 50px;
        }

        .product-info h2 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .farmer-name {
            color: #72583E;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .price {
            font-size: 21px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #4f4136;
        }

        .market-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .market-option {
            position: relative;
        }

        .market-option input {
            position: absolute;
            opacity: 0;
        }

        .market-option label {
            display: block;
            padding: 13px 15px;
            border: 1px solid #d7cec4;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s ease;
            background: #fff;
        }

        .market-option label:hover {
            background: #f8f3ee;
            border-color: #b9a99b;
        }

        .market-option input:checked + label {
            background: #f5f0eb;
            border-color: #72583E;
            box-shadow:
                0 0 0 1px #72583E;
        }

        .market-name {
            display: block;
            color: #3e3026;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .market-days {
            display: block;
            color: #777;
            font-size: 12px;
        }

        .quantity-input {
            width: 150px;
            box-sizing: border-box;
            padding: 11px 12px;
            border: 1px solid #d7cec4;
            border-radius: 8px;
            font-family: inherit;
            font-size: 15px;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #72583E;
            box-shadow:
                0 0 0 2px
                rgba(114, 88, 62, 0.10);
        }

        .stock-note {
            margin-top: 6px;
            font-size: 12px;
            color: #777;
        }

        .error-message {
            margin-bottom: 20px;
            padding: 12px 15px;
            background: #f8e8e5;
            border: 1px solid #dfb9b2;
            border-radius: 8px;
            color: #8a382d;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .submit-button,
        .cancel-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .submit-button {
            border: none;
            background: #72583E;
            color: #fff;
            flex: 1;
        }

        .submit-button:hover {
            background: #5f4833;
        }

        .cancel-button {
            border: 1px solid #d7cec4;
            background: #fff;
            color: #72583E;
        }

        .cancel-button:hover {
            background: #f5f0eb;
        }

        @media (max-width: 750px) {
            .add-cart-card {
                grid-template-columns: 1fr;
            }

            .product-preview {
                min-height: 220px;
            }

            .product-preview img {
                height: 220px;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="add-cart-page">
        <div class="page-header">
            <h1>Add to Cart</h1>
            <p>Choose where you want to pick up this product.</p>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= e($errorMessage) ?>
            </div>
        <?php endif; ?>

        <div class="add-cart-card">
            <div class="product-preview">
                <?php if (!empty($product['image'])): ?>
                    <img
                        src="../<?= e($product['image']) ?>"
                        alt="<?= e($productName) ?>"
                    >
                <?php else: ?>
                    <div class="product-preview-placeholder">
                        <i class="fa-solid fa-image"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <h2><?= e($productName) ?></h2>

                <div class="farmer-name">
                    <i class="fa-solid fa-store"></i>
                    <?= e($farmerName) ?>
                </div>

                <div class="price">
                    $<?= formatPrice($price) ?>
                    <?php if ($unit !== ''): ?>
                        <span style="font-size:14px;color:#777;">
                            / <?= e($unit) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <form
                    method="POST"
                    action="add_to_cart.php?id=<?= $productId ?>"
                >

                <?= csrf_field() ?>
                
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= $productId ?>"
                    >

                    <div class="field">
                        <label>Choose Market</label>

                        <div class="market-options">
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

                                <div class="market-option">
                                    <input
                                        type="radio"
                                        id="market_<?= $marketId ?>"
                                        name="market_id"
                                        value="<?= $marketId ?>"
                                        <?= $isSelected ? 'checked' : '' ?>
                                        <?= $isDisabled ? 'disabled' : '' ?>
                                        required
                                    >

                                    <label for="market_<?= $marketId ?>">
                                        <span class="market-name">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <?= e($market['name']) ?>
                                        </span>

                                        <?php if (!empty($market['operating_days'])): ?>
                                            <span class="market-days">
                                                <?= e($market['operating_days']) ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($isCurrentCartMarket): ?>
                                            <span
                                                style="
                                                    display:block;
                                                    margin-top:5px;
                                                    color:#6F7C59;
                                                    font-size:12px;
                                                    font-weight:600;
                                                "
                                            >
                                                <i class="fa-solid fa-check"></i>
                                                Current cart market
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="quantity">Quantity</label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            class="quantity-input"
                            min="0.1"
                            max="<?= e($stock) ?>"
                            step="0.1"
                            value="<?= e(
                                $_POST['quantity']
                                ?? '1'
                            ) ?>"
                            required
                        >

                        <div class="stock-note">
                            <?php if ($stockStatus === 'unavailable'): ?>
                                Currently unavailable this week.
                            <?php elseif ($stockStatus === 'sold_out'): ?>
                                Sold out for this week.
                            <?php else: ?>
                                Available this week:
                                <?= e($stock) ?>
                                <?= e($unit) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="actions">
                        <a
                            href="products.php"
                            class="cancel-button"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="submit-button"
                            <?= (
                                $stock <= 0 ||
                                $stockStatus !== 'available' ||
                                !$cartMarketAllowed
                            ) ? 'disabled' : '' ?>
                        >
                            <i class="fa-solid fa-cart-plus"></i>

                            <?php if (!$cartMarketAllowed): ?>
                                Cannot Add From This Market
                            <?php elseif ($stockStatus === 'unavailable'): ?>
                                Currently Unavailable
                            <?php elseif ($stockStatus === 'sold_out' || $stock <= 0): ?>
                                Sold Out This Week
                            <?php else: ?>
                                Add to Cart
                            <?php endif; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>


<script src="../assets/js/app.js"></script>
</body>
</html>