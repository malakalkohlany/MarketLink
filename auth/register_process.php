<?php

require_once __DIR__ . '/../includes/include.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $error = 'Invalid CSRF token.';

    } else {

        // --------------------------------------------------
        // Get form values

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $role = $_POST['role'] ?? '';
    $stall_name = trim($_POST['stall_name'] ?? '');

    $allowed_roles = ['customer', 'farmer'];


    // --------------------------------------------------
    // Validation
    // --------------------------------------------------

    if (!in_array($role, $allowed_roles, true)) {

        $error = 'Please select an account type.';

    } elseif ($name === '') {

        $error = 'Please enter your name.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif ($phone === '') {

    $error = 'Please enter your phone number.';

    } elseif (!preg_match('/^\+?[0-9][0-9\s\-()]{7,19}$/', $phone)) {

        $error = 'Please enter a valid phone number.';
        
    } elseif ($address === '') {

        $error = 'Please enter your address.';

    } elseif (strlen($password) < 8) {

        $error = 'Password must be at least 8 characters.';

    } elseif ($password !== $confirm_password) {

        $error = 'Passwords do not match.';

    } elseif ($role === 'farmer' && $stall_name === '') {

        $error = 'Please enter your business or stall name.';

    } else {

        // --------------------------------------------------
        // Check whether email already exists
        // --------------------------------------------------

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $check->bind_param('s', $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = 'That email address is already registered.';

        } else {

            // --------------------------------------------------
            // Hash password
            // --------------------------------------------------

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // --------------------------------------------------
            // Start transaction
            // --------------------------------------------------

            mysqli_begin_transaction($conn);

            try {

                // ==================================================
                // CUSTOMER
                // ==================================================

                if ($role === 'customer') {

                    $status = 'active';

                    $stmt = $conn->prepare(
                        "INSERT INTO users
                        (
                            name,
                            email,
                            password_hash,
                            phone,
                            address,
                            role,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        'sssssss',
                        $name,
                        $email,
                        $password_hash,
                        $phone,
                        $address,
                        $role,
                        $status
                    );

                    $stmt->execute();

                    $user_id = $conn->insert_id;


                // ==================================================
                // FARMER
                // ==================================================

                } else {
                    
                    $status = 'active';

                    $stmt = $conn->prepare(
                        "INSERT INTO users
                        (
                            name,
                            email,
                            password_hash,
                            phone,
                            address,
                            role,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        'sssssss',
                        $name,
                        $email,
                        $password_hash,
                        $phone,
                        $address,
                        $role,
                        $status
                    );

                    $stmt->execute();

                    $user_id = $conn->insert_id;


                    // --------------------------------------------------
                    // Create farmer record
                    // --------------------------------------------------

                    $farmer_status = 'pending';

                    $stmt2 = $conn->prepare(
                        "INSERT INTO farmers
                        (
                            user_id,
                            stall_name,
                            contact_person,
                            approval_status
                        )
                        VALUES (?, ?, ?, ?)"
                    );

                    $stmt2->bind_param(
                        'isss',
                        $user_id,
                        $stall_name,
                        $name,
                        $farmer_status
                    );

                    $stmt2->execute();

                    $farmer_id = $conn->insert_id;
                }


                // --------------------------------------------------
                // Commit
                // --------------------------------------------------

                mysqli_commit($conn);


                // --------------------------------------------------
                // Create session
                // --------------------------------------------------

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;


                // ==================================================
                // CUSTOMER → DASHBOARD
                // ==================================================

                if ($role === 'customer') {
    redirect('customer/dashboard.php');
}


                // ==================================================
                // FARMER → PENDING PAGE
                // ==================================================

                if ($role === 'farmer') {
    $_SESSION['farmer_id'] = $farmer_id;

    redirect('farmer/pending.php');
}

            } catch (Exception $e) {

                mysqli_rollback($conn);

                $error = 'Could not create account. Please try again.';
            }
        }
    }
}
}