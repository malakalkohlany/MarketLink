<?php

require_once __DIR__ . '/../includes/include.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = '';

/*
|--------------------------------------------------------------------------
| Handle Registration
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------
    // Get submitted values
    // --------------------------------------------------

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

        $error = 'Invalid account type.';

    } elseif ($name === '') {

        $error = 'Please enter your name.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif ($phone === '') {

        $error = 'Please enter your phone number.';

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
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $check->bind_param('s', $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = 'That email address is already registered.';

        } else {

            // --------------------------------------------------
            // Create password hash
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

                // --------------------------------------------------
                // Create user
                // --------------------------------------------------

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
                    VALUES (?, ?, ?, ?, ?, ?, 'active')"
                );

                $stmt->bind_param(
                    'ssssss',
                    $name,
                    $email,
                    $password_hash,
                    $phone,
                    $address,
                    $role
                );

                $stmt->execute();

                $user_id = $conn->insert_id;


                // --------------------------------------------------
                // Create farmer record
                // --------------------------------------------------

                if ($role === 'farmer') {

                    $stmt2 = $conn->prepare(
                        "INSERT INTO farmers
                        (
                            user_id,
                            stall_name,
                            approval_status
                        )
                        VALUES (?, ?, 'pending')"
                    );

                    $stmt2->bind_param(
                        'is',
                        $user_id,
                        $stall_name
                    );

                    $stmt2->execute();

                    $farmer_id = $conn->insert_id;
                }


                // --------------------------------------------------
                // Commit transaction
                // --------------------------------------------------

                mysqli_commit($conn);


                // --------------------------------------------------
                // Create session
                // --------------------------------------------------

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;


                // --------------------------------------------------
                // Redirect based on role
                // --------------------------------------------------

                if ($role === 'customer') {

                    header(
                        'Location: ' .
                        BASE_URL .
                        'customer/dashboard.php'
                    );

                    exit;
                }


                if ($role === 'farmer') {

                    $_SESSION['farmer_id'] = $farmer_id;

                    header(
                        'Location: ' .
                        BASE_URL .
                        'farmer/dashboard.php'
                    );

                    exit;
                }

            } catch (Exception $e) {

                mysqli_rollback($conn);

                $error = 'Could not create account. Please try again.';
            }
        }
    }
}
?>