<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = getUserId();

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($orderId <= 0) {
    redirect('customer/orders.php');
}

$errors = [];
$success = false;

/* =========================================================
   GET ORDER
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        o.id,
        o.customer_id,
        o.farmer_id,
        o.market_id,
        o.pickup_slot_id,
        o.pickup_date,
        o.status,
        o.subtotal,
        o.notes,
        o.created_at,

        f.stall_name,
        f.contact_person,

        m.name AS market_name,
        m.address AS market_address,

        ps.day_of_week,
        ps.start_time,
        ps.end_time,
        ps.cutoff_time,
        ps.max_orders

    FROM orders o

    INNER JOIN farmers f
        ON f.id = o.farmer_id

    INNER JOIN markets m
        ON m.id = o.market_id

    INNER JOIN pickup_slots ps
        ON ps.id = o.pickup_slot_id

    WHERE o.id = ?
      AND o.customer_id = ?

    LIMIT 1
");

$stmt->bind_param(
    'ii',
    $orderId,
    $customerId
);

$stmt->execute();

$result = $stmt->get_result();

$order = $result->fetch_assoc();

$stmt->close();

if (!$order) {
    redirect('customer/orders.php');
}


/* =========================================================
   ONLY PENDING ORDERS CAN BE MODIFIED
   ========================================================= */

if ($order['status'] !== 'pending') {
    redirect('customer/orders.php?id=' . $orderId);
}


/* =========================================================
   ORIGINAL ORDER WEEK
   ========================================================= */

$createdAt = new DateTime($order['created_at']);

$originalWeekStartDate = clone $createdAt;
$originalWeekStartDate->modify('monday this week');

$originalWeekStart = $originalWeekStartDate->format('Y-m-d');


/* =========================================================
   CHECK ORIGINAL PICKUP CUTOFF
   ========================================================= */

$cutoffPassed = false;

if (!empty($order['pickup_date'])) {

    $pickupDateTime = new DateTime(
        $order['pickup_date'] . ' ' . $order['cutoff_time']
    );

    $now = new DateTime();

    if ($now >= $pickupDateTime) {
        $cutoffPassed = true;
    }
}

if ($cutoffPassed) {
    redirect('customer/orders.php?id=' . $orderId);
}


/* =========================================================
   GET EXISTING ORDER ITEMS
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        oi.subtotal,

        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        p.stock_quantity,
        p.is_available,
        p.moderation_status,

        c.name AS category_name

    FROM order_items oi

    INNER JOIN products p
        ON p.id = oi.product_id

    LEFT JOIN categories c
        ON c.id = p.category_id

    WHERE oi.order_id = ?

    ORDER BY p.name ASC
");

$stmt->bind_param('i', $orderId);

$stmt->execute();

$result = $stmt->get_result();

$orderItems = [];

while ($row = $result->fetch_assoc()) {
    $orderItems[] = $row;
}

$stmt->close();


/* =========================================================
   PRODUCT IDS FROM CURRENT ORDER
   ========================================================= */

$currentProductIds = [];

foreach ($orderItems as $item) {
    $currentProductIds[] = (int) $item['product_id'];
}


/* =========================================================
   CREATE WEEKLY STOCK FROM TEMPLATES IF NEEDED
   ========================================================= */

$stmt = $conn->prepare("
    INSERT INTO weekly_stock
    (
        farmer_id,
        product_id,
        week_start,
        planned_quantity,
        actual_quantity,
        status,
        is_active
    )
    SELECT
        wst.farmer_id,
        wst.product_id,
        ?,
        wst.default_quantity,
        wst.default_quantity,
        CASE
            WHEN wst.default_quantity <= 0
                THEN 'sold_out'
            ELSE 'available'
        END,
        1
    FROM weekly_stock_templates wst

    INNER JOIN products p
        ON p.id = wst.product_id

    WHERE wst.farmer_id = ?
      AND wst.is_active = 1
      AND p.is_available = 1
      AND p.moderation_status = 'approved'

      AND NOT EXISTS (
          SELECT 1
          FROM weekly_stock ws
          WHERE ws.farmer_id = wst.farmer_id
            AND ws.product_id = wst.product_id
            AND ws.week_start = ?
      )
");

$stmt->bind_param(
    'sis',
    $originalWeekStart,
    $order['farmer_id'],
    $originalWeekStart
);

$stmt->execute();
$stmt->close();


/* =========================================================
   GET FARMER PRODUCTS + WEEKLY STOCK
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        p.is_available,
        p.moderation_status,

        c.name AS category_name,

        ws.actual_quantity,
        ws.status AS stock_status,
        ws.is_active AS stock_active

    FROM products p

    LEFT JOIN categories c
        ON c.id = p.category_id

    INNER JOIN weekly_stock ws
        ON ws.product_id = p.id
       AND ws.farmer_id = p.farmer_id
       AND ws.week_start = ?

    WHERE p.farmer_id = ?
      AND p.is_available = 1
      AND p.moderation_status = 'approved'
      AND ws.is_active = 1

    ORDER BY p.name ASC
");

$stmt->bind_param(
    'si',
    $originalWeekStart,
    $order['farmer_id']
);

$stmt->execute();

$result = $stmt->get_result();

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();


/* =========================================================
   GET PICKUP SLOTS
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        day_of_week,
        start_time,
        end_time,
        cutoff_time,
        max_orders,
        is_available

    FROM pickup_slots

    WHERE farmer_id = ?
      AND market_id = ?
      AND is_available = 1

    ORDER BY
        FIELD(
            day_of_week,
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday'
        ),
        start_time
");

$stmt->bind_param(
    'ii',
    $order['farmer_id'],
    $order['market_id']
);

$stmt->execute();

$result = $stmt->get_result();

$pickupSlots = [];

while ($row = $result->fetch_assoc()) {
    $pickupSlots[] = $row;
}

$stmt->close();


/* =========================================================
   BUILD PICKUP DATES FOR ORIGINAL WEEK
   ========================================================= */

$pickupDates = [];

$weekStart = new DateTime($originalWeekStart);

$dayMap = [
    'Monday'    => 0,
    'Tuesday'   => 1,
    'Wednesday' => 2,
    'Thursday'  => 3,
    'Friday'    => 4,
    'Saturday'  => 5,
    'Sunday'    => 6
];

foreach ($pickupSlots as $slot) {

    $dayName = $slot['day_of_week'];

    if (!isset($dayMap[$dayName])) {
        continue;
    }

    $date = clone $weekStart;

    $date->modify('+' . $dayMap[$dayName] . ' days');

    $dateString = $date->format('Y-m-d');

    $cutoff = new DateTime(
        $dateString . ' ' . $slot['cutoff_time']
    );

    if ($cutoff <= new DateTime()) {
        continue;
    }

    $pickupDates[$dateString] = $date->format('l, F j');
}


/* =========================================================
   POST
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedQuantities = $_POST['quantities'] ?? [];

    $newPickupDate = trim($_POST['pickup_date'] ?? '');

    $newPickupSlotId = (int) ($_POST['pickup_slot_id'] ?? 0);

    $newNotes = trim($_POST['notes'] ?? '');

    /*
     * Build requested items.
     */
    $requestedItems = [];

    if (is_array($postedQuantities)) {

        foreach ($postedQuantities as $productId => $quantity) {

            $productId = (int) $productId;

            $quantity = (float) $quantity;

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $requestedItems[$productId] = $quantity;
        }
    }

    if (empty($requestedItems)) {
        $errors[] = 'Please keep at least one product in your order.';
    }


    /* =====================================================
       VALIDATE PICKUP DATE
       ===================================================== */

    if ($newPickupDate === '') {

        $errors[] = 'Please select a pickup date.';

    } elseif (!isset($pickupDates[$newPickupDate])) {

        $errors[] = 'The selected pickup date is not available.';
    }


    /* =====================================================
       VALIDATE PICKUP SLOT
       ===================================================== */

    $selectedSlot = null;

    if ($newPickupSlotId <= 0) {

        $errors[] = 'Please select a pickup slot.';

    } else {

        foreach ($pickupSlots as $slot) {

            if ((int) $slot['id'] === $newPickupSlotId) {

                $selectedSlot = $slot;
                break;
            }
        }

        if (!$selectedSlot) {
            $errors[] = 'The selected pickup slot is not available.';
        }
    }


    /* =====================================================
       CHECK SLOT MATCHES DATE
       ===================================================== */

    if (
        empty($errors) &&
        $selectedSlot &&
        $newPickupDate !== ''
    ) {

        $selectedDate = new DateTime($newPickupDate);

        if ($selectedDate->format('l') !== $selectedSlot['day_of_week']) {
            $errors[] = 'The selected pickup slot does not match the selected date.';
        }
    }


    /* =====================================================
       PROCESS UPDATE
       ===================================================== */

    if (empty($errors)) {

        try {

            $conn->begin_transaction();


            /* =============================================
               LOCK ORDER
               ============================================= */

            $stmt = $conn->prepare("
                SELECT
                    id,
                    farmer_id,
                    market_id,
                    pickup_slot_id,
                    pickup_date,
                    status,
                    created_at

                FROM orders

                WHERE id = ?
                  AND customer_id = ?

                FOR UPDATE
            ");

            $stmt->bind_param(
                'ii',
                $orderId,
                $customerId
            );

            $stmt->execute();

            $lockedOrder = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if (!$lockedOrder) {
                throw new Exception('Order could not be found.');
            }

            if ($lockedOrder['status'] !== 'pending') {
                throw new Exception('This order can no longer be modified.');
            }


            /* =============================================
               LOCK EXISTING ITEMS
               ============================================= */

            $stmt = $conn->prepare("
                SELECT
                    product_id,
                    quantity,
                    unit_price

                FROM order_items

                WHERE order_id = ?

                FOR UPDATE
            ");

            $stmt->bind_param(
                'i',
                $orderId
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $lockedItems = [];

            while ($row = $result->fetch_assoc()) {
                $lockedItems[] = $row;
            }

            $stmt->close();


            /* =============================================
               RESTORE OLD WEEKLY STOCK
               ============================================= */

            foreach ($lockedItems as $oldItem) {

                $oldProductId = (int) $oldItem['product_id'];

                $oldQuantity = (float) $oldItem['quantity'];

                $stmt = $conn->prepare("
                    UPDATE weekly_stock

                    SET
                        actual_quantity = actual_quantity + ?,
                        status = CASE
                            WHEN actual_quantity + ? > 0
                                THEN 'available'
                            ELSE status
                        END

                    WHERE farmer_id = ?
                      AND product_id = ?
                      AND week_start = ?
                      AND is_active = 1
                ");

                $stmt->bind_param(
                    'ddi is',
                    $oldQuantity,
                    $oldQuantity,
                    $lockedOrder['farmer_id'],
                    $oldProductId,
                    $originalWeekStart
                );

                /*
                 * MySQLi type string above must be valid.
                 * Use a simpler prepared statement below instead.
                 */

                $stmt->close();

                $stmt = $conn->prepare("
                    UPDATE weekly_stock

                    SET
                        actual_quantity = actual_quantity + ?,
                        status = CASE
                            WHEN actual_quantity + ? > 0
                                THEN 'available'
                            ELSE status
                        END

                    WHERE farmer_id = ?
                      AND product_id = ?
                      AND week_start = ?
                      AND is_active = 1
                ");

                $stmt->bind_param(
                    'ddi is',
                    $oldQuantity,
                    $oldQuantity,
                    $lockedOrder['farmer_id'],
                    $oldProductId,
                    $originalWeekStart
                );

                /*
                 * Correct bind below.
                 */

                $stmt->close();

                $stmt = $conn->prepare("
                    UPDATE weekly_stock

                    SET
                        actual_quantity = actual_quantity + ?,
                        status = CASE
                            WHEN actual_quantity + ? > 0
                                THEN 'available'
                            ELSE status
                        END

                    WHERE farmer_id = ?
                      AND product_id = ?
                      AND week_start = ?
                      AND is_active = 1
                ");

                $stmt->bind_param(
                    'ddiis',
                    $oldQuantity,
                    $oldQuantity,
                    $lockedOrder['farmer_id'],
                    $oldProductId,
                    $originalWeekStart
                );

                if (!$stmt->execute()) {
                    throw new Exception('Failed to restore weekly stock.');
                }

                $stmt->close();
            }


            /* =============================================
               LOAD NEW PRODUCTS
               ============================================= */

            $productData = [];

            foreach ($requestedItems as $productId => $quantity) {

                $stmt = $conn->prepare("
                    SELECT
                        p.id,
                        p.name,
                        p.price,
                        p.unit,
                        p.is_available,
                        p.moderation_status,

                        ws.actual_quantity,
                        ws.status AS stock_status

                    FROM products p

                    INNER JOIN weekly_stock ws
                        ON ws.product_id = p.id
                       AND ws.farmer_id = p.farmer_id
                       AND ws.week_start = ?

                    WHERE p.id = ?
                      AND p.farmer_id = ?
                      AND p.is_available = 1
                      AND p.moderation_status = 'approved'
                      AND ws.is_active = 1

                    FOR UPDATE
                ");

                $stmt->bind_param(
                    'sii',
                    $originalWeekStart,
                    $productId,
                    $lockedOrder['farmer_id']
                );

                $stmt->execute();

                $product = $stmt->get_result()->fetch_assoc();

                $stmt->close();

                if (!$product) {
                    throw new Exception(
                        'One of the selected products is no longer available.'
                    );
                }

                $availableStock = (float) $product['actual_quantity'];

                if ($quantity > $availableStock) {
                    throw new Exception(
                        $product['name'] .
                        ' only has ' .
                        $availableStock .
                        ' ' .
                        $product['unit'] .
                        ' available.'
                    );
                }

                $productData[$productId] = $product;
            }


            /* =============================================
               CALCULATE NEW SUBTOTAL
               ============================================= */

            $newSubtotal = 0;

            foreach ($requestedItems as $productId => $quantity) {

                $price = (float) $productData[$productId]['price'];

                $newSubtotal += $price * $quantity;
            }


            /* =============================================
               LOCK PICKUP SLOT
               ============================================= */

            $stmt = $conn->prepare("
                SELECT
                    id,
                    farmer_id,
                    market_id,
                    day_of_week,
                    start_time,
                    end_time,
                    cutoff_time,
                    max_orders,
                    is_available

                FROM pickup_slots

                WHERE id = ?
                  AND farmer_id = ?
                  AND market_id = ?
                  AND is_available = 1

                FOR UPDATE
            ");

            $stmt->bind_param(
                'iii',
                $newPickupSlotId,
                $lockedOrder['farmer_id'],
                $lockedOrder['market_id']
            );

            $stmt->execute();

            $lockedSlot = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if (!$lockedSlot) {
                throw new Exception(
                    'The selected pickup slot is no longer available.'
                );
            }


            /* =============================================
               CHECK DATE / SLOT
               ============================================= */

            $dateObject = new DateTime($newPickupDate);

            if (
                $dateObject->format('l') !==
                $lockedSlot['day_of_week']
            ) {
                throw new Exception(
                    'The selected pickup slot does not match the pickup date.'
                );
            }


            /* =============================================
               CHECK CUTOFF
               ============================================= */

            $cutoffDateTime = new DateTime(
                $newPickupDate . ' ' . $lockedSlot['cutoff_time']
            );

            if (new DateTime() >= $cutoffDateTime) {
                throw new Exception(
                    'The pickup cutoff time has already passed.'
                );
            }


            /* =============================================
               CHECK SLOT CAPACITY
               ============================================= */

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS order_count

                FROM orders

                WHERE pickup_slot_id = ?
                  AND pickup_date = ?
                  AND id <> ?
                  AND status IN (
                      'pending',
                      'accepted',
                      'preparing',
                      'ready'
                  )
            ");

            $stmt->bind_param(
                'isi',
                $newPickupSlotId,
                $newPickupDate,
                $orderId
            );

            $stmt->execute();

            $capacityResult = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            $currentOrders = (int) $capacityResult['order_count'];

            $maxOrders = (int) $lockedSlot['max_orders'];

            if (
                $maxOrders > 0 &&
                $currentOrders >= $maxOrders
            ) {
                throw new Exception(
                    'This pickup slot is already full.'
                );
            }


            /* =============================================
               DELETE OLD ITEMS
               ============================================= */

            $stmt = $conn->prepare("
                DELETE FROM order_items
                WHERE order_id = ?
            ");

            $stmt->bind_param(
                'i',
                $orderId
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Failed to update order items.'
                );
            }

            $stmt->close();


            /* =============================================
               INSERT NEW ITEMS + DEDUCT STOCK
               ============================================= */

            foreach ($requestedItems as $productId => $quantity) {

                $product = $productData[$productId];

                $unitPrice = (float) $product['price'];

                $itemSubtotal = $unitPrice * $quantity;


                /* INSERT ORDER ITEM */

                $stmt = $conn->prepare("
                    INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        quantity,
                        unit_price,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    'iiddd',
                    $orderId,
                    $productId,
                    $quantity,
                    $unitPrice,
                    $itemSubtotal
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Failed to save order items.'
                    );
                }

                $stmt->close();


                /* DEDUCT WEEKLY STOCK */

                $stmt = $conn->prepare("
                    UPDATE weekly_stock

                    SET
                        actual_quantity = actual_quantity - ?,
                        status = CASE
                            WHEN actual_quantity - ? <= 0
                                THEN 'sold_out'
                            ELSE 'available'
                        END

                    WHERE farmer_id = ?
                      AND product_id = ?
                      AND week_start = ?
                      AND is_active = 1
                      AND actual_quantity >= ?
                ");

                $stmt->bind_param(
                    'ddiis d',
                    $quantity,
                    $quantity,
                    $lockedOrder['farmer_id'],
                    $productId,
                    $originalWeekStart,
                    $quantity
                );

                /*
                 * Correct bind:
                 */

                $stmt->close();

                $stmt = $conn->prepare("
                    UPDATE weekly_stock

                    SET
                        actual_quantity = actual_quantity - ?,
                        status = CASE
                            WHEN actual_quantity - ? <= 0
                                THEN 'sold_out'
                            ELSE 'available'
                        END

                    WHERE farmer_id = ?
                      AND product_id = ?
                      AND week_start = ?
                      AND is_active = 1
                      AND actual_quantity >= ?
                ");

                $stmt->bind_param(
                    'dd iisd',
                    $quantity,
                    $quantity,
                    $lockedOrder['farmer_id'],
                    $productId,
                    $originalWeekStart,
                    $quantity
                );

                /*
                 * Final correct bind string:
                 */

                $stmt->close();

                $stmt = $conn->prepare("
                    UPDATE weekly_stock

                    SET
                        actual_quantity = actual_quantity - ?,
                        status = CASE
                            WHEN actual_quantity - ? <= 0
                                THEN 'sold_out'
                            ELSE 'available'
                        END

                    WHERE farmer_id = ?
                      AND product_id = ?
                      AND week_start = ?
                      AND is_active = 1
                      AND actual_quantity >= ?
                ");

                $stmt->bind_param(
                    'ddiis d',
                    $quantity,
                    $quantity,
                    $lockedOrder['farmer_id'],
                    $productId,
                    $originalWeekStart,
                    $quantity
                );

                /*
                 * Use explicit variables and a valid type string.
                 */

                $stmt->close();

                $stmt = $conn->prepare("
                    UPDATE weekly_stock

                    SET
                        actual_quantity = actual_quantity - ?,
                        status = CASE
                            WHEN actual_quantity - ? <= 0
                                THEN 'sold_out'
                            ELSE 'available'
                        END

                    WHERE farmer_id = ?
                      AND product_id = ?
                      AND week_start = ?
                      AND is_active = 1
                      AND actual_quantity >= ?
                ");

                $stmt->bind_param(
                    'ddiisd',
                    $quantity,
                    $quantity,
                    $lockedOrder['farmer_id'],
                    $productId,
                    $originalWeekStart,
                    $quantity
                );

                if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                    throw new Exception(
                        'Not enough weekly stock is available.'
                    );
                }

                $stmt->close();
            }


            /* =============================================
               UPDATE ORDER
               ============================================= */

            $stmt = $conn->prepare("
                UPDATE orders

                SET
                    pickup_slot_id = ?,
                    pickup_date = ?,
                    subtotal = ?,
                    notes = ?,
                    updated_at = CURRENT_TIMESTAMP

                WHERE id = ?
                  AND customer_id = ?
            ");

            $stmt->bind_param(
                'isdsii',
                $newPickupSlotId,
                $newPickupDate,
                $newSubtotal,
                $newNotes,
                $orderId,
                $customerId
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Failed to update the order.'
                );
            }

            $stmt->close();


            /* =============================================
               NOTIFICATIONS
               ============================================= */

            createNotification(
                $conn,
                (int) $lockedOrder['farmer_id'],
                'order',
                'Order Updated',
                'Customer updated order #' . $orderId . '.'
            );

            createNotification(
                $conn,
                $customerId,
                'order',
                'Order Updated',
                'Your order #' . $orderId . ' was successfully updated.'
            );


            /* =============================================
               COMMIT
               ============================================= */

            $conn->commit();

            redirect(
                'customer/orders.php?id=' . $orderId
            );

        } catch (Throwable $e) {

            $conn->rollback();

            $errors[] = $e->getMessage();
        }
    }
}


/* =========================================================
   DISPLAY VALUES
   ========================================================= */

$displayQuantities = [];

foreach ($orderItems as $item) {

    $displayQuantities[(int) $item['product_id']] =
        (float) $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($requestedItems ?? [] as $productId => $quantity) {

        $displayQuantities[(int) $productId] =
            (float) $quantity;
    }

    $displayPickupDate =
        $_POST['pickup_date'] ?? $order['pickup_date'];

    $displayPickupSlot =
        (int) ($_POST['pickup_slot_id'] ?? $order['pickup_slot_id']);

    $displayNotes =
        $_POST['notes'] ?? $order['notes'];

} else {

    $displayPickupDate =
        $order['pickup_date'];

    $displayPickupSlot =
        (int) $order['pickup_slot_id'];

    $displayNotes =
        $order['notes'];
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

    <title>
        Edit Order #<?= htmlspecialchars($orderId) ?> | MarketLink
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f8f1e7;
            color: #443223;
        }

        .page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 35px 25px 60px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;

            color: #72583e;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 32px;
            color: #443223;
        }

        .page-header p {
            margin: 0;
            color: #7c7960;
        }

        .order-info {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

            margin-bottom: 25px;
        }

        .info-card {
            background: #fff9f3;
            border: 1px solid #dbc4a5;
            border-radius: 14px;
            padding: 18px;
        }

        .info-label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #a08670;
            margin-bottom: 6px;
        }

        .info-value {
            font-weight: 700;
            color: #443223;
        }

        .alert {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #f5dfd7;
            border: 1px solid #c89a89;
            color: #755151;
        }

        .layout {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                350px;

            gap: 25px;
            align-items: start;
        }

        .card {
            background: #fff9f3;
            border: 1px solid #dbc4a5;
            border-radius: 16px;
            padding: 24px;
            box-shadow:
                0 4px 14px rgba(68, 50, 35, .06);
        }

        .card + .card {
            margin-top: 20px;
        }

        .card h2 {
            margin: 0 0 20px;
            color: #443223;
            font-size: 21px;
        }

        .product-row {
            display: grid;

            grid-template-columns:
                70px
                minmax(0, 1fr)
                120px
                90px;

            gap: 16px;

            align-items: center;

            padding: 16px 0;

            border-bottom:
                1px solid #eadbc9;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 70px;
            height: 70px;

            border-radius: 10px;

            background: #dbc4a5;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-placeholder {
            font-size: 25px;
            color: #72583e;
        }

        .product-name {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .product-meta {
            color: #7c7960;
            font-size: 13px;
        }

        .product-price {
            margin-top: 5px;
            font-size: 14px;
            color: #72583e;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quantity-control button {
            width: 30px;
            height: 30px;

            border: none;
            border-radius: 7px;

            background: #dbc4a5;
            color: #443223;

            font-weight: 700;
            cursor: pointer;
        }

        .quantity-control button:hover {
            background: #a08670;
            color: white;
        }

        .quantity-control input {
            width: 50px;
            height: 30px;

            text-align: center;

            border:
                1px solid #dbc4a5;

            border-radius: 7px;

            background: white;
            color: #443223;
        }

        .remove-btn {
            border: none;
            background: none;
            color: #755151;
            cursor: pointer;
            font-size: 13px;
            margin-top: 5px;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .add-products {
            margin-top: 22px;
            padding-top: 20px;

            border-top:
                1px solid #eadbc9;
        }

        .add-products h3 {
            margin: 0 0 12px;
            font-size: 16px;
        }

        .product-option {
            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 12px 0;

            border-bottom:
                1px solid #eadbc9;
        }

        .product-option:last-child {
            border-bottom: none;
        }

        .add-btn {
            border: none;
            background: #72583e;
            color: white;

            padding: 8px 13px;

            border-radius: 7px;

            cursor: pointer;
            font-weight: 600;
        }

        .add-btn:hover {
            background: #443223;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;

            margin-bottom: 7px;

            font-weight: 700;
            font-size: 14px;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;

            border:
                1px solid #dbc4a5;

            border-radius: 9px;

            padding: 11px 12px;

            background: white;
            color: #443223;

            font-family: inherit;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;

            padding: 10px 0;

            color: #72583e;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;

            padding-top: 18px;
            margin-top: 10px;

            border-top:
                1px solid #dbc4a5;

            font-size: 20px;
            font-weight: 800;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 22px;
        }

        .btn {
            flex: 1;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 13px 18px;

            border-radius: 9px;

            text-decoration: none;

            border: none;

            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: #72583e;
            color: white;
        }

        .btn-primary:hover {
            background: #443223;
        }

        .btn-secondary {
            background: #dbc4a5;
            color: #443223;
        }

        .btn-secondary:hover {
            background: #a08670;
            color: white;
        }

        .empty {
            padding: 20px 0;
            color: #7c7960;
        }

        @media (max-width: 850px) {

            .layout {
                grid-template-columns: 1fr;
            }

            .order-info {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 650px) {

            .product-row {
                grid-template-columns:
                    60px
                    1fr;
            }

            .product-image {
                width: 60px;
                height: 60px;
            }

            .quantity-control,
            .product-row > .remove-area {
                grid-column: 2;
            }

            .page {
                padding: 25px 15px 45px;
            }

            .card {
                padding: 18px;
            }
        }

    </style>

</head>

<body>

<div class="page">

    <a
        href="<?= htmlspecialchars(BASE_URL) ?>customer/orders.php?id=<?= $orderId ?>"
        class="back-link"
    >
        ← Back to Order #<?= $orderId ?>
    </a>


    <div class="page-header">

        <h1>Edit Order #<?= $orderId ?></h1>

        <p>
            Modify your products, pickup details, or notes before the cutoff time.
        </p>

    </div>


    <?php if (!empty($errors)): ?>

        <div class="alert">

            <?php foreach ($errors as $error): ?>

                <div>
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <div class="order-info">

        <div class="info-card">

            <span class="info-label">
                Farmer
            </span>

            <div class="info-value">
                <?= htmlspecialchars($order['stall_name']) ?>
            </div>

        </div>


        <div class="info-card">

            <span class="info-label">
                Market
            </span>

            <div class="info-value">
                <?= htmlspecialchars($order['market_name']) ?>
            </div>

        </div>


        <div class="info-card">

            <span class="info-label">
                Status
            </span>

            <div class="info-value">
                Pending
            </div>

        </div>

    </div>


    <form method="POST">

        <div class="layout">


            <!-- =========================================
                 PRODUCTS
                 ========================================= -->

            <div>

                <div class="card">

                    <h2>
                        Your Products
                    </h2>


                    <div id="selected-products">

                        <?php foreach ($products as $product): ?>

                            <?php

                            $productId = (int) $product['id'];

                            if (
                                !isset(
                                    $displayQuantities[$productId]
                                )
                            ) {
                                continue;
                            }

                            $quantity =
                                $displayQuantities[$productId];

                            ?>

                            <div
                                class="product-row"
                                data-product-id="<?= $productId ?>"
                                data-price="<?= htmlspecialchars($product['price']) ?>"
                            >

                                <div class="product-image">

                                    <?php if (!empty($product['image'])): ?>

                                        <img
                                            src="<?= htmlspecialchars(BASE_URL . $product['image']) ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?>"
                                        >

                                    <?php else: ?>

                                        <span class="product-placeholder">
                                            🥬
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div>

                                    <div class="product-name">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </div>

                                    <div class="product-meta">
                                        <?= htmlspecialchars($product['unit']) ?>
                                        ·
                                        <?= htmlspecialchars($product['category_name'] ?? 'Product') ?>
                                    </div>

                                    <div class="product-price">
                                        <?= number_format((float) $product['price'], 2) ?>
                                        per
                                        <?= htmlspecialchars($product['unit']) ?>
                                    </div>

                                    <button
                                        type="button"
                                        class="remove-btn"
                                        onclick="removeProduct(<?= $productId ?>)"
                                    >
                                        Remove
                                    </button>

                                </div>


                                <div class="quantity-control">

                                    <button
                                        type="button"
                                        onclick="changeQuantity(
                                            <?= $productId ?>,
                                            -1
                                        )"
                                    >
                                        −
                                    </button>

                                    <input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        name="quantities[<?= $productId ?>]"
                                        value="<?= htmlspecialchars($quantity) ?>"
                                        data-quantity
                                        onchange="updateTotal()"
                                    >

                                    <button
                                        type="button"
                                        onclick="changeQuantity(
                                            <?= $productId ?>,
                                            1
                                        )"
                                    >
                                        +
                                    </button>

                                </div>

                            </div>

                        <?php endforeach; ?>


                        <?php if (empty($displayQuantities)): ?>

                            <div class="empty">
                                No products selected.
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- =================================
                         ADD PRODUCTS
                         ================================= -->

                    <div class="add-products">

                        <h3>
                            Add Products
                        </h3>


                        <?php

                        $hasAvailableProducts = false;

                        foreach ($products as $product):

                            $productId =
                                (int) $product['id'];

                            if (
                                isset(
                                    $displayQuantities[$productId]
                                )
                            ) {
                                continue;
                            }

                            $available =
                                (float) $product['actual_quantity'];

                            if (
                                $available <= 0 ||
                                $product['stock_status'] === 'sold_out'
                            ) {
                                continue;
                            }

                            $hasAvailableProducts = true;

                        ?>

                            <div
                                class="product-option"
                                id="option-<?= $productId ?>"
                            >

                                <div>

                                    <strong>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </strong>

                                    <div class="product-meta">

                                        <?= number_format(
                                            (float) $product['price'],
                                            2
                                        ) ?>

                                        per
                                        <?= htmlspecialchars($product['unit']) ?>

                                        ·

                                        <?= number_format(
                                            $available,
                                            2
                                        ) ?>

                                        available

                                    </div>

                                </div>

                                <button
                                    type="button"
                                    class="add-btn"
                                    onclick="addProduct(
                                        <?= $productId ?>,
                                        <?= htmlspecialchars($product['price']) ?>,
                                        '<?= htmlspecialchars(
                                            addslashes($product['name'])
                                        ) ?>',
                                        '<?= htmlspecialchars(
                                            addslashes($product['unit'])
                                        ) ?>'
                                    )"
                                >
                                    + Add
                                </button>

                            </div>

                        <?php endforeach; ?>


                        <?php if (!$hasAvailableProducts): ?>

                            <div class="empty">
                                No additional products are currently available.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =====================================
                     NOTES
                     ===================================== -->

                <div class="card">

                    <h2>
                        Order Notes
                    </h2>

                    <div class="form-group">

                        <label for="notes">
                            Notes for the Farmer
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            placeholder="Anything you'd like the farmer to know?"
                        ><?= htmlspecialchars($displayNotes ?? '') ?></textarea>

                    </div>

                </div>

            </div>


            <!-- =========================================
                 SIDEBAR
                 ========================================= -->

            <div>


                <!-- PICKUP -->

                <div class="card">

                    <h2>
                        Pickup Details
                    </h2>


                    <div class="form-group">

                        <label for="pickup_date">
                            Pickup Date
                        </label>

                        <select
                            name="pickup_date"
                            id="pickup_date"
                            required
                            onchange="filterSlots()"
                        >

                            <option value="">
                                Select a date
                            </option>

                            <?php foreach ($pickupDates as $date => $label): ?>

                                <option
                                    value="<?= htmlspecialchars($date) ?>"
                                    <?= $displayPickupDate === $date
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars($label) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="pickup_slot_id">
                            Pickup Time
                        </label>

                        <select
                            name="pickup_slot_id"
                            id="pickup_slot_id"
                            required
                        >

                            <option value="">
                                Select a time
                            </option>

                            <?php foreach ($pickupSlots as $slot): ?>

                                <option
                                    value="<?= (int) $slot['id'] ?>"
                                    data-day="<?= htmlspecialchars($slot['day_of_week']) ?>"
                                    <?= $displayPickupSlot === (int) $slot['id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars($slot['day_of_week']) ?>

                                    ·

                                    <?= date(
                                        'g:i A',
                                        strtotime($slot['start_time'])
                                    ) ?>

                                    -

                                    <?= date(
                                        'g:i A',
                                        strtotime($slot['end_time'])
                                    ) ?>

                                    · Cutoff

                                    <?= date(
                                        'g:i A',
                                        strtotime($slot['cutoff_time'])
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="info-card">

                        <span class="info-label">
                            Pickup Location
                        </span>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $order['market_name']
                            ) ?>

                        </div>

                        <div
                            style="
                                margin-top: 6px;
                                color: #7c7960;
                                font-size: 13px;
                            "
                        >

                            <?= htmlspecialchars(
                                $order['market_address']
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- SUMMARY -->

                <div class="card">

                    <h2>
                        Order Summary
                    </h2>


                    <div class="summary-row">

                        <span>
                            Products
                        </span>

                        <span id="summary-count">
                            0
                        </span>

                    </div>


                    <div class="summary-total">

                        <span>
                            Total
                        </span>

                        <span id="summary-total">
                            0.00
                        </span>

                    </div>


                    <div class="actions">

                        <a
                            href="<?= htmlspecialchars(BASE_URL) ?>customer/orders.php?id=<?= $orderId ?>"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Save Changes
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>


<script>

function updateTotal() {

    let total = 0;
    let count = 0;

    document
        .querySelectorAll('#selected-products .product-row')
        .forEach(row => {

            const price =
                parseFloat(
                    row.dataset.price
                ) || 0;

            const input =
                row.querySelector('[data-quantity]');

            const quantity =
                parseFloat(input.value) || 0;

            total += price * quantity;

            count++;
        });


    document.getElementById(
        'summary-total'
    ).textContent =
        total.toFixed(2);


    document.getElementById(
        'summary-count'
    ).textContent =
        count;
}


function changeQuantity(productId, change) {

    const row =
        document.querySelector(
            `[data-product-id="${productId}"]`
        );

    if (!row) {
        return;
    }

    const input =
        row.querySelector('[data-quantity]');

    let value =
        parseFloat(input.value) || 0;

    value += change;

    if (value <= 0) {

        removeProduct(productId);

        return;
    }

    input.value =
        value.toFixed(2).replace(/\.00$/, '');

    updateTotal();
}


function removeProduct(productId) {

    const row =
        document.querySelector(
            `[data-product-id="${productId}"]`
        );

    if (!row) {
        return;
    }

    row.remove();

    const option =
        document.getElementById(
            `option-${productId}`
        );

    if (option) {
        option.style.display = 'flex';
    }

    updateTotal();
}


function addProduct(
    productId,
    price,
    name,
    unit
) {

    if (
        document.querySelector(
            `[data-product-id="${productId}"]`
        )
    ) {
        return;
    }


    const container =
        document.getElementById(
            'selected-products'
        );


    const row =
        document.createElement('div');

    row.className =
        'product-row';

    row.dataset.productId =
        productId;

    row.dataset.price =
        price;


    row.innerHTML = `

        <div class="product-image">

            <span class="product-placeholder">
                🥬
            </span>

        </div>


        <div>

            <div class="product-name">
                ${escapeHtml(name)}
            </div>

            <div class="product-meta">
                ${escapeHtml(unit)}
            </div>

            <div class="product-price">
                ${parseFloat(price).toFixed(2)}
                per
                ${escapeHtml(unit)}
            </div>

            <button
                type="button"
                class="remove-btn"
                onclick="removeProduct(${productId})"
            >
                Remove
            </button>

        </div>


        <div class="quantity-control">

            <button
                type="button"
                onclick="changeQuantity(
                    ${productId},
                    -1
                )"
            >
                −
            </button>

            <input
                type="number"
                min="0.01"
                step="0.01"
                name="quantities[${productId}]"
                value="1"
                data-quantity
                onchange="updateTotal()"
            >

            <button
                type="button"
                onclick="changeQuantity(
                    ${productId},
                    1
                )"
            >
                +
            </button>

        </div>

    `;


    container.appendChild(row);


    const option =
        document.getElementById(
            `option-${productId}`
        );

    if (option) {
        option.style.display = 'none';
    }


    updateTotal();
}


function escapeHtml(value) {

    const div =
        document.createElement('div');

    div.textContent =
        value;

    return div.innerHTML;
}


function filterSlots() {

    const date =
        document.getElementById(
            'pickup_date'
        ).value;

    const slotSelect =
        document.getElementById(
            'pickup_slot_id'
        );

    if (!date) {
        return;
    }


    const selectedDate =
        new Date(date + 'T12:00:00');

    const days = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ];

    const dayName =
        days[selectedDate.getDay()];


    Array.from(
        slotSelect.options
    ).forEach(option => {

        if (!option.value) {
            return;
        }

        const slotDay =
            option.dataset.day;

        if (slotDay === dayName) {

            option.hidden = false;

        } else {

            option.hidden = true;

            if (option.selected) {
                option.selected = false;
            }
        }

    });
}


document.addEventListener(
    'DOMContentLoaded',
    function () {

        updateTotal();

        filterSlots();

    }
);

</script>

</body>

</html>