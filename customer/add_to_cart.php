<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

// --------------------------------------------------
// Get Product ID
// --------------------------------------------------

$productId = isset($_GET['id'])
    ? (int) $_GET['id']
    : (int) ($_POST['product_id'] ?? 0);

if ($productId <= 0) {
    header('Location: products.php');
    exit;
}


// --------------------------------------------------
// Get Product
// --------------------------------------------------

$product_stmt = $conn->prepare("
    SELECT
        p.id,
        p.farmer_id,
        p.category_id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.stock_quantity,
        p.image,
        p.is_available,
        p.moderation_status,
        p.created_at,
        c.name AS category_name,
        f.stall_name AS farmer_name,
        f.contact_person AS farmer_contact,
        f.description AS farmer_description,
        f.address AS farmer_address
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    LEFT JOIN farmers f
        ON p.farmer_id = f.id
    WHERE p.id = ?
      AND p.is_available = 1
      AND p.moderation_status = 'approved'
      AND f.approval_status = 'approved'
    LIMIT 1
");

$product_stmt->bind_param("i", $productId);
$product_stmt->execute();

$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

$product_stmt->close();


// --------------------------------------------------
// Product Not Found
// --------------------------------------------------

if (!$product) {
    header('Location: products.php');
    exit;
}


// --------------------------------------------------
// Handle Add To Cart
// --------------------------------------------------

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------
    // Get Quantity
    // --------------------------------------------------

    $quantity = isset($_POST['quantity'])
        ? (float) $_POST['quantity']
        : 0;


    // --------------------------------------------------
    // Validate Quantity
    // --------------------------------------------------

    if ($quantity <= 0) {

        $errorMessage = 'Please enter a valid quantity.';

    } else {

        $stockQuantity = (float) $product['stock_quantity'];


        // --------------------------------------------------
        // Check Stock
        // --------------------------------------------------

        if ($quantity > $stockQuantity) {

            $errorMessage =
                'The selected quantity is greater than the available stock.';

        } else {

            // --------------------------------------------------
            // Create Cart If It Does Not Exist
            // --------------------------------------------------

            if (
                !isset($_SESSION['cart']) ||
                !is_array($_SESSION['cart'])
            ) {
                $_SESSION['cart'] = [];
            }


            // --------------------------------------------------
            // Prevent Mixing Products From Different Farmers
            // --------------------------------------------------

            $cartFarmerId = null;

            foreach ($_SESSION['cart'] as $cartItem) {

                if (isset($cartItem['farmer_id'])) {

                    $cartFarmerId = (int) $cartItem['farmer_id'];

                    break;
                }
            }


            if (
                $cartFarmerId !== null &&
                $cartFarmerId !== (int) $product['farmer_id']
            ) {

                $errorMessage =
                    'Your cart already contains products from another farmer. '
                    . 'You can only order from one farmer at a time.';

            }


            // --------------------------------------------------
            // Only Continue If There Is No Error
            // --------------------------------------------------

            if (empty($errorMessage)) {

                // --------------------------------------------------
                // Product Already In Cart
                // --------------------------------------------------

                if (isset($_SESSION['cart'][$productId])) {

                    $currentQuantity = (float)
                        $_SESSION['cart'][$productId]['quantity'];

                    $newQuantity = $currentQuantity + $quantity;


                    // --------------------------------------------------
                    // Check Combined Quantity Against Stock
                    // --------------------------------------------------

                    if ($newQuantity > $stockQuantity) {

                        $errorMessage =
                            'The total quantity in your cart would exceed '
                            . 'the available stock.';

                    } else {

                        $_SESSION['cart'][$productId]['quantity'] =
                            $newQuantity;

                        $_SESSION['cart'][$productId]['subtotal'] =
                            $newQuantity * (float) $product['price'];

                        header('Location: cart.php?added=1');
                        exit;
                    }


                } else {

                    // --------------------------------------------------
                    // Add New Cart Item
                    // --------------------------------------------------

                    $_SESSION['cart'][$productId] = [
                        'product_id' => (int) $product['id'],
                        'name'       => $product['name'],
                        'price'      => (float) $product['price'],
                        'unit'       => $product['unit'],
                        'quantity'   => $quantity,
                        'image'      => $product['image'],
                        'farmer_id'  => (int) $product['farmer_id'],
                        'subtotal'   =>
                            $quantity * (float) $product['price']
                    ];

                    header('Location: cart.php?added=1');
                    exit;
                }
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

    <title>
        Add to Cart -
        <?= htmlspecialchars($product['name']) ?>
    </title>

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

</head>


<body>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">

    <div class="product-details-container">

        <!-- Back -->

        <a
            href="product_details.php?id=<?= (int) $product['id'] ?>"
            class="back-link"
        >
            ← Back to Product
        </a>


        <!-- Product Details -->

        <div class="product-details-card">


            <!-- Product Image -->

            <div class="product-image-section">

                <?php if (!empty($product['image'])): ?>

                    <img
                        src="../uploads/products/<?= htmlspecialchars($product['image']) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        class="product-detail-image"
                    >

                <?php else: ?>

                    <div class="product-image-placeholder">
                        No Image
                    </div>

                <?php endif; ?>

            </div>


            <!-- Product Information -->

            <div class="product-info-section">

                <div class="product-category">

                    <?= htmlspecialchars(
                        $product['category_name'] ?? 'Uncategorized'
                    ) ?>

                </div>


                <h1>
                    <?= htmlspecialchars($product['name']) ?>
                </h1>


                <?php if (!empty($product['description'])): ?>

                    <p class="product-description">
                        <?= htmlspecialchars($product['description']) ?>
                    </p>

                <?php endif; ?>


                <!-- Farmer -->

                <?php if (!empty($product['farmer_name'])): ?>

                    <div class="product-farmer">

                        <strong>From:</strong>

                        <?= htmlspecialchars($product['farmer_name']) ?>

                    </div>

                <?php endif; ?>


                <!-- Price -->

                <div class="product-price">

                    <?= number_format(
                        (float) $product['price'],
                        2
                    ) ?>

                    <span>
                        / <?= htmlspecialchars($product['unit']) ?>
                    </span>

                </div>


                <!-- Stock -->

                <div class="product-stock">

                    <strong>Available:</strong>

                    <?= htmlspecialchars($product['stock_quantity']) ?>

                    <?= htmlspecialchars($product['unit']) ?>

                </div>


                <!-- Error -->

                <?php if (!empty($errorMessage)): ?>

                    <div class="quantity-error">

                        <?= htmlspecialchars($errorMessage) ?>

                    </div>

                <?php endif; ?>


                <!-- Quantity Form -->

                <div class="quantity-section">

                    <h3>
                        Select Quantity
                    </h3>


                    <form
                        method="POST"
                        action="add_to_cart.php?id=<?= (int) $product['id'] ?>"
                    >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int) $product['id'] ?>"
                        >


                        <div class="quantity-box">

                            <label for="quantity">
                                Quantity
                            </label>

                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                class="quantity-input"
                                min="0.01"
                                max="<?= htmlspecialchars($product['stock_quantity']) ?>"
                                step="0.1"
                                value="1"
                                required
                                oninput="calculateTotal()"
                            >

                            <span class="quantity-unit">

                                <?= htmlspecialchars($product['unit']) ?>

                            </span>

                        </div>


                        <!-- Total -->

                        <div class="quantity-total">

                            <span>
                                Total
                            </span>

                            <strong>

                                <span id="totalPrice">

                                    <?= number_format(
                                        (float) $product['price'],
                                        2
                                    ) ?>

                                </span>

                            </strong>

                        </div>


                        <!-- Add To Cart -->

                        <button
                            type="submit"
                            class="add-to-cart-button"
                            <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>
                        >

                            <i class="fa-solid fa-cart-plus"></i>

                            <?= $product['stock_quantity'] > 0
                                ? 'Add to Cart'
                                : 'Out of Stock'
                            ?>

                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- Farmer Information -->

        <div class="farmer-section">

            <h2>
                Farmer Information
            </h2>


            <div class="farmer-info">

                <?php if (!empty($product['farmer_name'])): ?>

                    <div>

                        <strong>
                            Stall:
                        </strong>

                        <?= htmlspecialchars($product['farmer_name']) ?>

                    </div>

                <?php endif; ?>


                <?php if (!empty($product['farmer_contact'])): ?>

                    <div>

                        <strong>
                            Contact:
                        </strong>

                        <?= htmlspecialchars($product['farmer_contact']) ?>

                    </div>

                <?php endif; ?>


                <?php if (!empty($product['farmer_address'])): ?>

                    <div>

                        <strong>
                            Address:
                        </strong>

                        <?= htmlspecialchars($product['farmer_address']) ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>


<script>

const productPrice =
    <?= (float) $product['price'] ?>;

const maxStock =
    <?= (float) $product['stock_quantity'] ?>;


function calculateTotal() {

    const quantityInput =
        document.getElementById('quantity');

    const totalPrice =
        document.getElementById('totalPrice');

    let quantity =
        parseFloat(quantityInput.value);


    if (isNaN(quantity) || quantity < 0) {

        quantity = 0;
    }


    if (quantity > maxStock) {

        quantity = maxStock;

        quantityInput.value = maxStock;
    }


    const total =
        quantity * productPrice;


    totalPrice.textContent =
        total.toFixed(2);
}

</script>

</body>

</html>