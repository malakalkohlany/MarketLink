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

    $image_path = __DIR__ . '/../assets/images/products/' . $new_image_name;
    $image_db_path = 'assets/images/products/' . $new_image_name;
    
    if (
        empty($name) ||
        $category_id <= 0 ||
        $price < 0 ||
        empty($unit) ||
        $stock_quantity < 0
    ) {
        die("Please enter valid product information.");
        }
        move_uploaded_file($image_tmp, $image_path);

$stmt = $conn->prepare("
    INSERT INTO products
    (farmer_id, category_id, name, description, price, unit, stock_quantity ,image)
    VALUES (?, ?, ?, ?, ?, ?, ?,?)
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
    die("Insert failed: " . $conn->error);
}

$stmt->close();
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
    <title>Add Product</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <h1>Add New Product</h1>

        <?php if (isset($success_message)): ?>

        <p><?= htmlspecialchars($success_message) ?></p>

        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" required>
            <br><br>

            <select id="category_id" name="category_id" required>
            <option value="">Select Category</option>

            <?php while ($category = $categories->fetch_assoc()): ?>
            <option value="<?= $category['id'] ?>">
                <?= htmlspecialchars($category['name']) ?>
            </option>
            <?php endwhile; ?>
            </select>
            <br><br>

            <label for="unit">Unit</label>
            <input type="text" id="unit" name="unit" placeholder="kg, piece, box" required>
            <br><br>

            <label for="stock_quantity">Stock Quantity</label>
            <input type="number" id="stock_quantity" name="stock_quantity" step="0.01" min="0" required>
            <br><br>        

            <label for="image">Product image</label>
            <input type="file" id="image" name="image" accept="image/*">
            <br><br>

            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
            <br><br>

            <label for="price">Price</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
            <br><br>

            <button type="submit" name="add_product">Add Product</button>
        </form>
    </main>

    <script src="../assets/js/app.js"></script>

</body>
</html>