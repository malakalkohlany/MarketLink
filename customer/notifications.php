<?php

require_once __DIR__ . '/../includes/include.php';

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
</head>

<body>

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content notifications-page">

        <section class="notifications-hero">

            <div class="notifications-hero-copy">

                <span class="eyebrow">
                    YOUR ACTIVITY / NOTIFICATIONS
                </span>

                <h1>
                    Stay in the <em>loop.</em>
                </h1>

                <p>
                    Keep up with orders, products, markets, announcements, and everything happening across your MarketLink activity.
                </p>

            </div>

            <div class="notifications-hero-mark">
                10
            </div>

        </section>

        <section class="notifications-overview">

            <div class="notifications-summary">

                <div class="notifications-summary-icon">
                    <i data-lucide="bell"></i>
                </div>

                <div class="notifications-summary-content">

                    <strong>
                        <?= (int) $totalNotifications ?>
                        <?= $totalNotifications === 1
                            ? 'notification'
                            : 'notifications'
                        ?>
                    </strong>

                    <span>
                        <?php if ($unreadCount > 0): ?>
                            <?= (int) $unreadCount ?> unread
                        <?php else: ?>
                            You're all caught up.
                        <?php endif; ?>
                    </span>

                </div>

                <?php if ($unreadCount > 0): ?>

                    <button
                        type="button"
                        class="notifications-mark-all"
                        onclick="markAllPageNotificationsRead()"
                    >
                        <i data-lucide="check-check"></i>
                        Mark all as read
                    </button>

                <?php endif; ?>

            </div>

        </section>

        <section class="notifications-section">

            <div class="notifications-section-heading">

                <div>

                    <span class="customer-section-number">
                        01 / NOTIFICATIONS
                    </span>

                    <h2>
                        Your recent <em>activity.</em>
                    </h2>

                </div>

                <span class="customer-record-count">
                    <?= (int) $totalNotifications ?>
                    <?= $totalNotifications === 1
                        ? 'NOTIFICATION'
                        : 'NOTIFICATIONS'
                    ?>
                </span>

            </div>

            <?php if (empty($notifications)): ?>

                <div class="notifications-empty">

                    <div class="notifications-empty-mark">
                        <i data-lucide="bell-off"></i>
                    </div>

                    <h3>
                        No notifications yet.
                    </h3>

                    <p>
                        When there's something important to share, you'll see it here.
                    </p>

                </div>

            <?php else: ?>

                <div class="notifications-list">

                    <?php foreach ($notifications as $notification): ?>

                        <?php

                        $isUnread =
                            (int) $notification['is_read'] === 0;

                        $type =
                            trim(
                                (string) $notification['type']
                            );

                        $typeLabel =
                            $type !== ''
                                ? ucfirst($type)
                                : 'Update';

                        ?>

                        <article
                            class="notification-item <?= $isUnread ? 'unread' : '' ?>"
                            data-id="<?= (int) $notification['id'] ?>"
                        >

                            <div class="notification-indicator"></div>

                            <div class="notification-icon notification-icon-<?= htmlspecialchars(
                                $type,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">

                                <?php if ($type === 'order'): ?>

                                    <i data-lucide="shopping-bag"></i>

                                <?php elseif ($type === 'product'): ?>

                                    <i data-lucide="sprout"></i>

                                <?php elseif ($type === 'market'): ?>

                                    <i data-lucide="store"></i>

                                <?php elseif ($type === 'review'): ?>

                                    <i data-lucide="star"></i>

                                <?php elseif ($type === 'announcement'): ?>

                                    <i data-lucide="megaphone"></i>

                                <?php else: ?>

                                    <i data-lucide="bell"></i>

                                <?php endif; ?>

                            </div>

                            <div class="notification-content">

                                <div class="notification-top">

                                    <div class="notification-heading">

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
                                                    $typeLabel,
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

                                <div class="notification-bottom">

                                    <span class="notification-time">

                                        <i data-lucide="clock"></i>

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
                                            onclick="markPageNotificationRead(<?= (int) $notification['id'] ?>)"
                                        >
                                            <i data-lucide="check"></i>
                                            Mark as read
                                        </button>

                                    <?php else: ?>

                                        <span class="notification-read-status">

                                            <i data-lucide="check"></i>
                                            Read

                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <?php if ($totalPages > 1): ?>

                    <div class="product-pagination">

                        <?php if ($currentPage > 1): ?>

                            <a
                                href="?page=<?= $currentPage - 1 ?>"
                                aria-label="Previous page"
                            >
                                <i data-lucide="arrow-left"></i>
                            </a>

                        <?php endif; ?>

                        <?php for (
                            $page = 1;
                            $page <= $totalPages;
                            $page++
                        ): ?>

                            <a
                                href="?page=<?= $page ?>"
                                <?= $page === $currentPage
                                    ? 'class="active"'
                                    : ''
                                ?>
                            >
                                <?= $page ?>
                            </a>

                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>

                            <a
                                href="?page=<?= $currentPage + 1 ?>"
                                aria-label="Next page"
                            >
                                <i data-lucide="arrow-right"></i>
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

    <script src="../assets/js/lucide.js"></script>

    <script>
        lucide.createIcons();
    </script>

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
                        '.notification-item[data-id="' +
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

                    const icon = document.createElement('i');

                    icon.setAttribute(
                        'data-lucide',
                        'check'
                    );

                    status.appendChild(icon);

                    status.appendChild(
                        document.createTextNode(' Read')
                    );

                    button.replaceWith(status);

                    lucide.createIcons({
                        nodes: [icon]
                    });
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
                        '.notification-item.unread'
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

                            const icon = document.createElement('i');

                            icon.setAttribute(
                                'data-lucide',
                                'check'
                            );

                            status.appendChild(icon);

                            status.appendChild(
                                document.createTextNode(' Read')
                            );

                            button.replaceWith(status);

                            lucide.createIcons({
                                nodes: [icon]
                            });
                        }

                    });

                const button =
                    document.querySelector(
                        '.notifications-mark-all'
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

            const unreadCount =
                document.querySelectorAll(
                    '.notification-item.unread'
                ).length;

            const summaryText =
                document.querySelector(
                    '.notifications-summary-content span'
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