```php
<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$orders = [];

/*
|--------------------------------------------------------------------------
| Load all customer orders
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        orders.id,
        orders.status,
        orders.subtotal,
        orders.created_at,

        users.name AS customer_name,
        users.email AS customer_email,
        users.phone AS customer_phone,

        farmers.stall_name AS farmer_name,

        markets.name AS market_name

    FROM orders

    INNER JOIN users
        ON orders.customer_id = users.id

    LEFT JOIN farmers
        ON orders.farmer_id = farmers.id

    LEFT JOIN markets
        ON orders.market_id = markets.id

    WHERE users.role = 'customer'

    ORDER BY orders.created_at DESC
");

if ($stmt) {

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        $orders = $result->fetch_all(MYSQLI_ASSOC);

    } else {

        $errors[] = 'Failed to load orders: ' . $stmt->error;
    }

    $stmt->close();

} else {

    $errors[] = 'Failed to prepare order query: ' . $conn->error;
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

    <title>Orders | MarketLink</title>

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

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <div class="admin-container">

        <main class="main-content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div>

                    <h1>
                        Orders
                    </h1>

                    <p>
                        View all customer orders.
                    </p>

                </div>

            </div>


            <!-- ERRORS -->

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">

                    <?php foreach ($errors as $error): ?>

                        <p>
                            <?= htmlspecialchars($error) ?>
                        </p>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <!-- ORDERS TABLE -->

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
                                    Customer
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Farmer
                                </th>

                                <th>
                                    Market
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (!empty($orders)): ?>

                                <?php foreach ($orders as $order): ?>

                                    <?php

                                    $status = strtolower(
                                        trim($order['status'] ?? 'pending')
                                    );

                                    ?>

                                    <tr>


                                        <!-- ORDER ID -->

                                        <td>

                                            #<?= (int) $order['id'] ?>

                                        </td>


                                        <!-- CUSTOMER -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['customer_name'] ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- EMAIL -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['customer_email'] ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- PHONE -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['customer_phone'] ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- FARMER -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['farmer_name'] ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- MARKET -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['market_name'] ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- TOTAL -->

                                        <td>

                                            $<?= number_format(
                                                (float) ($order['subtotal'] ?? 0),
                                                2
                                            ) ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="status status-<?= htmlspecialchars($status) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst($status)
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <?= !empty($order['created_at'])

                                                ? date(
                                                    'Y-m-d',
                                                    strtotime(
                                                        $order['created_at']
                                                    )
                                                )

                                                : 'N/A'
                                            ?>

                                        </td>


                                    </tr>

                                <?php endforeach; ?>


                            <?php else: ?>

                                <tr>

                                    <td colspan="9">

                                        No orders found.

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
```
