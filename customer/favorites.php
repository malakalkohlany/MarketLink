<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('customer');

$customerId = getUserId();


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
            "DELETE FROM favorite_products
             WHERE customer_id = ?
               AND product_id = ?"
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
            "DELETE FROM favorite_farmers
             WHERE customer_id = ?
               AND farmer_id = ?"
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
| Get Favorite Products From Database
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
| Get Favorite Farmers From Database
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Favorites - MarketLink</title>

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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .favorites-container {
            width: 92%;
            max-width: 1200px;
            margin: 40px auto;
        }

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
        | Section Title
        |--------------------------------------------------------------------------
        */

        .favorites-section {
            margin-bottom: 50px;
        }

        .favorites-section-title {
            font-size: 24px;
            font-weight: bold;
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
                repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
        }

        /*
        |--------------------------------------------------------------------------
        | Favorite Card
        |--------------------------------------------------------------------------
        */

        .favorite-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);
            transition: 0.2s;
        }

        .favorite-card:hover {
            transform: translateY(-4px);
        }


        /*
        |--------------------------------------------------------------------------
        | Empty Image Area
        |--------------------------------------------------------------------------
        */

        .product-image-area {
            width: 100%;
            height: 200px;
            background: #eee;
            position: relative;
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
            z-index: 5;
        }

        .remove-favorite-button {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 50%;
            background: #ffffff;
            color: #e74c3c;
            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            font-size: 20px;
            line-height: 44px;
            padding: 0;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .remove-favorite-button:hover {
            transform: scale(1.1);
        }


        /*
        |--------------------------------------------------------------------------
        | Product Information
        |--------------------------------------------------------------------------
        */

        .product-info {
            padding: 20px;
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

        .view-button {
            display: block;
            margin-top: 18px;
            padding: 11px;
            background: #3498db;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
        }

        .view-button:hover {
            background: #2980b9;
        }


        /*
        |--------------------------------------------------------------------------
        | Farmer Information
        |--------------------------------------------------------------------------
        */

        .farmer-info {
            padding: 20px;
        }

        .farmer-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 12px;
            padding-right: 10px;
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
        | Empty Favorites
        |--------------------------------------------------------------------------
        */

        .empty-favorites {
            background: white;
            padding: 60px 30px;
            border-radius: 14px;
            text-align: center;
            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .empty-favorites h2 {
            margin-bottom: 10px;
        }

        .empty-favorites p {
            color: #777;
            margin: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Farmer Empty Area
        |--------------------------------------------------------------------------
        */

        .empty-section {
            background: white;
            padding: 40px 30px;
            border-radius: 14px;
            text-align: center;
            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .empty-section p {
            color: #777;
            margin: 0;
        }

    </style>

</head>


<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">

        <div class="favorites-container">


            <!-- =========================================================
                 PAGE HEADER
                 ========================================================= -->

            <div class="page-header">

                <h1>
                    Favorites
                </h1>

                <p>
                    Products and farmers you have saved to your favorites.
                </p>

            </div>



            <!-- =========================================================
                 FAVORITE PRODUCTS
                 ========================================================= -->

            <div class="favorites-section">

                <h2 class="favorites-section-title">
                    Favorite Products
                </h2>


                <?php if (count($favoriteProducts) > 0): ?>

                    <div class="favorites-grid">


                        <?php foreach ($favoriteProducts as $product): ?>


                            <div class="favorite-card">


                                <!-- Empty Image Area -->

                                <div class="product-image-area">


                                    <!-- Remove Favorite -->

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


                                </div>


                                <!-- Product Information -->

                                <div class="product-info">


                                    <div class="product-name">

                                        <?= htmlspecialchars(
                                            $product['name']
                                        ) ?>

                                    </div>


                                    <div class="product-description">

                                        <?= htmlspecialchars(
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

                                            <?= htmlspecialchars(
                                                $product['unit']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <div class="product-stock">

                                        Stock:

                                        <?= number_format(
                                            (float) $product['stock_quantity'],
                                            2
                                        ) ?>

                                        <?= htmlspecialchars(
                                            $product['unit']
                                        ) ?>

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



            <!-- =========================================================
                 FAVORITE FARMERS
                 ========================================================= -->

            <div class="favorites-section">

                <h2 class="favorites-section-title">
                    Favorite Farmers
                </h2>


                <?php if (count($favoriteFarmers) > 0): ?>


                    <div class="favorites-grid">


                        <?php foreach ($favoriteFarmers as $farmer): ?>


                            <div class="favorite-card">


                                <!-- Farmer Top Area -->

                                <div class="product-image-area">


                                    <!-- Remove Favorite Farmer -->

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


                                </div>


                                <!-- Farmer Information -->

                                <div class="farmer-info">


                                    <div class="farmer-name">

                                        <?= htmlspecialchars(
                                            $farmer['stall_name']
                                        ) ?>

                                    </div>


                                    <?php if (!empty($farmer['contact_person'])): ?>

                                        <div class="farmer-contact">

                                            <strong>
                                                Contact:
                                            </strong>

                                            <?= htmlspecialchars(
                                                $farmer['contact_person']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (!empty($farmer['address'])): ?>

                                        <div class="farmer-address">

                                            <strong>
                                                Address:
                                            </strong>

                                            <?= htmlspecialchars(
                                                $farmer['address']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <div class="farmer-description">

                                        <?= htmlspecialchars(
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


        </div>

    </main>


</body>

</html>