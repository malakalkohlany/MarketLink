<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$userId = (int) $_SESSION['user_id'];

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
");

if ($stmt) {
    $stmt->bind_param('i', $userId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }

    $stmt->close();
}

$unreadCount = 0;

foreach ($notifications as $notification) {
    if ((int) $notification['is_read'] === 0) {
        $unreadCount++;
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

            <div class="notifications-summary">
                <div class="notifications-summary-icon">
                    ♡
                </div>

                <div>
                    <strong>
                        <?= count($notifications) ?>
                        <?= count($notifications) === 1 ? 'notification' : 'notifications' ?>
                    </strong>

                    <span>
                        <?php if ($unreadCount > 0): ?>
                            <?= $unreadCount ?>
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
                            $isUnread = (int) $notification['is_read'] === 0;
                            $type = trim((string) $notification['type']);
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
                                                strtotime($notification['created_at'])
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

                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        function markPageNotificationRead(notificationId) {
            fetch('/MarketLink/actions/mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body:
                    'notification_id=' +
                    encodeURIComponent(notificationId)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    return;
                }

                const item = document.querySelector(
                    '.notification-page-item[data-id="' +
                    notificationId +
                    '"]'
                );

                if (!item) {
                    return;
                }

                item.classList.remove('unread');

                const label = item.querySelector(
                    '.notification-unread-label'
                );

                if (label) {
                    label.remove();
                }

                const button = item.querySelector(
                    '.notification-read-button'
                );

                if (button) {
                    const status = document.createElement('span');
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

                if (typeof updateNotificationBadge === 'function') {
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
                        (unreadCount === 1
                            ? ' unread'
                            : ' unread');
                } else {
                    summaryText.textContent =
                        "You're all caught up.";
                }
            }
        }
    </script>

</body>
</html>