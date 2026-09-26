<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();


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
    $favoriteProducts[] = (int) $favoriteProductId;
}

mysqli_stmt_close($favoriteStmt);


// ==========================================================
// Get Current Cart Farmer
// ==========================================================

$cartFarmerId = null;
$cartFarmerName = null;

if (
    isset($_SESSION['cart'])
    &&
    is_array($_SESSION['cart'])
    &&
    !empty($_SESSION['cart'])
) {

    foreach ($_SESSION['cart'] as $cartItem) {

        if (
            isset($cartItem['farmer_id'])
            &&
            (int) $cartItem['farmer_id'] > 0
        ) {

            $cartFarmerId = (int) $cartItem['farmer_id'];

            break;
        }
    }
}


// ----------------------------------------------------------
// Get current cart farmer's name
// ----------------------------------------------------------

if ($cartFarmerId !== null) {

    $cartFarmerStmt = $conn->prepare(
        "SELECT stall_name
         FROM farmers
         WHERE id = ?
         LIMIT 1"
    );

    if (!$cartFarmerStmt) {
        die(
            'Cart farmer prepare failed: '
            . $conn->error
        );
    }

    $cartFarmerStmt->bind_param(
        "i",
        $cartFarmerId
    );

    if (!$cartFarmerStmt->execute()) {
        die(
            'Cart farmer execute failed: '
            . $cartFarmerStmt->error
        );
    }

    $cartFarmerResult =
        $cartFarmerStmt->get_result();

    $cartFarmer =
        $cartFarmerResult->fetch_assoc();

    if ($cartFarmer) {
        $cartFarmerName =
            $cartFarmer['stall_name'];
    }

    $cartFarmerStmt->close();
}


// ==========================================================
// Get Products
// ==========================================================

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
        p.stock_quantity,

        f.stall_name AS farmer_name,

        c.name AS category_name,

        m.id AS market_id,
        m.name AS market_name,
        m.operating_days AS market_days

    FROM products p

    INNER JOIN farmers f
        ON p.farmer_id = f.id

    LEFT JOIN categories c
        ON p.category_id = c.id

    LEFT JOIN market_farmer mf
        ON p.farmer_id = mf.farmer_id

    LEFT JOIN markets m
        ON mf.market_id = m.id

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
    'ss',
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
    $products[] = $row;
}

$stmt->close();

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

    while ($row = mysqli_fetch_assoc($categoryResult)) {

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

    while ($row = mysqli_fetch_assoc($marketResult)) {

        $markets[] = $row;
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
            margin-bottom: 20px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .page-header p {
            margin: 0;
            color: #666;
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
           Empty Image Area
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

        .add-to-cart-form {
            width: 100%;
            margin: 0;
        }

        .add-to-cart-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px 15px;
            border: none;
            border-radius: 7px;
            background: #72583E;
            color: #ffffff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .add-to-cart-button:hover {
            background: #5f4833;
            transform: translateY(-1px);
        }


        /* =====================================================
           Switch Market Button
        ===================================================== */

        .switch-market-button {
            background: #8A7562;
        }

        .switch-market-button:hover {
            background: #72583E;
            transform: translateY(-1px);
        }


        /* =====================================================
           Out Of Stock
        ===================================================== */

        .add-to-cart-button.out-of-stock {
            background: #eeeeee;
            color: #888888;
            cursor: not-allowed;
        }

        .add-to-cart-button.out-of-stock:hover {
            background: #eeeeee;
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
           Mobile
        ===================================================== */

        @media (max-width: 700px) {

            .products-grid {
                grid-template-columns: 1fr;
            }

            .shopping-note {
                font-size: 13px;
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


    <!-- =====================================================
         Shopping Restriction Notice
    ====================================================== -->

    <div class="shopping-note">

        <i class="fa-solid fa-basket-shopping"></i>

        <span>

            <?php if ($cartFarmerName): ?>

                Your cart is currently from

                <strong>
                    <?= e($cartFarmerName) ?>
                </strong>.

                You can only order from one market at a time.

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
         Products
    ====================================================== -->

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

                <option value="<?= (int) $category['id'] ?>">
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

                <option value="<?= (int) $market['id'] ?>">
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
                    (float) $product['stock_quantity'];

                $farmerId =
                    (int) $product['farmer_id'];

                $farmerName =
                    $product['farmer_name']
                    ?? 'Unknown Market';

                $categoryId =
    (int) ($product['category_id'] ?? 0);

$marketId =
    (int) ($product['market_id'] ?? 0);

$marketDays =
    $product['market_days'] ?? '';


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
                // Market Check
                // --------------------------------------------------

                $sameMarket =
                    $cartFarmerId === null
                    || $cartFarmerId === $farmerId;


                // --------------------------------------------------
                // Quick Add uses 1 kg
                // --------------------------------------------------

                $canAddToCart =
                    $sameMarket
                    && $stock >= 1;

                ?>


                    <div
                        class="product-card"
                        data-category-id="<?= $categoryId ?>"
                        data-market-id="<?= $marketId ?>"
                        data-market-days="<?= e($marketDays) ?>"
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
                            class="favorite-button <?= $isFavorite ? 'filled' : 'empty' ?>"
                            title="<?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                            aria-label="<?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                        >

                            <?php if ($isFavorite): ?>

                                <i class="fa-solid fa-heart"></i>

                            <?php else: ?>

                                <i class="fa-regular fa-heart"></i>

                            <?php endif; ?>

                        </button>

                    </form>


                    <!-- =================================================
                         Product Image
                    ================================================== -->

                    <div class="product-image-container">
                    </div>


                    <!-- =================================================
                         Product Information
                    ================================================== -->

                    <div class="product-info">


                        <h2 class="product-name">

                            <?= e($productName) ?>

                        </h2>


                        <!-- Farmer / Market -->

                        <div class="product-farmer">

                            <i class="fa-solid fa-store"></i>

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

                            Stock:
                            <?= e($stock) ?>

                            <?php if ($unit !== ''): ?>

                                <?= e($unit) ?>

                            <?php endif; ?>

                        </div>


                        <!-- =================================================
                             Actions
                        ================================================== -->

                        <div class="product-actions">


                            <?php if ($sameMarket && $stock >= 1): ?>

                                <!-- Same Market / Can Add -->

                                <a
                                    href="add_to_cart.php?id=<?= $productId ?>"
                                    class="add-to-cart-button"
                                >

                                    <i class="fa-solid fa-cart-plus"></i>

                                    Add to Cart

                                </a>


                            <?php elseif (!$sameMarket): ?>

                                <!-- Different Market -->

                                <form
                                    method="POST"
                                    action="../actions/clear_cart.php"
                                    class="add-to-cart-form"
                                    onsubmit="return confirmSwitchMarket(
                                        <?= htmlspecialchars(
                                            json_encode($cartFarmerName),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>,
                                        <?= htmlspecialchars(
                                            json_encode($farmerName),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    );"
                                >

                                    <input
                                        type="hidden"
                                        name="return_to"
                                        value="../customer/products.php"
                                    >

                                    <button
                                        type="submit"
                                        class="add-to-cart-button switch-market-button"
                                        title="Clear your current cart and switch markets"
                                    >

                                        <i class="fa-solid fa-right-left"></i>

                                        Switch Market

                                    </button>

                                </form>


                            <?php else: ?>

                                <!-- Out Of Stock / Less Than 1kg -->

                                <button
                                    type="button"
                                    class="add-to-cart-button out-of-stock"
                                    disabled
                                >

                                    <i class="fa-solid fa-box-open"></i>

                                    Out of Stock

                                </button>

                            <?php endif; ?>


                            <!-- =================================================
                                 View Details
                                 from=products is the important change
                            ================================================== -->

                            <a
                                href="product_details.php?id=<?= $productId ?>&from=products"
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


<script src="../assets/js/app.js"></script>


<script>

function confirmSwitchMarket(currentMarket, newMarket) {

    return confirm(
        'Your cart currently contains products from "' +
        currentMarket +
        '".\n\n' +

        'To add a product from "' +
        newMarket +
        '", your current cart needs to be cleared.\n\n' +

        'This will remove all products currently in your cart.\n\n' +

        'Do you want to clear your cart and switch markets?'
    );

}

const productSearch =
    document.getElementById('productSearch');

const productCategory =
    document.getElementById('productCategory');

const productMarket =
    document.getElementById('productMarket');

const productDay =
    document.getElementById('productDay');

const minPrice =
    document.getElementById('minPrice');

const maxPrice =
    document.getElementById('maxPrice');

const productCards =
    document.querySelectorAll('.product-card');

const noMatchingProducts =
    document.getElementById(
        'noMatchingProducts'
    );

function applyProductFilters() {

    const searchTerm =
        productSearch.value.trim().toLowerCase();

    const selectedCategory =
        productCategory.value;

    const selectedMarket =
        productMarket.value;

    const selectedDay =
        productDay.value;

    const min =
        minPrice.value === ''
            ? null
            : parseFloat(minPrice.value);

    const max =
        maxPrice.value === ''
            ? null
            : parseFloat(maxPrice.value);

    let visibleProducts = 0;

    productCards.forEach(function (card) {

        const productName =
            card.querySelector('.product-name')?.textContent
                .trim()
                .toLowerCase() || '';

        const farmerName =
            card.querySelector('.product-farmer')?.textContent
                .trim()
                .toLowerCase() || '';

        const categoryId =
            card.dataset.categoryId || '';

        const marketId =
            card.dataset.marketId || '';

        const marketDays =
            card.dataset.marketDays || '';

        const price =
            parseFloat(
                card.dataset.price || '0'
            );


        const matchesSearch =
            searchTerm === '' ||
            productName.includes(searchTerm) ||
            farmerName.includes(searchTerm);


        const matchesCategory =
            selectedCategory === '' ||
            categoryId === selectedCategory;


        const matchesMarket =
            selectedMarket === '' ||
            marketId === selectedMarket;


        const matchesDay =
            selectedDay === '' ||
            marketDays
                .split(',')
                .map(day => day.trim())
                .includes(selectedDay);


        const matchesMinPrice =
            min === null ||
            price >= min;


        const matchesMaxPrice =
            max === null ||
            price <= max;


        const matches =
            matchesSearch &&
            matchesCategory &&
            matchesMarket &&
            matchesDay &&
            matchesMinPrice &&
            matchesMaxPrice;


        card.style.display =
            matches
                ? ''
                : 'none';

        if (matches) {
            visibleProducts++;
        }

    });

    noMatchingProducts.style.display =
    visibleProducts === 0
        ? 'block'
        : 'none';
}


productSearch.addEventListener(
    'input',
    applyProductFilters
);

productCategory.addEventListener(
    'change',
    applyProductFilters
);

productMarket.addEventListener(
    'change',
    applyProductFilters
);

productDay.addEventListener(
    'change',
    applyProductFilters
);

minPrice.addEventListener(
    'input',
    applyProductFilters
);

maxPrice.addEventListener(
    'input',
    applyProductFilters
);

</script>


</body>

</html>