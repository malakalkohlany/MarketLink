<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('customer');

// ===============================
// Get Logged-in Customer ID
// ===============================

$customerId = getUserId();

if (!$customerId) {
    header('Location: ../auth/login.php');
    exit;
}

// ===============================
// Get Customer Orders
// ===============================

$stmt = $conn->prepare("
    SELECT
        o.id,
        o.status,
        o.subtotal,
        o.notes,
        o.created_at,
        o.updated_at
    FROM orders o
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$ordersResult = $stmt->get_result();

$orders = [];

while ($order = $ordersResult->fetch_assoc()) {
    $orders[] = $order;
}

$stmt->close();


// ===============================
// Get Items For Each Order
// ===============================

foreach ($orders as &$order) {

    $itemStmt = $conn->prepare("
        SELECT
            oi.product_id,
            oi.quantity,
            oi.unit_price,
            oi.subtotal,
            p.name AS product_name,
            p.image AS product_image,
            p.unit AS product_unit
        FROM order_items oi
        LEFT JOIN products p
            ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");

    $itemStmt->bind_param("i", $order['id']);
    $itemStmt->execute();

    $itemsResult = $itemStmt->get_result();

    $order['items'] = [];

    while ($item = $itemsResult->fetch_assoc()) {
        $order['items'][] = $item;
    }

    $itemStmt->close();
}

unset($order);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Orders - MarketLink</title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #333;
        }


        /* ===============================
           Page Container
        =============================== */

        .orders-container {
            width: 92%;
            max-width: 1100px;
            margin: 40px auto 60px;
        }


        /* ===============================
           Page Header
        =============================== */

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 32px;
            color: #222;
        }

        .page-header p {
            margin: 0;
            color: #777;
            font-size: 15px;
        }


        /* ===============================
           Order Card
        =============================== */

        .order-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 25px;
            padding: 25px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.07);
        }


        /* ===============================
           Order Header
        =============================== */

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            padding-bottom: 18px;
            border-bottom: 1px solid #eeeeee;
        }

        .order-number {
            font-size: 20px;
            font-weight: bold;
            color: #222;
        }

        .order-date {
            margin-top: 6px;
            color: #888;
            font-size: 14px;
        }


        /* ===============================
           Status
        =============================== */

        .status {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 20px;

            font-size: 13px;
            font-weight: bold;

            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-preparing {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .status-ready {
            background: #dcfce7;
            color: #15803d;
        }

        .status-completed {
            background: #d1fae5;
            color: #047857;
        }


        /* ===============================
           Order Items
        =============================== */

        .items-title {
            margin: 22px 0 12px;

            font-size: 16px;
            font-weight: bold;
            color: #444;
        }


        .order-item {
            display: flex;
            align-items: center;

            padding: 14px 0;

            border-bottom: 1px solid #eeeeee;
        }


        .product-image {
            width: 65px;
            height: 65px;

            border-radius: 10px;

            object-fit: cover;

            background: #eeeeee;

            margin-right: 15px;
        }


        .no-image {
            width: 65px;
            height: 65px;

            border-radius: 10px;

            background: #eeeeee;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #999;

            font-size: 11px;

            margin-right: 15px;
        }


        .item-info {
            flex: 1;
        }


        .product-name {
            font-weight: bold;
            color: #333;

            margin-bottom: 6px;
        }


        .product-quantity {
            color: #888;
            font-size: 14px;
        }


        .item-price {
            font-weight: bold;
            color: #333;

            min-width: 90px;

            text-align: right;
        }


        /* ===============================
           Notes
        =============================== */

        .order-notes {
            margin-top: 18px;

            background: #f8f9fb;

            border-radius: 10px;

            padding: 13px 15px;

            color: #666;

            font-size: 14px;

            line-height: 1.6;
        }


        .notes-label {
            font-weight: bold;
            color: #444;
        }


        /* ===============================
           Order Footer
        =============================== */

        .order-footer {
            display: flex;

            justify-content: flex-end;

            align-items: center;

            margin-top: 22px;

            padding-top: 18px;

            border-top: 1px solid #eeeeee;
        }


        .total-label {
            color: #666;

            margin-right: 10px;

            font-size: 15px;
        }


        .total-price {
            font-size: 22px;

            font-weight: bold;

            color: #27ae60;
        }


        /* ===============================
           Empty Orders
        =============================== */

        .empty-orders {
            background: white;

            border-radius: 16px;

            padding: 60px 30px;

            text-align: center;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.07);
        }


        .empty-orders h2 {
            margin-bottom: 10px;

            color: #333;
        }


        .empty-orders p {
            color: #777;

            margin-bottom: 25px;
        }


        .browse-button {
            display: inline-block;

            padding: 12px 24px;

            background: #27ae60;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;
        }


        .browse-button:hover {
            background: #219150;
        }


        /* ===============================
           Mobile
        =============================== */

        @media (max-width: 700px) {

            .orders-container {
                width: 94%;
                margin-top: 25px;
            }


            .order-card {
                padding: 20px;
            }


            .order-header {
                align-items: flex-start;

                gap: 15px;

                flex-direction: column;
            }


            .order-item {
                align-items: flex-start;
            }


            .item-price {
                min-width: auto;

                margin-left: 10px;
            }


            .order-footer {
                justify-content: space-between;
            }

        }

    </style>

</head>


<body>


<div class="orders-container">


    <!-- ===============================
         Page Header
    =============================== -->

    <div class="page-header">

        <h1>
            My Orders
        </h1>

        <p>
            View your previous and current orders.
        </p>

    </div>


    <?php if (empty($orders)): ?>


        <!-- ===============================
             No Orders
        =============================== -->

        <div class="empty-orders">

            <h2>
                No Orders Yet
            </h2>

            <p>
                You have not placed any orders yet.
            </p>

            <a
                href="products.php"
                class="browse-button"
            >
                Browse Products
            </a>

        </div>


    <?php else: ?>


        <!-- ===============================
             Orders
        =============================== -->

        <?php foreach ($orders as $order): ?>


            <div class="order-card">


                <!-- Order Header -->

                <div class="order-header">

                    <div>

                        <div class="order-number">

                            Order #<?php
                            echo (int) $order['id'];
                            ?>

                        </div>

                        <div class="order-date">

                            <?php

                            echo date(
                                'M d, Y - h:i A',
                                strtotime(
                                    $order['created_at']
                                )
                            );

                            ?>

                        </div>

                    </div>


                    <!-- Status -->

                    <?php

                    $status = strtolower(
                        $order['status']
                    );

                    $statusClass =
                        'status-' . $status;

                    ?>

                    <span
                        class="status <?php echo htmlspecialchars($statusClass); ?>"
                    >

                        <?php

                        echo htmlspecialchars(
                            ucfirst($status)
                        );

                        ?>

                    </span>

                </div>


                <!-- Items Title -->

                <div class="items-title">

                    Order Items

                </div>


                <!-- Items -->

                <?php if (!empty($order['items'])): ?>


                    <?php foreach ($order['items'] as $item): ?>


                        <div class="order-item">


                            <!-- Product Image -->

                            <?php if (!empty($item['product_image'])): ?>

                                <img
                                    src="../uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                    class="product-image"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    No Image
                                </div>

                            <?php endif; ?>


                            <!-- Product Info -->

                            <div class="item-info">

                                <div class="product-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $item['product_name']
                                        ?? 'Product'
                                    );

                                    ?>

                                </div>


                                <div class="product-quantity">

                                    Quantity:

                                    <?php

                                    echo number_format(
                                        (float) $item['quantity'],
                                        2
                                    );

                                    ?>

                                    <?php

                                    echo htmlspecialchars(
                                        $item['product_unit']
                                        ?? ''
                                    );

                                    ?>

                                    × $

                                    <?php

                                    echo number_format(
                                        (float) $item['unit_price'],
                                        2
                                    );

                                    ?>

                                </div>

                            </div>


                            <!-- Item Subtotal -->

                            <div class="item-price">

                                $

                                <?php

                                echo number_format(
                                    (float) $item['subtotal'],
                                    2
                                );

                                ?>

                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <p>
                        No items found for this order.
                    </p>


                <?php endif; ?>


                <!-- Notes -->

                <?php if (!empty($order['notes'])): ?>

                    <div class="order-notes">

                        <span class="notes-label">
                            Note:
                        </span>

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $order['notes']
                            )
                        );

                        ?>

                    </div>

                <?php endif; ?>


                <!-- Footer -->

                <div class="order-footer">

                    <span class="total-label">
                        Order Total:
                    </span>

                    <span class="total-price">

                        $

                        <?php

                        echo number_format(
                            (float) $order['subtotal'],
                            2
                        );

                        ?>

                    </span>

                </div>


            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</div>


</body>

</html>