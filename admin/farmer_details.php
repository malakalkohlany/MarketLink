<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$farmer = null;
$products = [];

if (!$id) {
    die('Invalid farmer ID.');
}

/*
|--------------------------------------------------------------------------
| Get Farmer
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        f.id,
        f.user_id,
        f.stall_name,
        f.contact_person,
        f.description,
        f.address,
        f.latitude,
        f.longitude,
        u.email,
        f.approval_status,
        f.created_at,
        f.updated_at
    FROM farmers f
    LEFT JOIN users u
        ON f.user_id = u.id
    WHERE f.id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Failed to prepare farmer query: ' . $conn->error);
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die('Failed to load farmer: ' . $stmt->error);
}

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$stmt->close();

if (!$farmer) {
    die('Farmer not found.');
}

/*
|--------------------------------------------------------------------------
| Get Farmer Products
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.moderation_status,
        p.created_at,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC
");

if (!$stmt) {
    die('Failed to prepare products query: ' . $conn->error);
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die('Failed to load farmer products: ' . $stmt->error);
}

$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

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

    <title>
        Farmer Details | MarketLink
    </title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


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


        <!-- Farmer Information -->

        <section class="form-section">

            <div class="section-header">

                <h2>
                    <?= htmlspecialchars($farmer['stall_name']) ?>
                </h2>

                <a
                    href="farmers.php"
                    class="btn btn-secondary"
                >
                    Back
                </a>

            </div>


            <div class="details-grid">


                <!-- Farmer ID -->

                <div class="form-group">

                    <label>
                        Farmer ID
                    </label>

                    <p>
                        <?= (int) $farmer['id'] ?>
                    </p>

                </div>


                <!-- Stall Name -->

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


                <!-- Contact Person -->

                <div class="form-group">

                    <label>
                        Contact Person
                    </label>

                    <p>
                        <?= htmlspecialchars(
                            $farmer['contact_person'] ?? 'Not provided'
                        ) ?>
                    </p>

                </div>


                <!-- Email -->

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


                <!-- Address -->

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


                <!-- Approval Status -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <p>

                        <?php
                        $status = strtolower(
                            $farmer['approval_status'] ?? ''
                        );
                        ?>

                        <span
                            class="status status-<?= htmlspecialchars($status) ?>"
                        >
                            <?= ucfirst(
                                htmlspecialchars(
                                    $status ?: 'N/A'
                                )
                            ) ?>
                        </span>

                    </p>

                </div>


                <!-- Joined -->

                <div class="form-group">

                    <label>
                        Joined
                    </label>

                    <p>

                        <?= !empty($farmer['created_at'])
                            ? date(
                                'Y-m-d H:i',
                                strtotime($farmer['created_at'])
                            )
                            : 'N/A'
                        ?>

                    </p>

                </div>


                <!-- Last Updated -->

                <div class="form-group">

                    <label>
                        Last Updated
                    </label>

                    <p>

                        <?= !empty($farmer['updated_at'])
                            ? date(
                                'Y-m-d H:i',
                                strtotime($farmer['updated_at'])
                            )
                            : 'N/A'
                        ?>

                    </p>

                </div>


                <!-- Description -->

                <div class="form-group">

                    <label>
                        Description
                    </label>

                    <p>
                        <?= !empty($farmer['description'])
                            ? nl2br(
                                htmlspecialchars(
                                    $farmer['description']
                                )
                            )
                            : 'Not provided'
                        ?>
                    </p>

                </div>


                <!-- Location -->

                <div class="form-group">

                    <label>
                        Location
                    </label>

                    <p>

                        <?php if (
                            $farmer['latitude'] !== null &&
                            $farmer['longitude'] !== null
                        ): ?>

                            <?= htmlspecialchars(
                                $farmer['latitude']
                            ) ?>,
                            <?= htmlspecialchars(
                                $farmer['longitude']
                            ) ?>

                        <?php else: ?>

                            Not provided

                        <?php endif; ?>

                    </p>

                </div>


            </div>

        </section>


        <!-- Farmer Products -->

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
                                Date
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($products)): ?>

                            <?php foreach (
                                $products as $product
                            ): ?>

                                <?php
                                $productStatus = strtolower(
                                    $product['status'] ?? ''
                                );
                                ?>

                                <tr>

                                    <td>
                                        #<?= (int) $product['id'] ?>
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
                                            (float) $product['price'],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $productStatus
                                            ) ?>"
                                        >

                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $productStatus ?: 'N/A'
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= !empty(
                                            $product['created_at']
                                        )
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

</body>

</html>