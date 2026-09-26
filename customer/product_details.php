<?php

require_once __DIR__ . '/../includes/include.php';
requireRole(R_CUSTOMER);

// ===============================
// Get Product ID
// ===============================
$productId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

// ===============================
// Determine Previous Page
// ===============================
$from = $_GET['from'] ?? 'dashboard';

if ($from === 'products') {
    // Came from Products page
    $backPage = 'products.php';
} else {
    // Came from Dashboard
    $backPage = 'dashboard.php';
}

$backText = 'Back';

if ($productId <= 0) {
    header('Location: ' . $backPage);
    exit;
}

// ===============================
// Current Customer
// ===============================
$customerId = (int) getUserId();

// ===============================
// Get Product + Category + Farmer
// ===============================
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
        f.address AS farmer_address
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    LEFT JOIN farmers f
        ON p.farmer_id = f.id
    WHERE p.id = ?
      AND p.is_available = 1
      AND p.moderation_status = 'approved'
      AND f.approval_status = 'approved'
    LIMIT 1
");

$stmt->bind_param("i", $productId);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

// ===============================
// Product Not Found
// ===============================
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

        <title>
            Product Not Found - MarketLink
        </title>

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
                Product Not Found
            </h2>

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

// ===============================
// Error Message
// ===============================
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
}

// ===============================
// Review Message
// ===============================
$reviewMessage = '';
$reviewMessageType = '';

if (isset($_GET['review'])) {

    if ($_GET['review'] === 'success') {
        $reviewMessage =
            'Your review has been submitted successfully and is waiting for approval.';
        $reviewMessageType = 'success';
    }

    if ($_GET['review'] === 'already') {
        $reviewMessage =
            'You have already reviewed this product for this order.';
        $reviewMessageType = 'error';
    }

    if ($_GET['review'] === 'invalid') {
        $reviewMessage =
            'Please select a valid rating and enter your review.';
        $reviewMessageType = 'error';
    }

    if ($_GET['review'] === 'not_allowed') {
        $reviewMessage =
            'You can only review a product that you purchased.';
        $reviewMessageType = 'error';
    }

    if ($_GET['review'] === 'failed') {
        $reviewMessage =
            'Something went wrong while submitting your review.';
        $reviewMessageType = 'error';
    }
}

// ===============================
// Submit Product Review
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['submit_product_review'])) {

    $rating = isset($_POST['rating'])
        ? (int) $_POST['rating']
        : 0;

    $comment = trim($_POST['comment'] ?? '');

    $orderId = isset($_POST['order_id'])
        ? (int) $_POST['order_id']
        : 0;

    // -------------------------------
    // Validate Rating
    // -------------------------------
    if ($rating < 1 || $rating > 5 || $orderId <= 0) {

        header(
            'Location: product_details.php?id='
            . $productId
            . '&from='
            . urlencode($from)
            . '&review=invalid'
        );

        exit;
    }

    // -------------------------------
    // Check Customer Purchased Product
    // -------------------------------
    $purchaseStmt = $conn->prepare("
        SELECT
            o.id,
            p.farmer_id
        FROM orders o
        INNER JOIN order_items oi
            ON oi.order_id = o.id
        INNER JOIN products p
            ON p.id = oi.product_id
        WHERE o.id = ?
          AND o.customer_id = ?
          AND oi.product_id = ?
        LIMIT 1
    ");

    $purchaseStmt->bind_param(
        "iii",
        $orderId,
        $customerId,
        $productId
    );

    $purchaseStmt->execute();

    $purchaseResult = $purchaseStmt->get_result();
    $purchase = $purchaseResult->fetch_assoc();

    $purchaseStmt->close();

    if (!$purchase) {

        header(
            'Location: product_details.php?id='
            . $productId
            . '&from='
            . urlencode($from)
            . '&review=not_allowed'
        );

        exit;
    }

    // -------------------------------
    // Check Existing Review
    // -------------------------------
    $checkReviewStmt = $conn->prepare("
        SELECT id
        FROM reviews
        WHERE customer_id = ?
          AND order_id = ?
          AND product_id = ?
        LIMIT 1
    ");

    $checkReviewStmt->bind_param(
        "iii",
        $customerId,
        $orderId,
        $productId
    );

    $checkReviewStmt->execute();

    $existingReviewResult =
        $checkReviewStmt->get_result();

    $existingReview =
        $existingReviewResult->fetch_assoc();

    $checkReviewStmt->close();

    if ($existingReview) {

        header(
            'Location: product_details.php?id='
            . $productId
            . '&from='
            . urlencode($from)
            . '&review=already'
        );

        exit;
    }

    // -------------------------------
    // Insert Review
    // -------------------------------
    $insertReviewStmt = $conn->prepare("
        INSERT INTO reviews
        (
            customer_id,
            farmer_id,
            market_id,
            product_id,
            order_id,
            rating,
            comment,
            status
        )
        VALUES
        (
            ?,
            ?,
            NULL,
            ?,
            ?,
            ?,
            ?,
            'pending'
        )
    ");

    $farmerId = (int) $product['farmer_id'];

    $insertReviewStmt->bind_param(
        "iiiiss",
        $customerId,
        $farmerId,
        $productId,
        $orderId,
        $rating,
        $comment
    );

    if ($insertReviewStmt->execute()) {

        $insertReviewStmt->close();

        header(
            'Location: product_details.php?id='
            . $productId
            . '&from='
            . urlencode($from)
            . '&review=success'
        );

        exit;

    } else {

        $insertReviewStmt->close();

        header(
            'Location: product_details.php?id='
            . $productId
            . '&from='
            . urlencode($from)
            . '&review=failed'
        );

        exit;
    }
}

// ===============================
// Product Rating Summary
// ===============================
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

    $averageRating =
        (float) $ratingData['average_rating'];

    $reviewCount =
        (int) $ratingData['review_count'];
}

// ===============================
// Approved Product Reviews
// ===============================
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

$reviewsResult =
    $reviewsStmt->get_result();

while ($reviewRow = $reviewsResult->fetch_assoc()) {
    $approvedReviews[] = $reviewRow;
}

$reviewsStmt->close();

// ===============================
// Orders Available for Review
// ===============================
$reviewableOrders = [];

$reviewableStmt = $conn->prepare("
    SELECT DISTINCT
        oi.order_id,
        o.created_at AS order_date
    FROM order_items oi
    INNER JOIN orders o
        ON o.id = oi.order_id
    LEFT JOIN reviews r
        ON r.customer_id = o.customer_id
       AND r.order_id = oi.order_id
       AND r.product_id = oi.product_id
    WHERE o.customer_id = ?
      AND oi.product_id = ?
      AND r.id IS NULL
    ORDER BY o.created_at DESC
");

$reviewableStmt->bind_param(
    "ii",
    $customerId,
    $productId
);

$reviewableStmt->execute();

$reviewableResult =
    $reviewableStmt->get_result();

while ($orderRow = $reviewableResult->fetch_assoc()) {
    $reviewableOrders[] = $orderRow;
}

$reviewableStmt->close();

// ===============================
// Check If Customer Has Reviewed
// ===============================
$hasAnyProductReview = false;

$hasReviewStmt = $conn->prepare("
    SELECT id
    FROM reviews
    WHERE customer_id = ?
      AND product_id = ?
    LIMIT 1
");

$hasReviewStmt->bind_param(
    "ii",
    $customerId,
    $productId
);

$hasReviewStmt->execute();

$hasReviewResult =
    $hasReviewStmt->get_result();

if ($hasReviewResult->fetch_assoc()) {
    $hasAnyProductReview = true;
}

$hasReviewStmt->close();

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
            max-width: 1100px;
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

        .product-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow:
                0 6px 25px
                rgba(0, 0, 0, 0.08);
        }

        .top-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 35px;
        }

        .product-image-container {
            width: 100%;
            height: 420px;
            background: #eeeeee;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image {
            color: #999;
            font-size: 18px;
        }

        .product-info {
            padding: 5px 0;
        }

        .category-badge {
            display: inline-block;
            background: #eaf8ef;
            color: #27ae60;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .product-name {
            font-size: 34px;
            margin: 0 0 15px;
            color: #222;
        }

        .product-description {
            color: #666;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 25px;
        }

        .price {
            font-size: 31px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 5px;
        }

        .unit {
            color: #777;
            margin-bottom: 25px;
        }

        .info-box {
            border-top: 1px solid #eeeeee;
            margin-top: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #eeeeee;
        }

        .info-label {
            font-weight: bold;
            color: #444;
        }

        .info-value {
            color: #666;
            text-align: right;
        }

        .stock-available {
            color: #27ae60;
            font-weight: bold;
        }

        .stock-unavailable {
            color: #e74c3c;
            font-weight: bold;
        }

        .farmer-section {
            background: #f8f9fb;
            border-top: 1px solid #eeeeee;
            padding: 30px 35px;
        }

        .section-title {
            margin: 0 0 20px;
            font-size: 22px;
            color: #222;
        }

        .farmer-card {
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 22px;
        }

        .farmer-name {
            font-size: 22px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 15px;
        }

        .farmer-name a {
            color: #27ae60;
            text-decoration: none;
        }

        .farmer-name a:hover {
            text-decoration: underline;
        }

        .farmer-row {
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .farmer-label {
            font-weight: bold;
            color: #444;
        }

        .farmer-value {
            color: #666;
        }

        .farmer-description {
            color: #666;
            line-height: 1.7;
            margin-top: 15px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .button {
            flex: 1;
            display: inline-block;
            padding: 13px 20px;
            border: none;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
        }

        .back-button {
            background: #7f8c8d;
            color: white;
        }

        .back-button:hover {
            background: #6c7a7b;
        }

        .cart-button {
            background: #27ae60;
            color: white;
        }

        .cart-button:hover {
            background: #219150;
        }

        /* ===============================
           Quantity Box
        =============================== */

        .quantity-box {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fb;
            border: 1px solid #e1e5e8;
            border-radius: 12px;
        }

        .quantity-box.show {
            display: block;
        }

        .quantity-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .quantity-label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #444;
        }

        .quantity-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
        }

        .quantity-input:focus {
            border-color: #27ae60;
        }

        .quantity-help {
            display: block;
            margin-top: 7px;
            color: #777;
            font-size: 13px;
        }

        .selected-total {
            margin-top: 15px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            font-size: 17px;
            font-weight: bold;
            color: #27ae60;
        }

        .confirm-button {
            width: 100%;
            margin-top: 15px;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #27ae60;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .confirm-button:hover {
            background: #219150;
        }

        .cancel-button {
            width: 100%;
            margin-top: 10px;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #95a5a6;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .cancel-button:hover {
            background: #7f8c8d;
        }

        /* ===============================
           Error / Success
        =============================== */

        .error-message {
            margin-bottom: 20px;
            padding: 12px 15px;
            background: #fdecea;
            border: 1px solid #f5c6cb;
            color: #c0392b;
            border-radius: 8px;
            font-size: 14px;
        }

        .success-message {
            margin-bottom: 20px;
            padding: 12px 15px;
            background: #eaf8ef;
            border: 1px solid #b7e4c7;
            color: #218c4f;
            border-radius: 8px;
            font-size: 14px;
        }

        /* ===============================
           Reviews Section
        =============================== */

        .reviews-section {
            background: #f8f9fb;
            border-top: 1px solid #eeeeee;
            padding: 30px 35px 40px;
        }

        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .reviews-header h2 {
            margin: 0;
            font-size: 22px;
            color: #222;
        }

        .rating-summary {
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 15px 20px;
            text-align: center;
            min-width: 150px;
        }

        .average-rating {
            font-size: 28px;
            font-weight: bold;
            color: #27ae60;
        }

        .stars {
            color: #f1c40f;
            font-size: 21px;
            letter-spacing: 2px;
        }

        .review-count {
            color: #777;
            font-size: 13px;
            margin-top: 4px;
        }

        .review-form-card {
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .review-form-card h3 {
            margin: 0 0 18px;
            color: #222;
            font-size: 19px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }

        .order-select,
        .review-comment {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            font-family: Arial, sans-serif;
        }

        .order-select:focus,
        .review-comment:focus {
            border-color: #27ae60;
        }

        .review-comment {
            min-height: 110px;
            resize: vertical;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            font-size: 32px;
            color: #ccc;
            cursor: pointer;
            transition: 0.15s;
        }

        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: #f1c40f;
        }

        .submit-review-button {
            border: none;
            background: #27ae60;
            color: white;
            padding: 13px 25px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .submit-review-button:hover {
            background: #219150;
        }

        .no-review-message {
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 25px;
            color: #777;
            text-align: center;
            margin-bottom: 20px;
        }

        .review-card {
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .review-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .review-customer {
            font-weight: bold;
            color: #333;
        }

        .review-date {
            color: #999;
            font-size: 13px;
        }

        .review-stars {
            color: #f1c40f;
            font-size: 18px;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .review-comment-text {
            color: #666;
            line-height: 1.7;
            white-space: normal;
        }

        .farmer-response {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fb;
            border-left: 3px solid #27ae60;
            border-radius: 5px;
        }

        .farmer-response-title {
            font-weight: bold;
            color: #444;
            margin-bottom: 7px;
        }

        .farmer-response-text {
            color: #666;
            line-height: 1.6;
        }

        .pending-note {
            color: #777;
            font-size: 14px;
            margin-top: 8px;
        }

        @media (max-width: 768px) {

            .top-section {
                grid-template-columns: 1fr;
                gap: 25px;
                padding: 25px;
            }

            .product-image-container {
                height: 300px;
            }

            .product-name {
                font-size: 28px;
            }

            .farmer-section,
            .reviews-section {
                padding: 25px;
            }

            .actions {
                flex-direction: column;
            }

            .reviews-header {
                flex-direction: column;
                align-items: stretch;
            }

            .rating-summary {
                width: 100%;
            }

            .review-top {
                flex-direction: column;
                align-items: flex-start;
            }
        }

    </style>

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="details-container">

            <!-- Dynamic Back Link -->
            <a
              href="<?= $from === 'products' ? 'products.php' : 'dashboard.php' ?>"
              class="back-link"
            >
              ← Back
            </a>

            <!-- Error -->
            <?php if ($errorMessage): ?>

                <div class="error-message">
                    <?= e($errorMessage) ?>
                </div>

            <?php endif; ?>

            <!-- Review Message -->
            <?php if ($reviewMessage): ?>

                <div
                    class="<?= $reviewMessageType === 'success'
                        ? 'success-message'
                        : 'error-message'
                    ?>"
                >
                    <?= e($reviewMessage) ?>
                </div>

            <?php endif; ?>

            <div class="product-card">

                <!-- ===============================
                     PRODUCT INFORMATION
                =============================== -->

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
                                (float) $product['price'],
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
                                    Stock
                                </span>

                                <span class="info-value">

                                    <?= number_format(
                                        (float) $product['stock_quantity'],
                                        2
                                    ) ?>

                                    <?= e($product['unit']) ?>

                                </span>

                            </div>

                            <div class="info-row">

                                <span class="info-label">
                                    Availability
                                </span>

                                <span class="info-value">

                                    <?php if ($product['is_available']): ?>

                                        <span class="stock-available">
                                            Available
                                        </span>

                                    <?php else: ?>

                                        <span class="stock-unavailable">
                                            Not Available
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

                            <!-- Dynamic Back Button -->

                            <a
                               href="<?= $from === 'products' ? 'products.php' : 'dashboard.php' ?>"
                               class="button back-button"
                            >
                               Back
                            </a>

                            <?php if ((float) $product['stock_quantity'] > 0): ?>

                                <a
                                    href="add_to_cart.php?id=<?= (int) $product['id'] ?>"
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
                                    Out of Stock
                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

                <!-- ===============================
                     FARMER INFORMATION
                =============================== -->

                <div class="farmer-section">

                    <h2 class="section-title">
                        Farmer Information
                    </h2>

                    <div class="farmer-card">

                        <div class="farmer-name">

                            <a
                                href="farmer_details.php?id=<?= (int) $product['farmer_id'] ?>"
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

                <!-- ===============================
                     PRODUCT REVIEWS
                =============================== -->

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
                                    (int) round($averageRating);

                                for ($i = 1; $i <= 5; $i++):
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

                    <!-- ===============================
                         WRITE REVIEW
                    =============================== -->

                    <?php if (!empty($reviewableOrders)): ?>

                        <div class="review-form-card">

                            <h3>
                                Write a Review
                            </h3>

                            <form
                                method="POST"
                                action="product_details.php?id=<?= (int) $productId ?>&from=<?= urlencode($from) ?>"
                            >

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="order_id"
                                    >
                                        Select Order
                                    </label>

                                    <select
                                        name="order_id"
                                        id="order_id"
                                        class="order-select"
                                        required
                                    >

                                        <option value="">
                                            Select the order you purchased this product from
                                        </option>

                                        <?php foreach ($reviewableOrders as $order): ?>

                                            <option
                                                value="<?= (int) $order['order_id'] ?>"
                                            >
                                                Order #<?= (int) $order['order_id'] ?>
                                                -
                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $order['order_date']
                                                    )
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label class="form-label">
                                        Your Rating
                                    </label>

                                    <div class="rating-input">

                                        <input
                                            type="radio"
                                            name="rating"
                                            id="star5"
                                            value="5"
                                            required
                                        >

                                        <label
                                            for="star5"
                                            title="5 Stars"
                                        >
                                            ★
                                        </label>

                                        <input
                                            type="radio"
                                            name="rating"
                                            id="star4"
                                            value="4"
                                        >

                                        <label
                                            for="star4"
                                            title="4 Stars"
                                        >
                                            ★
                                        </label>

                                        <input
                                            type="radio"
                                            name="rating"
                                            id="star3"
                                            value="3"
                                        >

                                        <label
                                            for="star3"
                                            title="3 Stars"
                                        >
                                            ★
                                        </label>

                                        <input
                                            type="radio"
                                            name="rating"
                                            id="star2"
                                            value="2"
                                        >

                                        <label
                                            for="star2"
                                            title="2 Stars"
                                        >
                                            ★
                                        </label>

                                        <input
                                            type="radio"
                                            name="rating"
                                            id="star1"
                                            value="1"
                                        >

                                        <label
                                            for="star1"
                                            title="1 Star"
                                        >
                                            ★
                                        </label>

                                    </div>

                                </div>

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="comment"
                                    >
                                        Your Review
                                    </label>

                                    <textarea
                                        name="comment"
                                        id="comment"
                                        class="review-comment"
                                        placeholder="Write your review about this product..."
                                        required
                                    ></textarea>

                                </div>

                                <button
                                    type="submit"
                                    name="submit_product_review"
                                    class="submit-review-button"
                                >
                                    Submit Review
                                </button>

                            </form>

                        </div>

                    <?php elseif ($hasAnyProductReview): ?>

                        <div class="no-review-message">

                            You have already reviewed this product.

                            <div class="pending-note">
                                Your review may be waiting for approval.
                            </div>

                        </div>

                    <?php else: ?>

                        <div class="no-review-message">

                            You can write a review after purchasing this product.

                        </div>

                    <?php endif; ?>

                    <!-- ===============================
                         APPROVED REVIEWS
                    =============================== -->

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
                                        (int) $review['rating'];

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

                                <?php if (!empty($review['farmer_response'])): ?>

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