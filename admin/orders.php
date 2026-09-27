<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$orders = [];

$items_per_page = 10;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $items_per_page;
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM orders
    INNER JOIN users
        ON orders.customer_id = users.id
    WHERE users.role = 'customer'
");

if ($count_stmt) {

    if ($count_stmt->execute()) {

        $count_result = $count_stmt->get_result();

        $total_orders = (int) $count_result
            ->fetch_assoc()['total_orders'];

    } else {

        $total_orders = 0;

        $errors[] =
            'Failed to count orders: ' . $count_stmt->error;
    }

    $count_stmt->close();

} else {

    $total_orders = 0;

    $errors[] =
        'Failed to prepare count query: ' . $conn->error;
}
$total_pages = $total_orders > 0
    ? (int) ceil(
        $total_orders / $items_per_page
    )
    : 0;
    if ($total_pages > 0 && $page > $total_pages) {

    $page = $total_pages;

    $offset = ($page - 1) * $items_per_page;
}

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

    LIMIT ? OFFSET ?
");

if ($stmt) {

    
    $stmt->bind_param(
        "ii",
        $items_per_page,
        $offset
    );

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        $orders = $result->fetch_all(MYSQLI_ASSOC);

    } else {

        $errors[] =
            'Failed to load orders: ' . $stmt->error;
    }

    $stmt->close();

} else {

    $errors[] =
        'Failed to prepare order query: ' . $conn->error;
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

    <style>
            .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .pagination a {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 40px;
            height: 40px;

            padding: 0 12px;

            border: 1px solid #ddd;
            border-radius: 8px;

            background: #fff;
            color: #333;

            text-decoration: none;

            font-size: 14px;
            font-weight: 600;

            transition: 0.2s;
        }

        .pagination a:hover {
            background: #27ae60;
            border-color: #27ae60;
            color: #fff;
        }

        .pagination a.active {
            background: #27ae60;
            border-color: #27ae60;
            color: #fff;
        }

        @media (max-width: 600px) {

            .pagination {
                gap: 5px;
            }

            .pagination a {
                min-width: 36px;
                height: 36px;
                padding: 0 9px;
                font-size: 13px;
            }

        }

    </style>

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
                                        trim(
                                            $order['status'] ?? 'pending'
                                        )
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
                                                $order['customer_name']
                                                    ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- EMAIL -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['customer_email']
                                                    ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- PHONE -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['customer_phone']
                                                    ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- FARMER -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['farmer_name']
                                                    ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- MARKET -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $order['market_name']
                                                    ?? 'N/A'
                                            ) ?>

                                        </td>


                                        <!-- TOTAL -->

                                        <td>

                                            $<?= number_format(
                                                (float) (
                                                    $order['subtotal']
                                                        ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="status status-<?= htmlspecialchars(
                                                    $status
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst($status)
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <?= !empty(
                                                $order['created_at']
                                            )

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
                
                <?php if ($total_pages > 1): ?>

                    <div class="pagination">


                        <!-- PREVIOUS -->

                        <?php if ($page > 1): ?>

                            <a
                                href="?page=<?= $page - 1 ?>"
                            >
                                Previous
                            </a>

                        <?php endif; ?>


                        <!-- PAGE NUMBERS -->

                        <?php for (
                            $i = 1;
                            $i <= $total_pages;
                            $i++
                        ): ?>

                            <a
                                href="?page=<?= $i ?>"
                                class="<?= $i === $page
                                    ? 'active'
                                    : '' ?>"
                            >
                                <?= $i ?>
                            </a>

                        <?php endfor; ?>


                        <!-- NEXT -->

                        <?php if ($page < $total_pages): ?>

                            <a
                                href="?page=<?= $page + 1 ?>"
                            >
                                Next
                            </a>

                        <?php endif; ?>


                    </div>

                <?php endif; ?>


            </section>


        </main>

    </div>


</body>

</html>