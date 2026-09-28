<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($orderId <= 0) {
    $_SESSION['error'] = 'Invalid order.';
    redirect('customer/orders.php');
}

try {

    $conn->begin_transaction();


    $stmt = $conn->prepare("
        SELECT
            o.id,
            o.customer_id,
            o.farmer_id,
            o.market_id,
            o.pickup_slot_id,
            o.pickup_date,
            o.status,
            o.created_at,
            ps.cutoff_time
        FROM orders o
        INNER JOIN pickup_slots ps
            ON ps.id = o.pickup_slot_id
        WHERE o.id = ?
          AND o.customer_id = ?
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception(
            'Unable to prepare order query.'
        );
    }

    $stmt->bind_param(
        'ii',
        $orderId,
        $customerId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Unable to load the order.'
        );
    }

    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    $stmt->close();

    if (!$order) {
        throw new Exception(
            'Order not found.'
        );
    }

    if ($order['status'] !== 'pending') {
        throw new Exception(
            'Only pending orders can be modified.'
        );
    }

    if (empty($order['pickup_date'])) {
        throw new Exception(
            'This order does not have a pickup date.'
        );
    }

    $cutoffDateTime = new DateTime(
        $order['pickup_date']
        . ' '
        . $order['cutoff_time']
    );

    $now = new DateTime();

    if ($now >= $cutoffDateTime) {
        throw new Exception(
            'The cutoff time for this order has already passed.'
        );
    }

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            operating_days
        FROM markets
        WHERE id = ?
          AND status = 'active'
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception(
            'Unable to prepare market query.'
        );
    }

    $marketId = (int) $order['market_id'];

    $stmt->bind_param(
        'i',
        $marketId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Unable to load market.'
        );
    }

    $result = $stmt->get_result();
    $market = $result->fetch_assoc();

    $stmt->close();

    if (!$market) {
        throw new Exception(
            'The market for this order is no longer available.'
        );
    }

    $stmt = $conn->prepare("
        SELECT
            f.id,
            f.stall_name,
            f.approval_status
        FROM farmers f
        WHERE f.id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception(
            'Unable to prepare farmer query.'
        );
    }

    $farmerId = (int) $order['farmer_id'];

    $stmt->bind_param(
        'i',
        $farmerId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Unable to load farmer.'
        );
    }

    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();

    $stmt->close();

    if (!$farmer) {
        throw new Exception(
            'The farmer for this order could not be found.'
        );
    }

    $stmt = $conn->prepare("
        SELECT
            oi.product_id,
            oi.quantity,

            p.name,
            p.price,
            p.unit,
            p.image,
            p.farmer_id,
            p.is_available,
            p.moderation_status

        FROM order_items oi

        INNER JOIN products p
            ON p.id = oi.product_id

        WHERE oi.order_id = ?

        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception(
            'Unable to prepare order items query.'
        );
    }

    $stmt->bind_param(
        'i',
        $orderId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Unable to load order items.'
        );
    }

    $result = $stmt->get_result();

    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();

    if (empty($items)) {
        throw new Exception(
            'This order contains no products.'
        );
    }

    $createdAt = new DateTime(
        $order['created_at']
    );

    $createdAt->modify(
        'monday this week'
    );

    $weekStart = $createdAt->format('Y-m-d');


    $newCart = [];

    foreach ($items as $item) {

        $productId = (int) $item['product_id'];
        $quantity = (float) $item['quantity'];

        if ($quantity <= 0) {
            throw new Exception(
                'An order item has an invalid quantity.'
            );
        }

        if (
            (int) $item['farmer_id']
            !== $farmerId
        ) {
            throw new Exception(
                'An order product does not belong to the original farmer.'
            );
        }

        $stmt = $conn->prepare("
            UPDATE weekly_stock
            SET
                actual_quantity =
                    actual_quantity + ?,

                status =
                    CASE
                        WHEN actual_quantity + ? > 0
                            THEN 'available'
                        ELSE status
                    END

            WHERE farmer_id = ?
              AND product_id = ?
              AND week_start = ?
              AND is_active = 1
        ");

        if (!$stmt) {
            throw new Exception(
                'Unable to prepare stock restoration query.'
            );
        }

        $stmt->bind_param(
            'ddiis',
            $quantity,
            $quantity,
            $farmerId,
            $productId,
            $weekStart
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Unable to restore weekly stock.'
            );
        }

        $stmt->close();

        $price = (float) $item['price'];

        $newCart[$productId] = [
            'product_id' =>
                $productId,

            'name' =>
                $item['name'],

            'price' =>
                $price,

            'unit' =>
                $item['unit'] ?? '',

            'quantity' =>
                round($quantity, 1),

            'image' =>
                $item['image'] ?? '',

            'farmer_id' =>
                $farmerId,

            'farmer_name' =>
                $farmer['stall_name'],

            'market_id' =>
                $marketId,

            'market_name' =>
                $market['name'],

            'market_days' =>
                $market['operating_days'],

            'subtotal' =>
                round(
                    $quantity * $price,
                    2
                )
        ];
    }

    $stmt = $conn->prepare("
        DELETE FROM order_status_history
        WHERE order_id = ?
    ");

    if (!$stmt) {
        throw new Exception(
            'Unable to prepare history deletion.'
        );
    }

    $stmt->bind_param(
        'i',
        $orderId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Unable to delete order history.'
        );
    }

    $stmt->close();

    $stmt = $conn->prepare("
        DELETE FROM order_items
        WHERE order_id = ?
    ");

    if (!$stmt) {
        throw new Exception(
            'Unable to prepare item deletion.'
        );
    }

    $stmt->bind_param(
        'i',
        $orderId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Unable to delete order items.'
        );
    }

    $stmt->close();

    $stmt = $conn->prepare("
        DELETE FROM orders
        WHERE id = ?
          AND customer_id = ?
          AND status = 'pending'
    ");

    if (!$stmt) {
        throw new Exception(
            'Unable to prepare order deletion.'
        );
    }

    $stmt->bind_param(
        'ii',
        $orderId,
        $customerId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Unable to delete the old order.'
        );
    }

    if ($stmt->affected_rows !== 1) {
        throw new Exception(
            'The old order could not be deleted.'
        );
    }

    $stmt->close();


    $conn->commit();

    // Only modify session after DB commit succeeds
    $_SESSION['cart'] = $newCart;


    redirect('customer/cart.php');

} catch (Throwable $e) {

    if ($conn->in_transaction) {
        $conn->rollback();
    }

    $_SESSION['error'] =
        'Unable to modify this order: '
        . $e->getMessage();

    redirect('customer/orders.php');
}