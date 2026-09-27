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
| Messages
|--------------------------------------------------------------------------
*/

$errors = [];

$success = $_SESSION['weekly_stock_success'] ?? null;
unset($_SESSION['weekly_stock_success']);


/*
|--------------------------------------------------------------------------
| Handle POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Add product to weekly template
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $product_id = (int) ($_POST['product_id'] ?? 0);

        $default_quantity_raw =
            $_POST['default_quantity'] ?? '';

        if ($product_id <= 0) {

            $errors[] = 'Please select a product.';

        }

        if (
            $default_quantity_raw === '' ||
            !is_numeric($default_quantity_raw)
        ) {

            $errors[] =
                'Weekly quantity must be a valid number.';

        } else {

            $default_quantity =
                (float) $default_quantity_raw;

            if ($default_quantity <= 0) {

                $errors[] =
                    'Weekly quantity must be greater than 0.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Verify product belongs to this farmer
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Check whether already in template
        |--------------------------------------------------------------------------
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

                $errors[] =
                    'This product is already in your weekly stock template.';
            }

            $stmt->close();
        }


        /*
        |--------------------------------------------------------------------------
        | Insert template
        |--------------------------------------------------------------------------
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
                VALUES (?, ?, ?, 1)
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

                redirect(
                    'farmer/weekly_stock_template.php'
                );

            } else {

                $errors[] =
                    'Failed to add product to weekly stock template.';
            }

            $stmt->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update ONE template quantity
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'update') {

        $template_id =
            (int) ($_POST['template_id'] ?? 0);

        $default_quantity_raw =
            $_POST['default_quantity'] ?? '';


        if ($template_id <= 0) {

            $errors[] =
                'Invalid template item.';
        }


        if (
            $default_quantity_raw === '' ||
            !is_numeric($default_quantity_raw)
        ) {

            $errors[] =
                'Weekly quantity must be a valid number.';

        } else {

            $default_quantity =
                (float) $default_quantity_raw;

            if ($default_quantity <= 0) {

                $errors[] =
                    'Weekly quantity must be greater than 0.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

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

                redirect(
                    'farmer/weekly_stock_template.php'
                );

            } else {

                $errors[] =
                    'Failed to update weekly quantity.';
            }

            $stmt->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE ALL TEMPLATE QUANTITIES
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'update_all') {

        $quantities =
            $_POST['default_quantity'] ?? [];


        if (!is_array($quantities)) {

            $errors[] =
                'Invalid weekly stock template data.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Validate everything BEFORE updating anything
            |--------------------------------------------------------------------------
            */

            $validated_rows = [];

            foreach ($quantities as $template_id => $quantity) {

                $template_id = (int) $template_id;


                if ($template_id <= 0) {

                    $errors[] =
                        'Invalid template item.';

                    break;
                }


                if ($quantity === '' || !is_numeric($quantity)) {

                    $errors[] =
                        'One of the weekly quantities is invalid.';

                    break;
                }


                $quantity = (float) $quantity;


                if ($quantity <= 0) {

                    $errors[] =
                        'Weekly quantities must be greater than 0.';

                    break;
                }


                $validated_rows[] = [
                    'id' => $template_id,
                    'quantity' => $quantity
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Save all
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

                $conn->begin_transaction();

                try {

                    $stmt = $conn->prepare("
                        UPDATE weekly_stock_templates
                        SET default_quantity = ?
                        WHERE id = ?
                          AND farmer_id = ?
                    ");


                    foreach ($validated_rows as $row) {

                        $stmt->bind_param(
                            "dii",
                            $row['quantity'],
                            $row['id'],
                            $farmer_id
                        );


                        if (!$stmt->execute()) {

                            throw new Exception(
                                'Failed to update weekly stock template.'
                            );
                        }
                    }


                    $stmt->close();

                    $conn->commit();


                    $_SESSION['weekly_stock_success'] =
                        'All weekly stock template quantities saved successfully.';


                    redirect(
                        'farmer/weekly_stock_template.php'
                    );

                } catch (Throwable $e) {

                    $conn->rollback();

                    if (isset($stmt)) {
                        $stmt->close();
                    }

                    $errors[] =
                        'Failed to save weekly stock template changes.';
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Toggle active status
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'toggle') {

        $template_id =
            (int) ($_POST['template_id'] ?? 0);


        if ($template_id <= 0) {

            $errors[] =
                'Invalid template item.';
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

                redirect(
                    'farmer/weekly_stock_template.php'
                );

            } else {

                $errors[] =
                    'Failed to update template.';
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

    <style>

        /*
        |--------------------------------------------------------------------------
        | Page-specific additions
        |--------------------------------------------------------------------------
        */

        .template-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .save-all-button {
            border: none;
            border-radius: 8px;
            padding: 11px 18px;
            cursor: pointer;
            background: #443223;
            color: #ffffff;
            font: inherit;
            font-weight: 600;
        }

        .save-all-button:hover {
            background: #72583E;
        }

        .template-quantity-input {
            width: 110px;
            padding: 8px 10px;
            border: 1px solid #C9B69D;
            border-radius: 7px;
            background: #FFFFFF;
            color: #443223;
            font: inherit;
        }

        .template-quantity-input:focus {
            outline: none;
            border-color: #A08670;
        }

        .template-actions-cell {
            white-space: nowrap;
        }

        .template-actions-cell button {
            margin-right: 5px;
        }

        .inline-button-form {
            display: inline;
            margin: 0;
        }

    </style>

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <section class="dashboard-section">

        <div class="section-heading">

            <div>

                <h1>
                    Weekly Stock Template
                </h1>

                <p>
                    Set the products and quantities you normally
                    bring each week.
                </p>

            </div>


            <a
                href="weekly_stock.php"
                class="button"
            >
                Manage This Week
            </a>

        </div>


        <!-- SUCCESS -->

        <?php if (!empty($success)): ?>

            <div class="success-message">

                <?= e($success) ?>

            </div>

        <?php endif; ?>


        <!-- ERRORS -->

        <?php if (!empty($errors)): ?>

            <div class="error-message">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= e($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         ADD PRODUCT
    ====================================================== -->

    <?php if (!empty($available_products)): ?>

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <h2>
                        Add Product
                    </h2>

                    <p>
                        Choose a product and set its normal
                        weekly quantity.
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

                                <?php if (!empty($product['unit'])): ?>

                                    (<?= e($product['unit']) ?>)

                                <?php endif; ?>

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
                        min="0.01"
                        step="0.01"
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


    <!-- =====================================================
         CURRENT TEMPLATE
    ====================================================== -->

    <section class="dashboard-section">

        <div class="section-heading">

            <div>

                <h2>
                    My Weekly Products
                </h2>

                <p>
                    These quantities are your normal weekly stock.
                    You can adjust this week's actual quantity separately
                    from the Weekly Stock page.
                </p>

            </div>

        </div>


        <?php if (empty($templates)): ?>

            <div class="empty-state">

                <h3>
                    No weekly stock products yet
                </h3>

                <p>
                    Add products above to create your recurring
                    weekly stock template.
                </p>

            </div>


        <?php else: ?>


            <!-- =================================================
                 SAVE ALL BUTTON
            ================================================== -->

            <div class="template-actions">

                <button
                    type="submit"
                    form="save-all-template-form"
                    class="save-all-button"
                >
                    Save All Changes
                </button>

            </div>


            <!-- =================================================
                 SAVE ALL FORM

                 The visible quantity inputs belong to this form.

                 Individual forms are NOT placed inside it.
            ================================================== -->

            <form
                method="POST"
                id="save-all-template-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="update_all"
                >


                <div class="weekly-stock-table-wrapper">

                    <table class="weekly-stock-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Unit
                                </th>

                                <th>
                                    Normal Quantity
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($templates as $template): ?>

                            <?php
                                $template_id =
                                    (int) $template['id'];
                            ?>


                            <tr>

                                <!-- PRODUCT -->

                                <td>

                                    <?= e(
                                        $template['product_name']
                                    ) ?>

                                </td>


                                <!-- UNIT -->

                                <td>

                                    <?= e(
                                        $template['unit']
                                    ) ?>

                                </td>


                                <!-- QUANTITY -->

                                <td>

                                    <input
                                        type="number"
                                        id="quantity-<?= $template_id ?>"
                                        name="default_quantity[<?= $template_id ?>]"
                                        value="<?= e(
                                            $template['default_quantity']
                                        ) ?>"
                                        min="0.01"
                                        step="0.01"
                                        required
                                        class="template-quantity-input"
                                    >

                                </td>


                                <!-- STATUS -->

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


                                <!-- ACTIONS -->

                                <td class="template-actions-cell">

                                    <!--
                                        Individual Save

                                        This button is NOT a submit button
                                        for the Save All form.

                                        JavaScript copies this row's quantity
                                        into the separate hidden form below.
                                    -->

                                    <button
                                        type="button"
                                        class="small-button"
                                        onclick="saveSingleTemplate(<?= $template_id ?>)"
                                    >
                                        Save
                                    </button>


                                    <!--
                                        Toggle remains an independent action.
                                    -->

                                    <button
                                        type="submit"
                                        form="toggle-template-<?= $template_id ?>"
                                        class="small-button secondary"
                                    >

                                        <?= $template['is_active']
                                            ? 'Deactivate'
                                            : 'Activate'
                                        ?>

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </form>


            <!-- =================================================
                 INDIVIDUAL QUANTITY UPDATE FORMS

                 These are deliberately OUTSIDE the Save All form.
            ================================================== -->

            <?php foreach ($templates as $template): ?>

                <?php
                    $template_id =
                        (int) $template['id'];
                ?>


                <!-- Individual quantity update -->

                <form
                    method="POST"
                    id="single-update-<?= $template_id ?>"
                    style="display: none;"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="update"
                    >

                    <input
                        type="hidden"
                        name="template_id"
                        value="<?= $template_id ?>"
                    >

                    <input
                        type="hidden"
                        name="default_quantity"
                        id="single-quantity-<?= $template_id ?>"
                    >

                </form>


                <!-- Individual activate/deactivate -->

                <form
                    method="POST"
                    id="toggle-template-<?= $template_id ?>"
                    style="display: none;"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="toggle"
                    >

                    <input
                        type="hidden"
                        name="template_id"
                        value="<?= $template_id ?>"
                    >

                </form>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>


<script>

/*
|--------------------------------------------------------------------------
| Save one template quantity
|--------------------------------------------------------------------------
|
| The visible quantity input belongs to the Save All form.
|
| For the individual Save button, we copy that value into the
| separate hidden form belonging to this specific template.
|
*/

function saveSingleTemplate(id) {

    const quantityInput =
        document.getElementById('quantity-' + id);

    const singleQuantity =
        document.getElementById('single-quantity-' + id);

    const singleForm =
        document.getElementById('single-update-' + id);


    if (
        !quantityInput ||
        !singleQuantity ||
        !singleForm
    ) {
        return;
    }


    /*
     * Copy the current value.
     */

    singleQuantity.value =
        quantityInput.value;


    /*
     * Submit only this template.
     */

    singleForm.submit();
}

</script>


</body>

</html>