<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$user_id = getUserId();

$sql = "SELECT
            users.name,
            users.email,
            users.phone,
            users.address,
            users.role,
            users.status,
            users.created_at,
            farmers.stall_name,
            farmers.contact_person,
            farmers.description,
            farmers.address AS farmer_address,
            farmers.latitude,
            farmers.longitude,
            farmers.approval_status
        FROM users
        INNER JOIN farmers ON farmers.user_id = users.id
        WHERE users.id = ? AND users.role = 'farmer'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$profile_image = '../assets/images/farmers/farmer_' . $user_id . '.jpg';

if (file_exists(__DIR__ . '/../assets/images/farmers/farmer_' . $user_id . '.jpg')) {
    $profile_image = '../assets/images/farmers/farmer_' . $user_id . '.jpg';
} elseif (file_exists(__DIR__ . '/../assets/images/farmers/farmer_' . $user_id . '.png')) {
    $profile_image = '../assets/images/farmers/farmer_' . $user_id . '.png';
} elseif (file_exists(__DIR__ . '/../assets/images/farmers/farmer_' . $user_id . '.webp')) {
    $profile_image = '../assets/images/farmers/farmer_' . $user_id . '.webp';
} else {
    $profile_image = '../assets/images/farmers/default-farmer.png';
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

    <title>Farmer Profile | MarketLink</title>

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

    <main class="main-content farmer-profile-page">

        <section class="customer-page-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                    FARMER / PROFILE
                </span>

                <h1>
                    Your farmer <em>profile.</em>
                </h1>

                <p>
                    Manage your personal details, farmer information, and MarketLink account from one place.
                </p>

            </div>

            <div class="customer-page-hero-mark">
                08
            </div>

        </section>

        <section class="farmer-profile-section">

            <div class="farmer-profile-heading">

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
                    class="farmer-profile-edit"
                >
                    <i data-lucide="pen"></i>
                    Edit profile
                </a>

            </div>

            <div class="farmer-profile-layout">

                <div class="farmer-profile-identity">

                    <div class="farmer-profile-image">

                        <img
                            src="<?= e($profile_image) ?>"
                            alt="Farmer Profile Image"
                        >

                    </div>

                    <div class="farmer-profile-identity-content">

                        <span class="farmer-profile-label">
                            FARMER
                        </span>

                        <h3>
                            <?= e($farmer['name']) ?>
                        </h3>

                        <p>
                            <?= e($farmer['stall_name']) ?>
                        </p>

                        <div class="farmer-profile-status-row">

                            <span class="farmer-profile-status approved">
                                <?= e($farmer['approval_status']) ?>
                            </span>

                            <span class="farmer-profile-status">
                                <?= e($farmer['status']) ?>
                            </span>

                        </div>

                    </div>

                </div>

                <div class="farmer-profile-details">

                    <section class="farmer-profile-card">

                        <div class="farmer-profile-card-heading">

                            <div class="farmer-profile-card-icon">
                                <i data-lucide="user"></i>
                            </div>

                            <div>

                                <span class="farmer-profile-card-number">
                                    01
                                </span>

                                <h3>
                                    Personal information
                                </h3>

                            </div>

                        </div>

                        <div class="farmer-profile-info-grid">

                            <div class="farmer-profile-info-item">

                                <span>
                                    Full Name
                                </span>

                                <strong>
                                    <?= e($farmer['name']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item">

                                <span>
                                    Email
                                </span>

                                <strong>
                                    <?= e($farmer['email']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item">

                                <span>
                                    Phone Number
                                </span>

                                <strong>
                                    <?= e($farmer['phone']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item">

                                <span>
                                    Address
                                </span>

                                <strong>
                                    <?= e($farmer['address']) ?>
                                </strong>

                            </div>

                        </div>

                    </section>

                    <section class="farmer-profile-card">

                        <div class="farmer-profile-card-heading">

                            <div class="farmer-profile-card-icon">
                                <i data-lucide="store"></i>
                            </div>

                            <div>

                                <span class="farmer-profile-card-number">
                                    02
                                </span>

                                <h3>
                                    Farmer information
                                </h3>

                            </div>

                        </div>

                        <div class="farmer-profile-info-grid">

                            <div class="farmer-profile-info-item">

                                <span>
                                    Business / Stall Name
                                </span>

                                <strong>
                                    <?= e($farmer['stall_name']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item">

                                <span>
                                    Contact Person
                                </span>

                                <strong>
                                    <?= e($farmer['contact_person']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item farmer-profile-info-full">

                                <span>
                                    Description
                                </span>

                                <strong>
                                    <?= e($farmer['description']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item farmer-profile-info-full">

                                <span>
                                    Farmer Address
                                </span>

                                <strong>
                                    <?= e($farmer['farmer_address']) ?>
                                </strong>

                            </div>

                        </div>

                    </section>

                    <section class="farmer-profile-card">

                        <div class="farmer-profile-card-heading">

                            <div class="farmer-profile-card-icon">
                                <i data-lucide="shield-check"></i>
                            </div>

                            <div>

                                <span class="farmer-profile-card-number">
                                    03
                                </span>

                                <h3>
                                    Account information
                                </h3>

                            </div>

                        </div>

                        <div class="farmer-profile-info-grid">

                            <div class="farmer-profile-info-item">

                                <span>
                                    Role
                                </span>

                                <strong>
                                    <?= e($farmer['role']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item">

                                <span>
                                    Account Status
                                </span>

                                <strong>
                                    <?= e($farmer['status']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item">

                                <span>
                                    Approval Status
                                </span>

                                <strong>
                                    <?= e($farmer['approval_status']) ?>
                                </strong>

                            </div>

                            <div class="farmer-profile-info-item">

                                <span>
                                    Created At
                                </span>

                                <strong>
                                    <?= e($farmer['created_at']) ?>
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