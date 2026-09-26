<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

/*
|--------------------------------------------------------------------------
| Toggle Favorite Product
|--------------------------------------------------------------------------
*/

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

    /*
    |--------------------------------------------------------------------------
    | Check if product already exists in favorites
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | If favorite exists -> DELETE
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | If favorite does not exist -> INSERT
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Return to products page
    |--------------------------------------------------------------------------
    */

    header('Location: products.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Current Customer Favorite Products
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Get Products
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        p.stock_quantity
    FROM products p
    INNER JOIN farmers f ON p.farmer_id = f.id
    WHERE p.is_available = 1
      AND p.moderation_status = ?
      AND f.approval_status = ?
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Database Error: ' . $conn->error);
}

$moderation_status = M_APPROVED;
$farmer_status = A_APPROVED;

$stmt->bind_param(
    'ss',
    $moderation_status,
    $farmer_status
);

$stmt->execute();

$result = $stmt->get_result();

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
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

    <title>Products - MarketLink</title>


    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        


        /* =====================================================
           Page Header
        ===================================================== */

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
            position: relative !important;

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
            width: 100% !important;

            height: 220px !important;

            background: #f5f5f5 !important;
        }


        /* =====================================================
           Favorite Form
        ===================================================== */

        .favorite-form {
            position: absolute !important;

            top: 12px !important;

            right: 12px !important;

            z-index: 100 !important;

            margin: 0 !important;
        }


        /* =====================================================
           Favorite Button
        ===================================================== */

        .favorite-button {
            width: 36px !important;

            height: 36px !important;

            display: flex !important;

            align-items: center !important;

            justify-content: center !important;

            border: none !important;

            border-radius: 50% !important;

            background: #ffffff !important;

            cursor: pointer !important;

            font-size: 20px !important;

            padding: 0 !important;

            margin: 0 !important;

            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, 0.10) !important;

            transition: 0.2s ease;
        }


        /* Empty heart */

        .favorite-button.empty {
            color: #555555 !important;
        }


        /* Filled heart */

        .favorite-button.filled {
            color: #e53935 !important;
        }


        /* Heart hover */

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
           View Details Button
        ===================================================== */

        .view-details-button {
            display: block;

            width: 100%;

            box-sizing: border-box;

            text-align: center;

            text-decoration: none;

            background: #222;

            color: #ffffff;

            padding: 11px 15px;

            border-radius: 7px;

            transition: 0.2s ease;
        }

        .view-details-button:hover {
            background: #444;
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

        }

    </style>

</head>


<body>


    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">


        <!-- Page Header -->

        <div class="page-header">

            <h1>
                Products
            </h1>

            <p>
                Browse fresh products available from our farmers.
            </p>

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


                    /*
                    |--------------------------------------------------------------------------
                    | Check if Product is Favorite
                    |--------------------------------------------------------------------------
                    */

                    $isFavorite =
                        in_array(
                            $productId,
                            $favoriteProducts,
                            true
                        );

                    ?>


                    <div class="product-card">


                        <!-- Favorite Button -->

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


                        <!-- Empty Image Area -->

                        <div class="product-image-container">
                        </div>


                        <!-- Product Information -->

                        <div class="product-info">


                            <h2 class="product-name">

                                <?= e($productName) ?>

                            </h2>


                            <div class="product-description">

                                <?= e(
                                    truncateText(
                                        $description,
                                        90
                                    )
                                ) ?>

                            </div>


                            <div class="product-price">

                                $<?= formatPrice($price) ?>


                                <?php if ($unit !== ''): ?>

                                    <span class="product-unit">

                                        / <?= e($unit) ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="product-stock">

                                Stock:

                                <?= e($stock) ?>


                                <?php if ($unit !== ''): ?>

                                    <?= e($unit) ?>

                                <?php endif; ?>

                            </div>


                            <a
                                href="product_details.php?id=<?= $productId ?>"
                                class="view-details-button"
                            >

                                View Details

                            </a>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>
                    <?php endif; ?>
    </main>

    <script src="../assets/js/app.js"></script> 

</body>

</html>