<?php
require_once DIR . '/../includes/include.php';

requireRole(R_CUSTOMER);

$userId = (int) $_SESSION['user_id'];

$notifications = [];
$itemsPerPage = 10;

$currentPage = isset($_GET['page'])
? (int) $_GET['page']
: 1;

if ($currentPage < 1) {
$currentPage = 1;
}
$totalNotifications = 0;

$countStmt = $conn->prepare("
SELECT COUNT(*)
FROM notifications
WHERE user_id = ?
");

if ($countStmt) {

$countStmt->bind_param(
    'i',
    $userId
);

if ($countStmt->execute()) {

    $countStmt->bind_result(
        $totalNotifications
    );

    $countStmt->fetch();
}

$countStmt->close();

}
$totalPages = (int) ceil(
$totalNotifications / $itemsPerPage
);

if ($totalPages < 1) {
$totalPages = 1;
}

if ($currentPage > $totalPages) {
$currentPage = $totalPages;
}

$offset =
($currentPage - 1) * $itemsPerPage;
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
    'iii',
    $userId,
    $itemsPerPage,
    $offset
);

if ($stmt->execute()) {

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

$stmt->close();

}
$unreadCount = 0;

$unreadStmt = $conn->prepare("
SELECT COUNT(*)
FROM notifications
WHERE user_id = ?
AND is_read = 0
");

if ($unreadStmt) {

$unreadStmt->bind_param(
    'i',
    $userId
);

if ($unreadStmt->execute()) {

    $unreadStmt->bind_result(
        $unreadCount
    );

    $unreadStmt->fetch();
}

$unreadStmt->close();

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

<style>

    /* ==================================================
       PAGINATION
       ================================================== */

    .notifications-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .notifications-pagination a,
    .notifications-pagination span {
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        border: 1px solid #dddddd;
        background: #ffffff;
        color: #333333;
        transition:
            background 0.2s ease,
            color 0.2s ease,
            border-color 0.2s ease;
    }

    .notifications-pagination a:hover {
        background: #27ae60;
        border-color: #27ae60;
        color: #ffffff;
    }

    .notifications-pagination .active {
        background: #27ae60;
        border-color: #27ae60;
        color: #ffffff;
    }

    .notifications-pagination .disabled {
        color: #aaaaaa;
        background: #f5f5f5;
        border-color: #e5e5e5;
        cursor: not-allowed;
    }

    .pagination-info {
        text-align: center;
        margin-top: 12px;
        color: #777777;
        font-size: 13px;
    }

</style>
</head>
 <body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">

    <div class="notifications-page">
        <div class="notifications-page-header">

            <div>

                <span class="page-eyebrow">
                    YOUR ACTIVITY
                </span>

                <h1>
                    Notifications
                </h1>

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
            <div class="notifications-summary">

            <div class="notifications-summary-icon">
                ♡
            </div>

            <div>

                <strong>

                    <?= (int) $totalNotifications ?>

                    <?= $totalNotifications === 1
                        ? 'notification'
                        : 'notifications'
                    ?>

                </strong>

                <span>

                    <?php if ($unreadCount > 0): ?>

                        <?= (int) $unreadCount ?>

                        unread

                    <?php else: ?>

                        You're all caught up.

                    <?php endif; ?>

                </span>

            </div>

        </div>
           <section class="notifications-section">

            <?php if (empty($notifications)): ?>

                <div class="notifications-empty">

                    <div class="notifications-empty-icon">
                        ♡
                    </div>

                    <h2>
                        No notifications yet
                    </h2>

                    <p>
                        When there's something important to share,
                        you'll see it here.
                    </p>

                </div>

            <?php else: ?>

                <div class="notifications-page-list">

                    <?php foreach (
                        $notifications
                        as $notification
                    ): ?>

                        <?php

                        $isUnread =
                            (int) $notification['is_read'] === 0;

                        $type =
                            trim(
                                (string) $notification['type']
                            );

                        ?>

                        <article
                            class="
                                notification-page-item
                                <?= $isUnread ? 'unread' : '' ?>
                            "
                            data-id="<?= (int) $notification['id'] ?>"
                        >

                            <div
                                class="notification-page-indicator"
                            ></div>

                            <div
                                class="notification-page-icon"
                            >

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

                            <div
                                class="notification-page-content"
                            >

                                <div
                                    class="notification-page-top"
                                >

                                    <div>

                                        <h3>

                                            <?= htmlspecialchars(
                                                $notification['title'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </h3>

                                        <?php if ($type !== ''): ?>

                                            <span
                                                class="notification-type"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst($type),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <?php if ($isUnread): ?>

                                        <span
                                            class="notification-unread-label"
                                        >
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

                                <div
                                    class="notification-page-bottom"
                                >

                                    <span
                                        class="notification-page-time"
                                    >

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

                                        <span
                                            class="notification-read-status"
                                        >
                                            Read
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>
                   <?php if ($totalPages > 1): ?>

                    <div class="notifications-pagination">

                        <?php if ($currentPage > 1): ?>

                            <a
                                href="?page=<?= $currentPage - 1 ?>"
                            >
                                Previous
                            </a>

                        <?php else: ?>

                            <span class="disabled">
                                Previous
                            </span>

                        <?php endif; ?>

                        <?php for (
                            $page = 1;
                            $page <= $totalPages;
                            $page++
                        ): ?>

                            <?php if ($page === $currentPage): ?>

                                <span class="active">
                                    <?= $page ?>
                                </span>

                            <?php else: ?>

                                <a
                                    href="?page=<?= $page ?>"
                                >
                                    <?= $page ?>
                                </a>

                            <?php endif; ?>

                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>

                            <a
                                href="?page=<?= $currentPage + 1 ?>"
                            >
                                Next
                            </a>

                        <?php else: ?>

                            <span class="disabled">
                                Next
                            </span>

                        <?php endif; ?>

                    </div>

                    <div class="pagination-info">

                        Page
                        <?= $currentPage ?>
                        of
                        <?= $totalPages ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </div>

</main>

<script>
     function markPageNotificationRead(
        notificationId
    ) {

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
                    encodeURIComponent(
                        notificationId
                    )
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

            item.classList.remove(
                'unread'
            );

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
                    document.createElement(
                        'span'
                    );

                status.className =
                    'notification-read-status';

                status.textContent =
                    'Read';

                button.replaceWith(
                    status
                );
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

                    item.classList.remove(
                        'unread'
                    );

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
                            document.createElement(
                                'span'
                            );

                        status.className =
                            'notification-read-status';

                        status.textContent =
                            'Read';

                        button.replaceWith(
                            status
                        );
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

            if (
                typeof updateNotificationBadge ===
                'function'
            ) {

                updateNotificationBadge();

            }

        })

        .catch(error => {

            console.error(
                'Notification error:',
                error
            );

        });

    }
      function updatePageUnreadCount() {

        const unreadItems =
            document.querySelectorAll(
                '.notification-page-item.unread'
            );

        const unreadCount =
            unreadItems.length;

        const summaryText =
            document.querySelector(
                '.notifications-summary span'
            );

        if (summaryText) {

            if (unreadCount > 0) {

                summaryText.textContent =
                    unreadCount +
                    ' unread';

            } else {

                summaryText.textContent =
                    "You're all caught up.";

            }

        }

    }

</script>

</body>
 </html>
