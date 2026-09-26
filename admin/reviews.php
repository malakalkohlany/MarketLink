<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$stmt = $conn->prepare("
    SELECT
        r.id,
        r.rating,
        r.comment,
        r.status,
        r.created_at,
        u.name AS customer_name,
        p.name AS product_name
    FROM reviews r
    LEFT JOIN users u
        ON r.customer_id = u.id
    LEFT JOIN products p
        ON r.product_id = p.id
    ORDER BY r.created_at DESC
");

$stmt->execute();

$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>


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

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

</head>

<body>

<div class="admin-container">

     <main class="main-content">

        <div class="page-header">

            <div>

                <h1>
                    Reviews
                </h1>

                <p>
                    View customer reviews and ratings.
                </p>

            </div>

        </div>


        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= htmlspecialchars($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


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
                                Product
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

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($reviews)): ?>

                            <?php foreach ($reviews as $review): ?>

                                <tr>

                                    <td>
                                        <?= (int)$review['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $review['customer_name'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $review['product_name'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= (int)$review['rating'] ?>/5
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $review['comment'] ?? ''
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $review['status'] ?? 'pending'
                                            ) ?>"
                                        >
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $review['status'] ?? 'pending'
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <?php if (!empty($review['created_at'])): ?>

                                            <?= date(
                                                'Y-m-d H:i',
                                                strtotime(
                                                    $review['created_at']
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            N/A

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7">
                                    No reviews found.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>

</html>
