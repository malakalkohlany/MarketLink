<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

// Get current user
$userId = (int) $_SESSION['user_id'];

// --------------------------------------------------
// Pagination
// --------------------------------------------------

$notifications_per_page = 10;

$notifications_page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($notifications_page < 1) {
    $notifications_page = 1;
}

$notifications_offset =
    ($notifications_page - 1) * $notifications_per_page;

// --------------------------------------------------
// Count total notifications
// --------------------------------------------------

$count_notifications_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_notifications
    FROM notifications
    WHERE user_id = ?
");

$count_notifications_stmt->bind_param("i", $userId);
$count_notifications_stmt->execute();

$count_notifications_result =
    $count_notifications_stmt->get_result();

$total_notifications =
    (int) $count_notifications_result
        ->fetch_assoc()['total_notifications'];

$count_notifications_stmt->close();

$total_notifications_pages =
    (int) ceil(
        $total_notifications / $notifications_per_page
    );

if (
    $total_notifications_pages > 0 &&
    $notifications_page > $total_notifications_pages
) {
    $notifications_page = $total_notifications_pages;

    $notifications_offset =
        ($notifications_page - 1) * $notifications_per_page;
}

// --------------------------------------------------
// Count unread notifications
// --------------------------------------------------

$unreadCount = 0;

$count_unread_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_notifications
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
");

$count_unread_stmt->bind_param("i", $userId);
$count_unread_stmt->execute();

$count_unread_result =
    $count_unread_stmt->get_result();

$unreadCount =
    (int) $count_unread_result
        ->fetch_assoc()['unread_notifications'];

$count_unread_stmt->close();

// --------------------------------------------------
// Get notifications for current page
// --------------------------------------------------

$notifications = [];

$stmt = $conn->prepare("
    SELECT
        id,
        type,
        title,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");

if ($stmt) {

    $stmt->bind_param(
        "iii",
        $userId,
        $notifications_per_page,
        $notifications_offset
    );

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }

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

    <title>Notifications | MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/components.css"
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
        href="../assets/css/notifications.css"
    >

</head>

<body>

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="notifications-page">

            <!-- Page Header -->

            <div class="notifications-page-header">

                <div>

                    <span class="page-eyebrow">
                        YOUR ACTIVITY
                    </span>

                    <h1>Notifications</h1>

                    <p>
                        Stay up to date with your MarketLink activity.
                    </p>

                </div>

                <?php if ($unreadCount > 0): ?>

                    <button
                        type="button"
                        class="mark-all-button"
                        onclick="markAllPageNotificationsRead()"
                    >
                        Mark all as read
                    </button>

                <?php endif; ?>

            </div>


            <!-- Summary -->

            <div class="notifications-summary">

                <div class="notifications-summary-icon">
                    ♡
                </div>

                <div>

                    <strong>

                        <?= $total_notifications ?>

                        <?= $total_notifications === 1
                            ? 'notification'
                            : 'notifications'
                        ?>

                    </strong>

                    <span>

                        <?php if ($unreadCount > 0): ?>

                            <?= $unreadCount ?> unread

                        <?php else: ?>

                            You're all caught up.

                        <?php endif; ?>

                    </span>

                </div>

            </div>


            <!-- Notifications -->

            <section class="notifications-section">

                <?php if (empty($notifications)): ?>

                    <div class="notifications-empty">

                        <div class="notifications-empty-icon">
                            ♡
                        </div>

                        <h2>No notifications yet</h2>

                        <p>
                            When there's something important to share,
                            you'll see it here.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="notifications-page-list">

                        <?php foreach ($notifications as $notification): ?>

                            <?php

                            $isUnread =
                                (int) $notification['is_read'] === 0;

                            $type =
                                trim(
                                    (string) $notification['type']
                                );

                            ?>

                            <article
                                class="notification-page-item <?= $isUnread ? 'unread' : '' ?>"
                                data-id="<?= (int) $notification['id'] ?>"
                            >

                                <div class="notification-page-indicator"></div>


                                <div class="notification-page-icon">

                                    <?php if ($type === 'order'): ?>

                                        🛒

                                    <?php elseif ($type === 'product'): ?>

                                        🌱

                                    <?php elseif ($type === 'market'): ?>

                                        📍

                                    <?php elseif ($type === 'review'): ?>

                                        ★

                                    <?php elseif ($type === 'announcement'): ?>

                                        📢

                                    <?php else: ?>

                                        ♡

                                    <?php endif; ?>

                                </div>


                                <div class="notification-page-content">

                                    <div class="notification-page-top">

                                        <div>

                                            <h3>
                                                <?= htmlspecialchars(
                                                    $notification['title'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </h3>

                                            <?php if ($type !== ''): ?>

                                                <span class="notification-type">

                                                    <?= htmlspecialchars(
                                                        ucfirst($type),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <?php if ($isUnread): ?>

                                            <span class="notification-unread-label">
                                                Unread
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <p>

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $notification['message'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        ) ?>

                                    </p>


                                    <div class="notification-page-bottom">

                                        <span class="notification-page-time">

                                            <?= date(
                                                'M j, Y · g:i A',
                                                strtotime(
                                                    $notification['created_at']
                                                )
                                            ) ?>

                                        </span>


                                        <?php if ($isUnread): ?>

                                            <button
                                                type="button"
                                                class="notification-read-button"
                                                onclick="markPageNotificationRead(
                                                    <?= (int) $notification['id'] ?>
                                                )"
                                            >
                                                Mark as read
                                            </button>

                                        <?php else: ?>

                                            <span class="notification-read-status">
                                                Read
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>


                    <!-- Pagination -->

                    <?php if ($total_notifications_pages > 1): ?>

                        <div class="pagination">

                            <?php if ($notifications_page > 1): ?>

                                <a
                                    href="?page=<?= $notifications_page - 1 ?>"
                                >
                                    Previous
                                </a>

                            <?php endif; ?>


                            <?php for (
                                $i = 1;
                                $i <= $total_notifications_pages;
                                $i++
                            ): ?>

                                <a
                                    href="?page=<?= $i ?>"
                                    <?= $i == $notifications_page
                                        ? 'class="active"'
                                        : ''
                                    ?>
                                >
                                    <?= $i ?>
                                </a>

                            <?php endfor; ?>


                            <?php if (
                                $notifications_page <
                                $total_notifications_pages
                            ): ?>

                                <a
                                    href="?page=<?= $notifications_page + 1 ?>"
                                >
                                    Next
                                </a>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </section>

        </div>

    </main>


    <script>

        function markPageNotificationRead(notificationId) {

            fetch(
                '/MarketLink/actions/mark_notifications_read.php',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded'
                    },

                    body:
                        'notification_id=' +
                        encodeURIComponent(notificationId)
                }
            )

            .then(response => response.json())

            .then(data => {

                if (!data.success) {
                    return;
                }

                const item =
                    document.querySelector(
                        '.notification-page-item[data-id="' +
                        notificationId +
                        '"]'
                    );

                if (!item) {
                    return;
                }


                item.classList.remove('unread');


                const label =
                    item.querySelector(
                        '.notification-unread-label'
                    );

                if (label) {
                    label.remove();
                }


                const button =
                    item.querySelector(
                        '.notification-read-button'
                    );

                if (button) {

                    const status =
                        document.createElement('span');

                    status.className =
                        'notification-read-status';

                    status.textContent = 'Read';

                    button.replaceWith(status);
                }


                updatePageUnreadCount();

            })

            .catch(error => {

                console.error(
                    'Notification error:',
                    error
                );

            });

        }


        function markAllPageNotificationsRead() {

            fetch(
                '/MarketLink/actions/mark_all_notifications_read.php',
                {
                    method: 'POST'
                }
            )

            .then(response => response.json())

            .then(data => {

                if (!data.success) {
                    return;
                }


                document
                    .querySelectorAll(
                        '.notification-page-item.unread'
                    )
                    .forEach(item => {

                        item.classList.remove('unread');


                        const label =
                            item.querySelector(
                                '.notification-unread-label'
                            );

                        if (label) {
                            label.remove();
                        }


                        const button =
                            item.querySelector(
                                '.notification-read-button'
                            );

                        if (button) {

                            const status =
                                document.createElement('span');

                            status.className =
                                'notification-read-status';

                            status.textContent = 'Read';

                            button.replaceWith(status);
                        }

                    });


                const button =
                    document.querySelector(
                        '.mark-all-button'
                    );

                if (button) {
                    button.remove();
                }


                updatePageUnreadCount();

            })

            .catch(error => {

                console.error(
                    'Notification error:',
                    error
                );

            });

        }


        function updatePageUnreadCount() {

            const unreadCount =
                document.querySelectorAll(
                    '.notification-page-item.unread'
                ).length;


            const summaryText =
                document.querySelector(
                    '.notifications-summary span'
                );


            if (summaryText) {

                summaryText.textContent =
                    unreadCount > 0
                        ? unreadCount + ' unread'
                        : "You're all caught up.";

            }

        }

    </script>

</body>

</html>