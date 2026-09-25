<?php

require_once __DIR__ . '/../includes/include.php';

requireLogin();

if ($_SESSION['role'] !== 'farmer') {
    header("Location: " . BASE_URL);
    exit;
}

if ($_SESSION['approval_status'] !== 'rejected') {

    if ($_SESSION['approval_status'] === 'approved') {
        header("Location: " . BASE_URL . "farmer/dashboard.php");
        exit;
    }

    if ($_SESSION['approval_status'] === 'pending') {
        header("Location: " . BASE_URL . "farmer/pending.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Application Not Approved | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="approval-page">

            <div class="approval-card">

                <h1>Application Not Approved</h1>

                <p>
                    Hi <?= htmlspecialchars($_SESSION['name']) ?>,
                </p>

                <p>
                    Unfortunately, your farmer application was not approved.
                </p>

                <p>
                    Please contact MarketLink support if you believe this
                    decision was made in error.
                </p>

                <a href="<?= BASE_URL ?>auth/logout.php"
                class="btn btn-secondary">
                    Log Out
                </a>

            </div>

        </div>
    </main>

</body>
</html>