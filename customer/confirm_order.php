<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

// ===============================
// Get Logged-in Customer ID
// ===============================

$customerId = getUserId();

// ===============================
// Get Cart
// ===============================

$cart = [];

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
}

// ===============================
// Check Empty Cart
// ===============================

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// ===============================
// Variables
// ===============================

$error = '';

$products = [];

$cartItems = [];

$cartSubtotal = 0;

$farmerId = null;

// ===============================
// Get Product IDs
// ===============================

$productIds = [];

foreach ($cart as $item) {

    $productId = (int) ($item['product_id'] ?? 0);

    if ($productId > 0) {
        $productIds[] = $productId;
    }
}

$productIds = array_values(array_unique($productIds));

// ===============================
// Validate Product IDs
// ===============================

if (empty($productIds)) {

    $error = 'Your cart contains invalid products.';

} else {

    // ===============================
    // Get Products From Database
    // ===============================

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

            $productStmt->bind_param("i", $productId);

            $productStmt->execute();

            $productResult = $productStmt->get_result();

            if ($product = $productResult->fetch_assoc()) {

                $products[$productId] = $product;
            }
        }

        $productStmt->close();
    }
}

// ===============================
// Validate Cart Products
// ===============================

if (empty($error)) {

    foreach ($cart as $item) {

        $productId = (int) ($item['product_id'] ?? 0);

        $quantity = (float) ($item['quantity'] ?? 0);

        // ===============================
        // Product Exists
        // ===============================

        if (!isset($products[$productId])) {

            $error = 'One of the products in your cart no longer exists.';
            break;
        }

        $product = $products[$productId];

        // ===============================
        // Product Availability
        // ===============================

        if (
            (int) $product['is_available'] !== 1 ||
            strtolower($product['moderation_status']) !== 'approved'
        ) {

            $error =
                'The product "' .
                htmlspecialchars($product['name']) .
                '" is no longer available.';

            break;
        }

        // ===============================
        // Validate Quantity
        // ===============================

        if ($quantity <= 0) {

            $error = 'Invalid quantity for one of the products.';
            break;
        }

        // ===============================
        // Check Stock
        // ===============================

        if ($quantity > (float) $product['stock_quantity']) {

            $error =
                'Not enough stock available for "' .
                htmlspecialchars($product['name']) .
                '".';

            break;
        }

        // ===============================
        // Check Same Farmer
        // ===============================

        if ($farmerId === null) {

            $farmerId = (int) $product['farmer_id'];

        } elseif ($farmerId !== (int) $product['farmer_id']) {

            $error =
                'Products from different farmers cannot be placed in the same order. Please place separate orders.';

            break;
        }

        // ===============================
        // Calculate Price
        // ===============================

        $unitPrice = (float) $product['price'];

        $itemSubtotal = $quantity * $unitPrice;

        $cartSubtotal += $itemSubtotal;

        // ===============================
        // Prepare Cart Item
        // ===============================

        $cartItems[] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'quantity' => $quantity,
            'unit' => $product['unit'],
            'unit_price' => $unitPrice,
            'subtotal' => $itemSubtotal,
            'image' => $product['image']
        ];
    }
}

// ===============================
// Get Available Pickup Slots
// ===============================

$pickupSlots = [];

if (empty($error) && $farmerId !== null) {

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
        WHERE farmer_id = ?
          AND is_available = 1
        ORDER BY
            id ASC
    ");

    if ($slotStmt) {

        $slotStmt->bind_param("i", $farmerId);

        $slotStmt->execute();

        $slotResult = $slotStmt->get_result();

        while ($slot = $slotResult->fetch_assoc()) {

            $pickupSlots[] = $slot;
        }

        $slotStmt->close();
    }
}

// ===============================
// Process Confirm Order
// ===============================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {

    // ===============================
    // Get Form Data
    // ===============================

    $pickupSlotId = (int) ($_POST['pickup_slot_id'] ?? 0);

    $notes = trim($_POST['notes'] ?? '');

    // ===============================
    // Validate Pickup Slot
    // ===============================

    if ($pickupSlotId <= 0) {

        $error = 'Please select a pickup slot.';

    }

    // ===============================
    // Validate Notes
    // ===============================

    if (empty($error) && strlen($notes) > 1000) {

        $error = 'Notes cannot exceed 1000 characters.';
    }

    // ===============================
    // Start Transaction
    // ===============================

    if (empty($error)) {

        try {

            $conn->begin_transaction();

            // ===============================
            // Get Selected Pickup Slot
            // ===============================

            $slotStmt = $conn->prepare("
                SELECT
                    id,
                    market_id,
                    max_orders
                FROM pickup_slots
                WHERE id = ?
                  AND farmer_id = ?
                  AND is_available = 1
                FOR UPDATE
            ");

            $slotStmt->bind_param(
                "ii",
                $pickupSlotId,
                $farmerId
            );

            $slotStmt->execute();

            $slotResult = $slotStmt->get_result();

            $selectedSlot = $slotResult->fetch_assoc();

            $slotStmt->close();

            // ===============================
            // Check Selected Slot
            // ===============================

            if (!$selectedSlot) {

                throw new Exception(
                    'The selected pickup slot is no longer available.'
                );
            }

            // ===============================
            // Check Pickup Slot Capacity
            // ===============================

            $countStmt = $conn->prepare("
                SELECT COUNT(*) AS order_count
                FROM orders
                WHERE pickup_slot_id = ?
                  AND status IN (
                      'pending',
                      'accepted',
                      'preparing',
                      'ready'
                  )
            ");

            $countStmt->bind_param(
                "i",
                $pickupSlotId
            );

            $countStmt->execute();

            $countResult = $countStmt->get_result();

            $countRow = $countResult->fetch_assoc();

            $countStmt->close();

            $currentOrders = (int) ($countRow['order_count'] ?? 0);

            $maxOrders = $selectedSlot['max_orders'];

            if (
                $maxOrders !== null &&
                $currentOrders >= (int) $maxOrders
            ) {

                throw new Exception(
                    'This pickup slot is already full. Please select another slot.'
                );
            }

            // ===============================
            // Recheck Product Stock
            // ===============================

            $stockStmt = $conn->prepare("
                SELECT
                    id,
                    price,
                    stock_quantity,
                    farmer_id,
                    is_available,
                    moderation_status
                FROM products
                WHERE id = ?
                FOR UPDATE
            ");

            if (!$stockStmt) {

                throw new Exception(
                    'Unable to verify product stock.'
                );
            }

            foreach ($cartItems as &$cartItem) {

                $productId = (int) $cartItem['product_id'];

                $stockStmt->bind_param(
                    "i",
                    $productId
                );

                $stockStmt->execute();

                $stockResult = $stockStmt->get_result();

                $currentProduct = $stockResult->fetch_assoc();

                if (!$currentProduct) {

                    throw new Exception(
                        'One of the products is no longer available.'
                    );
                }

                if (
                    (int) $currentProduct['is_available'] !== 1 ||
                    strtolower($currentProduct['moderation_status']) !== 'approved'
                ) {

                    throw new Exception(
                        'One of the products is no longer available.'
                    );
                }

                if (
                    (int) $currentProduct['farmer_id'] !== $farmerId
                ) {

                    throw new Exception(
                        'Product farmer information has changed.'
                    );
                }

                $quantity = (float) $cartItem['quantity'];

                $currentStock =
                    (float) $currentProduct['stock_quantity'];

                if ($quantity > $currentStock) {

                    throw new Exception(
                        'Not enough stock is available for "' .
                        $cartItem['name'] .
                        '".'
                    );
                }

                // ===============================
                // Use Current Database Price
                // ===============================

                $currentPrice =
                    (float) $currentProduct['price'];

                $cartItem['unit_price'] = $currentPrice;

                $cartItem['subtotal'] =
                    $quantity * $currentPrice;
            }

            unset($cartItem);

            $stockStmt->close();

            // ===============================
            // Recalculate Order Subtotal
            // ===============================

            $cartSubtotal = 0;

            foreach ($cartItems as $cartItem) {

                $cartSubtotal +=
                    (float) $cartItem['subtotal'];
            }

            // ===============================
            // Insert Order
            // ===============================

            $status = 'pending';

            $orderStmt = $conn->prepare("
                INSERT INTO orders
                (
                    customer_id,
                    farmer_id,
                    market_id,
                    pickup_slot_id,
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
                    ?
                )
            ");

            if (!$orderStmt) {

                throw new Exception(
                    'Unable to create the order.'
                );
            }

            $marketId =
                (int) $selectedSlot['market_id'];

            $orderStmt->bind_param(
                "iiiisds",
                $customerId,
                $farmerId,
                $marketId,
                $pickupSlotId,
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

            // ===============================
            // Insert Order Items
            // ===============================

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
                    (int) $cartItem['product_id'];

                $quantity =
                    (float) $cartItem['quantity'];

                $unitPrice =
                    (float) $cartItem['unit_price'];

                $itemSubtotal =
                    (float) $cartItem['subtotal'];

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

            // ===============================
            // Update Product Stock
            // ===============================

            $updateStockStmt = $conn->prepare("
                UPDATE products
                SET stock_quantity = stock_quantity - ?
                WHERE id = ?
            ");

            if (!$updateStockStmt) {

                throw new Exception(
                    'Unable to update product stock.'
                );
            }

            foreach ($cartItems as $cartItem) {

                $quantity =
                    (float) $cartItem['quantity'];

                $productId =
                    (int) $cartItem['product_id'];

                $updateStockStmt->bind_param(
                    "di",
                    $quantity,
                    $productId
                );

                if (!$updateStockStmt->execute()) {

                    throw new Exception(
                        'Unable to update product stock.'
                    );
                }
            }

            $updateStockStmt->close();

            // ===============================
            // Commit Transaction
            // ===============================

            $conn->commit();


            // Notify the farmer about the new order
            $farmerUserStmt = $conn->prepare("
                SELECT user_id
                FROM farmers
                WHERE id = ?
                LIMIT 1
            ");

            if ($farmerUserStmt) {
                $farmerUserStmt->bind_param("i", $farmerId);
                $farmerUserStmt->execute();

                $farmerUserResult = $farmerUserStmt->get_result();
                $farmerUser = $farmerUserResult->fetch_assoc();

                $farmerUserStmt->close();

                if ($farmerUser) {
                    createNotification(
                        $conn,
                        (int)$farmerUser['user_id'],
                        'new_order',
                        'New Order Received',
                        "You received a new order (#{$orderId}). Please review it and prepare it for pickup."
                    );
                }
            }

            // ===============================
            // Empty Cart
            // ===============================

            $_SESSION['cart'] = [];

            // ===============================
            // Success Message
            // ===============================

            $_SESSION['order_success'] =
                'Order #' . $orderId .
                ' has been placed successfully.';

            // ===============================
            // Redirect To Orders
            // ===============================

            header('Location: orders.php');
            exit;

        } catch (Throwable $e) {

            // ===============================
            // Rollback Transaction
            // ===============================

            $conn->rollback();

            $error = $e->getMessage();
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

        /* ===============================
           Page Container
        =============================== */

        .confirm-container {
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
           Error Message
        =============================== */

        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        /* ===============================
           Layout
        =============================== */

        .confirm-layout {
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 25px;
            align-items: start;
        }

        /* ===============================
           Cards
        =============================== */

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

        /* ===============================
           Order Items
        =============================== */

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

        /* ===============================
           Form
        =============================== */

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
            border-color: #27ae60;
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

        /* ===============================
           Summary
        =============================== */

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
            color: #27ae60;
        }

        /* ===============================
           Buttons
        =============================== */

        .confirm-button {
            width: 100%;
            border: none;
            padding: 14px 20px;
            margin-top: 20px;
            background: #27ae60;
            color: white;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .confirm-button:hover {
            background: #219150;
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

        /* ===============================
           Empty Pickup Slots
        =============================== */

        .no-slots {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 12px;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ===============================
           Mobile
        =============================== */

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

            <!-- ===============================
                 Page Header
            =============================== -->

            <div class="page-header">

                <h1>
                    Confirm Order
                </h1>

                <p>
                    Review your order and select a pickup slot.
                </p>

            </div>

            <!-- ===============================
                 Error Message
            =============================== -->

            <?php if (!empty($error)): ?>

                <div class="error-message">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>

            <!-- ===============================
                 Main Layout
            =============================== -->

            <div class="confirm-layout">

                <!-- ===============================
                     Order Details
                =============================== -->

                <div class="confirm-card">

                    <h2 class="card-title">
                        Order Details
                    </h2>

                    <?php foreach ($cartItems as $item): ?>

                        <div class="confirm-item">

                            <!-- Product Image -->

                            <?php if (!empty($item['image'])): ?>

                                <img
                                    src="../uploads/products/<?php echo htmlspecialchars($item['image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                    class="confirm-image"
                                >

                            <?php else: ?>

                                <div class="confirm-no-image">
                                    No Image
                                </div>

                            <?php endif; ?>

                            <!-- Product Information -->

                            <div class="confirm-item-info">

                                <div class="confirm-product-name">

                                    <?php echo htmlspecialchars($item['name']); ?>

                                </div>

                                <div class="confirm-product-details">

                                    Quantity:

                                    <?php
                                    echo number_format(
                                        (float) $item['quantity'],
                                        2
                                    );
                                    ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $item['unit'] ?? ''
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

                            <!-- Item Total -->

                            <div class="confirm-item-total">

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

                </div>

                <!-- ===============================
                     Order Summary
                =============================== -->

                <div class="summary-card">

                    <h2 class="card-title">
                        Order Summary
                    </h2>

                    <form method="POST">

                        <!-- ===============================
                             Pickup Slot
                        =============================== -->

                        <div class="form-group">

                            <label
                                for="pickup_slot_id"
                                class="form-label"
                            >
                                Pickup Slot
                            </label>

                            <?php if (!empty($pickupSlots)): ?>

                                <select
                                    name="pickup_slot_id"
                                    id="pickup_slot_id"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Select a pickup slot
                                    </option>

                                    <?php foreach ($pickupSlots as $slot): ?>

                                        <option
                                            value="<?php echo (int) $slot['id']; ?>"
                                            <?php
                                            if (
                                                isset($_POST['pickup_slot_id']) &&
                                                (int) $_POST['pickup_slot_id'] ===
                                                (int) $slot['id']
                                            ) {
                                                echo 'selected';
                                            }
                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $slot['day_of_week']
                                            );
                                            ?>

                                            -

                                            <?php
                                            echo date(
                                                'h:i A',
                                                strtotime($slot['start_time'])
                                            );
                                            ?>

                                            to

                                            <?php
                                            echo date(
                                                'h:i A',
                                                strtotime($slot['end_time'])
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <div class="form-help">
                                    Select the time when you want to pick up your order.
                                </div>

                            <?php else: ?>

                                <div class="no-slots">
                                    No pickup slots are currently available for this farmer.
                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- ===============================
                             Notes
                        =============================== -->

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
                            ><?php
                            echo htmlspecialchars(
                                $_POST['notes'] ?? ''
                            );
                            ?></textarea>

                            <div class="form-help">
                                Optional. Maximum 1000 characters.
                            </div>

                        </div>

                        <!-- ===============================
                             Summary Rows
                        =============================== -->

                        <div class="summary-row">

                            <span>
                                Items
                            </span>

                            <span>
                                <?php echo count($cartItems); ?>
                            </span>

                        </div>

                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>

                            <span>
                                $
                                <?php
                                echo number_format(
                                    $cartSubtotal,
                                    2
                                );
                                ?>
                            </span>

                        </div>

                        <!-- ===============================
                             Total
                        =============================== -->

                        <div class="summary-total">

                            <span class="summary-total-label">
                                Order Total
                            </span>

                            <span class="summary-total-price">

                                $

                                <?php
                                echo number_format(
                                    $cartSubtotal,
                                    2
                                );
                                ?>

                            </span>

                        </div>

                        <!-- ===============================
                             Confirm Button
                        =============================== -->

                        <?php if (!empty($pickupSlots)): ?>

                            <button
                                type="submit"
                                class="confirm-button"
                            >
                                Confirm Order
                            </button>

                        <?php endif; ?>

                        <!-- ===============================
                             Back To Cart
                        =============================== -->

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

</body>

</html>

