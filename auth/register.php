<?php
require 'register_process.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - MarketLink</title>

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

                <span class="eyebrow">
                    Join MarketLink
                </span>

                <h1>
                    Create your<br>
                    account.
                </h1>

                <p>
                    Connect with local markets and discover
                    opportunities that work for you.
                </p>

            </div>


            <?php if ($error): ?>

                <div class="message message-error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form
                action="register.php"
                method="POST"
                class="auth-form"
            >

                <div
                    class="signup-section"
                    id="role-selection"
                >

                    <label class="signup-section-label">
                        I am joining as...
                    </label>


                    <div class="role-options">

                        <button
                            type="button"
                            class="role-card"
                            data-role="customer"
                            onclick="selectRole('customer')"
                        >

                            <span class="role-icon">
                                🛒
                            </span>

                            <span class="role-info">

                                <strong>
                                    Customer
                                </strong>

                                <small>
                                    Discover products and shop locally
                                </small>

                            </span>

                            <span class="role-check">
                                ✓
                            </span>

                        </button>



                        <button
                            type="button"
                            class="role-card"
                            data-role="farmer"
                            onclick="selectRole('farmer')"
                        >

                            <span class="role-icon">
                                🌱
                            </span>

                            <span class="role-info">

                                <strong>
                                    Farmer
                                </strong>

                                <small>
                                    Sell your products and reach customers
                                </small>

                            </span>

                            <span class="role-check">
                                ✓
                            </span>

                        </button>

                    </div>


                    <input
                        type="hidden"
                        name="role"
                        id="role"
                        value=""
                    >

                </div>


                <div
                    class="signup-details"
                    id="signup-details"
                    hidden
                >

                    <button
                        type="button"
                        class="back-role"
                        onclick="changeRole()"
                    >
                        ← Change account type
                    </button>


                    <div class="selected-role">

                        <span id="selected-role-icon">
                            🛒
                        </span>

                        <div>

                            <small>
                                Creating an account as
                            </small>

                            <strong id="selected-role-name">
                            </strong>

                        </div>

                    </div>

                    <div
                        class="form-field farmer-only"
                        id="business-name-field"
                        hidden
                    >

                        <label for="business_name">
                            Business / Stall Name
                        </label>

                        <input
                            type="text"
                            name="business_name"
                            id="business_name"
                            placeholder="Enter your business or stall name"
                            autocomplete="organization"
                        >

                    </div>


                    <div class="form-field">

                        <label
                            for="name"
                            id="name-label"
                        >
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            required
                        >

                    </div>


                    <div class="form-field">

                        <label for="email">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            placeholder="you@example.com"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <div class="form-field">

                        <label for="phone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            id="phone"
                            placeholder="Enter your phone number"
                            autocomplete="tel"
                            required
                        >

                    </div>


                    <div class="form-field">

                        <label for="address">
                            Address
                        </label>

                        <input
                            type="text"
                            name="address"
                            id="address"
                            placeholder="Enter your address"
                            autocomplete="street-address"
                            required
                        >

                    </div>


                    <div class="form-field">

                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="At least 8 characters"
                            autocomplete="new-password"
                            required
                        >

                    </div>


                    <div class="form-field">

                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            placeholder="Re-enter your password"
                            autocomplete="new-password"
                            required
                        >

                    </div>



                    <button
                        type="submit"
                        class="btn btn-primary auth-submit"
                    >
                        Create Account
                    </button>

                </div>

            </form>



            <p class="auth-footer">

                Already have an account?

                <a
                    href="login.php"
                    class="btn-link"
                >
                    Sign in
                </a>

            </p>

        </div>


        <div class="auth-visual">

            <div class="auth-visual-shape shape-one"></div>

            <div class="auth-visual-shape shape-two"></div>


            <div class="auth-visual-content">

                <span class="visual-badge">
                    ✦ Local connections
                </span>

                <h2>
                    Where local<br>
                    markets meet<br>
                    opportunity.
                </h2>

                <p>
                    Whether you're looking for fresh products
                    or growing your business, MarketLink brings
                    people together.
                </p>

            </div>

        </div>

    </section>

</main>

<script src="../assets/js/register.js"></script>

</body>
</html>