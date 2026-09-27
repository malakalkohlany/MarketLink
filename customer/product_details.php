<?php
require_once __DIR__ . '/../includes/include.php';
requireRole(R_CUSTOMER);

$productId = isset($_GET['id'])
    ? (int)$_GET['id']
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

$weekStart = date('Y-m-d', strtotime('monday this week'));

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
            WHEN wst.default_quantity <= 0 THEN 'sold_out'
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

$stmt->bind_param(
    "si",
    $weekStart,
    $productId
);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >
        <title>Product Not Found - MarketLink</title>
    </head>
    <body>
        <div class="message-card">
            <h2>Product Not Found</h2>
            <p>
                This product is not available or no longer exists.
            </p>
            <a
                href="<?= e($backPage) ?>"
                class="back-button"
            >
                <?= e($backText) ?>
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$hasWeeklyStock = !empty($product['weekly_week_start']);

$weeklyPlannedQuantity = $hasWeeklyStock
    ? (float)$product['weekly_planned_quantity']
    : 0;

$weeklyActualQuantity = $hasWeeklyStock
    ? (float)$product['weekly_actual_quantity']
    : 0;

$weeklyStatus = $product['weekly_status'] ?? null;

$isWeeklyAvailable =
    $hasWeeklyStock &&
    $weeklyStatus === 'available' &&
    $weeklyActualQuantity > 0;

$errorMessage = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_quantity') {
        $errorMessage = 'Please enter a valid quantity.';
    }

    if ($_GET['error'] === 'stock') {
        $errorMessage = 'The selected quantity is greater than the available stock.';
    }

    if ($_GET['error'] === 'unavailable') {
        $errorMessage = 'This product is currently unavailable.';
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

$ratingStmt->bind_param(
    "i",
    $productId
);

$ratingStmt->execute();
$ratingResult = $ratingStmt->get_result();
$ratingData = $ratingResult->fetch_assoc();
$ratingStmt->close();

if ($ratingData) {
    $averageRating = (float)$ratingData['average_rating'];
    $reviewCount = (int)$ratingData['review_count'];
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

$reviewsStmt->bind_param(
    "i",
    $productId
);

$reviewsStmt->execute();
$reviewsResult = $reviewsStmt->get_result();

while ($reviewRow = $reviewsResult->fetch_assoc()) {
    $approvedReviews[] = $reviewRow;
}

$reviewsStmt->close();
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
        <?= e($product['name']) ?> - MarketLink
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
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="details-container">
        <a
            href="<?= e($backPage) ?>"
            class="back-link"
        >
            ← Back
        </a>

        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?= e($errorMessage) ?>
            </div>
        <?php endif; ?>

        <div class="product-card">
            <div class="top-section">
                <div class="product-image-container">
                    <?php if (!empty($product['image'])): ?>
                        <img
                            src="../uploads/products/<?= e($product['image']) ?>"
                            alt="<?= e($product['name']) ?>"
                            class="product-image"
                        >
                    <?php else: ?>
                        <div class="no-image">
                            No Image Available
                        </div>
                    <?php endif; ?>
                </div>

                <div class="product-info">
                    <?php if (!empty($product['category_name'])): ?>
                        <div class="category-badge">
                            <?= e($product['category_name']) ?>
                        </div>
                    <?php endif; ?>

                    <h1 class="product-name">
                        <?= e($product['name']) ?>
                    </h1>

                    <div class="product-description">
                        <?= nl2br(
                            e(
                                $product['description']
                                ?? 'No description available.'
                            )
                        ) ?>
                    </div>

                    <div class="price">
                        $
                        <?= number_format(
                            (float)$product['price'],
                            2
                        ) ?>
                    </div>

                    <div class="unit">
                        Price per
                        <?= e($product['unit']) ?>
                    </div>

                    <div class="info-box">
                        <div class="info-row">
                            <span class="info-label">
                                This Week's Stock
                            </span>
                            <span class="info-value">
                                <?php if ($hasWeeklyStock): ?>
                                    <?= number_format(
                                        $weeklyActualQuantity,
                                        2
                                    ) ?>
                                    <?= e($product['unit']) ?>
                                <?php else: ?>
                                    Not Set
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                Availability
                            </span>
                            <span class="info-value">
                                <?php if ($isWeeklyAvailable): ?>
                                    <span class="stock-available">
                                        Available
                                    </span>
                                <?php elseif (
                                    $hasWeeklyStock &&
                                    $weeklyStatus === 'sold_out'
                                ): ?>
                                    <span class="stock-unavailable">
                                        Sold Out This Week
                                    </span>
                                <?php elseif (
                                    $hasWeeklyStock &&
                                    $weeklyStatus === 'unavailable'
                                ): ?>
                                    <span class="stock-unavailable">
                                        Currently Unavailable
                                    </span>
                                <?php else: ?>
                                    <span class="stock-unavailable">
                                        Weekly Stock Not Set
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                Category
                            </span>
                            <span class="info-value">
                                <?= e(
                                    $product['category_name']
                                    ?? 'Not specified'
                                ) ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                Added
                            </span>
                            <span class="info-value">
                                <?= date(
                                    'M d, Y',
                                    strtotime(
                                        $product['created_at']
                                    )
                                ) ?>
                            </span>
                        </div>
                    </div>

                    <div class="actions">
                        <a
                            href="<?= e($backPage) ?>"
                            class="button back-button"
                        >
                            Back
                        </a>

                        <?php if ($isWeeklyAvailable): ?>
                            <a
                                href="add_to_cart.php?id=<?= (int)$product['id'] ?>"
                                class="button cart-button"
                            >
                                Add to Cart
                            </a>
                        <?php else: ?>
                            <button
                                type="button"
                                class="button"
                                style="
                                    background:#e74c3c;
                                    color:white;
                                    cursor:not-allowed;
                                "
                                disabled
                            >
                                <?php if (!$hasWeeklyStock): ?>
                                    Weekly Stock Not Set
                                <?php elseif (
                                    $weeklyStatus === 'sold_out' ||
                                    $weeklyActualQuantity <= 0
                                ): ?>
                                    Sold Out This Week
                                <?php else: ?>
                                    Currently Unavailable
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="farmer-section">
                <h2 class="section-title">
                    Farmer Information
                </h2>

                <div class="farmer-card">
                    <div class="farmer-name">
                        <a
                            href="farmer_details.php?id=<?= (int)$product['farmer_id'] ?>"
                        >
                            <?= e($product['farmer_name']) ?>
                        </a>
                    </div>

                    <div class="farmer-row">
                        <span class="farmer-label">
                            Contact Person:
                        </span>
                        <span class="farmer-value">
                            <?= e(
                                $product['farmer_contact']
                                ?? 'Not available'
                            ) ?>
                        </span>
                    </div>

                    <div class="farmer-row">
                        <span class="farmer-label">
                            Location:
                        </span>
                        <span class="farmer-value">
                            <?= e(
                                $product['farmer_address']
                                ?? 'Not available'
                            ) ?>
                        </span>
                    </div>

                    <?php if (!empty($product['farmer_description'])): ?>
                        <div class="farmer-description">
                            <span class="farmer-label">
                                About the Farmer:
                            </span>
                            <br>
                            <?= nl2br(
                                e(
                                    $product['farmer_description']
                                )
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="reviews-section">
                <div class="reviews-header">
                    <h2>
                        Product Reviews
                    </h2>

                    <div class="rating-summary">
                        <div class="average-rating">
                            <?= number_format(
                                $averageRating,
                                1
                            ) ?>
                            / 5
                        </div>

                        <div class="stars">
                            <?php
                            $roundedRating =
                                (int)round($averageRating);

                            for (
                                $i = 1;
                                $i <= 5;
                                $i++
                            ):
                            ?>
                                <?= $i <= $roundedRating
                                    ? '★'
                                    : '☆'
                                ?>
                            <?php endfor; ?>
                        </div>

                        <div class="review-count">
                            <?= $reviewCount ?>
                            <?= $reviewCount === 1
                                ? 'review'
                                : 'reviews'
                            ?>
                        </div>
                    </div>
                </div>

                <div class="review-action">
                    <a
                        href="reviews.php?product_id=<?= (int)$product['id'] ?>"
                        class="button"
                    >
                        Write a Review
                    </a>
                </div>

                <?php if (!empty($approvedReviews)): ?>
                    <?php foreach ($approvedReviews as $review): ?>
                        <div class="review-card">
                            <div class="review-top">
                                <div class="review-customer">
                                    <?= e(
                                        $review['customer_name']
                                        ?? 'Customer'
                                    ) ?>
                                </div>

                                <div class="review-date">
                                    <?= date(
                                        'M d, Y',
                                        strtotime(
                                            $review['created_at']
                                        )
                                    ) ?>
                                </div>
                            </div>

                            <div class="review-stars">
                                <?php
                                $reviewRating =
                                    (int)$review['rating'];

                                for (
                                    $i = 1;
                                    $i <= 5;
                                    $i++
                                ):
                                ?>
                                    <?= $i <= $reviewRating
                                        ? '★'
                                        : '☆'
                                    ?>
                                <?php endfor; ?>
                            </div>

                            <?php if (!empty($review['comment'])): ?>
                                <div class="review-comment-text">
                                    <?= nl2br(
                                        e(
                                            $review['comment']
                                        )
                                    ) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (
                                !empty(
                                    $review['farmer_response']
                                )
                            ): ?>
                                <div class="farmer-response">
                                    <div class="farmer-response-title">
                                        Farmer Response
                                    </div>

                                    <div class="farmer-response-text">
                                        <?= nl2br(
                                            e(
                                                $review['farmer_response']
                                            )
                                        ) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-review-message">
                        No approved reviews yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="../assets/js/app.js"></script>

</body>
</html>