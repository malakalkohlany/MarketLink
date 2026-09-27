<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$success = '';


// ==================================================
// Handle Farmer Approval / Rejection
// ==================================================

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

        // Determine new approval status
        $newStatus = $action === 'approve'
            ? 'approved'
            : 'rejected';

        // --------------------------------------------------
        // Get farmer + user information
        // --------------------------------------------------

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

            $errors[] = 'Failed to prepare farmer lookup.';

        } else {

            $stmt->bind_param("i", $farmerId);
            $stmt->execute();

            $result = $stmt->get_result();
            $farmer = $result->fetch_assoc();

            $stmt->close();


            if (!$farmer) {

                $errors[] = 'Farmer not found.';

            } elseif ($farmer['approval_status'] !== 'pending') {

                $errors[] = 'This farmer has already been processed.';

            } else {

                // --------------------------------------------------
                // Update approval status
                // --------------------------------------------------

                $updateStmt = $conn->prepare("
                    UPDATE farmers
                    SET approval_status = ?
                    WHERE id = ?
                      AND approval_status = 'pending'
                ");

                if (!$updateStmt) {

                    $errors[] = 'Failed to prepare approval update.';

                } else {

                    $updateStmt->bind_param(
                        "si",
                        $newStatus,
                        $farmerId
                    );

                    if ($updateStmt->execute()) {

                        if ($updateStmt->affected_rows === 1) {

                            // --------------------------------------------------
                            // Notify farmer
                            // --------------------------------------------------

                            if (!empty($farmer['user_id'])) {

                                if ($newStatus === 'approved') {

                                    createNotification(
                                        $conn,
                                        (int) $farmer['user_id'],
                                        'farmer_approved',
                                        'Farmer Account Approved',
                                        "Your farmer account for {$farmer['stall_name']} has been approved. You can now access your farmer dashboard."
                                    );

                                    $success = 'Farmer approved successfully.';

                                } else {

                                    createNotification(
                                        $conn,
                                        (int) $farmer['user_id'],
                                        'farmer_rejected',
                                        'Farmer Account Rejected',
                                        "Your farmer account for {$farmer['stall_name']} has been rejected."
                                    );

                                    $success = 'Farmer rejected successfully.';
                                }

                            } else {

                                $success = $newStatus === 'approved'
                                    ? 'Farmer approved successfully.'
                                    : 'Farmer rejected successfully.';
                            }

                        } else {

                            $errors[] = 'The farmer could not be updated. They may have already been processed.';
                        }

                    } else {

                        $errors[] = 'Failed to update farmer.';
                    }

                    $updateStmt->close();
                }
            }
        }
    }
}}


// ==================================================
// Load Farmers
// ==================================================

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

    <style>

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .action-buttons form {
            margin: 0;
        }

        .btn-approve {
            background: var(--sage);
            color: var(--white);
            border: none;
        }

        .btn-approve:hover {
            background: var(--sage-dark);
            color: var(--white);
        }

        .btn-reject {
            background: var(--terracotta);
            color: var(--white);
            border: none;
        }

        .btn-reject:hover {
            background: var(--terracotta-dark);
            color: var(--white);
        }

        .status-pending {
            background: var(--marigold-soft);
            color: var(--marigold-dark);
        }

        .status-approved {
            background: var(--sage-light);
            color: var(--sage-dark);
        }

        .status-rejected {
            background: var(--terracotta-soft);
            color: var(--terracotta-dark);
        }

    </style>

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<div class="admin-container">

    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>
                    Farmers
                </h1>

                <p>
                    Manage farmer registrations and approvals.
                </p>

            </div>

        </div>


        <!-- ==============================================
             Success Message
        =============================================== -->

        <?php if (!empty($success)): ?>

            <div class="alert alert-success">

                <p>
                    <?= htmlspecialchars($success) ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- ==============================================
             Error Messages
        =============================================== -->

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= htmlspecialchars($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- ==============================================
             Farmers Table
        =============================================== -->

        <section class="table-section">

            <div class="section-header">

                <h2>
                    Farmers
                </h2>

            </div>


            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Stall Name</th>

                            <th>Contact Person</th>

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

                                <!-- ID -->
                                <td>
                                    <?= $farmerId ?>
                                </td>


                                <!-- Stall -->
                                <td>
                                    <?= htmlspecialchars(
                                        $farmer['stall_name']
                                    ) ?>
                                </td>


                                <!-- Contact -->
                                <td>
                                    <?= htmlspecialchars(
                                        $farmer['contact_person'] ?? 'N/A'
                                    ) ?>
                                </td>


                                <!-- Email -->
                                <td>
                                    <?= htmlspecialchars(
                                        $farmer['email'] ?? 'N/A'
                                    ) ?>
                                </td>


                                <!-- Address -->
                                <td>
                                    <?= htmlspecialchars(
                                        $farmer['address'] ?? 'N/A'
                                    ) ?>
                                </td>


                                <!-- Status -->
                                <td>

                                    <span
                                        class="status status-<?= htmlspecialchars($status) ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars($status)
                                        ) ?>

                                    </span>

                                </td>


                                <!-- Joined -->
                                <td>

                                    <?= !empty($farmer['created_at'])
                                        ? date(
                                            'Y-m-d',
                                            strtotime($farmer['created_at'])
                                        )
                                        : 'N/A'
                                    ?>

                                </td>


                                <!-- Actions -->
                                <td>

                                    <div class="action-buttons">

                                        <!-- View -->
                                        <a
                                            href="farmer_details.php?id=<?= $farmerId ?>"
                                            class="btn btn-sm btn-secondary"
                                        >
                                            View
                                        </a>


                                        <?php if ($status === 'pending'): ?>

                                            <!-- Approve -->
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
                                                    class="btn btn-sm btn-approve"
                                                >
                                                    Approve
                                                </button>

                                            </form>


                                            <!-- Reject -->
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
                                                    class="btn btn-sm btn-reject"
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

                            <td colspan="8">

                                No farmers found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>

</html>