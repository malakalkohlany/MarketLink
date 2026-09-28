<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$product_id = (int) $_GET['id'];

$user_id = getUserId();

if (!$user_id) {
    die("You must be logged in.");
}


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

$stmt->close();

if (!$farmer) {
    die("Farmer profile not found.");
}

$farmer_id = (int) $farmer['id'];

$product_stmt = $conn->prepare("
    SELECT
        id,
        category_id,
        name,
        description,
        price,
        unit,
        stock_quantity,
        image
    FROM products
    WHERE id = ?
      AND farmer_id = ?
    LIMIT 1
");

$product_stmt->bind_param("ii", $product_id, $farmer_id);
$product_stmt->execute();

$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

$product_stmt->close();

if (!$product) {
    die("Product not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: edit_product.php?id=' . $product_id);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $stock_quantity = (float) ($_POST['stock_quantity'] ?? 0);

    if (
        empty($name) ||
        $category_id <= 0 ||
        $price < 0 ||
        empty($unit) ||
        $stock_quantity < 0
    ) {
        die("Please fill all required fields correctly.");
    }

    $image_db_path = $product['image'];

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            die("Failed to upload image.");
        }

        $image_tmp = $_FILES['image']['tmp_name'];

        $image_info = getimagesize($image_tmp);

        if ($image_info === false) {
            die("The uploaded file is not a valid image.");
        }

        $allowed_types = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $mime_type = $image_info['mime'];

        if (!isset($allowed_types[$mime_type])) {
            die("Only JPG, PNG, and WebP images are allowed.");
        }

        $max_file_size = 5 * 1024 * 1024; // 5 MB

        if ($_FILES['image']['size'] > $max_file_size) {
            die("Image size must not exceed 5 MB.");
        }

        $image_extension = $allowed_types[$mime_type];

        $new_image_name = uniqid('product_', true) . '.' . $image_extension;

        $image_path = __DIR__ . '/../uploads/products/' . $new_image_name;

        $image_db_path = 'uploads/products/' . $new_image_name;

        if (!move_uploaded_file($image_tmp, $image_path)) {
            die("Failed to save the uploaded image.");
        }
    }

    
    $stmt = $conn->prepare("
        UPDATE products
        SET
            category_id = ?,
            name = ?,
            description = ?,
            price = ?,
            unit = ?,
            stock_quantity = ?,
            image = ?
        WHERE id = ?
          AND farmer_id = ?
    ");

    $stmt->bind_param(
        "issdsdsii",
        $category_id,
        $name,
        $description,
        $price,
        $unit,
        $stock_quantity,
        $image_db_path,
        $product_id,
        $farmer_id
    );

    if (!$stmt->execute()) {
        die("Update failed.");
    }

    $stmt->close();


    redirect('farmer/inventory.php');
}

$category_stmt = $conn->prepare("
    SELECT
        id,
        name
    FROM categories
    WHERE status = 'active'
    ORDER BY name ASC
");

$category_stmt->execute();

$categories = $category_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Edit Product | MarketLink</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/farmer.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content farmer-add-product-page">

    <section class="customer-page-hero">
        <div class="customer-page-hero-copy">
            <span class="eyebrow">FARMER / PRODUCTS</span>

            <h1>
                Refine your <em>produce.</em>
            </h1>

            <p>
                Update your product details, stock, pricing, or image to keep your MarketLink listing current.
            </p>
        </div>

        <div class="customer-page-hero-mark">
            03
        </div>
    </section>

    <section class="farmer-add-product-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    01 / PRODUCT DETAILS
                </span>

                <h2>
                    Edit your <em>listing.</em>
                </h2>
            </div>
        </div>

        <div class="farmer-product-form-card">

            <form
                class="farmer-product-form"
                method="POST"
                enctype="multipart/form-data"
            >

                <?= csrf_field() ?>

                <div class="farmer-form-grid">

                    <div class="farmer-form-field farmer-form-field-full">
                        <label for="name">
                            Product Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($product['name']) ?>"
                            required
                        >
                    </div>

                    <div class="farmer-form-field">
                        <label for="category_id">
                            Category
                        </label>

                        <select
                            id="category_id"
                            name="category_id"
                            required
                        >
                            <option value="">
                                Select Category
                            </option>

                            <?php while ($category = $categories->fetch_assoc()): ?>

                                <option
                                    value="<?= (int) $category['id'] ?>"
                                    <?= (int) $category['id'] === (int) $product['category_id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>

                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="farmer-form-field">
                        <label for="unit">
                            Unit
                        </label>

                        <input
                            type="text"
                            id="unit"
                            name="unit"
                            value="<?= htmlspecialchars($product['unit']) ?>"
                            placeholder="kg, piece, box"
                            required
                        >
                    </div>

                    <div class="farmer-form-field">
                        <label for="stock_quantity">
                            Stock Quantity
                        </label>

                        <input
                            type="number"
                            id="stock_quantity"
                            name="stock_quantity"
                            step="0.1"
                            min="0"
                            value="<?= htmlspecialchars($product['stock_quantity']) ?>"
                            required
                        >
                    </div>

                    <div class="farmer-form-field">
                        <label for="price">
                            Price
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            step="0.1"
                            min="0"
                            value="<?= htmlspecialchars($product['price']) ?>"
                            required
                        >
                    </div>

                    <div class="farmer-form-field farmer-form-field-full">
                        <label for="image">
                            Product Image
                        </label>

                        <label class="farmer-file-input" for="image">

                            <input
                                type="file"
                                id="image"
                                name="image"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <span class="farmer-file-input-icon">
                                <i data-lucide=" fa-cloud-arrow-up"></i>
                            </span>

                            <strong>
                                Choose a product image
                            </strong>

                            <span class="farmer-file-input-note">
                                Click anywhere here to replace the current image.
                            </span>

                            <span class="farmer-file-input-name" id="farmerFileName">
                                No new image selected
                            </span>

                        </label>

                        <?php if (!empty($product['image'])): ?>

                            <div class="farmer-current-image">

                                <span class="farmer-current-image-label">
                                    Current Image
                                </span>

                                <img
                                    src="../<?= htmlspecialchars($product['image']) ?>"
                                    alt="Current product image"
                                >

                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="farmer-form-field farmer-form-field-full">
                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                        ><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>

                </div>

                <div class="farmer-form-actions">

                    <a
                        href="products.php"
                        class="farmer-form-cancel"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="farmer-form-submit"
                    >
                        <i data-lucide=" check"></i>
                        Update Product
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<script src="../assets/js/app.js"></script>

<script>
const imageInput = document.getElementById('image');
const fileName = document.getElementById('farmerFileName');

if (imageInput && fileName) {
    imageInput.addEventListener('change', function () {
        fileName.textContent = this.files.length
            ? this.files[0].name
            : 'No new image selected';
    });
}
</script>

</body>
</html>

<?php
$category_stmt->close();
?>