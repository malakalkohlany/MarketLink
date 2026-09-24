<?php
require_once 'login_process.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body class="auth-page">

    <div class="auth-container">

        <div class="auth-form-section">

            <div class="auth-header">
                <h1>Welcome back.</h1>
                <p>Sign in to your MarketLink account.</p>
            </div>

            <?php if ($error): ?>
                <div class="message message-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form
                action="login.php"
                method="POST"
                class="auth-form"
            >

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="auth-button">
                    Sign In
                </button>

            </form>

            <p class="auth-footer">
                Don't have an account?
                <a href="register.php">Create one</a>
            </p>

        </div>

        <div class="auth-visual">

            <div class="visual-content">
                <span class="visual-label">MARKETLINK</span>

                <h2>
                    Local markets.<br>
                    Better connections.
                </h2>

                <p>
                    Connect with local farmers and discover
                    products directly from your community.
                </p>
            </div>

        </div>

    </div>

    <script src="../assets/js/login.js"></script>

</body>
</html>