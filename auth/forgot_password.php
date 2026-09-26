<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate email
    if ($email === '') {

        $error = 'Please enter your email address.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif ($new_password === '') {

        $error = 'Please enter a new password.';

    } elseif (strlen($new_password) < 6) {

        $error = 'Password must be at least 6 characters.';

    } elseif ($confirm_password === '') {

        $error = 'Please confirm your new password.';

    } elseif ($new_password !== $confirm_password) {

        $error = 'Passwords do not match.';

    } else {

        // Find the user by email
        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        if (!$user) {

            $error = 'No account was found with this email address.';

        } else {

            // Hash the new password
            $password_hash = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

            // Update the existing user's password
            $update_stmt = $conn->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE id = ?
            ");

            $update_stmt->bind_param(
                "si",
                $password_hash,
                $user['id']
            );

            if ($update_stmt->execute()) {

                $success = 'Your password has been reset successfully. You can now sign in with your new password.';

                // Clear the form values after successful reset
                $_POST = [];

            } else {

                $error = 'Something went wrong. Please try again.';
            }

            $update_stmt->close();
        }
    }
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

    <title>Reset Password | MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/auth.css"
    >

</head>

<body class="auth-page">

    <main class="auth-container">

        <section class="auth-card">

            <div class="auth-content">

                <div class="auth-brand">
                    <span class="brand-mark">✦</span>
                    <span>MarketLink</span>
                </div>

                <div class="auth-heading">

                    <span class="eyebrow">
                        Account recovery.
                    </span>

                    <h1>
                        Reset your MarketLink password.
                    </h1>

                    <p>
                        Enter your email address and choose a new password for your account.
                    </p>

                </div>

                <?php if ($error): ?>

                    <div class="message message-error">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>

                <?php if ($success): ?>

                    <div class="message message-success">
                        <?= htmlspecialchars($success) ?>
                    </div>

                <?php endif; ?>

                <form
                    action="forget_password.php"
                    method="POST"
                    class="auth-form"
                >

                    <div class="form-field">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                        >

                    </div>

                    <div class="form-field">

                        <label for="new_password">
                            New Password
                        </label>

                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            placeholder="Enter your new password"
                            required
                        >

                    </div>

                    <div class="form-field">

                        <label for="confirm_password">
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your new password"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary auth-submit"
                    >
                        Reset Password
                    </button>

                </form>

                <p class="auth-footer">

                    Remember your password?

                    <a
                        href="login.php"
                        class="btn-link"
                    >
                        Sign In
                    </a>

                </p>

            </div>

            <div class="auth-visual">

                <div class="auth-visual-shape shape-one"></div>

                <div class="auth-visual-shape shape-two"></div>

                <div class="auth-visual-content">

                    <span class="visual-badge">
                        ✦ Secure account
                    </span>

                    <h2>
                        Fresh start.<br>
                        Same account.
                    </h2>

                    <p>
                        Update your password and continue connecting with local farmers and markets.
                    </p>

                </div>

            </div>

        </section>

    </main>

</body>

</html>