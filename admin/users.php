<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userId = isset($_POST['user_id'])
        ? (int) $_POST['user_id']
        : 0;

    $action = $_POST['action'] ?? '';

    if ($userId <= 0) {

        $errors[] = 'Invalid user.';

    } elseif (!in_array($action, ['activate', 'deactivate'], true)) {

        $errors[] = 'Invalid action.';

    } else {

        if (
            isset($_SESSION['user_id']) &&
            $userId === (int) $_SESSION['user_id']
        ) {

            $errors[] = 'You cannot change your own account status.';

        } else {

            $newStatus = $action === 'activate'
                ? 'active'
                : 'inactive';

            $checkStmt = $conn->prepare("
                SELECT
                    id,
                    name,
                    email,
                    role,
                    status
                FROM users
                WHERE id = ?
                LIMIT 1
            ");

            if (!$checkStmt) {

                $errors[] = 'Failed to prepare user lookup.';

            } else {

                $checkStmt->bind_param(
                    "i",
                    $userId
                );

                if ($checkStmt->execute()) {

                    $result = $checkStmt->get_result();
                    $user = $result->fetch_assoc();

                } else {

                    $user = null;
                    $errors[] = 'Failed to find user.';
                }

                $checkStmt->close();

                if (empty($errors)) {

                    if (!$user) {

                        $errors[] = 'User not found.';

                    } elseif (
                        isset($user['role']) &&
                        $user['role'] === 'admin'
                    ) {

                        $errors[] =
                            'Admin accounts cannot be deactivated from this page.';

                    } else {

                        $updateStmt = $conn->prepare("
                            UPDATE users
                            SET status = ?
                            WHERE id = ?
                        ");

                        if (!$updateStmt) {

                            $errors[] =
                                'Failed to prepare user update.';

                        } else {

                            $updateStmt->bind_param(
                                "si",
                                $newStatus,
                                $userId
                            );

                            if ($updateStmt->execute()) {

                                if ($updateStmt->affected_rows >= 0) {

                                    $success =
                                        $newStatus === 'active'
                                            ? 'User activated successfully.'
                                            : 'User deactivated successfully.';

                                } else {

                                    $errors[] =
                                        'The user status could not be updated.';
                                }

                            } else {

                                $errors[] =
                                    'Failed to update user: ' .
                                    $updateStmt->error;
                            }

                            $updateStmt->close();
                        }
                    }
                }
            }
        }
    }
}

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$roleFilter = isset($_GET['role'])
    ? trim($_GET['role'])
    : '';

$statusFilter = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';

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

if ($search !== '') {

    $where[] = "
        (
            u.name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $searchParam = '%' . $search . '%';

    $params[] = $searchParam;
    $params[] = $searchParam;

    $types .= 'ss';
}

if ($roleFilter !== '') {

    $where[] = "u.role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

if ($statusFilter !== '') {

    $where[] = "u.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(' AND ', $where);
}

$countSql = "
    SELECT COUNT(*) AS total_users
    FROM users u
    {$whereSql}
";

$countStmt = $conn->prepare($countSql);

$total_users = 0;

if ($countStmt) {

    if (!empty($params)) {

        $countStmt->bind_param(
            $types,
            ...$params
        );
    }

    if ($countStmt->execute()) {

        $countResult = $countStmt->get_result();

        $countRow = $countResult->fetch_assoc();

        $total_users = (int) (
            $countRow['total_users'] ?? 0
        );

    } else {

        $errors[] = 'Failed to count users.';
    }

    $countStmt->close();

} else {

    $errors[] = 'Failed to prepare user count query.';
}

$total_pages = $total_users > 0
    ? (int) ceil(
        $total_users / $items_per_page
    )
    : 0;

if (
    $total_pages > 0 &&
    $page > $total_pages
) {

    $page = $total_pages;
}

$offset =
    ($page - 1) *
    $items_per_page;

$users = [];

$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.role,
        u.status,
        u.created_at
    FROM users u
    {$whereSql}
    ORDER BY
        CASE
            WHEN u.role = 'admin' THEN 0
            WHEN u.role = 'farmer' THEN 1
            WHEN u.role = 'customer' THEN 2
            ELSE 3
        END,
        u.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $bindParams = $params;

    $bindTypes = $types . 'ii';

    $bindParams[] = $items_per_page;
    $bindParams[] = $offset;

    $stmt->bind_param(
        $bindTypes,
        ...$bindParams
    );

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        $users = $result->fetch_all(
            MYSQLI_ASSOC
        );

    } else {

        $errors[] = 'Failed to load users.';
    }

    $stmt->close();

} else {

    $errors[] = 'Failed to prepare user query.';
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

    <title>
        Users | MarketLink
    </title>

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
        href="../assets/css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/customer.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/admin_ann.css"
    >

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content admin-users-page">

        <section class="customer-page-hero admin-users-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                    ADMIN / USERS
                </span>

                <h1>
                    Manage local <em>users.</em>
                </h1>

                <p>
                    View registered accounts, review their roles and status,
                    and manage access across the MarketLink platform.
                </p>

            </div>


        </section>

        <?php if (!empty($success)): ?>

            <div class="admin-users-alert admin-users-alert-success">

                <i data-lucide="circle-check"></i>

                <p>
                    <?= e($success) ?>
                </p>

            </div>

        <?php endif; ?>

        <?php if (!empty($errors)): ?>

            <div class="admin-users-alert admin-users-alert-error">

                <i data-lucide="circle-alert"></i>

                <div>

                    <?php foreach ($errors as $error): ?>

                        <p>
                            <?= e($error) ?>
                        </p>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>

        <section class="admin-users-section">

            <div class="admin-users-section-heading">

                <div>

                    <span class="customer-section-number">
                        01 / USERS
                    </span>

                    <h2>
                        Registered <em>accounts.</em>
                    </h2>

                </div>

                <span class="admin-users-record-count">
                    <?= $total_users ?>
                    <?= $total_users === 1 ? 'USER' : 'USERS' ?>
                </span>

            </div>

            <form
                method="GET"
                class="admin-users-filters"
            >

                <div class="admin-users-filter-field admin-users-search-field">

                    <label for="search">
                        Search users
                    </label>

                    <div class="admin-users-input-wrap">

                        <i data-lucide="search"></i>

                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="<?= e($search) ?>"
                            placeholder="Search by name or email..."
                        >

                    </div>

                </div>

                <div class="admin-users-filter-field">

                    <label for="role">
                        Role
                    </label>

                    <select
                        name="role"
                        id="role"
                    >

                        <option value="">
                            All Roles
                        </option>

                        <option
                            value="admin"
                            <?= $roleFilter === 'admin' ? 'selected' : '' ?>
                        >
                            Admin
                        </option>

                        <option
                            value="farmer"
                            <?= $roleFilter === 'farmer' ? 'selected' : '' ?>
                        >
                            Farmer
                        </option>

                        <option
                            value="customer"
                            <?= $roleFilter === 'customer' ? 'selected' : '' ?>
                        >
                            Customer
                        </option>

                    </select>

                </div>

                <div class="admin-users-filter-field">

                    <label for="status">
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="active"
                            <?= $statusFilter === 'active' ? 'selected' : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $statusFilter === 'inactive' ? 'selected' : '' ?>
                        >
                            Inactive
                        </option>

                        <option
                            value="pending"
                            <?= $statusFilter === 'pending' ? 'selected' : '' ?>
                        >
                            Pending
                        </option>

                    </select>

                </div>

                <div class="admin-users-filter-actions">

                    <button
                        type="submit"
                        class="admin-users-search-button"
                    >
                        <i data-lucide="search"></i>
                        Search
                    </button>

                    <?php if (
                        $search !== '' ||
                        $roleFilter !== '' ||
                        $statusFilter !== ''
                    ): ?>

                        <a
                            href="user.php"
                            class="admin-users-reset-button"
                        >
                            Reset
                        </a>

                    <?php endif; ?>

                </div>

            </form>

            <div class="admin-users-results-heading">

                <?php if ($total_users > 0): ?>

                    <span>
                        Showing
                        <?= $offset + 1 ?>
                        -
                        <?= min(
                            $offset + $items_per_page,
                            $total_users
                        ) ?>
                        of
                        <?= $total_users ?>
                        users
                    </span>

                <?php else: ?>

                    <span>
                        No users to display
                    </span>

                <?php endif; ?>

            </div>

            <div class="admin-users-table-card">

                <div class="admin-users-table-wrap">

                    <table class="admin-users-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Joined
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (!empty($users)): ?>

                                <?php foreach ($users as $user): ?>

                                    <?php

                                    $userId =
                                        (int) $user['id'];

                                    $userRole =
                                        $user['role'] ?? '';

                                    $userStatus =
                                        $user['status'] ?? 'active';

                                    ?>

                                    <tr>

                                        <td
                                            data-label="ID"
                                            class="admin-users-id"
                                        >
                                            <?= $userId ?>
                                        </td>

                                        <td
                                            data-label="Name"
                                            class="admin-users-name"
                                        >
                                            <?= e(
                                                $user['name'] ?? 'N/A'
                                            ) ?>
                                        </td>

                                        <td
                                            data-label="Email"
                                            class="admin-users-email"
                                        >
                                            <?= e(
                                                $user['email'] ?? 'N/A'
                                            ) ?>
                                        </td>

                                        <td data-label="Role">

                                            <span
                                                class="admin-user-role admin-user-role-<?= e($userRole) ?>"
                                            >
                                                <?= e(
                                                    ucfirst($userRole)
                                                ) ?>
                                            </span>

                                        </td>

                                        <td data-label="Status">

                                            <span
                                                class="admin-user-status admin-user-status-<?= e($userStatus) ?>"
                                            >
                                                <?= e(
                                                    ucfirst($userStatus)
                                                ) ?>
                                            </span>

                                        </td>

                                        <td data-label="Joined">

                                            <?= !empty(
                                                $user['created_at']
                                            )
                                                ? e(
                                                    date(
                                                        'M j, Y',
                                                        strtotime(
                                                            $user['created_at']
                                                        )
                                                    )
                                                )
                                                : 'N/A'
                                            ?>

                                        </td>

                                        <td
                                            data-label="Action"
                                            class="admin-users-actions-cell"
                                        >

                                            <div class="admin-users-actions">

                                                <a
                                                    href="user_details.php?id=<?= $userId ?>"
                                                    class="admin-users-view"
                                                >
                                                    <i data-lucide="arrow-up-right"></i>
                                                    View
                                                </a>

                                                <?php if (
                                                    $userRole !== 'admin'
                                                ): ?>

                                                    <?php if (
                                                        $userStatus === 'active'
                                                    ): ?>

                                                        <form
                                                            method="POST"
                                                            onsubmit="return confirm('Deactivate this user?');"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="user_id"
                                                                value="<?= $userId ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="action"
                                                                value="deactivate"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="admin-users-status-button admin-users-deactivate"
                                                            >
                                                                <i data-lucide="ban"></i>
                                                                Deactivate
                                                            </button>

                                                        </form>

                                                    <?php else: ?>

                                                        <form
                                                            method="POST"
                                                            onsubmit="return confirm('Activate this user?');"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="user_id"
                                                                value="<?= $userId ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="action"
                                                                value="activate"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="admin-users-status-button admin-users-activate"
                                                            >
                                                                <i data-lucide="check"></i>
                                                                Activate
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="7"
                                        class="admin-users-empty"
                                    >

                                        <div class="admin-users-empty-mark">

                                            <i data-lucide="users-round"></i>

                                        </div>

                                        <h3>
                                            No users found.
                                        </h3>

                                        <p>

                                            <?php if (
                                                $search !== '' ||
                                                $roleFilter !== '' ||
                                                $statusFilter !== ''
                                            ): ?>

                                                No users match the selected filters.

                                            <?php else: ?>

                                                There are no registered users yet.

                                            <?php endif; ?>

                                        </p>

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <?php if ($total_pages > 1): ?>

                <div class="product-pagination">

                    <?php if ($page > 1): ?>

                        <a
                            href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"
                            aria-label="Previous page"
                        >
                            <i data-lucide="chevron-left"></i>
                        </a>

                    <?php endif; ?>

                    <?php for (
                        $i = 1;
                        $i <= $total_pages;
                        $i++
                    ): ?>

                        <a
                            href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"
                            class="<?= $i === $page ? 'active' : '' ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>

                        <a
                            href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"
                            aria-label="Next page"
                        >
                            <i data-lucide="chevron-right"></i>
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

    <script src="../assets/js/lucide.js"></script>

    <script>
        lucide.createIcons();
    </script>

</body>

</html>