<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int)getUserId();


// ==========================================================
// Toggle Favorite Product
// ==========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['toggle_favorite'])
) {
    $productId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    if (!$productId || !$customerId) {
        header('Location: products.php');
        exit;
    }

    // ------------------------------------------------------
    // Check if product already exists in favorites
    // ------------------------------------------------------

    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT customer_id
         FROM favorite_products
         WHERE customer_id = ?
           AND product_id = ?
         LIMIT 1"
    );

    if (!$checkStmt) {
        die(
            'Favorite check prepare failed: '
            . mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $checkStmt,
        "ii",
        $customerId,
        $productId
    );

    if (!mysqli_stmt_execute($checkStmt)) {
        die(
            'Favorite check execute failed: '
            . mysqli_stmt_error($checkStmt)
        );
    }

    mysqli_stmt_store_result($checkStmt);

    $exists = mysqli_stmt_num_rows($checkStmt) > 0;

    mysqli_stmt_close($checkStmt);


    // ------------------------------------------------------
    // Remove favorite
    // ------------------------------------------------------

    if ($exists) {

        $deleteStmt = mysqli_prepare(
            $conn,
            "DELETE FROM favorite_products
             WHERE customer_id = ?
               AND product_id = ?"
        );

        if (!$deleteStmt) {
            die(
                'Favorite delete prepare failed: '
                . mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $deleteStmt,
            "ii",
            $customerId,
            $productId
        );

        if (!mysqli_stmt_execute($deleteStmt)) {
            die(
                'Favorite delete failed: '
                . mysqli_stmt_error($deleteStmt)
            );
        }

        mysqli_stmt_close($deleteStmt);


    // ------------------------------------------------------
    // Add favorite
    // ------------------------------------------------------

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
            die(
                'Favorite insert prepare failed: '
                . mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $insertStmt,
            "ii",
            $customerId,
            $productId
        );

        if (!mysqli_stmt_execute($insertStmt)) {
            die(
                'Favorite insert failed: '
                . mysqli_stmt_error($insertStmt)
            );
        }

        mysqli_stmt_close($insertStmt);
    }


    header('Location: products.php');
    exit;
}


// ==========================================================
// Get Current Customer Favorite Products
// ==========================================================

$favoriteProducts = [];

$favoriteStmt = mysqli_prepare(
    $conn,
    "SELECT product_id
     FROM favorite_products
     WHERE customer_id = ?"
);

if (!$favoriteStmt) {
    die(
        'Favorite list prepare failed: '
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $favoriteStmt,
    "i",
    $customerId
);

if (!mysqli_stmt_execute($favoriteStmt)) {
    die(
        'Favorite list execute failed: '
        . mysqli_stmt_error($favoriteStmt)
    );
}

mysqli_stmt_bind_result(
    $favoriteStmt,
    $favoriteProductId
);

while (mysqli_stmt_fetch($favoriteStmt)) {
    $favoriteProducts[] = (int)$favoriteProductId;
}

mysqli_stmt_close($favoriteStmt);


// ==========================================================
// Get Current Cart Market
// ==========================================================

$cartMarketId = null;
$cartMarketName = null;

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

            if (
                isset($cartItem['market_name'])
                && $cartItem['market_name'] !== ''
            ) {
                $cartMarketName =
                    $cartItem['market_name'];
            }

            break;
        }
    }
}


// ----------------------------------------------------------
// Backward compatibility for old cart items that only have
// farmer_id
// ----------------------------------------------------------

if ($cartMarketId === null) {

    $cartFarmerId = null;

    if (
        isset($_SESSION['cart']) &&
        is_array($_SESSION['cart']) &&
        !empty($_SESSION['cart'])
    ) {
        foreach ($_SESSION['cart'] as $cartItem) {

            if (
                isset($cartItem['farmer_id']) &&
                (int)$cartItem['farmer_id'] > 0
            ) {
                $cartFarmerId =
                    (int)$cartItem['farmer_id'];

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

                $cartMarketResult =
                    $cartMarketStmt->get_result();

                $cartMarket =
                    $cartMarketResult->fetch_assoc();

                if ($cartMarket) {

                    $cartMarketId =
                        (int)$cartMarket['id'];

                    $cartMarketName =
                        $cartMarket['name'];
                }
            }

            $cartMarketStmt->close();
        }
    }
}


// ==========================================================
// Determine Current Week
// ==========================================================

$today = new DateTime();

$weekStart = clone $today;

if ($weekStart->format('N') != 1) {
    $weekStart->modify('monday this week');
}

$weekStartDate =
    $weekStart->format('Y-m-d');


// ==========================================================
// Lazy Generate Weekly Stock
// ==========================================================
//
// Active weekly stock templates automatically create a row
// for the current week if one does not already exist.
//
// Existing weekly rows are NEVER overwritten.
// This means farmers can adjust the current week's quantity
// without changing their recurring template.
//

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


// ==========================================================
// Send Weekly Stock Reminders
// ==========================================================

if (function_exists('sendWeeklyStockReminders')) {
    sendWeeklyStockReminders(
        $conn,
        $weekStartDate
    );
}


// ==========================================================
// Get Products
// ==========================================================
//
// IMPORTANT:
//
// Do NOT join market_farmer here.
//
// A farmer can belong to multiple markets, so joining it
// directly would create duplicate product rows.
//
// Products are loaded once.
// Farmer markets are loaded separately below.
//

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
    die(
        'Database Error: '
        . $conn->error
    );
}

$moderation_status = M_APPROVED;
$farmer_status = A_APPROVED;

$stmt->bind_param(
    'sss',
    $weekStartDate,
    $moderation_status,
    $farmer_status
);

if (!$stmt->execute()) {
    die(
        'Product query failed: '
        . $stmt->error
    );
}

$result = $stmt->get_result();

$products = [];

while ($row = $result->fetch_assoc()) {

    // ------------------------------------------------------
    // Determine effective weekly stock
    // ------------------------------------------------------

    if (
        $row['weekly_actual_quantity'] !== null
    ) {

        $row['stock_quantity'] =
            (float)$row['weekly_actual_quantity'];

        $row['stock_status'] =
            $row['weekly_status']
            ?? 'available';

    } else {

        // No weekly stock row exists.
        // Fall back to the product's normal stock.

        $row['stock_quantity'] =
            (float)$row['product_stock_quantity'];

        $row['stock_status'] =
            'available';
    }

    $products[] = $row;
}

$stmt->close();


// ==========================================================
// Get Markets For All Farmers
// ==========================================================
//
// Creates:
//
// $farmerMarkets[farmer_id] = [
//     [
//         'id' => 1,
//         'name' => 'Market A',
//         'operating_days' => 'Saturday'
//     ],
//     [
//         'id' => 2,
//         'name' => 'Market B',
//         'operating_days' => 'Saturday,Sunday'
//     ]
// ]
//
// Each product still appears only once.
//

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

    while ($row = mysqli_fetch_assoc(
        $marketFarmerResult
    )) {

        $farmerId =
            (int)$row['farmer_id'];

        if (!isset($farmerMarkets[$farmerId])) {
            $farmerMarkets[$farmerId] = [];
        }

        $farmerMarkets[$farmerId][] = [
            'id' =>
                (int)$row['market_id'],

            'name' =>
                $row['market_name'],

            'operating_days' =>
                $row['market_days'] ?? ''
        ];
    }
}


// ==========================================================
// Get Categories
// ==========================================================

$categories = [];

$categoryResult = mysqli_query(
    $conn,
    "SELECT id, name
     FROM categories
     WHERE status = 'active'
     ORDER BY name ASC"
);

if ($categoryResult) {

    while ($row = mysqli_fetch_assoc(
        $categoryResult
    )) {
        $categories[] = $row;
    }
}


// ==========================================================
// Get Markets
// ==========================================================

$markets = [];

$marketResult = mysqli_query(
    $conn,
    "SELECT id, name
     FROM markets
     WHERE status = 'active'
     ORDER BY name ASC"
);

if ($marketResult) {

    while ($row = mysqli_fetch_assoc(
        $marketResult
    )) {
        $markets[] = $row;
    }
}


// ==========================================================
// Cart Count
// ==========================================================

$cartCount = 0;

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart'])
) {
    $cartCount =
        count($_SESSION['cart']);
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
        Products - MarketLink
    </title>

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

        /* =====================================================
           Page Header
        ===================================================== */

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
            line-height: 1.2;
            font-weight: 700;
            color: #222;
        }

        .page-header p {
            margin: 0;
            color: #666;
            font-size: 15px;
            line-height: 1.5;
        }


        /* =====================================================
           Shopping Restriction Notice
        ===================================================== */

        .shopping-note {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 25px;
            padding: 14px 18px;
            background: #f5f0eb;
            border: 1px solid #d7cec4;
            border-radius: 10px;
            color: #5f4833;
            font-size: 14px;
            line-height: 1.5;
        }

        .shopping-note i {
            margin-top: 2px;
            font-size: 16px;
            flex-shrink: 0;
        }

        .shopping-note strong {
            color: #72583E;
        }


        /* =====================================================
           Product Filters
        ===================================================== */

        .product-filters {
            display: grid;
            grid-template-columns:
                minmax(220px, 1.5fr)
                repeat(3, minmax(150px, 1fr))
                minmax(170px, 1.1fr);

            gap: 14px;
            margin-bottom: 24px;
            padding: 18px;
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.03);
        }

        .product-search,
        .product-filter-group,
        .product-price-filter {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .product-filters label {
            font-size: 13px;
            font-weight: 600;
            color: #444;
        }

        .product-filters input,
        .product-filters select {
            width: 100%;
            box-sizing: border-box;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            background: #ffffff;
            color: #333;
            font-family: inherit;
            font-size: 14px;
            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .product-filters input::placeholder {
            color: #999;
        }

        .product-filters input:focus,
        .product-filters select:focus {
            border-color: #a38d78;
            box-shadow:
                0 0 0 3px
                rgba(114, 88, 62, 0.10);
        }

        .price-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }


        /* =====================================================
           No Matching Products
        ===================================================== */

        #noMatchingProducts {
            margin-bottom: 24px;
            padding: 28px 20px;
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            text-align: center;
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }


        /* =====================================================
           Products Grid
        ===================================================== */

        .products-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fill,
                    minmax(250px, 1fr)
                );
            gap: 24px;
        }


        /* =====================================================
           Product Card
        ===================================================== */

        .product-card {
            position: relative;
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow:
                0 8px 20px
                rgba(0, 0, 0, 0.08);
        }


        /* =====================================================
           Product Image
        ===================================================== */

        .product-image-container {
            width: 100%;
            height: 220px;
            background: #f5f5f5;
        }


        /* =====================================================
           Favorite Form
        ===================================================== */

        .favorite-form {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
            margin: 0;
        }


        /* =====================================================
           Favorite Button
        ===================================================== */

        .favorite-button {
            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: none;
            border-radius: 50%;

            background: #ffffff;

            cursor: pointer;

            font-size: 20px;
            padding: 0;
            margin: 0;

            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, 0.10);

            transition: 0.2s ease;
        }

        .favorite-button.empty {
            color: #555555;
        }

        .favorite-button.filled {
            color: #e53935;
        }

        .favorite-button:hover {
            transform: scale(1.08);
        }


        /* =====================================================
           Product Information
        ===================================================== */

        .product-info {
            padding: 18px;
        }

        .product-name {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 600;
            color: #222;
        }

        .product-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            min-height: 42px;
            margin-bottom: 14px;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .product-unit {
            font-size: 14px;
            color: #666;
        }

        .product-stock {
            font-size: 14px;
            color: #555;
            margin-bottom: 16px;
        }


        /* =====================================================
           Farmer
        ===================================================== */

        .product-farmer {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 10px;
            color: #72583E;
            font-size: 13px;
            font-weight: 600;
        }

        .product-farmer i {
            font-size: 12px;
        }


        /* =====================================================
           Product Actions
        ===================================================== */

        .product-actions {
            display: flex;
            flex-direction: column;
            gap: 9px;
            margin-top: 18px;
        }

        .add-to-cart-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            width: 100%;
            box-sizing: border-box;

            padding: 11px 15px;

            border: none;
            border-radius: 7px;

            background: #72583E;
            color: #ffffff;

            font-family: inherit;
            font-size: 14px;
            font-weight: 600;

            text-decoration: none;
            cursor: pointer;

            transition: 0.2s ease;
        }

        .add-to-cart-button:hover {
            background: #5f4833;
            transform: translateY(-1px);
        }


        /* =====================================================
           Disabled / Blocked Button
        ===================================================== */

        .add-to-cart-button.disabled-button {
            background: #eeeeee;
            color: #888888;
            cursor: not-allowed;
        }

        .add-to-cart-button.disabled-button:hover {
            background: #eeeeee;
            transform: none;
        }


        /* =====================================================
           Different Market
        ===================================================== */

        .different-market-button {
            background: #eee9e4;
            color: #806b57;
        }

        .different-market-button:hover {
            background: #eee9e4;
            transform: none;
        }


        /* =====================================================
           View Details Button
        ===================================================== */

        .view-details-button {
            display: block;
            width: 100%;
            box-sizing: border-box;

            text-align: center;
            text-decoration: none;

            background: transparent;
            color: #72583E;

            border: 1px solid #d7cec4;

            padding: 10px 15px;

            border-radius: 7px;

            transition: 0.2s ease;
        }

        .view-details-button:hover {
            background: #f5f0eb;
        }


        /* =====================================================
           Empty Products
        ===================================================== */

        .empty-products {
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            color: #666;
        }


        /* =====================================================
           Floating Cart
        ===================================================== */

        .floating-cart {
            position: fixed;
            right: 28px;
            bottom: 28px;
            z-index: 1000;

            display: flex;
            align-items: center;
            gap: 9px;

            padding: 13px 18px;

            background: #72583E;
            color: #ffffff;

            border-radius: 999px;
            text-decoration: none;

            font-size: 14px;
            font-weight: 600;

            box-shadow:
                0 8px 24px
                rgba(62, 48, 38, 0.20);

            transition:
                transform 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .floating-cart:hover {
            background: #5f4833;
            color: #ffffff;

            transform: translateY(-3px);

            box-shadow:
                0 12px 30px
                rgba(62, 48, 38, 0.25);
        }

        .floating-cart i {
            font-size: 15px;
        }

        .floating-cart-count {
            min-width: 22px;
            height: 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0 6px;
            box-sizing: border-box;

            background: #DFA62F;
            color: #3E3026;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;
        }


        /* =====================================================
           Mobile
        ===================================================== */

        @media (max-width: 1100px) {

            .product-filters {
                grid-template-columns:
                    repeat(2, minmax(180px, 1fr));
            }

            .product-search {
                grid-column: span 2;
            }
        }

        @media (max-width: 700px) {

            .products-grid {
                grid-template-columns: 1fr;
            }

            .shopping-note {
                font-size: 13px;
            }

            .product-filters {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 15px;
            }

            .product-search {
                grid-column: auto;
            }

            .price-inputs {
                grid-template-columns: 1fr 1fr;
            }

            .floating-cart {
                right: 16px;
                bottom: 16px;
                padding: 12px 16px;
            }
        }

    </style>

</head>


<body>


<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">


    <!-- =====================================================
         Page Header
    ====================================================== -->

    <div class="page-header">

        <h1>
            Products
        </h1>

        <p>
            Browse fresh products available from our farmers.
        </p>

    </div>


    <?php if (
        isset($_GET['added'])
        && $_GET['added'] === '1'
    ): ?>

        <div
            class="shopping-note"
            style="margin-bottom: 20px;"
        >

            <i class="fa-solid fa-circle-check"></i>

            <span>
                Product added to your cart.
                You can continue shopping or open your cart
                when you're ready.
            </span>

        </div>

    <?php endif; ?>



    <div class="shopping-note">

        <i class="fa-solid fa-basket-shopping"></i>

        <span>

            <?php if ($cartMarketName): ?>

                Your cart is currently from

                <strong>
                    <?= e($cartMarketName) ?>
                </strong>.

                You can only order from one market at a time.

                Products from other markets are temporarily
                unavailable for this cart.

                Complete or clear your current cart before
                ordering from another market.

            <?php else: ?>

                You can browse products from all markets.

                Your cart can contain products from only one
                market per order.

            <?php endif; ?>

        </span>

    </div>


    <!-- =====================================================
         Product Filters
    ====================================================== -->

    <div class="product-filters">

        <div class="product-search">

            <label for="productSearch">
                Search Products
            </label>

            <input
                type="text"
                id="productSearch"
                placeholder="Search by product or farmer"
                autocomplete="off"
            >

        </div>


        <div class="product-filter-group">

            <label for="productCategory">
                Category
            </label>

            <select id="productCategory">

                <option value="">
                    All Categories
                </option>

                <?php foreach ($categories as $category): ?>

                    <option
                        value="<?= (int)$category['id'] ?>"
                    >
                        <?= e($category['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="product-filter-group">

            <label for="productMarket">
                Market
            </label>

            <select id="productMarket">

                <option value="">
                    All Markets
                </option>

                <?php foreach ($markets as $market): ?>

                    <option
                        value="<?= (int)$market['id'] ?>"
                    >
                        <?= e($market['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="product-filter-group">

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


        <div class="product-price-filter">

            <label>
                Price
            </label>

            <div class="price-inputs">

                <input
                    type="number"
                    id="minPrice"
                    placeholder="Min"
                    min="0"
                    step="0.01"
                >

                <input
                    type="number"
                    id="maxPrice"
                    placeholder="Max"
                    min="0"
                    step="0.01"
                >

            </div>

        </div>

    </div>


    <div
        id="noMatchingProducts"
        style="display: none;"
    >
        No products match your filters.
    </div>


    <?php if (empty($products)): ?>

        <div class="empty-products">

            No products are available at the moment.

        </div>

    <?php else: ?>


        <div class="products-grid">


            <?php foreach ($products as $product): ?>

                <?php

                $productId =
                    (int)$product['id'];

                $productName =
                    $product['name'];

                $description =
                    $product['description'] ?? '';

                $price =
                    (float)$product['price'];

                $unit =
                    $product['unit'] ?? '';

                $stock =
                    (float)($product['stock_quantity'] ?? 0);

                $stockStatus =
                    $product['stock_status']
                    ?? 'available';

                $farmerId =
                    (int)$product['farmer_id'];

                $farmerName =
                    $product['farmer_name']
                    ?? 'Unknown Farmer';

                $categoryId =
                    (int)($product['category_id'] ?? 0);


                // --------------------------------------------------
                // Get ALL markets for this farmer
                // --------------------------------------------------

                $productMarkets =
                    $farmerMarkets[$farmerId] ?? [];


                // --------------------------------------------------
                // Check whether this product can use the
                // market already assigned to the cart.
                //
                // Empty cart:
                //     true
                //
                // Cart = Market A
                // Product = Market A:
                //     true
                //
                // Cart = Market A
                // Product = Market B only:
                //     false
                //
                // Cart = Market A
                // Product = Market A + Market B:
                //     true
                // --------------------------------------------------

                $canAddForCartMarket = true;

                if ($cartMarketId !== null) {

                    $canAddForCartMarket = false;

                    foreach (
                        $productMarkets
                        as $productMarket
                    ) {

                        $productMarketId =
                            (int)(
                                $productMarket['id']
                                ?? 0
                            );

                        if (
                            $productMarketId
                            === $cartMarketId
                        ) {

                            $canAddForCartMarket = true;

                            break;
                        }
                    }
                }


                // --------------------------------------------------
                // Build filter data
                // --------------------------------------------------

                $marketIds = [];

                $marketDays = [];

                foreach (
                    $productMarkets
                    as $productMarket
                ) {

                    $marketIds[] =
                        (int)$productMarket['id'];

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

                                $marketDays[] =
                                    $day;
                            }
                        }
                    }
                }


                // --------------------------------------------------
                // Favorite
                // --------------------------------------------------

                $isFavorite =
                    in_array(
                        $productId,
                        $favoriteProducts,
                        true
                    );


                // --------------------------------------------------
                // Weekly Stock
                // --------------------------------------------------

                $isSoldOut =
                    $stockStatus === 'sold_out';

                $isUnavailable =
                    $stockStatus === 'unavailable';

                $canAddToCart =
                    !$isSoldOut
                    && !$isUnavailable
                    && $stock > 0;

                ?>


                <div
                    class="product-card"

                    data-category-id="<?= $categoryId ?>"

                    data-market-ids="<?= e(
                        implode(',', $marketIds)
                    ) ?>"

                    data-market-days="<?= e(
                        implode(',', $marketDays)
                    ) ?>"

                    data-price="<?= $price ?>"
                >


                    <!-- =================================================
                         Favorite Button
                    ================================================== -->

                    <form
                        method="POST"
                        action="products.php"
                        class="favorite-form"
                    >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= $productId ?>"
                        >

                        <button
                            type="submit"
                            name="toggle_favorite"
                            class="favorite-button
                                <?= $isFavorite
                                    ? 'filled'
                                    : 'empty'
                                ?>"
                            title="<?= $isFavorite
                                ? 'Remove from Favorites'
                                : 'Add to Favorites'
                            ?>"
                            aria-label="<?= $isFavorite
                                ? 'Remove from Favorites'
                                : 'Add to Favorites'
                            ?>"
                        >

                            <?php if ($isFavorite): ?>

                                <i
                                    class="fa-solid fa-heart"
                                ></i>

                            <?php else: ?>

                                <i
                                    class="fa-regular fa-heart"
                                ></i>

                            <?php endif; ?>

                        </button>

                    </form>


                    <!-- =================================================
                         Product Image
                    ================================================== -->

                    <div class="product-image-container">

                        <?php if (
                            !empty($product['image'])
                        ): ?>

                            <img
                                src="../<?= e(
                                    $product['image']
                                ) ?>"
                                alt="<?= e(
                                    $productName
                                ) ?>"
                                style="
                                    width: 100%;
                                    height: 100%;
                                    object-fit: cover;
                                    display: block;
                                "
                            >

                        <?php else: ?>

                            <div
                                style="
                                    width: 100%;
                                    height: 100%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: #999;
                                "
                            >
                                No Image
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- =================================================
                         Product Information
                    ================================================== -->

                    <div class="product-info">


                        <h2 class="product-name">

                            <?= e($productName) ?>

                        </h2>


                        <!-- Farmer -->

                        <div class="product-farmer">

                            <i
                                class="fa-solid fa-store"
                            ></i>

                            <?= e($farmerName) ?>

                        </div>


                        <!-- Description -->

                        <div class="product-description">

                            <?= e(
                                truncateText(
                                    $description,
                                    90
                                )
                            ) ?>

                        </div>


                        <!-- Price -->

                        <div class="product-price">

                            $<?= formatPrice($price) ?>

                            <?php if ($unit !== ''): ?>

                                <span class="product-unit">

                                    /
                                    <?= e($unit) ?>

                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Stock -->

                        <div class="product-stock">

                            <?php if ($isUnavailable): ?>

                                Stock:
                                Currently unavailable

                            <?php elseif ($isSoldOut): ?>

                                Stock:
                                Sold out this week

                            <?php else: ?>

                                Stock:
                                <?= e($stock) ?>

                                <?php if ($unit !== ''): ?>

                                    <?= e($unit) ?>

                                <?php endif; ?>

                            <?php endif; ?>

                        </div>


                        <!-- =================================================
                             Actions
                        ================================================== -->

                        <div class="product-actions">


                            <?php if (
                                $canAddToCart
                                && $canAddForCartMarket
                            ): ?>


                                <!-- -----------------------------------------
                                     Add To Cart
                                ------------------------------------------ -->

                                <?php if (
                                    $cartMarketId !== null
                                ): ?>

                                    <a
                                        href="add_to_cart.php?id=<?= $productId ?>&market_id=<?= $cartMarketId ?>"
                                        class="add-to-cart-button"
                                    >

                                        <i
                                            class="fa-solid fa-cart-plus"
                                        ></i>

                                        Add to Cart

                                    </a>

                                <?php else: ?>

                                    <a
                                        href="add_to_cart.php?id=<?= $productId ?>"
                                        class="add-to-cart-button"
                                    >

                                        <i
                                            class="fa-solid fa-cart-plus"
                                        ></i>

                                        Add to Cart

                                    </a>

                                <?php endif; ?>


                            <?php else: ?>


                                <!-- -----------------------------------------
                                     Blocked
                                ------------------------------------------ -->

                                <button
                                    type="button"
                                    class="add-to-cart-button
                                        disabled-button
                                        <?= !$canAddForCartMarket
                                            ? 'different-market-button'
                                            : ''
                                        ?>"
                                    disabled
                                >

                                    <i
                                        class="fa-solid
                                            <?= !$canAddForCartMarket
                                                ? 'fa-store-slash'
                                                : 'fa-box-open'
                                            ?>"
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


                            <!-- View Details -->

                            <a
                                href="product_details.php?id=<?= $productId ?>"
                                class="view-details-button"
                            >

                                View Details

                            </a>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>

    <?php endif; ?>


</main>


<?php if ($cartCount > 0): ?>

    <a
        href="cart.php"
        class="floating-cart"
    >

        <i
            class="fa-solid fa-cart-shopping"
        ></i>

        <span>
            Cart
        </span>

        <span class="floating-cart-count">
            <?= $cartCount ?>
        </span>

    </a>

<?php endif; ?>


<script src="../assets/js/app.js"></script>
<script src="../assets/js/customer-products.js"></script>


</body>

</html>