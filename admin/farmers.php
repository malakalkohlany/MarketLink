<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
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
    LEFT JOIN users u ON f.user_id = u.id
    ORDER BY f.created_at DESC
");

if ($stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $farmers = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $errors[] = 'Failed to load farmers: ' . $stmt->error;
    }

    $stmt->close();
} else {
    $errors[] = 'Failed to prepare farmer query: ' . $conn->error;
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

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

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
                    Manage all farmers.
                </p>

            </div>

        </div>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= htmlspecialchars($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

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

                                <tr>
                                    <td>
                                        <?= (int) $farmer['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farmer['stall_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farmer['contact_person'] ?? 'N/A') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farmer['email'] ?? 'N/A') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farmer['address'] ?? 'N/A') ?>
                                    </td>

                                    <td>
                                        <span class="status status-<?= htmlspecialchars($farmer['approval_status']) ?>">
                                            <?= ucfirst(htmlspecialchars($farmer['approval_status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= !empty($farmer['created_at'])
                                            ? date('Y-m-d', strtotime($farmer['created_at']))
                                            : 'N/A'
                                        ?>
                                    </td>

                                    <td>
                                        <a
                                            href="farmer_details.php?id=<?= (int) $farmer['id'] ?>"
                                            class="btn btn-sm btn-secondary"
                                        >
                                            View
                                        </a>
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