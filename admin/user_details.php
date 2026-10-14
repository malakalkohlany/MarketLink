<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {
    die('Invalid user ID.');
}

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email,
        role,
        status,
        created_at,
        updated_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Failed to prepare user query.');
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die('Failed to load user.');
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die('User not found.');
}

$userRole = strtolower($user['role'] ?? '');
$userStatus = strtolower($user['status'] ?? '');

$farmer = null;
$products = [];

if ($userRole === 'farmer') {

    $stmt = $conn->prepare("
        SELECT
            id,
            stall_name,
            contact_person,
            description,
            address,
            latitude,
            longitude,
            approval_status,
            created_at,
            updated_at
        FROM farmers
        WHERE user_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        die('Failed to prepare farmer query.');
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        die('Failed to load farmer profile.');
    }

    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();
    $stmt->close();

    if ($farmer) {

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

        $farmerId = (int) $farmer['id'];

        $stmt->bind_param('i', $farmerId);

        if (!$stmt->execute()) {
            die('Failed to load farmer products.');
        }

        $result = $stmt->get_result();
        $products = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
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

    <title>User Details | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-farmer-details admin-user-details">

    <section class="admin-details-hero">

        <div class="admin-details-hero-copy">

            <span class="eyebrow">ADMIN / USER DETAILS</span>

            <h1>
                Local
                <em>community.</em>
            </h1>

            <p>
                Review account information, access status, and
                associated activity across the MarketLink platform.
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
                    <?= e($user['name'] ?? 'Unknown user') ?>
                </h2>
            </div>

            <a href="user.php" class="btn btn-outline">
                ← Back to users
            </a>

        </div>

        <div class="admin-farmer-profile">

            <div class="admin-farmer-profile-top">

                <div class="admin-farmer-profile-avatar">
                    <?= e(
                        strtoupper(
                            substr(
                                $user['name'] ?: '?',
                                0,
                                1
                            )
                        )
                    ) ?>
                </div>

                <div class="admin-farmer-profile-title">

                    <span class="admin-detail-label">
                        Account
                    </span>

                    <h3>
                        <?= e($user['name'] ?? 'Unknown user') ?>
                    </h3>

                    <span class="admin-detail-id">
                        User #<?= (int) $user['id'] ?>
                    </span>

                </div>

                <span class="admin-status admin-status-<?= e($userStatus) ?>">
                    <?= e(ucfirst($userStatus ?: 'N/A')) ?>
                </span>

            </div>

            <div class="admin-details-grid">

                <div class="admin-detail-item">
                    <span class="admin-detail-label">
                        Full Name
                    </span>

                    <strong>
                        <?= e($user['name'] ?? 'Not provided') ?>
                    </strong>
                </div>

                <div class="admin-detail-item">
                    <span class="admin-detail-label">
                        Email Address
                    </span>

                    <strong>
                        <?= e($user['email'] ?? 'Not provided') ?>
                    </strong>
                </div>

                <div class="admin-detail-item">
                    <span class="admin-detail-label">
                        Account Role
                    </span>

                    <strong>
                        <?= e(ucfirst($userRole ?: 'N/A')) ?>
                    </strong>
                </div>

                <div class="admin-detail-item">
                    <span class="admin-detail-label">
                        Account Status
                    </span>

                    <strong>
                        <?= e(ucfirst($userStatus ?: 'N/A')) ?>
                    </strong>
                </div>

                <div class="admin-detail-item">
                    <span class="admin-detail-label">
                        Joined
                    </span>

                    <strong>
                        <?= !empty($user['created_at'])
                            ? e(date(
                                'M j, Y',
                                strtotime($user['created_at'])
                            ))
                            : 'N/A'
                        ?>
                    </strong>
                </div>

                <div class="admin-detail-item">
                    <span class="admin-detail-label">
                        Last Updated
                    </span>

                    <strong>
                        <?= !empty($user['updated_at'])
                            ? e(date(
                                'M j, Y',
                                strtotime($user['updated_at'])
                            ))
                            : 'N/A'
                        ?>
                    </strong>
                </div>

            </div>

        </div>

    </section>

    <?php if ($userRole === 'farmer'): ?>

        <section class="admin-details-section admin-products-section">

            <div class="admin-details-section-heading">

                <div>
                    <span class="eyebrow">02 / Farmer Profile</span>

                    <h2>
                        Farm <em>details.</em>
                    </h2>
                </div>

                <?php if ($farmer): ?>

                    <span class="admin-record-count">
                        Farmer #<?= (int) $farmer['id'] ?>
                    </span>

                <?php endif; ?>

            </div>

            <?php if ($farmer): ?>

                <div class="admin-farmer-profile">

                    <div class="admin-details-grid">

                        <div class="admin-detail-item">
                            <span class="admin-detail-label">
                                Stall Name
                            </span>

                            <strong>
                                <?= e(
                                    $farmer['stall_name'] ?? 'Not provided'
                                ) ?>
                            </strong>
                        </div>

                        <div class="admin-detail-item">
                            <span class="admin-detail-label">
                                Contact Person
                            </span>

                            <strong>
                                <?= e(
                                    $farmer['contact_person'] ?? 'Not provided'
                                ) ?>
                            </strong>
                        </div>

                        <div class="admin-detail-item admin-detail-wide">
                            <span class="admin-detail-label">
                                Address
                            </span>

                            <strong>
                                <?= e(
                                    $farmer['address'] ?? 'Not provided'
                                ) ?>
                            </strong>
                        </div>

                        <div class="admin-detail-item">
                            <span class="admin-detail-label">
                                Farmer Approval
                            </span>

                            <?php $approvalStatus = strtolower(
                                $farmer['approval_status'] ?? ''
                            ); ?>

                            <strong>
                                <?= e(
                                    ucfirst($approvalStatus ?: 'N/A')
                                ) ?>
                            </strong>
                        </div>

                        <div class="admin-detail-item">
                            <span class="admin-detail-label">
                                Products Listed
                            </span>

                            <strong>
                                <?= count($products) ?>
                            </strong>
                        </div>

                        <div class="admin-detail-item admin-detail-wide">
                            <span class="admin-detail-label">
                                Description
                            </span>

                            <p>
                                <?= !empty($farmer['description'])
                                    ? nl2br(e($farmer['description']))
                                    : 'No description provided.'
                                ?>
                            </p>
                        </div>

                        <div class="admin-detail-item admin-detail-wide">
                            <span class="admin-detail-label">
                                Coordinates
                            </span>

                            <strong>
                                <?php if (
                                    $farmer['latitude'] !== null &&
                                    $farmer['longitude'] !== null
                                ): ?>

                                    <?= e($farmer['latitude']) ?>,
                                    <?= e($farmer['longitude']) ?>

                                <?php else: ?>

                                    Not provided

                                <?php endif; ?>
                            </strong>
                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="admin-farmer-profile">
                    <p>
                        This account has the farmer role but does not
                        have an associated farmer profile.
                    </p>
                </div>

            <?php endif; ?>

        </section>

        <?php if ($farmer): ?>

            <section class="admin-details-section admin-products-section">

                <div class="admin-details-section-heading">

                    <div>
                        <span class="eyebrow">03 / Inventory</span>

                        <h2>
                            Farmer <em>products.</em>
                        </h2>
                    </div>

                    <span class="admin-record-count">
                        <?= count($products) ?>
                        <?= count($products) === 1
                            ? 'product'
                            : 'products'
                        ?>
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
                                            <?= e($product['name']) ?>
                                        </td>

                                        <td class="admin-product-category">
                                            <?= e(
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
                                            <span class="admin-status admin-status-<?= e($productStatus) ?>">
                                                <?= e(
                                                    ucfirst(
                                                        $productStatus ?: 'N/A'
                                                    )
                                                ) ?>
                                            </span>
                                        </td>

                                        <td class="admin-product-date">
                                            <?= !empty($product['created_at'])
                                                ? e(date(
                                                    'M j, Y',
                                                    strtotime(
                                                        $product['created_at']
                                                    )
                                                ))
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
                                        <p>
                                            This farmer has not added any products.
                                        </p>
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        <?php endif; ?>

    <?php else: ?>

        <section class="admin-details-section">

            <div class="admin-details-section-heading">

                <div>
                    <span class="eyebrow">02 / Account</span>

                    <h2>
                        Account <em>overview.</em>
                    </h2>
                </div>

            </div>

            <div class="admin-farmer-profile">

                <div class="admin-details-grid">

                    <div class="admin-detail-item">
                        <span class="admin-detail-label">
                            Account ID
                        </span>

                        <strong>
                            #<?= (int) $user['id'] ?>
                        </strong>
                    </div>

                    <div class="admin-detail-item">
                        <span class="admin-detail-label">
                            Access Level
                        </span>

                        <strong>
                            <?= e(ucfirst($userRole ?: 'N/A')) ?>
                        </strong>
                    </div>

                    <div class="admin-detail-item admin-detail-wide">
                        <span class="admin-detail-label">
                            Account Information
                        </span>

                        <p>
                            <?php if ($userRole === 'customer'): ?>

                                This account is registered as a MarketLink
                                customer.

                            <?php elseif ($userRole === 'admin'): ?>

                                This account has administrative access
                                to the MarketLink platform.

                            <?php else: ?>

                                No additional profile information is
                                available for this account.

                            <?php endif; ?>
                        </p>
                    </div>

                </div>

            </div>

        </section>

    <?php endif; ?>

</main>

<script src="../assets/js/app.js"></script>
<script src="../assets/js/lucide.js"></script>

<script>
    lucide.createIcons();
</script>

</body>
</html>