<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$adminNotifications = [];

$filter_status = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';

$filter_type = isset($_GET['type'])
    ? trim($_GET['type'])
    : '';

$allowed_statuses = [
    '',
    'read',
    'unread'
];

$items_per_page = 10;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

$where = [];
$params = [];
$types = '';

$where[] = '1 = 1';

if (
    $filter_status !== '' &&
    in_array($filter_status, $allowed_statuses, true)
) {
    if ($filter_status === 'read') {
        $where[] = 'n.is_read = 1';
    }

    if ($filter_status === 'unread') {
        $where[] = 'n.is_read = 0';
    }
}

if ($filter_type !== '') {
    $where[] = 'n.type = ?';
    $params[] = $filter_type;
    $types .= 's';
}

$where_sql = implode(' AND ', $where);

$count_sql = "
    SELECT COUNT(*) AS total
    FROM notifications n
    WHERE {$where_sql}
";

$count_stmt = $conn->prepare($count_sql);

if ($count_stmt) {
    if (!empty($params)) {
        $count_stmt->bind_param(
            $types,
            ...$params
        );
    }

    if ($count_stmt->execute()) {
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();

        $total_notifications = (int) (
            $count_row['total'] ?? 0
        );
    } else {
        $total_notifications = 0;
        $errors[] = 'Failed to count notifications.';
    }

    $count_stmt->close();
} else {
    $total_notifications = 0;
    $errors[] = 'Failed to prepare notification count query.';
}

$total_pages = $total_notifications > 0
    ? (int) ceil(
        $total_notifications / $items_per_page
    )
    : 0;

if (
    $total_pages > 0 &&
    $page > $total_pages
) {
    $page = $total_pages;
}

$offset = ($page - 1) * $items_per_page;

$sql = "
    SELECT
        n.id,
        n.user_id,
        n.title,
        n.message,
        n.type,
        n.is_read,
        n.created_at,
        u.name AS user_name,
        u.email AS user_email
    FROM notifications n
    LEFT JOIN users u
        ON n.user_id = u.id
    WHERE {$where_sql}
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $bind_params = $params;
    $bind_types = $types . 'ii';

    $bind_params[] = $items_per_page;
    $bind_params[] = $offset;

    $stmt->bind_param(
        $bind_types,
        ...$bind_params
    );

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $adminNotifications =
            $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $errors[] = 'Failed to load notifications.';
    }

    $stmt->close();
} else {
    $errors[] = 'Failed to prepare notifications query.';
}

$unread_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE is_read = 0
");

$unread_count = 0;

if ($unread_stmt) {
    if ($unread_stmt->execute()) {
        $unread_result =
            $unread_stmt->get_result();

        $unread_row =
            $unread_result->fetch_assoc();

        $unread_count =
            (int) ($unread_row['total'] ?? 0);
    }

    $unread_stmt->close();
}

$type_stmt = $conn->prepare("
    SELECT DISTINCT type
    FROM notifications
    WHERE type IS NOT NULL
    AND type <> ''
    ORDER BY type ASC
");

$notification_types = [];

if ($type_stmt) {
    if ($type_stmt->execute()) {
        $type_result =
            $type_stmt->get_result();

        while (
            $type_row =
            $type_result->fetch_assoc()
        ) {
            $notification_types[] =
                $type_row['type'];
        }
    }

    $type_stmt->close();
}

$query_string = http_build_query([
    'status' => $filter_status,
    'type' => $filter_type
]);
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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/admin_ann.css">
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-notifications-page">

    <section class="customer-page-hero admin-customers-hero">
        <div class="customer-page-hero-copy">
            <span class="eyebrow">
                ADMIN / NOTIFICATIONS
            </span>

            <h1>
                System <em>notifications.</em>
            </h1>

            <p>
                View and monitor notifications
                delivered across MarketLink.
            </p>
        </div>

        <div class="customer-page-hero-mark">
            09
        </div>
    </section>

    <?php if (!empty($errors)): ?>
        <div class="admin-page-alert alert-danger">
            <span class="admin-alert-mark">!</span>

            <div>
                <?php foreach ($errors as $error): ?>
                    <p>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <section class="admin-notification-summary-section">

        <div class="admin-section-heading">
            <div>
                <span class="eyebrow">
                    01 / OVERVIEW
                </span>

                <h2>
                    Notification <em>activity.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                <?= $total_notifications ?>
                total
            </span>
        </div>

        <div class="admin-notification-summary">

            <div class="admin-notification-summary-card">
                <span class="admin-notification-summary-label">
                    Total notifications
                </span>

                <strong>
                    <?= $total_notifications ?>
                </strong>

                <span class="admin-notification-summary-note">
                    Across all users
                </span>
            </div>

            <div class="admin-notification-summary-card admin-notification-summary-unread">
                <span class="admin-notification-summary-label">
                    Unread notifications
                </span>

                <strong>
                    <?= $unread_count ?>
                </strong>

                <span class="admin-notification-summary-note">
                    Awaiting attention
                </span>
            </div>

        </div>

    </section>

    <section class="admin-notification-filter-section">

        <div class="admin-section-heading">
            <div>
                <span class="eyebrow">
                    02 / FILTER
                </span>

                <h2>
                    Find specific <em>notifications.</em>
                </h2>
            </div>
        </div>

        <form
            method="GET"
            class="admin-notification-filter-card"
        >

            <div class="admin-notification-filter-field">
                <label for="status">
                    Status
                </label>

                <select
                    name="status"
                    id="status"
                >
                    <option value="">
                        All statuses
                    </option>

                    <option
                        value="read"
                        <?= $filter_status === 'read'
                            ? 'selected'
                            : '' ?>
                    >
                        Read
                    </option>

                    <option
                        value="unread"
                        <?= $filter_status === 'unread'
                            ? 'selected'
                            : '' ?>
                    >
                        Unread
                    </option>
                </select>
            </div>

            <div class="admin-notification-filter-field">
                <label for="type">
                    Type
                </label>

                <select
                    name="type"
                    id="type"
                >
                    <option value="">
                        All types
                    </option>

                    <?php foreach (
                        $notification_types
                        as $notification_type
                    ): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $notification_type,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= $filter_type === $notification_type
                                ? 'selected'
                                : '' ?>
                        >
                            <?= htmlspecialchars(
                                ucfirst($notification_type),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-notification-filter-actions">

                <button
                    type="submit"
                    class="admin-action-submit"
                >
                    Filter
                </button>

                <a
                    href="notifications.php"
                    class="admin-action-reset"
                >
                    Reset
                </a>

            </div>

        </form>

    </section>

    <section class="admin-notifications-section">

        <div class="admin-section-heading">
            <div>
                <span class="eyebrow">
                    03 / DIRECTORY
                </span>

                <h2>
                    System <em>notifications.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                <?= $total_notifications ?>
                <?= $total_notifications === 1
                    ? 'notification'
                    : 'notifications' ?>
            </span>
        </div>

        <div class="admin-notifications-table">

            <table>

                <thead>
                    <tr>
                        <th class="admin-notification-id">
                            ID
                        </th>

                        <th class="admin-notification-user">
                            User
                        </th>

                        <th class="admin-notification-content">
                            Notification
                        </th>

                        <th class="admin-notification-type">
                            Type
                        </th>

                        <th class="admin-notification-status">
                            Status
                        </th>

                        <th class="admin-notification-date">
                            Date
                        </th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($adminNotifications)): ?>

                    <?php foreach (
                        $adminNotifications
                        as $notification
                    ): ?>

                        <?php
                        $is_read =
                            (int) $notification['is_read'] === 1;

                        $notification_status =
                            $is_read
                                ? 'Read'
                                : 'Unread';

                        $status_class =
                            $is_read
                                ? 'admin-status-active'
                                : 'admin-status-pending';
                        ?>

                        <tr>

                            <td class="admin-notification-id-cell">
                                #<?= (int) $notification['id'] ?>
                            </td>

                            <td>
                                <div class="admin-notification-user-wrap">

                                    <span class="admin-notification-user-name">
                                        <?= htmlspecialchars(
                                            $notification['user_name']
                                                ?? 'Unknown User',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <?php if (
                                        !empty(
                                            $notification['user_email']
                                        )
                                    ): ?>

                                        <span class="admin-notification-user-email">
                                            <?= htmlspecialchars(
                                                $notification['user_email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                    <span class="admin-notification-user-id">
                                        ID:
                                        <?= (int) $notification['user_id'] ?>
                                    </span>

                                </div>
                            </td>

                            <td>
                                <div class="admin-notification-content-wrap">

                                    <span class="admin-notification-title">
                                        <?= htmlspecialchars(
                                            $notification['title']
                                                ?? 'N/A',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <span class="admin-notification-message">
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $notification['message']
                                                    ?? 'N/A',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        ) ?>
                                    </span>

                                </div>
                            </td>

                            <td>
                                <?php if (
                                    !empty(
                                        $notification['type']
                                    )
                                ): ?>

                                    <span class="admin-notification-type-label">
                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $notification['type']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                <?php else: ?>

                                    <span class="admin-table-muted">
                                        N/A
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <span
                                    class="admin-status <?= $status_class ?>"
                                >
                                    <span class="admin-status-dot"></span>
                                    <?= $notification_status ?>
                                </span>
                            </td>

                            <td>
                                <?php if (
                                    !empty(
                                        $notification['created_at']
                                    )
                                ): ?>

                                    <div class="admin-notification-date-wrap">
                                        <span class="admin-notification-date">
                                            <?= date(
                                                'Y-m-d',
                                                strtotime(
                                                    $notification['created_at']
                                                )
                                            ) ?>
                                        </span>

                                        <span class="admin-notification-time">
                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $notification['created_at']
                                                )
                                            ) ?>
                                        </span>
                                    </div>

                                <?php else: ?>

                                    <span class="admin-table-muted">
                                        N/A
                                    </span>

                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td
                            colspan="6"
                            class="admin-table-empty"
                        >
                            <div class="admin-empty-state">

                                <span class="admin-empty-mark">
                                    ✦
                                </span>

                                <strong>
                                    No notifications found.
                                </strong>

                                <span>
                                    Try adjusting the filters
                                    or check again later.
                                </span>

                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php if ($total_pages > 1): ?>

            <div class="admin-pagination">

                <div class="admin-pagination-info">
                    Page <?= $page ?>
                    of <?= $total_pages ?>
                </div>

                <div class="admin-pagination-controls">

                    <?php if ($page > 1): ?>

                        <a
                            href="?page=<?= $page - 1 ?>&<?= $query_string ?>"
                            class="admin-pagination-arrow"
                        >
                            Previous
                        </a>

                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min(
                        $total_pages,
                        $page + 2
                    );
                    ?>

                    <?php if ($start_page > 1): ?>

                        <a
                            href="?page=1&<?= $query_string ?>"
                            class="admin-pagination-number"
                        >
                            1
                        </a>

                        <?php if ($start_page > 2): ?>
                            <span class="admin-pagination-dots">
                                ...
                            </span>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php for (
                        $i = $start_page;
                        $i <= $end_page;
                        $i++
                    ): ?>

                        <a
                            href="?page=<?= $i ?>&<?= $query_string ?>"
                            class="admin-pagination-number <?= $i === $page
                                ? 'active'
                                : '' ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>

                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="admin-pagination-dots">
                                ...
                            </span>
                        <?php endif; ?>

                        <a
                            href="?page=<?= $total_pages ?>&<?= $query_string ?>"
                            class="admin-pagination-number"
                        >
                            <?= $total_pages ?>
                        </a>

                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>

                        <a
                            href="?page=<?= $page + 1 ?>&<?= $query_string ?>"
                            class="admin-pagination-arrow"
                        >
                            Next
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>