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

if (!$farmer) {
    die("Farmer account not found.");
}

$farmer_id = $farmer['id'];

$stmt->close();

$market_stmt = $conn->prepare("
    SELECT
        id,
        name
    FROM markets
    ORDER BY name ASC
");

$market_stmt->execute();

$markets = $market_stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: pickup_slots.php');
        exit;
    }

    $market_id = (int) $_POST['market_id'];
    $day_of_week = trim($_POST['day_of_week']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $cutoff_time = $_POST['cutoff_time'];
    $max_orders = (int) $_POST['max_orders'];

    if (
        $market_id <= 0 ||
        empty($day_of_week) ||
        empty($start_time) ||
        empty($end_time) ||
        empty($cutoff_time) ||
        $max_orders <= 0
    ) {
        die("Please enter valid pickup slot information.");
    }

    $stmt = $conn->prepare("
        INSERT INTO pickup_slots
        (
            farmer_id,
            market_id,
            day_of_week,
            start_time,
            end_time,
            cutoff_time,
            max_orders
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iissssi",
        $farmer_id,
        $market_id,
        $day_of_week,
        $start_time,
        $end_time,
        $cutoff_time,
        $max_orders
    );

    if (!$stmt->execute()) {
        die("Insert failed.");
    }

    $stmt->close();
    $success_message = "Pickup slot added successfully.";
}

$slots_per_page = 10;

$slots_page = isset($_GET['slots_page']) ? (int)$_GET['slots_page'] : 1;

if ($slots_page < 1) {
    $slots_page = 1;
}

$slots_offset = ($slots_page - 1) * $slots_per_page;

$count_slots_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_slots
    FROM pickup_slots
    WHERE farmer_id = ?
");

$count_slots_stmt->bind_param("i", $farmer_id);
$count_slots_stmt->execute();

$count_slots_result = $count_slots_stmt->get_result();
$total_slots = $count_slots_result->fetch_assoc()['total_slots'];

$count_slots_stmt->close();

$total_slots_pages = ceil($total_slots / $slots_per_page);

if ($total_slots_pages > 0 && $slots_page > $total_slots_pages) {
    $slots_page = $total_slots_pages;
    $slots_offset = ($slots_page - 1) * $slots_per_page;
}

$orders_per_page = 10;

$orders_page = isset($_GET['orders_page']) ? (int)$_GET['orders_page'] : 1;

if ($orders_page < 1) {
    $orders_page = 1;
}

$orders_offset = ($orders_page - 1) * $orders_per_page;

$count_orders_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM orders
    WHERE farmer_id = ?
");

$count_orders_stmt->bind_param("i", $farmer_id);
$count_orders_stmt->execute();

$count_orders_result = $count_orders_stmt->get_result();
$total_orders = $count_orders_result->fetch_assoc()['total_orders'];

$count_orders_stmt->close();

$total_orders_pages = ceil($total_orders / $orders_per_page);

if ($total_orders_pages > 0 && $orders_page > $total_orders_pages) {
    $orders_page = $total_orders_pages;
    $orders_offset = ($orders_page - 1) * $orders_per_page;
}

$slot_stmt = $conn->prepare("
    SELECT
        pickup_slots.id,
        pickup_slots.day_of_week,
        pickup_slots.start_time,
        pickup_slots.end_time,
        pickup_slots.cutoff_time,
        pickup_slots.max_orders,
        pickup_slots.is_available,
        markets.name AS market_name
    FROM pickup_slots
    INNER JOIN markets
        ON pickup_slots.market_id = markets.id
    WHERE pickup_slots.farmer_id = ?
    ORDER BY pickup_slots.day_of_week ASC, pickup_slots.start_time ASC
    LIMIT ? OFFSET ?
");

$slot_stmt->bind_param("iii", $farmer_id, $slots_per_page, $slots_offset);
$slot_stmt->execute();

$slots = $slot_stmt->get_result();

$order_stmt = $conn->prepare("
    SELECT
        orders.id AS order_id,
        orders.status,
        orders.created_at,
        pickup_slots.day_of_week,
        pickup_slots.start_time,
        pickup_slots.end_time,
        markets.name AS market_name
    FROM orders
    INNER JOIN pickup_slots
        ON orders.pickup_slot_id = pickup_slots.id
    INNER JOIN markets
        ON orders.market_id = markets.id
    WHERE orders.farmer_id = ?
    ORDER BY pickup_slots.day_of_week ASC, pickup_slots.start_time ASC
    LIMIT ? OFFSET ?
");

$order_stmt->bind_param("iii", $farmer_id, $orders_per_page, $orders_offset);
$order_stmt->execute();

$orders = $order_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Slots</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/farmer.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content farmer-pickup-slots-page">

        <section class="customer-page-hero">
            <div class="customer-page-hero-copy">
                <span class="eyebrow">FARMER / PICKUP SLOTS</span>

                <h1>
                    Manage your <em>pickup times.</em>
                </h1>

                <p>
                    Set your available pickup windows and keep track of orders using your scheduled slots.
                </p>
            </div>

            <div class="customer-page-hero-mark">
                06
            </div>
        </section>

        <?php if (isset($success_message)): ?>
            <div class="customer-products-notice customer-products-success">
                <i data-lucide="CheckCircle"></i>
                <span><?= e($success_message) ?></span>
            </div>
        <?php endif; ?>

        <section class="farmer-pickup-slots-section">

            <div class="customer-section-heading">
                <div>
                    <span class="customer-section-number">01 / CREATE SLOT</span>

                    <h2>
                        Add a pickup <em>window.</em>
                    </h2>
                </div>
            </div>

            <div class="farmer-product-form-card">

                <form
                    class="farmer-product-form"
                    method="POST"
                >
                    <?= csrf_field() ?>

                    <div class="farmer-form-grid">

                        <div class="farmer-form-field">
                            <label for="market_id">
                                Market
                            </label>

                            <select
                                name="market_id"
                                id="market_id"
                                required
                            >
                                <option value="">
                                    Select Market
                                </option>

                                <?php while ($market = $markets->fetch_assoc()): ?>
                                    <option value="<?= e($market['id']) ?>">
                                        <?= e($market['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="farmer-form-field">
                            <label for="day_of_week">
                                Day
                            </label>

                            <select
                                name="day_of_week"
                                id="day_of_week"
                                required
                            >
                                <option value="">
                                    Select Day
                                </option>

                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>

                        <div class="farmer-form-field">
                            <label for="start_time">
                                Start Time
                            </label>

                            <input
                                type="time"
                                name="start_time"
                                id="start_time"
                                required
                            >
                        </div>

                        <div class="farmer-form-field">
                            <label for="end_time">
                                End Time
                            </label>

                            <input
                                type="time"
                                name="end_time"
                                id="end_time"
                                required
                            >
                        </div>

                        <div class="farmer-form-field">
                            <label for="cutoff_time">
                                Cutoff Time
                            </label>

                            <input
                                type="time"
                                name="cutoff_time"
                                id="cutoff_time"
                                required
                            >
                        </div>

                        <div class="farmer-form-field">
                            <label for="max_orders">
                                Maximum Orders
                            </label>

                            <input
                                type="number"
                                name="max_orders"
                                id="max_orders"
                                min="1"
                                placeholder="e.g. 20"
                                required
                            >
                        </div>

                    </div>

                    <div class="farmer-form-actions">
                        <button
                            type="submit"
                            class="farmer-form-submit"
                        >
                            <i data-lucide=" fa-plus"></i>
                            Add Pickup Slot
                        </button>
                    </div>

                </form>

            </div>

        </section>

        <section class="farmer-pickup-slots-section">

            <div class="customer-section-heading">
                <div>
                    <span class="customer-section-number">02 / YOUR SCHEDULE</span>

                    <h2>
                        Your pickup <em>slots.</em>
                    </h2>
                </div>

                <span class="customer-record-count">
                    <?= e($total_slots) ?> SLOTS
                </span>
            </div>

            <?php if ($total_slots === 0): ?>

                <div class="customer-products-empty">
                    <div class="customer-products-empty-mark">
                        <i data-lucide=" clock"></i>
                    </div>

                    <h3>
                        No pickup slots yet.
                    </h3>

                    <p>
                        Create your first pickup window to let customers know when their orders can be collected.
                    </p>
                </div>

            <?php else: ?>

                <div class="farmer-pickup-table-wrapper">

                    <table class="farmer-pickup-table">

                        <thead>
                            <tr>
                                <th>Market</th>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Cutoff Time</th>
                                <th>Maximum Orders</th>
                                <th>Availability</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($slot = $slots->fetch_assoc()): ?>

                                <tr>
                                    <td>
                                        <span class="farmer-pickup-market">
                                            <?= e($slot['market_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($slot['day_of_week']) ?>
                                    </td>

                                    <td>
                                        <?= e($slot['start_time']) ?>
                                    </td>

                                    <td>
                                        <?= e($slot['end_time']) ?>
                                    </td>

                                    <td>
                                        <?= e($slot['cutoff_time']) ?>
                                    </td>

                                    <td>
                                        <span class="farmer-pickup-capacity">
                                            <?= e($slot['max_orders']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ($slot['is_available']): ?>
                                            <span class="farmer-pickup-availability pickup-available">
                                                Available
                                            </span>
                                        <?php else: ?>
                                            <span class="farmer-pickup-availability pickup-unavailable">
                                                Unavailable
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

                <?php if ($total_slots_pages > 1): ?>

                    <div class="product-pagination">

                        <?php if ($slots_page > 1): ?>

                            <a
                                href="?slots_page=<?= $slots_page - 1 ?>&orders_page=<?= $orders_page ?>"
                                class="product-pagination-button"
                            >
                                Previous
                            </a>

                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_slots_pages; $i++): ?>

                            <a
                                href="?slots_page=<?= $i ?>&orders_page=<?= $orders_page ?>"
                                class="product-pagination-button <?= $i == $slots_page ? 'active' : '' ?>"
                            >
                                <?= $i ?>
                            </a>

                        <?php endfor; ?>

                        <?php if ($slots_page < $total_slots_pages): ?>

                            <a
                                href="?slots_page=<?= $slots_page + 1 ?>&orders_page=<?= $orders_page ?>"
                                class="product-pagination-button"
                            >
                                Next
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </section>

        <section class="farmer-pickup-slots-section">

            <div class="customer-section-heading">
                <div>
                    <span class="customer-section-number">03 / SCHEDULED ORDERS</span>

                    <h2>
                        Orders using your <em>slots.</em>
                    </h2>
                </div>

                <span class="customer-record-count">
                    <?= e($total_orders) ?> ORDERS
                </span>
            </div>

            <?php if ($total_orders === 0): ?>

                <div class="customer-products-empty">
                    <div class="customer-products-empty-mark">
                        <i data-lucide=" shopping-bag"></i>
                    </div>

                    <h3>
                        No pickup orders yet.
                    </h3>

                    <p>
                        Orders assigned to your pickup slots will appear here.
                    </p>
                </div>

            <?php else: ?>

                <div class="farmer-pickup-table-wrapper">

                    <table class="farmer-pickup-table farmer-pickup-orders-table">

                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Market</th>
                                <th>Day</th>
                                <th>Pickup Time</th>
                                <th>Status</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($order = $orders->fetch_assoc()): ?>

                                <tr>
                                    <td>
                                        <span class="farmer-pickup-order-id">
                                            #<?= e($order['order_id']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="farmer-pickup-market">
                                            <?= e($order['market_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($order['day_of_week']) ?>
                                    </td>

                                    <td>
                                        <span class="farmer-pickup-time">
                                            <?= e($order['start_time']) ?>
                                            -
                                            <?= e($order['end_time']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="farmer-pickup-order-status pickup-status-<?= e($order['status']) ?>">
                                            <?= e(ucfirst($order['status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= formatDate($order['created_at']) ?>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

                <?php if ($total_orders_pages > 1): ?>

                    <div class="product-pagination">

                        <?php if ($orders_page > 1): ?>

                            <a
                                href="?orders_page=<?= $orders_page - 1 ?>&slots_page=<?= $slots_page ?>"
                                class="product-pagination-button"
                            >
                                Previous
                            </a>

                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_orders_pages; $i++): ?>

                            <a
                                href="?orders_page=<?= $i ?>&slots_page=<?= $slots_page ?>"
                                class="product-pagination-button <?= $i == $orders_page ? 'active' : '' ?>"
                            >
                                <?= $i ?>
                            </a>

                        <?php endfor; ?>

                        <?php if ($orders_page < $total_orders_pages): ?>

                            <a
                                href="?orders_page=<?= $orders_page + 1 ?>&slots_page=<?= $slots_page ?>"
                                class="product-pagination-button"
                            >
                                Next
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </main>

    <script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>