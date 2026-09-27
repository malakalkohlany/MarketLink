<?php
require_once __DIR__ . '/../includes/include.php';
requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

$cart = [];

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart'])
) {
    $cart = $_SESSION['cart'];
}

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$error = '';

$products = [];
$cartItems = [];

$cartSubtotal = 0;

$farmerId = null;
$cartMarketId = null;
$cartMarketName = null;

$pickupSlots = [];
$pickupDates = [];

$selectedPickupDate = '';
$selectedPickupSlotId = 0;
$notes = '';

$today = new DateTime();

$weekStart = clone $today;

if ($weekStart->format('N') != 1) {
    $weekStart->modify('monday this week');
}

$weekStartDate = $weekStart->format('Y-m-d');

$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

$weekEndDate = $weekEnd->format('Y-m-d');

foreach ($cart as $item) {
    if (
        isset($item['market_id']) &&
        (int) $item['market_id'] > 0
    ) {
        $cartMarketId = (int) $item['market_id'];

        if (
            isset($item['market_name']) &&
            trim((string) $item['market_name']) !== ''
        ) {
            $cartMarketName = trim(
                (string) $item['market_name']
            );
        }

        break;
    }
}

$productIds = [];

foreach ($cart as $item) {
    $productId = (int) (
        $item['product_id'] ?? 0
    );

    if ($productId > 0) {
        $productIds[] = $productId;
    }
}

$productIds = array_values(
    array_unique($productIds)
);

if (empty($productIds)) {
    $error = 'Your cart contains invalid products.';
} else {
    $productStmt = $conn->prepare("
        SELECT
            id,
            farmer_id,
            name,
            description,
            price,
            unit,
            stock_quantity,
            image,
            is_available,
            moderation_status
        FROM products
        WHERE id = ?
    ");

    if (!$productStmt) {
        $error = 'Unable to load products.';
    } else {
        foreach ($productIds as $productId) {
            $productStmt->bind_param(
                "i",
                $productId
            );

            $productStmt->execute();

            $productResult =
                $productStmt->get_result();

            if (
                $product =
                    $productResult->fetch_assoc()
            ) {
                $products[$productId] = $product;
            }
        }

        $productStmt->close();
    }
}

if (empty($error)) {
    foreach ($cart as $item) {
        $productId = (int) (
            $item['product_id'] ?? 0
        );

        $quantity = (float) (
            $item['quantity'] ?? 0
        );

        if (!isset($products[$productId])) {
            $error =
                'One of the products in your cart no longer exists.';
            break;
        }

        $product = $products[$productId];

        if (
            (int) $product['is_available'] !== 1 ||
            strtolower(
                (string) $product['moderation_status']
            ) !== 'approved'
        ) {
            $error =
                'The product "' .
                htmlspecialchars(
                    $product['name']
                ) .
                '" is no longer available.';
            break;
        }

        if ($quantity <= 0) {
            $error =
                'Invalid quantity for one of the products.';
            break;
        }

        $productFarmerId =
            (int) $product['farmer_id'];

        if ($farmerId === null) {
            $farmerId =
                $productFarmerId;
        } elseif (
            $farmerId !== $productFarmerId
        ) {
            $error =
                'Products from different farmers cannot be placed in the same order. Please place separate orders.';
            break;
        }

        $itemMarketId = (int) (
            $item['market_id'] ?? 0
        );

        if ($itemMarketId <= 0) {
            $error =
                'Your cart contains an item without a valid market. Please clear your cart and add the products again.';
            break;
        }

        if ($cartMarketId === null) {
            $cartMarketId =
                $itemMarketId;
        } elseif (
            $cartMarketId !== $itemMarketId
        ) {
            $error =
                'Your cart contains products from different markets. Please clear your cart and create a new order.';
            break;
        }

        $unitPrice =
            (float) $product['price'];

        $itemSubtotal =
            $quantity * $unitPrice;

        $cartSubtotal +=
            $itemSubtotal;

        $cartItems[] = [
            'product_id' =>
                $productId,
            'name' =>
                $product['name'],
            'quantity' =>
                $quantity,
            'unit' =>
                $product['unit'],
            'unit_price' =>
                $unitPrice,
            'subtotal' =>
                $itemSubtotal,
            'image' =>
                $product['image']
        ];
    }
}

if (
    empty($error) &&
    $farmerId !== null &&
    $cartMarketId !== null
) {
    $marketStmt = $conn->prepare("
        SELECT
            m.id,
            m.name
        FROM market_farmer mf
        INNER JOIN markets m
            ON mf.market_id = m.id
        WHERE mf.farmer_id = ?
          AND mf.market_id = ?
          AND m.status = 'active'
        LIMIT 1
    ");

    if (!$marketStmt) {
        $error =
            'Unable to verify the selected market.';
    } else {
        $marketStmt->bind_param(
            "ii",
            $farmerId,
            $cartMarketId
        );

        $marketStmt->execute();

        $marketResult =
            $marketStmt->get_result();

        $cartMarket =
            $marketResult->fetch_assoc();

        $marketStmt->close();

        if (!$cartMarket) {
            $error =
                'The market selected for your cart is no longer available for this farmer.';
        } else {
            $cartMarketName =
                $cartMarket['name'];
        }
    }
}

if (
    empty($error) &&
    $farmerId !== null
) {
    $generateStmt = $conn->prepare("
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

    if (!$generateStmt) {
        $error =
            'Unable to prepare weekly stock.';
    } else {
        $generateStmt->bind_param(
            "sis",
            $weekStartDate,
            $farmerId,
            $weekStartDate
        );

        if (!$generateStmt->execute()) {
            $error =
                'Unable to prepare this week\'s stock.';
        }

        $generateStmt->close();
    }
}

if (
    empty($error) &&
    $farmerId !== null &&
    $cartMarketId !== null
) {
    $slotStmt = $conn->prepare("
        SELECT
            ps.id,
            ps.farmer_id,
            ps.market_id,
            ps.day_of_week,
            ps.start_time,
            ps.end_time,
            ps.cutoff_time,
            ps.max_orders,
            m.name AS market_name
        FROM pickup_slots ps
        INNER JOIN markets m
            ON m.id = ps.market_id
        WHERE ps.farmer_id = ?
          AND ps.market_id = ?
          AND ps.is_available = 1
          AND m.status = 'active'
        ORDER BY
            FIELD(
                ps.day_of_week,
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday'
            ),
            ps.start_time ASC
    ");

    if (!$slotStmt) {
        $error = 'Unable to load pickup slots.';
    } else {
        $slotStmt->bind_param(
            "ii",
            $farmerId,
            $cartMarketId
        );

        if (!$slotStmt->execute()) {
            $error = 'Unable to load pickup slots.';
        } else {
            $slotResult = $slotStmt->get_result();

            while ($slot = $slotResult->fetch_assoc()) {
                $pickupSlots[] = $slot;
            }
        }

        $slotStmt->close();
    }
}

if (
    empty($error) &&
    !empty($pickupSlots)
) {
    $dayOffsets = [
        'Monday' => 0,
        'Tuesday' => 1,
        'Wednesday' => 2,
        'Thursday' => 3,
        'Friday' => 4,
        'Saturday' => 5,
        'Sunday' => 6
    ];

    $todayDate = $today->format('Y-m-d');
    $nowTime = $today->format('H:i:s');

    foreach ($pickupSlots as &$slot) {
        $dayName = $slot['day_of_week'];

        if (!isset($dayOffsets[$dayName])) {
            $slot['pickup_date'] = null;
            $slot['pickup_date_label'] = null;
            $slot['is_expired'] = true;
            continue;
        }

        $slotDate = clone $weekStart;

        $slotDate->modify(
            '+' . $dayOffsets[$dayName] . ' days'
        );

        $slotDateValue = $slotDate->format('Y-m-d');

        $slot['pickup_date'] = $slotDateValue;

        $slot['pickup_date_label'] =
            $slotDate->format('l, F j, Y');

        $slot['is_expired'] = false;

        if ($slotDateValue < $todayDate) {
            $slot['is_expired'] = true;
            continue;
        }

        if (
            $slotDateValue === $todayDate &&
            !empty($slot['cutoff_time'])
        ) {
            if ($nowTime >= $slot['cutoff_time']) {
                $slot['is_expired'] = true;
                continue;
            }
        }
    }

    unset($slot);

    $pickupSlots = array_values(
        array_filter(
            $pickupSlots,
            function ($slot) {
                return empty($slot['is_expired']);
            }
        )
    );

    foreach ($pickupSlots as $slot) {
        $dateValue = $slot['pickup_date'];

        if (!$dateValue) {
            continue;
        }

        if (!isset($pickupDates[$dateValue])) {
            $pickupDates[$dateValue] = [
                'date' => $dateValue,
                'label' => $slot['pickup_date_label']
            ];
        }
    }

    ksort($pickupDates);

    $pickupDates = array_values($pickupDates);
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($error)
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid CSRF token.';
    } else {

        $selectedPickupSlotId =
        (int) (
            $_POST['pickup_slot_id'] ?? 0
        );

    $selectedPickupDate =
        trim(
            $_POST['pickup_date'] ?? ''
        );

    $notes =
        trim(
            $_POST['notes'] ?? ''
        );

    if ($selectedPickupDate === '') {
        $error =
            'Please select a pickup date.';
    } else {
        $pickupDateObject =
            DateTime::createFromFormat(
                'Y-m-d',
                $selectedPickupDate
            );

        $dateIsValid =
            $pickupDateObject &&
            $pickupDateObject->format('Y-m-d') ===
                $selectedPickupDate;

        if (!$dateIsValid) {
            $error =
                'The selected pickup date is invalid.';
        } elseif (
            $selectedPickupDate <
            $today->format('Y-m-d')
        ) {
            $error =
                'The selected pickup date has already passed.';
        } elseif (
            $selectedPickupDate <
            $weekStartDate ||
            $selectedPickupDate >
            $weekEndDate
        ) {
            $error =
                'Pickup must be scheduled within the current week.';
        }
    }

    if (
        empty($error) &&
        $selectedPickupSlotId <= 0
    ) {
        $error =
            'Please select a pickup slot.';
    }

    if (
        empty($error) &&
        strlen($notes) > 1000
    ) {
        $error =
            'Notes cannot exceed 1000 characters.';
    }

    $selectedSlotFromForm = null;

    if (empty($error)) {
        foreach ($pickupSlots as $slot) {
            if (
                (int)$slot['id'] ===
                $selectedPickupSlotId
            ) {
                $selectedSlotFromForm = $slot;
                break;
            }
        }

        if (!$selectedSlotFromForm) {
            $error =
                'The selected pickup slot is no longer available.';
        } elseif (
            $selectedSlotFromForm['pickup_date'] !==
            $selectedPickupDate
        ) {
            $error =
                'The selected pickup date does not match the selected pickup slot.';
        } elseif (
            !empty($selectedSlotFromForm['cutoff_time']) &&
            $selectedPickupDate === $today->format('Y-m-d') &&
            $today->format('H:i:s') >=
                $selectedSlotFromForm['cutoff_time']
        ) {
            $error =
                'The cutoff time for this pickup slot has passed. Please select another pickup slot.';
        }
    }

    if (empty($error)) {
        $transactionStarted = false;

        try {
            $conn->begin_transaction();

            $transactionStarted = true;

            $slotStmt = $conn->prepare("
                SELECT
                    id,
                    market_id,
                    day_of_week,
                    start_time,
                    end_time,
                    cutoff_time,
                    max_orders
                FROM pickup_slots
                WHERE id = ?
                  AND farmer_id = ?
                  AND market_id = ?
                  AND is_available = 1
                FOR UPDATE
            ");

            if (!$slotStmt) {
                throw new Exception(
                    'Unable to verify the pickup slot.'
                );
            }

            $slotStmt->bind_param(
                "iii",
                $selectedPickupSlotId,
                $farmerId,
                $cartMarketId
            );

            $slotStmt->execute();

            $slotResult =
                $slotStmt->get_result();

            $selectedSlot =
                $slotResult->fetch_assoc();

            $slotStmt->close();

            if (!$selectedSlot) {
                throw new Exception(
                    'The selected pickup slot is no longer available.'
                );
            }

            $dayOffsets = [
                'Monday' => 0,
                'Tuesday' => 1,
                'Wednesday' => 2,
                'Thursday' => 3,
                'Friday' => 4,
                'Saturday' => 5,
                'Sunday' => 6
            ];

            if (
                !isset(
                    $dayOffsets[
                        $selectedSlot['day_of_week']
                    ]
                )
            ) {
                throw new Exception(
                    'The selected pickup day is invalid.'
                );
            }

            $expectedPickupDate = clone $weekStart;

            $expectedPickupDate->modify(
                '+' .
                $dayOffsets[
                    $selectedSlot['day_of_week']
                ] .
                ' days'
            );

            $expectedPickupDate =
                $expectedPickupDate->format('Y-m-d');

            if (
                $selectedPickupDate !==
                $expectedPickupDate
            ) {
                throw new Exception(
                    'The selected pickup date does not match the selected pickup slot.'
                );
            }

            $currentDate =
                $today->format('Y-m-d');

            if (
                $selectedPickupDate ===
                $currentDate &&
                !empty($selectedSlot['cutoff_time'])
            ) {
                $nowTime =
                    date('H:i:s');

                if (
                    $nowTime >=
                    $selectedSlot['cutoff_time']
                ) {
                    throw new Exception(
                        'The cutoff time for this pickup slot has passed. Please select another pickup slot.'
                    );
                }
            }

            $countStmt = $conn->prepare("
                SELECT
                    COUNT(*) AS order_count
                FROM orders
                WHERE pickup_slot_id = ?
                  AND pickup_date = ?
                  AND status IN (
                      'pending',
                      'accepted',
                      'preparing',
                      'ready'
                  )
            ");

            if (!$countStmt) {
                throw new Exception(
                    'Unable to check pickup slot capacity.'
                );
            }

            $countStmt->bind_param(
                "is",
                $selectedPickupSlotId,
                $selectedPickupDate
            );

            $countStmt->execute();

            $countResult =
                $countStmt->get_result();

            $countRow =
                $countResult->fetch_assoc();

            $countStmt->close();

            $currentOrders =
                (int) (
                    $countRow['order_count'] ?? 0
                );

            $maxOrders =
                $selectedSlot['max_orders'];

            if (
                $maxOrders !== null &&
                $currentOrders >=
                (int) $maxOrders
            ) {
                throw new Exception(
                    'This pickup slot is already full. Please select another slot.'
                );
            }

            $marketStmt = $conn->prepare("
                SELECT
                    m.id,
                    m.name
                FROM market_farmer mf
                INNER JOIN markets m
                    ON mf.market_id = m.id
                WHERE mf.farmer_id = ?
                  AND mf.market_id = ?
                  AND m.status = 'active'
                FOR UPDATE
            ");

            if (!$marketStmt) {
                throw new Exception(
                    'Unable to verify the market.'
                );
            }

            $marketStmt->bind_param(
                "ii",
                $farmerId,
                $cartMarketId
            );

            $marketStmt->execute();

            $marketResult =
                $marketStmt->get_result();

            $selectedMarket =
                $marketResult->fetch_assoc();

            $marketStmt->close();

            if (!$selectedMarket) {
                throw new Exception(
                    'The selected market is no longer available.'
                );
            }

            $stockStmt = $conn->prepare("
                SELECT
                    ws.id,
                    ws.actual_quantity,
                    ws.status,
                    ws.is_active,
                    p.name,
                    p.price,
                    p.farmer_id,
                    p.is_available,
                    p.moderation_status
                FROM weekly_stock ws
                INNER JOIN products p
                    ON p.id = ws.product_id
                WHERE ws.product_id = ?
                  AND ws.farmer_id = ?
                  AND ws.week_start = ?
                FOR UPDATE
            ");

            if (!$stockStmt) {
                throw new Exception(
                    'Unable to verify weekly stock.'
                );
            }

            foreach ($cartItems as &$cartItem) {
                $productId =
                    (int) $cartItem['product_id'];

                $stockStmt->bind_param(
                    "iis",
                    $productId,
                    $farmerId,
                    $weekStartDate
                );

                $stockStmt->execute();

                $stockResult =
                    $stockStmt->get_result();

                $currentStock =
                    $stockResult->fetch_assoc();

                if (!$currentStock) {
                    throw new Exception(
                        'Weekly stock for "' .
                        $cartItem['name'] .
                        '" is no longer available.'
                    );
                }

                if (
                    (int) $currentStock['is_available'] !== 1 ||
                    strtolower(
                        (string)
                        $currentStock['moderation_status']
                    ) !== 'approved'
                ) {
                    throw new Exception(
                        'The product "' .
                        $cartItem['name'] .
                        '" is no longer available.'
                    );
                }

                if (
                    (int) $currentStock['farmer_id'] !==
                    $farmerId
                ) {
                    throw new Exception(
                        'Product farmer information has changed.'
                    );
                }

                if (
                    (int) $currentStock['is_active'] !== 1
                ) {
                    throw new Exception(
                        'The weekly stock for "' .
                        $cartItem['name'] .
                        '" is no longer active.'
                    );
                }

                $weeklyStatus =
                    strtolower(
                        (string)
                        $currentStock['status']
                    );

                if ($weeklyStatus === 'sold_out') {
                    throw new Exception(
                        '"' .
                        $cartItem['name'] .
                        '" is sold out for this week.'
                    );
                }

                if ($weeklyStatus === 'unavailable') {
                    throw new Exception(
                        '"' .
                        $cartItem['name'] .
                        '" is temporarily unavailable this week.'
                    );
                }

                if ($weeklyStatus !== 'available') {
                    throw new Exception(
                        '"' .
                        $cartItem['name'] .
                        '" cannot currently be ordered.'
                    );
                }

                $quantity =
                    (float) $cartItem['quantity'];

                $availableStock =
                    (float)
                    $currentStock['actual_quantity'];

                if ($availableStock <= 0) {
                    throw new Exception(
                        '"' .
                        $cartItem['name'] .
                        '" is sold out for this week.'
                    );
                }

                if (
                    $quantity >
                    $availableStock
                ) {
                    throw new Exception(
                        'Not enough weekly stock is available for "' .
                        $cartItem['name'] .
                        '". Only ' .
                        number_format(
                            $availableStock,
                            2
                        ) .
                        ' ' .
                        $cartItem['unit'] .
                        ' remaining.'
                    );
                }

                $currentPrice =
                    (float)
                    $currentStock['price'];

                $cartItem['unit_price'] =
                    $currentPrice;

                $cartItem['subtotal'] =
                    $quantity *
                    $currentPrice;

                $cartItem['weekly_stock_id'] =
                    (int)
                    $currentStock['id'];

                $cartItem['available_stock'] =
                    $availableStock;
            }

            unset($cartItem);

            $stockStmt->close();

            $cartSubtotal = 0;

            foreach ($cartItems as $cartItem) {
                $cartSubtotal +=
                    (float)
                    $cartItem['subtotal'];
            }

            $status = 'pending';

            $marketId =
                (int)
                $selectedSlot['market_id'];

            $orderStmt = $conn->prepare("
                INSERT INTO orders
                (
                    customer_id,
                    farmer_id,
                    market_id,
                    pickup_slot_id,
                    pickup_date,
                    status,
                    subtotal,
                    notes
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            if (!$orderStmt) {
                throw new Exception(
                    'Unable to create the order.'
                );
            }

            $orderStmt->bind_param(
                "iiiissds",
                $customerId,
                $farmerId,
                $marketId,
                $selectedPickupSlotId,
                $selectedPickupDate,
                $status,
                $cartSubtotal,
                $notes
            );

            if (!$orderStmt->execute()) {
                throw new Exception(
                    'Unable to save the order.'
                );
            }

            $orderId =
                (int) $conn->insert_id;

            $orderStmt->close();

            $itemStmt = $conn->prepare("
                INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    quantity,
                    unit_price,
                    subtotal
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            if (!$itemStmt) {
                throw new Exception(
                    'Unable to save order items.'
                );
            }

            foreach ($cartItems as $cartItem) {
                $productId =
                    (int)
                    $cartItem['product_id'];

                $quantity =
                    (float)
                    $cartItem['quantity'];

                $unitPrice =
                    (float)
                    $cartItem['unit_price'];

                $itemSubtotal =
                    (float)
                    $cartItem['subtotal'];

                $itemStmt->bind_param(
                    "iiddd",
                    $orderId,
                    $productId,
                    $quantity,
                    $unitPrice,
                    $itemSubtotal
                );

                if (!$itemStmt->execute()) {
                    throw new Exception(
                        'Unable to save one of the order items.'
                    );
                }
            }

            $itemStmt->close();

            $updateStockStmt = $conn->prepare("
                UPDATE weekly_stock
                SET
                    actual_quantity =
                        actual_quantity - ?,
                    status =
                        CASE
                            WHEN actual_quantity - ? <= 0
                                THEN 'sold_out'
                            ELSE status
                        END
                WHERE id = ?
                  AND actual_quantity >= ?
            ");

            if (!$updateStockStmt) {
                throw new Exception(
                    'Unable to update weekly stock.'
                );
            }

            foreach ($cartItems as $cartItem) {
                $quantity =
                    (float)
                    $cartItem['quantity'];

                $weeklyStockId =
                    (int)
                    $cartItem['weekly_stock_id'];

                $availableStock =
                    (float)
                    $cartItem['available_stock'];

                $updateStockStmt->bind_param(
                    "ddid",
                    $quantity,
                    $quantity,
                    $weeklyStockId,
                    $quantity
                );

                if (
                    !$updateStockStmt->execute() ||
                    $updateStockStmt->affected_rows !== 1
                ) {
                    throw new Exception(
                        'Weekly stock changed while placing your order. Please try again.'
                    );
                }
            }

            $updateStockStmt->close();

            $conn->commit();

            $transactionStarted = false;

            $farmerUserStmt = $conn->prepare("
                SELECT user_id
                FROM farmers
                WHERE id = ?
                LIMIT 1
            ");

            if ($farmerUserStmt) {
                $farmerUserStmt->bind_param(
                    "i",
                    $farmerId
                );

                $farmerUserStmt->execute();

                $farmerUserResult =
                    $farmerUserStmt->get_result();

                $farmerUser =
                    $farmerUserResult->fetch_assoc();

                $farmerUserStmt->close();

                if ($farmerUser) {
                    createNotification(
                        $conn,
                        (int)
                        $farmerUser['user_id'],
                        'new_order',
                        'New Order Received',
                        "You received a new order (#{$orderId}). Please review it and prepare it for pickup."
                    );
                }
            }

            createNotification(
                $conn,
                $customerId,
                'order_confirmation',
                'Order Confirmed',
                "Your order (#{$orderId}) has been placed successfully and is pending farmer confirmation."
            );

            $_SESSION['cart'] = [];

            $_SESSION['order_success'] =
                'Order #' .
                $orderId .
                ' has been placed successfully.';

            header('Location: orders.php');
            exit;
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $conn->rollback();
            }

            $error =
                $e->getMessage();
        }
    }
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
    <title>Confirm Order - MarketLink</title>

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

        .confirm-container {
            width: 92%;
            max-width: 1100px;
            margin: 40px auto 60px;
        }

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

        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .market-notice {
            background: #f5eee7;
            color: #72583e;
            border: 1px solid #dbc4a5;
            border-radius: 10px;
            padding: 13px 15px;
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 14px;
        }

        .market-notice strong {
            color: #443223;
        }

        .confirm-layout {
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 25px;
            align-items: start;
        }

        .confirm-card,
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.07);
        }

        .card-title {
            margin: 0 0 20px;
            font-size: 20px;
            color: #222;
        }

        .confirm-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eeeeee;
        }

        .confirm-item:first-of-type {
            padding-top: 0;
        }

        .confirm-image {
            width: 65px;
            height: 65px;
            border-radius: 10px;
            object-fit: cover;
            background: #eeeeee;
            flex-shrink: 0;
        }

        .confirm-no-image {
            width: 65px;
            height: 65px;
            border-radius: 10px;
            background: #eeeeee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 11px;
            flex-shrink: 0;
        }

        .confirm-item-info {
            flex: 1;
        }

        .confirm-product-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 6px;
        }

        .confirm-product-details {
            color: #888;
            font-size: 14px;
        }

        .confirm-item-total {
            font-weight: bold;
            color: #333;
            min-width: 90px;
            text-align: right;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }

        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            color: #333;
            outline: none;
        }

        .form-select:focus,
        .form-textarea:focus {
            border-color: #72583e;
        }

        .form-textarea {
            min-height: 110px;
            resize: vertical;
        }

        .form-help {
            margin-top: 7px;
            color: #888;
            font-size: 13px;
        }

        .summary-card {
            position: sticky;
            top: 25px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            color: #666;
            font-size: 15px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 18px;
            border-top: 1px solid #eeeeee;
        }

        .summary-total-label {
            font-size: 16px;
            font-weight: bold;
            color: #444;
        }

        .summary-total-price {
            font-size: 24px;
            font-weight: bold;
            color: #72583e;
        }

        .confirm-button {
            width: 100%;
            border: none;
            padding: 14px 20px;
            margin-top: 20px;
            background: #72583e;
            color: white;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .confirm-button:hover {
            background: #443223;
        }

        .back-button {
            display: block;
            text-align: center;
            margin-top: 12px;
            padding: 12px 20px;
            background: #f1f2f6;
            color: #444;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .back-button:hover {
            background: #e5e7eb;
        }

        .no-slots {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 12px;
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 800px) {
            .confirm-container {
                width: 94%;
                margin-top: 25px;
            }

            .confirm-layout {
                grid-template-columns: 1fr;
            }

            .summary-card {
                position: static;
            }
        }

        @media (max-width: 600px) {
            .confirm-card,
            .summary-card {
                padding: 20px;
            }

            .confirm-item {
                align-items: flex-start;
            }

            .confirm-item-total {
                min-width: auto;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="confirm-container">
            <div class="page-header">
                <h1>
                    Confirm Order
                </h1>

                <p>
                    Review your order and select a pickup date and time.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($cartMarketId !== null): ?>
                <div class="market-notice">
                    <strong>Pickup Market:</strong>
                    <?= htmlspecialchars(
                        $cartMarketName ??
                        'Selected Market'
                    ) ?>

                    <br>

                    All products in this order must be collected
                    from this market.
                </div>
            <?php endif; ?>

            <div class="confirm-layout">
                <div class="confirm-card">
                    <h2 class="card-title">
                        Order Details
                    </h2>

                    <?php foreach ($cartItems as $item): ?>
                        <div class="confirm-item">
                            <?php if (!empty($item['image'])): ?>
                                <img
                                    src="../uploads/products/<?= htmlspecialchars($item['image']) ?>"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                    class="confirm-image"
                                >
                            <?php else: ?>
                                <div class="confirm-no-image">
                                    No Image
                                </div>
                            <?php endif; ?>

                            <div class="confirm-item-info">
                                <div class="confirm-product-name">
                                    <?= htmlspecialchars(
                                        $item['name']
                                    ) ?>
                                </div>

                                <div class="confirm-product-details">
                                    Quantity:
                                    <?= number_format(
                                        (float) $item['quantity'],
                                        2
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $item['unit'] ?? ''
                                    ) ?>

                                    × $

                                    <?= number_format(
                                        (float) $item['unit_price'],
                                        2
                                    ) ?>
                                </div>
                            </div>

                            <div class="confirm-item-total">
                                $
                                <?= number_format(
                                    (float) $item['subtotal'],
                                    2
                                ) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-card">
                    <h2 class="card-title">
                        Order Summary
                    </h2>

                    <form method="POST">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label
                                for="pickup_date"
                                class="form-label"
                            >
                                Pickup Date
                            </label>

                            <?php if (!empty($pickupDates)): ?>
                                <select
                                    name="pickup_date"
                                    id="pickup_date"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        Select a pickup date
                                    </option>

                                    <?php foreach ($pickupDates as $date): ?>
                                        <option
                                            value="<?= htmlspecialchars($date['date']) ?>"
                                            <?=
                                                $selectedPickupDate ===
                                                $date['date']
                                                    ? 'selected'
                                                    : ''
                                            ?>
                                        >
                                            <?= htmlspecialchars(
                                                $date['label']
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="form-help">
                                    Pickup dates are based on the farmer's
                                    available pickup days this week.
                                </div>
                            <?php else: ?>
                                <div class="no-slots">
                                    No pickup dates are currently
                                    available for this farmer.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label
                                for="pickup_slot_id"
                                class="form-label"
                            >
                                Pickup Time
                            </label>

                            <?php if (!empty($pickupSlots)): ?>
                                <select
                                    name="pickup_slot_id"
                                    id="pickup_slot_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        Select a pickup time
                                    </option>

                                    <?php foreach ($pickupSlots as $slot): ?>
                                        <option
                                            value="<?= (int) $slot['id'] ?>"
                                            data-day="<?= htmlspecialchars($slot['day_of_week']) ?>"
                                            <?=
                                                $selectedPickupSlotId ===
                                                (int) $slot['id']
                                                    ? 'selected'
                                                    : ''
                                            ?>
                                        >
                                            <?= htmlspecialchars(
                                                $slot['pickup_date_label']
                                            ) ?>

                                            -

                                            <?= date(
                                                'h:i A',
                                                strtotime($slot['start_time'])
                                            ) ?>

                                            to

                                            <?= date(
                                                'h:i A',
                                                strtotime($slot['end_time'])
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="form-help">
                                    Select the pickup time that matches
                                    your selected pickup day.
                                </div>
                            <?php else: ?>
                                <div class="no-slots">
                                    No pickup slots are currently
                                    available for this farmer.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label
                                for="notes"
                                class="form-label"
                            >
                                Notes
                            </label>

                            <textarea
                                name="notes"
                                id="notes"
                                class="form-textarea"
                                maxlength="1000"
                                placeholder="Add any notes for your order..."
                            ><?= htmlspecialchars($notes) ?></textarea>

                            <div class="form-help">
                                Optional. Maximum 1000 characters.
                            </div>
                        </div>

                        <div class="summary-row">
                            <span>
                                Items
                            </span>

                            <span>
                                <?= count($cartItems) ?>
                            </span>
                        </div>

                        <div class="summary-row">
                            <span>
                                Market
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $cartMarketName ??
                                    'Selected Market'
                                ) ?>
                            </span>
                        </div>

                        <div class="summary-row">
                            <span>
                                Subtotal
                            </span>

                            <span>
                                $
                                <?= number_format(
                                    $cartSubtotal,
                                    2
                                ) ?>
                            </span>
                        </div>

                        <div class="summary-total">
                            <span class="summary-total-label">
                                Order Total
                            </span>

                            <span class="summary-total-price">
                                $
                                <?= number_format(
                                    $cartSubtotal,
                                    2
                                ) ?>
                            </span>
                        </div>

                        <?php if (!empty($pickupSlots)): ?>
                            <button
                                type="submit"
                                class="confirm-button"
                            >
                                Confirm Order
                            </button>
                        <?php endif; ?>

                        <a
                            href="cart.php"
                            class="back-button"
                        >
                            Back to Cart
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        const pickupDate =
            document.getElementById('pickup_date');

        const pickupSlot =
            document.getElementById('pickup_slot_id');

        function updatePickupSlots() {
            if (!pickupDate || !pickupSlot) {
                return;
            }

            const selectedDate =
                pickupDate.value;

            if (!selectedDate) {
                return;
            }

            const date =
                new Date(
                    selectedDate + 'T00:00:00'
                );

            const dayNames = [
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday'
            ];

            const selectedDay =
                dayNames[date.getDay()];

            let selectedStillValid = false;

            Array.from(
                pickupSlot.options
            ).forEach(function(option, index) {
                if (index === 0) {
                    return;
                }

                const optionDay =
                    option.dataset.day;

                const matches =
                    optionDay === selectedDay;

                option.hidden =
                    !matches;

                option.disabled =
                    !matches;

                if (
                    matches &&
                    option.selected
                ) {
                    selectedStillValid = true;
                }
            });

            if (!selectedStillValid) {
                const currentOption =
                    pickupSlot.options[
                        pickupSlot.selectedIndex
                    ];

                if (
                    currentOption &&
                    currentOption.disabled
                ) {
                    pickupSlot.value = '';
                }
            }
        }

        if (pickupDate) {
            pickupDate.addEventListener(
                'change',
                updatePickupSlots
            );
        }

        updatePickupSlots();
    </script>
</body>
</html>