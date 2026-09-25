<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$adminNotifications = [];
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

        while ($row = $result->fetch_assoc()) {
            $adminNotifications[] = $row;
        }
    } else {
        $errors[] = 'Failed to load notifications: ' . $stmt->error;
    }

    $stmt->close();
} else {
    $errors[] = 'Failed to prepare notifications query: ' . $conn->error;
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

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

</head>

<body>

<div class="admin-container">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


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
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>


                    <tbody>

                        <?php if (!empty($adminNotifications)): ?>

                            <?php foreach ($adminNotifications as $notification): ?>
                                <tr>
                                    <td>
                                        <?= (int) $notification['id'] ?>
                                    </td>

                                    <td>
                                        <?= (int) $notification['user_id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($notification['title'] ?? 'N/A') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($notification['message'] ?? 'N/A') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($notification['type'] ?? 'N/A') ?>
                                    </td>

                                    <td>
                                        <?php if ((int) $notification['is_read'] === 1): ?>
                                            <span class="status status-active">Read</span>
                                        <?php else: ?>
                                            <span class="status status-pending">Unread</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($notification['created_at'] ?? 'N/A') ?>
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