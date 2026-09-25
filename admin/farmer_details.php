<?php

require_once '../includes/include.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$farmer = null;
$products = [];

if ($id) {

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

    $stmt->execute([$id]);

    $farmer = $stmt->fetch();

    if ($farmer) {

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

        $stmt->execute([$id]);

        $products = $stmt->fetchAll();
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

    <title>Farmer Details | FreshFind</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>
     <aside class="sidebar">

        <div class="logo">
            FreshFind
        </div>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="add_market.php">
                Add Market
            </a>

            <a href="markets.php">
                Markets
            </a>

            <a href="categories.php">
                Categories
            </a>

            <a href="farmers.php" class="active">
                Farmers
            </a>

            <a href="products.php">
                Products
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

            <h1>
                Farmer Details
            </h1>

            <p>
                View farmer information and products.
            </p>

        </div>

  <?php if (!$farmer): ?>

            <section class="table-section">

                <h2>
                    Farmer not found
                </h2>

                <a
                    href="farmers.php"
                    class="btn btn-secondary"
                >
                    Back to Farmers
                </a>

            </section>


        <?php else: ?>


            <section class="form-section">

                <div class="section-header">

                    <h2>
                        <?= htmlspecialchars(
                            $farmer['stall_name']
                        ) ?>
                    </h2>

                    <a
                        href="farmers.php"
                        class="btn btn-secondary"
                    >
                        Back
                    </a>

                </div>


                <div class="details-grid">


                    <div class="form-group">

                        <label>
                            Farmer ID
                        </label>

                        <p>
                            <?= $farmer['id'] ?>
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
                                    $farmer['status']
                                ) ?>"
                            >

                                <?= ucfirst(
                                    $farmer['status']
                                ) ?>

                            </span>

                        </p>

                    </div>


                    <div class="form-group">

                        <label>
                            Joined
                        </label>

                        <p>
                            <?= date(
                                'Y-m-d H:i',
                                strtotime(
                                    $farmer['created_at']
                                )
                            ) ?>
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
                                Date
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if (!empty($products)): ?>


                            <?php foreach ($products as $product): ?>


                                <tr>


                                    <td>
                                        #<?= $product['id'] ?>
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
                                            $product['price'],
                                            2
                                        ) ?>
                                    </td>


                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $product['status']
                                            ) ?>"
                                        >

                                            <?= ucfirst(
                                                $product['status']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>
                                        <?= date(
                                            'Y-m-d',
                                            strtotime(
                                                $product['created_at']
                                            )
                                        ) ?>
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

            </section>


        <?php endif; ?>


    </main>

</body>

</html>