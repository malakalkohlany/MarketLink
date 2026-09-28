<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$user_id = getUserId();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid security token. Please try again.';

    } else {

        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {

            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if ($name === '') {
                $errors[] = 'Full name is required.';
            }

            if ($email === '') {
                $errors[] = 'Email address is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            if (empty($errors)) {

                $email_stmt = $conn->prepare("
                    SELECT id
                    FROM users
                    WHERE email = ?
                      AND id != ?
                    LIMIT 1
                ");

                if (!$email_stmt) {

                    $errors[] = 'Unable to verify the email address.';

                } else {

                    $email_stmt->bind_param(
                        "si",
                        $email,
                        $user_id
                    );

                    $email_stmt->execute();

                    $email_result = $email_stmt->get_result();

                    if ($email_result->num_rows > 0) {
                        $errors[] = 'That email address is already in use.';
                    }

                    $email_stmt->close();
                }
            }

            if (empty($errors)) {

                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        name = ?,
                        email = ?,
                        phone = ?,
                        address = ?
                    WHERE id = ?
                      AND role = 'customer'
                ");

                if (!$stmt) {

                    $errors[] = 'Unable to update your profile.';

                } else {

                    $stmt->bind_param(
                        "ssssi",
                        $name,
                        $email,
                        $phone,
                        $address,
                        $user_id
                    );

                    if ($stmt->execute()) {

                        $stmt->close();

                        redirect('customer/profile.php?updated=1');

                    } else {

                        $errors[] = 'Unable to update your profile.';

                        $stmt->close();
                    }
                }
            }
        }

        if ($action === 'change_password') {

            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($current_password === '') {
                $errors[] = 'Current password is required.';
            }

            if ($new_password === '') {
                $errors[] = 'New password is required.';
            } elseif (strlen($new_password) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            }

            if ($confirm_password === '') {
                $errors[] = 'Please confirm your new password.';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match.';
            }

            if (empty($errors)) {

                $stmt = $conn->prepare("
                    SELECT password_hash
                    FROM users
                    WHERE id = ?
                      AND role = 'customer'
                    LIMIT 1
                ");

                if (!$stmt) {

                    $errors[] = 'Unable to verify your current password.';

                } else {

                    $stmt->bind_param(
                        "i",
                        $user_id
                    );

                    $stmt->execute();

                    $result = $stmt->get_result();

                    $user = $result->fetch_assoc();

                    $stmt->close();

                    if (
                        !$user ||
                        !password_verify(
                            $current_password,
                            $user['password_hash']
                        )
                    ) {
                        $errors[] = 'Current password is incorrect.';
                    }
                }
            }

            if (empty($errors)) {

                $password_hash = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare("
                    UPDATE users
                    SET password_hash = ?
                    WHERE id = ?
                      AND role = 'customer'
                ");

                if (!$stmt) {

                    $errors[] = 'Unable to change your password.';

                } else {

                    $stmt->bind_param(
                        "si",
                        $password_hash,
                        $user_id
                    );

                    if ($stmt->execute()) {

                        $stmt->close();

                        redirect('customer/profile.php?password_changed=1');

                    } else {

                        $errors[] = 'Unable to change your password.';

                        $stmt->close();
                    }
                }
            }
        }
    }
}

$sql = "
    SELECT
        name,
        email,
        phone,
        address
    FROM users
    WHERE id = ?
      AND role = 'customer'
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Failed to prepare customer profile query.');
}

$stmt->bind_param(
    "i",
    $user_id
);

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

    <title>Edit Profile | MarketLink</title>

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

    <main class="main-content customer-edit-profile-page">

        <section class="customer-page-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                    CUSTOMER / PROFILE
                </span>

                <h1>
                    Edit your <em>profile.</em>
                </h1>

                <p>
                    Keep your personal details and MarketLink account information up to date.
                </p>

            </div>

            <div class="customer-page-hero-mark">
                10
            </div>

        </section>

        <section class="customer-edit-profile-section">

            <div class="customer-edit-profile-heading">

                <div>

                    <span class="customer-section-number">
                        01 / EDIT PROFILE
                    </span>

                    <h2>
                        Keep things <em>current.</em>
                    </h2>

                </div>

            </div>

            <?php if (!empty($errors)): ?>

                <div class="customer-profile-alert customer-profile-alert-error">

                    <?php foreach ($errors as $error): ?>

                        <p>
                            <?= e($error) ?>
                        </p>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <form
                class="customer-edit-profile-form"
                action="edit_profile.php"
                method="post"
            >

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action"
                    value="update_profile"
                >

                <section class="customer-edit-profile-card">

                    <div class="customer-edit-profile-card-heading">

                        <div class="customer-edit-profile-card-icon">

                            <i data-lucide="user"></i>

                        </div>

                        <div>

                            <span class="customer-edit-profile-card-number">
                                01
                            </span>

                            <h3>
                                Personal information
                            </h3>

                        </div>

                    </div>

                    <div class="customer-edit-profile-grid">

                        <div class="customer-edit-profile-field">

                            <label for="name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?= e($customer['name'] ?? '') ?>"
                                required
                            >

                        </div>

                        <div class="customer-edit-profile-field">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= e($customer['email'] ?? '') ?>"
                                required
                            >

                        </div>

                        <div class="customer-edit-profile-field">

                            <label for="phone">
                                Phone Number
                            </label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="<?= e($customer['phone'] ?? '') ?>"
                            >

                        </div>

                        <div class="customer-edit-profile-field">

                            <label for="address">
                                Address
                            </label>

                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?= e($customer['address'] ?? '') ?>"
                            >

                        </div>

                    </div>

                </section>

                <div class="customer-edit-profile-actions">

                    <a
                        href="profile.php"
                        class="customer-edit-profile-cancel"
                    >
                        <i data-lucide="x"></i>
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="customer-edit-profile-save"
                    >
                        <i data-lucide="save"></i>
                        Save Changes
                    </button>

                </div>

            </form>

            <form
                class="customer-edit-profile-form customer-password-form"
                action="edit_profile.php"
                method="post"
            >

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action"
                    value="change_password"
                >

                <section class="customer-edit-profile-card">

                    <div class="customer-edit-profile-card-heading">

                        <div class="customer-edit-profile-card-icon">

                            <i data-lucide="shield-check"></i>

                        </div>

                        <div>

                            <span class="customer-edit-profile-card-number">
                                02
                            </span>

                            <h3>
                                Account security
                            </h3>

                        </div>

                    </div>

                    <div class="customer-edit-profile-grid customer-password-grid">

                        <div class="customer-edit-profile-field customer-edit-profile-field-full">

                            <label for="current_password">
                                Current Password
                            </label>

                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >

                        </div>

                        <div class="customer-edit-profile-field">

                            <label for="new_password">
                                New Password
                            </label>

                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                        <div class="customer-edit-profile-field">

                            <label for="confirm_password">
                                Confirm New Password
                            </label>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                    </div>

                </section>

                <div class="customer-edit-profile-actions">

                    <a
                        href="profile.php"
                        class="customer-edit-profile-cancel"
                    >
                        <i data-lucide="x"></i>
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="customer-edit-profile-save"
                    >
                        <i data-lucide="save"></i>
                        Change Password
                    </button>

                </div>

            </form>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

    <script src="../assets/js/lucide.js"></script>

    <script>
        lucide.createIcons();
    </script>

</body>

</html>