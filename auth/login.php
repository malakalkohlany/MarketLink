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

    <main class="auth-container">

        <section class="auth-card">

            <div class="auth-content">

                <div class="auth-brand">
                    <span class="brand-mark">✦</span>
                    <span>MarketLink</span>
                </div>

                <div class="auth-heading">
                    <span class="eyebrow">Welcome back.</span>
                    <h1>Sign in to your MarketLink account.</h1>
                    <p>Continue to ...</p>
                </div>

                <?php if ($error): ?>
                    <div class="message message-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form
                    action="login.php"
                    method="POST"
                    class="auth-form">

                    <div class="form-field">
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

                    <div class="form-field">
                        <label for="password">Password</label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        Sign In
                    </button>

                </form>

                <p class="auth-footer">
                    Don't have an account?
                    <a href="register.php" class="btn-link">Create one</a>
                </p>

            </div>

            <div class="auth-visual">

                <div class="auth-visual-shape shape-one"></div>

                <div class="auth-visual-shape shape-two"></div>

                <div class="auth-visual-content">

                    <span class="visual-badge">✦ Local connections</span>

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

        </section>

    </main>

    <script src="../assets/js/login.js"></script>

</body>
</html>