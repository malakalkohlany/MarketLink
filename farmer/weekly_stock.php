<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$farmer_id = $_SESSION['farmer_id'] ?? null;

if (!$farmer_id) {
    redirect('auth/logout.php');
}


$today = new DateTime();

$week_start = clone $today;

if ($week_start->format('N') != 1) {
    $week_start->modify('monday this week');
}

$week_start_date = $week_start->format('Y-m-d');


$errors = [];

$success = $_SESSION['weekly_stock_success'] ?? '';

unset($_SESSION['weekly_stock_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: weekly_stock.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update') {

        $weekly_stock_id = (int) ($_POST['weekly_stock_id'] ?? 0);

        $actual_quantity = $_POST['actual_quantity'] ?? '';

        $status = $_POST['status'] ?? '';

        $allowed_statuses = [
            'available',
            'sold_out',
            'unavailable'
        ];


        if ($weekly_stock_id <= 0) {
            $errors[] = 'Invalid weekly stock item.';
        }


        if (
            $actual_quantity === '' ||
            !is_numeric($actual_quantity)
        ) {

            $errors[] = 'Quantity must be a valid number.';

        } else {

            $actual_quantity = (float) $actual_quantity;

            if ($actual_quantity < 0) {
                $errors[] = 'Quantity cannot be negative.';
            }
        }


        if (!in_array($status, $allowed_statuses, true)) {
            $errors[] = 'Invalid stock status.';
        }

        $old_stock = null;

        if (empty($errors)) {

            $old_stmt = $conn->prepare("
                SELECT
                    actual_quantity,
                    status
                FROM weekly_stock
                WHERE id = ?
                  AND farmer_id = ?
                  AND week_start = ?
                LIMIT 1
            ");

            if (!$old_stmt) {

                $errors[] =
                    'Failed to check weekly stock item.';

            } else {

                $old_stmt->bind_param(
                    "iis",
                    $weekly_stock_id,
                    $farmer_id,
                    $week_start_date
                );

                if (!$old_stmt->execute()) {

                    $errors[] =
                        'Failed to check weekly stock item.';

                } else {

                    $old_result = $old_stmt->get_result();

                    $old_stock = $old_result->fetch_assoc();

                    if (!$old_stock) {

                        $errors[] =
                            'Weekly stock item not found.';
                    }
                }

                $old_stmt->close();
            }
        }

        if (empty($errors) && $old_stock) {

            $old_quantity =
                (float) $old_stock['actual_quantity'];

            $old_status =
                (string) $old_stock['status'];

            $stock_changed =
                abs($old_quantity - $actual_quantity) > 0.00001
                || $old_status !== $status;

            $update_stmt = $conn->prepare("
                UPDATE weekly_stock
                SET
                    actual_quantity = ?,
                    status = ?
                WHERE id = ?
                  AND farmer_id = ?
                  AND week_start = ?
            ");

            if (!$update_stmt) {

                $errors[] =
                    'Failed to prepare weekly stock update.';

            } else {

                $update_stmt->bind_param(
                    "dsiis",
                    $actual_quantity,
                    $status,
                    $weekly_stock_id,
                    $farmer_id,
                    $week_start_date
                );

                if (!$update_stmt->execute()) {

                    $errors[] =
                        'Failed to update weekly stock.';
                }

                $update_stmt->close();
            }

            if (empty($errors)) {

                if ($stock_changed) {

                    notifyWeeklyStockUpdated(
                        $conn,
                        (int) $farmer_id,
                        $week_start_date
                    );
                }

                $_SESSION['weekly_stock_success'] =
                    'Weekly stock updated successfully.';

                redirect('farmer/weekly_stock.php');
            }
        }
    }

    elseif ($action === 'update_all') {

        $quantities =
            $_POST['actual_quantity'] ?? [];

        $statuses =
            $_POST['status'] ?? [];

        if (
            !is_array($quantities) ||
            !is_array($statuses)
        ) {

            $errors[] =
                'Invalid weekly stock data.';
        }

        if (empty($errors)) {

            $validated_rows = [];

            foreach ($quantities as $stock_id => $quantity) {

                $stock_id = (int) $stock_id;

                if ($stock_id <= 0) {

                    $errors[] =
                        'Invalid weekly stock item.';

                    break;
                }

                if (
                    $quantity === '' ||
                    !is_numeric($quantity)
                ) {

                    $errors[] =
                        'Quantity for one of the products is invalid.';

                    break;
                }

                $quantity = (float) $quantity;

                if ($quantity < 0) {

                    $errors[] =
                        'Quantity cannot be negative.';

                    break;
                }

                $status =
                    $statuses[$stock_id] ?? '';

                if (!in_array(
                    $status,
                    [
                        'available',
                        'sold_out',
                        'unavailable'
                    ],
                    true
                )) {

                    $errors[] =
                        'One of the stock statuses is invalid.';

                    break;
                }

                $validated_rows[] = [
                    'id' => $stock_id,
                    'quantity' => $quantity,
                    'status' => $status
                ];
            }
        }

        if (empty($errors)) {

            foreach ($statuses as $stock_id => $status) {

                if (
                    !array_key_exists(
                        $stock_id,
                        $quantities
                    )
                ) {

                    $errors[] =
                        'Invalid weekly stock data.';

                    break;
                }
            }
        }

        $old_stock = [];

        if (empty($errors)) {

            $old_stmt = $conn->prepare("
                SELECT
                    id,
                    actual_quantity,
                    status
                FROM weekly_stock
                WHERE farmer_id = ?
                  AND week_start = ?
            ");

            if (!$old_stmt) {

                $errors[] =
                    'Failed to check current weekly stock.';

            } else {

                $old_stmt->bind_param(
                    "is",
                    $farmer_id,
                    $week_start_date
                );

                if (!$old_stmt->execute()) {

                    $errors[] =
                        'Failed to check current weekly stock.';

                } else {

                    $old_result =
                        $old_stmt->get_result();

                    while (
                        $old_row =
                        $old_result->fetch_assoc()
                    ) {

                        $id =
                            (int) $old_row['id'];

                        $old_stock[$id] = [
                            'quantity' =>
                                (float) $old_row['actual_quantity'],

                            'status' =>
                                (string) $old_row['status']
                        ];
                    }
                }

                $old_stmt->close();
            }
        }

        $stock_changed = false;

        if (empty($errors)) {

            foreach ($validated_rows as $row) {

                $stock_id = $row['id'];

                if (!isset($old_stock[$stock_id])) {

                    $errors[] =
                        'One of the weekly stock items could not be found.';

                    break;
                }

                $old_quantity =
                    $old_stock[$stock_id]['quantity'];

                $old_status =
                    $old_stock[$stock_id]['status'];

                if (
                    abs(
                        $old_quantity -
                        $row['quantity']
                    ) > 0.00001
                    ||
                    $old_status !== $row['status']
                ) {

                    $stock_changed = true;

                    break;
                }
            }
        }

        if (empty($errors)) {

            $update_stmt = null;

            $transaction_started = false;

            try {

                $conn->begin_transaction();

                $transaction_started = true;

                $update_stmt = $conn->prepare("
                    UPDATE weekly_stock
                    SET
                        actual_quantity = ?,
                        status = ?
                    WHERE id = ?
                      AND farmer_id = ?
                      AND week_start = ?
                ");

                if (!$update_stmt) {

                    throw new Exception(
                        'Failed to prepare weekly stock update.'
                    );
                }

                foreach ($validated_rows as $row) {

                    $quantity = $row['quantity'];
                    $status = $row['status'];
                    $stock_id = $row['id'];

                    $update_stmt->bind_param(
                        "dsiis",
                        $quantity,
                        $status,
                        $stock_id,
                        $farmer_id,
                        $week_start_date
                    );

                    if (!$update_stmt->execute()) {

                        throw new Exception(
                            'Failed to update weekly stock.'
                        );
                    }
                }

                /*
                 * The statement is no longer needed.
                 * Close it BEFORE committing.
                 */
                $update_stmt->close();
                $update_stmt = null;

                $conn->commit();

                $transaction_started = false;

                if ($stock_changed) {

                    notifyWeeklyStockUpdated(
                        $conn,
                        (int) $farmer_id,
                        $week_start_date
                    );
                }

                $_SESSION['weekly_stock_success'] =
                    'All weekly stock changes saved successfully.';

                redirect('farmer/weekly_stock.php');

            } catch (Throwable $e) {

                if ($transaction_started) {
                    $conn->rollback();
                }

                if ($update_stmt !== null) {

                    $update_stmt->close();

                    $update_stmt = null;
                }

                $errors[] =
                    'Failed to save weekly stock changes.';
            }
        }
    }
}


$generate_stmt = $conn->prepare("
    INSERT INTO weekly_stock (
        farmer_id,
        product_id,
        week_start,
        planned_quantity,
        actual_quantity,
        status
    )
    SELECT
        farmer_id,
        product_id,
        ?,
        default_quantity,
        default_quantity,
        'available'
    FROM weekly_stock_templates
    WHERE farmer_id = ?
      AND is_active = 1
      AND NOT EXISTS (
          SELECT 1
          FROM weekly_stock ws
          WHERE ws.farmer_id =
                weekly_stock_templates.farmer_id
            AND ws.product_id =
                weekly_stock_templates.product_id
            AND ws.week_start = ?
      )
");

if ($generate_stmt) {

    $generate_stmt->bind_param(
        "sis",
        $week_start_date,
        $farmer_id,
        $week_start_date
    );

    $generate_stmt->execute();

    $generate_stmt->close();
}


sendWeeklyStockReminders(
    $conn,
    $week_start_date
);

$weekly_stock = [];

$stock_stmt = $conn->prepare("
    SELECT
        ws.id,
        ws.product_id,
        ws.week_start,
        ws.planned_quantity,
        ws.actual_quantity,
        ws.status,

        p.name AS product_name,
        p.unit,
        p.price,
        p.image

    FROM weekly_stock ws

    INNER JOIN products p
        ON p.id = ws.product_id

    WHERE ws.farmer_id = ?
      AND ws.week_start = ?

    ORDER BY p.name ASC
");

if ($stock_stmt) {

    $stock_stmt->bind_param(
        "is",
        $farmer_id,
        $week_start_date
    );

    if ($stock_stmt->execute()) {

        $stock_result =
            $stock_stmt->get_result();

        while (
            $row =
            $stock_result->fetch_assoc()
        ) {

            $weekly_stock[] = $row;
        }
    }

    $stock_stmt->close();
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

    <title>Weekly Stock | MarketLink</title>

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

    <link
        rel="stylesheet"
        href="../assets/css/weekly-stock.css"
    >

    <style>

        .page-content {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin-bottom: 8px;
        }

        .page-header p {
            margin: 0;
            color: #72583E;
        }

        .week-info {
            background: #FFF9F3;
            border: 1px solid #DBC4A5;
            border-radius: 12px;
            padding: 15px 18px;
            margin-bottom: 20px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #E8F3E8;
            color: #355C3A;
            border: 1px solid #B8D5BA;
        }

        .alert-error {
            background: #F8E7E4;
            color: #7A3028;
            border: 1px solid #E2B8B1;
        }

        .stock-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .stock-actions-left {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .stock-actions-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .stock-table-wrapper {
            overflow-x: auto;
            background: #FFF9F3;
            border-radius: 12px;
            border: 1px solid #DBC4A5;
        }

        .stock-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stock-table th,
        .stock-table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #E4D5C3;
            vertical-align: middle;
        }

        .stock-table th {
            background: #DBC4A5;
            color: #443223;
            font-weight: 600;
        }

        .stock-table tr:last-child td {
            border-bottom: none;
        }

        .product-name {
            font-weight: 600;
            color: #443223;
        }

        .product-unit {
            font-size: 13px;
            color: #7C7960;
        }

        .stock-table input[type="number"],
        .stock-table select {
            padding: 9px 10px;
            border: 1px solid #C9B69D;
            border-radius: 7px;
            background: #FFFFFF;
            color: #443223;
            font: inherit;
        }

        .stock-table input[type="number"] {
            width: 110px;
        }

        .stock-table select {
            width: 150px;
        }

        .stock-table input:focus,
        .stock-table select:focus {
            outline: none;
            border-color: #A08670;
        }

        .btn-save {
            border: none;
            border-radius: 7px;
            padding: 9px 15px;
            cursor: pointer;
            background: #72583E;
            color: #FFFFFF;
            font: inherit;
        }

        .btn-save:hover {
            background: #443223;
        }

        .btn-save-all {
            border: none;
            border-radius: 8px;
            padding: 11px 18px;
            cursor: pointer;
            background: #443223;
            color: #FFFFFF;
            font: inherit;
            font-weight: 600;
        }

        .btn-save-all:hover {
            background: #72583E;
        }

        .btn-secondary {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 8px;
            background: #DBC4A5;
            color: #443223;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #C9B69D;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: #72583E;
        }

        .status-hint {
            margin-top: 5px;
            font-size: 12px;
            color: #7C7960;
        }

    </style>

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-layout">

    <main class="main-content">

        <div class="page-header">

            <h1>Weekly Stock</h1>

            <p>
                Manage the quantity and availability of your
                products for the current week.
            </p>

        </div>

        <?php if (!empty($success)): ?>

            <div class="alert alert-success">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($errors)): ?>

            <div class="alert alert-error">

                <?php foreach ($errors as $error): ?>

                    <div>
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <div class="week-info">

            <strong>Current week:</strong>

            <?= htmlspecialchars(
                $week_start->format('F j, Y')
            ) ?>

            &ndash;

            <?= htmlspecialchars(
                (clone $week_start)
                    ->modify('+6 days')
                    ->format('F j, Y')
            ) ?>

        </div>

        <div class="stock-actions">

            <div class="stock-actions-left">

                <a
                    href="weekly_stock_template.php"
                    class="btn-secondary"
                >
                    Manage Weekly Stock Template
                </a>

            </div>


            <?php if (!empty($weekly_stock)): ?>

                <div class="stock-actions-right">

                    <button
                        type="submit"
                        form="save-all-form"
                        class="btn-save-all"
                    >
                        Save All Changes
                    </button>

                </div>

            <?php endif; ?>

        </div>

        <form
            method="POST"
            id="save-all-form"
        >

             <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_all"
            >

            <div class="stock-table-wrapper">

                <?php if (empty($weekly_stock)): ?>

                    <div class="empty-state">

                        <h3>No weekly stock yet</h3>

                        <p>
                            Add active products to your weekly
                            stock template to generate your
                            weekly stock.
                        </p>

                    </div>

                <?php else: ?>

                    <table class="stock-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Planned Quantity
                                </th>

                                <th>
                                    Actual Quantity
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Individual Save
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($weekly_stock as $stock): ?>

                            <?php

                            $stock_id =
                                (int) $stock['id'];

                            ?>

                            <tr>

                                <!-- Product -->

                                <td>

                                    <div class="product-name">

                                        <?= htmlspecialchars(
                                            $stock['product_name']
                                        ) ?>

                                    </div>


                                    <?php if (
                                        !empty($stock['unit'])
                                    ): ?>

                                        <div class="product-unit">

                                            Unit:

                                            <?= htmlspecialchars(
                                                $stock['unit']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </td>


                                <!-- Planned Quantity -->

                                <td>

                                    <?= htmlspecialchars(
                                        number_format(
                                            (float) $stock[
                                                'planned_quantity'
                                            ],
                                            2
                                        )
                                    ) ?>

                                </td>


                                <!-- Actual Quantity -->

                                <td>

                                    <input
                                        type="number"
                                        id="quantity-<?= $stock_id ?>"
                                        name="actual_quantity[<?= $stock_id ?>]"
                                        value="<?= htmlspecialchars(
                                            $stock[
                                                'actual_quantity'
                                            ]
                                        ) ?>"
                                        min="0"
                                        step="0.1"
                                    >

                                </td>


                                <!-- Status -->

                                <td>

                                    <select
                                        id="status-<?= $stock_id ?>"
                                        name="status[<?= $stock_id ?>]"
                                    >

                                        <option
                                            value="available"
                                            <?= $stock['status'] === 'available'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Available
                                        </option>

                                        <option
                                            value="sold_out"
                                            <?= $stock['status'] === 'sold_out'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Sold Out
                                        </option>

                                        <option
                                            value="unavailable"
                                            <?= $stock['status'] === 'unavailable'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Unavailable
                                        </option>

                                    </select>

                                </td>


                                <!-- Individual Save -->

                                <td>

                                    <button
                                        type="button"
                                        class="btn-save"
                                        onclick="saveSingleStock(<?= $stock_id ?>)"
                                    >
                                        Save
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </form>

        <?php foreach ($weekly_stock as $stock): ?>

            <?php

            $stock_id =
                (int) $stock['id'];

            ?>

            <form
                method="POST"
                id="single-update-<?= $stock_id ?>"
                style="display: none;"
            >

                 <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action"
                    value="update"
                >

                <input
                    type="hidden"
                    name="weekly_stock_id"
                    value="<?= $stock_id ?>"
                >

                <input
                    type="hidden"
                    name="actual_quantity"
                    id="single-quantity-<?= $stock_id ?>"
                >

                <input
                    type="hidden"
                    name="status"
                    id="single-status-<?= $stock_id ?>"
                >

            </form>

        <?php endforeach; ?>

    </main>

</div>


<script>

function saveSingleStock(id) {

    const quantityInput =
        document.getElementById(
            'quantity-' + id
        );

    const statusInput =
        document.getElementById(
            'status-' + id
        );

    const singleQuantity =
        document.getElementById(
            'single-quantity-' + id
        );

    const singleStatus =
        document.getElementById(
            'single-status-' + id
        );

    const singleForm =
        document.getElementById(
            'single-update-' + id
        );


    if (
        !quantityInput ||
        !statusInput ||
        !singleQuantity ||
        !singleStatus ||
        !singleForm
    ) {

        return;
    }


    singleQuantity.value =
        quantityInput.value;

    singleStatus.value =
        statusInput.value;


    singleForm.submit();
}

</script>
</body>
</html>