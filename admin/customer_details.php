<?php

require_once __DIR__ . '/../includes/include.php';

requireRole('admin');

$customer_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);
$customer = null;
$orders = [];

if ($customer_id) {

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            email,
            phone,
            address,
            status,
            created_at,
            updated_at
        FROM users
        WHERE id = ?
        AND role = 'customer'
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    $stmt->close();

    if ($customer) {

        $stmt = $conn->prepare("
            SELECT
                o.id,
                o.status,
                o.subtotal,
                o.notes,
                o.created_at,
                m.name AS market_name,
                f.stall_name AS farmer_name
            FROM orders o
            LEFT JOIN markets m
                ON o.market_id = m.id
            LEFT JOIN farmers f
                ON o.farmer_id = f.id
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
        ");

        $stmt->bind_param("i", $customer_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
    }
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

<title>Customer Details | FreshFind</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

</head>

<body>
<aside class="sidebar">

    <div class="logo">
        FreshFind
    </div>

    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="markets.php">
            Markets
        </a>

        <a href="add_market.php">
            Add Market
        </a>

        <a href="categories.php">
            Categories
        </a>

        <a href="farmers.php">
            Farmers
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="users.php" class="active">
            Customers
        </a>

        <a href="orders.php">
            Orders
        </a>

        <a href="reviews.php">
            Reviews
        </a>

        <a href="announcements.php">
            Announcements
        </a>

        <a href="reports.php">
            Reports
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </nav>

</aside>
<main class="main-content">

    <div class="page-header">

        <div>

            <h1>
                Customer Details
            </h1>

            <p>
                View customer information and order history.
            </p>

        </div>

    </div>

    <?php if (!$customer): ?>

        <section class="table-section">

            <div class="section-header">

                <h2>
                    Customer Not Found
                </h2>

            </div>

            <p>
                The requested customer does not exist.
            </p>

            <div class="form-actions">

                <a
                    href="users.php"
                    class="btn btn-secondary"
                >
                    Back to Customers
                </a>

            </div>

        </section>
  <?php else: ?>

        <section class="form-section">

            <div class="section-header">

                <h2>
                    <?= htmlspecialchars($customer['name']) ?>
                </h2>

                <a
                    href="users.php"
                    class="btn btn-secondary"
                >
                    Back
                </a>

            </div>

            <div class="details-grid">

                <div class="form-group">

                    <label>
                        Customer ID
                    </label>

                    <p>
                        <?= (int) $customer['id'] ?>
                    </p>

                </div>

                <div class="form-group">

                    <label>
                        Name
                    </label>

                    <p>
                        <?= htmlspecialchars($customer['name']) ?>
                    </p>

                </div>

                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <p>
                        <?= htmlspecialchars($customer['email']) ?>
                    </p>

                </div>

                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <p>
                        <?= htmlspecialchars(
                            $customer['phone'] ?: 'Not provided'
                        ) ?>
                    </p>

                </div>

                <div class="form-group">

                    <label>
                        Address
                    </label>

                    <p>
                        <?= htmlspecialchars(
                            $customer['address'] ?: 'Not provided'
                        ) ?>
                    </p>

                </div>

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <p>

                        <span
                            class="status status-<?= htmlspecialchars(
                                $customer['status']
                            ) ?>"
                        >
                            <?= ucfirst(
                                htmlspecialchars($customer['status'])
                            ) ?>
                        </span>

                    </p>

                </div>

                <div class="form-group">

                    <label>
                        Joined
                    </label>

                    <p>
                        <?= date(
                            'Y-m-d H:i',
                            strtotime($customer['created_at'])
                        ) ?>
                    </p>

                </div>

                <div class="form-group">

                    <label>
                        Last Updated
                    </label>


   <p>
                        <?= date(
                            'Y-m-d H:i',
                            strtotime($customer['updated_at'])
                        ) ?>
                    </p>

                </div>

            </div>

        </section>

        <section class="table-section">

            <div class="section-header">

                <h2>
                    Customer Orders
                </h2>

            </div>

            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                Order ID
                            </th>

                            <th>
                                Market
                            </th>

                            <th>
                                Farmer
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Subtotal
                            </th>

                            <th>
                                Notes
                            </th>

                            <th>
                                Created At
                            </th>

                            <th>
                                Updated At
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($orders)): ?>

                            <?php foreach ($orders as $order): ?>

                                <tr>

                                    <td>
                                        #<?= (int) $order['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $order['market_name'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $order['farmer_name'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $order['status']
                                            ) ?>"
                                        >
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $order['status']
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $order['subtotal'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $order['notes'] ?: 'No notes'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            'Y-m-d H:i',
                                            strtotime(
                                                $order['created_at']
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            'Y-m-d H:i',
                                            strtotime(
                                                $order['updated_at']
                                            )
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8">
                                    No orders found for this customer.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    <?php endif; ?>

</main>
</body>

</html>