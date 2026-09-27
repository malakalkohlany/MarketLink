<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$userId = getUserId();
$message = '';
$error = '';
$section = $_GET['section'] ?? 'profile';

if (isset($_GET['updated'])) {
    $message = "Profile updated successfully.";
}

if (isset($_GET['password_changed'])) {
    $message = "Password changed successfully.";
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_profile'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: profile.php?section=edit');
    exit;
    }
    $section = 'edit';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $error = "Name cannot be empty.";
    } else {
        $stmt = $conn->prepare("
            UPDATE users
            SET name = ?, phone = ?, address = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssi",
            $name,
            $phone,
            $address,
            $userId
        );

        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $stmt->close();
            header("Location: profile.php?updated=1");
            exit;
        } else {
            $error = "Failed to update profile.";
            $stmt->close();
        }
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['change_password'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: profile.php?section=password');
        exit;
    }
    $section = 'password';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        $currentPassword === ''
        || $newPassword === ''
        || $confirmPassword === ''
    ) {
        $error = "Please fill in all password fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("
            SELECT password_hash
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $passwordData = $result->fetch_assoc();

        $stmt->close();

        if (
            !$passwordData ||
            !password_verify(
                $currentPassword,
                $passwordData['password_hash']
            )
        ) {
            $error = "Current password is incorrect.";
        } else {
            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "si",
                $hashedPassword,
                $userId
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: profile.php?password_changed=1");
                exit;
            } else {
                $error = "Failed to change password.";
                $stmt->close();
            }
        }
    }
}

$stmt = $conn->prepare("
    SELECT id, name, email, phone, address
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    die("User not found.");
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
    <title>My Profile - MarketLink</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .profile-container {
            width: 90%;
            max-width: 700px;
            margin: 50px auto;
        }

        .profile-card {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .profile-title {
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
        }

        .profile-icon {
            width: 90px;
            height: 90px;
            background: #eee;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
        }

        .profile-info {
            margin-bottom: 25px;
        }

        .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 25px;
        }

        .profile-button {
            display: inline-block;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
            text-align: center;
        }

        .edit-button {
            background: #3498db;
            color: white;
        }

        .password-button {
            background: #8e44ad;
            color: white;
        }

        .logout-button {
            background: #e74c3c;
            color: white;
        }

        .save-button {
            background: #27ae60;
            color: white;
        }

        .cancel-button {
            background: #7f8c8d;
            color: white;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-group input[readonly] {
            background: #eee;
            cursor: not-allowed;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .form-buttons .profile-button {
            flex: 1;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #3498db;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="profile-container">
            <div class="profile-card">

                <?php if ($message !== ''): ?>
                    <div class="message">
                        <?php echo e($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="error">
                        <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($section === 'profile'): ?>

                    <h1 class="profile-title">
                        My Profile
                    </h1>

                    <div class="profile-icon">
                        👤
                    </div>

                    <div class="profile-info">
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <?php echo e($user['name']); ?>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <?php echo e($user['email']); ?>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <?php
                            echo e(
                                $user['phone'] ?? ''
                            );
                            ?>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <?php
                            echo e(
                                $user['address'] ?? ''
                            );
                            ?>
                        </div>
                    </div>

                    <div class="buttons">
                        <a
                            href="profile.php?section=edit"
                            class="profile-button edit-button"
                        >
                            Edit
                        </a>

                        <a
                            href="profile.php?section=password"
                            class="profile-button password-button"
                        >
                            Change Password
                        </a>

                        <a
                            href="../auth/logout.php"
                            class="profile-button logout-button"
                        >
                            Logout
                        </a>
                    </div>

                <?php elseif ($section === 'edit'): ?>

                    <h1 class="profile-title">
                        Edit Profile
                    </h1>

                    <form method="POST" autocomplete="off">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label>Name</label>
                            <input
                                type="text"
                                name="name"
                                value="<?php echo e($user['name']); ?>"
                                autocomplete="name"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input
                                type="email"
                                value="<?php echo e($user['email']); ?>"
                                readonly
                                autocomplete="off"
                            >
                        </div>

                        <div class="form-group">
                            <label>Phone</label>
                            <input
                                type="text"
                                name="phone"
                                value="<?php echo e($user['phone'] ?? ''); ?>"
                                autocomplete="tel"
                            >
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <input
                                type="text"
                                name="address"
                                value="<?php echo e($user['address'] ?? ''); ?>"
                                autocomplete="street-address"
                            >
                        </div>

                        <div class="form-buttons">
                            <button
                                type="submit"
                                name="update_profile"
                                class="profile-button save-button"
                            >
                                Save Changes
                            </button>

                            <a
                                href="profile.php"
                                class="profile-button cancel-button"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>

                <?php elseif ($section === 'password'): ?>

                    <h1 class="profile-title">
                        Change Password
                    </h1>

                    <form
                        method="POST"
                        autocomplete="off"
                        novalidate
                    >

                    <?= csrf_field() ?>
                    
                        <div class="form-group">
                            <label>
                                Current Password
                            </label>

                            <input
                                type="password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>
                                New Password
                            </label>

                            <input
                                type="password"
                                name="new_password"
                                autocomplete="new-password"
                                minlength="6"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>
                                Confirm New Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                autocomplete="new-password"
                                minlength="6"
                                required
                            >
                        </div>

                        <div class="form-buttons">
                            <button
                                type="submit"
                                name="change_password"
                                class="profile-button save-button"
                            >
                                Change Password
                            </button>

                            <a
                                href="profile.php"
                                class="profile-button cancel-button"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>