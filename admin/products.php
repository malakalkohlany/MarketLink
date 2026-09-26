<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
        p.created_at,
        f.stall_name AS farmer_name,
        c.name AS category_name
    FROM products p
    LEFT JOIN farmers f
        ON p.farmer_id = f.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    ORDER BY p.created_at DESC
");

if ($stmt) {

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
        href="../assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/sidebar.css"
    >

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <div class="admin-container">

        <main class="main-content">


            <!-- Page Header -->

            <div class="page-header">

                <div>

                    <h1>Products</h1>

                    <p>
                        View all products listed by farmers.
                    </p>

                </div>

            </div>


            <!-- Errors -->

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">

                    <?php foreach ($errors as $error): ?>

                        <p>
                            <?= e($error) ?>
                        </p>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <!-- Products Table -->

            <section class="table-section">

                <div class="section-header">

                    <h2>All Products</h2>

                </div>


                <div class="table-responsive">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Product</th>

                                <th>Farmer</th>

                                <th>Category</th>

                                <th>Price</th>

                                <th>Unit</th>

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
                                        ((int) $product['is_available'] === 1)
                                            ? 'Available'
                                            : 'Unavailable';


                                    ?>

                                    <tr>

                                        <!-- ID -->

                                        <td>

                                            <?= (int) $product['id'] ?>

                                        </td>


                                        <!-- Product -->

                                        <td>

                                            <strong>
                                                <?= e(
                                                    $product['name'] ?? 'N/A'
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- Farmer -->

                                        <td>

                                            <?= e(
                                                $product['farmer_name'] ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- Category -->

                                        <td>

                                            <?= e(
                                                $product['category_name'] ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- Price -->

                                        <td>

                                            $<?= number_format(
                                                (float) $product['price'],
                                                2
                                            ) ?>

                                        </td>


                                        <!-- Unit -->

                                        <td>

                                            <?= e(
                                                $product['unit'] ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- Stock -->

                                        <td>

                                            <?= number_format(
                                                (float) $product['stock_quantity'],
                                                2
                                            ) ?>

                                        </td>


                                        <!-- Status -->

                                        <td>

                                            <?php if ($availability === 'Available'): ?>

                                                <span class="status status-active">

                                                    Available

                                                </span>

                                            <?php else: ?>

                                                <span class="status status-inactive">

                                                    Unavailable

                                                </span>

                                            <?php endif; ?>

                                            <br>


                                        </td>


                                        <!-- Added -->

                                        <td>

                                            <?= !empty($product['created_at'])

                                                ? date(

                                                    'Y-m-d',

                                                    strtotime(

                                                        $product['created_at']

                                                    )

                                                )

                                                : 'N/A'

                                            ?>

                                        </td>


                                        <!-- Actions -->

                                        <td>

                                            <?php if ($moderation === 'pending'): ?>

                                                <form method="POST" style="display:inline;">

                                                    <input
                                                        type="hidden"
                                                        name="product_id"
                                                        value="<?= (int) $product['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="action"
                                                        value="approve"
                                                    >
                                                        Approve
                                                    </button>

                                                </form>


                                                <form method="POST" style="display:inline;">

                                                    <input
                                                        type="hidden"
                                                        name="product_id"
                                                        value="<?= (int) $product['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="action"
                                                        value="reject"
                                                    >
                                                        Reject
                                                    </button>

                                                </form>

                                            <?php endif; ?>


                                            <?php if (
                                                $moderation === 'approved'
                                                && (int) $product['is_available'] === 1
                                            ): ?>

                                                <form method="POST" style="display:inline;">

                                                    <input
                                                        type="hidden"
                                                        name="product_id"
                                                        value="<?= (int) $product['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="action"
                                                        value="remove"
                                                    >
                                                        Remove
                                                    </button>

                                                </form>

                                            <?php endif; ?>


                                            <?php if ((int) $product['is_available'] === 0): ?>

                                                <form method="POST" style="display:inline;">

                                                    <input
                                                        type="hidden"
                                                        name="product_id"
                                                        value="<?= (int) $product['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="action"
                                                        value="restore"
                                                    >
                                                        Restore
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="10">

                                        No products found.

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