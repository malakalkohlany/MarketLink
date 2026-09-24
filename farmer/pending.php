<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';

requireLogin();

if ($_SESSION['role'] !== 'farmer') {
    header("Location: " . BASE_URL);
    exit;
}

if ($_SESSION['approval_status'] !== 'pending') {

    if ($_SESSION['approval_status'] === 'approved') {
        header("Location: " . BASE_URL . "farmer/dashboard.php");
        exit;
    }

    if ($_SESSION['approval_status'] === 'rejected') {
        header("Location: " . BASE_URL . "farmer/rejected.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Application Pending | MarketLink</title>

    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/base.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/dashboard.css">
</head>

<body>

<div class="approval-page">

    <div class="approval-card">

        <h1>Application Under Review</h1>

        <p>
            Hi <?= htmlspecialchars($_SESSION['name']) ?>,
        </p>

        <p>
            Your farmer application has been submitted successfully
            and is currently waiting for admin approval.
        </p>

        <p>
            You will be able to access your farmer dashboard once
            your application has been approved.
        </p>

        <a href="<?= BASE_URL ?>auth/logout.php"
           class="btn btn-secondary">
            Log Out
        </a>

    </div>

</div>

</body>
</html>