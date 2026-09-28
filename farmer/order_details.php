<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);

requireApprovedFarmer();

if (
    !isset($_GET['id'])
    || !is_numeric($_GET['id'])
) {
    die("Invalid order ID.");
}

$order_id = (int) $_GET['id'];
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

$order_stmt = $conn->prepare("
    SELECT
        orders.id,
        orders.status,
        orders.subtotal,
        orders.notes,
        orders.created_at,
        users.name AS customer_name,
        users.email AS customer_email,
        users.phone AS customer_phone,
        users.address AS customer_address
    FROM orders
    INNER JOIN users
        ON orders.customer_id = users.id
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

if (!$order) {
    die("Order not found.");
}

$order_stmt->close();

$item_stmt = $conn->prepare("
    SELECT
        order_items.id,
        order_items.quantity,
        order_items.unit_price,
        order_items.subtotal,
        products.name AS product_name,
        products.unit
    FROM order_items
    INNER JOIN products
        ON order_items.product_id = products.id
    WHERE order_items.order_id = ?
    ORDER BY order_items.id ASC
");

$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();

$items = $item_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Order Details | MarketLink</title>

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
        .farmer-order-details {
            background: var(--cream);
            min-height: 100vh;
            padding-bottom: 70px;
        }

        .order-details-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 30px;
            padding: 38px 0 42px;
            border-bottom: 1px solid var(--border);
        }

        .order-details-hero-copy {
            max-width: 760px;
        }

        .order-details-hero h1 {
            margin: 10px 0 12px;
            color: var(--text);
            font-family: var(--font-main);
            font-size: clamp(2.3rem, 5vw, 4.5rem);
            line-height: .98;
            letter-spacing: -.045em;
        }

        .order-details-hero h1 em {
            color: var(--sage-dark);
            font-family: var(--font-serif);
            font-weight: 500;
        }

        .order-details-hero p {
            max-width: 600px;
            margin: 0;
            color: var(--text-muted);
            font-size: .95rem;
            line-height: 1.7;
        }

        .order-details-mark {
            color: var(--terracotta);
            font-family: var(--font-serif);
            font-size: 5rem;
            line-height: 1;
            opacity: .22;
        }

        .order-details-section {
            margin-top: 48px;
        }

        .order-details-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .order-details-section-number {
            display: block;
            margin-bottom: 8px;
            color: var(--terracotta-dark);
            font-family: var(--font-main);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .order-details-section-heading h2 {
            margin: 0;
            color: var(--text);
            font-family: var(--font-main);
            font-size: 1.65rem;
            letter-spacing: -.025em;
        }

        .order-details-section-heading h2 em {
            color: var(--sage-dark);
            font-family: var(--font-serif);
            font-weight: 500;
        }

        .order-details-card {
            padding: 28px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px 30px;
        }

        .order-detail {
            min-width: 0;
        }

        .order-detail-wide {
            grid-column: span 2;
        }

        .order-detail-label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-muted);
            font-family: var(--font-main);
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .order-detail-value {
            color: var(--text);
            font-size: .9rem;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .order-detail-status {
            display: inline-flex;
            align-items: center;
            min-height: 29px;
            padding: 5px 12px;
            border-radius: var(--radius-pill);
            background: var(--sage-soft);
            border: 1px solid var(--sage-light);
            color: var(--sage-dark);
            font-family: var(--font-main);
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .order-detail-total {
            color: var(--terracotta-dark);
            font-family: var(--font-main);
            font-size: 1.05rem;
            font-weight: 700;
        }

        .order-details-table {
            width: 100%;
            overflow: hidden;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
        }

        .order-details-table table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .order-details-table th {
            padding: 14px 20px;
            background: var(--surface-alt);
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
            font-family: var(--font-main);
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-align: left;
            text-transform: uppercase;
        }

        .order-details-table td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-soft);
            font-size: .86rem;
            line-height: 1.4;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }

        .order-details-table tbody tr:last-child td {
            border-bottom: none;
        }

        .order-details-table tbody tr:hover {
            background: var(--cream-light);
        }

        .order-details-table td:first-child {
            color: var(--text);
            font-family: var(--font-main);
            font-weight: 700;
        }

        .order-details-table td:last-child {
            color: var(--text);
            font-family: var(--font-main);
            font-weight: 700;
        }

        .order-details-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .order-details-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 20px;
            border: 1px solid var(--border);
            border-radius: var(--radius-pill);
            background: var(--surface);
            color: var(--text-soft);
            font-family: var(--font-main);
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-decoration: none;
            text-transform: uppercase;
            transition: var(--transition-fast);
        }

        .order-details-back:hover {
            background: var(--surface-alt);
            color: var(--text);
            transform: var(--lift-small);
        }

        @media (max-width: 850px) {
            .order-details-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .order-detail-wide {
                grid-column: span 2;
            }

            .order-details-mark {
                font-size: 4rem;
            }
        }

        @media (max-width: 650px) {
            .order-details-hero {
                align-items: flex-start;
            }

            .order-details-mark {
                display: none;
            }

            .order-details-grid {
                grid-template-columns: 1fr;
            }

            .order-detail-wide {
                grid-column: span 1;
            }

            .order-details-card {
                padding: 22px;
            }

            .order-details-table th,
            .order-details-table td {
                padding: 14px 12px;
                font-size: .76rem;
            }
        }
    </style>

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content farmer-order-details">

    <section class="order-details-hero">

        <div class="order-details-hero-copy">

            <span class="eyebrow">
                FARMER / ORDER DETAILS
            </span>

            <h1>
                Order <em>#<?= e($order['id']) ?>.</em>
            </h1>

            <p>
                Review the customer information and products
                included in this order.
            </p>

        </div>

        <div class="order-details-mark">
            03
        </div>

    </section>

    <section class="order-details-section">

        <div class="order-details-section-heading">

            <div>

                <span class="order-details-section-number">
                    01 / ORDER
                </span>

                <h2>
                    Order <em>information.</em>
                </h2>

            </div>

        </div>

        <div class="order-details-card">

            <div class="order-details-grid">

                <div class="order-detail">

                    <span class="order-detail-label">
                        Order ID
                    </span>

                    <div class="order-detail-value">
                        #<?= e($order['id']) ?>
                    </div>

                </div>

                <div class="order-detail">

                    <span class="order-detail-label">
                        Status
                    </span>

                    <div class="order-detail-value">

                        <span class="order-detail-status">
                            <?= e($order['status']) ?>
                        </span>

                    </div>

                </div>

                <div class="order-detail">

                    <span class="order-detail-label">
                        Subtotal
                    </span>

                    <div class="order-detail-value order-detail-total">
                        <?= formatPrice($order['subtotal']) ?>
                    </div>

                </div>

                <div class="order-detail">

                    <span class="order-detail-label">
                        Date
                    </span>

                    <div class="order-detail-value">
                        <?= formatDateTime($order['created_at']) ?>
                    </div>

                </div>

                <div class="order-detail order-detail-wide">

                    <span class="order-detail-label">
                        Notes
                    </span>

                    <div class="order-detail-value">
                        <?= e($order['notes'] ?? '') ?: 'No notes provided.' ?>
                    </div>

                </div>

            </div>

        </div>

    </section>

    <section class="order-details-section">

        <div class="order-details-section-heading">

            <div>

                <span class="order-details-section-number">
                    02 / CUSTOMER
                </span>

                <h2>
                    Customer <em>information.</em>
                </h2>

            </div>

        </div>

        <div class="order-details-card">

            <div class="order-details-grid">

                <div class="order-detail">

                    <span class="order-detail-label">
                        Name
                    </span>

                    <div class="order-detail-value">
                        <?= e($order['customer_name']) ?>
                    </div>

                </div>

                <div class="order-detail">

                    <span class="order-detail-label">
                        Email
                    </span>

                    <div class="order-detail-value">
                        <?= e($order['customer_email']) ?>
                    </div>

                </div>

                <div class="order-detail">

                    <span class="order-detail-label">
                        Phone
                    </span>

                    <div class="order-detail-value">
                        <?= e($order['customer_phone']) ?>
                    </div>

                </div>

                <div class="order-detail order-detail-wide">

                    <span class="order-detail-label">
                        Address
                    </span>

                    <div class="order-detail-value">
                        <?= e($order['customer_address']) ?>
                    </div>

                </div>

            </div>

        </div>

    </section>

    <section class="order-details-section">

        <div class="order-details-section-heading">

            <div>

                <span class="order-details-section-number">
                    03 / ITEMS
                </span>

                <h2>
                    Order <em>items.</em>
                </h2>

            </div>

        </div>

        <div class="order-details-table">

            <table>

                <thead>

                    <tr>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while ($item = $items->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= e($item['product_name']) ?>
                            </td>

                            <td>
                                <?= e($item['unit']) ?>
                            </td>

                            <td>
                                <?= e($item['quantity']) ?>
                            </td>

                            <td>
                                <?= formatPrice($item['unit_price']) ?>
                            </td>

                            <td>
                                <?= formatPrice($item['subtotal']) ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

        <div class="order-details-footer">

            <a
                href="orders.php"
                class="order-details-back"
            >
                Back to Orders
            </a>

        </div>

    </section>

</main>

</body>
</html>