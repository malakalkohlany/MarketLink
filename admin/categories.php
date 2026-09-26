<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$success = '';
$categories = [];
$edit_category = null;




if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_category'])) {

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors[] = 'Invalid category status.';
        }

        if (empty($errors)) {

            $stmt = $conn->prepare("
                INSERT INTO categories
                (name, description, status)
                VALUES (?, ?, ?)
            ");

              if ($stmt) {

                $stmt->bind_param(
                    'sss',
                    $name,
                    $description,
                    $status
                );

                try {

                    $stmt->execute();
                    $stmt->close();

                    header('Location: categories.php?success=added');
                    exit;

                } catch (mysqli_sql_exception $e) {

                    if ($e->getCode() === 1062) {
                        $errors[] = 'A category with this name already exists.';
                    } else {
                        $errors[] = 'Failed to add category.';
                    }

                    $stmt->close();
                }
            }
        }
    }
     if (isset($_POST['update_category'])) {

        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (!$id) {
            $errors[] = 'Invalid category.';
        }

        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors[] = 'Invalid category status.';
        }

        if (empty($errors)) {

            $stmt = $conn->prepare("
                UPDATE categories
                SET
                    name = ?,
                    description = ?,
                    status = ?
                WHERE id = ?
            ");

            if ($stmt) {

                $stmt->bind_param(
                    'sssi',
                    $name,
                    $description,
                    $status,
                    $id
                );

                try {

                    $stmt->execute();
                    $stmt->close();

                    header('Location: categories.php?success=updated');
                    exit;

                } catch (mysqli_sql_exception $e) {

                    if ($e->getCode() === 1062) {
                        $errors[] = 'A category with this name already exists.';
                    } else {
                        $errors[] = 'Failed to update category.';
                    }

                    $stmt->close();
                }
            }
        }
    }
}

if (isset($_GET['delete'])) {

    $id = filter_input(
        INPUT_GET,
        'delete',
        FILTER_VALIDATE_INT
    );

    if ($id) {

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM products
            WHERE category_id = ?
        ");

        if ($stmt) {

            $stmt->bind_param('i', $id);
            $stmt->execute();

            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $stmt->close();

            if ((int)$row['total'] > 0) {

                $errors[] = 'This category cannot be deleted because products are assigned to it.';

            } else {

                $stmt = $conn->prepare("
                    DELETE FROM categories
                    WHERE id = ?
                ");

                if ($stmt) {

                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt->close();

                    header('Location: categories.php?success=deleted');
                    exit;
                }
            }
        }
    }
}

if (isset($_GET['edit'])) {

    $id = filter_input(
        INPUT_GET,
        'edit',
        FILTER_VALIDATE_INT
    );

    if ($id) {

        $stmt = $conn->prepare("
            SELECT
                id,
                name,
                description,
                image,
                status,
                created_at,
                updated_at
            FROM categories
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param('i', $id);
            $stmt->execute();

            $result = $stmt->get_result();
            $edit_category = $result->fetch_assoc();

            $stmt->close();
        }
    }
}

$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.name,
            c.description,
            c.image,
            c.status,
            c.created_at,
            c.updated_at,
            COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p
            ON p.category_id = c.id
        WHERE c.name LIKE ?
           OR c.description LIKE ?
        GROUP BY
            c.id,
            c.name,
            c.description,
            c.image,
            c.status,
            c.created_at,
            c.updated_at
        ORDER BY c.name ASC
    ");

    $keyword = '%' . $search . '%';

    $stmt->bind_param(
        'ss',
        $keyword,
        $keyword
    );
      $stmt->execute();

    $result = $stmt->get_result();
    $categories = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} else {

    $result = $conn->query("
        SELECT
            c.id,
            c.name,
            c.description,
            c.image,
            c.status,
            c.created_at,
            c.updated_at,
            COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p
            ON p.category_id = c.id
        GROUP BY
            c.id,
            c.name,
            c.description,
            c.image,
            c.status,
            c.created_at,
            c.updated_at
        ORDER BY c.name ASC
    ");

    if ($result) {
        $categories = $result->fetch_all(MYSQLI_ASSOC);
    }
}

if (isset($_GET['success'])) {

    if ($_GET['success'] === 'added') {
        $success = 'Produce category added successfully.';
    }

    if ($_GET['success'] === 'updated') {
        $success = 'Produce category updated successfully.';
    }

    if ($_GET['success'] === 'deleted') {
        $success = 'Produce category deleted successfully.';
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

    <title>Produce Categories | MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>
    <div class="admin-container">

    <aside class="sidebar">

        <div class="logo">
            MarketLink
        </div>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="markets.php">
                Markets
            </a>

            <a href="add_market.php">
                Add Market
            </a>

            <a href="categories.php" class="active">
                Produce Categories
            </a>

            <a href="farmers.php">
                Farmers
            </a>

            <a href="products.php">
                Produce
            </a>

            <a href="users.php">
                Users
            </a>

            <a href="orders.php">
                Orders
            </a>

            <a href="reviews.php">
                Reviews
            </a>

            <a href="announcements.php">
                Announcements
            </a>

            <a href="reports.php">
                Reports
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </nav>

    </aside>

    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>
                    Produce Categories
                </h1>

                <p>
                    Manage categories used to organize the Produce Guide.
                </p>

            </div>

        </div>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= htmlspecialchars($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <?php if ($success !== ''): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>

        <section class="form-section">

            <div class="section-header">

                <h2>
                    <?= $edit_category ? 'Edit Produce Category' : 'Add Produce Category' ?>
                </h2>

            </div>

            <form
                method="POST"
                action="categories.php"
            >

                <?php if ($edit_category): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)$edit_category['id'] ?>"
                    >

                <?php endif; ?>

                <div class="form-group">

                    <label for="name">
                        Category Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="100"
                        value="<?= htmlspecialchars($edit_category['name'] ?? '') ?>"
                        placeholder="Fruits, Vegetables, Herbs, Dairy..."
                        required
                    >
                       </div>

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Describe this produce category"
                    ><?= htmlspecialchars($edit_category['description'] ?? '') ?></textarea>

                </div>

                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option
                            value="active"
                            <?= (($edit_category['status'] ?? 'active') === 'active') ? 'selected' : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= (($edit_category['status'] ?? '') === 'inactive') ? 'selected' : '' ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

                <div class="form-actions">

                    <?php if ($edit_category): ?>

                        <button
                            type="submit"
                            name="update_category"
                            class="btn btn-primary"
                        >
                            Update Category
                        </button>

                        <a
                            href="categories.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="add_category"
                            class="btn btn-primary"
                        >
                            Add Category
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </section>
          <section class="table-section">

            <div class="section-header">

                <h2>
                    Produce Categories
                </h2>

                <form
                    method="GET"
                    action="categories.php"
                    class="search-form"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search produce categories..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Search
                    </button>

                    <?php if ($search !== ''): ?>

                        <a
                            href="categories.php"
                            class="btn btn-secondary"
                        >
                            Clear
                        </a>

                    <?php endif; ?>

                </form>

            </div>

            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Products
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($categories)): ?>

                            <?php foreach ($categories as $category): ?>

                                <tr>

                                    <td>
                                        <?= (int)$category['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($category['description'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= (int)$category['product_count'] ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars($category['status']) ?>"
                                        >
                                            <?= ucfirst(htmlspecialchars($category['status'])) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?= date(
                                            'Y-m-d',
                                            strtotime($category['created_at'])
                                        ) ?>
                                    </td>

                                    <td>

                                        <a
                                            href="categories.php?edit=<?= (int)$category['id'] ?>"
                                            class="btn btn-sm btn-primary"
                                        >
                                            Edit
                                        </a>

                                        <?php if ((int)$category['product_count'] === 0): ?>

                                            <a
                                                href="categories.php?delete=<?= (int)$category['id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this produce category?')"
                                            >
                                                Delete
                                            </a>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7">
                                    No produce categories found.
                                </td>

                            </tr>

                               <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>

</html>
