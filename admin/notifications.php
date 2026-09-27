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

$where[] = "1 = 1";

if (
$filter_status !== '' &&
in_array($filter_status, $allowed_statuses, true)

) {

if ($filter_status === 'read') {
    $where[] = "n.is_read = 1";
}

if ($filter_status === 'unread') {
    $where[] = "n.is_read = 0";
}

}

if ($filter_type !== '') {

$where[] = "n.type = ?";

$params[] = $filter_type;
$types .= 's';

}

$where_sql = implode(
' AND ',
$where
);
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

    $errors[] =
        'Failed to count notifications.';
}

$count_stmt->close();

} else {

$total_notifications = 0;

$errors[] =
    'Failed to prepare notification count query.';

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

    $errors[] =
        'Failed to load notifications.';
}

$stmt->close();

} else {

$errors[] =
    'Failed to prepare notifications query.';

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

    while ($type_row =
        $type_result->fetch_assoc()
    ) {

        $notification_types[] =
            $type_row['type'];
    }
}

$type_stmt->close();

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
    href="../assets/css/navbar.css"
>

<link
    rel="stylesheet"
    href="../assets/css/sidebar.css"
>

<link
    rel="stylesheet"
    href="../assets/css/dashboard.css"
>

<style>

    .notification-summary {
        display: grid;
        grid-template-columns:
            repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }

    .notification-summary-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 10px;
        padding: 20px;
    }

    .notification-summary-card span {
        display: block;
        color: #777;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .notification-summary-card strong {
        display: block;
        font-size: 28px;
        color: #222;
    }

    .notification-filters {
        display: flex;
        align-items: end;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 25px;
        padding: 20px;
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 10px;
    }

    .notification-filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .notification-filter-group label {
        font-size: 13px;
        font-weight: 600;
        color: #555;
    }

    .notification-filter-group select {
        min-width: 170px;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 7px;
        background: #fff;
    }

    .notification-filter-button {
        padding: 10px 18px;
        border: 0;
        border-radius: 7px;
        background: #27ae60;
        color: #fff;
        cursor: pointer;
        font-weight: 600;
    }

    .notification-reset-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 18px;
        border: 1px solid #ddd;
        border-radius: 7px;
        background: #fff;
        color: #333;
        text-decoration: none;
        font-weight: 600;
    }

    .notification-message {
        max-width: 350px;
        line-height: 1.5;
    }

    .notification-user {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .notification-user-name {
        font-weight: 600;
    }

    .notification-user-email {
        color: #777;
        font-size: 12px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 25px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .pagination a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
        color: #333;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: 0.2s;
    }

    .pagination a:hover {
        background: #27ae60;
        border-color: #27ae60;
        color: #fff;
    }

    .pagination a.active {
        background: #27ae60;
        border-color: #27ae60;
        color: #fff;
    }

    @media (max-width: 700px) {

        .notification-filters {
            align-items: stretch;
        }

        .notification-filter-group {
            width: 100%;
        }

        .notification-filter-group select {
            width: 100%;
        }

        .notification-filter-button,
        .notification-reset-button {
            width: 100%;
        }

        .pagination {
            gap: 5px;
        }

        .pagination a {
            min-width: 36px;
            height: 36px;
            padding: 0 9px;
            font-size: 13px;
        }
    }

</style>


</head>
 <body>
     <?php 
     include __DIR__ . '/../includes/navbar.php'; ?> <?php include __DIR__ . '/../includes/sidebar.php'; 
     ?> 
     <main class="main-content">
<div class="page-header">

    <div>

        <h1>
            Notifications
        </h1>

        <p>
            View and monitor all system notifications.
        </p>

    </div>

</div>

<?php if (!empty($errors)): ?>

    <div class="alert alert-danger">

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

<?php endif; ?>

<!-- SUMMARY -->

<section class="notification-summary">

    <div class="notification-summary-card">

        <span>
            Total Notifications
        </span>

        <strong>
            <?= $total_notifications ?>
        </strong>

    </div>

    <div class="notification-summary-card">

        <span>
            Unread Notifications
        </span>

        <strong>
            <?= $unread_count ?>
        </strong>

    </div>

</section>

<!-- FILTERS -->

<form
    method="GET"
    class="notification-filters"
>

    <div class="notification-filter-group">

        <label for="status">
            Status
        </label>

        <select
            name="status"
            id="status"
        >

            <option value="">
                All
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

    <div class="notification-filter-group">

        <label for="type">
            Type
        </label>

        <select
            name="type"
            id="type"
        >

            <option value="">
                All Types
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

    <button
        type="submit"
        class="notification-filter-button"
    >
        Filter
    </button>

    <a
        href="notifications.php"
        class="notification-reset-button"
    >
        Reset
    </a>

</form>

<!-- NOTIFICATIONS TABLE -->

<section class="table-section">

    <div class="section-header">

        <h2>
            System Notifications
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
                        User
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

                <?php if (!empty($adminNotifications)): ?>

                    <?php foreach (
                        $adminNotifications
                        as $notification
                    ): ?>

                        <?php

                        $notification_status =
                            (int) $notification['is_read'] === 1
                                ? 'Read'
                                : 'Unread';

                        $status_class =
                            (int) $notification['is_read'] === 1
                                ? 'active'
                                : 'pending';

                        ?>

                        <tr>

                            <!-- ID -->

                            <td>

                                #<?= (int) $notification['id'] ?>

                            </td>

                            <!-- USER -->

                            <td>

                                <div class="notification-user">

                                    <span
                                        class="notification-user-name"
                                    >

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

                                        <span
                                            class="notification-user-email"
                                        >

                                            <?= htmlspecialchars(
                                                $notification['user_email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                    <span
                                        class="notification-user-email"
                                    >

                                        ID:
                                        <?= (int) $notification['user_id'] ?>

                                    </span>

                                </div>

                            </td>

                            <!-- TITLE -->

                            <td>

                                <?= htmlspecialchars(
                                    $notification['title']
                                        ?? 'N/A',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>

                            <!-- MESSAGE -->

                            <td>

                                <div
                                    class="notification-message"
                                >

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $notification['message']
                                                ?? 'N/A',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>

                                </div>

                            </td>

                            <!-- TYPE -->

                            <td>

                                <?php if (
                                    !empty(
                                        $notification['type']
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $notification['type']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                <?php else: ?>

                                    N/A

                                <?php endif; ?>

                            </td>

                            <!-- STATUS -->

                            <td>

                                <span
                                    class="status status-<?= $status_class ?>"
                                >

                                    <?= $notification_status ?>

                                </span>

                            </td>

                            <!-- DATE -->

                            <td>

                                <?php if (
                                    !empty(
                                        $notification['created_at']
                                    )
                                ): ?>

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

    <!-- PAGINATION -->

    <?php if ($total_pages > 1): ?>

        <div class="pagination">

            <?php if ($page > 1): ?>

                <a
                    href="?page=<?= $page - 1 ?>&status=<?= urlencode($filter_status) ?>&type=<?= urlencode($filter_type) ?>"
                >
                    Previous
                </a>

            <?php endif; ?>

            <?php for (
                $i = 1;
                $i <= $total_pages;
                $i++
            ): ?>

                <a
                    href="?page=<?= $i ?>&status=<?= urlencode($filter_status) ?>&type=<?= urlencode($filter_type) ?>"
                    class="<?= $i === $page
                        ? 'active'
                        : '' ?>"
                >
                    <?= $i ?>
                </a>

            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>

                <a
                    href="?page=<?= $page + 1 ?>&status=<?= urlencode($filter_status) ?>&type=<?= urlencode($filter_type) ?>"
                >
                    Next
                </a>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</section>

</main> 
</body>
 </html>