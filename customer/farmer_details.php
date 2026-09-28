<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$farmerId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($farmerId <= 0) {
    redirect('farmers.php');
}

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
    die('Farmer query failed.');
}

$farmerStmt->bind_param(
    'i',
    $farmerId
);

if (!$farmerStmt->execute()) {
    die('Farmer query failed.');
}

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
        <title>Farmer Not Found | MarketLink</title>

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

    <main class="main-content customer-farmer-details-page">

        <section class="customer-page-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                    CUSTOMER / FARMER DETAILS
                </span>

                <h1>
                    Farmer <em>not found.</em>
                </h1>

                <p>
                    The farmer you're looking for is no longer available.
                </p>

            </div>

            <div class="customer-page-hero-mark">
                02
            </div>

        </section>

        <section class="customer-farmer-details-section">

            <div class="customer-farmer-empty">

                <span class="customer-farmer-empty-mark">
                    ✦
                </span>

                <strong>
                    Farmer not found.
                </strong>

                <span>
                    This farmer may no longer be approved or available.
                </span>

                <a
                    href="farmers.php"
                    class="customer-farmer-back"
                >
                    <i data-lucide=" arrow-left"></i>
                    Back to Farmers
                </a>

            </div>

        </section>

    </main>

    </body>
    </html>
    <?php
    exit;
}

$weekStartDate = date(
    'Y-m-d',
    strtotime('monday this week')
);

$moderationStatus = M_APPROVED;
$farmerStatus = A_APPROVED;

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
    WHERE wst.farmer_id = ?
      AND wst.is_active = 1
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
        'sisss',
        $weekStartDate,
        $farmerId,
        $moderationStatus,
        $farmerStatus,
        $weekStartDate
    );

    $generateStmt->execute();
    $generateStmt->close();
}

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

if (!$marketStmt) {
    die('Market query failed.');
}

$marketStmt->bind_param(
    'i',
    $farmerId
);

if (!$marketStmt->execute()) {
    die('Market query failed.');
}

$marketResult = $marketStmt->get_result();

while ($row = $marketResult->fetch_assoc()) {
    $markets[] = $row;
}

$marketStmt->close();

$weeklyStock = [];

$weeklyStockStmt = $conn->prepare("
    SELECT
        ws.id,
        ws.product_id,
        ws.week_start,
        ws.planned_quantity,
        ws.actual_quantity,
        ws.status,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        c.name AS category_name
    FROM weekly_stock ws
    INNER JOIN products p
        ON ws.product_id = p.id
       AND ws.farmer_id = p.farmer_id
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE ws.farmer_id = ?
      AND ws.week_start = ?
      AND ws.is_active = 1
      AND p.is_available = 1
      AND p.moderation_status = ?
    ORDER BY p.name ASC
");

if (!$weeklyStockStmt) {
    die('Weekly stock query failed.');
}

$weeklyStockStmt->bind_param(
    'iss',
    $farmerId,
    $weekStartDate,
    $moderationStatus
);

if (!$weeklyStockStmt->execute()) {
    die('Weekly stock query failed.');
}

$weeklyStockResult = $weeklyStockStmt->get_result();

while ($row = $weeklyStockResult->fetch_assoc()) {
    $weeklyStock[] = $row;
}

$weeklyStockStmt->close();

$products = [];

$productStmt = $conn->prepare("
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
        c.name AS category_name,
        ws.actual_quantity AS weekly_actual_quantity,
        ws.status AS weekly_status
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    LEFT JOIN weekly_stock ws
        ON ws.product_id = p.id
       AND ws.farmer_id = p.farmer_id
       AND ws.week_start = ?
       AND ws.is_active = 1
    WHERE p.farmer_id = ?
      AND p.is_available = 1
      AND p.moderation_status = ?
    ORDER BY p.created_at DESC
");

if (!$productStmt) {
    die('Product query failed.');
}

$productStmt->bind_param(
    'sis',
    $weekStartDate,
    $farmerId,
    $moderationStatus
);

if (!$productStmt->execute()) {
    die('Product query failed.');
}

$productResult = $productStmt->get_result();

while ($row = $productResult->fetch_assoc()) {

    if ($row['weekly_actual_quantity'] !== null) {
        $row['display_stock'] =
            (float) $row['weekly_actual_quantity'];

        $row['display_status'] =
            $row['weekly_status'] ?? 'available';
    } else {
        $row['display_stock'] =
            (float) $row['stock_quantity'];

        $row['display_status'] = 'available';
    }

    $products[] = $row;
}

$productStmt->close();

$averageRating = 0;
$reviewCount = 0;

$ratingStmt = $conn->prepare("
    SELECT
        COALESCE(AVG(rating), 0) AS average_rating,
        COUNT(id) AS review_count
    FROM reviews
    WHERE farmer_id = ?
      AND product_id IS NULL
      AND status = 'approved'
");

if (!$ratingStmt) {
    die('Review rating query failed.');
}

$ratingStmt->bind_param(
    'i',
    $farmerId
);

if (!$ratingStmt->execute()) {
    die('Review rating query failed.');
}

$ratingResult = $ratingStmt->get_result();
$ratingData = $ratingResult->fetch_assoc();

$ratingStmt->close();

if ($ratingData) {
    $averageRating =
        (float) $ratingData['average_rating'];

    $reviewCount =
        (int) $ratingData['review_count'];
}

$reviews = [];

$reviewsStmt = $conn->prepare("
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

if (!$reviewsStmt) {
    die('Review query failed.');
}

$reviewsStmt->bind_param(
    'i',
    $farmerId
);

if (!$reviewsStmt->execute()) {
    die('Review query failed.');
}

$reviewsResult = $reviewsStmt->get_result();

while ($row = $reviewsResult->fetch_assoc()) {
    $reviews[] = $row;
}

$reviewsStmt->close();

$farmerLatitude = !empty($farmer['latitude'])
    ? (float) $farmer['latitude']
    : 42.3555;

$farmerLongitude = !empty($farmer['longitude'])
    ? (float) $farmer['longitude']
    : -71.0565;

$roundedRating = (int) round($averageRating);

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
        <?= e($farmer['stall_name']) ?> | MarketLink
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
        href="../assets/css/customer.css"
    >

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <link
        rel="stylesheet"
        href="../assets/fontawesome/css/all.min.css"
    >

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content customer-farmer-details-page">

    <section class="customer-page-hero">

        <div class="customer-page-hero-copy">

            <span class="eyebrow">
                CUSTOMER / FARMER DETAILS
            </span>

            <h1>
                Meet <em>the farmer.</em>
            </h1>

            <p>
                Learn about
                <?= e($farmer['stall_name']) ?>,
                explore their markets, see their weekly stock,
                and shop their available products.
            </p>

        </div>

        <div class="customer-page-hero-mark">
            02
        </div>

    </section>

    <section class="customer-farmer-overview-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    01 / FARMER
                </span>

                <h2>
                    Meet the <em>person.</em>
                </h2>

            </div>

            <a
                href="farmers.php"
                class="customer-farmer-back"
            >
                <i data-lucide=" arrow-left"></i>
                All Farmers
            </a>

        </div>

        <div class="customer-farmer-overview">

            <div class="customer-farmer-profile-card">

                <div class="customer-farmer-profile-mark">
                    ✦
                </div>

                <span class="customer-farmer-label">
                    FARMER
                </span>

                <h3>
                    <?= e($farmer['stall_name']) ?>
                </h3>

                <?php if (!empty($farmer['description'])): ?>

                    <p class="customer-farmer-profile-description">
                        <?= nl2br(e($farmer['description'])) ?>
                    </p>

                <?php else: ?>

                    <p class="customer-farmer-profile-description">
                        Local produce from a MarketLink farmer.
                    </p>

                <?php endif; ?>

                <div class="customer-farmer-profile-details">

                    <div class="customer-farmer-profile-row">

                        <span>
                            <i data-lucide=" fa-user"></i>
                            Contact Person
                        </span>

                        <strong>
                            <?= e(
                                $farmer['contact_person']
                                ?: 'Not available'
                            ) ?>
                        </strong>

                    </div>

                    <div class="customer-farmer-profile-row">

                        <span>
                            <i data-lucide=" fa-location-dot"></i>
                            Location
                        </span>

                        <strong>
                            <?= e(
                                $farmer['address']
                                ?: 'Not available'
                            ) ?>
                        </strong>

                    </div>

                    <div class="customer-farmer-profile-row">

                        <span>
                            <i data-lucide=" fa-calendar"></i>
                            Joined MarketLink
                        </span>

                        <strong>
                            <?= formatDate($farmer['created_at']) ?>
                        </strong>

                    </div>

                </div>

            </div>

            <div class="customer-farmer-map-card">

                <div class="customer-farmer-map-header">

                    <div>

                        <span class="customer-farmer-label">
                            LOCATION
                        </span>

                        <h3>
                            Find the <em>stall.</em>
                        </h3>

                    </div>

                    <span class="customer-farmer-map-icon">
                        <i data-lucide=" fa-location-dot"></i>
                    </span>

                </div>

                <div
                    id="farmerMap"
                    class="customer-farmer-map"
                ></div>

                <?php if (!empty($farmer['address'])): ?>

                    <div class="customer-farmer-map-address">

                        <i data-lucide=" fa-location-dot"></i>

                        <span>
                            <?= e($farmer['address']) ?>
                        </span>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

    <section class="customer-farmer-markets-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    02 / MARKETS
                </span>

                <h2>
                    Where to <em>find them.</em>
                </h2>

            </div>

            <span class="customer-record-count">
                <?= count($markets) ?>
                <?= count($markets) === 1 ? 'market' : 'markets' ?>
            </span>

        </div>

        <?php if (empty($markets)): ?>

            <div class="customer-farmer-empty">

                <span class="customer-farmer-empty-mark">
                    ✦
                </span>

                <strong>
                    No active markets.
                </strong>

                <span>
                    This farmer is not currently assigned to an active market.
                </span>

            </div>

        <?php else: ?>

            <div class="customer-farmer-markets-grid">

                <?php foreach ($markets as $index => $market): ?>

                    <article class="customer-farmer-market-card">

                        <div class="customer-farmer-market-number">
                            <?= str_pad(
                                $index + 1,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) ?>
                        </div>

                        <div class="customer-farmer-market-content">

                            <span class="customer-farmer-label">
                                MARKET
                            </span>

                            <h3>
                                <?= e($market['name']) ?>
                            </h3>

                            <?php if (!empty($market['address'])): ?>

                                <p>
                                    <i data-lucide=" fa-location-dot"></i>
                                    <?= e($market['address']) ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($market['operating_days'])): ?>

                                <div class="customer-farmer-market-detail">

                                    <span>
                                        <i data-lucide=" fa-calendar-days"></i>
                                        Market Days
                                    </span>

                                    <strong>
                                        <?= e(
                                            $market['operating_days']
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endif; ?>

                            <?php if (
                                !empty($market['opening_time'])
                                && !empty($market['closing_time'])
                            ): ?>

                                <div class="customer-farmer-market-detail">

                                    <span>
                                        <i data-lucide=" clock"></i>
                                        Opening Hours
                                    </span>

                                    <strong>
                                        <?= e(
                                            date(
                                                'g:i A',
                                                strtotime(
                                                    $market['opening_time']
                                                )
                                            )
                                        ) ?>
                                        —
                                        <?= e(
                                            date(
                                                'g:i A',
                                                strtotime(
                                                    $market['closing_time']
                                                )
                                            )
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

    <section class="customer-farmer-weekly-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    03 / THIS WEEK
                </span>

                <h2>
                    What's fresh <em>right now.</em>
                </h2>

            </div>

            <span class="customer-record-count">
                Week of <?= date('M j', strtotime($weekStartDate)) ?>
            </span>

        </div>

        <?php if (empty($weeklyStock)): ?>

            <div class="customer-farmer-empty">

                <span class="customer-farmer-empty-mark">
                    ✦
                </span>

                <strong>
                    No weekly stock posted.
                </strong>

                <span>
                    This farmer has not listed weekly availability yet.
                </span>

            </div>

        <?php else: ?>

            <div class="customer-farmer-weekly-grid">

                <?php foreach ($weeklyStock as $stockItem): ?>

                    <?php

                    $weeklyQuantity =
                        (float) $stockItem['actual_quantity'];

                    $weeklyStatus =
                        $stockItem['status'] ?? 'available';

                    $isSoldOut =
                        $weeklyStatus === 'sold_out'
                        || $weeklyQuantity <= 0;

                    $isUnavailable =
                        $weeklyStatus === 'unavailable';

                    ?>

                    <article class="customer-farmer-weekly-card">

                        <div class="customer-farmer-weekly-top">

                            <span class="customer-farmer-label">
                                <?= e(
                                    $stockItem['category_name']
                                    ?: 'PRODUCE'
                                ) ?>
                            </span>

                            <span
                                class="
                                    customer-farmer-stock-status
                                    <?= $isUnavailable
                                        ? 'is-unavailable'
                                        : ($isSoldOut
                                            ? 'is-sold-out'
                                            : 'is-available') ?>
                                "
                            >
                                <?php if ($isUnavailable): ?>

                                    Unavailable

                                <?php elseif ($isSoldOut): ?>

                                    Sold Out

                                <?php else: ?>

                                    Available

                                <?php endif; ?>
                            </span>

                        </div>

                        <h3>
                            <?= e($stockItem['name']) ?>
                        </h3>

                        <?php if (!empty($stockItem['description'])): ?>

                            <p>
                                <?= e(
                                    truncateText(
                                        $stockItem['description'],
                                        85
                                    )
                                ) ?>
                            </p>

                        <?php endif; ?>

                        <div class="customer-farmer-weekly-bottom">

                            <strong>
                                <?= e($weeklyQuantity) ?>
                                <?php if (!empty($stockItem['unit'])): ?>
                                    <?= e($stockItem['unit']) ?>
                                <?php endif; ?>
                            </strong>

                            <span>
                                available this week
                            </span>

                        </div>

                        <a
                            href="product_details.php?id=<?= (int) $stockItem['product_id'] ?>"
                            class="customer-farmer-product-link"
                        >
                            View Product
                            <i data-lucide=" arrow-right"></i>
                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

    <section class="customer-farmer-products-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    04 / SHOP
                </span>

                <h2>
                    Browse their <em>produce.</em>
                </h2>

            </div>

            <span class="customer-record-count">
                <?= count($products) ?>
                <?= count($products) === 1 ? 'product' : 'products' ?>
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
                    This farmer does not currently have any approved products available.
                </span>

            </div>

        <?php else: ?>

            <div class="customer-products-grid">

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
                        (float) ($product['display_stock'] ?? 0);

                    $stockStatus =
                        $product['display_status'] ?? 'available';

                    $categoryName =
                        $product['category_name']
                        ?? 'Uncategorized';

                    $isSoldOut =
                        $stockStatus === 'sold_out';

                    $isUnavailable =
                        $stockStatus === 'unavailable';

                    $canAddToCart =
                        !$isSoldOut
                        && !$isUnavailable
                        && $stock > 0;

                    ?>

                    <article class="customer-product-card">

                        <div class="customer-product-image">

                            <?php if (!empty($product['image'])): ?>

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

                                <?= e($farmer['stall_name']) ?>

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

                                <?php if ($canAddToCart): ?>

                                    <a
                                        href="add_to_cart.php?id=<?= $productId ?>"
                                        class="customer-product-add"
                                    >
                                        <i data-lucide=" fa-cart-plus"></i>
                                        Add to Cart
                                    </a>

                                <?php else: ?>

                                    <button
                                        type="button"
                                        class="customer-product-add customer-product-disabled"
                                        disabled
                                    >

                                        <i
                                            data-lucide=" <?= $isUnavailable
                                                ? 'fa-box-open'
                                                : 'fa-box-open' ?>"
                                        ></i>

                                        <?php if ($isUnavailable): ?>

                                            Currently Unavailable

                                        <?php elseif ($isSoldOut): ?>

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

        <?php endif; ?>

    </section>

    <section class="customer-farmer-reviews-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    05 / REVIEWS
                </span>

                <h2>
                    From their <em>customers.</em>
                </h2>

            </div>

            <span class="customer-record-count">
                <?= $reviewCount ?>
                <?= $reviewCount === 1 ? 'review' : 'reviews' ?>
            </span>

        </div>

        <div class="customer-farmer-rating-card">

            <div class="customer-farmer-rating-main">

                <strong>
                    <?= number_format($averageRating, 1) ?>
                </strong>

                <span>
                    / 5
                </span>

            </div>

            <div class="customer-farmer-rating-stars">

                <?php for ($i = 1; $i <= 5; $i++): ?>

                    <span>
                        <?= $i <= $roundedRating ? '★' : '☆' ?>
                    </span>

                <?php endfor; ?>

            </div>

            <p>
                Based on
                <?= $reviewCount ?>
                approved
                <?= $reviewCount === 1 ? 'review' : 'reviews' ?>.
            </p>

            <a
                href="reviews.php"
                class="customer-farmer-review-link"
            >
                Write a Review
                <i data-lucide=" arrow-right"></i>
            </a>

        </div>

        <?php if (!empty($reviews)): ?>

            <div class="customer-farmer-reviews-list">

                <?php foreach ($reviews as $review): ?>

                    <?php
                    $reviewRating =
                        (int) $review['rating'];
                    ?>

                    <article class="customer-farmer-review-card">

                        <div class="customer-farmer-review-top">

                            <div>

                                <strong>
                                    <?= e(
                                        $review['customer_name']
                                        ?: 'Customer'
                                    ) ?>
                                </strong>

                                <span>
                                    <?= formatDate(
                                        $review['created_at']
                                    ) ?>
                                </span>

                            </div>

                            <div class="customer-farmer-review-stars">

                                <?php for (
                                    $i = 1;
                                    $i <= 5;
                                    $i++
                                ): ?>

                                    <span>
                                        <?= $i <= $reviewRating
                                            ? '★'
                                            : '☆' ?>
                                    </span>

                                <?php endfor; ?>

                            </div>

                        </div>

                        <?php if (!empty($review['comment'])): ?>

                            <p class="customer-farmer-review-comment">
                                <?= nl2br(
                                    e($review['comment'])
                                ) ?>
                            </p>

                        <?php endif; ?>

                        <?php if (
                            !empty($review['farmer_response'])
                        ): ?>

                            <div class="customer-farmer-response">

                                <div class="customer-farmer-response-title">

                                    <i data-lucide=" fa-reply"></i>

                                    Farmer Response

                                </div>

                                <p>
                                    <?= nl2br(
                                        e(
                                            $review['farmer_response']
                                        )
                                    ) ?>
                                </p>

                                <?php if (
                                    !empty(
                                        $review['farmer_response_at']
                                    )
                                ): ?>

                                    <span>
                                        <?= formatDate(
                                            $review['farmer_response_at']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="customer-farmer-empty">

                <span class="customer-farmer-empty-mark">
                    ✦
                </span>

                <strong>
                    No approved reviews yet.
                </strong>

                <span>
                    Customer reviews for this farmer will appear here.
                </span>

            </div>

        <?php endif; ?>

    </section>

</main>
<script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>

<script>

const farmerLatitude = <?= json_encode($farmerLatitude) ?>;
const farmerLongitude = <?= json_encode($farmerLongitude) ?>;
const farmerName = <?= json_encode($farmer['stall_name']) ?>;
const farmerAddress = <?= json_encode($farmer['address'] ?? '') ?>;

const farmerMap = L
    .map('farmerMap')
    .setView(
        [
            farmerLatitude,
            farmerLongitude
        ],
        13
    );

L.tileLayer(
    'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
    {
        maxZoom: 20,
        attribution: '&copy; OpenStreetMap contributors'
    }
).addTo(farmerMap);

L.marker([
    farmerLatitude,
    farmerLongitude
])
.addTo(farmerMap)
.bindPopup(
    `<strong>${farmerName}</strong><br>${farmerAddress}`
)
.openPopup();

setTimeout(() => {
    farmerMap.invalidateSize();
}, 150);

</script>

</body>
</html>