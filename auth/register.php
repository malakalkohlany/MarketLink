<?php

require_once __DIR__ . '/register_process.php';

$selectedRole = $role ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account - MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>


<body class="auth-page">
    <a href="../index.php" class="auth-home-link">
        <i class="fa-solid fa-arrow-left"></i>
        <span>MarketLink</span>
    </a>

<main class="auth-container">

<section class="auth-card">


    <!-- ==================================================
         FORM SIDE
    =================================================== -->

    <div class="auth-content">


        <!-- ==================================================
             BRAND
        =================================================== -->

        <div class="auth-brand">

            <span class="brand-mark">
                ✦
            </span>

            <span>
                MarketLink
            </span>

        </div>


        <!-- ==================================================
             HEADING
        =================================================== -->

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


        <!-- ==================================================
             ERROR MESSAGE
        =================================================== -->

        <?php if ($error): ?>

            <div class="message message-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- ==================================================
             REGISTRATION FORM
        =================================================== -->

        <form
            action="register.php"
            method="POST"
            class="auth-form"
        >

        <?= csrf_field() ?>

            <!-- ==================================================
                 ROLE SELECTION
            =================================================== -->

            <div
                class="signup-section"
                id="role-selection"
                <?= $selectedRole !== '' ? 'hidden' : '' ?>
            >

                <label class="signup-section-label">
                    I am joining as...
                </label>


                <div class="role-options">


                    <!-- CUSTOMER -->

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


                    <!-- FARMER -->

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


                <!-- Selected role sent to PHP -->

                <input
                    type="hidden"
                    name="role"
                    id="role"
                    value="<?= htmlspecialchars($selectedRole) ?>"
                >

            </div>


            <!-- ==================================================
                 REGISTRATION DETAILS
            =================================================== -->

            <div
                class="signup-details"
                id="signup-details"
                <?= $selectedRole === '' ? 'hidden' : '' ?>
            >


                <!-- ==================================================
                     CHANGE ROLE
                =================================================== -->

                <button
                    type="button"
                    class="back-role"
                    onclick="changeRole()"
                >
                    ← Change account type
                </button>


                <!-- ==================================================
                     SELECTED ROLE DISPLAY
                =================================================== -->

                <div class="selected-role">

                    <span id="selected-role-icon">

                        <?= $selectedRole === 'farmer'
                            ? '🌱'
                            : '🛒'
                        ?>

                    </span>

                    <div>

                        <small>
                            Creating an account as
                        </small>

                        <strong id="selected-role-name">

                            <?= $selectedRole === 'farmer'
                                ? 'Farmer'
                                : (
                                    $selectedRole === 'customer'
                                        ? 'Customer'
                                        : ''
                                )
                            ?>

                        </strong>

                    </div>

                </div>


                <!-- ==================================================
                     FARMER ONLY
                =================================================== -->

                <div
                    class="form-field farmer-only"
                    id="stall-name-field"
                    <?= $selectedRole !== 'farmer' ? 'hidden' : '' ?>
                >

                    <label for="stall_name">
                        Business / Stall Name
                    </label>

                    <input
                        type="text"
                        name="stall_name"
                        id="stall_name"
                        placeholder="Enter your business or stall name"
                        autocomplete="organization"
                        value="<?= htmlspecialchars($stall_name ?? '') ?>"
                    >

                </div>


                <!-- ==================================================
                     FULL NAME
                =================================================== -->

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
                        value="<?= htmlspecialchars($name ?? '') ?>"
                        required
                    >

                </div>


                <!-- ==================================================
                     EMAIL
                =================================================== -->

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
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        required
                    >

                </div>


                <!-- ==================================================
                     PHONE
                =================================================== -->

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
                        value="<?= htmlspecialchars($phone ?? '') ?>"
                        required
                    >

                </div>


                <!-- ==================================================
                     ADDRESS
                =================================================== -->

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
                        value="<?= htmlspecialchars($address ?? '') ?>"
                        required
                    >

                </div>


                <!-- ==================================================
                     PASSWORD
                =================================================== -->

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


                <!-- ==================================================
                     CONFIRM PASSWORD
                =================================================== -->

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


                <!-- ==================================================
                     SUBMIT
                =================================================== -->

                <button
                    type="submit"
                    class="btn btn-primary auth-submit"
                >
                    Create Account
                </button>

            </div>

        </form>


        <!-- ==================================================
             LOGIN LINK
        =================================================== -->

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


    <!-- ==================================================
         VISUAL SIDE
    =================================================== -->

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


<!-- ==================================================
     REGISTER JAVASCRIPT
================================================== -->

<script src="../assets/js/register.js"></script>


<!-- ==================================================
     RESTORE SELECTED ROLE AFTER VALIDATION ERROR
================================================== -->

<?php if ($selectedRole !== ''): ?>

<script>

document.addEventListener('DOMContentLoaded', function () {

    selectRole(
        <?= json_encode($selectedRole) ?>
    );

});

</script>

<?php endif; ?>


</body>

</html>