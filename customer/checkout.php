<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('customer');

$customerId = getUserId();

if (!$customerId) {
    die('Customer is not logged in.');
}

$cart = $_SESSION['cart'] ?? [];

if (!is_array($cart) || empty($cart)) {
    die('Your cart is empty.');
}

$transactionStarted = false;

try {

    // Start transaction
    $conn->begin_transaction();
    $transactionStarted = true;

    // Group cart items by farmer
    $farmerItems = [];

    foreach ($cart as $item) {

        $productId = (int)($item['product_id'] ?? 0);
        $quantity = (float)($item['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            throw new Exception(
                'Invalid product or quantity.'
            );
        }

        // Get current product information
        $productStmt = $conn->prepare("
            SELECT
                id,
                farmer_id,
                name,
                price,
                unit,
                stock_quantity,
                is_available,
                moderation_status
            FROM products
            WHERE id = ?
            FOR UPDATE
        ");

        if (!$productStmt) {
            throw new Exception(
                'Product query failed: ' . $conn->error
            );
        }

        $productStmt->bind_param(
            "i",
            $productId
        );

        if (!$productStmt->execute()) {
            throw new Exception(
                'Product query execution failed: ' .
                $productStmt->error
            );
        }

        $result = $productStmt->get_result();
        $product = $result->fetch_assoc();

        $productStmt->close();

        if (!$product) {
            throw new Exception(
                'Product ID ' . $productId . ' was not found.'
            );
        }

        // Check availability
        if ((int)$product['is_available'] !== 1) {
            throw new Exception(
                'Product "' .
                $product['name'] .
                '" is not available.'
            );
        }

        // Check moderation
        if ($product['moderation_status'] !== 'approved') {
            throw new Exception(
                'Product "' .
                $product['name'] .
                '" is not approved.'
            );
        }

        // Check stock
        $stock = (float)$product['stock_quantity'];

        if ($quantity > $stock) {
            throw new Exception(
                'Not enough stock for "' .
                $product['name'] .
                '". Available: ' .
                $stock .
                ', Requested: ' .
                $quantity
            );
        }

        $farmerId = (int)$product['farmer_id'];
        $unitPrice = (float)$product['price'];

        $itemSubtotal = $quantity * $unitPrice;

        // Group items by farmer
        if (!isset($farmerItems[$farmerId])) {
            $farmerItems[$farmerId] = [];
        }

        $farmerItems[$farmerId][] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $itemSubtotal
        ];
    }

    /*
     * Create one order for each farmer.
     */
    foreach ($farmerItems as $farmerId => $items) {

        /*
         * Find an available pickup slot for this farmer.
         *
         * The pickup slot already contains the market_id,
         * so we use the market connected to that slot.
         */
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
            ORDER BY id ASC
            LIMIT 1
        ");

        if (!$slotStmt) {
            throw new Exception(
                'Pickup slot query failed: ' .
                $conn->error
            );
        }

        $slotStmt->bind_param(
            "i",
            $farmerId
        );

        if (!$slotStmt->execute()) {
            throw new Exception(
                'Pickup slot query execution failed: ' .
                $slotStmt->error
            );
        }

        $slotResult = $slotStmt->get_result();
        $pickupSlot = $slotResult->fetch_assoc();

        $slotStmt->close();

        if (!$pickupSlot) {
            throw new Exception(
                'No available pickup slot was found for farmer ID ' .
                $farmerId . '.'
            );
        }

        $pickupSlotId = (int)$pickupSlot['id'];
        $marketId = (int)$pickupSlot['market_id'];

        /*
         * Calculate order subtotal.
         */
        $orderSubtotal = 0;

        foreach ($items as $item) {
            $orderSubtotal += $item['subtotal'];
        }

        /*
         * Create order.
         */
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
            (?, ?, ?, ?, 'pending', ?, NULL)
        ");

        if (!$orderStmt) {
            throw new Exception(
                'Order query failed: ' .
                $conn->error
            );
        }

        $orderStmt->bind_param(
            "iiiid",
            $customerId,
            $farmerId,
            $marketId,
            $pickupSlotId,
            $orderSubtotal
        );

        if (!$orderStmt->execute()) {
            throw new Exception(
                'Order could not be created: ' .
                $orderStmt->error
            );
        }

        $orderId = (int)$conn->insert_id;

        $orderStmt->close();

        if ($orderId <= 0) {
            throw new Exception(
                'Order ID was not created.'
            );
        }

        /*
         * Prepare order item query.
         */
        $itemStmt = $conn->prepare("
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

        if (!$itemStmt) {
            throw new Exception(
                'Order items query failed: ' .
                $conn->error
            );
        }

        /*
         * Prepare stock update.
         */
        $stockStmt = $conn->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity - ?
            WHERE id = ?
              AND stock_quantity >= ?
        ");

        if (!$stockStmt) {
            throw new Exception(
                'Stock query failed: ' .
                $conn->error
            );
        }

        /*
         * Insert order items and decrease stock.
         */
        foreach ($items as $item) {

            $productId = (int)$item['product_id'];
            $quantity = (float)$item['quantity'];
            $unitPrice = (float)$item['unit_price'];
            $itemSubtotal = (float)$item['subtotal'];

            /*
             * Insert order item.
             */
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
                    'Order item could not be created: ' .
                    $itemStmt->error
                );
            }

            /*
             * Decrease product stock.
             */
            $stockStmt->bind_param(
                "did",
                $quantity,
                $productId,
                $quantity
            );

            if (!$stockStmt->execute()) {
                throw new Exception(
                    'Stock could not be updated: ' .
                    $stockStmt->error
                );
            }

            if ($stockStmt->affected_rows !== 1) {
                throw new Exception(
                    'Stock was not updated for product ID ' .
                    $productId
                );
            }
        }

        $itemStmt->close();
        $stockStmt->close();

        /*
         * Add initial order status history.
         */
        $historyStmt = $conn->prepare("
            INSERT INTO order_status_history
            (
                order_id,
                status,
                changed_by
            )
            VALUES (?, 'pending', ?)
        ");

        if (!$historyStmt) {
            throw new Exception(
                'Order history query failed: ' .
                $conn->error
            );
        }

        $historyStmt->bind_param(
            "ii",
            $orderId,
            $customerId
        );

        if (!$historyStmt->execute()) {
            throw new Exception(
                'Order history could not be created: ' .
                $historyStmt->error
            );
        }

        $historyStmt->close();
    }

    /*
     * Everything succeeded.
     */
    $conn->commit();
    $transactionStarted = false;

    // Clear cart
    $_SESSION['cart'] = [];

    // Go to orders page
    header('Location: orders.php?success=1');
    exit;

} catch (Throwable $e) {

    // Rollback everything if any step fails
    if ($transactionStarted) {
        $conn->rollback();
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Checkout Error</title>';
    echo '</head>';

    echo '<body style="font-family: Arial, sans-serif; padding: 40px;">';

    echo '<h2 style="color:#8a5a5a;">Checkout Error</h2>';

    echo '<p>';
    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );
    echo '</p>';

    echo '<p>';
    echo '<a href="cart.php">← Back to Cart</a>';
    echo '</p>';

    echo '</body>';
    echo '</html>';

    exit;
}
?>