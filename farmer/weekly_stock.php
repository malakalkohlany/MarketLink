<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$farmer_id = $_SESSION['farmer_id'] ?? null;

if (!$farmer_id) {
    redirect('auth/logout.php');
}

/*
|--------------------------------------------------------------------------
| Get Current Week
|--------------------------------------------------------------------------
| Monday is used as the beginning of the stocking week.
*/
$today = new DateTime();
$week_start = clone $today;

if ($week_start->format('N') != 1) {
    $week_start->modify('monday this week');
}

$week_start_date = $week_start->format('Y-m-d');

$errors = [];
$success = $_SESSION['weekly_stock_success'] ?? null;
unset($_SESSION['weekly_stock_success']);


/*
|--------------------------------------------------------------------------
| Handle Stock Updates
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Update Actual Weekly Stock
    |--------------------------------------------------------------------------
    */
    if ($action === 'update') {

        $weekly_stock_id = (int) ($_POST['weekly_stock_id'] ?? 0);
        $actual_quantity = $_POST['actual_quantity'] ?? '';
        $status = $_POST['status'] ?? '';

        if ($weekly_stock_id <= 0) {
            $errors[] = 'Invalid weekly stock item.';
        }

        if ($actual_quantity === '' || !is_numeric($actual_quantity)) {
            $errors[] = 'Please enter a valid quantity.';
        } elseif ((float) $actual_quantity < 0) {
            $errors[] = 'Quantity cannot be negative.';
        }

        $allowed_statuses = [
            'available',
            'sold_out',
            'unavailable'
        ];

        if (!in_array($status, $allowed_statuses, true)) {
            $errors[] = 'Invalid stock status.';
        }

        if (empty($errors)) {

            /*
            |--------------------------------------------------------------------------
            | Make sure this stock row belongs to this farmer
            |--------------------------------------------------------------------------
            */
            $check_stmt = $conn->prepare("
                SELECT id
                FROM weekly_stock
                WHERE id = ?
                  AND farmer_id = ?
                  AND week_start = ?
                LIMIT 1
            ");

            $check_stmt->bind_param(
                "iis",
                $weekly_stock_id,
                $farmer_id,
                $week_start_date
            );

            $check_stmt->execute();

            $check_result = $check_stmt->get_result();

            if (!$check_result->fetch_assoc()) {

                $errors[] = 'Weekly stock item not found.';

            } else {

                $update_stmt = $conn->prepare("
                    UPDATE weekly_stock
                    SET
                        actual_quantity = ?,
                        status = ?
                    WHERE id = ?
                      AND farmer_id = ?
                      AND week_start = ?
                ");

                $quantity = (float) $actual_quantity;

                $update_stmt->bind_param(
                    "dsiis",
                    $quantity,
                    $status,
                    $weekly_stock_id,
                    $farmer_id,
                    $week_start_date
                );

                if ($update_stmt->execute()) {

                    $_SESSION['weekly_stock_success'] =
                        'Weekly stock updated successfully.';

                    redirect('farmer/weekly_stock.php');

                } else {

                    $errors[] = 'Failed to update weekly stock.';
                }

                $update_stmt->close();
            }

            $check_stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Create Current Week From Active Templates
|--------------------------------------------------------------------------
|
| We use lazy generation:
| if the farmer opens this page and the current week's rows don't exist,
| active templates are copied into weekly_stock.
|
| The template is NOT modified when the weekly stock is later edited.
|
*/
$insert_stmt = $conn->prepare("
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
          WHERE ws.farmer_id = weekly_stock_templates.farmer_id
            AND ws.product_id = weekly_stock_templates.product_id
            AND ws.week_start = ?
      )
");

$insert_stmt->bind_param(
    "sis",
    $week_start_date,
    $farmer_id,
    $week_start_date
);

$insert_stmt->execute();
$insert_stmt->close();


/*
|--------------------------------------------------------------------------
| Get Current Week's Stock
|--------------------------------------------------------------------------
*/
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
        ON ws.product_id = p.id
    WHERE ws.farmer_id = ?
      AND ws.week_start = ?
    ORDER BY p.name ASC
");

$stock_stmt->bind_param(
    "is",
    $farmer_id,
    $week_start_date
);

$stock_stmt->execute();

$stock_result = $stock_stmt->get_result();

$weekly_stock = [];

while ($row = $stock_result->fetch_assoc()) {
    $weekly_stock[] = $row;
}

$stock_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Weekly Stock</title>

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

        .weekly-stock-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .weekly-stock-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .weekly-stock-header h1 {
            margin-bottom: 5px;
        }

        .week-label {
            color: #666;
        }

        .stock-table-wrapper {
            overflow-x: auto;
        }

        .stock-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stock-table th,
        .stock-table td {
            padding: 12px;
            text-align: left;
            vertical-align: middle;
        }

        .stock-table input,
        .stock-table select {
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
        }

        .stock-product {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stock-product img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 6px;
        }

        .stock-product-name {
            font-weight: 600;
        }

        .stock-unit {
            color: #777;
            font-size: 0.9rem;
        }

        .planned-quantity {
            color: #666;
        }

        .status-available {
            color: #3f6b45;
            font-weight: 600;
        }

        .status-sold-out {
            color: #8a5a35;
            font-weight: 600;
        }

        .status-unavailable {
            color: #8a4444;
            font-weight: 600;
        }

        .stock-update-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .stock-update-form input {
            max-width: 110px;
        }

        .stock-update-form select {
            max-width: 150px;
        }

        .empty-stock {
            padding: 30px;
            text-align: center;
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .alert-success {
            background: #e8f3e8;
            color: #365c39;
        }

        .alert-error {
            background: #f7e7e7;
            color: #7b3939;
        }

    </style>

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">

        <div class="weekly-stock-container">

            <div class="weekly-stock-header">

                <div>

                    <h1>This Week's Stock</h1>

                    <div class="week-label">
                        Week starting
                        <?= e(date('F j, Y', strtotime($week_start_date))) ?>
                    </div>

                </div>

                <div>

                    <a href="weekly_stock_template.php">
                        Manage Weekly Template
                    </a>

                </div>

            </div>


            <?php if ($success): ?>

                <div class="alert alert-success">
                    <?= e($success) ?>
                </div>

            <?php endif; ?>


            <?php if (!empty($errors)): ?>

                <div class="alert alert-error">

                    <?php foreach ($errors as $error): ?>

                        <div>
                            <?= e($error) ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <?php if (empty($weekly_stock)): ?>

                <div class="empty-stock">

                    <h2>No Weekly Stock</h2>

                    <p>
                        You don't have any active weekly stock templates yet.
                    </p>

                    <a href="weekly_stock_template.php">
                        Set Up Weekly Stock
                    </a>

                </div>

            <?php else: ?>

                <div class="stock-table-wrapper">

                    <table class="stock-table">

                        <thead>

                            <tr>

                                <th>Product</th>

                                <th>Normal Quantity</th>

                                <th>Actual Quantity</th>

                                <th>Status</th>

                                <th>Update</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($weekly_stock as $stock): ?>

                                <tr>

                                    <td>

                                        <div class="stock-product">

                                            <?php if (!empty($stock['image'])): ?>

                                                <img
                                                    src="<?= e($stock['image']) ?>"
                                                    alt="<?= e($stock['product_name']) ?>"
                                                >

                                            <?php endif; ?>

                                            <div>

                                                <div class="stock-product-name">
                                                    <?= e($stock['product_name']) ?>
                                                </div>

                                                <div class="stock-unit">
                                                    per <?= e($stock['unit']) ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <span class="planned-quantity">

                                            <?= e($stock['planned_quantity']) ?>

                                            <?= e($stock['unit']) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <form
                                            method="POST"
                                            class="stock-update-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update"
                                            >

                                            <input
                                                type="hidden"
                                                name="weekly_stock_id"
                                                value="<?= (int) $stock['id'] ?>"
                                            >

                                            <input
                                                type="number"
                                                name="actual_quantity"
                                                value="<?= e($stock['actual_quantity']) ?>"
                                                min="0"
                                                step="0.1"
                                                required
                                            >

                                    </td>


                                    <td>

                                        <select name="status">

                                            <option
                                                value="available"
                                                <?= $stock['status'] === 'available' ? 'selected' : '' ?>
                                            >
                                                Available
                                            </option>

                                            <option
                                                value="sold_out"
                                                <?= $stock['status'] === 'sold_out' ? 'selected' : '' ?>
                                            >
                                                Sold Out
                                            </option>

                                            <option
                                                value="unavailable"
                                                <?= $stock['status'] === 'unavailable' ? 'selected' : '' ?>
                                            >
                                                Temporarily Unavailable
                                            </option>

                                        </select>

                                    </td>


                                    <td>

                                            <button type="submit">
                                                Save
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </main>

</body>

</html>