<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$customers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid CSRF token.';
    } else {

        $customerId = filter_input(
            INPUT_POST,
            'customer_id',
            FILTER_VALIDATE_INT
        );

        $newStatus = $_POST['status'] ?? '';

        if (
            $customerId &&
            in_array($newStatus, ['active', 'inactive'], true)
        ) {

            $stmt = $conn->prepare("
                UPDATE users
                SET status = ?
                WHERE id = ?
                  AND role = 'customer'
            ");

            if ($stmt) {

                $stmt->bind_param(
                    'si',
                    $newStatus,
                    $customerId
                );

                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header('Location: customers.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email,
        phone,
        address,
        status,
        created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY created_at DESC
");

if ($stmt) {

    if ($stmt->execute()) {

        $result = $stmt->get_result();
        $customers = $result->fetch_all(MYSQLI_ASSOC);

    } else {

        $errors[] = 'Failed to load customers: ' . $stmt->error;
    }

    $stmt->close();

} else {

    $errors[] = 'Failed to prepare customer query: ' . $conn->error;
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

    <title>Customers | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="admin-container">

        <main class="main-content">

            <div class="page-header">

                <div>

                    <h1>
                        Customers
                    </h1>

                    <p>
                        Manage all customers.
                    </p>

                </div>

            </div>


            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">

                    <?php foreach ($errors as $error): ?>

                        <p>
                            <?= htmlspecialchars($error) ?>
                        </p>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <section class="table-section">

                <div class="section-header">

                    <h2>
                        Customers
                    </h2>

                </div>


                <div class="table-responsive">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (!empty($customers)): ?>

                                <?php foreach ($customers as $customer): ?>

                                    <?php
                                        $status = ($customer['status'] ?? '') === 'active'
                                            ? 'active'
                                            : 'inactive';
                                    ?>

                                    <tr>

                                        <td>
                                            <?= (int) $customer['id'] ?>
                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $customer['name'] ?? 'N/A'
                                            ) ?>
                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $customer['email'] ?? 'N/A'
                                            ) ?>
                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $customer['phone'] ?? 'N/A'
                                            ) ?>
                                        </td>


                                        <td>
                                            <?= htmlspecialchars(
                                                $customer['address'] ?? 'N/A'
                                            ) ?>
                                        </td>


                                        <td>

                                            <span class="status status-<?= $status ?>">

                                                <?= ucfirst($status) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= !empty($customer['created_at'])
                                                ? date(
                                                    'Y-m-d',
                                                    strtotime($customer['created_at'])
                                                )
                                                : 'N/A'
                                            ?>

                                        </td>


                                        <td>

                                            <a
                                                href="customer_details.php?id=<?= (int) $customer['id'] ?>"
                                                class="btn btn-sm btn-secondary"
                                            >
                                                View
                                            </a>

                                            <form
                                                method="POST"
                                                style="display: inline;"
                                            >

                                            <?= csrf_field() ?>

                                                <input
                                                    type="hidden"
                                                    name="customer_id"
                                                    value="<?= (int) $customer['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="<?= $status === 'active'
                                                        ? 'inactive'
                                                        : 'active'
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-secondary"
                                                >
                                                    <?= $status === 'active'
                                                        ? 'Deactivate'
                                                        : 'Activate'
                                                    ?>
                                                </button>

                                            </form>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="8">
                                        No customers found.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>

    </div>

</body>

</html>