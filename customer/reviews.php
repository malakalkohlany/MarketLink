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

    <link
        rel="stylesheet"
        href="../assets/css/customer.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/customer_n.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content customer-reviews-page">

    <span class="eyebrow">
        CUSTOMER / REVIEWS
    </span>

    <?php if (!empty($successMessage)): ?>
        <div class="customer-reviews-message customer-reviews-success">
            <span class="customer-reviews-message-icon">
                <i class="fa-solid fa-circle-check"></i>
            </span>

            <span>
                <?= e($successMessage) ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="customer-reviews-message customer-reviews-error">
            <span class="customer-reviews-message-icon">
                <i class="fa-solid fa-circle-exclamation"></i>
            </span>

            <div>
                <?php foreach ($errors as $error): ?>
                    <div>
                        <?= e($error) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <section class="customer-reviews-write-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    01 / WRITE A REVIEW
                </span>

                <h2>
                    Share your <em>experience.</em>
                </h2>
            </div>

            <span class="customer-record-count">
                <?= count($productOrders) + count($farmerOrders) ?>
                option<?= count($productOrders) + count($farmerOrders) !== 1 ? 's' : '' ?>
            </span>
        </div>

        <div class="customer-reviews-grid">

            <article class="customer-review-form-card">

                <div class="customer-review-form-card-header">
                    <span class="customer-review-card-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </span>

                    <div>
                        <span class="customer-review-card-label">
                            PRODUCT
                        </span>

                        <h3>
                            Product <em>review.</em>
                        </h3>

                        <p>
                            Review a product that you purchased
                            and picked up through MarketLink.
                        </p>
                    </div>
                </div>

                <?php if (!empty($productOrders)): ?>

                    <form
                        method="POST"
                        class="customer-review-form"
                        id="productReviewForm"
                    >

                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="review_type"
                            value="product"
                        >

                        <div class="customer-review-field">

                            <label
                                for="productSelect"
                                class="customer-review-label"
                            >
                                Product
                            </label>

                            <select
                                name="product_id"
                                id="productSelect"
                                class="customer-review-select"
                                required
                            >
                                <option value="">
                                    Select a product
                                </option>

                                <?php foreach ($productOrders as $item): ?>

                                    <option
                                        value="<?= (int)$item['product_id'] ?>"
                                        data-order="<?= (int)$item['order_id'] ?>"
                                    >
                                        <?= e($item['product_name']) ?>
                                        -
                                        Order #<?= (int)$item['order_id'] ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="customer-review-field">

                            <label
                                for="productOrderSelect"
                                class="customer-review-label"
                            >
                                Order
                            </label>

                            <select
                                name="order_id"
                                id="productOrderSelect"
                                class="customer-review-select"
                                required
                            >
                                <option value="">
                                    Select an order
                                </option>

                                <?php foreach ($productOrders as $item): ?>

                                    <option
                                        value="<?= (int)$item['order_id'] ?>"
                                        data-product="<?= (int)$item['product_id'] ?>"
                                    >
                                        Order #<?= (int)$item['order_id'] ?>
                                        -
                                        <?= date(
                                            'M d, Y',
                                            strtotime($item['order_date'])
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="customer-review-field">

                            <span class="customer-review-label">
                                Rating
                            </span>

                            <div class="customer-review-rating-input">

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

                        </div>

                        <div class="customer-review-field">

                            <label
                                for="productComment"
                                class="customer-review-label"
                            >
                                Comment
                            </label>

                            <textarea
                                name="comment"
                                id="productComment"
                                class="customer-review-textarea"
                                maxlength="2000"
                                placeholder="Write your experience..."
                            ></textarea>

                            <span class="customer-review-help">
                                Optional. Maximum 2000 characters.
                            </span>

                        </div>

                        <button
                            type="submit"
                            class="customer-review-submit"
                        >
                            Submit Product Review
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                    </form>

                <?php else: ?>

                    <div class="customer-review-empty-option">
                        <span>
                            <i class="fa-solid fa-circle-info"></i>
                        </span>

                        <p>
                            You do not have any completed products
                            available for review yet.
                        </p>
                    </div>

                <?php endif; ?>

            </article>

            <article class="customer-review-form-card">

                <div class="customer-review-form-card-header">
                    <span class="customer-review-card-icon customer-review-card-icon-farmer">
                        <i class="fa-solid fa-user"></i>
                    </span>

                    <div>
                        <span class="customer-review-card-label">
                            FARMER
                        </span>

                        <h3>
                            Farmer <em>review.</em>
                        </h3>

                        <p>
                            Review a farmer you purchased from
                            through MarketLink.
                        </p>
                    </div>
                </div>

                <?php if (!empty($farmerOrders)): ?>

                    <form
                        method="POST"
                        class="customer-review-form"
                        id="farmerReviewForm"
                    >

                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="review_type"
                            value="farmer"
                        >

                        <div class="customer-review-field">

                            <label
                                for="farmerSelect"
                                class="customer-review-label"
                            >
                                Farmer
                            </label>

                            <select
                                name="farmer_id"
                                id="farmerSelect"
                                class="customer-review-select"
                                required
                            >
                                <option value="">
                                    Select a farmer
                                </option>

                                <?php foreach ($farmerOrders as $item): ?>

                                    <option
                                        value="<?= (int)$item['farmer_id'] ?>"
                                        data-order="<?= (int)$item['order_id'] ?>"
                                    >
                                        <?= e($item['farmer_name']) ?>
                                        -
                                        Order #<?= (int)$item['order_id'] ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="customer-review-field">

                            <label
                                for="farmerOrderSelect"
                                class="customer-review-label"
                            >
                                Order
                            </label>

                            <select
                                name="order_id"
                                id="farmerOrderSelect"
                                class="customer-review-select"
                                required
                            >
                                <option value="">
                                    Select an order
                                </option>

                                <?php foreach ($farmerOrders as $item): ?>

                                    <option
                                        value="<?= (int)$item['order_id'] ?>"
                                        data-farmer="<?= (int)$item['farmer_id'] ?>"
                                    >
                                        Order #<?= (int)$item['order_id'] ?>
                                        -
                                        <?= date(
                                            'M d, Y',
                                            strtotime($item['order_date'])
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="customer-review-field">

                            <span class="customer-review-label">
                                Rating
                            </span>

                            <div class="customer-review-rating-input">

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

                        </div>

                        <div class="customer-review-field">

                            <label
                                for="farmerComment"
                                class="customer-review-label"
                            >
                                Comment
                            </label>

                            <textarea
                                name="comment"
                                id="farmerComment"
                                class="customer-review-textarea"
                                maxlength="2000"
                                placeholder="Write your experience..."
                            ></textarea>

                            <span class="customer-review-help">
                                Optional. Maximum 2000 characters.
                            </span>

                        </div>

                        <button
                            type="submit"
                            class="customer-review-submit"
                        >
                            Submit Farmer Review
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                    </form>

                <?php else: ?>

                    <div class="customer-review-empty-option">
                        <span>
                            <i class="fa-solid fa-circle-info"></i>
                        </span>

                        <p>
                            You do not have any completed orders
                            with farmers available for review yet.
                        </p>
                    </div>

                <?php endif; ?>

            </article>

        </div>

    </section>

    <section class="customer-my-reviews-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    02 / YOUR REVIEWS
                </span>

                <h2>
                    My <em>reviews.</em>
                </h2>
            </div>

            <span class="customer-record-count">
                <?= $totalReviews ?>
                review<?= $totalReviews === 1 ? '' : 's' ?>
            </span>
        </div>

        <?php if (!empty($myReviews)): ?>

            <div class="customer-my-reviews-list">

                <?php foreach ($myReviews as $review): ?>

                    <?php
                    if (!empty($review['product_name'])) {
                        $reviewTitle = $review['product_name'];
                        $reviewType = 'Product Review';
                    } elseif (!empty($review['farmer_name'])) {
                        $reviewTitle = $review['farmer_name'];
                        $reviewType = 'Farmer Review';
                    } else {
                        $reviewTitle = 'Review';
                        $reviewType = 'Review';
                    }

                    $rating = (int)$review['rating'];

                    $statusClass = 'status-pending';

                    if ($review['status'] === 'approved') {
                        $statusClass = 'status-approved';
                    } elseif ($review['status'] === 'rejected') {
                        $statusClass = 'status-rejected';
                    }
                    ?>

                    <article class="customer-my-review-card">

                        <div class="customer-my-review-top">

                            <div class="customer-my-review-heading">

                                <span class="customer-my-review-type">
                                    <?= e($reviewType) ?>
                                </span>

                                <h3>
                                    <?= e($reviewTitle) ?>
                                </h3>

                            </div>

                            <div class="customer-my-review-rating">

                                <?php for ($i = 1; $i <= 5; $i++): ?>

                                    <span class="<?= $i <= $rating ? 'is-filled' : '' ?>">
                                        <?= $i <= $rating ? '★' : '☆' ?>
                                    </span>

                                <?php endfor; ?>

                            </div>

                        </div>

                        <?php if (!empty($review['comment'])): ?>

                            <div class="customer-my-review-comment">
                                <?= nl2br(e($review['comment'])) ?>
                            </div>

                        <?php endif; ?>

                        <div class="customer-my-review-meta">

                            <span>
                                Order #<?= (int)$review['order_id'] ?>
                            </span>

                            <span>
                                <?= date(
                                    'M d, Y',
                                    strtotime($review['created_at'])
                                ) ?>
                            </span>

                            <span class="customer-my-review-status <?= htmlspecialchars($statusClass) ?>">
                                <?= e(ucfirst($review['status'])) ?>
                            </span>

                        </div>

                        <?php if (!empty($review['farmer_response'])): ?>

                            <div class="customer-farmer-response">

                                <div class="customer-farmer-response-header">
                                    <span>
                                        <i class="fa-solid fa-reply"></i>
                                    </span>

                                    <strong>
                                        Farmer response
                                    </strong>
                                </div>

                                <div class="customer-farmer-response-text">
                                    <?= nl2br(
                                        e($review['farmer_response'])
                                    ) ?>
                                </div>

                                <?php if (!empty($review['farmer_response_at'])): ?>

                                    <span class="customer-farmer-response-date">
                                        <?= date(
                                            'M d, Y · g:i A',
                                            strtotime(
                                                $review['farmer_response_at']
                                            )
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

            <?php if ($totalPages > 1): ?>

                <div class="customer-reviews-pagination">

                    <?php if ($currentPage > 1): ?>

                        <a
                            href="?page=<?= $currentPage - 1 ?>"
                            class="customer-reviews-pagination-button"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            Previous
                        </a>

                    <?php else: ?>

                        <span class="customer-reviews-pagination-button is-disabled">
                            <i class="fa-solid fa-arrow-left"></i>
                            Previous
                        </span>

                    <?php endif; ?>

                    <div class="customer-reviews-pagination-pages">

                        <?php for (
                            $page = 1;
                            $page <= $totalPages;
                            $page++
                        ): ?>

                            <?php if ($page === $currentPage): ?>

                                <span class="customer-reviews-pagination-page is-active">
                                    <?= $page ?>
                                </span>

                            <?php else: ?>

                                <a
                                    href="?page=<?= $page ?>"
                                    class="customer-reviews-pagination-page"
                                >
                                    <?= $page ?>
                                </a>

                            <?php endif; ?>

                        <?php endfor; ?>

                    </div>

                    <?php if ($currentPage < $totalPages): ?>

                        <a
                            href="?page=<?= $currentPage + 1 ?>"
                            class="customer-reviews-pagination-button"
                        >
                            Next
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    <?php else: ?>

                        <span class="customer-reviews-pagination-button is-disabled">
                            Next
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="customer-reviews-empty">

                <span class="customer-reviews-empty-mark">
                    <i class="fa-regular fa-star"></i>
                </span>

                <strong>
                    No reviews yet.
                </strong>

                <span>
                    Your submitted reviews will appear here.
                </span>

            </div>

        <?php endif; ?>

    </section>

</main>

<script src="../assets/js/app.js"></script>
<script src="../assets/js/customer-reviews.js"></script>

</body>
</html>