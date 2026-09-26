<?php

<<<<<<< HEAD
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('customer');

$customerId = (int) getUserId();

/* =========================
   Mark One Notification Read
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['mark_read'])) {

        $notificationId = (int) ($_POST['notification_id'] ?? 0);

        if ($notificationId > 0) {

            $stmt = $conn->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE id = ?
                  AND user_id = ?
            ");

            if ($stmt) {
                $stmt->bind_param(
                    "ii",
                    $notificationId,
                    $customerId
                );

                $stmt->execute();
                $stmt->close();
            }
        }

        header("Location: notifications.php");
        exit;
    }

    /* =========================
       Mark All Notifications Read
    ========================= */
    if (isset($_POST['mark_all_read'])) {

        $stmt = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND is_read = 0
        ");

        if ($stmt) {
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: notifications.php");
        exit;
    }
}

/* =========================
   Get Notifications
========================= */
=======
require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

// --------------------------------------------------
// Get current user
// --------------------------------------------------

$userId = (int) $_SESSION['user_id'];


// --------------------------------------------------
// Get all notifications for this customer
// --------------------------------------------------
>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1

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

<<<<<<< HEAD
    $stmt->bind_param("i", $customerId);
=======
    $stmt->bind_param('i', $userId);
>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }

    $stmt->close();
}

<<<<<<< HEAD
/* =========================
   Unread Count
========================= */

$unreadCount = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
");

if ($stmt) {

    $stmt->bind_param("i", $customerId);

    if ($stmt->execute()) {

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $unreadCount = (int) ($row['unread_count'] ?? 0);
    }

    $stmt->close();
=======

// --------------------------------------------------
// Count unread notifications
// --------------------------------------------------

$unreadCount = 0;

foreach ($notifications as $notification) {

    if ((int) $notification['is_read'] === 0) {
        $unreadCount++;
    }
>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1
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

<<<<<<< HEAD
    <title>Notifications - MarketLink</title>

    <!-- Dashboard CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

    <!-- Navbar CSS -->
=======
    <title>Notifications | MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/components.css"
    >

>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1
    <link
        rel="stylesheet"
        href="../assets/css/navbar.css"
    >

<<<<<<< HEAD
    <!-- Sidebar CSS -->
=======
>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1
    <link
        rel="stylesheet"
        href="../assets/css/sidebar.css"
    >

<<<<<<< HEAD
    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        /* =========================
           Sidebar / Main Content Fix
        ========================= */

        .main-content {
            margin-left: 240px;
            min-height: 100vh;
            box-sizing: border-box;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        @media (max-width: 800px) {

            .main-content {
                margin-left: 0;
            }

            .main-content.expanded {
                margin-left: 0;
            }
        }


        /* =========================
           Notifications Page
        ========================= */

        .notifications-page {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
            box-sizing: border-box;
        }


        /* =========================
           Page Header
        ========================= */

        .notifications-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .notifications-header-left h1 {
            margin: 0 0 8px;
            font-size: 30px;
            color: #2d3d23;
        }

        .notifications-header-left p {
            margin: 0;
            color: #766F67;
            font-size: 14px;
        }


        /* =========================
           Mark All Button
        ========================= */

        .mark-all-btn {
            border: none;
            background: #2d3d23;
            color: #ffffff;
            padding: 11px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .mark-all-btn:hover {
            background: #3d5130;
        }


        /* =========================
           Notification List
        ========================= */

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }


        /* =========================
           Notification Card
        ========================= */

        .notification-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            background: #ffffff;
            border: 1px solid #e8e1d8;
            border-radius: 16px;
            padding: 20px;
            box-sizing: border-box;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .notification-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(45, 61, 35, 0.08);
        }

        .notification-card.unread {
            border-left: 4px solid #2d3d23;
            background: #fcfaf6;
        }


        /* =========================
           Notification Icon
        ========================= */

        .notification-type-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 12px;
            background: #f9f4ed;
            color: #2d3d23;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }


        /* =========================
           Notification Content
        ========================= */

        .notification-card-content {
            flex: 1;
            min-width: 0;
        }

        .notification-card-title {
            margin: 0 0 6px;
            color: #2d3d23;
            font-size: 16px;
            font-weight: 700;
        }

        .notification-card-message {
            margin: 0 0 9px;
            color: #766F67;
            font-size: 14px;
            line-height: 1.6;
        }

        .notification-card-time {
            color: #9a938b;
            font-size: 12px;
        }


        /* =========================
           Read Button
        ========================= */

        .notification-card-action {
            flex-shrink: 0;
        }

        .mark-read-btn {
            border: 1px solid #d9d0c5;
            background: #ffffff;
            color: #2d3d23;
            padding: 8px 12px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .mark-read-btn:hover {
            background: #f9f4ed;
            border-color: #c3aa7e;
        }


        /* =========================
           Unread Badge
        ========================= */

        .unread-badge {
            display: inline-block;
            margin-left: 8px;
            padding: 4px 8px;
            border-radius: 20px;
            background: #2d3d23;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            vertical-align: middle;
        }


        /* =========================
           Empty State
        ========================= */

        .notifications-empty {
            background: #ffffff;
            border: 1px solid #e8e1d8;
            border-radius: 16px;
            padding: 60px 30px;
            text-align: center;
        }

        .notifications-empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #f9f4ed;
            color: #2d3d23;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .notifications-empty h3 {
            margin: 0 0 8px;
            color: #2d3d23;
            font-size: 18px;
        }

        .notifications-empty p {
            margin: 0;
            color: #766F67;
            font-size: 14px;
        }


        /* =========================
           Responsive
        ========================= */

        @media (max-width: 800px) {

            .notifications-page {
                padding: 20px;
                width: 100%;
            }

            .notifications-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .notification-card {
                padding: 16px;
            }
        }

        @media (max-width: 550px) {

            .notification-card {
                flex-wrap: wrap;
            }

            .notification-card-action {
                width: 100%;
            }

            .mark-read-btn {
                width: 100%;
            }

            .notifications-header-left h1 {
                font-size: 25px;
            }
        }

    </style>

=======
    <link
        rel="stylesheet"
        href="../assets/css/notifications.css"
    >

>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1
</head>

<body>

<<<<<<< HEAD
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
=======
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1


    <main class="main-content">

        <div class="notifications-page">

<<<<<<< HEAD
            <!-- =========================
                 Page Header
            ========================== -->

            <div class="notifications-header">

                <div class="notifications-header-left">

                    <h1>
                        Notifications

                        <?php if ($unreadCount > 0): ?>

                            <span class="unread-badge">
                                <?= $unreadCount ?> unread
                            </span>

                        <?php endif; ?>

                    </h1>

                    <p>
                        Stay updated with your MarketLink activity.
=======

            <!-- ==========================================
                 PAGE HEADER
            =========================================== -->

            <div class="notifications-page-header">

                <div>

                    <span class="page-eyebrow">
                        YOUR ACTIVITY
                    </span>

                    <h1>Notifications</h1>

                    <p>
                        Stay up to date with your MarketLink activity.
>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1
                    </p>

                </div>


                <?php if ($unreadCount > 0): ?>

<<<<<<< HEAD
                    <form
                        method="POST"
                        style="margin: 0;"
                    >

                        <button
                            type="submit"
                            name="mark_all_read"
                            class="mark-all-btn"
                        >
                            Mark all as read
                        </button>

                    </form>
=======
                    <button
                        type="button"
                        class="mark-all-button"
                        onclick="markAllPageNotificationsRead()"
                    >
                        Mark all as read
                    </button>
>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1

                <?php endif; ?>

            </div>


<<<<<<< HEAD
            <!-- =========================
                 Notifications
            ========================== -->

            <?php if (empty($notifications)): ?>

                <div class="notifications-empty">

                    <div class="notifications-empty-icon">
                        <i class="fa-regular fa-bell"></i>
                    </div>

                    <h3>
                        No notifications yet
                    </h3>

                    <p>
                        You're all caught up.
                    </p>

                </div>

            <?php else: ?>

                <div class="notifications-list">

                    <?php foreach ($notifications as $notification): ?>

                        <?php

                        $type = strtolower(
                            $notification['type'] ?? 'system'
                        );

                        switch ($type) {

                            case 'order':
                                $icon = 'fa-solid fa-box';
                                break;

                            case 'review':
                                $icon = 'fa-solid fa-star';
                                break;

                            case 'announcement':
                                $icon = 'fa-solid fa-bullhorn';
                                break;

                            default:
                                $icon = 'fa-solid fa-bell';
                                break;
                        }

                        ?>

                        <div
                            class="notification-card <?= !$notification['is_read'] ? 'unread' : '' ?>"
                        >

                            <div class="notification-type-icon">

                                <i class="<?= $icon ?>"></i>

                            </div>


                            <div class="notification-card-content">

                                <h3 class="notification-card-title">

                                    <?= htmlspecialchars(
                                        $notification['title']
                                    ) ?>

                                    <?php if (!$notification['is_read']): ?>

                                        <span class="unread-badge">
                                            New
                                        </span>

                                    <?php endif; ?>

                                </h3>


                                <p class="notification-card-message">

                                    <?= htmlspecialchars(
                                        $notification['message']
                                    ) ?>

                                </p>


                                <div class="notification-card-time">

                                    <?= date(
                                        'M j, Y - h:i A',
                                        strtotime(
                                            $notification['created_at']
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <?php if (!$notification['is_read']): ?>

                                <div class="notification-card-action">

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="notification_id"
                                            value="<?= (int) $notification['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="mark_read"
                                            class="mark-read-btn"
                                        >
                                            Mark as read
                                        </button>

                                    </form>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
=======
            <!-- ==========================================
                 NOTIFICATION SUMMARY
            =========================================== -->

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


            <!-- ==========================================
                 NOTIFICATIONS
            =========================================== -->

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
>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1

        </div>

    </main>

<<<<<<< HEAD
=======

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

>>>>>>> cf5e085f3df1d0a838d4808fe045f36304c6d1a1
</body>

</html>