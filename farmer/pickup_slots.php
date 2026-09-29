<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$user_id = getUserId();

$stmt = $conn->prepare("
    SELECT id
    FROM farmers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$stmt->close();

if (!$farmer) {
    die("Farmer account not found.");
}

$farmer_id = (int)$farmer['id'];


/*
|--------------------------------------------------------------------------
| Automatically Complete Expired Orders
|--------------------------------------------------------------------------
*/

$expired_orders_stmt = $conn->prepare("
    UPDATE orders
    INNER JOIN pickup_slots
        ON orders.pickup_slot_id = pickup_slots.id
    SET
        orders.status = 'completed',
        orders.updated_at = CURRENT_TIMESTAMP
    WHERE orders.farmer_id = ?
      AND orders.pickup_date IS NOT NULL
      AND orders.status NOT IN ('completed', 'cancelled')
      AND CONCAT(
            orders.pickup_date,
            ' ',
            pickup_slots.end_time
          ) < CURRENT_TIMESTAMP
");

$expired_orders_stmt->bind_param(
    "i",
    $farmer_id
);

$expired_orders_stmt->execute();

$expired_orders_stmt->close();


/*
|--------------------------------------------------------------------------
| Update Order Status
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: orders.php');
        exit;
    }


    $order_id = isset($_POST['order_id'])
        ? (int)$_POST['order_id']
        : 0;

    $new_status = isset($_POST['status'])
        ? trim($_POST['status'])
        : '';


    $allowed_statuses = [
        'accepted',
        'preparing',
        'ready',
        'completed',
        'cancelled'
    ];


    if ($order_id <= 0) {

        $_SESSION['error'] = 'Invalid order.';

    } elseif (!in_array($new_status, $allowed_statuses, true)) {

        $_SESSION['error'] = 'Invalid order status.';

    } else {

        $order_stmt = $conn->prepare("
            SELECT
                orders.id,
                orders.customer_id,
                orders.status,
                orders.pickup_date,
                pickup_slots.start_time,
                pickup_slots.end_time,
                CONCAT(
                    orders.pickup_date,
                    ' ',
                    pickup_slots.end_time
                ) < CURRENT_TIMESTAMP AS pickup_expired
            FROM orders
            INNER JOIN pickup_slots
                ON orders.pickup_slot_id = pickup_slots.id
            WHERE orders.id = ?
              AND orders.farmer_id = ?
            LIMIT 1
        ");

        $order_stmt->bind_param(
            "ii",
            $order_id,
            $farmer_id
        );

        $order_stmt->execute();

        $order_result = $order_stmt->get_result();
        $order = $order_result->fetch_assoc();

        $order_stmt->close();


        if (!$order) {

            $_SESSION['error'] = 'Order not found.';

        } else {

            $old_status = $order['status'];
            $customer_id = (int)$order['customer_id'];
            $pickup_expired = (bool)$order['pickup_expired'];


            /*
            |--------------------------------------------------------------------------
            | Prevent Modification After Pickup Time
            |--------------------------------------------------------------------------
            */

            if (
                $pickup_expired &&
                !in_array(
                    $old_status,
                    ['completed', 'cancelled'],
                    true
                )
            ) {

                $complete_stmt = $conn->prepare("
                    UPDATE orders
                    SET
                        status = 'completed',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                      AND farmer_id = ?
                      AND status NOT IN ('completed', 'cancelled')
                ");

                $complete_stmt->bind_param(
                    "ii",
                    $order_id,
                    $farmer_id
                );

                $complete_stmt->execute();

                $complete_stmt->close();


                $_SESSION['error'] =
                    "Order #{$order_id} can no longer be modified because the pickup time has passed.";

            } else {

                $valid_transitions = [

                    'pending' => [
                        'accepted',
                        'cancelled'
                    ],

                    'accepted' => [
                        'preparing',
                        'cancelled'
                    ],

                    'preparing' => [
                        'ready',
                        'cancelled'
                    ],

                    'ready' => [
                        'completed'
                    ],

                    'completed' => [],

                    'cancelled' => []
                ];


                if (
                    !isset($valid_transitions[$old_status]) ||
                    !in_array(
                        $new_status,
                        $valid_transitions[$old_status],
                        true
                    )
                ) {

                    $_SESSION['error'] =
                        "Cannot change order from '{$old_status}' to '{$new_status}'.";

                } else {

                    $conn->begin_transaction();

                    try {

                        $update_stmt = $conn->prepare("
                            UPDATE orders
                            SET
                                status = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?
                              AND farmer_id = ?
                        ");

                        $update_stmt->bind_param(
                            "sii",
                            $new_status,
                            $order_id,
                            $farmer_id
                        );

                        if (!$update_stmt->execute()) {
                            throw new Exception(
                                'Failed to update order status.'
                            );
                        }

                        if ($update_stmt->affected_rows !== 1) {
                            throw new Exception(
                                'Order status was not updated.'
                            );
                        }

                        $update_stmt->close();


                        $history_stmt = $conn->prepare("
                            INSERT INTO order_status_history (
                                order_id,
                                status,
                                changed_by
                            )
                            VALUES (?, ?, ?)
                        ");

                        $history_stmt->bind_param(
                            "isi",
                            $order_id,
                            $new_status,
                            $user_id
                        );

                        if (!$history_stmt->execute()) {
                            throw new Exception(
                                'Failed to record order status history.'
                            );
                        }

                        $history_stmt->close();


                        switch ($new_status) {

                            case 'accepted':

                                $notification_title =
                                    'Order Accepted';

                                $notification_message =
                                    "Your order #{$order_id} has been accepted by the market.";

                                break;


                            case 'preparing':

                                $notification_title =
                                    'Order Being Prepared';

                                $notification_message =
                                    "Your order #{$order_id} is now being prepared.";

                                break;


                            case 'ready':

                                $notification_title =
                                    'Order Ready for Pickup';

                                $notification_message =
                                    "Your order #{$order_id} is ready for pickup.";

                                break;


                            case 'completed':

                                $notification_title =
                                    'Order Completed';

                                $notification_message =
                                    "Your order #{$order_id} has been completed.";

                                break;


                            case 'cancelled':

                                $notification_title =
                                    'Order Cancelled';

                                $notification_message =
                                    "Your order #{$order_id} has been cancelled by the market.";

                                break;


                            default:

                                $notification_title =
                                    'Order Status Updated';

                                $notification_message =
                                    "The status of your order #{$order_id} has been updated.";
                        }


                        if (!createNotification(
                            $conn,
                            $customer_id,
                            'order_status',
                            $notification_title,
                            $notification_message
                        )) {

                            throw new Exception(
                                'Failed to create customer notification.'
                            );
                        }


                        $conn->commit();


                        $_SESSION['success'] =
                            "Order #{$order_id} updated successfully.";

                    } catch (Throwable $e) {

                        $conn->rollback();

                        $_SESSION['error'] =
                            'Failed to update the order. Please try again.';
                    }
                }
            }
        }
    }


    $redirect_page = isset($_GET['page'])
        ? (int)$_GET['page']
        : 1;

    redirect('orders.php?page=' . max(1, $redirect_page));
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$items_per_page = 10;

$page = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $items_per_page;


$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM orders
    WHERE farmer_id = ?
");

$count_stmt->bind_param(
    "i",
    $farmer_id
);

$count_stmt->execute();

$count_result = $count_stmt->get_result();

$total_orders = (int)$count_result
    ->fetch_assoc()['total_orders'];

$count_stmt->close();


$total_pages = $total_orders > 0
    ? (int)ceil($total_orders / $items_per_page)
    : 0;


if ($total_pages > 0 && $page > $total_pages) {

    $page = $total_pages;

    $offset = ($page - 1) * $items_per_page;
}


/*
|--------------------------------------------------------------------------
| Get Orders
|--------------------------------------------------------------------------
*/

$order_stmt = $conn->prepare("
    SELECT
        orders.id,
        orders.customer_id,
        orders.status,
        orders.subtotal,
        orders.notes,
        orders.created_at,
        orders.updated_at,
        orders.pickup_date,
        pickup_slots.start_time,
        pickup_slots.end_time,
        users.name AS customer_name
    FROM orders
    INNER JOIN users
        ON orders.customer_id = users.id
    INNER JOIN pickup_slots
        ON orders.pickup_slot_id = pickup_slots.id
    WHERE orders.farmer_id = ?
    ORDER BY orders.created_at DESC
    LIMIT ? OFFSET ?
");

$order_stmt->bind_param(
    "iii",
    $farmer_id,
    $items_per_page,
    $offset
);

$order_stmt->execute();

$orders = $order_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Slots</title>
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
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">

        <h1>Pickup Slots</h1>

        <?php if (isset($success_message)): ?>
            <p><?= e($success_message) ?></p>
        <?php endif; ?>
        
        <h2>Add Pickup Slots</h2>

        <form method="POST">
              <?= csrf_field() ?>
            <label for="market_id">Market</label>
            <select name="market_id" id="market_id" required>
            <option value="">Select Market</option>
            <?php while ($market = $markets->fetch_assoc()): ?>
                <option value="<?= e($market['id']) ?>">
                    <?= e($market['name']) ?>
                </option>
            <?php endwhile; ?>   
        </select>
        <br><br>
        
        <label for="day_of_week">Day</label>
        <select name="day_of_week" id="day_of_week" required>
                <option value="">Select Day</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
        </select>
        <br><br>

        <label for="start_time">Start Time</label>
        <input type="time" name="start_time" id="start_time" required>
        <br><br>

        <label for="end_time">End Time</label>
        <input type="time" name="end_time" id="end_time" required>
        <br><br>
        
        <label for="cutoff_time">Cutoff Time</label>
        <input type="time" name="cutoff_time" id="cutoff_time" required>
        <br><br>
        
        <label for="max_orders">Maximun Orders</label>
        <input type="number" name="max_orders" id="max_orders" min="1" required>
        <br><br>    

        <button type="submit">Add Pickup Slot</button>
        </form>

        <h2>My Pickup Slot</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Market</th>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Cutoff Time</th>
                    <th>Maximun Orders</th>
                    <th>Availability</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($slot = $slots->fetch_assoc()): ?>
                <tr>
                    <td><?= e($slot['market_name']) ?></td>
                    <td><?= e($slot['day_of_week']) ?></td>
                    <td><?= e($slot['start_time']) ?></td>
                    <td><?= e($slot['end_time']) ?></td>
                    <td><?= e($slot['cutoff_time']) ?></td>
                    <td><?= e($slot['max_orders']) ?></td>
                    <td>
                        <?php if ($slot['is_available']): ?>
                            Available
                        <?php else: ?>
                            Unavailable
                        <?php endif; ?>                    
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>    
        <?php if ($total_slots_pages > 1): ?>

            <div class="pagination">

                <?php if ($slots_page > 1): ?>
                    <a href="?slots_page=<?= $slots_page - 1 ?>&orders_page=<?= $orders_page ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_slots_pages; $i++): ?>
                    <a href="?slots_page=<?= $i ?>&orders_page=<?= $orders_page ?>"
                    <?= $i == $slots_page ? 'class="active"' : '' ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($slots_page < $total_slots_pages): ?>
                    <a href="?slots_page=<?= $slots_page + 1 ?>&orders_page=<?= $orders_page ?>">
                        Next
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

            <h2>Orders Using My Pickup Slots</h2>
            <table border="1">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Market</th>
                        <th>Day</th>
                        <th>Packup Time</th>
                        <th>Status</th>
                        <th>Order Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($order['order_id']) ?></td>
                            <td><?= e($order['market_name']) ?></td>
                            <td><?= e($order['day_of_week']) ?></td>
                            <td>
                            <?= e($order['start_time']) ?>
                            -<?= e($order['end_time']) ?></td>    
                            <td><?= e($order['status']) ?></td>     
                            <td><?= formatDate($order['created_at']) ?></td>       
                        </tr>
                    <?php endwhile; ?>    
                </tbody>
            </table>

        <?php if ($total_orders_pages > 1): ?>

            <div class="pagination">

                <?php if ($orders_page > 1): ?>
                    <a href="?orders_page=<?= $orders_page - 1 ?>&slots_page=<?= $slots_page ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_orders_pages; $i++): ?>
                    <a href="?orders_page=<?= $i ?>&slots_page=<?= $slots_page ?>"
                    <?= $i == $orders_page ? 'class="active"' : '' ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($orders_page < $total_orders_pages): ?>
                    <a href="?orders_page=<?= $orders_page + 1 ?>&slots_page=<?= $slots_page ?>">
                        Next
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>
    </main>
</body>
</html>