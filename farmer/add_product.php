<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$default_categories = [
    'Vegetables',
    'Fruits',
    'Grains',
    'Herbs',
    'Dairy Products',
    'Eggs',
    'Honey',
    'Meat',
    'Poultry',
    'Other'
];

$category_insert = $conn->prepare("
    INSERT IGNORE INTO categories (name, status)
    VALUES (?, 'active')
");

foreach ($default_categories as $category_name) {
    $category_insert->bind_param("s", $category_name);
    $category_insert->execute();
}

$category_insert->close();

$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: add_product.php');
        exit;
        }

$stmt = $conn->prepare("
    SELECT id
    FROM farmers
    WHERE user_id = ?
    LIMIT 1");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$farmer_id =$farmer['id'];

    $name = trim($_POST['name']);
    $category_id = (int) $_POST['category_id'];
    $description = trim($_POST['description']);
    $price = (float) $_POST['price'];
    $unit = trim($_POST['unit']);
    $stock_quantity = (float) $_POST['stock_quantity'];

    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];

    $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
    $new_image_name = uniqid('product_', true) . '.' . $image_extension;

if (
    empty($name) ||
    $category_id <= 0 ||
    $price < 0 ||
    empty($unit) ||
    $stock_quantity < 0 ||
    empty($description)
) {
    die("Please enter valid product information.");
}

$image_name = '';
$image_db_path = '';

if (
    isset($_FILES['image']) &&
    $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        die("Image upload failed.");
    }

    $image_tmp = $_FILES['image']['tmp_name'];

    $image_info = getimagesize($image_tmp);

    if ($image_info === false) {
        die("Uploaded file is not a valid image.");
    }

$allowed_types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowed_types[$image_info['mime']])) {
    die("Only JPG, PNG, and WebP images are allowed.");
}

$max_file_size = 5 * 1024 * 1024; // 5 MB

if ($_FILES['image']['size'] > $max_file_size) {
    die("Image size must not exceed 5 MB.");
}

$image_extension = $allowed_types[$image_info['mime']];

$new_image_name = uniqid('product_', true) . '.' . $image_extension;

$image_path = __DIR__ . '/../uploads/products/' . $new_image_name;

    $image_db_path = 'uploads/products/' . $new_image_name;

    if (!move_uploaded_file($image_tmp, $image_path)) {
        die("Failed to save product image.");
    }
}

$stmt = $conn->prepare("
    INSERT INTO products
    (farmer_id, category_id, name, description, price, unit, stock_quantity, image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iissdsds",
    $farmer_id,
    $category_id,
    $name,
    $description,
    $price,
    $unit,
    $stock_quantity,
    $image_db_path
);

if (!$stmt->execute()) {
    die("Insert failed.");
}

$stmt->close();

$farmerInfoStmt = $conn->prepare("
    SELECT
        f.stall_name,
        GROUP_CONCAT(mf.market_id) AS market_ids
    FROM farmers f
    LEFT JOIN market_farmer mf
        ON mf.farmer_id = f.id
    WHERE f.id = ?
    GROUP BY f.id, f.stall_name
    LIMIT 1
");

if ($farmerInfoStmt) {
    $farmerInfoStmt->bind_param("i", $farmer_id);
    $farmerInfoStmt->execute();

    $farmerInfoResult = $farmerInfoStmt->get_result();
    $farmerInfo = $farmerInfoResult->fetch_assoc();

    $farmerInfoStmt->close();

    if ($farmerInfo) {

        $stallName = $farmerInfo['stall_name'];

        $customerStmt = $conn->prepare("
            SELECT DISTINCT customer_id
            FROM (
                SELECT customer_id
                FROM favorite_farmers
                WHERE farmer_id = ?

                UNION

                SELECT fm.customer_id
                FROM favorite_markets fm
                INNER JOIN market_farmer mf
                    ON mf.market_id = fm.market_id
                WHERE mf.farmer_id = ?
            ) AS interested_customers
        ");

        if ($customerStmt) {
            $customerStmt->bind_param(
                "ii",
                $farmer_id,
                $farmer_id
            );

            $customerStmt->execute();

            $customerResult = $customerStmt->get_result();

            while ($customer = $customerResult->fetch_assoc()) {

                $customerId = (int)$customer['customer_id'];

                createNotification(
                    $conn,
                    $customerId,
                    'new_stock',
                    'New Stock Available',
                    "{$stallName} just added {$name}. Check it out!"
                );
            }

            $customerStmt->close();
        }
    }
}

$success_message = "Product added successfully!";
}

$category_stmt = $conn->prepare("
    SELECT id , name
    FROM categories
    WHERE status = 'active'
    ORDER BY name ASC");

$category_stmt->execute();
$categories = $category_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | MarketLink</title>
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

            <h1>Add fresh <em>produce.</em></h1>

            <p>
                Add a new product to your MarketLink inventory and make it
                available for customers to discover.
            </p>
        </div>

        <div class="customer-page-hero-mark">02</div>
    </section>

    <?php if (isset($success_message)): ?>

        <div class="customer-products-notice customer-products-success">
            <span class="customer-products-notice-icon">
                <i class="fa-solid fa-check"></i>
            </span>

            <div>
                <strong>Product added successfully.</strong>

                <span>
                    <?= htmlspecialchars($success_message) ?>
                </span>
            </div>
        </div>

    <?php endif; ?>

    <section class="farmer-add-product-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">01 / PRODUCT DETAILS</span>

                <h2>Create a new <em>listing.</em></h2>
            </div>
        </div>

        <div class="farmer-product-form-card">

            <form
                class="farmer-product-form"
                action=""
                method="POST"
                enctype="multipart/form-data"
            >

                <?= csrf_field() ?>

                <div class="farmer-form-grid">

                    <div class="farmer-form-field farmer-form-field-full">
                        <label for="name">Product Name</label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            required
                        >
                    </div>

                    <div class="farmer-form-field">
                        <label for="category_id">Category</label>

                        <select
                            id="category_id"
                            name="category_id"
                            required
                        >
                            <option value="">Select Category</option>

                            <?php while ($category = $categories->fetch_assoc()): ?>

                                <option value="<?= $category['id'] ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>

                            <?php endwhile; ?>

                        </select>
                    </div>

                    <div class="farmer-form-field">
                        <label for="unit">Unit</label>

                        <input
                            type="text"
                            id="unit"
                            name="unit"
                            placeholder="kg, piece, box"
                            required
                        >
                    </div>

                    <div class="farmer-form-field">
                        <label for="stock_quantity">Stock Quantity</label>

                        <input
                            type="number"
                            id="stock_quantity"
                            name="stock_quantity"
                            step="0.1"
                            min="0"
                            required
                        >
                    </div>

                    <div class="farmer-form-field">
                        <label for="price">Price</label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            step="0.1"
                            min="0"
                            required
                        >
                    </div>

                    <div class="farmer-form-field farmer-form-field-full">
                        <label for="image">Product Image</label>

                        <div class="farmer-file-input">
                            <input
                                type="file"
                                id="image"
                                name="image"
                                accept="image/*"
                                required
                            >

                            <span class="farmer-file-input-note">
                                Add a clear image of your product.
                            </span>
                        </div>
                    </div>

                    <div class="farmer-form-field farmer-form-field-full">
                        <label for="description">Description</label>

                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                        ></textarea>
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
                        name="add_product"
                        class="farmer-form-submit"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Product
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<script src="../assets/js/app.js"></script>

</body>
</html>