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
| Handle Add / Update / Toggle
|--------------------------------------------------------------------------
*/

$errors = [];
$success = $_SESSION['weekly_stock_success'] ?? null;
unset($_SESSION['weekly_stock_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Add product to weekly template
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $product_id = (int) ($_POST['product_id'] ?? 0);
        $default_quantity = (float) ($_POST['default_quantity'] ?? 0);

        if ($product_id <= 0) {
            $errors[] = 'Please select a product.';
        }

        if ($default_quantity <= 0) {
            $errors[] = 'Weekly quantity must be greater than 0.';
        }

        /*
        | Verify product belongs to this farmer
        */

        if (empty($errors)) {

            $stmt = $conn->prepare("
                SELECT id
                FROM products
                WHERE id = ?
                  AND farmer_id = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "ii",
                $product_id,
                $farmer_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if (!$result->fetch_assoc()) {
                $errors[] = 'Invalid product.';
            }

            $stmt->close();
        }

        /*
        | Check whether it is already in the template
        */

        if (empty($errors)) {

            $stmt = $conn->prepare("
                SELECT id
                FROM weekly_stock_templates
                WHERE farmer_id = ?
                  AND product_id = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "ii",
                $farmer_id,
                $product_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->fetch_assoc()) {
                $errors[] = 'This product is already in your weekly stock template.';
            }

            $stmt->close();
        }

        /*
        | Insert template item
        */

        if (empty($errors)) {

            $stmt = $conn->prepare("
                INSERT INTO weekly_stock_templates
                    (
                        farmer_id,
                        product_id,
                        default_quantity,
                        is_active
                    )
                VALUES
                    (?, ?, ?, 1)
            ");

            $stmt->bind_param(
                "iid",
                $farmer_id,
                $product_id,
                $default_quantity
            );

            if ($stmt->execute()) {

                $_SESSION['weekly_stock_success'] =
                    'Product added to your weekly stock template.';

                $stmt->close();

                redirect('farmer/weekly_stock_template.php');

            } else {

                $errors[] = 'Failed to add product to weekly stock template.';
            }

            $stmt->close();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update template quantity
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'update') {

        $template_id = (int) ($_POST['template_id'] ?? 0);
        $default_quantity = (float) ($_POST['default_quantity'] ?? 0);

        if ($template_id <= 0) {
            $errors[] = 'Invalid template item.';
        }

        if ($default_quantity <= 0) {
            $errors[] = 'Weekly quantity must be greater than 0.';
        }

        if (empty($errors)) {

            $stmt = $conn->prepare("
                UPDATE weekly_stock_templates
                SET default_quantity = ?
                WHERE id = ?
                  AND farmer_id = ?
            ");

            $stmt->bind_param(
                "dii",
                $default_quantity,
                $template_id,
                $farmer_id
            );

            if ($stmt->execute()) {

                $_SESSION['weekly_stock_success'] =
                    'Weekly quantity updated.';

                $stmt->close();

                redirect('farmer/weekly_stock_template.php');

            } else {

                $errors[] = 'Failed to update weekly quantity.';
            }

            $stmt->close();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle active status
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'toggle') {

        $template_id = (int) ($_POST['template_id'] ?? 0);

        if ($template_id <= 0) {
            $errors[] = 'Invalid template item.';
        }

        if (empty($errors)) {

            $stmt = $conn->prepare("
                UPDATE weekly_stock_templates
                SET is_active = IF(is_active = 1, 0, 1)
                WHERE id = ?
                  AND farmer_id = ?
            ");

            $stmt->bind_param(
                "ii",
                $template_id,
                $farmer_id
            );

            if ($stmt->execute()) {

                $_SESSION['weekly_stock_success'] =
                    'Weekly stock template updated.';

                $stmt->close();

                redirect('farmer/weekly_stock_template.php');

            } else {

                $errors[] = 'Failed to update template.';
            }

            $stmt->close();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Get farmer's products that are not already in the template
|--------------------------------------------------------------------------
*/

$available_products = [];

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.unit
    FROM products p
    LEFT JOIN weekly_stock_templates wst
        ON wst.product_id = p.id
       AND wst.farmer_id = ?
    WHERE p.farmer_id = ?
      AND wst.id IS NULL
    ORDER BY p.name ASC
");

$stmt->bind_param(
    "ii",
    $farmer_id,
    $farmer_id
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $available_products[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Get current weekly template
|--------------------------------------------------------------------------
*/

$templates = [];

$stmt = $conn->prepare("
    SELECT
        wst.id,
        wst.product_id,
        wst.default_quantity,
        wst.is_active,
        p.name AS product_name,
        p.unit
    FROM weekly_stock_templates wst
    INNER JOIN products p
        ON p.id = wst.product_id
    WHERE wst.farmer_id = ?
    ORDER BY p.name ASC
");

$stmt->bind_param(
    "i",
    $farmer_id
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $templates[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Weekly Stock Template</title>

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

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">

    <section class="dashboard-section">

        <div class="section-heading">

            <div>

                <h1>Weekly Stock Template</h1>

                <p>
                    Set the products and quantities you normally bring
                    each week.
                </p>

            </div>

            <a
                href="weekly_stock.php"
                class="button"
            >
                Manage This Week
            </a>

        </div>

        <?php if (!empty($success)): ?>

            <div class="success-message">
                <?= e($success) ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($errors)): ?>

            <div class="error-message">

                <?php foreach ($errors as $error): ?>

                    <p><?= e($error) ?></p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


    </section>


    <!-- =======================================================
         ADD PRODUCT
    ======================================================== -->

    <?php if (!empty($available_products)): ?>

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <h2>Add Product</h2>

                    <p>
                        Choose a product and set its normal weekly quantity.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                class="weekly-stock-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="add"
                >


                <div class="form-group">

                    <label for="product_id">
                        Product
                    </label>

                    <select
                        name="product_id"
                        id="product_id"
                        required
                    >

                        <option value="">
                            Select a product
                        </option>

                        <?php foreach ($available_products as $product): ?>

                            <option
                                value="<?= (int) $product['id'] ?>"
                            >
                                <?= e($product['name']) ?>
                                (<?= e($product['unit']) ?>)
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="default_quantity">
                        Normal Weekly Quantity
                    </label>

                    <input
                        type="number"
                        name="default_quantity"
                        id="default_quantity"
                        min="0.1"
                        step="0.1"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="button"
                >
                    Add to Weekly Stock
                </button>

            </form>

        </section>

    <?php endif; ?>


    <!-- =======================================================
         CURRENT TEMPLATE
    ======================================================== -->

    <section class="dashboard-section">

        <div class="section-heading">

            <div>

                <h2>My Weekly Products</h2>

                <p>
                    These quantities are your normal weekly stock.
                </p>

            </div>

        </div>


        <?php if (empty($templates)): ?>

            <div class="empty-state">

                <h3>No weekly stock products yet</h3>

                <p>
                    Add products above to create your recurring
                    weekly stock template.
                </p>

            </div>

        <?php else: ?>

            <div class="weekly-stock-table-wrapper">

                <table class="weekly-stock-table">

                    <thead>

                        <tr>

                            <th>Product</th>

                            <th>Unit</th>

                            <th>Normal Quantity</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($templates as $template): ?>

                        <tr>

                            <td>
                                <?= e($template['product_name']) ?>
                            </td>

                            <td>
                                <?= e($template['unit']) ?>
                            </td>


                            <td>

                                <form
                                    method="POST"
                                    class="inline-update-form"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update"
                                    >

                                    <input
                                        type="hidden"
                                        name="template_id"
                                        value="<?= (int) $template['id'] ?>"
                                    >

                                    <input
                                        type="number"
                                        name="default_quantity"
                                        value="<?= e($template['default_quantity']) ?>"
                                        min="0.1"
                                        step="0.1"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="small-button"
                                    >
                                        Save
                                    </button>

                                </form>

                            </td>


                            <td>

                                <?php if ($template['is_active']): ?>

                                    <span class="stock-status active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="stock-status inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <form
                                    method="POST"
                                    class="inline-form"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="toggle"
                                    >

                                    <input
                                        type="hidden"
                                        name="template_id"
                                        value="<?= (int) $template['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="small-button secondary"
                                    >
                                        <?= $template['is_active']
                                            ? 'Deactivate'
                                            : 'Activate'
                                        ?>
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>

</html>