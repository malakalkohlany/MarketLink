<?php

require_once __DIR__ . '/../includes/include.php';

requireLogin();
requireRole(R_FARMER);

$userId = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare(
    "SELECT
        f.id AS farmer_id,
        f.approval_status
     FROM farmers f
     WHERE f.user_id = ?
     LIMIT 1"
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$result = $stmt->get_result();

$farmer = $result->fetch_assoc();

if (!$farmer) {

    session_unset();
    session_destroy();

 redirect(BASE_URL . 'auth/login.php');
}

$approvalStatus = $farmer['approval_status'];


if ($approvalStatus === 'approved') {

    $_SESSION['farmer_id'] = $farmer['farmer_id'];
    $_SESSION['approval_status'] = 'approved';

   redirect(BASE_URL . 'farmer/dashboard.php');
}


if ($approvalStatus === 'rejected') {

    $_SESSION['farmer_id'] = $farmer['farmer_id'];
    $_SESSION['approval_status'] = 'rejected';

 redirect(BASE_URL . 'farmer/rejected.php');
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
        Application Pending | MarketLink
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/components.css"
    >

        <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/auth.css"
    >

</head>


<body>

    <main>

        <div class="approval-page">

            <div class="approval-card">

                <h1>
                    Application Under Review
                </h1>


                <p>
                    Hi
                    <?= htmlspecialchars($_SESSION['name'] ?? 'there') ?>,
                </p>


                <p>
                    Your farmer application has been submitted successfully
                    and is currently waiting for admin approval.
                </p>


                <p>
                    You will be able to access your farmer dashboard once
                    your application has been approved.
                </p>


                <div class="approval-actions">

                    <a
                        href="<?= BASE_URL ?>farmer/pending.php"
                        class="btn btn-primary"
                    >
                        Check Application Status
                    </a>

                    <a
                        href="<?= BASE_URL ?>auth/logout.php"
                        class="btn btn-secondary"
                    >
                        Log Out
                    </a>

                </div>

            </div>

        </div>
    </main>
</body>
</html>