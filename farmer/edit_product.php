<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$product_id = (int) $_GET['id'];

$user_id = getUserId();

if (!$user_id) {
    die("You must be logged in.");
}

// Get farmer
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

// Get product
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

// Update product
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

    // Keep current image
    $image_db_path = $product['image'];

    // Upload new image if selected
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

        $image_path = __DIR__ . '/../assets/images/products/' . $new_image_name;

        $image_db_path = 'assets/images/products/' . $new_image_name;

        if (!move_uploaded_file($image_tmp, $image_path)) {
            die("Failed to save the uploaded image.");
        }
    }

    // Update database
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
        die("Update failed: " . $stmt->error);
    }

    $stmt->close();

    header("Location: inventory.php");
    exit;
}

// Get active categories
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

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">

    <h1>Edit Product</h1>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

            <?= csrf_field() ?>

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

        <br><br>

        <label for="category_id">
            Category:
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
                    <?= (int) $category['id'] === (int) $product['category_id']
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= htmlspecialchars($category['name']) ?>
                </option>

            <?php endwhile; ?>

        </select>

        <br><br>

        <label for="description">
            Description:
        </label>

        <textarea
            id="description"
            name="description"
            rows="5"
        ><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

        <br><br>

        <label for="price">
            Price:
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

        <br><br>

        <label for="unit">
            Unit:
        </label>

        <input
            type="text"
            id="unit"
            name="unit"
            value="<?= htmlspecialchars($product['unit']) ?>"
            required
        >

        <br><br>

        <label for="stock_quantity">
            Stock Quantity:
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

        <br><br>

        <label for="image">
            Product Image:
        </label>

        <input
            type="file"
            id="image"
            name="image"
            accept="image/jpeg,image/png,image/webp"
        >

        <br><br>

        <?php if (!empty($product['image'])): ?>

            <p>
                Current Image:
            </p>

            <img
                src="../<?= htmlspecialchars($product['image']) ?>"
                alt="Current product image"
                width="120"
            >

        <?php endif; ?>

        <br><br>

        <button type="submit">
            Update Product
        </button>

    </form>

</main>

</body>

</html>

<?php

$category_stmt->close();

?>
