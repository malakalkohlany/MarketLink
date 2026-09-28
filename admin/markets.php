<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$markets = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $marketId = filter_input(
            INPUT_POST,
            'market_id',
            FILTER_VALIDATE_INT
        );

        $newStatus = $_POST['status'] ?? '';

        if (
            $marketId &&
            in_array($newStatus, ['active', 'inactive'], true)
        ) {
            $stmt = $conn->prepare("
                UPDATE markets
                SET status = ?
                WHERE id = ?
            ");

            if ($stmt) {
                $stmt->bind_param(
                    'si',
                    $newStatus,
                    $marketId
                );
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    redirect('admin/markets.php');
}

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        address,
        opening_time,
        closing_time,
        status,
        created_at
    FROM markets
    ORDER BY created_at DESC
");

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $markets = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $errors[] = 'Failed to load markets.';
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
    <title>Markets | MarketLink</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/admin_ann.css">
</head>

<body>

<div class="admin-container">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content admin-markets-page">

        <section class="customer-page-hero admin-customers-hero">
            <div class="customer-page-hero-copy">
                <span class="eyebrow">ADMIN / MARKETS</span>

                <h1>
                    Manage local <em>markets.</em>
                </h1>

                <p>
                    Create, update, and manage the markets available
                    throughout MarketLink.
                </p>
            </div>

            <div class="customer-page-hero-mark">07</div>
        </section>

        <?php if (!empty($errors)): ?>
            <div class="admin-page-alert alert-danger">
                <span class="admin-alert-mark">!</span>

                <div>
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <section class="admin-management-section">

            <div class="admin-section-heading">
                <div>
                    <span class="eyebrow">01 / DIRECTORY</span>

                    <h2>
                        Market <em>directory.</em>
                    </h2>

                    <p>
                        View operating hours, locations, and market status.
                    </p>
                </div>

                <div class="admin-record-count">
                    <?= count($markets) ?>
                    <span>markets</span>
                </div>
            </div>

            <div class="admin-markets-table">

                <table>

                    <thead>
                        <tr>
                            <th class="admin-market-id">ID</th>
                            <th class="admin-market-name">Market</th>
                            <th class="admin-market-address">Address</th>
                            <th class="admin-market-hours">Hours</th>
                            <th class="admin-market-status">Status</th>
                            <th class="admin-market-created">Created</th>
                            <th class="admin-market-actions">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($markets)): ?>

                            <?php foreach ($markets as $market): ?>

                                <?php
                                $status = $market['status'] ?? '';
                                $isActive = $status === 'active';
                                ?>

                                <tr>

                                    <td class="admin-market-id">
                                        <span class="admin-table-id">
                                            #<?= (int) $market['id'] ?>
                                        </span>
                                    </td>

                                    <td class="admin-market-name">
                                        <div class="admin-market-name-wrap">
                                            <span class="admin-market-icon">✦</span>

                                            <div>
                                                <strong>
                                                    <?= htmlspecialchars($market['name']) ?>
                                                </strong>

                                                <span>
                                                    Local marketplace
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="admin-market-address">
                                        <?= htmlspecialchars(
                                            $market['address'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td class="admin-market-hours">
                                        <?php if (
                                            !empty($market['opening_time']) ||
                                            !empty($market['closing_time'])
                                        ): ?>

                                            <span class="admin-hours-main">
                                                <?= htmlspecialchars(
                                                    $market['opening_time'] ?? 'N/A'
                                                ) ?>
                                                —
                                                <?= htmlspecialchars(
                                                    $market['closing_time'] ?? 'N/A'
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="admin-table-muted">
                                                Hours unavailable
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <td class="admin-market-status">

                                        <span
                                            class="admin-status <?= $isActive
                                                ? 'admin-status-active'
                                                : 'admin-status-inactive' ?>"
                                        >
                                            <span class="admin-status-dot"></span>

                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $status ?: 'N/A'
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                    <td class="admin-market-created">

                                        <?php if (!empty($market['created_at'])): ?>

                                            <span class="admin-table-date">
                                                <?= date(
                                                    'M d, Y',
                                                    strtotime($market['created_at'])
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="admin-table-muted">
                                                N/A
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td class="admin-market-actions">

                                        <div class="admin-market-action-group">

                                            <a
                                                href="edit_market.php?id=<?= (int) $market['id'] ?>"
                                                class="admin-action-view"
                                            >
                                                Edit
                                            </a>

                                            <form method="POST">

                                                <?= csrf_field() ?>

                                                <input
                                                    type="hidden"
                                                    name="market_id"
                                                    value="<?= (int) $market['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="<?= $isActive
                                                        ? 'inactive'
                                                        : 'active' ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="<?= $isActive
                                                        ? 'admin-action-reject'
                                                        : 'admin-action-approve' ?>"
                                                >
                                                    <?= $isActive
                                                        ? 'Deactivate'
                                                        : 'Activate' ?>
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7">

                                    <div class="admin-table-empty">
                                        <span class="admin-empty-mark">✦</span>

                                        <strong>No markets found.</strong>

                                        <p>
                                            There are currently no markets
                                            available in the directory.
                                        </p>

                                        <a
                                            href="add_market.php"
                                            class="admin-action-view"
                                        >
                                            Add a market
                                        </a>
                                    </div>

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