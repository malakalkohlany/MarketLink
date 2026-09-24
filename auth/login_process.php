<?php

session_start();

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {

        // Find user
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

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $error = 'Your account is not currently active.';
        } else {

            // Login successful
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Farmer-specific information
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
                        header('Location: ../farmer/dashboard.php');
                        exit;
                    }

                    if ($farmer['approval_status'] === 'pending') {
                        header('Location: ../farmer/pending.php');
                        exit;
                    }

                    if ($farmer['approval_status'] === 'rejected') {
                        header('Location: ../farmer/rejected.php');
                        exit;
                    }
                }

            } elseif ($user['role'] === 'customer') {

                header('Location: ../customer/dashboard.php');
                exit;

            } elseif ($user['role'] === 'admin') {

                header('Location: ../admin/dashboard.php');
                exit;
            }
        }

        $stmt->close();
    }
}
?>