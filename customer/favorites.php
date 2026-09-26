
<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();


/*
|--------------------------------------------------------------------------
| Remove Favorite Product
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['remove_product'])
) {

    $productId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    if ($productId && $productId > 0) {

        $stmt = $conn->prepare(
            "
            DELETE FROM favorite_products
            WHERE customer_id = ?
              AND product_id = ?
            "
        );

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param(
            "ii",
            $customerId,
            $productId
        );

        if (!$stmt->execute()) {
            die("Database Error: " . $stmt->error);
        }

        $stmt->close();
    }

    header('Location: favorites.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Remove Favorite Farmer
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['remove_farmer'])
) {

    $farmerId = filter_input(
        INPUT_POST,
        'farmer_id',
        FILTER_VALIDATE_INT
    );

    if ($farmerId && $farmerId > 0) {

        $stmt = $conn->prepare(
            "
            DELETE FROM favorite_farmers
            WHERE customer_id = ?
              AND farmer_id = ?
            "
        );

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param(
            "ii",
            $customerId,
            $farmerId
        );

        if (!$stmt->execute()) {
            die("Database Error: " . $stmt->error);
        }

        $stmt->close();
    }

    header('Location: favorites.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Remove Favorite Market
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['remove_market'])
) {

    $marketId = filter_input(
        INPUT_POST,
        'market_id',
        FILTER_VALIDATE_INT
    );

    if ($marketId && $marketId > 0) {

        $stmt = $conn->prepare(
            "
            DELETE FROM favorite_markets
            WHERE customer_id = ?
              AND market_id = ?
            "
        );

        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param(
            "ii",
            $customerId,
            $marketId
        );

        if (!$stmt->execute()) {
            die("Database Error: " . $stmt->error);
        }

        $stmt->close();
    }

    header('Location: favorites.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Favorite Products
|--------------------------------------------------------------------------
*/
$favoriteProducts = [];

$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.stock_quantity,
        p.farmer_id
    FROM favorite_products fp

    INNER JOIN products p
        ON fp.product_id = p.id

    WHERE fp.customer_id = ?
      AND p.is_available = 1
      AND p.moderation_status = 'approved'

    ORDER BY fp.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param(
    "i",
    $customerId
);

if (!$stmt->execute()) {
    die("Database Error: " . $stmt->error);
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $favoriteProducts[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| Get Favorite Farmers
|--------------------------------------------------------------------------
*/
$favoriteFarmers = [];

$sql = "
    SELECT
        f.id,
        f.stall_name,
        f.contact_person,
        f.description,
        f.address

    FROM favorite_farmers ff

    INNER JOIN farmers f
        ON ff.farmer_id = f.id

    WHERE ff.customer_id = ?
      AND f.approval_status = 'approved'

    ORDER BY ff.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param(
    "i",
    $customerId
);

if (!$stmt->execute()) {
    die("Database Error: " . $stmt->error);
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $favoriteFarmers[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| Get Favorite Markets
|--------------------------------------------------------------------------
*/
$favoriteMarkets = [];

$sql = "
    SELECT
        m.id,
        m.name,
        m.description,
        m.address,
        m.latitude,
        m.longitude,
        m.opening_time,
        m.closing_time,
        m.operating_days,
        m.map_provider

    FROM favorite_markets fm

    INNER JOIN markets m
        ON fm.market_id = m.id

    WHERE fm.customer_id = ?
      AND m.status = 'active'

    ORDER BY fm.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param(
    "i",
    $customerId
);

if (!$stmt->execute()) {
    die("Database Error: " . $stmt->error);
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $favoriteMarkets[] = $row;
}

$stmt->close();

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
        Favorites - MarketLink
    </title>


    <!-- Project CSS -->

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


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | General
        |--------------------------------------------------------------------------
        */

        * {
            box-sizing: border-box;
        }


        .favorites-container {

            width: 100%;

            padding: 30px;

            box-sizing: border-box;
        }


        /*
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Section
        |--------------------------------------------------------------------------
        */

        .favorites-section {

            margin-bottom: 50px;
        }


        .favorites-section-title {

            font-size: 24px;

            font-weight: bold;

            margin-top: 0;

            margin-bottom: 20px;
        }


        /*
        |--------------------------------------------------------------------------
        | Grid
        |--------------------------------------------------------------------------
        */

        .favorites-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(260px, 1fr));

            gap: 20px;
        }


        /*
        |--------------------------------------------------------------------------
        | Favorite Card
        |--------------------------------------------------------------------------
        */

        .favorite-card {

            position: relative;

            background: #ffffff;

            border-radius: 14px;

            padding: 22px;

            box-shadow:
                0 2px 10px
                rgba(0, 0, 0, 0.08);

            display: flex;

            flex-direction: column;

            min-height: 220px;

            box-sizing: border-box;

            transition: 0.2s ease;
        }


        .favorite-card:hover {

            transform: translateY(-3px);
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Favorite
        |--------------------------------------------------------------------------
        */

        .remove-favorite-form {

            position: absolute;

            top: 12px;

            right: 12px;

            z-index: 10;

            margin: 0;
        }


        .remove-favorite-button {

            width: 36px;

            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: none;

            border-radius: 50%;

            background: #ffffff;

            color: #e53935;

            cursor: pointer;

            font-size: 20px;

            padding: 0;

            margin: 0;

            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, 0.10);

            transition: 0.2s ease;
        }


        .remove-favorite-button:hover {

            transform: scale(1.08);
        }


        /*
        |--------------------------------------------------------------------------
        | Product
        |--------------------------------------------------------------------------
        */

        .product-info {

            padding-top: 5px;

            padding-right: 35px;
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


        /*
        |--------------------------------------------------------------------------
        | Farmer
        |--------------------------------------------------------------------------
        */

        .farmer-info {

            padding-top: 5px;

            padding-right: 35px;
        }


        .farmer-name {

            font-size: 20px;

            font-weight: bold;

            margin-bottom: 12px;
        }


        .farmer-contact,
        .farmer-address,
        .farmer-description {

            color: #777;

            font-size: 14px;

            line-height: 1.5;

            margin-bottom: 10px;
        }


        .farmer-description {

            min-height: 42px;
        }


        /*
        |--------------------------------------------------------------------------
        | Market
        |--------------------------------------------------------------------------
        */

        .market-info-container {

            padding-top: 5px;

            padding-right: 35px;
        }


        .market-name {

            font-size: 20px;

            font-weight: bold;

            margin-bottom: 12px;
        }


        .market-address,
        .market-time,
        .market-days,
        .market-description {

            color: #777;

            font-size: 14px;

            line-height: 1.5;

            margin-bottom: 9px;
        }


        .market-address strong,
        .market-time strong,
        .market-days strong {

            color: #333;
        }


        .market-description {

            min-height: 42px;

            margin-top: 5px;
        }


        /*
        |--------------------------------------------------------------------------
        | View Button
        |--------------------------------------------------------------------------
        */

        .view-button {

            display: block;

            margin-top: auto;

            padding: 11px 16px;

            background: #2f6f4e;

            color: #ffffff;

            text-align: center;

            text-decoration: none;

            border-radius: 8px;

            font-weight: 600;

            transition: 0.2s ease;
        }


        .view-button:hover {

            opacity: 0.9;
        }


        /*
        |--------------------------------------------------------------------------
        | Empty Areas
        |--------------------------------------------------------------------------
        */

        .empty-favorites,
        .empty-section {

            background: #ffffff;

            padding: 40px 30px;

            border-radius: 14px;

            text-align: center;

            box-shadow:
                0 2px 10px
                rgba(0, 0, 0, 0.05);
        }


        .empty-favorites h2,
        .empty-section h2 {

            margin-top: 0;

            margin-bottom: 10px;
        }


        .empty-favorites p,
        .empty-section p {

            color: #777;

            margin: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 768px) {

            .favorites-container {

                padding: 20px;
            }

        }

    </style>

</head>


<body>


<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">


    <div class="favorites-container">


        <!-- =====================================================
             PAGE HEADER
             ===================================================== -->

        <div class="page-header">

            <h1>
                Favorites
            </h1>

            <p>
                Products, farmers and markets you have saved to your favorites.
            </p>

        </div>


        <!-- =====================================================
             FAVORITE PRODUCTS
             ===================================================== -->

        <div class="favorites-section">

            <h2 class="favorites-section-title">
                Favorite Products
            </h2>


            <?php if (count($favoriteProducts) > 0): ?>

                <div class="favorites-grid">


                    <?php foreach ($favoriteProducts as $product): ?>

                        <div class="favorite-card">


                            <!-- Remove -->

                            <form
                                method="POST"
                                action="favorites.php"
                                class="remove-favorite-form"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int) $product['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="remove_product"
                                    class="remove-favorite-button"
                                    title="Remove from Favorites"
                                    aria-label="Remove from Favorites"
                                >

                                    <i class="fa-solid fa-heart"></i>

                                </button>

                            </form>


                            <!-- Product -->

                            <div class="product-info">


                                <div class="product-name">

                                    <?= e($product['name']) ?>

                                </div>


                                <div class="product-description">

                                    <?= e(
                                        $product['description']
                                        ?? 'No description available.'
                                    ) ?>

                                </div>


                                <div class="product-price">

                                    $

                                    <?= number_format(
                                        (float) $product['price'],
                                        2
                                    ) ?>

                                </div>


                                <?php if (!empty($product['unit'])): ?>

                                    <div class="product-unit">

                                        Per
                                        <?= e($product['unit']) ?>

                                    </div>

                                <?php endif; ?>


                                <div class="product-stock">

                                    Stock:

                                    <?= number_format(
                                        (float) $product['stock_quantity'],
                                        2
                                    ) ?>

                                    <?= e($product['unit']) ?>

                                </div>


                                <a
                                    href="product_details.php?id=<?= (int) $product['id'] ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>


                            </div>


                        </div>

                    <?php endforeach; ?>


                </div>

            <?php else: ?>


                <div class="empty-favorites">

                    <h2>
                        No Favorite Products
                    </h2>

                    <p>
                        Products you add to your favorites will appear here.
                    </p>

                </div>


            <?php endif; ?>

        </div>


        <!-- =====================================================
             FAVORITE FARMERS
             ===================================================== -->

        <div class="favorites-section">

            <h2 class="favorites-section-title">
                Favorite Farmers
            </h2>


            <?php if (count($favoriteFarmers) > 0): ?>


                <div class="favorites-grid">


                    <?php foreach ($favoriteFarmers as $farmer): ?>

                        <div class="favorite-card">


                            <!-- Remove -->

                            <form
                                method="POST"
                                action="favorites.php"
                                class="remove-favorite-form"
                            >

                                <input
                                    type="hidden"
                                    name="farmer_id"
                                    value="<?= (int) $farmer['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="remove_farmer"
                                    class="remove-favorite-button"
                                    title="Remove from Favorites"
                                    aria-label="Remove from Favorites"
                                >

                                    <i class="fa-solid fa-heart"></i>

                                </button>

                            </form>


                            <!-- Farmer -->

                            <div class="farmer-info">


                                <div class="farmer-name">

                                    <?= e($farmer['stall_name']) ?>

                                </div>


                                <?php if (!empty($farmer['contact_person'])): ?>

                                    <div class="farmer-contact">

                                        <strong>
                                            Contact:
                                        </strong>

                                        <?= e(
                                            $farmer['contact_person']
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <?php if (!empty($farmer['address'])): ?>

                                    <div class="farmer-address">

                                        <strong>
                                            Address:
                                        </strong>

                                        <?= e(
                                            $farmer['address']
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <div class="farmer-description">

                                    <?= e(
                                        $farmer['description']
                                        ?? 'No description available.'
                                    ) ?>

                                </div>


                                <a
                                    href="farmer_details.php?id=<?= (int) $farmer['id'] ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>


                            </div>


                        </div>

                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-section">

                    <h2>
                        No Favorite Farmers
                    </h2>

                    <p>
                        Farmers you add to your favorites will appear here.
                    </p>

                </div>


            <?php endif; ?>


        </div>


        <!-- =====================================================
             FAVORITE MARKETS
             ===================================================== -->

        <div class="favorites-section">

            <h2 class="favorites-section-title">
                Favorite Markets
            </h2>


            <?php if (count($favoriteMarkets) > 0): ?>


                <div class="favorites-grid">


                    <?php foreach ($favoriteMarkets as $market): ?>

                        <div class="favorite-card">


                            <!-- Remove Favorite Market -->

                            <form
                                method="POST"
                                action="favorites.php"
                                class="remove-favorite-form"
                            >

                                <input
                                    type="hidden"
                                    name="market_id"
                                    value="<?= (int) $market['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="remove_market"
                                    class="remove-favorite-button"
                                    title="Remove from Favorites"
                                    aria-label="Remove from Favorites"
                                >

                                    <i class="fa-solid fa-heart"></i>

                                </button>

                            </form>


                            <!-- Market Information -->

                            <div class="market-info-container">


                                <div class="market-name">

                                    <?= e($market['name']) ?>

                                </div>


                                <div class="market-address">

                                    <strong>
                                        📍 Address:
                                    </strong>

                                    <?= e($market['address']) ?>

                                </div>


                                <div class="market-time">

                                    <strong>
                                        Opening:
                                    </strong>

                                    <?= e($market['opening_time']) ?>

                                </div>


                                <div class="market-time">

                                    <strong>
                                        Closing:
                                    </strong>

                                    <?= e($market['closing_time']) ?>

                                </div>


                                <div class="market-days">

                                    <strong>
                                        Operating Days:
                                    </strong>

                                    <?= e($market['operating_days']) ?>

                                </div>


                                <?php if (!empty($market['description'])): ?>

                                    <div class="market-description">

                                        <?= e($market['description']) ?>

                                    </div>

                                <?php endif; ?>


                                <a
                                    href="market-details.php?id=<?= (int) $market['id'] ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>


                            </div>


                        </div>

                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-section">

                    <h2>
                        No Favorite Markets
                    </h2>

                    <p>
                        Markets you add to your favorites will appear here.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>


</main>


</body>

</html>

