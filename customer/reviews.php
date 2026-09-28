<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

$errors = [];
$successMessage = '';

$reviewsPerPage = 10;

$currentPage = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($currentPage < 1) {
    $currentPage = 1;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: reviews.php');
        exit;
    }

    $reviewType = $_POST['review_type'] ?? '';

    $orderId = isset($_POST['order_id'])
        ? (int) $_POST['order_id']
        : 0;

    $rating = isset($_POST['rating'])
        ? (int) $_POST['rating']
        : 0;

    $comment = trim($_POST['comment'] ?? '');
     if (!in_array($reviewType, ['product', 'farmer'], true)) {
        $errors[] = 'Invalid review type.';
    }

    if ($orderId <= 0) {
        $errors[] = 'Please select an order.';
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Please select a rating between 1 and 5.';
    }

    if (mb_strlen($comment) > 2000) {
        $errors[] =
            'Your comment is too long. Please keep it under 2000 characters.';
    }
      if (empty($errors) && $reviewType === 'product') {

        $productId = isset($_POST['product_id'])
            ? (int) $_POST['product_id']
            : 0;

        if ($productId <= 0) {

            $errors[] = 'Please select a valid product.';

        } else {
            
            $stmt = $conn->prepare("
                SELECT
                    o.id AS order_id,
                    o.farmer_id,
                    oi.product_id,
                    p.name AS product_name
                FROM orders o
                INNER JOIN order_items oi
                    ON oi.order_id = o.id
                INNER JOIN products p
                    ON p.id = oi.product_id
                WHERE o.id = ?
                  AND o.customer_id = ?
                  AND o.status = 'completed'
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

                $errors[] =
                    'This product is not part of the selected completed order.';

            } else { $stmt = $conn->prepare("
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

                    $errors[] =
                        'You have already reviewed this product for this order.';

                } else {

                    $farmerId =
                        (int) $orderData['farmer_id'];
                            $stmt = $conn->prepare("
                        INSERT INTO reviews
                        (
                            customer_id,
                            farmer_id,
                            product_id,
                            order_id,
                            rating,
                            comment,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')
                    ");

                    $stmt->bind_param(
                        "iiiiis",
                        $customerId,
                        $farmerId,
                        $productId,
                        $orderId,
                        $rating,
                        $comment
                    );


                    if ($stmt->execute()) {

                        $successMessage =
                            'Your product review has been submitted and is waiting for approval.';

                    } else {

                        $errors[] =
                            'Unable to submit your review right now.';
                    }

                    $stmt->close();
                }
            }
        }
    }
 if (empty($errors) && $reviewType === 'farmer') {

        $farmerId = isset($_POST['farmer_id'])
            ? (int) $_POST['farmer_id']
            : 0;


        if ($farmerId <= 0) {

            $errors[] = 'Please select a valid farmer.';

        } else {    $stmt = $conn->prepare("
                SELECT
                    o.id AS order_id,
                    o.farmer_id
                FROM orders o
                WHERE o.id = ?
                  AND o.customer_id = ?
                  AND o.farmer_id = ?
                  AND o.status = 'completed'
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

                $errors[] =
                    'This farmer is not associated with the selected completed order.';

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

                    $errors[] =
                        'You have already reviewed this farmer for this order.';

                } else {
  $stmt = $conn->prepare("
                        INSERT INTO reviews
                        (
                            customer_id,
                            farmer_id,
                            product_id,
                            order_id,
                            rating,
                            comment,
                            status
                        )
                        VALUES (?, ?, NULL, ?, ?, ?, 'pending')
                    ");

                    $stmt->bind_param(
                        "iiiis",
                        $customerId,
                        $farmerId,
                        $orderId,
                        $rating,
                        $comment
                    );


                    if ($stmt->execute()) {

                        $successMessage =
                            'Your farmer review has been submitted and is waiting for approval.';

                    } else {

                        $errors[] =
                            'Unable to submit your review right now.';
                    }

                    $stmt->close();
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
    INNER JOIN order_items oi
        ON oi.order_id = o.id
    INNER JOIN products p
        ON p.id = oi.product_id
    INNER JOIN farmers f
        ON f.id = p.farmer_id
    LEFT JOIN reviews r
        ON r.customer_id = o.customer_id
       AND r.order_id = o.id
       AND r.product_id = oi.product_id
    WHERE o.customer_id = ?
      AND o.status = 'completed'
      AND r.id IS NULL
    ORDER BY o.created_at DESC, oi.product_id ASC
");

$stmt->bind_param(
    "i",
    $customerId
);

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
    INNER JOIN farmers f
        ON f.id = o.farmer_id
    LEFT JOIN reviews r
        ON r.customer_id = o.customer_id
       AND r.order_id = o.id
       AND r.farmer_id = o.farmer_id
       AND r.product_id IS NULL
    WHERE o.customer_id = ?
      AND o.status = 'completed'
      AND r.id IS NULL
    ORDER BY o.created_at DESC
");

$stmt->bind_param(
    "i",
    $customerId
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $farmerOrders[] = $row;
}

$stmt->close();

$countStmt = $conn->prepare("
    SELECT COUNT(*) AS total_reviews
    FROM reviews
    WHERE customer_id = ?
");

$countStmt->bind_param(
    "i",
    $customerId
);

$countStmt->execute();

$countResult = $countStmt->get_result();

$countRow = $countResult->fetch_assoc();

$totalReviews =
    (int) ($countRow['total_reviews'] ?? 0);

$countStmt->close();$totalPages =
    $totalReviews > 0
        ? (int) ceil(
            $totalReviews / $reviewsPerPage
        )
        : 1;

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset =
    ($currentPage - 1) *
    $reviewsPerPage;
    $myReviews = [];

$stmt = $conn->prepare("
    SELECT
        r.id,
        r.order_id,
        r.rating,
        r.comment,
        r.status,
        r.farmer_response,
        r.farmer_response_at,
        r.created_at,
        r.updated_at,

        p.name AS product_name,

        f.stall_name AS farmer_name

    FROM reviews r

    LEFT JOIN products p
        ON p.id = r.product_id

    LEFT JOIN farmers f
        ON f.id = r.farmer_id

    WHERE r.customer_id = ?

    ORDER BY r.created_at DESC

    LIMIT ? OFFSET ?
");

$stmt->bind_param(
    "iii",
    $customerId,
    $reviewsPerPage,
    $offset
);

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reviews - MarketLink</title>

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

    <div class="reviews-container">
          <div class="page-header">

            <h1>Reviews</h1>

            <p>
                Share your experience with products and farmers.
            </p>

        </div>
           <?php if (!empty($successMessage)): ?>

            <div class="message success-message">

                <?= e($successMessage) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($errors)): ?>

            <div class="message error-message">

                <?php foreach ($errors as $error): ?>

                    <div>
                        <?= e($error) ?>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <div class="review-grid">
       <div class="review-card">

                <h2>Product Review</h2>

                <div class="review-card-description">

                    Review a product that you purchased
                    and picked up through MarketLink.

                </div>


                <?php if (!empty($productOrders)): ?>

                    <form
                        method="POST"
                        class="review-form"
                        id="productReviewForm"
                    >

                    <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="review_type"
                            value="product"
                        >


                        <label for="productSelect">
                            Product
                        </label>

                        <select
                            name="product_id"
                            id="productSelect"
                            required
                        >

                            <option value="">
                                Select a product
                            </option>

                            <?php foreach ($productOrders as $item): ?>

                                <option
                                    value="<?= (int) $item['product_id'] ?>"
                                    data-order="<?= (int) $item['order_id'] ?>"
                                >

                                    <?= e($item['product_name']) ?>

                                    -
                                    Order #<?= (int) $item['order_id'] ?>

                                </option>

                            <?php endforeach; ?>

                        </select>


                        <label for="productOrderSelect">
                            Order
                        </label>

                        <select
                            name="order_id"
                            id="productOrderSelect"
                            required
                        >

                            <option value="">
                                Select an order
                            </option>

                            <?php foreach ($productOrders as $item): ?>

                                <option
                                    value="<?= (int) $item['order_id'] ?>"
                                    data-product="<?= (int) $item['product_id'] ?>"
                                >

                                    Order #<?= (int) $item['order_id'] ?>

                                    -

                                    <?= date(
                                        'M d, Y',
                                        strtotime($item['order_date'])
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>


                        <label>
                            Rating
                        </label>

                        <div class="rating-input">

                            <input
                                type="radio"
                                id="product-star5"
                                name="rating"
                                value="5"
                                required
                            >

                            <label for="product-star5">
                                ★
                            </label>


                            <input
                                type="radio"
                                id="product-star4"
                                name="rating"
                                value="4"
                            >

                            <label for="product-star4">
                                ★
                            </label>


                            <input
                                type="radio"
                                id="product-star3"
                                name="rating"
                                value="3"
                            >

                            <label for="product-star3">
                                ★
                            </label>


                            <input
                                type="radio"
                                id="product-star2"
                                name="rating"
                                value="2"
                            >

                            <label for="product-star2">
                                ★
                            </label>


                            <input
                                type="radio"
                                id="product-star1"
                                name="rating"
                                value="1"
                            >

                            <label for="product-star1">
                                ★
                            </label>

                        </div>


                        <label for="productComment">
                            Comment
                        </label>

                        <textarea
                            name="comment"
                            id="productComment"
                            maxlength="2000"
                            placeholder="Write your experience..."
                        ></textarea>


                        <button
                            type="submit"
                            class="submit-review"
                        >
                            Submit Product Review
                        </button>

                    </form>

                <?php else: ?>

                    <div class="empty-review-option">

                        You do not have any completed products
                        available for review yet.

                    </div>

                <?php endif; ?>

            </div>
               <div class="review-card">

                <h2>Farmer Review</h2>

                <div class="review-card-description">

                    Review a farmer you purchased from
                    through MarketLink.

                </div>


                <?php if (!empty($farmerOrders)): ?>

                    <form
                        method="POST"
                        class="review-form"
                        id="farmerReviewForm"
                    >

                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="review_type"
                            value="farmer"
                        >


                        <label for="farmerSelect">
                            Farmer
                        </label>

                        <select
                            name="farmer_id"
                            id="farmerSelect"
                            required
                        >

                            <option value="">
                                Select a farmer
                            </option>

                            <?php foreach ($farmerOrders as $item): ?>

                                <option
                                    value="<?= (int) $item['farmer_id'] ?>"
                                    data-order="<?= (int) $item['order_id'] ?>"
                                >

                                    <?= e($item['farmer_name']) ?>

                                    -
                                    Order #<?= (int) $item['order_id'] ?>

                                </option>

                            <?php endforeach; ?>

                        </select>


                        <label for="farmerOrderSelect">
                            Order
                        </label>

                        <select
                            name="order_id"
                            id="farmerOrderSelect"
                            required
                        >

                            <option value="">
                                Select an order
                            </option>

                            <?php foreach ($farmerOrders as $item): ?>

                                <option
                                    value="<?= (int) $item['order_id'] ?>"
                                    data-farmer="<?= (int) $item['farmer_id'] ?>"
                                >

                                    Order #<?= (int) $item['order_id'] ?>

                                    -

                                    <?= date(
                                        'M d, Y',
                                        strtotime($item['order_date'])
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>


                        <label>
                            Rating
                        </label>

                        <div class="rating-input">

                            <input
                                type="radio"
                                id="farmer-star5"
                                name="rating"
                                value="5"
                                required
                            >

                            <label for="farmer-star5">
                                ★
                            </label>


                            <input
                                type="radio"
                                id="farmer-star4"
                                name="rating"
                                value="4"
                            >

                            <label for="farmer-star4">
                                ★
                            </label>


                            <input
                                type="radio"
                                id="farmer-star3"
                                name="rating"
                                value="3"
                            >

                            <label for="farmer-star3">
                                ★
                            </label>


                            <input
                                type="radio"
                                id="farmer-star2"
                                name="rating"
                                value="2"
                            >

                            <label for="farmer-star2">
                                ★
                            </label>


                            <input
                                type="radio"
                                id="farmer-star1"
                                name="rating"
                                value="1"
                            >

                            <label for="farmer-star1">
                                ★
                            </label>

                        </div>


                        <label for="farmerComment">
                            Comment
                        </label>

                        <textarea
                            name="comment"
                            id="farmerComment"
                            maxlength="2000"
                            placeholder="Write your experience..."
                        ></textarea>


                        <button
                            type="submit"
                            class="submit-review"
                        >
                            Submit Farmer Review
                        </button>

                    </form>

                <?php else: ?>

                    <div class="empty-review-option">

                        You do not have any completed orders
                        with farmers available for review yet.

                    </div>

                <?php endif; ?>

            </div>

        </div>
           <div class="my-reviews-section">

            <div class="my-reviews-header">

                <h2>
                    My Reviews
                </h2>

                <div class="review-count">

                    <?= $totalReviews ?>

                    <?= $totalReviews === 1
                        ? 'review'
                        : 'reviews'
                    ?>

                </div>

            </div>


            <?php if (!empty($myReviews)): ?>

                <?php foreach ($myReviews as $review): ?>

                    <?php
 if (!empty($review['product_name'])) {

                        $reviewTitle =
                            $review['product_name'];

                        $reviewType =
                            'Product Review';

                    } elseif (!empty($review['farmer_name'])) {

                        $reviewTitle =
                            $review['farmer_name'];

                        $reviewType =
                            'Farmer Review';

                    } else {

                        $reviewTitle =
                            'Review';

                        $reviewType =
                            'Review';

                    }$rating =
                        (int) $review['rating'];

                    $stars = '';

                    for ($i = 1; $i <= 5; $i++) {

                        $stars .=
                            $i <= $rating
                                ? '★'
                                : '☆';
                    }
                    $statusClass =
                        'status-pending';

                    if ($review['status'] === 'approved') {

                        $statusClass =
                            'status-approved';

                    } elseif (
                        $review['status'] === 'rejected'
                    ) {

                        $statusClass =
                            'status-rejected';
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

                                <?= nl2br(
                                    e($review['comment'])
                                ) ?>

                            </div>

                        <?php endif; ?>


                        <div class="review-date">

                            Order #<?= (int) $review['order_id'] ?>

                            &nbsp; • &nbsp;

                            <?= date(
                                'M d, Y',
                                strtotime($review['created_at'])
                            ) ?>

                        </div>


                        <div class="status <?= $statusClass ?>">

                            <?= e(
                                ucfirst(
                                    $review['status']
                                )
                            ) ?>

                        </div>


                        <!-- ================================= -->
                        <!-- FARMER RESPONSE -->
                        <!-- ================================= -->

                        <?php if (
                            !empty($review['farmer_response'])
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


                                <?php if (
                                    !empty(
                                        $review['farmer_response_at']
                                    )
                                ): ?>

                                    <div class="farmer-response-date">

                                        <?= date(
                                            'M d, Y · g:i A',
                                            strtotime(
                                                $review[
                                                    'farmer_response_at'
                                                ]
                                            )
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>
                   <?php if ($totalPages > 1): ?>

                    <div class="pagination">

                        <?php if ($currentPage > 1): ?>

                            <a
                                href="?page=<?= $currentPage - 1 ?>"
                            >
                                Previous
                            </a>

                        <?php else: ?>

                            <span class="disabled">
                                Previous
                            </span>

                        <?php endif; ?>


                        <?php for (
                            $page = 1;
                            $page <= $totalPages;
                            $page++
                        ): ?>

                            <?php if ($page === $currentPage): ?>

                                <span class="active">
                                    <?= $page ?>
                                </span>

                            <?php else: ?>

                                <a
                                    href="?page=<?= $page ?>"
                                >
                                    <?= $page ?>
                                </a>

                            <?php endif; ?>

                        <?php endfor; ?>


                        <?php if ($currentPage < $totalPages): ?>

                            <a
                                href="?page=<?= $currentPage + 1 ?>"
                            >
                                Next
                            </a>

                        <?php else: ?>

                            <span class="disabled">
                                Next
                            </span>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


            <?php else: ?>

                <div class="no-reviews">

                    <h3>
                        No reviews yet
                    </h3>

                    <p>
                        Your submitted reviews will appear here.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<script src="../assets/js/app.js"></script>
<script src="../assets/js/customer-reviews.js"></script>

</body>
</html>