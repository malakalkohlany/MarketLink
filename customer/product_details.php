<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$productId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

$from = $_GET['from'] ?? 'dashboard';

if ($from === 'products') {
    $backPage = 'products.php';
} else {
    $backPage = 'dashboard.php';
}

$backText = 'Back';

if ($productId <= 0) {
    redirect($backPage);
}

$weekStart = date(
    'Y-m-d',
    strtotime('monday this week')
);

$generateStmt = $conn->prepare("
    INSERT INTO weekly_stock (
        farmer_id,
        product_id,
        week_start,
        planned_quantity,
        actual_quantity,
        status,
        is_active
    )
    SELECT
        wst.farmer_id,
        wst.product_id,
        ?,
        wst.default_quantity,
        wst.default_quantity,
        CASE
            WHEN wst.default_quantity <= 0
                THEN 'sold_out'
            ELSE 'available'
        END,
        1
    FROM weekly_stock_templates wst
    INNER JOIN products p
        ON p.id = wst.product_id
    INNER JOIN farmers f
        ON f.id = wst.farmer_id
    WHERE wst.product_id = ?
      AND wst.is_active = 1
      AND p.is_available = 1
      AND p.moderation_status = 'approved'
      AND f.approval_status = 'approved'
      AND NOT EXISTS (
          SELECT 1
          FROM weekly_stock ws
          WHERE ws.farmer_id = wst.farmer_id
            AND ws.product_id = wst.product_id
            AND ws.week_start = ?
      )
");

if ($generateStmt) {
    $generateStmt->bind_param(
        "sis",
        $weekStart,
        $productId,
        $weekStart
    );

    $generateStmt->execute();
    $generateStmt->close();
}

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.farmer_id,
        p.category_id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.stock_quantity,
        p.image,
        p.is_available,
        p.moderation_status,
        p.created_at,
        c.name AS category_name,
        f.stall_name AS farmer_name,
        f.contact_person AS farmer_contact,
        f.description AS farmer_description,
        f.address AS farmer_address,
        f.approval_status AS farmer_approval_status,
        ws.planned_quantity AS weekly_planned_quantity,
        ws.actual_quantity AS weekly_actual_quantity,
        ws.status AS weekly_status,
        ws.week_start AS weekly_week_start
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    LEFT JOIN farmers f
        ON p.farmer_id = f.id
    LEFT JOIN weekly_stock ws
        ON ws.product_id = p.id
        AND ws.farmer_id = p.farmer_id
        AND ws.week_start = ?
    WHERE p.id = ?
      AND p.is_available = 1
      AND p.moderation_status = 'approved'
      AND f.approval_status = 'approved'
    LIMIT 1
");

if (!$stmt) {
    die('Database query failed.');
}

$stmt->bind_param(
    "si",
    $weekStart,
    $productId
);

if (!$stmt->execute()) {
    die('Product query failed.');
}

$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    redirect($backPage);
}

$hasWeeklyStock = !empty(
    $product['weekly_week_start']
);

$weeklyPlannedQuantity = $hasWeeklyStock
    ? (float) $product['weekly_planned_quantity']
    : 0;

$weeklyActualQuantity = $hasWeeklyStock
    ? (float) $product['weekly_actual_quantity']
    : 0;

$weeklyStatus =
    $product['weekly_status'] ?? null;

$isWeeklyAvailable =
    $hasWeeklyStock
    && $weeklyStatus === 'available'
    && $weeklyActualQuantity > 0;

$errorMessage = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_quantity') {
        $errorMessage =
            'Please enter a valid quantity.';
    }

    if ($_GET['error'] === 'stock') {
        $errorMessage =
            'The selected quantity is greater than the available stock.';
    }

    if ($_GET['error'] === 'unavailable') {
        $errorMessage =
            'This product is currently unavailable.';
    }
}

$averageRating = 0;
$reviewCount = 0;

$ratingStmt = $conn->prepare("
    SELECT
        COALESCE(AVG(rating), 0) AS average_rating,
        COUNT(id) AS review_count
    FROM reviews
    WHERE product_id = ?
      AND status = 'approved'
");

if ($ratingStmt) {
    $ratingStmt->bind_param(
        "i",
        $productId
    );

    $ratingStmt->execute();

    $ratingResult =
        $ratingStmt->get_result();

    $ratingData =
        $ratingResult->fetch_assoc();

    $ratingStmt->close();

    if ($ratingData) {
        $averageRating =
            (float) $ratingData['average_rating'];

        $reviewCount =
            (int) $ratingData['review_count'];
    }
}

$approvedReviews = [];

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
        ON u.id = r.customer_id
    WHERE r.product_id = ?
      AND r.status = 'approved'
    ORDER BY r.created_at DESC
");

if ($reviewsStmt) {
    $reviewsStmt->bind_param(
        "i",
        $productId
    );

    $reviewsStmt->execute();

    $reviewsResult =
        $reviewsStmt->get_result();

    while (
        $reviewRow =
        $reviewsResult->fetch_assoc()
    ) {
        $approvedReviews[] =
            $reviewRow;
    }

    $reviewsStmt->close();
}

$roundedRating =
    (int) round($averageRating);

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
        <?= e($product['name']) ?> | MarketLink
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
        href="../assets/fontawesome/css/all.min.css"
    >
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content customer-product-details-page">

    <section class="customer-page-hero customer-product-details-hero">

        <div class="customer-page-hero-copy">

            <span class="eyebrow">
                CUSTOMER / PRODUCT
            </span>

            <h1>
                <?= e($product['name']) ?> from
                <em><?= e($product['farmer_name']) ?>.</em>
            </h1>

            <p>
                Explore this locally grown product,
                check this week's availability,
                and learn more about the farmer behind it.
            </p>

        </div>

        <div class="customer-page-hero-mark">
            02
        </div>

    </section>

    <section class="customer-product-details-section">

        <div class="customer-product-details-back">

            <a
                href="<?= e($backPage) ?>"
                class="customer-product-back-link"
            >
                <i data-lucide=" arrow-left"></i>
                <?= e($backText) ?>
            </a>

        </div>

        <?php if ($errorMessage): ?>

            <div class="customer-products-notice customer-products-error">

                <span class="customer-products-notice-icon">
                    <i data-lucide="circle-alert"></i>
                </span>

                <div>

                    <strong>
                        Something needs your attention.
                    </strong>

                    <span>
                        <?= e($errorMessage) ?>
                    </span>

                </div>

            </div>

        <?php endif; ?>

        <article class="customer-product-detail-card">

            <div class="customer-product-detail-main">

                <div class="customer-product-detail-image">

                    <?php if (!empty($product['image'])): ?>

                        <img
                            src="../<?= e($product['image']) ?>"
                            alt="<?= e($product['name']) ?>"
                        >

                    <?php else: ?>

                        <div class="customer-product-detail-image-empty">

                            <span>
                                ✦
                            </span>

                            <small>
                                No image available
                            </small>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="customer-product-detail-content">

                    <?php if (!empty($product['category_name'])): ?>

                        <div class="customer-product-category">
                            <?= e($product['category_name']) ?>
                        </div>

                    <?php endif; ?>

                    <h2 class="customer-product-detail-name">
                        <?= e($product['name']) ?>
                    </h2>

                    <div class="customer-product-detail-farmer">

                        <i data-lucide=" store"></i>

                        <a
                            href="farmer_details.php?id=<?= (int) $product['farmer_id'] ?>"
                        >
                            <?= e($product['farmer_name']) ?>
                        </a>

                    </div>

                    <div class="customer-product-detail-description">

                        <?= nl2br(
                            e(
                                $product['description']
                                ?? 'No description available.'
                            )
                        ) ?>

                    </div>

                    <div class="customer-product-detail-price">

                        <strong>
                            $<?= formatPrice(
                                (float) $product['price']
                            ) ?>
                        </strong>

                        <?php if (!empty($product['unit'])): ?>

                            <span>
                                / <?= e($product['unit']) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                    <div class="customer-product-detail-stock-card">

                        <div class="customer-product-detail-stock-row">

                            <span>
                                This Week's Stock
                            </span>

                            <strong>

                                <?php if ($hasWeeklyStock): ?>

                                    <?= number_format(
                                        $weeklyActualQuantity,
                                        2
                                    ) ?>

                                    <?= e($product['unit']) ?>

                                <?php else: ?>

                                    Not Set

                                <?php endif; ?>

                            </strong>

                        </div>

                        <div class="customer-product-detail-stock-row">

                            <span>
                                Availability
                            </span>

                            <strong>

                                <?php if ($isWeeklyAvailable): ?>

                                    <span class="stock-available">
                                        Available
                                    </span>

                                <?php elseif (
                                    $hasWeeklyStock
                                    && $weeklyStatus === 'sold_out'
                                ): ?>

                                    <span class="stock-sold-out">
                                        Sold Out This Week
                                    </span>

                                <?php elseif (
                                    $hasWeeklyStock
                                    && $weeklyStatus === 'unavailable'
                                ): ?>

                                    <span class="stock-unavailable">
                                        Currently Unavailable
                                    </span>

                                <?php else: ?>

                                    <span class="stock-unavailable">
                                        Weekly Stock Not Set
                                    </span>

                                <?php endif; ?>

                            </strong>

                        </div>

                        <div class="customer-product-detail-stock-row">

                            <span>
                                Category
                            </span>

                            <strong>
                                <?= e(
                                    $product['category_name']
                                    ?? 'Not specified'
                                ) ?>
                            </strong>

                        </div>

                        <div class="customer-product-detail-stock-row">

                            <span>
                                Added
                            </span>

                            <strong>
                                <?= date(
                                    'M d, Y',
                                    strtotime(
                                        $product['created_at']
                                    )
                                ) ?>
                            </strong>

                        </div>

                    </div>

                    <div class="customer-product-detail-actions">

                        <?php if ($isWeeklyAvailable): ?>

                            <a
                                href="add_to_cart.php?id=<?= (int) $product['id'] ?>"
                                class="customer-product-add"
                            >
                                <i data-lucide="shopping-cart-plus"></i>
                                Add to Cart
                            </a>

                        <?php else: ?>

                            <button
                                type="button"
                                class="customer-product-add customer-product-disabled"
                                disabled
                            >

                                <?php if (!$hasWeeklyStock): ?>

                                    <i data-lucide="calendar-x"></i>
                                    Weekly Stock Not Set

                                <?php elseif (
                                    $weeklyStatus === 'sold_out'
                                    || $weeklyActualQuantity <= 0
                                ): ?>

                                    <i data-lucide="package-open"></i>
                                    Sold Out This Week

                                <?php else: ?>

                                    <i data-lucide="ban"></i>
                                    Currently Unavailable

                                <?php endif; ?>

                            </button>

                        <?php endif; ?>

                        <a
                            href="<?= e($backPage) ?>"
                            class="customer-product-details"
                        >
                            <i data-lucide=" arrow-left"></i>
                            Back to Products
                        </a>

                    </div>

                </div>

            </div>

        </article>

    </section>

    <section class="customer-product-farmer-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    03 / THE FARMER
                </span>

                <h2>
                    Meet the local
                    <em>seller.</em>
                </h2>

            </div>

        </div>

        <article class="customer-product-farmer-card">

            <div class="customer-product-farmer-card-header">

                <div class="customer-product-farmer-icon">
                    <i data-lucide=" store"></i>
                </div>

                <div>

                    <span>
                        LOCAL FARMER
                    </span>

                    <h3>
                        <a
                            href="farmer_details.php?id=<?= (int) $product['farmer_id'] ?>"
                        >
                            <?= e($product['farmer_name']) ?>
                        </a>
                    </h3>

                </div>

            </div>

            <div class="customer-product-farmer-info">

                <div class="customer-product-farmer-info-row">

                    <span>
                        Contact Person
                    </span>

                    <strong>
                        <?= e(
                            $product['farmer_contact']
                            ?? 'Not available'
                        ) ?>
                    </strong>

                </div>

                <div class="customer-product-farmer-info-row">

                    <span>
                        Location
                    </span>

                    <strong>
                        <?= e(
                            $product['farmer_address']
                            ?? 'Not available'
                        ) ?>
                    </strong>

                </div>

            </div>

            <?php if (!empty($product['farmer_description'])): ?>

                <div class="customer-product-farmer-description">

                    <span>
                        ABOUT THE FARMER
                    </span>

                    <p>
                        <?= nl2br(
                            e(
                                $product['farmer_description']
                            )
                        ) ?>
                    </p>

                </div>

            <?php endif; ?>

            <a
                href="farmer_details.php?id=<?= (int) $product['farmer_id'] ?>"
                class="customer-product-details customer-product-farmer-link"
            >
                View Farmer
                <i data-lucide=" arrow-right"></i>
            </a>

        </article>

    </section>

    <section class="customer-product-reviews-section">

        <div class="customer-section-heading">

            <div>

                <span class="customer-section-number">
                    04 / CUSTOMER VOICES
                </span>

                <h2>
                    Product
                    <em>reviews.</em>
                </h2>

            </div>

            <span class="customer-record-count">
                <?= $reviewCount ?>
                <?= $reviewCount === 1 ? 'review' : 'reviews' ?>
            </span>

        </div>

        <div class="customer-product-reviews-summary">

            <div class="customer-product-rating-score">

                <strong>
                    <?= number_format(
                        $averageRating,
                        1
                    ) ?>
                </strong>

                <span>
                    / 5
                </span>

            </div>

            <div class="customer-product-rating-stars">

                <?php for (
                    $i = 1;
                    $i <= 5;
                    $i++
                ): ?>

                    <i
                        data-lucide="star"
                        class="<?= $i <= $roundedRating ? 'rating-star-filled' : '' ?>"
                    ></i>

                <?php endfor; ?>

            </div>

            <div class="customer-product-rating-copy">

                <strong>
                    Customer rating
                </strong>

                <span>
                    Based on
                    <?= $reviewCount ?>
                    <?= $reviewCount === 1
                        ? 'approved review'
                        : 'approved reviews' ?>
                </span>

            </div>

            <a
                href="reviews.php?product_id=<?= (int) $product['id'] ?>"
                class="customer-product-add customer-product-review-button"
            >
                <i data-lucide="pen"></i>
                Write a Review
            </a>

        </div>

        <?php if (!empty($approvedReviews)): ?>

            <div class="customer-product-reviews-list">

                <?php foreach ($approvedReviews as $review): ?>

                    <article class="customer-product-review-card">

                        <div class="customer-product-review-top">

                            <div>

                                <strong>
                                    <?= e(
                                        $review['customer_name']
                                        ?? 'Customer'
                                    ) ?>
                                </strong>

                                <div class="customer-product-review-stars">

                                    <?php
                                    $reviewRating =
                                        (int) $review['rating'];
                                    ?>

                                    <?php for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ): ?>

                                        <i
                                            data-lucide="star"
                                            class="<?= $i <= $reviewRating ? 'rating-star-filled' : '' ?>"
                                        ></i>

                                    <?php endfor; ?>

                                </div>

                            </div>

                            <span>
                                <?= date(
                                    'M d, Y',
                                    strtotime(
                                        $review['created_at']
                                    )
                                ) ?>
                            </span>

                        </div>

                        <?php if (!empty($review['comment'])): ?>

                            <p class="customer-product-review-comment">
                                <?= nl2br(
                                    e(
                                        $review['comment']
                                    )
                                ) ?>
                            </p>

                        <?php endif; ?>

                        <?php if (
                            !empty(
                                $review['farmer_response']
                            )
                        ): ?>

                            <div class="customer-product-review-response">

                                <div>

                                    <i data-lucide="reply"></i>

                                    <strong>
                                        Farmer Response
                                    </strong>

                                </div>

                                <p>
                                    <?= nl2br(
                                        e(
                                            $review['farmer_response']
                                        )
                                    ) ?>
                                </p>

                            </div>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="customer-products-empty customer-product-no-reviews">

                <span class="customer-products-empty-mark">
                    ✦
                </span>

                <strong>
                    No reviews yet.
                </strong>

                <span>
                    Be the first customer to share your experience
                    with this product.
                </span>

            </div>

        <?php endif; ?>

    </section>

</main>

<script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

<script src="../assets/js/app.js"></script>

</body>
</html>