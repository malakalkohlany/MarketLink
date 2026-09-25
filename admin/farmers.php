<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('admin');

$errors = [];
$farmers = [];


$stmt = $conn->prepare("
    SELECT
        id,
        stall_name,
        phone,
        email,
        status,
        created_at
    FROM farmers
    ORDER BY created_at DESC
");

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();
    $farmers = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} else {

    $errors[] = 'Failed to load farmers.';
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

    <title>Farmers | FreshFind</title>

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
                    Farmers
                </h1>

                <p>
                    Manage all farmers.
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
                    Farmers
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
                                Stall Name
                            </th>

                            <th>
                                Phone
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Joined
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>
 <tbody>

                        <?php if (!empty($farmers)): ?>

                            <?php foreach ($farmers as $farmer): ?>

                                <tr>

                                    <td>
                                        <?= (int)$farmer['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $farmer['stall_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $farmer['phone'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $farmer['email'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>

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

                                    </td>

                                    <td>
                                        <?= !empty($farmer['created_at'])
                                            ? date(
                                                'Y-m-d',
                                                strtotime(
                                                    $farmer['created_at']
                                                )
                                            )
                                            : 'N/A'
                                        ?>
                                    </td>
                                      <td>

                                        <a
                                            href="farmer_details.php?id=<?= (int)$farmer['id'] ?>"
                                            class="btn btn-sm btn-secondary"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7">
                                    No farmers found.
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