<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product ID.");
}

$product_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];

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

$farmer_id = $farmer['id'];

$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $category_id = (int) $_POST['category_id'];
    $description = trim($_POST['description']);
    $price = (float) $_POST['price'];
    $unit = trim($_POST['unit']);
    $stock_quantity = (float) $_POST['stock_quantity'];
    
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

if (!empty($_FILES['image']['name'])) {

    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];

    $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
    $new_image_name = uniqid('product_', true) . '.' . $image_extension;

    $image_path = __DIR__ . '/../assets/images/products/' . $new_image_name;
    $image_db_path = 'assets/images/products/' . $new_image_name;

    move_uploaded_file($image_tmp, $image_path);
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
    WHERE id = ? AND farmer_id = ?
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
    WHERE id = ? AND farmer_id = ?
    LIMIT 1
");

$product_stmt->bind_param("ii", $product_id, $farmer_id);
$product_stmt->execute();

$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

$product_stmt->close();

$category_stmt = $conn->prepare("
    SELECT id, name
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
</head>
<body>
    <h1>Edit Product</h1>
    <form method="POST" enctype="multipart/form-data">

    <label for="name">Product Name</label>
    <input type="text" id="name" name="name" 
    value="<?= htmlspecialchars($product['name']) ?>" require>
    <br><br>

    <label for="category_id">Category:</label>
    <select id="category_id" name="category_id" required>

    <?php while ($category = $categories->fetch_assoc()): ?>

        <option
            value="<?= $category['id'] ?>"
            <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>
        >
            <?= htmlspecialchars($category['name']) ?>
        </option>

    <?php endwhile; ?>
    </select>
    <br><br>

    <label for="description">Description:</label>
    <textarea
    id="description"
    name="description"
    rows="5"><?= htmlspecialchars($product['description']) ?>
    </textarea>
    <br><br>

    <label for="price">Price:</label>
    <input
    type="number"
    id="price"
    name="price"
    step="0.01"
    min="0"
    value="<?= htmlspecialchars($product['price']) ?>"required>
    <br><br>

    <label for="unit">Unit:</label>
    <input
    type="text"
    id="unit"
    name="unit"
    value="<?= htmlspecialchars($product['unit']) ?>" required >
    <br><br>

    <label for="stock_quantity">Stock Quantity:</label>
    <input
    type="number"
    id="stock_quantity"
    name="stock_quantity"
    step="0.01"
    min="0"
    value="<?= htmlspecialchars($product['stock_quantity']) ?>"required>
    <br><br>

    <label for="image">Product Image:</label>
    <input
    type="file"
    id="image"
    name="image"
    accept="image/*">
    <br><br>

    <?php if (!empty($product['image'])): ?>
    <p>Current Image:</p>
    <img
        src="../<?= htmlspecialchars($product['image']) ?>"
        width="120">

    <?php endif; ?>
    <br><br>

        <button type="submit">Update Product</button>

    </form>
</body>
</html>