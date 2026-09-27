<?php

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../includes/session.php';


?>

    <nav class="navbar">
        
    <div class="navbar-left">

        <button class="navbar-menu" onclick="toggleSidebar()">
            ☰
        </button>

    </div>

    <div class="navbar-right">

        <?php
            $notification_count = 0;
            $notifications = [];

            if (isset($_SESSION['user_id'])) {

                $user_id = (int) $_SESSION['user_id'];

                // Get unread notification count
                $stmt = $conn->prepare("
                    SELECT COUNT(*)
                    FROM notifications
                    WHERE user_id = ? AND is_read = 0
                ");

                if ($stmt) {
                    $stmt->bind_param("i", $user_id);

                    if ($stmt->execute()) {
                        $stmt->bind_result($notification_count);
                        $stmt->fetch();
                    }

                    $stmt->close();
                }

                // Get latest notifications
                $stmt = $conn->prepare("
                    SELECT id, title, message, type, is_read, created_at
                    FROM notifications
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                    LIMIT 5
                ");

                if ($stmt) {
                    $stmt->bind_param("i", $user_id);

                    if ($stmt->execute()) {
                        $notif_result = $stmt->get_result();

                        while ($row = $notif_result->fetch_assoc()) {
                            $notifications[] = $row;
                        }
                    }

                    $stmt->close();
                }
            }
        ?>

        <div class="notification-wrapper">

            <button
                type="button"
                class="navbar-icon notification-button"
                title="Notifications"
                onclick="toggleNotifications()"
            >
                ♡

                <?php if ($notification_count > 0): ?>
                    <span class="notification-badge">
                        <?= $notification_count > 99 ? '99+' : $notification_count ?>
                    </span>
                <?php endif; ?>
            </button>

            <div
                class="notification-dropdown"
                id="notificationDropdown"
            >

                <div class="notification-header">

                    <div>
                        <strong>Notifications</strong>

                        <?php if ($notification_count > 0): ?>
                            <span>
                                <?= $notification_count ?> unread
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($notification_count > 0): ?>
                        <button
                            type="button"
                            onclick="markAllNotificationsRead()"
                        >
                            Mark all read
                        </button>
                    <?php endif; ?>

                </div>

                <div class="notification-list">

                    <?php if (empty($notifications)): ?>

                        <div class="notification-empty">

                            <div class="notification-empty-icon">
                                🔔
                            </div>

                            <p>No notifications yet</p>

                            <span>
                                You're all caught up.
                            </span>

                        </div>

                    <?php else: ?>

                        <?php foreach ($notifications as $notification): ?>

                            <div
                                class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>"
                                data-id="<?= (int) $notification['id'] ?>"
                                onclick="openNotification(<?= (int) $notification['id'] ?>)"
                            >

                                <div class="notification-dot"></div>

                                <div class="notification-content">

                                    <div class="notification-title">
                                        <?= htmlspecialchars($notification['title']) ?>
                                    </div>

                                    <div class="notification-message">
                                        <?= htmlspecialchars($notification['message']) ?>
                                    </div>

                                    <div class="notification-time">
                                        <?= date(
                                            'M j, g:i A',
                                            strtotime($notification['created_at'])
                                        ) ?>
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <?php if (!empty($notifications)): ?>

                    <a
                        href="notifications.php"
                        class="notification-footer"
                    >
                        View all notifications →
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <div class="navbar-divider"></div>


        <a href="#" class="navbar-user">

            <div class="user-avatar">
                <?= strtoupper(
                    substr(
                        $_SESSION['name'] ?? 'U',
                        0,
                        1
                    )
                ) ?>
            </div>

            <div>

                <div class="user-name">
                    <?= htmlspecialchars(
                        $_SESSION['name'] ?? 'User'
                    ) ?>
                </div>

                <div class="user-role">
                    <?= ucfirst(
                        $_SESSION['role'] ?? ''
                    ) ?>
                </div>

            </div>

        </a>

    </div>

    </nav>

    <script>
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    </script>

    <script src="../assets/js/navbar.js"></script>
    <script src="../assets/js/app.js"></script>