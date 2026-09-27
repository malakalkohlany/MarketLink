<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

/* =========================================================
   GET FARMER ID
   ========================================================= */

$farmerId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($farmerId <= 0) {
redirect('customer/farmers.php');
}

/* =========================================================
   GET FARMER
   ========================================================= */

$farmerStmt = $conn->prepare("
    SELECT
        id,
        stall_name,
        contact_person,
        description,
        address,
        latitude,
        longitude,
        approval_status,
        created_at
    FROM farmers
    WHERE id = ?
      AND approval_status = 'approved'
    LIMIT 1
");

if (!$farmerStmt) {
    die('Farmer query failed: ' . $conn->error);
}

$farmerStmt->bind_param("i", $farmerId);
$farmerStmt->execute();

$farmerResult = $farmerStmt->get_result();
$farmer = $farmerResult->fetch_assoc();

$farmerStmt->close();

if (!$farmer) {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Farmer Not Found - MarketLink</title>

        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f5f6fa;
            }

            .message-card {
                width: 90%;
                max-width: 600px;
                margin: 100px auto;
                background: white;
                padding: 40px;
                text-align: center;
                border-radius: 15px;
                box-shadow:
                    0 5px 20px
                    rgba(0, 0, 0, 0.08);
            }

            .message-card h2 {
                margin-bottom: 15px;
                color: #333;
            }

            .message-card p {
                color: #777;
                margin-bottom: 25px;
            }

            .back-button {
                display: inline-block;
                padding: 12px 25px;
                background: #27ae60;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }

            .back-button:hover {
                background: #219150;
            }
        </style>
    </head>

    <body>

        <div class="message-card">

            <h2>
                Farmer Not Found
            </h2>

            <p>
                This farmer is not available or no longer exists.
            </p>

            <a
                href="farmers.php"
                class="back-button"
            >
                Back to Farmers
            </a>

        </div>

    </body>

    </html>

    <?php
    exit;
}


/* =========================================================
   GET FARMER'S MARKETS / OPERATING DAYS
   ========================================================= */

$markets = [];

$marketStmt = $conn->prepare("
    SELECT
        m.id,
        m.name,
        m.address,
        m.operating_days,
        m.opening_time,
        m.closing_time,
        m.latitude,
        m.longitude
    FROM market_farmer mf
    INNER JOIN markets m
        ON mf.market_id = m.id
    WHERE mf.farmer_id = ?
      AND m.status = 'active'
    ORDER BY m.name ASC
");

if ($marketStmt) {

    $marketStmt->bind_param(
        "i",
        $farmerId
    );

    $marketStmt->execute();

    $marketResult = $marketStmt->get_result();

    while ($row = $marketResult->fetch_assoc()) {
        $markets[] = $row;
    }

    $marketStmt->close();
}


/* =========================================================
   CURRENT WEEK START
   Monday = start of current week
   ========================================================= */

$currentWeekStart = date(
    'Y-m-d',
    strtotime('monday this week')
);


/* =========================================================
   GET CURRENT WEEKLY STOCK
   ========================================================= */

$weeklyStock = [];

$weeklyStockStmt = $conn->prepare("
    SELECT
        ws.id,
        ws.product_id,
        ws.week_start,
        ws.planned_quantity,
        ws.is_active,

        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        p.is_available,
        p.moderation_status,

        c.name AS category_name

    FROM weekly_stock ws

    INNER JOIN products p
        ON ws.product_id = p.id

    LEFT JOIN categories c
        ON p.category_id = c.id

    WHERE ws.farmer_id = ?
      AND ws.week_start = ?
      AND ws.is_active = 1

      AND p.is_available = 1
      AND p.moderation_status = 'approved'

    ORDER BY p.name ASC
");

if ($weeklyStockStmt) {

    $weeklyStockStmt->bind_param(
        "is",
        $farmerId,
        $currentWeekStart
    );

    $weeklyStockStmt->execute();

    $weeklyStockResult = $weeklyStockStmt->get_result();

    while ($row = $weeklyStockResult->fetch_assoc()) {
        $weeklyStock[] = $row;
    }

    $weeklyStockStmt->close();
}


/* =========================================================
   GET ALL AVAILABLE PRODUCTS
   ========================================================= */

$products = [];

$productStmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.stock_quantity,
        p.image,
        p.is_available,
        p.moderation_status,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.farmer_id = ?
      AND p.is_available = 1
      AND p.moderation_status = 'approved'
    ORDER BY p.name ASC
");

if (!$productStmt) {
    die('Products query failed: ' . $conn->error);
}

$productStmt->bind_param(
    "i",
    $farmerId
);

$productStmt->execute();

$productResult = $productStmt->get_result();

while ($row = $productResult->fetch_assoc()) {
    $products[] = $row;
}

$productStmt->close();


/* =========================================================
   GET APPROVED FARMER REVIEWS
   ========================================================= */

$reviews = [];

$reviewStmt = $conn->prepare("
    SELECT
        r.id,
        r.rating,
        r.comment,
        r.created_at,
        r.farmer_response,
        r.farmer_response_at,
        u.name AS customer_name

    FROM reviews r

    INNER JOIN users u
        ON r.customer_id = u.id

    WHERE r.farmer_id = ?
      AND r.product_id IS NULL
      AND r.status = 'approved'

    ORDER BY r.created_at DESC
");

if ($reviewStmt) {

    $reviewStmt->bind_param(
        "i",
        $farmerId
    );

    $reviewStmt->execute();

    $reviewResult = $reviewStmt->get_result();

    while ($row = $reviewResult->fetch_assoc()) {
        $reviews[] = $row;
    }

    $reviewStmt->close();
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
        <?= e($farmer['stall_name']); ?>
        - MarketLink
    </title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

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

        .details-container {
            width: 92%;
            max-width: 1150px;
            margin: 40px auto 60px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-size: 15px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .farmer-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow:
                0 6px 25px
                rgba(0, 0, 0, 0.08);
        }

        /* =====================================================
           FARMER HEADER
           ===================================================== */

        .farmer-header {
            padding: 35px;

            background:
                linear-gradient(
                    135deg,
                    #eaf8ef,
                    #ffffff
                );

            border-bottom:
                1px solid #eeeeee;
        }

        .farmer-name {
            font-size: 36px;
            margin: 0 0 12px;
            color: #222;
        }

        .farmer-status {
            display: inline-block;

            background: #eaf8ef;
            color: #27ae60;

            padding: 7px 14px;

            border-radius: 20px;

            font-size: 13px;
            font-weight: bold;
        }

        /* =====================================================
           MAIN FARMER INFORMATION
           ===================================================== */

        .farmer-content {
            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 35px;

            padding: 35px;
        }

        .section-title {
            margin: 0 0 20px;

            font-size: 22px;

            color: #222;
        }

        .info-box {
            border-top:
                1px solid #eeeeee;
        }

        .info-row {
            display: flex;

            justify-content:
                space-between;

            align-items:
                flex-start;

            gap: 20px;

            padding: 15px 0;

            border-bottom:
                1px solid #eeeeee;
        }

        .info-label {
            font-weight: bold;

            color: #444;

            min-width: 130px;
        }

        .info-value {
            color: #666;

            text-align: right;

            line-height: 1.5;
        }

        .description-box {
            margin-top: 25px;
        }

        .description {
            color: #666;

            line-height: 1.8;

            font-size: 15px;
        }

        /* =====================================================
           LOCATION MAP
           ===================================================== */

        .map-box {
            width: 100%;

            background: #fff;

            border-radius: 12px;

            overflow: hidden;

            border:
                1px solid #eeeeee;
        }

        #map {
            width: 100%;
            height: 350px;
        }

        /* =====================================================
           OPERATING DAYS / MARKETS
           ===================================================== */

        .markets-section {
            padding: 30px 35px 35px;

            background: #ffffff;

            border-top:
                1px solid #eeeeee;
        }

        .markets-header {
            margin-bottom: 20px;
        }

        .markets-header h2 {
            margin: 0 0 6px;

            font-size: 24px;

            color: #222;
        }

        .markets-header p {
            margin: 0;

            color: #777;

            font-size: 14px;
        }

        .markets-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(260px, 1fr)
                );

            gap: 18px;
        }

        .market-card {
            padding: 20px;

            background: #f8f9fb;

            border:
                1px solid #eeeeee;

            border-radius: 12px;
        }

        .market-name {
            margin: 0 0 12px;

            color: #222;

            font-size: 18px;
        }

        .market-detail {
            display: flex;

            gap: 10px;

            margin-bottom: 9px;

            color: #666;

            font-size: 14px;

            line-height: 1.5;
        }

        .market-detail strong {
            color: #444;
        }

        .market-days {
            display: inline-block;

            padding: 7px 11px;

            background: #eaf8ef;

            color: #27ae60;

            border-radius: 7px;

            font-size: 13px;

            font-weight: bold;

            margin-top: 4px;
        }

        .no-markets {
            padding: 25px;

            background: #f8f9fb;

            border:
                1px solid #eeeeee;

            border-radius: 12px;

            color: #777;

            text-align: center;
        }

        /* =====================================================
           CURRENT WEEKLY STOCK
           ===================================================== */

        .weekly-stock-section {
            padding: 30px 35px 40px;

            background: #f8f9fb;

            border-top:
                1px solid #eeeeee;
        }

        .weekly-stock-header {
            display: flex;

            justify-content:
                space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 25px;
        }

        .weekly-stock-header h2 {
            margin: 0 0 6px;

            font-size: 24px;

            color: #222;
        }

        .weekly-stock-header p {
            margin: 0;

            color: #777;

            font-size: 14px;
        }

        .week-label {
            white-space: nowrap;

            padding: 8px 13px;

            background: #eaf8ef;

            color: #27ae60;

            border-radius: 20px;

            font-size: 13px;

            font-weight: bold;
        }

        .weekly-stock-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(230px, 1fr)
                );

            gap: 20px;
        }

        .weekly-stock-card {
            background: white;

            border-radius: 14px;

            overflow: hidden;

            border:
                1px solid #eeeeee;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, 0.05);
        }

        .weekly-stock-image {
            width: 100%;

            height: 170px;

            background: #eeeeee;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;
        }

        .weekly-stock-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .weekly-stock-no-image {
            color: #999;

            font-size: 14px;
        }

        .weekly-stock-content {
            padding: 18px;
        }

        .weekly-stock-category {
            display: inline-block;

            background: #eaf8ef;

            color: #27ae60;

            padding: 5px 10px;

            border-radius: 15px;

            font-size: 12px;

            font-weight: bold;

            margin-bottom: 10px;
        }

        .weekly-stock-name {
            margin: 0 0 8px;

            color: #222;

            font-size: 18px;
        }

        .weekly-stock-price {
            color: #27ae60;

            font-size: 20px;

            font-weight: bold;
        }

        .weekly-stock-unit {
            color: #777;

            font-size: 13px;

            margin-top: 3px;
        }

        .weekly-quantity {
            margin-top: 13px;

            padding: 10px 12px;

            background: #f3f7f4;

            border-radius: 8px;

            font-size: 14px;

            color: #555;
        }

        .weekly-quantity strong {
            color: #27ae60;
        }

        .no-weekly-stock {
            background: white;

            padding: 35px 20px;

            text-align: center;

            border-radius: 12px;

            border:
                1px solid #eeeeee;

            color: #777;
        }

        .no-weekly-stock h3 {
            margin: 0 0 8px;

            color: #444;

            font-size: 18px;
        }

        .no-weekly-stock p {
            margin: 0;

            color: #888;

            font-size: 14px;
        }

        /* =====================================================
           PRODUCTS
           ===================================================== */

        .products-section {
            padding: 30px 35px 40px;

            background: #f8f9fb;

            border-top:
                1px solid #eeeeee;
        }

        .products-header {
            display: flex;

            justify-content:
                space-between;

            align-items: center;

            margin-bottom: 25px;
        }

        .products-header h2 {
            margin: 0;

            font-size: 24px;

            color: #222;
        }

        .product-count {
            color: #777;

            font-size: 14px;
        }

        .products-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(230px, 1fr)
                );

            gap: 20px;
        }

        .product-card {
            background: white;

            border-radius: 14px;

            overflow: hidden;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, 0.06);

            display: flex;

            flex-direction: column;
        }

        .product-image-container {
            width: 100%;

            height: 190px;

            background: #eeeeee;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;
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

        .product-content {
            padding: 18px;

            display: flex;

            flex-direction: column;

            flex: 1;
        }

        .category-badge {
            display: inline-block;

            width: fit-content;

            background: #eaf8ef;

            color: #27ae60;

            padding: 5px 10px;

            border-radius: 15px;

            font-size: 12px;

            font-weight: bold;

            margin-bottom: 10px;
        }

        .product-name {
            margin: 0 0 8px;

            font-size: 19px;

            color: #222;
        }

        .product-description {
            color: #777;

            font-size: 14px;

            line-height: 1.5;

            margin-bottom: 15px;
        }

        .product-price {
            color: #27ae60;

            font-size: 21px;

            font-weight: bold;

            margin-top: auto;
        }

        .product-unit {
            color: #777;

            font-size: 13px;

            margin-top: 4px;
        }

        .product-stock {
            margin-top: 10px;

            font-size: 13px;

            color: #666;
        }

        .stock-available {
            color: #27ae60;

            font-weight: bold;
        }

        .stock-out {
            color: #e74c3c;

            font-weight: bold;
        }

        .view-product {
            display: block;

            text-align: center;

            margin-top: 15px;

            padding: 11px;

            background: #27ae60;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 14px;

            font-weight: bold;
        }

        .view-product:hover {
            background: #219150;
        }

        .no-products {
            background: white;

            padding: 30px;

            text-align: center;

            border-radius: 12px;

            color: #777;
        }

        /* =====================================================
           REVIEWS
           ===================================================== */

        .reviews-section {
            padding: 30px 35px 40px;

            background: #ffffff;

            border-top:
                1px solid #eeeeee;
        }

        .reviews-header {
            display: flex;

            justify-content:
                space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 25px;
        }

        .reviews-header h2 {
            margin: 0 0 6px;

            font-size: 24px;

            color: #222;
        }

        .reviews-header p {
            margin: 0;

            color: #777;

            font-size: 14px;
        }

        .review-count {
            color: #777;

            font-size: 14px;

            white-space: nowrap;
        }

        .reviews-list {
            display: flex;

            flex-direction: column;

            gap: 16px;
        }

        .review-card {
            background: #f8f9fb;

            border:
                1px solid #eeeeee;

            border-radius: 12px;

            padding: 20px;
        }

        .review-top {
            display: flex;

            justify-content:
                space-between;

            align-items: flex-start;

            gap: 20px;
        }

        .review-customer {
            display: flex;

            flex-direction: column;

            gap: 5px;
        }

        .review-customer strong {
            color: #333;

            font-size: 15px;
        }

        .review-date {
            color: #999;

            font-size: 12px;
        }

        .review-rating {
            white-space: nowrap;
        }

        .star {
            color: #d5d5d5;

            font-size: 18px;
        }

        .star.filled {
            color: #d4a72c;
        }

        .review-comment {
            margin: 15px 0 0;

            color: #555;

            font-size: 14px;

            line-height: 1.7;
        }

        .farmer-response {
            margin-top: 18px;

            padding: 15px 18px;

            background: #eaf8ef;

            border-left:
                4px solid #27ae60;

            border-radius: 8px;
        }

        .farmer-response-title {
            font-size: 13px;

            font-weight: bold;

            color: #27ae60;

            margin-bottom: 7px;
        }

        .farmer-response p {
            margin: 0;

            color: #555;

            font-size: 14px;

            line-height: 1.6;
        }

        .farmer-response-date {
            display: block;

            margin-top: 8px;

            color: #888;

            font-size: 11px;
        }

        .no-reviews {
            background: #f8f9fb;

            border:
                1px solid #eeeeee;

            border-radius: 12px;

            padding: 35px 20px;

            text-align: center;
        }

        .no-reviews-icon {
            font-size: 28px;

            color: #d5d5d5;

            margin-bottom: 8px;
        }

        .no-reviews h3 {
            margin: 0 0 8px;

            color: #444;

            font-size: 18px;
        }

        .no-reviews p {
            margin: 0;

            color: #888;

            font-size: 14px;
        }

        /* =====================================================
           BOTTOM
           ===================================================== */

        .bottom-actions {
            padding: 0 35px 35px;

            background: #f8f9fb;
        }

        .back-button {
            display: inline-block;

            padding: 12px 22px;

            background: #7f8c8d;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;
        }

        .back-button:hover {
            background: #6c7a7b;
        }

        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 768px) {

            .details-container {
                width: 94%;

                margin-top: 25px;
            }

            .farmer-header {
                padding: 25px;
            }

            .farmer-name {
                font-size: 28px;
            }

            .farmer-content {
                grid-template-columns: 1fr;

                padding: 25px;

                gap: 25px;
            }

            .markets-section,
            .weekly-stock-section,
            .products-section,
            .reviews-section {
                padding: 25px;
            }

            .bottom-actions {
                padding: 0 25px 25px;
            }

            .info-row {
                flex-direction: column;

                gap: 5px;
            }

            .info-value {
                text-align: left;
            }

            .weekly-stock-header,
            .reviews-header {
                flex-direction: column;

                gap: 10px;
            }

            .week-label {
                white-space: normal;
            }

            .review-top {
                flex-direction: column;

                gap: 10px;
            }

            .review-rating {
                order: -1;
            }
        }

    </style>

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">

    <div class="details-container">

        <a
            href="farmers.php"
            class="back-link"
        >
            ← Back to Farmers
        </a>


        <div class="farmer-card">


            <!-- =================================================
                 FARMER HEADER
                 ================================================= -->

            <div class="farmer-header">

                <h1 class="farmer-name">

                    <?= e($farmer['stall_name']); ?>

                </h1>

                <span class="farmer-status">

                    Approved Farmer

                </span>

            </div>


            <!-- =================================================
                 FARMER INFORMATION + MAP
                 ================================================= -->

            <div class="farmer-content">


                <!-- FARMER INFORMATION -->

                <div>

                    <h2 class="section-title">
                        Farmer Information
                    </h2>


                    <div class="info-box">


                        <div class="info-row">

                            <span class="info-label">
                                Contact Person
                            </span>

                            <span class="info-value">

                                <?= e(
                                    $farmer['contact_person']
                                    ?: 'Not available'
                                ); ?>

                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Address
                            </span>

                            <span class="info-value">

                                <?= e(
                                    $farmer['address']
                                    ?: 'Not available'
                                ); ?>

                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Farmer ID
                            </span>

                            <span class="info-value">

                                #<?= (int) $farmer['id']; ?>

                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Products
                            </span>

                            <span class="info-value">

                                <?= count($products); ?>

                                available products

                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Markets
                            </span>

                            <span class="info-value">

                                <?= count($markets); ?>

                                market<?= count($markets) === 1 ? '' : 's'; ?>

                            </span>

                        </div>


                    </div>


                    <?php if (!empty($farmer['description'])): ?>

                        <div class="description-box">

                            <h3 class="section-title">
                                About the Farmer
                            </h3>

                            <div class="description">

                                <?= nl2br(
                                    e($farmer['description'])
                                ); ?>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>


                <!-- LOCATION -->

                <div>

                    <h2 class="section-title">
                        Location
                    </h2>

                    <div class="map-box">

                        <div id="map"></div>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 OPERATING DAYS / MARKETS
                 ================================================= -->

            <div class="markets-section">

                <div class="markets-header">

                    <h2>
                        Operating Days
                    </h2>

                    <p>
                        Markets where this farmer is currently available.
                    </p>

                </div>


                <?php if (!empty($markets)): ?>

                    <div class="markets-grid">

                        <?php foreach ($markets as $market): ?>

                            <div class="market-card">

                                <h3 class="market-name">

                                    <?= e($market['name']); ?>

                                </h3>


                                <?php if (!empty($market['operating_days'])): ?>

                                    <div class="market-days">

                                        <?= e(
                                            $market['operating_days']
                                        ); ?>

                                    </div>

                                <?php else: ?>

                                    <div class="market-detail">

                                        <strong>
                                            Days:
                                        </strong>

                                        <span>
                                            Not specified
                                        </span>

                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    !empty($market['opening_time']) ||
                                    !empty($market['closing_time'])
                                ): ?>

                                    <div class="market-detail">

                                        <strong>
                                            Hours:
                                        </strong>

                                        <span>

                                            <?php if (
                                                !empty($market['opening_time'])
                                            ): ?>

                                                <?= date(
                                                    'g:i A',
                                                    strtotime(
                                                        $market['opening_time']
                                                    )
                                                ); ?>

                                            <?php endif; ?>


                                            <?php if (
                                                !empty($market['opening_time']) &&
                                                !empty($market['closing_time'])
                                            ): ?>

                                                -
                                            <?php endif; ?>


                                            <?php if (
                                                !empty($market['closing_time'])
                                            ): ?>

                                                <?= date(
                                                    'g:i A',
                                                    strtotime(
                                                        $market['closing_time']
                                                    )
                                                ); ?>

                                            <?php endif; ?>

                                        </span>

                                    </div>

                                <?php endif; ?>


                                <?php if (!empty($market['address'])): ?>

                                    <div class="market-detail">

                                        <strong>
                                            Location:
                                        </strong>

                                        <span>
                                            <?= e(
                                                $market['address']
                                            ); ?>
                                        </span>

                                    </div>

                                <?php endif; ?>


                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="no-markets">

                        This farmer is not currently assigned
                        to any active market.

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 CURRENT WEEKLY STOCK
                 ================================================= -->

            <div class="weekly-stock-section">

                <div class="weekly-stock-header">

                    <div>

                        <h2>
                            Current Weekly Stock
                        </h2>

                        <p>
                            Products this farmer has listed for
                            the current week.
                        </p>

                    </div>


                    <span class="week-label">

                        Week of
                        <?= date(
                            'M d, Y',
                            strtotime($currentWeekStart)
                        ); ?>

                    </span>

                </div>


                <?php if (!empty($weeklyStock)): ?>

                    <div class="weekly-stock-grid">

                        <?php foreach ($weeklyStock as $stock): ?>

                            <div class="weekly-stock-card">


                                <div class="weekly-stock-image">

                                    <?php if (
                                        !empty($stock['image'])
                                    ): ?>

                                        <img
                                            src="../uploads/products/<?= e(
                                                $stock['image']
                                            ); ?>"
                                            alt="<?= e(
                                                $stock['name']
                                            ); ?>"
                                        >

                                    <?php else: ?>

                                        <div class="weekly-stock-no-image">

                                            No Image Available

                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div class="weekly-stock-content">


                                    <?php if (
                                        !empty(
                                            $stock['category_name']
                                        )
                                    ): ?>

                                        <span
                                            class="weekly-stock-category"
                                        >

                                            <?= e(
                                                $stock['category_name']
                                            ); ?>

                                        </span>

                                    <?php endif; ?>


                                    <h3 class="weekly-stock-name">

                                        <?= e(
                                            $stock['name']
                                        ); ?>

                                    </h3>


                                    <div class="weekly-stock-price">

                                        $

                                        <?= number_format(
                                            (float) $stock['price'],
                                            2
                                        ); ?>

                                    </div>


                                    <div class="weekly-stock-unit">

                                        per

                                        <?= e(
                                            $stock['unit']
                                        ); ?>

                                    </div>


                                    <div class="weekly-quantity">

                                        Planned this week:

                                        <strong>

                                            <?= number_format(
                                                (float) $stock[
                                                    'planned_quantity'
                                                ],
                                                2
                                            ); ?>

                                            <?= e(
                                                $stock['unit']
                                            ); ?>

                                        </strong>

                                    </div>


                                    <a
                                        href="product_details.php?id=<?= (int) $stock['product_id']; ?>"
                                        class="view-product"
                                    >
                                        View Product
                                    </a>


                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="no-weekly-stock">

                        <h3>
                            No weekly stock listed yet
                        </h3>

                        <p>
                            This farmer has not published
                            their current weekly stock.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 ALL PRODUCTS
                 ================================================= -->

            <div class="products-section">

                <div class="products-header">

                    <h2>
                        Products from this Farmer
                    </h2>

                    <span class="product-count">

                        <?= count($products); ?>

                        product<?= count($products) === 1 ? '' : 's'; ?>

                    </span>

                </div>


                <?php if (!empty($products)): ?>

                    <div class="products-grid">

                        <?php foreach ($products as $product): ?>

                            <div class="product-card">


                                <div class="product-image-container">

                                    <?php if (
                                        !empty($product['image'])
                                    ): ?>

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


                                <div class="product-content">


                                    <?php if (
                                        !empty(
                                            $product['category_name']
                                        )
                                    ): ?>

                                        <span
                                            class="category-badge"
                                        >

                                            <?= e(
                                                $product[
                                                    'category_name'
                                                ]
                                            ); ?>

                                        </span>

                                    <?php endif; ?>


                                    <h3 class="product-name">

                                        <?= e(
                                            $product['name']
                                        ); ?>

                                    </h3>


                                    <div class="product-description">

                                        <?= e(
                                            truncateText(
                                                $product['description']
                                                ??
                                                'No description available.',
                                                90
                                            )
                                        ); ?>

                                    </div>


                                    <div class="product-price">

                                        $

                                        <?= number_format(
                                            (float) $product['price'],
                                            2
                                        ); ?>

                                    </div>


                                    <div class="product-unit">

                                        per

                                        <?= e(
                                            $product['unit']
                                        ); ?>

                                    </div>


                                    <div class="product-stock">

                                        Stock:

                                        <?php if (
                                            (float)
                                            $product['stock_quantity']
                                            > 0
                                        ): ?>

                                            <span
                                                class="stock-available"
                                            >

                                                <?= number_format(
                                                    (float)
                                                    $product[
                                                        'stock_quantity'
                                                    ],
                                                    2
                                                ); ?>

                                                <?= e(
                                                    $product['unit']
                                                ); ?>

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="stock-out"
                                            >

                                                Out of Stock

                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <a
                                        href="product_details.php?id=<?= (int) $product['id']; ?>"
                                        class="view-product"
                                    >

                                        View Product

                                    </a>


                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="no-products">

                        This farmer currently has no
                        available products.

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 REVIEWS
                 ================================================= -->

            <div class="reviews-section">

                <div class="reviews-header">

                    <div>

                        <h2>
                            Customer Reviews
                        </h2>

                        <p>
                            Reviews from customers who have
                            purchased from this farmer.
                        </p>

                    </div>


                    <span class="review-count">

                        <?= count($reviews); ?>

                        review<?= count($reviews) === 1 ? '' : 's'; ?>

                    </span>

                </div>


                <?php if (!empty($reviews)): ?>

                    <div class="reviews-list">

                        <?php foreach ($reviews as $review): ?>

                            <div class="review-card">


                                <div class="review-top">


                                    <div class="review-customer">

                                        <strong>

                                            <?= e(
                                                $review[
                                                    'customer_name'
                                                ]
                                                ??
                                                'Customer'
                                            ); ?>

                                        </strong>

                                        <span class="review-date">

                                            <?= date(
                                                'M d, Y',
                                                strtotime(
                                                    $review[
                                                        'created_at'
                                                    ]
                                                )
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="review-rating">

                                        <?php for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ): ?>

                                            <span
                                                class="<?= $i <=
                                                    (int)
                                                    $review['rating']
                                                    ? 'star filled'
                                                    : 'star'; ?>"
                                            >
                                                ★
                                            </span>

                                        <?php endfor; ?>

                                    </div>


                                </div>


                                <?php if (
                                    !empty(
                                        $review['comment']
                                    )
                                ): ?>

                                    <p class="review-comment">

                                        <?= nl2br(
                                            e(
                                                $review['comment']
                                            )
                                        ); ?>

                                    </p>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $review[
                                            'farmer_response'
                                        ]
                                    )
                                ): ?>

                                    <div class="farmer-response">

                                        <div
                                            class="farmer-response-title"
                                        >
                                            Farmer Response
                                        </div>

                                        <p>

                                            <?= nl2br(
                                                e(
                                                    $review[
                                                        'farmer_response'
                                                    ]
                                                )
                                            ); ?>

                                        </p>


                                        <?php if (
                                            !empty(
                                                $review[
                                                    'farmer_response_at'
                                                ]
                                            )
                                        ): ?>

                                            <span
                                                class="farmer-response-date"
                                            >

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $review[
                                                            'farmer_response_at'
                                                        ]
                                                    )
                                                ); ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                <?php endif; ?>


                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="no-reviews">

                        <div class="no-reviews-icon">
                            ★
                        </div>

                        <h3>
                            No reviews yet
                        </h3>

                        <p>
                            This farmer hasn't received
                            any approved reviews yet.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 BOTTOM ACTION
                 ================================================= -->

            <div class="bottom-actions">

                <a
                    href="farmers.php"
                    class="back-button"
                >
                    ← Back to Farmers
                </a>

            </div>


        </div>

    </div>

</main>


<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


<script>

    const latitude =
        <?= $farmer['latitude'] !== null
            ? (float) $farmer['latitude']
            : 'null'; ?>;

    const longitude =
        <?= $farmer['longitude'] !== null
            ? (float) $farmer['longitude']
            : 'null'; ?>;


    const defaultLatitude = 42.3555;
    const defaultLongitude = -71.0565;


    const mapLatitude =
        latitude !== null
            ? latitude
            : defaultLatitude;


    const mapLongitude =
        longitude !== null
            ? longitude
            : defaultLongitude;


    const mapZoom =
        latitude !== null &&
        longitude !== null
            ? 13
            : 4;


    const map = L.map('map').setView(
        [
            mapLatitude,
            mapLongitude
        ],
        mapZoom
    );


    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,

            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    if (
        latitude !== null &&
        longitude !== null
    ) {

        const marker = L.marker([
            latitude,
            longitude
        ]).addTo(map);


        marker.bindPopup(
            '<strong><?= e(
                $farmer['stall_name']
            ); ?></strong><br>' +

            '<?= e(
                $farmer['address']
                ?: 'Address not available'
            ); ?>'
        ).openPopup();

    }


    setTimeout(function () {

        map.invalidateSize();

    }, 300);

</script>


</body>

</html>