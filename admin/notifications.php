<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('admin');

$notifications = [];
$errors = [];

$stmt = $conn->prepare("
    SELECT
        id,
        user_id,
        title,
        message,
        type,
        is_read,
        created_at
    FROM notifications
    ORDER BY created_at DESC
");
if ($stmt) {

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        $notifications = $result->fetch_all(MYSQLI_ASSOC);

    } else {

        $errors[] = 'Failed to load notifications.';
    }

    $stmt->close();

} else {

    $errors[] = 'Failed to prepare notifications query.';
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

    <title>Notifications | MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<div class="admin-container">

    <aside class="sidebar">

        <div class="logo">
            MarketLink
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

            <a href="notifications.php" class="active">
                Notifications
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
                    Notifications
                </h1>

                <p>
                    View all notifications.
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
                    Notifications
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
                                User ID
                            </th>

                            <th>
                                Title
                            </th>

                            <th>
                                Message
                            </th>

                            <th>
                                Type
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

                        <?php if (!empty($notifications)): ?>

                            <?php foreach ($notifications as $notification): ?>

                                <tr>

                                    <td>
                                        <?= (int)$notification['id'] ?>
                                    </td>

                                    <td>
                                        <?= (int)$notification['user_id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $notification['title'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $notification['message'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $notification['type'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if ((int)$notification['is_read'] === 1): ?>

                                            <span class="status status-active">
                                                Read
                                            </span>

                                        <?php else: ?>

                                            <span class="status status-pending">
                                                Unread
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if (!empty($notification['created_at'])): ?>

                                            <?= date(
                                                'Y-m-d H:i',
                                                strtotime(
                                                    $notification['created_at']
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
                                    No notifications found.
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
