<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];

$perPage = 8;

$currentPage = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'default' => 1,
            'min_range' => 1
        ]
    ]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid CSRF token.';

    } else {

        $product_id = (int) ($_POST['product_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($product_id <= 0) {

            $errors[] = 'Invalid product.';

        } else {

            if ($action === 'approve') {

                $stmt = $conn->prepare("
                    UPDATE products
                    SET moderation_status = 'approved'
                    WHERE id = ?
                ");

                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $stmt->close();

            } elseif ($action === 'reject') {

                $stmt = $conn->prepare("
                    UPDATE products
                    SET moderation_status = 'rejected'
                    WHERE id = ?
                ");

                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $stmt->close();

            } elseif ($action === 'remove') {

                $stmt = $conn->prepare("
                    UPDATE products
                    SET is_available = 0
                    WHERE id = ?
                ");

                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $stmt->close();

            } elseif ($action === 'restore') {

                $stmt = $conn->prepare("
                    UPDATE products
                    SET is_available = 1
                    WHERE id = ?
                ");

                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

$totalProducts = 0;

$countStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM products
");

if ($countStmt) {

    if ($countStmt->execute()) {

        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();

        $totalProducts = (int) ($countRow['total'] ?? 0);

    } else {

        $errors[] = 'Failed to count products: ' . $countStmt->error;
    }

    $countStmt->close();

} else {

    $errors[] = 'Failed to prepare product count query: ' . $conn->error;
}

$totalPages = max(1, (int) ceil($totalProducts / $perPage));

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

$products = [];

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.stock_quantity,
        p.image,
        p.is_available,
        p.moderation_status,
        p.created_at,
        f.stall_name AS farmer_name,
        c.name AS category_name
    FROM products p
    LEFT JOIN farmers f
        ON p.farmer_id = f.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $perPage,
        $offset
    );

    if ($stmt->execute()) {

        $result = $stmt->get_result();
        $products = $result->fetch_all(MYSQLI_ASSOC);

    } else {

        $errors[] = 'Failed to load products: ' . $stmt->error;
    }

    $stmt->close();

} else {

    $errors[] = 'Failed to prepare product query: ' . $conn->error;
}

$startItem = $totalProducts > 0
    ? $offset + 1
    : 0;

$endItem = min(
    $offset + $perPage,
    $totalProducts
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products | MarketLink</title>

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

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-products-page">

    <section class="admin-page-hero">

        <div>

            <span class="eyebrow">
                ADMIN / PRODUCTS
            </span>

            <h1>
                Manage local <em>produce.</em>
            </h1>

            <p>
                Review products listed by farmers, moderate
                submissions, and manage their availability.
            </p>

        </div>

        <div class="admin-page-mark">
            <span>05</span>
        </div>

    </section>


    <?php if (!empty($errors)): ?>

        <div class="admin-page-alert alert-danger">

            <span class="admin-alert-mark">
                !
            </span>

            <div>

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= e($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <section class="admin-management-section">

        <div class="admin-section-heading">

            <div>

                <span class="eyebrow">
                    01 / Inventory
                </span>

                <h2>
                    All <em>products.</em>
                </h2>

            </div>

            <span class="admin-record-count">

                <?= $totalProducts ?>

                <?= $totalProducts === 1
                    ? 'product'
                    : 'products'
                ?>

            </span>

        </div>


        <div class="admin-products-table">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Product</th>
                        <th>Farmer</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (!empty($products)): ?>

                    <?php foreach ($products as $product): ?>

                        <?php

                        $availability =
                            (int) $product['is_available'] === 1
                                ? 'available'
                                : 'unavailable';

                        $moderation =
                            $product['moderation_status'] ?? '';

                        ?>

                        <tr>

                            <td class="admin-product-id">

                                #<?= (int) $product['id'] ?>

                            </td>


                            <td>

                                <div class="admin-product-name">

                                    <div class="admin-product-mark">

                                        <?php if (!empty($product['image'])): ?>

                                            <img
                                                src="../<?= e($product['image']) ?>"
                                                alt="<?= e($product['name'] ?? 'Product') ?>"
                                            >

                                        <?php else: ?>

                                            <span>✦</span>

                                        <?php endif; ?>

                                    </div>

                                    <div>

                                        <strong>
                                            <?= e(
                                                $product['name'] ?? 'N/A'
                                            ) ?>
                                        </strong>

                                        <span>
                                            <?= e(
                                                $product['unit'] ?? 'N/A'
                                            ) ?>
                                        </span>

                                    </div>

                                </div>

                            </td>


                            <td class="admin-product-farmer">

                                <?= e(
                                    $product['farmer_name'] ?? 'N/A'
                                ) ?>

                            </td>


                            <td class="admin-product-category">

                                <?= e(
                                    $product['category_name'] ?? 'N/A'
                                ) ?>

                            </td>


                            <td class="admin-product-price">

                                $<?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>

                            </td>


                            <td class="admin-product-stock">

                                <?= number_format(
                                    (float) $product['stock_quantity'],
                                    2
                                ) ?>

                            </td>


                            <td>

                                <div class="admin-product-statuses">

                                    <span
                                        class="admin-status admin-status-<?= $availability ?>"
                                    >
                                        <?= ucfirst($availability) ?>
                                    </span>

                                    <?php if ($moderation): ?>

                                        <span
                                            class="admin-status admin-moderation-<?= e($moderation) ?>"
                                        >
                                            <?= ucfirst(e($moderation)) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <td class="admin-product-date">

                                <?= !empty($product['created_at'])
                                    ? date(
                                        'M j, Y',
                                        strtotime(
                                            $product['created_at']
                                        )
                                    )
                                    : 'N/A'
                                ?>

                            </td>


                            <td>

                                <div class="admin-product-actions">

                                    <?php if ($moderation === 'pending'): ?>

                                        <form method="POST">

                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int) $product['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="action"
                                                value="approve"
                                                class="admin-action-approve"
                                            >
                                                Approve
                                            </button>

                                        </form>

                                        <form method="POST">

                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int) $product['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="action"
                                                value="reject"
                                                class="admin-action-reject"
                                            >
                                                Reject
                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <?php if (
                                        $moderation === 'approved' &&
                                        (int) $product['is_available'] === 1
                                    ): ?>

                                        <form method="POST">

                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int) $product['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="action"
                                                value="remove"
                                                class="admin-action-reject"
                                            >
                                                Remove
                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <?php if (
                                        (int) $product['is_available'] === 0
                                    ): ?>

                                        <form method="POST">

                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int) $product['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="action"
                                                value="restore"
                                                class="admin-action-approve"
                                            >
                                                Restore
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="9"
                            class="admin-table-empty"
                        >

                            <span>✦</span>

                            <strong>
                                No products found.
                            </strong>

                            <p>
                                Products listed by farmers will
                                appear here.
                            </p>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


        <?php if ($totalPages > 1): ?>

            <div class="admin-pagination">

                <div class="admin-pagination-info">

                    Showing
                    <strong><?= $startItem ?></strong>
                    –
                    <strong><?= $endItem ?></strong>
                    of
                    <strong><?= $totalProducts ?></strong>

                </div>


                <div class="admin-pagination-controls">

                    <?php if ($currentPage > 1): ?>

                        <a
                            href="?page=<?= $currentPage - 1 ?>"
                            class="admin-pagination-arrow"
                        >
                            Previous
                        </a>

                    <?php else: ?>

                        <span class="admin-pagination-arrow disabled">
                            Previous
                        </span>

                    <?php endif; ?>


                    <?php

                    $paginationStart = max(
                        1,
                        $currentPage - 2
                    );

                    $paginationEnd = min(
                        $totalPages,
                        $currentPage + 2
                    );

                    ?>

                    <?php if ($paginationStart > 1): ?>

                        <a
                            href="?page=1"
                            class="admin-pagination-number"
                        >
                            1
                        </a>

                        <?php if ($paginationStart > 2): ?>

                            <span class="admin-pagination-dots">
                                …
                            </span>

                        <?php endif; ?>

                    <?php endif; ?>


                    <?php for (
                        $page = $paginationStart;
                        $page <= $paginationEnd;
                        $page++
                    ): ?>

                        <?php if ($page === $currentPage): ?>

                            <span
                                class="admin-pagination-number active"
                            >
                                <?= $page ?>
                            </span>

                        <?php else: ?>

                            <a
                                href="?page=<?= $page ?>"
                                class="admin-pagination-number"
                            >
                                <?= $page ?>
                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>


                    <?php if ($paginationEnd < $totalPages): ?>

                        <?php if ($paginationEnd < $totalPages - 1): ?>

                            <span class="admin-pagination-dots">
                                …
                            </span>

                        <?php endif; ?>

                        <a
                            href="?page=<?= $totalPages ?>"
                            class="admin-pagination-number"
                        >
                            <?= $totalPages ?>
                        </a>

                    <?php endif; ?>


                    <?php if ($currentPage < $totalPages): ?>

                        <a
                            href="?page=<?= $currentPage + 1 ?>"
                            class="admin-pagination-arrow"
                        >
                            Next
                        </a>

                    <?php else: ?>

                        <span class="admin-pagination-arrow disabled">
                            Next
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>

</html>