<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$success = '';
$categories = [];
$edit_category = null;

$perPage = 10;

$currentPage = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'default' => 1,
            'min_range' => 1
        ]
    ]
);

$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid CSRF token.';

    } else {

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

                        redirect('admin/categories.php?success=added');

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

        } elseif (isset($_POST['update_category'])) {

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

                        redirect('admin/categories.php?success=updated');

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

        } elseif (isset($_POST['delete_category'])) {

            $id = filter_input(
                INPUT_POST,
                'delete_category',
                FILTER_VALIDATE_INT
            );

            if (!$id) {

                $errors[] = 'Invalid category.';

            } else {

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

                    if ((int) ($row['total'] ?? 0) > 0) {

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

                            redirect('admin/categories.php?success=deleted');
                        }
                    }
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

$totalCategories = 0;

if ($search !== '') {

    $keyword = '%' . $search . '%';

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM categories
        WHERE name LIKE ?
           OR description LIKE ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            'ss',
            $keyword,
            $keyword
        );

        if ($stmt->execute()) {

            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $totalCategories = (int) ($row['total'] ?? 0);
        }

        $stmt->close();
    }

} else {

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM categories
    ");

    if ($result) {

        $row = $result->fetch_assoc();
        $totalCategories = (int) ($row['total'] ?? 0);
    }
}

$totalPages = max(
    1,
    (int) ceil($totalCategories / $perPage)
);

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

if ($search !== '') {

    $keyword = '%' . $search . '%';

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
        LIMIT ? OFFSET ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            'ssii',
            $keyword,
            $keyword,
            $perPage,
            $offset
        );

        if ($stmt->execute()) {

            $result = $stmt->get_result();
            $categories = $result->fetch_all(MYSQLI_ASSOC);
        }

        $stmt->close();
    }

} else {

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
        GROUP BY
            c.id,
            c.name,
            c.description,
            c.image,
            c.status,
            c.created_at,
            c.updated_at
        ORDER BY c.name ASC
        LIMIT ? OFFSET ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            'ii',
            $perPage,
            $offset
        );

        if ($stmt->execute()) {

            $result = $stmt->get_result();
            $categories = $result->fetch_all(MYSQLI_ASSOC);
        }

        $stmt->close();
    }
}

$startItem = $totalCategories > 0
    ? $offset + 1
    : 0;

$endItem = min(
    $offset + $perPage,
    $totalCategories
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Categories | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/admin_ann.css">

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-categories-page">

    <section class="customer-page-hero admin-customers-hero">

        <div class="customer-page-hero-copy">

            <span class="eyebrow">
                ADMIN / CATEGORIES
            </span>

            <h1>
                Organize local <em>produce.</em>
            </h1>

            <p>
                Create and manage the categories farmers use
                to organize their products.
            </p>

        </div>

        <div class="customer-page-hero-mark">
            <span>06</span>
        </div>

    </section>


    <?php if (!empty($errors)): ?>

        <div class="admin-page-alert alert-danger">

            <span class="admin-alert-mark">
                !
            </span>

            <div>

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= e($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <div class="admin-page-alert alert-success">

            <span class="admin-alert-mark">
                ✓
            </span>

            <div>

                <p>
                    <?= e($success) ?>
                </p>

            </div>

        </div>

    <?php endif; ?>


    <section class="admin-category-form-section">

        <div class="admin-section-heading">

            <div>

                <span class="eyebrow">
                    01 / <?= $edit_category ? 'Edit' : 'New' ?>
                </span>

                <h2>
                    <?= $edit_category
                        ? 'Edit <em>category.</em>'
                        : 'Add a <em>category.</em>'
                    ?>
                </h2>

            </div>

            <?php if ($edit_category): ?>

                <a
                    href="categories.php"
                    class="admin-category-cancel"
                >
                    Cancel
                </a>

            <?php endif; ?>

        </div>


        <div class="admin-category-form-card">

            <form
                method="POST"
                action="categories.php"
            >

                <?= csrf_field() ?>

                <?php if ($edit_category): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $edit_category['id'] ?>"
                    >

                <?php endif; ?>


                <div class="admin-category-form-grid">

                    <div class="admin-category-form-field">

                        <label for="name">
                            Category name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="100"
                            value="<?= e(
                                $edit_category['name'] ?? ''
                            ) ?>"
                            placeholder="Fruits, Vegetables, Herbs..."
                            required
                        >

                    </div>


                    <div class="admin-category-form-field">

                        <label for="status">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                        >

                            <option
                                value="active"
                                <?= (
                                    ($edit_category['status'] ?? 'active')
                                    === 'active'
                                ) ? 'selected' : '' ?>
                            >
                                Active
                            </option>

                            <option
                                value="inactive"
                                <?= (
                                    ($edit_category['status'] ?? '')
                                    === 'inactive'
                                ) ? 'selected' : '' ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                    <div class="admin-category-form-field admin-category-description">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Describe this produce category"
                        ><?= e(
                            $edit_category['description'] ?? ''
                        ) ?></textarea>

                    </div>

                </div>


                <div class="admin-category-form-actions">

                    <?php if ($edit_category): ?>

                        <button
                            type="submit"
                            name="update_category"
                            class="btn btn-primary"
                        >
                            Update category
                        </button>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="add_category"
                            class="btn btn-primary"
                        >
                            Add category
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>

    </section>


    <section class="admin-management-section">

        <div class="admin-section-heading">

            <div>

                <span class="eyebrow">
                    02 / Directory
                </span>

                <h2>
                    Produce <em>categories.</em>
                </h2>

            </div>

            <span class="admin-record-count">

                <?= $totalCategories ?>

                <?= $totalCategories === 1
                    ? 'category'
                    : 'categories'
                ?>

            </span>

        </div>


        <form
            method="GET"
            action="categories.php"
            class="admin-category-search"
        >

            <div class="admin-category-search-input">

                <span>⌕</span>

                <input
                    type="text"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Search categories..."
                >

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Search
            </button>

            <?php if ($search !== ''): ?>

                <a
                    href="categories.php"
                    class="btn btn-soft"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>


        <div class="admin-categories-table">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (!empty($categories)): ?>

                    <?php foreach ($categories as $category): ?>

                        <tr>

                            <td class="admin-category-id">

                                #<?= (int) $category['id'] ?>

                            </td>


                            <td>

                                <div class="admin-category-name">

                                    <div class="admin-category-mark">
                                        <span>✦</span>
                                    </div>

                                    <strong>
                                        <?= e(
                                            $category['name']
                                        ) ?>
                                    </strong>

                                </div>

                            </td>


                            <td class="admin-category-description">

                                <?= !empty($category['description'])
                                    ? e($category['description'])
                                    : 'No description'
                                ?>

                            </td>


                            <td class="admin-category-products">

                                <?= (int) $category['product_count'] ?>

                                <span>
                                    <?= (int) $category['product_count'] === 1
                                        ? 'product'
                                        : 'products'
                                    ?>
                                </span>

                            </td>


                            <td>

                                <span
                                    class="admin-status admin-category-status-<?= e(
                                        $category['status']
                                    ) ?>"
                                >
                                    <?= ucfirst(
                                        e($category['status'])
                                    ) ?>
                                </span>

                            </td>


                            <td class="admin-category-date">

                                <?= !empty($category['created_at'])
                                    ? date(
                                        'M j, Y',
                                        strtotime(
                                            $category['created_at']
                                        )
                                    )
                                    : 'N/A'
                                ?>

                            </td>


                            <td>

                                <div class="admin-category-actions">

                                    <a
                                        href="categories.php?edit=<?= (int) $category['id'] ?>&page=<?= $currentPage ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
                                        class="admin-action-view"
                                    >
                                        Edit
                                    </a>


                                    <?php if (
                                        (int) $category['product_count'] === 0
                                    ): ?>

                                        <form
                                            method="POST"
                                            action="categories.php"
                                            onsubmit="return confirm('Are you sure you want to delete this produce category?')"
                                        >

                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="delete_category"
                                                value="<?= (int) $category['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="admin-action-reject"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            class="admin-table-empty"
                        >

                            <span>✦</span>

                            <strong>
                                No categories found.
                            </strong>

                            <p>
                                <?= $search !== ''
                                    ? 'Try a different search term.'
                                    : 'Create your first produce category above.'
                                ?>
                            </p>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


        <?php if ($totalPages > 1): ?>

            <div class="admin-pagination">

                <div class="admin-pagination-info">

                    Showing
                    <strong><?= $startItem ?></strong>
                    –
                    <strong><?= $endItem ?></strong>
                    of
                    <strong><?= $totalCategories ?></strong>

                </div>


                <div class="admin-pagination-controls">

                    <?php

                    $searchQuery = $search !== ''
                        ? '&search=' . urlencode($search)
                        : '';

                    ?>


                    <?php if ($currentPage > 1): ?>

                        <a
                            href="?page=<?= $currentPage - 1 ?><?= $searchQuery ?>"
                            class="admin-pagination-arrow"
                        >
                            Previous
                        </a>

                    <?php else: ?>

                        <span class="admin-pagination-arrow disabled">
                            Previous
                        </span>

                    <?php endif; ?>


                    <?php

                    $paginationStart = max(
                        1,
                        $currentPage - 2
                    );

                    $paginationEnd = min(
                        $totalPages,
                        $currentPage + 2
                    );

                    ?>


                    <?php if ($paginationStart > 1): ?>

                        <a
                            href="?page=1<?= $searchQuery ?>"
                            class="admin-pagination-number"
                        >
                            1
                        </a>

                        <?php if ($paginationStart > 2): ?>

                            <span class="admin-pagination-dots">
                                …
                            </span>

                        <?php endif; ?>

                    <?php endif; ?>


                    <?php for (
                        $page = $paginationStart;
                        $page <= $paginationEnd;
                        $page++
                    ): ?>

                        <?php if ($page === $currentPage): ?>

                            <span
                                class="admin-pagination-number active"
                            >
                                <?= $page ?>
                            </span>

                        <?php else: ?>

                            <a
                                href="?page=<?= $page ?><?= $searchQuery ?>"
                                class="admin-pagination-number"
                            >
                                <?= $page ?>
                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>


                    <?php if ($paginationEnd < $totalPages): ?>

                        <?php if ($paginationEnd < $totalPages - 1): ?>

                            <span class="admin-pagination-dots">
                                …
                            </span>

                        <?php endif; ?>

                        <a
                            href="?page=<?= $totalPages ?><?= $searchQuery ?>"
                            class="admin-pagination-number"
                        >
                            <?= $totalPages ?>
                        </a>

                    <?php endif; ?>


                    <?php if ($currentPage < $totalPages): ?>

                        <a
                            href="?page=<?= $currentPage + 1 ?><?= $searchQuery ?>"
                            class="admin-pagination-arrow"
                        >
                            Next
                        </a>

                    <?php else: ?>

                        <span class="admin-pagination-arrow disabled">
                            Next
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>

</html>