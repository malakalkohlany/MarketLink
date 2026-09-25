<<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('admin');

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$farmer = null;
$products = [];
$errors = [];

if (!$id) {
    die('Invalid farmer ID.');
}

$stmt = $conn->prepare("
    SELECT
        id,
        stall_name,
        email,
        phone,
        address,
        status,
        created_at,
        updated_at
    FROM farmers
    WHERE id = ?
");

if ($stmt) {

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();

    $stmt->close();
}

if (!$farmer) {
    die('Farmer not found.');
}

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.status,
        p.created_at,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC
");

if ($stmt) {

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
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

    <title>Farmer Details | FreshFind</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<div class="admin-container">

    <aside class="sidebar">

        <div class="logo">
            FreshFind
        </div>

        <nav>

 <a href="dashboard.php">
                Dashboard
            </a>

            <a href="markets.php">
                Markets
            </a>

            <a href="add_market.php">
                Add Market
            </a>

            <a href="categories.php">
                Produce Categories
            </a>

            <a href="farmers.php" class="active">
                Farmers
            </a>

            <a href="products.php">
                Produce
            </a>

            <a href="users.php">
                Users
            </a>

            <a href="orders.php">
                Orders
            </a>

            <a href="reviews.php">
                Reviews
            </a>

            <a href="announcements.php">
                Announcements
            </a>

            <a href="reports.php">
                Reports
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </nav>

    </aside>

    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>
                    Farmer Details
                </h1>

                <p>
                    View farmer information and products.
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


        <section class="form-section">

            <div class="section-header">

                <h2>
                    <?= htmlspecialchars($farmer['stall_name']) ?>
                </h2>

                <a
                    href="farmers.php"
                    class="btn btn-secondary"
                >
                    Back to Farmers
                </a>
                 </div>


            <div class="details-grid">

                <div class="form-group">

                    <label>
                        Farmer ID
                    </label>

                    <p>
                        <?= (int)$farmer['id'] ?>
                    </p>

                </div>


                <div class="form-group">

                    <label>
                        Stall Name
                    </label>

                    <p>
                        <?= htmlspecialchars(
                            $farmer['stall_name']
                        ) ?>
                    </p>

                </div>


                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <p>
                        <?= htmlspecialchars(
                            $farmer['email'] ?? 'Not provided'
                        ) ?>
                    </p>

                </div>


                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <p>
                        <?= htmlspecialchars(
                            $farmer['phone'] ?? 'Not provided'
                        ) ?>
                    </p>

                </div>
                
                <div class="form-group">

                    <label>
                        Address
                    </label>

                    <p>
                        <?= htmlspecialchars(
                            $farmer['address'] ?? 'Not provided'
                        ) ?>
                    </p>

                </div>


                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <p>

                        <span
                            class="status status-<?= htmlspecialchars(
                                $farmer['status'] ?? ''
                            ) ?>"
                        >
                            <?= ucfirst(
                                htmlspecialchars(
                                    $farmer['status'] ?? 'N/A'
                                )
                            ) ?>
                        </span>

                    </p>

                </div>


                <div class="form-group">

                    <label>
                        Joined
                    </label>

                    <p>
                        <?= !empty($farmer['created_at'])
                            ? date(
                                'Y-m-d H:i',
                                strtotime(
                                    $farmer['created_at']
                                )
                            )
                            : 'N/A'
                        ?>
                    </p>

                </div>


                <div class="form-group">

                    <label>
                        Last Updated
                    </label>

                    <p>
                        <?= !empty($farmer['updated_at'])
                            ? date(
                                'Y-m-d H:i',
                                strtotime(
                                    $farmer['updated_at']
                                )
                            )
                            : 'N/A'
                        ?>
                    </p>

                </div>

            </div>

        </section>
          <section class="table-section">

            <div class="section-header">

                <h2>
                    Farmer Products
                </h2>

            </div>


            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                Product ID
                            </th>

                            <th>
                                Product
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($products)): ?>

                            <?php foreach ($products as $product): ?>

                                <tr>

                                    <td>
                                        <?= (int)$product['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $product['name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $product['category_name']
                                            ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float)$product['price'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $product['status'] ?? ''
                                            ) ?>"
                                        >
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $product['status'] ?? 'N/A'
                                                )
                                            ) ?>
                                        </span>

                                    </td>

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

                                <td colspan="6">
                                    No products found for this farmer.
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
