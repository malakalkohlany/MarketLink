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

            $errors[] =
             'Failed to prepare user lookup.';

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

                $errors[] =
                 'Failed to find user.';
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

                    }else {

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

    $countResult =
        $countStmt->get_result();

    $countRow =
        $countResult->fetch_assoc();

    $total_users =
        (int) (
            $countRow['total_users'] ?? 0
        );

} else {

    $errors[] =
    'Failed to count users.';
}

$countStmt->close();

} else {

$errors[] =
    'Failed to prepare user count query.';

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

$bindTypes =
    $types . 'ii';

$bindParams[] =
    $items_per_page;

$bindParams[] =
    $offset;

$stmt->bind_param(
    $bindTypes,
    ...$bindParams
);

if ($stmt->execute()) {

    $result =
        $stmt->get_result();

    $users =
        $result->fetch_all(
            MYSQLI_ASSOC
        );

} else {

    $errors[] =
    'Failed to load users.';
}

$stmt->close();

} else {

$errors[] =
    'Failed to prepare user query.';

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

<style>


    .user-search {

        display: flex;

        align-items: end;

        gap: 10px;

        flex-wrap: wrap;

        margin-bottom: 25px;

        padding: 20px;

        background: #fff;

        border: 1px solid #e5e5e5;

        border-radius: 10px;
    }

    .user-search-group {

        display: flex;

        flex-direction: column;

        gap: 6px;

        flex: 1;

        min-width: 220px;
    }

    .user-search-group label {

        font-size: 13px;

        font-weight: 600;

        color: #555;
    }

    .user-search-group input,
    .user-search-group select {

        width: 100%;

        padding: 10px 12px;

        border: 1px solid #ddd;

        border-radius: 7px;

        background: #fff;

        box-sizing: border-box;

        font-size: 14px;
    }

    .user-search-button {

        padding: 10px 18px;

        border: 0;

        border-radius: 7px;

        background: #27ae60;

        color: #fff;

        cursor: pointer;

        font-weight: 600;
    }

    .user-search-button:hover {

        background: #219150;
    }

    .user-reset-button {

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

    .user-reset-button:hover {

        background: #f5f5f5;
    }
      .action-buttons {

        display: flex;

        align-items: center;

        gap: 6px;

        flex-wrap: wrap;
    }

    .action-buttons form {

        margin: 0;
    }

    .btn-activate {

        background: var(--sage);

        color: var(--white);

        border: none;
    }

    .btn-activate:hover {

        background: var(--sage-dark);

        color: var(--white);
    }

    .btn-deactivate {

        background: var(--terracotta);

        color: var(--white);

        border: none;
    }

    .btn-deactivate:hover {

        background: var(--terracotta-dark);

        color: var(--white);
    }


    .status-active {

        background: var(--sage-light);

        color: var(--sage-dark);
    }

    .status-inactive {

        background: var(--terracotta-soft);

        color: var(--terracotta-dark);
    }

    .status-pending {

        background: var(--marigold-soft);

        color: var(--marigold-dark);
    }
      .role-admin {

        background: #e8def8;

        color: #6c3fa0;
    }

    .role-farmer {

        background: var(--sage-light);

        color: var(--sage-dark);
    }

    .role-customer {

        background: #e3f2fd;

        color: #1976d2;
    }
      .results-info {

        margin-bottom: 15px;

        color: #666;

        font-size: 14px;
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

        .user-search {

            align-items: stretch;
        }

        .user-search-group {

            width: 100%;

            min-width: 100%;
        }

        .user-search-button,
        .user-reset-button {

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
    include __DIR__ . '/../includes/navbar.php'; 
    ?>
     <?php 
     include __DIR__ . '/../includes/sidebar.php';
      ?> 
      <div class="admin-container">
<main class="main-content">
     <div class="page-header">

        <div>

            <h1>
                Users
            </h1>

            <p>
                Manage registered users and their account status.
            </p>

        </div>

    </div>


    <?php if (!empty($success)): ?>

        <div class="alert alert-success">

            <p>
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </div>

    <?php endif; ?>

 

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

    <form
        method="GET"
        class="user-search"
    >

        <!-- Search -->

        <div class="user-search-group">

            <label for="search">
                Search Users
            </label>

            <input
                type="text"
                name="search"
                id="search"
                value="<?= htmlspecialchars(
                    $search,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                placeholder="Search by name or email..."
            >

        </div>


        <div class="user-search-group">

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
                    <?= $roleFilter === 'admin'
                        ? 'selected'
                        : '' ?>
                >
                    Admin
                </option>

                <option
                    value="farmer"
                    <?= $roleFilter === 'farmer'
                        ? 'selected'
                        : '' ?>
                >
                    Farmer
                </option>

                <option
                    value="customer"
                    <?= $roleFilter === 'customer'
                        ? 'selected'
                        : '' ?>
                >
                    Customer
                </option>

            </select>

        </div>


        <div class="user-search-group">

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
                    <?= $statusFilter === 'active'
                        ? 'selected'
                        : '' ?>
                >
                    Active
                </option>

                <option
                    value="inactive"
                    <?= $statusFilter === 'inactive'
                        ? 'selected'
                        : '' ?>
                >
                    Inactive
                </option>

                <option
                    value="pending"
                    <?= $statusFilter === 'pending'
                        ? 'selected'
                        : '' ?>
                >
                    Pending
                </option>

            </select>

        </div>


        <button
            type="submit"
            class="user-search-button"
        >
            Search
        </button>


        <?php if (
            $search !== '' ||
            $roleFilter !== '' ||
            $statusFilter !== ''
        ): ?>

            <a
                href="user.php"
                class="user-reset-button"
            >
                Reset
            </a>

        <?php endif; ?>

    </form>
    <section class="table-section">

        <div class="section-header">

            <h2>
                Registered Users
            </h2>

        </div>

        <?php if ($total_users > 0): ?>

            <div class="results-info">

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

            </div>

        <?php endif; ?>

        <div class="table-responsive">

            <table class="data-table">

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

                    <?php foreach (
                        $users as $user
                    ): ?>

                        <?php

                        $userId =
                            (int) $user['id'];

                        $userRole =
                            $user['role'] ?? '';

                        $userStatus =
                            $user['status'] ?? 'active';

                        ?>

                        <tr>

                            <!-- ID -->

                            <td>

                                <?= $userId ?>

                            </td>

                            <!-- Name -->

                            <td>

                                <?= htmlspecialchars(
                                    $user['name'] ?? 'N/A',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>

                            <!-- Email -->

                            <td>

                                <?= htmlspecialchars(
                                    $user['email'] ?? 'N/A',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>

                            <!-- Role -->

                            <td>

                                <span
                                    class="status role-<?= htmlspecialchars(
                                        $userRole,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                    <?= ucfirst(
                                        htmlspecialchars(
                                            $userRole,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>

                                </span>

                            </td>

                            <!-- Status -->

                            <td>

                                <span
                                    class="status status-<?= htmlspecialchars(
                                        $userStatus,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                    <?= ucfirst(
                                        htmlspecialchars(
                                            $userStatus,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>

                                </span>

                            </td>

                            <!-- Joined -->

                            <td>

                                <?= !empty(
                                    $user['created_at']
                                )

                                    ? date(
                                        'Y-m-d',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    )

                                    : 'N/A'
                                ?>

                            </td>

                            <!-- Actions -->

                            <td>

                                <div
                                    class="action-buttons"
                                >

                                    <!-- View -->

                                    <a
                                        href="user_details.php?id=<?= $userId ?>"
                                        class="btn btn-sm btn-secondary"
                                    >
                                        View
                                    </a>

                                    <?php if (
                                        $userRole !== 'admin'
                                    ): ?>

                                        <?php if (
                                            $userStatus === 'active'
                                        ): ?>

                                            <!-- Deactivate -->

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
                                                    class="btn btn-sm btn-deactivate"
                                                >
                                                    Deactivate
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <!-- Activate -->

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
                                                    class="btn btn-sm btn-activate"
                                                >
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

                        <td colspan="7">

                            <?php if (
                                $search !== '' ||
                                $roleFilter !== '' ||
                                $statusFilter !== ''
                            ): ?>

                                No users found matching your filters.

                            <?php else: ?>

                                No users found.

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>
        <?php if ($total_pages > 1): ?>

            <div class="pagination">

                <?php if ($page > 1): ?>

                    <a
                        href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"
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
                        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"
                        class="<?= $i === $page
                            ? 'active'
                            : '' ?>"
                    >

                        <?= $i ?>

                    </a>

                <?php endfor; ?>

                <?php if (
                    $page < $total_pages
                ): ?>

                    <a
                        href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"
                    >
                        Next
                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

</main>


</div> 
</body> 
</html>