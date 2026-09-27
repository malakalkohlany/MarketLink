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

    redirect('customers.php');
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

        $errors[] = 'Failed to load customers.';
    }

    $stmt->close();

} else {

    $errors[] = 'Failed to prepare customer query.';
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

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/navbar.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content admin-customers-page">

    <section class="admin-page-hero">

        <div>

            <span class="eyebrow">
                ADMIN / CUSTOMERS
            </span>

            <h1>
                Manage local <em>customers.</em>
            </h1>

            <p>
                View customer accounts, contact information,
                activity status, and registration details.
            </p>

        </div>


        <div class="admin-page-mark">
            <span>03</span>
        </div>

    </section>

    <?php if (!empty($errors)): ?>

        <div class="admin-page-alert alert-danger">

            <span class="admin-alert-mark">
                !
            </span>

            <div>

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= htmlspecialchars($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <section class="admin-management-section">


        <div class="admin-section-heading">

            <div>

                <span class="eyebrow">
                    01 / Directory
                </span>

                <h2>
                    Customer <em>accounts.</em>
                </h2>

            </div>


            <span class="admin-record-count">

                <?= count($customers) ?>

                <?= count($customers) === 1
                    ? 'customer'
                    : 'customers'
                ?>

            </span>

        </div>


        <div class="admin-customers-table">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Customer</th>

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

                        $status =
                            ($customer['status'] ?? '') === 'active'
                                ? 'active'
                                : 'inactive';

                        $name =
                            trim($customer['name'] ?? '');

                        $initial =
                            strtoupper(
                                substr(
                                    $name !== ''
                                        ? $name
                                        : 'U',
                                    0,
                                    1
                                )
                            );

                        ?>


                        <tr>



                            <td class="admin-customer-id">

                                #<?= (int) $customer['id'] ?>

                            </td>


                            <td>

                                <div class="admin-customer-name">

                                    <div class="admin-customer-avatar">
                                        <?= htmlspecialchars($initial) ?>
                                    </div>

                                    <div>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $name !== ''
                                                    ? $name
                                                    : 'N/A'
                                            ) ?>
                                        </strong>

                                        <span>
                                            Customer account
                                        </span>

                                    </div>

                                </div>

                            </td>


                            <td class="admin-customer-email">

                                <?= htmlspecialchars(
                                    $customer['email'] ?? 'N/A'
                                ) ?>

                            </td>


                            <td class="admin-customer-phone">

                                <?= htmlspecialchars(
                                    $customer['phone'] ?? 'N/A'
                                ) ?>

                            </td>


                            <td class="admin-customer-address">

                                <?= htmlspecialchars(
                                    $customer['address'] ?? 'N/A'
                                ) ?>

                            </td>

                            <td>

                                <span
                                    class="admin-status admin-status-<?= $status ?>"
                                >

                                    <?= ucfirst($status) ?>

                                </span>

                            </td>

                            <td class="admin-customer-date">

                                <?= !empty($customer['created_at'])
                                    ? date(
                                        'M j, Y',
                                        strtotime(
                                            $customer['created_at']
                                        )
                                    )
                                    : 'N/A'
                                ?>

                            </td>


                            <td>

                                <div class="admin-customer-actions">

                                    <a
                                        href="customer_details.php?id=<?= (int) $customer['id'] ?>"
                                        class="admin-action-view"
                                    >
                                        View
                                    </a>

                                    

                                </div>

                            </td>


                        </tr>

                    <?php endforeach; ?>


                <?php else: ?>

                    <tr>

                        <td
                            colspan="8"
                            class="admin-table-empty"
                        >

                            <span>✦</span>

                            <strong>
                                No customers found.
                            </strong>

                            <p>
                                Customer accounts will appear here
                                once registered.
                            </p>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="admin-customers-footer">

            <a
                href="users.php"
                class="admin-action-reject"
            >
                Manage Users
            </a>

        </div>


    </section>


</main>

</body>

</html>