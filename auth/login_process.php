<?php

require_once __DIR__ . '/../includes/include.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ===============================
    // CSRF CHECK
    // ===============================

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $error = 'Invalid CSRF token.';

    } else {

        // ===============================
        // GET FORM DATA
        // ===============================

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // ===============================
        // VALIDATION
        // ===============================

        if ($email === '' || $password === '') {

            $error = 'Please enter your email and password.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = 'Please enter a valid email address.';

        } else {

            // ===============================
            // FIND USER
            // ===============================

            $stmt = $conn->prepare("
                SELECT id, name, email, password_hash, role, status
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // ===============================
            // CHECK LOGIN
            // ===============================

            if (
                !$user ||
                !password_verify($password, $user['password_hash'])
            ) {

                $error = 'Invalid email or password.';

            } elseif ($user['status'] !== 'active') {

                $error = 'Your account is not currently active.';

            } else {

                // ===============================
                // LOGIN SUCCESSFUL
                // ===============================

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // ===============================
                // FARMER
                // ===============================

                if ($user['role'] === 'farmer') {

                    $farmerStmt = $conn->prepare("
                        SELECT id, stall_name, approval_status
                        FROM farmers
                        WHERE user_id = ?
                        LIMIT 1
                    ");

                    $farmerStmt->bind_param("i", $user['id']);
                    $farmerStmt->execute();

                    $farmerResult = $farmerStmt->get_result();
                    $farmer = $farmerResult->fetch_assoc();

                    if (!$farmer) {

                        session_unset();
                        session_destroy();

                        $error = 'Farmer profile not found.';

                    } else {

                        $_SESSION['farmer_id'] = $farmer['id'];
                        $_SESSION['stall_name'] = $farmer['stall_name'];
                        $_SESSION['approval_status'] = $farmer['approval_status'];

                        if ($farmer['approval_status'] === 'approved') {

                            redirect(
                                BASE_URL . 'farmer/dashboard.php'
                            );

                        } elseif ($farmer['approval_status'] === 'pending') {

                            redirect(
                                BASE_URL . 'farmer/pending.php'
                            );

                        } elseif ($farmer['approval_status'] === 'rejected') {

                            redirect(
                                BASE_URL . 'farmer/rejected.php'
                            );

                        } else {

                            $error = 'Invalid farmer approval status.';
                        }
                    }

                    $farmerStmt->close();

                // ===============================
                // CUSTOMER
                // ===============================

                } elseif ($user['role'] === 'customer') {

                    redirect(
                        BASE_URL . 'customer/dashboard.php'
                    );

                // ===============================
                // ADMIN
                // ===============================

                } elseif ($user['role'] === 'admin') {

                    redirect(
                        BASE_URL . 'admin/dashboard.php'
                    );

                // ===============================
                // UNKNOWN ROLE
                // ===============================

                } else {

                    session_unset();
                    session_destroy();

                    $error = 'Invalid account role.';
                }
            }

            $stmt->close();
        }
    }
}
?>