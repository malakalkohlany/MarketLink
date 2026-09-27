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
                id,
                customer_id,
                status
            FROM orders
            WHERE id = ?
              AND farmer_id = ?
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

    
    $redirect_page = isset($_GET['page'])
        ? (int)$_GET['page']
        : 1;

    header(
        'Location: orders.php?page=' . max(1, $redirect_page)
    );

    exit;
}

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

$order_stmt = $conn->prepare("
    SELECT
        orders.id,
        orders.customer_id,
        orders.status,
        orders.subtotal,
        orders.notes,
        orders.created_at,
        orders.updated_at,
        users.name AS customer_name
    FROM orders
    INNER JOIN users
        ON orders.customer_id = users.id
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
    <title>Orders</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>My Orders</h1>
        <table border="1">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Subtotal</th>
                    <th>Notes</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php while ($order = $orders->fetch_assoc()): ?>

                    <tr>

                        <td><?= e($order['id']) ?></td>

                        <td><?= e($order['customer_name']) ?></td>

                        <td><?= e($order['status']) ?></td>

                        <td><?= formatPrice($order['subtotal']) ?></td>

                        <td><?= e($order['notes'] ?? '') ?></td>

                        <td><?= formatDateTime($order['created_at']) ?></td>

                        <td>

                            <a href="order_details.php?id=<?= (int)$order['id'] ?>">
                                View Details
                            </a>

                            <?php
                            $current_status = $order['status'];

                            $next_statuses = [
                                'pending' => [
                                    'accepted' => 'Accept Order',
                                    'cancelled' => 'Cancel Order'
                                ],

                                'accepted' => [
                                    'preparing' => 'Start Preparing',
                                    'cancelled' => 'Cancel Order'
                                ],

                                'preparing' => [
                                    'ready' => 'Mark Ready',
                                    'cancelled' => 'Cancel Order'
                                ],

                                'ready' => [
                                    'completed' => 'Mark Completed'
                                ],

                                'completed' => [],

                                'cancelled' => []
                            ];
                            ?>

                            <?php if (!empty($next_statuses[$current_status])): ?>

                                <form
                                    method="POST"
                                    style="margin-top: 8px;"
                                >

                                <?= csrf_field() ?>

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int)$order['id'] ?>"
                                    >

                                    <select
                                        name="status"
                                        required
                                    >

                                        <option value="">
                                            Change Status
                                        </option>

                                        <?php foreach (
                                            $next_statuses[$current_status]
                                            as $status_value => $status_label
                                        ): ?>

                                            <option value="<?= e($status_value) ?>">
                                                <?= e($status_label) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <button type="submit">
                                        Update
                                    </button>

                                </form>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>
        <?php if ($total_pages > 1): ?>

    <div class="pagination">

        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>"
               <?= $i == $page ? 'class="active"' : '' ?>>
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>">Next</a>
        <?php endif; ?>

    </div>

    <?php endif; ?>
    </main>
</body>
</html>