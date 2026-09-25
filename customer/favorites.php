<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();


/*
|--------------------------------------------------------------------------
| Remove Favorite Product
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product'])) {

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

        .favorites-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
        }

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
        =========================================================
        Empty Image Area
        =========================================================
        */

        .product-image-area {
            width: 100%;
            height: 200px;
            background: #eee;
            position: relative;
        }


        /*
        =========================================================
        Remove Favorite
        =========================================================
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
            font-size: 28px;
            line-height: 44px;
            padding: 0;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .remove-favorite-button:hover {
            transform: scale(1.1);
        }


        /*
        =========================================================
        Product Information
        =========================================================
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
        =========================================================
        Empty Favorites
        =========================================================
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

    </style>

</head>


<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">

        <div class="favorites-container">


            <!-- Page Header -->

            <div class="page-header">

                <h1>
                    Favorites
                </h1>

                <p>
                    Products you have saved to your favorites.
                </p>

            </div>


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
                                        ♥
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

    </main>


</body>

</html>