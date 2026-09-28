<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['remove_product'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: favorites.php');
        exit;
    }

    $productId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    if ($productId && $productId > 0) {
        $stmt = $conn->prepare("
            DELETE FROM favorite_products
            WHERE customer_id = ?
              AND product_id = ?
        ");

        if (!$stmt) {
            die("Something went wrong.");
        }

        $stmt->bind_param(
            "ii",
            $customerId,
            $productId
        );

        if (!$stmt->execute()) {
            die("Something went wrong.");
        }

        $stmt->close();
    }

    redirect('favorites.php');
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['remove_farmer'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: favorites.php');
        exit;
    }

    $farmerId = filter_input(
        INPUT_POST,
        'farmer_id',
        FILTER_VALIDATE_INT
    );

    if ($farmerId && $farmerId > 0) {
        $stmt = $conn->prepare("
            DELETE FROM favorite_farmers
            WHERE customer_id = ?
              AND farmer_id = ?
        ");

        if (!$stmt) {
            die("Something went wrong.");
        }

        $stmt->bind_param(
            "ii",
            $customerId,
            $farmerId
        );

        if (!$stmt->execute()) {
            die("Something went wrong.");
        }

        $stmt->close();
    }

    redirect('favorites.php');
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['remove_market'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: favorites.php');
        exit;
    }

    $marketId = filter_input(
        INPUT_POST,
        'market_id',
        FILTER_VALIDATE_INT
    );

    if ($marketId && $marketId > 0) {
        $stmt = $conn->prepare("
            DELETE FROM favorite_markets
            WHERE customer_id = ?
              AND market_id = ?
        ");

        if (!$stmt) {
            die("Something went wrong.");
        }

        $stmt->bind_param(
            "ii",
            $customerId,
            $marketId
        );

        if (!$stmt->execute()) {
            die("Something went wrong.");
        }

        $stmt->close();
    }

    redirect('favorites.php');
}

$favoriteProducts = [];

$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.unit,
        p.stock_quantity,
        p.farmer_id
    FROM favorite_products fp
    INNER JOIN products p
        ON fp.product_id = p.id
    WHERE fp.customer_id = ?
      AND p.is_available = 1
      AND p.moderation_status = 'approved'
    ORDER BY fp.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Something went wrong.");
}

$stmt->bind_param(
    "i",
    $customerId
);

if (!$stmt->execute()) {
    die("Something went wrong.");
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $favoriteProducts[] = $row;
}

$stmt->close();

$favoriteFarmers = [];

$sql = "
    SELECT
        f.id,
        f.stall_name,
        f.contact_person,
        f.description,
        f.address
    FROM favorite_farmers ff
    INNER JOIN farmers f
        ON ff.farmer_id = f.id
    WHERE ff.customer_id = ?
      AND f.approval_status = 'approved'
    ORDER BY ff.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Something went wrong.");
}

$stmt->bind_param(
    "i",
    $customerId
);

if (!$stmt->execute()) {
    die("Something went wrong.");
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $favoriteFarmers[] = $row;
}

$stmt->close();

$favoriteMarkets = [];

$sql = "
    SELECT
        m.id,
        m.name,
        m.description,
        m.address,
        m.latitude,
        m.longitude,
        m.opening_time,
        m.closing_time,
        m.operating_days,
        m.map_provider
    FROM favorite_markets fm
    INNER JOIN markets m
        ON fm.market_id = m.id
    WHERE fm.customer_id = ?
      AND m.status = 'active'
    ORDER BY fm.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Something went wrong.");
}

$stmt->bind_param(
    "i",
    $customerId
);

if (!$stmt->execute()) {
    die("Something went wrong.");
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $favoriteMarkets[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Favorites - MarketLink</title>

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

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">

    <div class="favorites-container">

        <div class="page-header">
            <h1>
                Favorites
            </h1>

            <p>
                Products, farmers and markets you have saved to your favorites.
            </p>
        </div>

        <div class="favorites-section">
            <h2 class="favorites-section-title">
                Favorite Products
            </h2>

            <?php if (count($favoriteProducts) > 0): ?>

                <div class="favorites-grid">

                    <?php foreach ($favoriteProducts as $product): ?>

                        <div class="favorite-card">

                            <form
                                method="POST"
                                action="favorites.php"
                                class="remove-favorite-form"
                            >

                            <?= csrf_field() ?>
                            
                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int) $product['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="remove_product"
                                    class="remove-favorite-button"
                                    title="Remove from Favorites"
                                    aria-label="Remove from Favorites"
                                >
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            </form>

                            <div class="product-info">

                                <div class="product-name">
                                    <?= e($product['name']) ?>
                                </div>

                                <div class="product-description">
                                    <?= e(
                                        $product['description']
                                        ?? 'No description available.'
                                    ) ?>
                                </div>

                                <div class="product-price">
                                    $
                                    <?= number_format(
                                        (float) $product['price'],
                                        2
                                    ) ?>
                                </div>

                                <?php if (!empty($product['unit'])): ?>
                                    <div class="product-unit">
                                        Per
                                        <?= e($product['unit']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="product-stock">
                                    Stock:
                                    <?= number_format(
                                        (float) $product['stock_quantity'],
                                        2
                                    ) ?>
                                    <?= e($product['unit']) ?>
                                </div>

                                <a
                                    href="product_details.php?id=<?= (int) $product['id'] ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-favorites">
                    <h2>
                        No Favorite Products
                    </h2>

                    <p>
                        Products you add to your favorites will appear here.
                    </p>
                </div>

            <?php endif; ?>
        </div>

        <div class="favorites-section">
            <h2 class="favorites-section-title">
                Favorite Farmers
            </h2>

            <?php if (count($favoriteFarmers) > 0): ?>

                <div class="favorites-grid">

                    <?php foreach ($favoriteFarmers as $farmer): ?>

                        <div class="favorite-card">

                            <form
                                method="POST"
                                action="favorites.php"
                                class="remove-favorite-form"
                            >

                            <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="farmer_id"
                                    value="<?= (int) $farmer['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="remove_farmer"
                                    class="remove-favorite-button"
                                    title="Remove from Favorites"
                                    aria-label="Remove from Favorites"
                                >
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            </form>

                            <div class="farmer-info">

                                <div class="farmer-name">
                                    <?= e($farmer['stall_name']) ?>
                                </div>

                                <?php if (!empty($farmer['contact_person'])): ?>
                                    <div class="farmer-contact">
                                        <strong>
                                            Contact:
                                        </strong>
                                        <?= e(
                                            $farmer['contact_person']
                                        ) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($farmer['address'])): ?>
                                    <div class="farmer-address">
                                        <strong>
                                            Address:
                                        </strong>
                                        <?= e(
                                            $farmer['address']
                                        ) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="farmer-description">
                                    <?= e(
                                        $farmer['description']
                                        ?? 'No description available.'
                                    ) ?>
                                </div>

                                <a
                                    href="farmer_details.php?id=<?= (int) $farmer['id'] ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-section">
                    <h2>
                        No Favorite Farmers
                    </h2>

                    <p>
                        Farmers you add to your favorites will appear here.
                    </p>
                </div>

            <?php endif; ?>

        </div>

        <div class="favorites-section">
            <h2 class="favorites-section-title">
                Favorite Markets
            </h2>

            <?php if (count($favoriteMarkets) > 0): ?>

                <div class="favorites-grid">

                    <?php foreach ($favoriteMarkets as $market): ?>

                        <div class="favorite-card">

                            <form
                                method="POST"
                                action="favorites.php"
                                class="remove-favorite-form"
                            >

                            <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="market_id"
                                    value="<?= (int) $market['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="remove_market"
                                    class="remove-favorite-button"
                                    title="Remove from Favorites"
                                    aria-label="Remove from Favorites"
                                >
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            </form>

                            <div class="market-info-container">

                                <div class="market-name">
                                    <?= e($market['name']) ?>
                                </div>

                                <div class="market-address">
                                    <strong>
                                        📍 Address:
                                    </strong>
                                    <?= e($market['address']) ?>
                                </div>

                                <div class="market-time">
                                    <strong>
                                        Opening:
                                    </strong>
                                    <?= e($market['opening_time']) ?>
                                </div>

                                <div class="market-time">
                                    <strong>
                                        Closing:
                                    </strong>
                                    <?= e($market['closing_time']) ?>
                                </div>

                                <div class="market-days">
                                    <strong>
                                        Operating Days:
                                    </strong>
                                    <?= e($market['operating_days']) ?>
                                </div>

                                <?php if (!empty($market['description'])): ?>
                                    <div class="market-description">
                                        <?= e($market['description']) ?>
                                    </div>
                                <?php endif; ?>

                                <a
                                    href="market-details.php?id=<?= (int) $market['id'] ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-section">
                    <h2>
                        No Favorite Markets
                    </h2>

                    <p>
                        Markets you add to your favorites will appear here.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

</body>
</html>