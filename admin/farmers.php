<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid CSRF token.';

    } else {

        $farmerId = isset($_POST['farmer_id'])
            ? (int) $_POST['farmer_id']
            : 0;

        $action = $_POST['action'] ?? '';

        if ($farmerId <= 0) {

            $errors[] = 'Invalid farmer.';

        } elseif (!in_array($action, ['approve', 'reject'], true)) {

            $errors[] = 'Invalid action.';

        } else {

            $newStatus = $action === 'approve'
                ? 'approved'
                : 'rejected';

            /*
             * Get the farmer first so we have their
             * user_id and stall_name for the notification.
             */
            $stmt = $conn->prepare("
                SELECT
                    f.id,
                    f.stall_name,
                    f.user_id,
                    f.approval_status
                FROM farmers f
                WHERE f.id = ?
                LIMIT 1
            ");

            if (!$stmt) {

               $errors[] =
                'Failed to prepare farmer lookup.';

            } else {

                $stmt->bind_param("i", $farmerId);
                $stmt->execute();

                $result = $stmt->get_result();
                $farmer = $result->fetch_assoc();

                $stmt->close();

                if (!$farmer) {

                    $errors[] = 'Farmer not found.';

                } elseif ($farmer['approval_status'] !== 'pending') {

                    $errors[] =
                        'This farmer has already been processed.';

                } else {

                    /*
                     * Only update a farmer who is still pending.
                     * This also protects against two admin requests
                     * processing the same farmer.
                     */
                    $updateStmt = $conn->prepare("
                        UPDATE farmers
                        SET approval_status = ?
                        WHERE id = ?
                          AND approval_status = 'pending'
                    ");

                    if (!$updateStmt) {

                        $errors[] =
                         'Failed to prepare approval update.';

                    } else {

                        $updateStmt->bind_param(
                            "si",
                            $newStatus,
                            $farmerId
                        );

                        if ($updateStmt->execute()) {

                            if ($updateStmt->affected_rows === 1) {

                                /*
                                 * Notify the farmer.
                                 */
                                if (!empty($farmer['user_id'])) {

                                    if ($newStatus === 'approved') {

                                        createNotification(
                                            $conn,
                                            (int) $farmer['user_id'],
                                            'farmer_approved',
                                            'Farmer Account Approved',
                                            "Your farmer account for {$farmer['stall_name']} has been approved. You can now access your farmer dashboard."
                                        );

                                        $success =
                                            'Farmer approved successfully.';

                                    } else {

                                        createNotification(
                                            $conn,
                                            (int) $farmer['user_id'],
                                            'farmer_rejected',
                                            'Farmer Account Rejected',
                                            "Your farmer account for {$farmer['stall_name']} has been rejected."
                                        );

                                        $success =
                                            'Farmer rejected successfully.';
                                    }

                                } else {

                                    /*
                                     * Fallback if the farmer has no
                                     * associated user account.
                                     */
                                    $success = $newStatus === 'approved'
                                        ? 'Farmer approved successfully.'
                                        : 'Farmer rejected successfully.';
                                }

                            } else {

                                $errors[] =
                                    'The farmer could not be updated. '
                                    . 'They may have already been processed.';
                            }

                        } else {

                            $errors[] =
                                'Failed to update farmer: '
                                . $updateStmt->error;
                        }

                        $updateStmt->close();
                    }
                }
            }
        }
    }
}


/*
 * Load all farmers for the directory.
 */
$farmers = [];

$stmt = $conn->prepare("
    SELECT
        f.id,
        f.stall_name,
        f.contact_person,
        f.address,
        u.email,
        f.approval_status,
        f.created_at
    FROM farmers f
    LEFT JOIN users u
        ON f.user_id = u.id
    ORDER BY
        CASE
            WHEN f.approval_status = 'pending' THEN 0
            WHEN f.approval_status = 'approved' THEN 1
            ELSE 2
        END,
        f.created_at DESC
");

if ($stmt) {

    if ($stmt->execute()) {

        $result = $stmt->get_result();
        $farmers = $result->fetch_all(MYSQLI_ASSOC);

    } else {

        $errors[] = 'Failed to load farmers.';
    }

    $stmt->close();

} else {

    $errors[] = 'Failed to prepare farmer query.';
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

    <title>Farmers | MarketLink</title>

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

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content admin-farmers-page">

        <section class="admin-page-hero">

            <div>

                <span class="eyebrow">
                    ADMIN / FARMERS
                </span>

                <h1>
                    Manage local
                    <em>farmers.</em>
                </h1>

                <p>
                    Review registrations, manage approvals,
                    and keep the MarketLink marketplace trusted.
                </p>

            </div>

            <div class="admin-page-mark">
                <span>02</span>
            </div>

        </section>

        <?php if (!empty($success)): ?>

            <div class="admin-page-alert alert-success">
                <span class="admin-alert-mark">✓</span>

                <p>
                    <?= e($success) ?>
                </p>
            </div>

        <?php endif; ?>

        <?php if (!empty($errors)): ?>

            <div class="admin-page-alert alert-danger">

                <span class="admin-alert-mark">!</span>

                <div>

                    <?php foreach ($errors as $error): ?>

                        <p>
                            <?= e($error) ?>
                        </p>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>

        <section class="admin-management-section">

            <div class="admin-section-heading">

                <div>

                    <span class="eyebrow">
                        01 / Directory
                    </span>

                    <h2>
                        Farmer <em>registrations.</em>
                    </h2>

                </div>

                <span class="admin-record-count">
                    <?= count($farmers) ?>
                    <?= count($farmers) === 1 ? 'farmer' : 'farmers' ?>
                </span>

            </div>

            <div class="admin-farmers-table">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Farmer</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($farmers)): ?>

                            <?php foreach ($farmers as $farmer): ?>

                                <?php

                                $farmerId = (int) $farmer['id'];
                                $status = $farmer['approval_status'];

                                ?>

                                <tr>

                                    <td class="admin-table-id">
                                        <?= $farmerId ?>
                                    </td>

                                    <td>

                                        <div class="admin-farmer-name">

                                            <div class="admin-farmer-avatar">
                                                <?= strtoupper(
                                                    substr(
                                                        $farmer['stall_name'],
                                                        0,
                                                        1
                                                    )
                                                ) ?>
                                            </div>

                                            <div>

                                                <strong>
                                                    <?= e($farmer['stall_name']) ?>
                                                </strong>

                                                <span>
                                                    <?= e(
                                                        $farmer['contact_person']
                                                        ?? 'No contact name'
                                                    ) ?>
                                                </span>

                                            </div>

                                        </div>

                                    </td>

                                    <td class="admin-table-contact">
                                        <?= e(
                                            $farmer['contact_person']
                                            ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td class="admin-table-email">
                                        <?= e(
                                            $farmer['email']
                                            ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td class="admin-table-address">
                                        <?= e(
                                            $farmer['address']
                                            ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="admin-status admin-status-<?= e($status) ?>"
                                        >
                                            <?= e(ucfirst($status)) ?>
                                        </span>

                                    </td>

                                    <td class="admin-table-date">

                                        <?= !empty($farmer['created_at'])
                                            ? date(
                                                'M j, Y',
                                                strtotime($farmer['created_at'])
                                            )
                                            : 'N/A'
                                        ?>

                                    </td>

                                    <td>

                                        <div class="admin-farmer-actions">

                                            <a
                                                href="farmer_details.php?id=<?= $farmerId ?>"
                                                class="admin-action-view"
                                            >
                                                View
                                            </a>

                                            <?php if ($status === 'pending'): ?>

                                                <form
                                                    method="POST"
                                                    onsubmit="return confirm('Approve this farmer?');"
                                                >

                                                    <?= csrf_field() ?>

                                                    <input
                                                        type="hidden"
                                                        name="farmer_id"
                                                        value="<?= $farmerId ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="approve"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="admin-action-approve"
                                                    >
                                                        Approve
                                                    </button>

                                                </form>

                                                <form
                                                    method="POST"
                                                    onsubmit="return confirm('Reject this farmer?');"
                                                >

                                                    <?= csrf_field() ?>

                                                    <input
                                                        type="hidden"
                                                        name="farmer_id"
                                                        value="<?= $farmerId ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="reject"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="admin-action-reject"
                                                    >
                                                        Reject
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="admin-table-empty"
                                >
                                    <span>✦</span>

                                    <strong>
                                        No farmers found.
                                    </strong>

                                    <p>
                                        Farmer registrations will appear here.
                                    </p>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>
</html>