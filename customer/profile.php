<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$user_id = getUserId();

$sql = "
    SELECT
        users.name,
        users.email,
        users.phone,
        users.address,
        users.role,
        users.status,
        users.created_at
    FROM users
    WHERE users.id = ?
      AND users.role = 'customer'
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Failed to prepare customer profile query.');
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();

$stmt->close();

if (!$customer) {
    die('Customer profile not found.');
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

    <title>Customer Profile | MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/components.css"
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
        href="../assets/css/customer.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/profile.css"
    >

</head>

<body>

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content customer-profile-page">

        <section class="customer-page-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                    CUSTOMER / PROFILE
                </span>

                <h1>
                    Your customer <em>profile.</em>
                </h1>

                <p>
                    Manage your personal details and MarketLink account information from one place.
                </p>

            </div>

            <div class="customer-page-hero-mark">
                09
            </div>

        </section>

        <section class="customer-profile-section">

            <div class="customer-profile-heading">

                <div>

                    <span class="customer-section-number">
                        01 / PROFILE
                    </span>

                    <h2>
                        Your MarketLink <em>identity.</em>
                    </h2>

                </div>

                <a
                    href="edit_profile.php"
                    class="customer-profile-edit"
                >
                    <i data-lucide="pen"></i>
                    Edit profile
                </a>

            </div>

            <div class="customer-profile-layout">

                <div class="customer-profile-identity">

                    <div class="customer-profile-identity-mark">
                        <i data-lucide="user"></i>
                    </div>

                    <div class="customer-profile-identity-content">

                        <span class="customer-profile-label">
                            CUSTOMER
                        </span>

                        <h3>
                            <?= e($customer['name']) ?>
                        </h3>

                        <p>
                            <?= e($customer['email']) ?>
                        </p>

                        <div class="customer-profile-status-row">

                            <span class="customer-profile-status customer-profile-status-active">
                                <?= e(ucfirst($customer['status'])) ?>
                            </span>

                            <span class="customer-profile-status">
                                Customer
                            </span>

                        </div>

                    </div>

                </div>

                <div class="customer-profile-details">

                    <section class="customer-profile-card">

                        <div class="customer-profile-card-heading">

                            <div class="customer-profile-card-icon">

                                <i data-lucide="user"></i>

                            </div>

                            <div>

                                <span class="customer-profile-card-number">
                                    01
                                </span>

                                <h3>
                                    Personal information
                                </h3>

                            </div>

                        </div>

                        <div class="customer-profile-info-grid">

                            <div class="customer-profile-info-item">

                                <span>
                                    Full Name
                                </span>

                                <strong>
                                    <?= e($customer['name']) ?>
                                </strong>

                            </div>

                            <div class="customer-profile-info-item">

                                <span>
                                    Email
                                </span>

                                <strong>
                                    <?= e($customer['email']) ?>
                                </strong>

                            </div>

                            <div class="customer-profile-info-item">

                                <span>
                                    Phone Number
                                </span>

                                <strong>
                                    <?= e($customer['phone'] ?: 'Not provided') ?>
                                </strong>

                            </div>

                            <div class="customer-profile-info-item">

                                <span>
                                    Address
                                </span>

                                <strong>
                                    <?= e($customer['address'] ?: 'Not provided') ?>
                                </strong>

                            </div>

                        </div>

                    </section>

                    <section class="customer-profile-card">

                        <div class="customer-profile-card-heading">

                            <div class="customer-profile-card-icon">

                                <i data-lucide="shopping-bag"></i>

                            </div>

                            <div>

                                <span class="customer-profile-card-number">
                                    02
                                </span>

                                <h3>
                                    Account information
                                </h3>

                            </div>

                        </div>

                        <div class="customer-profile-info-grid">

                            <div class="customer-profile-info-item">

                                <span>
                                    Account Type
                                </span>

                                <strong>
                                    Customer
                                </strong>

                            </div>

                            <div class="customer-profile-info-item">

                                <span>
                                    Account Status
                                </span>

                                <strong>
                                    <?= e(ucfirst($customer['status'])) ?>
                                </strong>

                            </div>

                            <div class="customer-profile-info-item customer-profile-info-full">

                                <span>
                                    Member Since
                                </span>

                                <strong>
                                    <?= e(date('F j, Y', strtotime($customer['created_at']))) ?>
                                </strong>

                            </div>

                        </div>

                    </section>

                </div>

            </div>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

    <script src="../assets/js/lucide.js"></script>

    <script>
        lucide.createIcons();
    </script>

</body>

</html>