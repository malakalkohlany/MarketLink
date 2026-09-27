<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect('reviews.php');
    }

    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($reviewId > 0) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("
                UPDATE reviews
                SET status = 'approved'
                WHERE id = ?
                  AND status = 'pending'
            ");

            if ($stmt) {
                $stmt->bind_param("i", $reviewId);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($action === 'remove') {
            $stmt = $conn->prepare("
                UPDATE reviews
                SET status = 'rejected'
                WHERE id = ?
            ");

            if ($stmt) {
                $stmt->bind_param("i", $reviewId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    redirect('reviews.php');
}

$reviews = [];

$stmt = $conn->prepare("
    SELECT
        r.id,
        r.rating,
        r.comment,
        r.status,
        r.created_at,
        u.name AS customer_name,
        p.name AS product_name,
        f.stall_name AS farmer_name
    FROM reviews r
    LEFT JOIN users u
        ON r.customer_id = u.id
    LEFT JOIN products p
        ON r.product_id = p.id
    LEFT JOIN farmers f
        ON r.farmer_id = f.id
    ORDER BY
        CASE
            WHEN r.status = 'pending' THEN 0
            WHEN r.status = 'approved' THEN 1
            WHEN r.status = 'rejected' THEN 2
            ELSE 3
        END,
        r.created_at DESC
");

if ($stmt) {
    if ($stmt->execute()) {
        $reviews = $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);
    }

    $stmt->close();
}

$totalReviews = count($reviews);
$pendingReviews = 0;
$approvedReviews = 0;
$rejectedReviews = 0;

foreach ($reviews as $review) {
    if (($review['status'] ?? '') === 'pending') {
        $pendingReviews++;
    } elseif (($review['status'] ?? '') === 'approved') {
        $approvedReviews++;
    } elseif (($review['status'] ?? '') === 'rejected') {
        $rejectedReviews++;
    }
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

    <title>Reviews | MarketLink</title>

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
        href="../assets/css/admin.css"
    >
    
    <link rel="stylesheet" href="../assets/css/admin_ann.css">
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-reviews-page">

    <section class="admin-page-hero">
        <div class="admin-page-hero-copy">
            <span class="eyebrow">
                ADMIN / REVIEWS
            </span>

            <h1>
                Customer <em>feedback.</em>
            </h1>

            <p>
                Review customer feedback and manage
                submitted reviews across MarketLink.
            </p>
        </div>

        <div class="admin-page-mark">
            09
        </div>
    </section>

    <section class="admin-review-summary-section">

        <div class="admin-section-heading">
            <div>
                <span class="eyebrow">
                    01 / OVERVIEW
                </span>

                <h2>
                    Review <em>activity.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                <?= $totalReviews ?>
                total
            </span>
        </div>

        <div class="admin-review-summary">

            <div class="admin-review-summary-card">
                <span class="admin-review-summary-label">
                    Total reviews
                </span>

                <strong>
                    <?= $totalReviews ?>
                </strong>

                <span class="admin-review-summary-note">
                    All submitted feedback
                </span>
            </div>

            <div class="admin-review-summary-card admin-review-summary-pending">
                <span class="admin-review-summary-label">
                    Pending reviews
                </span>

                <strong>
                    <?= $pendingReviews ?>
                </strong>

                <span class="admin-review-summary-note">
                    Awaiting moderation
                </span>
            </div>

            <div class="admin-review-summary-card admin-review-summary-approved">
                <span class="admin-review-summary-label">
                    Approved
                </span>

                <strong>
                    <?= $approvedReviews ?>
                </strong>

                <span class="admin-review-summary-note">
                    Visible feedback
                </span>
            </div>

            <div class="admin-review-summary-card admin-review-summary-rejected">
                <span class="admin-review-summary-label">
                    Removed
                </span>

                <strong>
                    <?= $rejectedReviews ?>
                </strong>

                <span class="admin-review-summary-note">
                    Rejected feedback
                </span>
            </div>

        </div>

    </section>

    <section class="admin-reviews-section">

        <div class="admin-section-heading">
            <div>
                <span class="eyebrow">
                    02 / MODERATION
                </span>

                <h2>
                    Customer <em>reviews.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                <?= $totalReviews ?>
                <?= $totalReviews === 1 ? 'review' : 'reviews' ?>
            </span>
        </div>

        <div class="admin-reviews-table">

            <table>

                <thead>
                    <tr>
                        <th class="admin-review-id">
                            ID
                        </th>

                        <th class="admin-review-customer">
                            Customer
                        </th>

                        <th class="admin-review-subject">
                            Review
                        </th>

                        <th class="admin-review-rating">
                            Rating
                        </th>

                        <th class="admin-review-status">
                            Status
                        </th>

                        <th class="admin-review-date">
                            Date
                        </th>

                        <th class="admin-review-actions">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($reviews)): ?>

                    <?php foreach ($reviews as $review): ?>

                        <?php
                        $status = $review['status'] ?? 'pending';

                        $isFarmerReview =
                            empty($review['product_name']);

                        $statusLabel = ucfirst($status);

                        if ($status === 'rejected') {
                            $statusLabel = 'Removed';
                        }

                        $statusClass = match ($status) {
                            'approved' => 'admin-status-active',
                            'pending' => 'admin-status-pending',
                            'rejected' => 'admin-status-rejected',
                            default => 'admin-status-default'
                        };
                        ?>

                        <tr>

                            <td class="admin-review-id-cell">
                                #<?= (int) $review['id'] ?>
                            </td>

                            <td>
                                <span class="admin-review-customer-name">
                                    <?= htmlspecialchars(
                                        $review['customer_name'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <div class="admin-review-subject-wrap">

                                    <span class="admin-review-type">
                                        <?= $isFarmerReview
                                            ? 'Farmer Review'
                                            : 'Product Review' ?>
                                    </span>

                                    <span class="admin-review-subject-name">
                                        <?= htmlspecialchars(
                                            $isFarmerReview
                                                ? ($review['farmer_name'] ?? 'N/A')
                                                : ($review['product_name'] ?? 'N/A'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <span class="admin-review-comment">
                                        <?= htmlspecialchars(
                                            $review['comment'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                </div>
                            </td>

                            <td>
                                <div class="admin-review-rating-wrap">

                                    <span class="admin-review-rating-value">
                                        <?= (int) $review['rating'] ?>/5
                                    </span>

                                    <span class="admin-review-stars">
                                        <?php for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ): ?>
                                            <span class="<?= $i <= (int) $review['rating']
                                                ? 'filled'
                                                : '' ?>">
                                                ★
                                            </span>
                                        <?php endfor; ?>
                                    </span>

                                </div>
                            </td>

                            <td>
                                <span class="admin-status <?= $statusClass ?>">
                                    <span class="admin-status-dot"></span>
                                    <?= htmlspecialchars(
                                        $statusLabel,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($review['created_at'])): ?>

                                    <div class="admin-review-date-wrap">
                                        <span class="admin-review-date-value">
                                            <?= date(
                                                'Y-m-d',
                                                strtotime(
                                                    $review['created_at']
                                                )
                                            ) ?>
                                        </span>

                                        <span class="admin-review-time">
                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $review['created_at']
                                                )
                                            ) ?>
                                        </span>
                                    </div>

                                <?php else: ?>

                                    <span class="admin-table-muted">
                                        N/A
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>

                                <?php if ($status === 'pending'): ?>

                                    <div class="admin-review-actions-wrap">

                                        <form method="POST">
                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?= (int) $review['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="approve"
                                            >

                                            <button
                                                type="submit"
                                                class="admin-action-approve"
                                            >
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST">
                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?= (int) $review['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="remove"
                                            >

                                            <button
                                                type="submit"
                                                class="admin-action-reject"
                                            >
                                                Remove
                                            </button>
                                        </form>

                                    </div>

                                <?php elseif ($status === 'approved'): ?>

                                    <form method="POST">
                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="review_id"
                                            value="<?= (int) $review['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="remove"
                                        >

                                        <button
                                            type="submit"
                                            class="admin-action-reject"
                                        >
                                            Remove
                                        </button>
                                    </form>

                                <?php elseif ($status === 'rejected'): ?>

                                    <span class="admin-review-removed">
                                        Removed
                                    </span>

                                <?php else: ?>

                                    <span class="admin-table-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td
                            colspan="7"
                            class="admin-table-empty"
                        >
                            <div class="admin-empty-state">

                                <span class="admin-empty-mark">
                                    ✦
                                </span>

                                <strong>
                                    No reviews found.
                                </strong>

                                <span>
                                    Customer reviews will appear here
                                    once they are submitted.
                                </span>

                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>