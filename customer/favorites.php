<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = getUserId();


/*
|--------------------------------------------------------------------------
| Favorite Farmers
|--------------------------------------------------------------------------
*/

$farmers = [];

$farmerStmt = $conn->prepare("
    SELECT
        f.id,
        f.stall_name,
        f.contact_person,
        f.address,
        f.latitude,
        f.longitude,
        ff.created_at
    FROM favorite_farmers ff
    INNER JOIN farmers f
        ON ff.farmer_id = f.id
    WHERE ff.customer_id = ?
      AND f.approval_status = 'approved'
    ORDER BY ff.created_at DESC
");

$farmerStmt->bind_param(
    "i",
    $customerId
);

$farmerStmt->execute();

$farmerResult = $farmerStmt->get_result();

while ($row = $farmerResult->fetch_assoc()) {
    $farmers[] = $row;
}

$farmerStmt->close();


/*
|--------------------------------------------------------------------------
| Favorite Products
|--------------------------------------------------------------------------
*/

$products = [];

$productStmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.unit,
        p.stock_quantity,
        p.image,
        p.is_available,
        c.name AS category_name,
        f.stall_name AS farmer_name,
        fp.created_at
    FROM favorite_products fp
    INNER JOIN products p
        ON fp.product_id = p.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    LEFT JOIN farmers f
        ON p.farmer_id = f.id
    WHERE fp.customer_id = ?
      AND p.moderation_status = 'approved'
    ORDER BY fp.created_at DESC
");

$productStmt->bind_param(
    "i",
    $customerId
);

$productStmt->execute();

$productResult = $productStmt->get_result();

while ($row = $productResult->fetch_assoc()) {
    $products[] = $row;
}

$productStmt->close();


/*
|--------------------------------------------------------------------------
| Favorite Markets
|--------------------------------------------------------------------------
*/

$markets = [];

$marketStmt = $conn->prepare("
    SELECT
        m.id,
        m.name,
        m.address,
        m.latitude,
        m.longitude,
        m.operating_days,
        m.opening_time,
        m.closing_time,
        fm.created_at
    FROM favorite_markets fm
    INNER JOIN markets m
        ON fm.market_id = m.id
    WHERE fm.customer_id = ?
      AND m.status = 'active'
    ORDER BY fm.created_at DESC
");

$marketStmt->bind_param(
    "i",
    $customerId
);

$marketStmt->execute();

$marketResult = $marketStmt->get_result();

while ($row = $marketResult->fetch_assoc()) {
    $markets[] = $row;
}

$marketStmt->close();

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
            max-width: 1150px;
            margin: 40px auto 60px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 32px;
            color: #222;
        }

        .page-header p {
            margin: 0;
            color: #777;
        }

        .section {
            margin-bottom: 35px;
        }

        .section-title {
            margin: 0 0 18px;
            font-size: 24px;
            color: #222;
        }

        .cards-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(260px, 1fr)
                );
            gap: 20px;
        }

        .favorite-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow:
                0 4px 16px
                rgba(0, 0, 0, 0.06);
        }

        .favorite-name {
            margin: 0 0 12px;
            font-size: 20px;
            color: #222;
        }

        .favorite-info {
            margin: 8px 0;
            color: #666;
            line-height: 1.5;
        }

        .favorite-label {
            font-weight: bold;
            color: #444;
        }

        .favorite-link {
            display: inline-block;
            margin-top: 14px;
            padding: 10px 15px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .favorite-link:hover {
            background: #219150;
        }

        .empty-message {
            background: white;
            padding: 25px;
            border-radius: 12px;
            color: #777;
            text-align: center;
        }

        .product-image-container {
            width: 100%;
            height: 170px;
            background: #eeeeee;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image {
            color: #999;
            font-size: 14px;
        }

        .price {
            color: #27ae60;
            font-weight: bold;
            font-size: 19px;
            margin: 10px 0;
        }

        .status-available {
            color: #27ae60;
            font-weight: bold;
        }

        .status-unavailable {
            color: #e74c3c;
            font-weight: bold;
        }

        .directions-link {
            display: inline-block;
            margin-top: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }

        .directions-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {

            .favorites-container {
                width: 94%;
                margin-top: 25px;
            }

            .page-header h1 {
                font-size: 28px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">

    <div class="favorites-container">

        <div class="page-header">

            <h1>My Favorites</h1>

            <p>
                Your favorite farmers, products, and preferred markets.
            </p>

        </div>


        <!-- =====================================================
             Favorite Farmers
        ====================================================== -->

        <section class="section">

            <h2 class="section-title">
                Favorite Farmers
            </h2>

            <?php if (!empty($farmers)): ?>

                <div class="cards-grid">

                    <?php foreach ($farmers as $farmer): ?>

                        <div class="favorite-card">

                            <h3 class="favorite-name">
                                <?= e($farmer['stall_name']); ?>
                            </h3>

                            <div class="favorite-info">

                                <span class="favorite-label">
                                    Contact:
                                </span>

                                <?= e(
                                    $farmer['contact_person']
                                    ?: 'Not available'
                                ); ?>

                            </div>

                            <div class="favorite-info">

                                <span class="favorite-label">
                                    Location:
                                </span>

                                <?= e(
                                    $farmer['address']
                                    ?: 'Not available'
                                ); ?>

                            </div>

                            <a
                                href="farmer_details.php?id=<?= (int)$farmer['id']; ?>"
                                class="favorite-link"
                            >
                                View Farmer
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-message">
                    You have no favorite farmers yet.
                </div>

            <?php endif; ?>

        </section>


        <!-- =====================================================
             Favorite Products
        ====================================================== -->

        <section class="section">

            <h2 class="section-title">
                Favorite Products
            </h2>

            <?php if (!empty($products)): ?>

                <div class="cards-grid">

                    <?php foreach ($products as $product): ?>

                        <div class="favorite-card">

                            <div class="product-image-container">

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="../uploads/products/<?= e(
                                            $product['image']
                                        ); ?>"
                                        alt="<?= e(
                                            $product['name']
                                        ); ?>"
                                        class="product-image"
                                    >

                                <?php else: ?>

                                    <div class="no-image">
                                        No Image Available
                                    </div>

                                <?php endif; ?>

                            </div>

                            <h3 class="favorite-name">
                                <?= e($product['name']); ?>
                            </h3>

                            <?php if (
                                !empty($product['category_name'])
                            ): ?>

                                <div class="favorite-info">

                                    <span class="favorite-label">
                                        Category:
                                    </span>

                                    <?= e(
                                        $product['category_name']
                                    ); ?>

                                </div>

                            <?php endif; ?>

                            <div class="price">

                                $
                                <?= number_format(
                                    (float)$product['price'],
                                    2
                                ); ?>

                                per
                                <?= e($product['unit']); ?>

                            </div>

                            <div class="favorite-info">

                                <span class="favorite-label">
                                    Farmer:
                                </span>

                                <?= e(
                                    $product['farmer_name']
                                    ?: 'Unknown Farmer'
                                ); ?>

                            </div>

                            <div class="favorite-info">

                                <?php if (
                                    $product['is_available']
                                    && (float)$product['stock_quantity'] > 0
                                ): ?>

                                    <span class="status-available">
                                        Available
                                    </span>

                                <?php else: ?>

                                    <span class="status-unavailable">
                                        Not Available
                                    </span>

                                <?php endif; ?>

                            </div>

                            <a
                                href="product_details.php?id=<?= (int)$product['id']; ?>"
                                class="favorite-link"
                            >
                                View Product
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-message">
                    You have no favorite products yet.
                </div>

            <?php endif; ?>

        </section>


        <!-- =====================================================
             Preferred Markets
        ====================================================== -->

        <section class="section">

            <h2 class="section-title">
                Preferred Markets
            </h2>

            <?php if (!empty($markets)): ?>

                <div class="cards-grid">

                    <?php foreach ($markets as $market): ?>

                        <div class="favorite-card">

                            <h3 class="favorite-name">
                                <?= e($market['name']); ?>
                            </h3>

                            <div class="favorite-info">

                                <span class="favorite-label">
                                    Address:
                                </span>

                                <?= e($market['address']); ?>

                            </div>

                            <?php if (
                                !empty($market['operating_days'])
                            ): ?>

                                <div class="favorite-info">

                                    <span class="favorite-label">
                                        Operating Days:
                                    </span>

                                    <?= e(
                                        $market['operating_days']
                                    ); ?>

                                </div>

                            <?php endif; ?>

                            <?php if (
                                $market['opening_time']
                                && $market['closing_time']
                            ): ?>

                                <div class="favorite-info">

                                    <span class="favorite-label">
                                        Hours:
                                    </span>

                                    <?= e(
                                        date(
                                            'h:i A',
                                            strtotime(
                                                $market['opening_time']
                                            )
                                        )
                                    ); ?>

                                    -
                                    <?= e(
                                        date(
                                            'h:i A',
                                            strtotime(
                                                $market['closing_time']
                                            )
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>

                            <a
                                href="market-details.php?id=<?= (int)$market['id']; ?>"
                                class="favorite-link"
                            >
                                View Market
                            </a>

                            <?php if (
                                $market['latitude'] !== null
                                && $market['longitude'] !== null
                            ): ?>

                                <br>

                                <a
                                    href="https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=<?= (float)$market['latitude']; ?>,<?= (float)$market['longitude']; ?>"
                                    target="_blank"
                                    class="directions-link"
                                >
                                    Open Directions
                                </a>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-message">
                    You have no preferred markets yet.
                </div>

            <?php endif; ?>

        </section>

    </div>

</main>

</body>

</html>