<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

/*
|--------------------------------------------------------------------------
| Handle Review Actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

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

    header('Location: reviews.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Reviews
|--------------------------------------------------------------------------
|
| Farmer is joined directly through reviews.farmer_id.
| This is important because farmer reviews have product_id = NULL.
|
*/

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

$stmt->execute();

$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>
                    Reviews
                </h1>

                <p>
                    Review customer feedback and manage submitted reviews.
                </p>

            </div>

        </div>


        <section class="table-section">

            <div class="section-header">

                <h2>
                    Customer Reviews
                </h2>

            </div>


            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Product
                            </th>

                            <th>
                                Farmer
                            </th>

                            <th>
                                Rating
                            </th>

                            <th>
                                Comment
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
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
                                ?>

                                <tr>

                                    <!-- ID -->

                                    <td>
                                        <?= (int) $review['id'] ?>
                                    </td>


                                    <!-- Customer -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $review['customer_name'] ?? 'N/A'
                                        ) ?>

                                    </td>


                                    <!-- Type -->

                                    <td>

                                        <?php if ($isFarmerReview): ?>

                                            <span>
                                                Farmer Review
                                            </span>

                                        <?php else: ?>

                                            <span>
                                                Product Review
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Product -->

                                    <td>

                                        <?php if ($isFarmerReview): ?>

                                            —
                                            
                                        <?php else: ?>

                                            <?= htmlspecialchars(
                                                $review['product_name'] ?? 'N/A'
                                            ) ?>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Farmer -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $review['farmer_name'] ?? 'N/A'
                                        ) ?>

                                    </td>


                                    <!-- Rating -->

                                    <td>

                                        <?= (int) $review['rating'] ?>/5

                                    </td>


                                    <!-- Comment -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $review['comment'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars($status) ?>"
                                        >

                                            <?= ucfirst(
                                                htmlspecialchars($status)
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Date -->

                                    <td>

                                        <?php if (!empty($review['created_at'])): ?>

                                            <?= date(
                                                'Y-m-d H:i',
                                                strtotime($review['created_at'])
                                            ) ?>

                                        <?php else: ?>

                                            N/A

                                        <?php endif; ?>

                                    </td>


                                    <!-- Actions -->

                                    <td>

                                        <?php if ($status === 'pending'): ?>

                                            <div
                                                style="
                                                    display: flex;
                                                    gap: 6px;
                                                    flex-wrap: wrap;
                                                "
                                            >

                                                <!-- Approve -->

                                                <form method="POST">

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
                                                        class="btn btn-sm"
                                                    >
                                                        Approve
                                                    </button>

                                                </form>


                                                <!-- Remove -->

                                                <form method="POST">

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
                                                        class="btn btn-sm btn-secondary"
                                                    >
                                                        Remove
                                                    </button>

                                                </form>

                                            </div>


                                        <?php elseif ($status === 'approved'): ?>

                                            <!-- Approved reviews can still be removed -->

                                            <form method="POST">

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
                                                    class="btn btn-sm btn-secondary"
                                                >
                                                    Remove
                                                </button>

                                            </form>


                                        <?php elseif ($status === 'rejected'): ?>

                                            <span>
                                                Removed
                                            </span>


                                        <?php else: ?>

                                            <span>
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <tr>

                                <td colspan="10">

                                    No reviews found.

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