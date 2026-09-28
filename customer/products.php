<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['toggle_favorite'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect('customer/products.php');
    }

    $productId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    if (!$productId || !$customerId) {
        redirect('customer/products.php');
    }

    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT customer_id
         FROM favorite_products
         WHERE customer_id = ?
           AND product_id = ?
         LIMIT 1"
    );

    if (!$checkStmt) {
        die('Favorite check prepare failed: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $checkStmt,
        "ii",
        $customerId,
        $productId
    );

    if (!mysqli_stmt_execute($checkStmt)) {
        die('Favorite check execute failed.');
    }

    mysqli_stmt_store_result($checkStmt);

    $exists = mysqli_stmt_num_rows($checkStmt) > 0;

    mysqli_stmt_close($checkStmt);

    if ($exists) {
        $deleteStmt = mysqli_prepare(
            $conn,
            "DELETE FROM favorite_products
             WHERE customer_id = ?
               AND product_id = ?"
        );

        if (!$deleteStmt) {
            die('Favorite delete prepare failed.');
        }

        mysqli_stmt_bind_param(
            $deleteStmt,
            "ii",
            $customerId,
            $productId
        );

        if (!mysqli_stmt_execute($deleteStmt)) {
            die('Favorite delete failed.');
        }

        mysqli_stmt_close($deleteStmt);
    } else {
        $insertStmt = mysqli_prepare(
            $conn,
            "INSERT INTO favorite_products
            (
                customer_id,
                product_id,
                created_at
            )
            VALUES (?, ?, NOW())"
        );

        if (!$insertStmt) {
            die('Favorite insert prepare failed.');
        }

        mysqli_stmt_bind_param(
            $insertStmt,
            "ii",
            $customerId,
            $productId
        );

        if (!mysqli_stmt_execute($insertStmt)) {
            die('Favorite insert failed.');
        }

        mysqli_stmt_close($insertStmt);
    }

    redirect('customer/products.php');
}

$favoriteProducts = [];

$favoriteStmt = mysqli_prepare(
    $conn,
    "SELECT product_id
     FROM favorite_products
     WHERE customer_id = ?"
);

if (!$favoriteStmt) {
    die('Favorite list prepare failed.');
}

mysqli_stmt_bind_param(
    $favoriteStmt,
    "i",
    $customerId
);

if (!mysqli_stmt_execute($favoriteStmt)) {
    die('Favorite list execute failed.');
}

mysqli_stmt_bind_result(
    $favoriteStmt,
    $favoriteProductId
);

while (mysqli_stmt_fetch($favoriteStmt)) {
    $favoriteProducts[] = (int) $favoriteProductId;
}

mysqli_stmt_close($favoriteStmt);

$cartMarketId = null;
$cartMarketName = null;

if (
    isset($_SESSION['cart'])
    && is_array($_SESSION['cart'])
    && !empty($_SESSION['cart'])
) {
    foreach ($_SESSION['cart'] as $cartItem) {
        if (
            isset($cartItem['market_id'])
            && (int) $cartItem['market_id'] > 0
        ) {
            $cartMarketId = (int) $cartItem['market_id'];

            if (
                isset($cartItem['market_name'])
                && $cartItem['market_name'] !== ''
            ) {
                $cartMarketName = $cartItem['market_name'];
            }

            break;
        }
    }
}

if ($cartMarketId === null) {
    $cartFarmerId = null;

    if (
        isset($_SESSION['cart'])
        && is_array($_SESSION['cart'])
        && !empty($_SESSION['cart'])
    ) {
        foreach ($_SESSION['cart'] as $cartItem) {
            if (
                isset($cartItem['farmer_id'])
                && (int) $cartItem['farmer_id'] > 0
            ) {
                $cartFarmerId = (int) $cartItem['farmer_id'];
                break;
            }
        }
    }

    if ($cartFarmerId !== null) {
        $cartMarketStmt = $conn->prepare(
            "SELECT
                m.id,
                m.name
             FROM market_farmer mf
             INNER JOIN markets m
                 ON mf.market_id = m.id
             WHERE mf.farmer_id = ?
               AND m.status = 'active'
             ORDER BY m.name ASC
             LIMIT 1"
        );

        if ($cartMarketStmt) {
            $cartMarketStmt->bind_param(
                "i",
                $cartFarmerId
            );

            if ($cartMarketStmt->execute()) {
                $cartMarketResult = $cartMarketStmt->get_result();
                $cartMarket = $cartMarketResult->fetch_assoc();

                if ($cartMarket) {
                    $cartMarketId = (int) $cartMarket['id'];
                    $cartMarketName = $cartMarket['name'];
                }
            }

            $cartMarketStmt->close();
        }
    }
}

$today = new DateTime();
$weekStart = clone $today;

if ($weekStart->format('N') != 1) {
    $weekStart->modify('monday this week');
}

$weekStartDate = $weekStart->format('Y-m-d');

$moderation_status = M_APPROVED;
$farmer_status = A_APPROVED;

$generateSql = "
    INSERT INTO weekly_stock
    (
        farmer_id,
        product_id,
        week_start,
        planned_quantity,
        actual_quantity,
        status,
        is_active,
        created_at,
        updated_at
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
        END,
        1,
        NOW(),
        NOW()
    FROM weekly_stock_templates wst
    INNER JOIN products p
        ON p.id = wst.product_id
       AND p.farmer_id = wst.farmer_id
    INNER JOIN farmers f
        ON f.id = wst.farmer_id
    WHERE wst.is_active = 1
      AND p.is_available = 1
      AND p.moderation_status = ?
      AND f.approval_status = ?
      AND NOT EXISTS (
          SELECT 1
          FROM weekly_stock ws
          WHERE ws.farmer_id = wst.farmer_id
            AND ws.product_id = wst.product_id
            AND ws.week_start = ?
      )
";

$generateStmt = $conn->prepare($generateSql);

if ($generateStmt) {
    $generateStmt->bind_param(
        "ssss",
        $weekStartDate,
        $moderation_status,
        $farmer_status,
        $weekStartDate
    );

    $generateStmt->execute();
    $generateStmt->close();
}

if (function_exists('sendWeeklyStockReminders')) {
    sendWeeklyStockReminders(
        $conn,
        $weekStartDate
    );
}

$sql = "
    SELECT
        p.id,
        p.farmer_id,
        p.category_id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        p.stock_quantity AS product_stock_quantity,
        ws.actual_quantity AS weekly_actual_quantity,
        ws.status AS weekly_status,
        f.stall_name AS farmer_name,
        c.name AS category_name
    FROM products p
    INNER JOIN farmers f
        ON p.farmer_id = f.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    LEFT JOIN weekly_stock ws
        ON ws.product_id = p.id
       AND ws.farmer_id = p.farmer_id
       AND ws.week_start = ?
    WHERE p.is_available = 1
      AND p.moderation_status = ?
      AND f.approval_status = ?
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Database query failed.');
}

$stmt->bind_param(
    'sss',
    $weekStartDate,
    $moderation_status,
    $farmer_status
);

if (!$stmt->execute()) {
    die('Product query failed.');
}

$result = $stmt->get_result();

$products = [];

while ($row = $result->fetch_assoc()) {
    if ($row['weekly_actual_quantity'] !== null) {
        $row['stock_quantity'] =
            (float) $row['weekly_actual_quantity'];

        $row['stock_status'] =
            $row['weekly_status'] ?? 'available';
    } else {
        $row['stock_quantity'] =
            (float) $row['product_stock_quantity'];

        $row['stock_status'] = 'available';
    }

    $products[] = $row;
}

$stmt->close();

$farmerMarkets = [];

$marketFarmerSql = "
    SELECT
        mf.farmer_id,
        m.id AS market_id,
        m.name AS market_name,
        m.operating_days AS market_days
    FROM market_farmer mf
    INNER JOIN markets m
        ON mf.market_id = m.id
    WHERE m.status = 'active'
    ORDER BY m.name ASC
";

$marketFarmerResult = mysqli_query(
    $conn,
    $marketFarmerSql
);

if ($marketFarmerResult) {
    while ($row = mysqli_fetch_assoc($marketFarmerResult)) {
        $farmerId = (int) $row['farmer_id'];

        if (!isset($farmerMarkets[$farmerId])) {
            $farmerMarkets[$farmerId] = [];
        }

        $farmerMarkets[$farmerId][] = [
            'id' => (int) $row['market_id'],
            'name' => $row['market_name'],
            'operating_days' => $row['market_days'] ?? ''
        ];
    }
}

$categories = [];

$categoryResult = mysqli_query(
    $conn,
    "SELECT id, name
     FROM categories
     WHERE status = 'active'
     ORDER BY name ASC"
);

if ($categoryResult) {
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row;
    }
}

$markets = [];

$marketResult = mysqli_query(
    $conn,
    "SELECT id, name
     FROM markets
     WHERE status = 'active'
     ORDER BY name ASC"
);

if ($marketResult) {
    while ($row = mysqli_fetch_assoc($marketResult)) {
        $markets[] = $row;
    }
}

$cartCount = 0;

if (
    isset($_SESSION['cart'])
    && is_array($_SESSION['cart'])
) {
    $cartCount = count($_SESSION['cart']);
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
    <title>Products | MarketLink</title>

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
        href="../assets/fontawesome/css/all.min.css"
    >
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content customer-products-page">

    <section class="customer-page-hero">
        <div class="customer-page-hero-copy">
            <span class="eyebrow">
                CUSTOMER / MARKETPLACE
            </span>

            <h1>
                Fresh from <em>local hands.</em>
            </h1>

            <p>
                Browse fresh produce from local farmers
                and find what is available at your market.
            </p>
        </div>

        <div class="customer-page-hero-mark">
            01
        </div>
    </section>

    <?php if (
        isset($_GET['added'])
        && $_GET['added'] === '1'
    ): ?>

        <div class="customer-products-notice customer-products-success">
            <span class="customer-products-notice-icon">
                <i data-lucide="CheckCircle"></i>
            </span>

            <div>
                <strong>
                    Product added to your cart.
                </strong>

                <span>
                    You can continue shopping or open your cart
                    when you're ready.
                </span>
            </div>
        </div>

    <?php endif; ?>

    <section class="customer-shopping-section">

        <div class="customer-shopping-note">

            <span class="customer-shopping-note-icon">
                <i data-lucide="shopping-basket"></i>
            </span>

            <div>

                <?php if ($cartMarketName): ?>

                    <strong>
                        Your cart is currently from
                        <?= e($cartMarketName) ?>.
                    </strong>

                    <span>
                        You can only order from one market at a time.
                        Products from other markets are temporarily
                        unavailable for this cart. Complete or clear
                        your current cart before ordering from another
                        market.
                    </span>

                <?php else: ?>

                    <strong>
                        Shopping across local markets.
                    </strong>

                    <span>
                        You can browse products from all markets.
                        Your cart can contain products from only one
                        market per order.
                    </span>

                <?php endif; ?>

            </div>

        </div>

    </section>

    <section class="customer-products-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    01 / DISCOVER
                </span>

                <h2>
                    Browse local <em>produce.</em>
                </h2>

            </div>

            <span class="customer-record-count">
                <?= count($products) ?>
                <?= count($products) === 1 ? 'product' : 'products' ?>
            </span>

        </div>

        <div class="customer-product-filters">

            <div class="customer-product-search">

                <label for="productSearch">
                    Search Products
                </label>

                <div class="customer-product-input-wrap">

                    <i data-lucide="search"></i>

                    <input
                        type="text"
                        id="productSearch"
                        placeholder="Search by product or farmer"
                        autocomplete="off"
                    >

                </div>

            </div>

            <div class="customer-product-filter">

                <label for="productCategory">
                    Category
                </label>

                <select id="productCategory">

                    <option value="">
                        All Categories
                    </option>

                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= (int) $category['id'] ?>"
                        >
                            <?= e($category['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="customer-product-filter">

                <label for="productMarket">
                    Market
                </label>

                <select id="productMarket">

                    <option value="">
                        All Markets
                    </option>

                    <?php foreach ($markets as $market): ?>

                        <option
                            value="<?= (int) $market['id'] ?>"
                        >
                            <?= e($market['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="customer-product-filter">

                <label for="productDay">
                    Market Day
                </label>

                <select id="productDay">

                    <option value="">
                        All Days
                    </option>

                    <option value="Saturday">
                        Saturday
                    </option>

                    <option value="Sunday">
                        Sunday
                    </option>

                </select>

            </div>

            <div class="customer-product-price-filter">

                <label>
                    Price
                </label>

                <div class="customer-product-price-inputs">

                    <input
                        type="number"
                        id="minPrice"
                        placeholder="Min"
                        min="0"
                        step="0.5"
                    >

                    <span>
                        —
                    </span>

                    <input
                        type="number"
                        id="maxPrice"
                        placeholder="Max"
                        min="0"
                        step="0.5"
                    >

                </div>

            </div>

        </div>

        <div
            id="noMatchingProducts"
            class="customer-products-no-match"
        >

            <span class="customer-products-no-match-icon">
                ✦
            </span>

            <strong>
                No products match your filters.
            </strong>

            <span>
                Try changing your search or filter options.
            </span>

        </div>

        <?php if (empty($products)): ?>

            <div class="customer-products-empty">

                <span class="customer-products-empty-mark">
                    ✦
                </span>

                <strong>
                    No products available.
                </strong>

                <span>
                    Fresh products will appear here once
                    farmers have approved listings available.
                </span>

            </div>

        <?php else: ?>

            <div
                class="customer-products-grid"
                id="productGrid"
            >

                <?php foreach ($products as $product): ?>

                    <?php

                    $productId =
                        (int) $product['id'];

                    $productName =
                        $product['name'];

                    $description =
                        $product['description'] ?? '';

                    $price =
                        (float) $product['price'];

                    $unit =
                        $product['unit'] ?? '';

                    $stock =
                        (float) ($product['stock_quantity'] ?? 0);

                    $stockStatus =
                        $product['stock_status'] ?? 'available';

                    $farmerId =
                        (int) $product['farmer_id'];

                    $farmerName =
                        $product['farmer_name']
                        ?? 'Unknown Farmer';

                    $categoryId =
                        (int) ($product['category_id'] ?? 0);

                    $categoryName =
                        $product['category_name']
                        ?? 'Uncategorized';

                    $productMarkets =
                        $farmerMarkets[$farmerId] ?? [];

                    $canAddForCartMarket = true;

                    if ($cartMarketId !== null) {

                        $canAddForCartMarket = false;

                        foreach ($productMarkets as $productMarket) {

                            $productMarketId =
                                (int) ($productMarket['id'] ?? 0);

                            if (
                                $productMarketId
                                === $cartMarketId
                            ) {

                                $canAddForCartMarket = true;

                                break;
                            }
                        }
                    }

                    $marketIds = [];
                    $marketDays = [];

                    foreach ($productMarkets as $productMarket) {

                        $marketIds[] =
                            (int) $productMarket['id'];

                        if (
                            !empty(
                                $productMarket['operating_days']
                            )
                        ) {

                            $days = explode(
                                ',',
                                $productMarket['operating_days']
                            );

                            foreach ($days as $day) {

                                $day = trim($day);

                                if (
                                    $day !== ''
                                    && !in_array(
                                        $day,
                                        $marketDays,
                                        true
                                    )
                                ) {

                                    $marketDays[] = $day;
                                }
                            }
                        }
                    }

                    $isFavorite = in_array(
                        $productId,
                        $favoriteProducts,
                        true
                    );

                    $isSoldOut =
                        $stockStatus === 'sold_out';

                    $isUnavailable =
                        $stockStatus === 'unavailable';

                    $canAddToCart =
                        !$isSoldOut
                        && !$isUnavailable
                        && $stock > 0;

                    ?>

                    <article
                        class="customer-product-card"
                        data-category-id="<?= $categoryId ?>"
                        data-category-name="<?= e($categoryName) ?>"
                        data-farmer-name="<?= e($farmerName) ?>"
                        data-product-name="<?= e($productName) ?>"
                        data-market-ids="<?= e(
                            implode(',', $marketIds)
                        ) ?>"
                        data-market-days="<?= e(
                            implode(',', $marketDays)
                        ) ?>"
                        data-price="<?= $price ?>"
                    >

                        <div class="customer-product-image">

                            <?php if (
                                !empty($product['image'])
                            ): ?>

                                <img
                                    src="../<?= e($product['image']) ?>"
                                    alt="<?= e($productName) ?>"
                                >

                            <?php else: ?>

                                <div class="customer-product-image-empty">

                                    <span>
                                        ✦
                                    </span>

                                    <small>
                                        No image
                                    </small>

                                </div>

                            <?php endif; ?>

                            <form
                                method="POST"
                                action="products.php"
                                class="customer-product-favorite"
                            >

                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $productId ?>"
                                >

                                <button
                                    type="submit"
                                    name="toggle_favorite"
                                    class="<?= $isFavorite
                                        ? 'is-favorite'
                                        : '' ?>"
                                    title="<?= $isFavorite
                                        ? 'Remove from Favorites'
                                        : 'Add to Favorites' ?>"
                                    aria-label="<?= $isFavorite
                                        ? 'Remove from Favorites'
                                        : 'Add to Favorites' ?>"
                                >

                                    <i
                                        data-lucide="heart"
                                        class="<?= $isFavorite ? 'favorite-active' : '' ?>"
                                    ></i>

                                </button>

                            </form>

                        </div>

                        <div class="customer-product-content">

                            <div class="customer-product-category">
                                <?= e($categoryName) ?>
                            </div>

                            <h3 class="customer-product-name">
                                <?= e($productName) ?>
                            </h3>

                            <div class="customer-product-farmer">

                                <i data-lucide=" store"></i>

                                <?= e($farmerName) ?>

                            </div>

                            <p class="customer-product-description">

                                <?= e(
                                    truncateText(
                                        $description,
                                        90
                                    )
                                ) ?>

                            </p>

                            <div class="customer-product-meta">

                                <div class="customer-product-price">

                                    <strong>
                                        $<?= formatPrice($price) ?>
                                    </strong>

                                    <?php if ($unit !== ''): ?>

                                        <span>
                                            / <?= e($unit) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="customer-product-stock">

                                    <?php if ($isUnavailable): ?>

                                        <span class="stock-unavailable">
                                            Currently unavailable
                                        </span>

                                    <?php elseif ($isSoldOut): ?>

                                        <span class="stock-sold-out">
                                            Sold out this week
                                        </span>

                                    <?php else: ?>

                                        <span class="stock-available">

                                            <?= e($stock) ?>

                                            <?php if ($unit !== ''): ?>

                                                <?= e($unit) ?>

                                            <?php endif; ?>

                                            available

                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="customer-product-actions">

                                <?php if (
                                    $canAddToCart
                                    && $canAddForCartMarket
                                ): ?>

                                    <?php if (
                                        $cartMarketId !== null
                                    ): ?>

                                        <a
                                            href="add_to_cart.php?id=<?= $productId ?>&market_id=<?= $cartMarketId ?>"
                                            class="customer-product-add"
                                        >

                                            <i data-lucide="shopping-cart-plus"></i>

                                            Add to Cart

                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="add_to_cart.php?id=<?= $productId ?>"
                                            class="customer-product-add"
                                        >

                                            <i data-lucide="shopping-cart-plus"></i>

                                            Add to Cart

                                        </a>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <button
                                        type="button"
                                        class="customer-product-add customer-product-disabled <?= !$canAddForCartMarket
                                            ? 'different-market'
                                            : '' ?>"
                                        disabled
                                    >

                                        <i
                                            data-lucide=" <?= !$canAddForCartMarket
                                                ? 'store-slash'
                                                : 'fa-box-open' ?>"
                                        ></i>

                                        <?php if (
                                            !$canAddForCartMarket
                                        ): ?>

                                            Different Market

                                        <?php elseif (
                                            $isUnavailable
                                        ): ?>

                                            Currently Unavailable

                                        <?php elseif (
                                            $isSoldOut
                                        ): ?>

                                            Sold Out This Week

                                        <?php else: ?>

                                            Out of Stock

                                        <?php endif; ?>

                                    </button>

                                <?php endif; ?>

                                <a
                                    href="product_details.php?id=<?= $productId ?>"
                                    class="customer-product-details"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <div
                id="productPagination"
                class="product-pagination"
            ></div>

        <?php endif; ?>

    </section>

</main>

<?php if ($cartCount > 0): ?>

    <a
        href="cart.php"
        class="customer-floating-cart"
    >

        <i data-lucide="shopping-cart"></i>

        <span>
            Cart
        </span>

        <strong>
            <?= $cartCount ?>
        </strong>

    </a>

<?php endif; ?>

<script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

<script src="../assets/js/app.js"></script>

<script src="../assets/js/customer-products.js"></script>

</body>
</html>