<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

// ======================================================
// Get Customer + Order ID
// ======================================================

$customerId = (int) getUserId();
$orderId    = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($orderId <= 0) {
    $_SESSION['error'] = 'Invalid order.';
    redirect('customer/orders.php');
}

// ======================================================
// Get Completed Order
// ======================================================

$stmt = $conn->prepare("
    SELECT
        o.id,
        o.customer_id,
        o.farmer_id,
        o.market_id,
        o.status,
        o.subtotal
    FROM orders o
    WHERE o.id = ?
      AND o.customer_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $orderId,
    $customerId
);

$stmt->execute();

$orderResult = $stmt->get_result();
$order = $orderResult->fetch_assoc();

$stmt->close();

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    redirect('customer/orders.php');
}

// ======================================================
// Only Completed Orders Can Be Reordered
// ======================================================

if ($order['status'] !== 'completed') {
    $_SESSION['error'] = 'Only completed orders can be reordered.';
    redirect('customer/orders.php');
}

$farmerId = (int) $order['farmer_id'];
$marketId = (int) $order['market_id'];

// ======================================================
// Get Farmer
// ======================================================

$farmerStmt = $conn->prepare("
    SELECT
        id,
        stall_name,
        approval_status
    FROM farmers
    WHERE id = ?
    LIMIT 1
");

$farmerStmt->bind_param(
    "i",
    $farmerId
);

$farmerStmt->execute();

$farmerResult = $farmerStmt->get_result();
$farmer = $farmerResult->fetch_assoc();

$farmerStmt->close();

if (!$farmer) {
    $_SESSION['error'] = 'The farmer for this order no longer exists.';
    redirect('customer/orders.php');
}

if ($farmer['approval_status'] !== A_APPROVED) {
    $_SESSION['error'] = 'This farmer is no longer available for ordering.';
    redirect('customer/orders.php');
}

$farmerName = $farmer['stall_name'];

// ======================================================
// Get Market
// ======================================================

$marketStmt = $conn->prepare("
    SELECT
        m.id,
        m.name,
        m.operating_days
    FROM markets m
    INNER JOIN market_farmer mf
        ON mf.market_id = m.id
       AND mf.farmer_id = ?
    WHERE m.id = ?
      AND m.status = 'active'
    LIMIT 1
");

$marketStmt->bind_param(
    "ii",
    $farmerId,
    $marketId
);

$marketStmt->execute();

$marketResult = $marketStmt->get_result();
$market = $marketResult->fetch_assoc();

$marketStmt->close();

if (!$market) {
    $_SESSION['error'] = 'The market from this order is no longer available.';
    redirect('customer/orders.php');
}

$marketName = $market['name'];
$marketDays = $market['operating_days'];

// ======================================================
// Get Current Week Monday
// ======================================================

$today = new DateTime('today');

$weekStart = clone $today;
$weekStart->modify('monday this week');

$weekStartDate = $weekStart->format('Y-m-d');

// ======================================================
// Get Order Items
// ======================================================

$itemStmt = $conn->prepare("
    SELECT
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        oi.subtotal,

        p.id AS current_product_id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.image,
        p.is_available,
        p.moderation_status,
        p.farmer_id,

        f.stall_name AS farmer_name,
        f.approval_status

    FROM order_items oi

    INNER JOIN products p
        ON p.id = oi.product_id

    INNER JOIN farmers f
        ON f.id = p.farmer_id

    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");

$itemStmt->bind_param(
    "i",
    $orderId
);

$itemStmt->execute();

$itemsResult = $itemStmt->get_result();

$orderItems = [];

while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
}

$itemStmt->close();

if (empty($orderItems)) {
    $_SESSION['error'] = 'This order does not contain any products.';
    redirect('customer/orders.php');
}

// ======================================================
// Build New Cart
// ======================================================

$newCart = [];

foreach ($orderItems as $item) {

    $productId = (int) $item['product_id'];
    $quantity  = (float) $item['quantity'];

    // --------------------------------------------------
    // Make sure the product still belongs to the same
    // farmer as the original order.
    // --------------------------------------------------

    if ((int) $item['farmer_id'] !== $farmerId) {
        $_SESSION['error'] =
            'One of the products in this order is no longer available from the original farmer.';

        redirect('customer/orders.php');
    }

    // --------------------------------------------------
    // Product must still be available.
    // --------------------------------------------------

    if ((int) $item['is_available'] !== 1) {
        $_SESSION['error'] =
            'The product "' . $item['name'] . '" is no longer available for reorder.';

        redirect('customer/orders.php');
    }

    // --------------------------------------------------
    // Product must still be approved.
    // --------------------------------------------------

    if ($item['moderation_status'] !== M_APPROVED) {
        $_SESSION['error'] =
            'The product "' . $item['name'] . '" is no longer available for reorder.';

        redirect('customer/orders.php');
    }

    // --------------------------------------------------
    // Farmer must still be approved.
    // --------------------------------------------------

    if ($item['approval_status'] !== A_APPROVED) {
        $_SESSION['error'] =
            'The farmer for "' . $item['name'] . '" is no longer available for ordering.';

        redirect('customer/orders.php');
    }

    // --------------------------------------------------
    // Quantity must be valid.
    // --------------------------------------------------

    if ($quantity <= 0) {
        $_SESSION['error'] =
            'Invalid quantity found for "' . $item['name'] . '".';

        redirect('customer/orders.php');
    }

    // ==================================================
    // Generate Weekly Stock From Template If Needed
    // ==================================================

    $stockStmt = $conn->prepare("
        SELECT
            id,
            planned_quantity,
            actual_quantity,
            status,
            is_active
        FROM weekly_stock
        WHERE product_id = ?
          AND farmer_id = ?
          AND week_start = ?
        LIMIT 1
    ");

    $stockStmt->bind_param(
        "iis",
        $productId,
        $farmerId,
        $weekStartDate
    );

    $stockStmt->execute();

    $stockResult = $stockStmt->get_result();
    $weeklyStock = $stockResult->fetch_assoc();

    $stockStmt->close();

    // --------------------------------------------------
    // If no weekly stock exists, generate it from the
    // farmer's recurring weekly stock template.
    // This matches the behavior of add_to_cart.php.
    // --------------------------------------------------

    if (!$weeklyStock) {

        $templateStmt = $conn->prepare("
            SELECT
                default_quantity,
                is_active
            FROM weekly_stock_templates
            WHERE farmer_id = ?
              AND product_id = ?
            LIMIT 1
        ");

        $templateStmt->bind_param(
            "ii",
            $farmerId,
            $productId
        );

        $templateStmt->execute();

        $templateResult = $templateStmt->get_result();
        $template = $templateResult->fetch_assoc();

        $templateStmt->close();

        if ($template && (int) $template['is_active'] === 1) {

            $defaultQuantity = (float) $template['default_quantity'];

            if ($defaultQuantity > 0) {

                $insertStockStmt = $conn->prepare("
                    INSERT INTO weekly_stock (
                        farmer_id,
                        product_id,
                        week_start,
                        planned_quantity,
                        actual_quantity,
                        status,
                        is_active
                    )
                    VALUES (?, ?, ?, ?, ?, 'available', 1)
                    ON DUPLICATE KEY UPDATE
                        updated_at = CURRENT_TIMESTAMP
                ");

                $insertStockStmt->bind_param(
                    "iisdd",
                    $farmerId,
                    $productId,
                    $weekStartDate,
                    $defaultQuantity,
                    $defaultQuantity
                );

                $insertStockStmt->execute();

                $insertStockStmt->close();

                // Re-fetch generated stock.
                $stockStmt = $conn->prepare("
                    SELECT
                        id,
                        planned_quantity,
                        actual_quantity,
                        status,
                        is_active
                    FROM weekly_stock
                    WHERE product_id = ?
                      AND farmer_id = ?
                      AND week_start = ?
                    LIMIT 1
                ");

                $stockStmt->bind_param(
                    "iis",
                    $productId,
                    $farmerId,
                    $weekStartDate
                );

                $stockStmt->execute();

                $stockResult = $stockStmt->get_result();
                $weeklyStock = $stockResult->fetch_assoc();

                $stockStmt->close();
            }
        }
    }

    // ==================================================
    // Validate Weekly Stock
    // ==================================================

    if (!$weeklyStock) {
        $_SESSION['error'] =
            'The product "' . $item['name'] . '" has no stock available for this week.';

        redirect('customer/orders.php');
    }

    if ((int) $weeklyStock['is_active'] !== 1) {
        $_SESSION['error'] =
            'The product "' . $item['name'] . '" is unavailable this week.';

        redirect('customer/orders.php');
    }

    if ($weeklyStock['status'] !== 'available') {
        $_SESSION['error'] =
            'The product "' . $item['name'] . '" is currently ' .
            str_replace('_', ' ', $weeklyStock['status']) . '.';

        redirect('customer/orders.php');
    }

    $availableQuantity = (float) $weeklyStock['actual_quantity'];

    if ($availableQuantity < $quantity) {
        $_SESSION['error'] =
            'There is not enough weekly stock for "' .
            $item['name'] .
            '". Available: ' .
            rtrim(rtrim(number_format($availableQuantity, 1, '.', ''), '0'), '.') .
            ' ' .
            ($item['unit'] ?? '') .
            '.';

        redirect('customer/orders.php');
    }

    // ==================================================
    // Build Cart Item
    //
    // IMPORTANT:
    // This is intentionally the SAME structure used
    // by add_to_cart.php.
    // ==================================================

    $price = (float) $item['price'];

    $newCart[$productId] = [
        'product_id'   => $productId,
        'name'         => $item['name'],
        'price'        => $price,
        'unit'         => $item['unit'] ?? '',
        'quantity'     => round($quantity, 1),
        'image'        => $item['image'] ?? '',
        'farmer_id'    => $farmerId,
        'farmer_name'  => $farmerName,
        'market_id'    => $marketId,
        'market_name'  => $marketName,
        'market_days'  => $marketDays,
        'subtotal'     => round($quantity * $price, 2)
    ];
}

// ======================================================
// Make Sure Cart Was Successfully Built
// ======================================================

if (empty($newCart)) {
    $_SESSION['error'] = 'Unable to prepare this order for reorder.';
    redirect('customer/orders.php');
}

// ======================================================
// Replace Cart
//
// This is a NEW reorder cart.
// The original completed order is NOT modified.
// ======================================================

$_SESSION['cart'] = $newCart;

// Optional informational message.
// confirm_order.php can still be used normally.
$_SESSION['success'] = 'Your previous order has been added for reorder.';

// ======================================================
// IMPORTANT:
// Go DIRECTLY to confirm_order.php.
//
// Do NOT redirect to cart.php.
// ======================================================

redirect('customer/confirm_order.php');