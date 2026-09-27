<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$farmer = null;
$products = [];

if (!$id) {
    die('Invalid farmer ID.');
}

$stmt = $conn->prepare("
    SELECT
        f.id,
        f.user_id,
        f.stall_name,
        f.contact_person,
        f.description,
        f.address,
        f.latitude,
        f.longitude,
        u.email,
        f.approval_status,
        f.created_at,
        f.updated_at
    FROM farmers f
    LEFT JOIN users u
        ON f.user_id = u.id
    WHERE f.id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Failed to prepare farmer query.');
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die('Failed to load farmer.');
}

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();
$stmt->close();

if (!$farmer) {
    die('Farmer not found.');
}

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.moderation_status,
        p.created_at,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC
");

if (!$stmt) {
    die('Failed to prepare products query.');
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die('Failed to load farmer products.');
}

$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$farmerStatus = strtolower($farmer['approval_status'] ?? '');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Farmer Details | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-farmer-details">

    <section class="admin-details-hero">

        <div class="admin-details-hero-copy">

            <span class="eyebrow">ADMIN / FARMER DETAILS</span>

            <h1>
                Local
                <em>farmer.</em>
            </h1>

            <p>
                Review registration information, location details,
                and products associated with this farmer.
            </p>

        </div>

        <div class="admin-details-hero-mark">
            <span>✦</span>
        </div>

    </section>


    <section class="admin-details-section">

        <div class="admin-details-section-heading">

            <div>
                <span class="eyebrow">01 / Profile</span>

                <h2>
                    <?= htmlspecialchars($farmer['stall_name']) ?>
                </h2>
            </div>

            <a href="farmers.php" class="btn btn-outline">
                ← Back to farmers
            </a>

        </div>


        <div class="admin-farmer-profile">

            <div class="admin-farmer-profile-top">

                <div class="admin-farmer-profile-avatar">
                    <?= strtoupper(substr($farmer['stall_name'], 0, 1)) ?>
                </div>

                <div class="admin-farmer-profile-title">

                    <span class="admin-detail-label">
                        Stall
                    </span>

                    <h3>
                        <?= htmlspecialchars($farmer['stall_name']) ?>
                    </h3>

                    <span class="admin-detail-id">
                        Farmer #<?= (int) $farmer['id'] ?>
                    </span>

                </div>

                <span class="admin-status admin-status-<?= htmlspecialchars($farmerStatus) ?>">
                    <?= ucfirst(htmlspecialchars($farmerStatus ?: 'N/A')) ?>
                </span>

            </div>


            <div class="admin-details-grid">

                <div class="admin-detail-item">
                    <span class="admin-detail-label">Contact Person</span>
                    <strong>
                        <?= htmlspecialchars(
                            $farmer['contact_person'] ?? 'Not provided'
                        ) ?>
                    </strong>
                </div>

                <div class="admin-detail-item">
                    <span class="admin-detail-label">Email</span>
                    <strong>
                        <?= htmlspecialchars(
                            $farmer['email'] ?? 'Not provided'
                        ) ?>
                    </strong>
                </div>

                <div class="admin-detail-item admin-detail-wide">
                    <span class="admin-detail-label">Address</span>
                    <strong>
                        <?= htmlspecialchars(
                            $farmer['address'] ?? 'Not provided'
                        ) ?>
                    </strong>
                </div>

                <div class="admin-detail-item">
                    <span class="admin-detail-label">Joined</span>
                    <strong>
                        <?= !empty($farmer['created_at'])
                            ? date(
                                'M j, Y',
                                strtotime($farmer['created_at'])
                            )
                            : 'N/A'
                        ?>
                    </strong>
                </div>

                <div class="admin-detail-item">
                    <span class="admin-detail-label">Last Updated</span>
                    <strong>
                        <?= !empty($farmer['updated_at'])
                            ? date(
                                'M j, Y',
                                strtotime($farmer['updated_at'])
                            )
                            : 'N/A'
                        ?>
                    </strong>
                </div>

                <div class="admin-detail-item admin-detail-wide">
                    <span class="admin-detail-label">Description</span>

                    <p>
                        <?= !empty($farmer['description'])
                            ? nl2br(
                                htmlspecialchars($farmer['description'])
                            )
                            : 'No description provided.'
                        ?>
                    </p>
                </div>

                <div class="admin-detail-item admin-detail-wide">
                    <span class="admin-detail-label">Location</span>

                    <?php if (
                        $farmer['latitude'] !== null &&
                        $farmer['longitude'] !== null
                    ): ?>

                        <strong>
                            <?= htmlspecialchars($farmer['latitude']) ?>,
                            <?= htmlspecialchars($farmer['longitude']) ?>
                        </strong>

                    <?php else: ?>

                        <strong>Not provided</strong>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>


    <section class="admin-details-section admin-products-section">

        <div class="admin-details-section-heading">

            <div>
                <span class="eyebrow">02 / Inventory</span>

                <h2>
                    Farmer <em>products.</em>
                </h2>
            </div>

            <span class="admin-record-count">
                <?= count($products) ?>
                <?= count($products) === 1 ? 'product' : 'products' ?>
            </span>

        </div>


        <div class="admin-products-table">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Added</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($products)): ?>

                    <?php foreach ($products as $product): ?>

                        <?php
                        $productStatus = strtolower(
                            $product['moderation_status'] ?? ''
                        );
                        ?>

                        <tr>

                            <td class="admin-product-id">
                                #<?= (int) $product['id'] ?>
                            </td>

                            <td class="admin-product-name">
                                <?= htmlspecialchars($product['name']) ?>
                            </td>

                            <td class="admin-product-category">
                                <?= htmlspecialchars(
                                    $product['category_name'] ?? 'N/A'
                                ) ?>
                            </td>

                            <td class="admin-product-price">
                                <?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <span class="admin-status admin-status-<?= htmlspecialchars($productStatus) ?>">
                                    <?= ucfirst(
                                        htmlspecialchars(
                                            $productStatus ?: 'N/A'
                                        )
                                    ) ?>
                                </span>
                            </td>

                            <td class="admin-product-date">
                                <?= !empty($product['created_at'])
                                    ? date(
                                        'M j, Y',
                                        strtotime($product['created_at'])
                                    )
                                    : 'N/A'
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="admin-products-empty">
                            <span>✦</span>
                            <strong>No products yet.</strong>
                            <p>This farmer has not added any products.</p>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>