<?php

require_once __DIR__ . '/../includes/include.php';
requireRole(R_CUSTOMER);

$customerId = (int) getUserId();
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewType = $_POST['review_type'] ?? '';
    $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');

    if (!in_array($reviewType, ['product', 'farmer', 'market'], true)) {
        $errors[] = 'Invalid review type.';
    }

    if ($orderId <= 0) {
        $errors[] = 'Please select an order.';
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Please select a rating between 1 and 5.';
    }

    if (empty($errors)) {
        if ($reviewType === 'product') {
            $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

            if ($productId <= 0) {
                $errors[] = 'Invalid product.';
            } else {
                $stmt = $conn->prepare("
                    SELECT
                        o.id AS order_id,
                        o.farmer_id,
                        o.market_id,
                        oi.product_id
                    FROM orders o
                    INNER JOIN order_items oi ON oi.order_id = o.id
                    WHERE o.id = ?
                      AND o.customer_id = ?
                      AND oi.product_id = ?
                    LIMIT 1
                ");

                $stmt->bind_param(
                    "iii",
                    $orderId,
                    $customerId,
                    $productId
                );
                $stmt->execute();
                $result = $stmt->get_result();
                $orderData = $result->fetch_assoc();
                $stmt->close();

                if (!$orderData) {
                    $errors[] = 'This product is not part of the selected order.';
                } else {
                    $stmt = $conn->prepare("
                        SELECT id
                        FROM reviews
                        WHERE customer_id = ?
                          AND order_id = ?
                          AND product_id = ?
                        LIMIT 1
                    ");

                    $stmt->bind_param(
                        "iii",
                        $customerId,
                        $orderId,
                        $productId
                    );
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existingReview = $result->fetch_assoc();
                    $stmt->close();

                    if ($existingReview) {
                        $errors[] = 'You have already reviewed this product for this order.';
                    } else {
                        $farmerId = !empty($orderData['farmer_id'])
                            ? (int) $orderData['farmer_id']
                            : null;

                        $marketId = !empty($orderData['market_id'])
                            ? (int) $orderData['market_id']
                            : null;

                        $stmt = $conn->prepare("
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
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                        ");

                        $stmt->bind_param(
                            "iiiiiss",
                            $customerId,
                            $farmerId,
                            $marketId,
                            $productId,
                            $orderId,
                            $rating,
                            $comment
                        );

                        if ($stmt->execute()) {
                            $successMessage = 'Your product review has been submitted and is waiting for approval.';
                        } else {
                            $errors[] = 'Unable to submit your review right now.';
                        }

                        $stmt->close();
                    }
                }
            }
        }

        if ($reviewType === 'farmer') {
            $farmerId = isset($_POST['farmer_id']) ? (int) $_POST['farmer_id'] : 0;

            if ($farmerId <= 0) {
                $errors[] = 'Invalid farmer.';
            } else {
                $stmt = $conn->prepare("
                    SELECT
                        id,
                        farmer_id,
                        market_id
                    FROM orders
                    WHERE id = ?
                      AND customer_id = ?
                      AND farmer_id = ?
                    LIMIT 1
                ");

                $stmt->bind_param(
                    "iii",
                    $orderId,
                    $customerId,
                    $farmerId
                );
                $stmt->execute();
                $result = $stmt->get_result();
                $orderData = $result->fetch_assoc();
                $stmt->close();

                if (!$orderData) {
                    $errors[] = 'This farmer is not associated with the selected order.';
                } else {
                    $stmt = $conn->prepare("
                        SELECT id
                        FROM reviews
                        WHERE customer_id = ?
                          AND order_id = ?
                          AND farmer_id = ?
                          AND product_id IS NULL
                        LIMIT 1
                    ");

                    $stmt->bind_param(
                        "iii",
                        $customerId,
                        $orderId,
                        $farmerId
                    );
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existingReview = $result->fetch_assoc();
                    $stmt->close();

                    if ($existingReview) {
                        $errors[] = 'You have already reviewed this farmer for this order.';
                    } else {
                        $marketId = !empty($orderData['market_id'])
                            ? (int) $orderData['market_id']
                            : null;

                        $productId = null;

                        $stmt = $conn->prepare("
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
                            VALUES (?, ?, ?, NULL, ?, ?, ?, 'pending')
                        ");

                        $stmt->bind_param(
                            "iiiiss",
                            $customerId,
                            $farmerId,
                            $marketId,
                            $orderId,
                            $rating,
                            $comment
                        );

                        if ($stmt->execute()) {
                            $successMessage = 'Your farmer review has been submitted and is waiting for approval.';
                        } else {
                            $errors[] = 'Unable to submit your review right now.';
                        }

                        $stmt->close();
                    }
                }
            }
        }

        if ($reviewType === 'market') {
            $marketId = isset($_POST['market_id']) ? (int) $_POST['market_id'] : 0;

            if ($marketId <= 0) {
                $errors[] = 'Invalid market.';
            } else {
                $stmt = $conn->prepare("
                    SELECT
                        id,
                        farmer_id,
                        market_id
                    FROM orders
                    WHERE id = ?
                      AND customer_id = ?
                      AND market_id = ?
                    LIMIT 1
                ");

                $stmt->bind_param(
                    "iii",
                    $orderId,
                    $customerId,
                    $marketId
                );
                $stmt->execute();
                $result = $stmt->get_result();
                $orderData = $result->fetch_assoc();
                $stmt->close();

                if (!$orderData) {
                    $errors[] = 'This market is not associated with the selected order.';
                } else {
                    $stmt = $conn->prepare("
                        SELECT id
                        FROM reviews
                        WHERE customer_id = ?
                          AND order_id = ?
                          AND market_id = ?
                          AND product_id IS NULL
                          AND farmer_id IS NULL
                        LIMIT 1
                    ");

                    $stmt->bind_param(
                        "iii",
                        $customerId,
                        $orderId,
                        $marketId
                    );
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existingReview = $result->fetch_assoc();
                    $stmt->close();

                    if ($existingReview) {
                        $errors[] = 'You have already reviewed this market for this order.';
                    } else {
                        $farmerId = null;
                        $productId = null;

                        $stmt = $conn->prepare("
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
                            VALUES (?, NULL, ?, NULL, ?, ?, ?, 'pending')
                        ");

                        $stmt->bind_param(
                            "iiiis",
                            $customerId,
                            $marketId,
                            $orderId,
                            $rating,
                            $comment
                        );

                        if ($stmt->execute()) {
                            $successMessage = 'Your market review has been submitted and is waiting for approval.';
                        } else {
                            $errors[] = 'Unable to submit your review right now.';
                        }

                        $stmt->close();
                    }
                }
            }
        }
    }
}

$productOrders = [];

$stmt = $conn->prepare("
    SELECT
        o.id AS order_id,
        o.created_at AS order_date,
        oi.product_id,
        p.name AS product_name,
        p.farmer_id,
        f.stall_name AS farmer_name
    FROM orders o
    INNER JOIN order_items oi ON oi.order_id = o.id
    INNER JOIN products p ON p.id = oi.product_id
    LEFT JOIN farmers f ON f.id = p.farmer_id
    LEFT JOIN reviews r
        ON r.customer_id = o.customer_id
       AND r.order_id = o.id
       AND r.product_id = oi.product_id
    WHERE o.customer_id = ?
      AND r.id IS NULL
    ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $productOrders[] = $row;
}

$stmt->close();

$farmerOrders = [];

$stmt = $conn->prepare("
    SELECT
        o.id AS order_id,
        o.created_at AS order_date,
        o.farmer_id,
        f.stall_name AS farmer_name
    FROM orders o
    INNER JOIN farmers f ON f.id = o.farmer_id
    LEFT JOIN reviews r
        ON r.customer_id = o.customer_id
       AND r.order_id = o.id
       AND r.farmer_id = o.farmer_id
       AND r.product_id IS NULL
    WHERE o.customer_id = ?
      AND o.farmer_id IS NOT NULL
      AND r.id IS NULL
    ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $farmerOrders[] = $row;
}

$stmt->close();

$marketOrders = [];

$stmt = $conn->prepare("
    SELECT
        o.id AS order_id,
        o.created_at AS order_date,
        o.market_id,
        m.name AS market_name
    FROM orders o
    INNER JOIN markets m ON m.id = o.market_id
    LEFT JOIN reviews r
        ON r.customer_id = o.customer_id
       AND r.order_id = o.id
       AND r.market_id = o.market_id
       AND r.product_id IS NULL
       AND r.farmer_id IS NULL
    WHERE o.customer_id = ?
      AND o.market_id IS NOT NULL
      AND r.id IS NULL
    ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $marketOrders[] = $row;
}

$stmt->close();

$myReviews = [];

$stmt = $conn->prepare("
    SELECT
        r.id,
        r.order_id,
        r.rating,
        r.comment,
        r.status,
        r.created_at,
        p.name AS product_name,
        f.stall_name AS farmer_name,
        m.name AS market_name
    FROM reviews r
    LEFT JOIN products p ON p.id = r.product_id
    LEFT JOIN farmers f ON f.id = r.farmer_id
    LEFT JOIN markets m ON m.id = r.market_id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");

$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $myReviews[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - MarketLink</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
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

        .reviews-container {
            width: 92%;
            max-width: 1150px;
            margin: 40px auto 60px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            margin: 0 0 10px;
            font-size: 34px;
            color: #222;
        }

        .page-header p {
            margin: 0;
            color: #777;
            font-size: 16px;
        }

        .message {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .success-message {
            background: #eaf8ef;
            border: 1px solid #bce8cb;
            color: #218838;
        }

        .error-message {
            background: #fdecea;
            border: 1px solid #f5c6cb;
            color: #c0392b;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-bottom: 40px;
        }

        .review-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
        }

        .review-card h2 {
            margin: 0 0 10px;
            font-size: 21px;
            color: #222;
        }

        .review-card-description {
            color: #777;
            line-height: 1.6;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .review-form label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
            color: #444;
            font-size: 14px;
        }

        .review-form select,
        .review-form textarea {
            width: 100%;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            padding: 11px;
            font-size: 14px;
            outline: none;
            margin-bottom: 15px;
            background: white;
        }

        .review-form select:focus,
        .review-form textarea:focus {
            border-color: #27ae60;
        }

        .review-form textarea {
            resize: vertical;
            min-height: 90px;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 4px;
            margin-bottom: 18px;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            font-size: 27px;
            color: #ccc;
            cursor: pointer;
            margin: 0;
            transition: 0.2s;
        }

        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: #f1c40f;
        }

        .submit-review {
            width: 100%;
            border: none;
            border-radius: 8px;
            padding: 12px;
            background: #27ae60;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .submit-review:hover {
            background: #219150;
        }

        .empty-review-option {
            background: #f8f9fb;
            border-radius: 10px;
            padding: 15px;
            color: #777;
            font-size: 14px;
            line-height: 1.5;
        }

        .my-reviews-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
        }

        .my-reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .my-reviews-header h2 {
            margin: 0;
            font-size: 25px;
            color: #222;
        }

        .review-count {
            background: #eaf8ef;
            color: #27ae60;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .my-review-item {
            border: 1px solid #e7e7e7;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .my-review-item:last-child {
            margin-bottom: 0;
        }

        .review-item-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }

        .review-item-title {
            font-size: 17px;
            font-weight: bold;
            color: #222;
        }

        .review-item-type {
            display: inline-block;
            margin-top: 5px;
            padding: 4px 9px;
            border-radius: 12px;
            background: #f1f3f5;
            color: #666;
            font-size: 11px;
        }

        .stars-display {
            color: #f1c40f;
            font-size: 18px;
            white-space: nowrap;
        }

        .review-comment {
            color: #555;
            line-height: 1.7;
            margin: 10px 0;
        }

        .review-date {
            color: #999;
            font-size: 12px;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-pending {
            background: #fff4d6;
            color: #a66a00;
        }

        .status-approved {
            background: #eaf8ef;
            color: #218838;
        }

        .status-rejected {
            background: #fdecea;
            color: #c0392b;
        }

        .no-reviews {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }

        .no-reviews h3 {
            margin: 0 0 10px;
            color: #444;
        }

        @media (max-width: 900px) {
            .review-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .reviews-container {
                width: 94%;
                margin-top: 25px;
            }

            .page-header h1 {
                font-size: 28px;
            }

            .my-reviews-section {
                padding: 20px;
            }

            .review-item-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .my-reviews-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="reviews-container">
        <div class="page-header">
            <h1>Reviews</h1>
            <p>Share your experience with products, farmers, and markets.</p>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="message success-message">
                <?= e($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message error-message">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="review-grid">
            <div class="review-card">
                <h2>Product Review</h2>
                <div class="review-card-description">
                    Review a product that was included in one of your orders.
                </div>

                <?php if (!empty($productOrders)): ?>
                    <form method="POST" class="review-form">
                        <input type="hidden" name="review_type" value="product">

                        <label>Product</label>
                        <select name="product_id" required>
                            <option value="">Select a product</option>
                            <?php foreach ($productOrders as $item): ?>
                                <option
                                    value="<?= (int) $item['product_id'] ?>"
                                    data-order="<?= (int) $item['order_id'] ?>"
                                >
                                    <?= e($item['product_name']) ?> -
                                    Order #<?= (int) $item['order_id'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Order</label>
                        <select name="order_id" id="productOrderSelect" required>
                            <option value="">Select an order</option>
                            <?php foreach ($productOrders as $item): ?>
                                <option
                                    value="<?= (int) $item['order_id'] ?>"
                                    data-product="<?= (int) $item['product_id'] ?>"
                                >
                                    Order #<?= (int) $item['order_id'] ?> -
                                    <?= date('M d, Y', strtotime($item['order_date'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Rating</label>
                        <div class="rating-input">
                            <input
                                type="radio"
                                id="product-star5"
                                name="rating"
                                value="5"
                                required
                            >
                            <label for="product-star5">★</label>

                            <input
                                type="radio"
                                id="product-star4"
                                name="rating"
                                value="4"
                            >
                            <label for="product-star4">★</label>

                            <input
                                type="radio"
                                id="product-star3"
                                name="rating"
                                value="3"
                            >
                            <label for="product-star3">★</label>

                            <input
                                type="radio"
                                id="product-star2"
                                name="rating"
                                value="2"
                            >
                            <label for="product-star2">★</label>

                            <input
                                type="radio"
                                id="product-star1"
                                name="rating"
                                value="1"
                            >
                            <label for="product-star1">★</label>
                        </div>

                        <label>Comment</label>
                        <textarea
                            name="comment"
                            placeholder="Write your experience..."
                        ></textarea>

                        <button type="submit" class="submit-review">
                            Submit Product Review
                        </button>
                    </form>
                <?php else: ?>
                    <div class="empty-review-option">
                        You do not have any products available for review yet.
                    </div>
                <?php endif; ?>
            </div>

            <div class="review-card">
                <h2>Farmer Review</h2>
                <div class="review-card-description">
                    Review a farmer associated with one of your orders.
                </div>

                <?php if (!empty($farmerOrders)): ?>
                    <form method="POST" class="review-form">
                        <input type="hidden" name="review_type" value="farmer">

                        <label>Farmer</label>
                        <select name="farmer_id" required>
                            <option value="">Select a farmer</option>
                            <?php foreach ($farmerOrders as $item): ?>
                                <option value="<?= (int) $item['farmer_id'] ?>">
                                    <?= e($item['farmer_name']) ?> -
                                    Order #<?= (int) $item['order_id'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Order</label>
                        <select name="order_id" required>
                            <option value="">Select an order</option>
                            <?php foreach ($farmerOrders as $item): ?>
                                <option value="<?= (int) $item['order_id'] ?>">
                                    Order #<?= (int) $item['order_id'] ?> -
                                    <?= date('M d, Y', strtotime($item['order_date'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Rating</label>
                        <div class="rating-input">
                            <input
                                type="radio"
                                id="farmer-star5"
                                name="rating"
                                value="5"
                                required
                            >
                            <label for="farmer-star5">★</label>

                            <input
                                type="radio"
                                id="farmer-star4"
                                name="rating"
                                value="4"
                            >
                            <label for="farmer-star4">★</label>

                            <input
                                type="radio"
                                id="farmer-star3"
                                name="rating"
                                value="3"
                            >
                            <label for="farmer-star3">★</label>

                            <input
                                type="radio"
                                id="farmer-star2"
                                name="rating"
                                value="2"
                            >
                            <label for="farmer-star2">★</label>

                            <input
                                type="radio"
                                id="farmer-star1"
                                name="rating"
                                value="1"
                            >
                            <label for="farmer-star1">★</label>
                        </div>

                        <label>Comment</label>
                        <textarea
                            name="comment"
                            placeholder="Write your experience..."
                        ></textarea>

                        <button type="submit" class="submit-review">
                            Submit Farmer Review
                        </button>
                    </form>
                <?php else: ?>
                    <div class="empty-review-option">
                        You do not have any orders with farmers to review yet.
                    </div>
                <?php endif; ?>
            </div>

            <div class="review-card">
                <h2>Market Review</h2>
                <div class="review-card-description">
                    Review a market associated with one of your orders.
                </div>

                <?php if (!empty($marketOrders)): ?>
                    <form method="POST" class="review-form">
                        <input type="hidden" name="review_type" value="market">

                        <label>Market</label>
                        <select name="market_id" required>
                            <option value="">Select a market</option>
                            <?php foreach ($marketOrders as $item): ?>
                                <option value="<?= (int) $item['market_id'] ?>">
                                    <?= e($item['market_name']) ?> -
                                    Order #<?= (int) $item['order_id'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Order</label>
                        <select name="order_id" required>
                            <option value="">Select an order</option>
                            <?php foreach ($marketOrders as $item): ?>
                                <option value="<?= (int) $item['order_id'] ?>">
                                    Order #<?= (int) $item['order_id'] ?> -
                                    <?= date('M d, Y', strtotime($item['order_date'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Rating</label>
                        <div class="rating-input">
                            <input
                                type="radio"
                                id="market-star5"
                                name="rating"
                                value="5"
                                required
                            >
                            <label for="market-star5">★</label>

                            <input
                                type="radio"
                                id="market-star4"
                                name="rating"
                                value="4"
                            >
                            <label for="market-star4">★</label>

                            <input
                                type="radio"
                                id="market-star3"
                                name="rating"
                                value="3"
                            >
                            <label for="market-star3">★</label>

                            <input
                                type="radio"
                                id="market-star2"
                                name="rating"
                                value="2"
                            >
                            <label for="market-star2">★</label>

                            <input
                                type="radio"
                                id="market-star1"
                                name="rating"
                                value="1"
                            >
                            <label for="market-star1">★</label>
                        </div>

                        <label>Comment</label>
                        <textarea
                            name="comment"
                            placeholder="Write your experience..."
                        ></textarea>

                        <button type="submit" class="submit-review">
                            Submit Market Review
                        </button>
                    </form>
                <?php else: ?>
                    <div class="empty-review-option">
                        You do not have any orders with markets to review yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="my-reviews-section">
            <div class="my-reviews-header">
                <h2>My Reviews</h2>
                <div class="review-count">
                    <?= count($myReviews) ?> reviews
                </div>
            </div>

            <?php if (!empty($myReviews)): ?>
                <?php foreach ($myReviews as $review): ?>
                    <?php
                    if (!empty($review['product_name'])) {
                        $reviewTitle = $review['product_name'];
                        $reviewType = 'Product Review';
                    } elseif (!empty($review['farmer_name'])) {
                        $reviewTitle = $review['farmer_name'];
                        $reviewType = 'Farmer Review';
                    } elseif (!empty($review['market_name'])) {
                        $reviewTitle = $review['market_name'];
                        $reviewType = 'Market Review';
                    } else {
                        $reviewTitle = 'Review';
                        $reviewType = 'Review';
                    }

                    $rating = (int) $review['rating'];
                    $stars = '';

                    for ($i = 1; $i <= 5; $i++) {
                        $stars .= $i <= $rating ? '★' : '☆';
                    }
                    ?>

                    <div class="my-review-item">
                        <div class="review-item-top">
                            <div>
                                <div class="review-item-title">
                                    <?= e($reviewTitle) ?>
                                </div>
                                <div class="review-item-type">
                                    <?= e($reviewType) ?>
                                </div>
                            </div>

                            <div class="stars-display">
                                <?= $stars ?>
                            </div>
                        </div>

                        <?php if (!empty($review['comment'])): ?>
                            <div class="review-comment">
                                <?= nl2br(e($review['comment'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="review-date">
                            Order #<?= (int) $review['order_id'] ?>
                            &nbsp; • &nbsp;
                            <?= date('M d, Y', strtotime($review['created_at'])) ?>
                        </div>

                        <?php
                        $statusClass = 'status-pending';

                        if ($review['status'] === 'approved') {
                            $statusClass = 'status-approved';
                        } elseif ($review['status'] === 'rejected') {
                            $statusClass = 'status-rejected';
                        }
                        ?>

                        <div class="status <?= $statusClass ?>">
                            <?= e(ucfirst($review['status'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <h3>No reviews yet</h3>
                    <p>Your submitted reviews will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="../assets/js/app.js"></script>
</body>
</html>