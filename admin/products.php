<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
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

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="9">

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