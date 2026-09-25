<?php

require_once __DIR__ . '/../includes/include.php';


$errors = [];
$markets = [];

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        address,
        opening_time,
        closing_time,
        status,
        created_at
    FROM markets
    ORDER BY created_at DESC
");

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();
    $markets = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} else {

    $errors[] = 'Failed to load markets.';
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

    <title>Markets | FreshFind</title>

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

            <a href="markets.php" class="active">
                Markets
            </a>

            <a href="add_market.php">
                Add Market
            </a>

            <a href="categories.php">
                Produce Categories
            </a>

            <a href="farmers.php">
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
                    Markets
                </h1>

                <p>
                    Manage all markets.
                </p>

            </div>

            <div>

                <a
                    href="add_market.php"
                    class="btn btn-primary"
                >
                    Add Market
                </a>

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
                    Markets
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
                                Name
                            </th>

                            <th>
                                Address
                            </th>

                            <th>
                                Opening
                            </th>

                            <th>
                                Closing
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($markets)): ?>

                            <?php foreach ($markets as $market): ?>

                                <tr>

                                    <td>
                                        <?= (int)$market['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $market['name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $market['address'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $market['opening_time'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $market['closing_time'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $market['status'] ?? ''
                                            ) ?>"
                                        >
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $market['status'] ?? 'N/A'
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?= !empty($market['created_at'])
                                            ? date(
                                                'Y-m-d',
                                                strtotime(
                                                    $market['created_at']
                                                )
                                            )
                                            : 'N/A'
                                        ?>
                                    </td>

                                    <td>

                                        <a
                                            href="edit_market.php?id=<?= (int)$market['id'] ?>"
                                            class="btn btn-sm btn-secondary"
                                        >
                                            Edit
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8">
                                    No markets found.
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

    <div class="section-header">

        <h2>Markets</h2>

        <a
            href="add_market.php"
            class="btn btn-primary"
        >
            Add Market
        </a>

    </div>

    <table class="data-table">

        <thead>

            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>Operating Days</th>
                <th>Opening</th>
                <th>Closing</th>
                <th>Coordinates</th>
                <th>Map Provider</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

        </thead>

        <tbody>

            <?php if (empty($markets)): ?>

                <tr>
                    <td colspan="10">
                        No markets found.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($markets as $market): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($market['id']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['address'] ?? 'N/A') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['operating_days'] ?? 'N/A') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['opening_time'] ?? 'N/A') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['closing_time'] ?? 'N/A') ?>
                        </td>

                        <td>

                            <?php if (
                                $market['latitude'] !== null &&
                                $market['longitude'] !== null
                            ): ?>

                                <?= htmlspecialchars($market['latitude']) ?>,
                                <?= htmlspecialchars($market['longitude']) ?>

                            <?php else: ?>

                                N/A

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= htmlspecialchars($market['map_provider'] ?? 'N/A') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['status'] ?? 'N/A') ?>
                        </td>

                        <td>

                            <a
                                href="edit_market.php?id=<?= urlencode($market['id']) ?>"
                                class="btn btn-secondary"
                            >
                                Edit
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</section>

</html>
