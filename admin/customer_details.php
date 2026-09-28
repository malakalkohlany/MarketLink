<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

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

    <title>Customer Details | MarketLink</title>

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
        href="../assets/css/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >
    <style>
        /* Customer details - order history table */
.admin-customer-orders-table {
    width: 100%;
    overflow-x: auto;
}

.admin-customer-orders-table table {
    width: 100%;
    border-collapse: collapse;
}

.admin-customer-orders-table tr {
    display: table-row;
}

.admin-customer-orders-table th,
.admin-customer-orders-table td {
    display: table-cell;
}

.admin-customer-orders-table .admin-table-empty {
    text-align: center;
    padding: 3rem 1.5rem;
}
    </style>
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-customer-details">

    <section class="admin-details-hero">

        <div class="admin-details-hero-copy">

            <span class="eyebrow">
                ADMIN / CUSTOMER DETAILS
            </span>

            <h1>
                Customer <em>profile.</em>
            </h1>

            <p>
                View account information, activity status,
                and order history.
            </p>

        </div>

        <div class="admin-details-hero-mark">
            <span>04</span>
        </div>

    </section>


    <?php if (!$customer): ?>

        <section class="admin-details-section">

            <div class="admin-details-empty">

                <span class="admin-empty-mark">
                    ✦
                </span>

                <h2>
                    Customer not found.
                </h2>

                <p>
                    The requested customer does not exist
                    or is no longer available.
                </p>

                <a
                    href="customers.php"
                    class="btn btn-secondary"
                >
                    Back to Customers
                </a>

            </div>

        </section>

    <?php else: ?>

        <section class="admin-details-section">

            <div class="admin-details-section-heading">

                <div>

                    <span class="eyebrow">
                        01 / Profile
                    </span>

                    <h2>
                        <?= htmlspecialchars($customer['name']) ?>
                    </h2>

                </div>

                <a
                    href="customers.php"
                    class="btn btn-outline"
                >
                    Back to Customers
                </a>

            </div>


            <div class="admin-customer-profile">

                <div class="admin-customer-profile-top">

                    <div class="admin-customer-profile-avatar">

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    trim($customer['name'] ?? 'U'),
                                    0,
                                    1
                                )
                            )
                        ) ?>

                    </div>

                    <div class="admin-customer-profile-title">

                        <span class="eyebrow">
                            Customer account
                        </span>

                        <h3>
                            <?= htmlspecialchars($customer['name']) ?>
                        </h3>

                        <span>
                            Customer #<?= (int) $customer['id'] ?>
                        </span>

                    </div>

                    <span
                        class="admin-status admin-status-<?= htmlspecialchars($customer['status']) ?>"
                    >
                        <?= ucfirst(htmlspecialchars($customer['status'])) ?>
                    </span>

                </div>


                <div class="admin-details-grid">

                    <div class="admin-detail-item">

                        <span class="admin-detail-label">
                            Customer ID
                        </span>

                        <strong class="admin-detail-id">
                            #<?= (int) $customer['id'] ?>
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span class="admin-detail-label">
                            Name
                        </span>

                        <strong>
                            <?= htmlspecialchars($customer['name']) ?>
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span class="admin-detail-label">
                            Email
                        </span>

                        <strong>
                            <?= htmlspecialchars($customer['email']) ?>
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span class="admin-detail-label">
                            Phone
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $customer['phone'] ?: 'Not provided'
                            ) ?>
                        </strong>

                    </div>


                    <div class="admin-detail-item admin-detail-wide">

                        <span class="admin-detail-label">
                            Address
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $customer['address'] ?: 'Not provided'
                            ) ?>
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span class="admin-detail-label">
                            Joined
                        </span>

                        <strong>
                            <?= date(
                                'M j, Y · H:i',
                                strtotime($customer['created_at'])
                            ) ?>
                        </strong>

                    </div>


                    <div class="admin-detail-item">

                        <span class="admin-detail-label">
                            Last Updated
                        </span>

                        <strong>
                            <?= date(
                                'M j, Y · H:i',
                                strtotime($customer['updated_at'])
                            ) ?>
                        </strong>

                    </div>

                </div>

            </div>

        </section>


        <section class="admin-customer-orders-section">

            <div class="admin-details-section-heading">

                <div>

                    <span class="eyebrow">
                        02 / Order History
                    </span>

                    <h2>
                        Customer <em>orders.</em>
                    </h2>

                </div>

                <span class="admin-record-count">

                    <?= count($orders) ?>

                    <?= count($orders) === 1
                        ? 'order'
                        : 'orders'
                    ?>

                </span>

            </div>


            <div class="admin-customer-orders-table">

                <table>

                    <thead>

                        <tr>

                            <th>Order</th>
                            <th>Market</th>
                            <th>Farmer</th>
                            <th>Status</th>
                            <th>Subtotal</th>
                            <th>Notes</th>
                            <th>Created</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (!empty($orders)): ?>

                        <?php foreach ($orders as $order): ?>

                            <?php
                            $orderStatus = $order['status'] ?? 'unknown';
                            ?>

                            <tr>

                                <td class="admin-order-id">

                                    #<?= (int) $order['id'] ?>

                                </td>

                                <td class="admin-order-market">

                                    <?= htmlspecialchars(
                                        $order['market_name'] ?? 'N/A'
                                    ) ?>

                                </td>

                                <td class="admin-order-farmer">

                                    <?= htmlspecialchars(
                                        $order['farmer_name'] ?? 'N/A'
                                    ) ?>

                                </td>

                                <td>

                                    <span
                                        class="admin-status admin-status-<?= htmlspecialchars($orderStatus) ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars($orderStatus)
                                        ) ?>

                                    </span>

                                </td>

                                <td class="admin-order-total">

                                    <?= number_format(
                                        (float) $order['subtotal'],
                                        2
                                    ) ?>

                                </td>

                                <td class="admin-order-notes">

                                    <?= htmlspecialchars(
                                        $order['notes'] ?: 'No notes'
                                    ) ?>

                                </td>

                                <td class="admin-order-date">

                                    <?= date(
                                        'M j, Y · H:i',
                                        strtotime($order['created_at'])
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="7"
                                class="admin-table-empty"
                            >

                                <span>✦</span>

                                <strong>
                                    No orders found.
                                </strong>

                                <p>
                                    This customer has not placed
                                    any orders yet.
                                </p>

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