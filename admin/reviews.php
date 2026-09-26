<?php

require_once '../includes/include.php';

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

$reviews = $stmt->fetchAll();

?>

<div class="page-header">

    <h1>
        Reviews
    </h1>

    <p>
        View customer reviews and ratings.
    </p>

</div>


<section class="table-section">

    <div class="section-header">

        <h2>
            Customer Reviews
        </h2>

    </div>


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
                            <?= $review['id'] ?>
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
                            <?= htmlspecialchars(
                                $review['rating']
                            ) ?>/5
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
                                    $review['status'] ?? 'pending'
                                ) ?>
                            </span>

                        </td>

                        <td>
                            <?= date(
                                'Y-m-d H:i',
                                strtotime(
                                    $review['created_at']
                                )
                            ) ?>
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

</section>