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

    redirect('customer/favorites.php');
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

    redirect('customer/favorites.php');
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

    redirect('customer/favorites.php');
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
        href="../assets/css/customer.css"
    >

    <link
        rel="stylesheet"
        href="../assets/fontawesome/css/all.min.css"
    >

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content customer-farmers-page">

    <div class="customer-page-hero">
        <div class="customer-page-hero-copy">
            <span class="eyebrow">
                CUSTOMER / SAVED
            </span>

            <h1>
                The things you
                <br>
                want to <em>remember.</em>
            </h1>

            <p>
                Keep your favorite products, farmers, and markets close at
                hand so you can return to them whenever you shop.
            </p>
        </div>

        <div class="customer-page-hero-mark">
            04
        </div>
    </div>

    <section class="customer-farmers-intro">
        <div class="customer-shopping-note">
            <span class="customer-shopping-note-icon">
                <i data-lucide=" fa-heart"></i>
            </span>

            <div>
                <strong>
                    Your saved places and products.
                </strong>

                <span>
                    Everything you save from across MarketLink will appear
                    here for easy access.
                </span>
            </div>
        </div>
    </section>

    <section class="customer-farmers-list-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    01 / PRODUCTS
                </span>

                <h2>
                    Favorite <em>products.</em>
                </h2>
            </div>

            <span class="customer-record-count">
                <?= count($favoriteProducts) ?>
                item<?= count($favoriteProducts) !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if (!empty($favoriteProducts)): ?>

            <div class="customer-farmers-grid">

                <?php foreach ($favoriteProducts as $product): ?>

                    <article class="customer-product-card">

                        <div class="customer-product-content">

                            <div class="customer-farmer-card-top">

                                <span class="customer-farmer-card-number">
                                    <?= str_pad(
                                        (string) $product['id'],
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    ) ?>
                                </span>

                                <form
                                    method="POST"
                                    action="favorites.php"
                                    class="customer-farmer-favorite-form"
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
                                        class="customer-farmer-favorite is-favorite"
                                        title="Remove from Favorites"
                                        aria-label="Remove from Favorites"
                                    >
                                        <i data-lucide=" fa-heart"></i>
                                    </button>
                                </form>

                            </div>

                            <span class="customer-product-category">
                                FAVORITE PRODUCT
                            </span>

                            <h3 class="customer-product-name">
                                <?= e($product['name']) ?>
                            </h3>

                            <p class="customer-product-description">
                                <?= e(
                                    $product['description']
                                    ?? 'No description available.'
                                ) ?>
                            </p>

                            <div class="customer-product-meta">
                                <?php if (!empty($product['unit'])): ?>
                                    <span>
                                        Per <?= e($product['unit']) ?>
                                    </span>
                                <?php endif; ?>

                                <span>
                                    Stock:
                                    <?= number_format(
                                        (float) $product['stock_quantity'],
                                        2
                                    ) ?>
                                    <?= e($product['unit']) ?>
                                </span>
                            </div>

                            <div class="customer-product-price">
                                $
                                <?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>
                            </div>

                            <div class="customer-product-actions">
                                <a
                                    href="product_details.php?id=<?= (int) $product['id'] ?>"
                                    class="customer-product-details"
                                >
                                    View Product
                                    <i data-lucide=" arrow-right"></i>
                                </a>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="customer-farmers-empty">
                <span class="customer-farmers-empty-mark">
                    <i data-lucide=" fa-heart"></i>
                </span>

                <strong>
                    No favorite products yet.
                </strong>

                <span>
                    Products you save will appear here when you add them
                    to your favorites.
                </span>
            </div>

        <?php endif; ?>

    </section>

    <section class="customer-farmers-list-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    02 / FARMERS
                </span>

                <h2>
                    Favorite <em>farmers.</em>
                </h2>
            </div>

            <span class="customer-record-count">
                <?= count($favoriteFarmers) ?>
                farmer<?= count($favoriteFarmers) !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if (!empty($favoriteFarmers)): ?>

            <div class="customer-farmers-grid">

                <?php foreach ($favoriteFarmers as $farmer): ?>

                    <article class="customer-farmer-card">

                        <div class="customer-farmer-card-top">

                            <span class="customer-farmer-card-number">
                                <?= str_pad(
                                    (string) $farmer['id'],
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span>

                            <form
                                method="POST"
                                action="favorites.php"
                                class="customer-farmer-favorite-form"
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
                                    class="customer-farmer-favorite is-favorite"
                                    title="Remove from Favorites"
                                    aria-label="Remove from Favorites"
                                >
                                    <i data-lucide=" fa-heart"></i>
                                </button>
                            </form>

                        </div>

                        <div class="customer-farmer-card-content">

                            <span class="customer-farmer-label">
                                FAVORITE FARMER
                            </span>

                            <h3>
                                <?= e($farmer['stall_name']) ?>
                            </h3>

                            <?php if (!empty($farmer['contact_person'])): ?>

                                <div class="customer-farmer-meta">
                                    <i data-lucide=" fa-user"></i>

                                    <span>
                                        <?= e($farmer['contact_person']) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <?php if (!empty($farmer['address'])): ?>

                                <div class="customer-farmer-meta">
                                    <i data-lucide=" fa-location-dot"></i>

                                    <span>
                                        <?= e($farmer['address']) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <p class="customer-farmer-description">
                                <?= e(
                                    $farmer['description']
                                    ?? 'No description available.'
                                ) ?>
                            </p>

                            <div class="customer-farmer-card-footer">
                                <a
                                    href="farmer_details.php?id=<?= (int) $farmer['id'] ?>"
                                    class="customer-farmer-details"
                                >
                                    View Farmer
                                    <i data-lucide=" arrow-right"></i>
                                </a>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="customer-farmers-empty">
                <span class="customer-farmers-empty-mark">
                    <i data-lucide=" sprout"></i>
                </span>

                <strong>
                    No favorite farmers yet.
                </strong>

                <span>
                    Farmers you save will appear here when you add them
                    to your favorites.
                </span>
            </div>

        <?php endif; ?>

    </section>

    <section class="customer-farmers-list-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    03 / MARKETS
                </span>

                <h2>
                    Favorite <em>markets.</em>
                </h2>
            </div>

            <span class="customer-record-count">
                <?= count($favoriteMarkets) ?>
                market<?= count($favoriteMarkets) !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if (!empty($favoriteMarkets)): ?>

            <div class="customer-farmers-grid">

                <?php foreach ($favoriteMarkets as $market): ?>

                    <article class="customer-farmer-card">

                        <div class="customer-farmer-card-top">

                            <span class="customer-farmer-card-number">
                                <?= str_pad(
                                    (string) $market['id'],
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span>

                            <form
                                method="POST"
                                action="favorites.php"
                                class="customer-farmer-favorite-form"
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
                                    class="customer-farmer-favorite is-favorite"
                                    title="Remove from Favorites"
                                    aria-label="Remove from Favorites"
                                >
                                    <i data-lucide=" fa-heart"></i>
                                </button>
                            </form>

                        </div>

                        <div class="customer-farmer-card-content">

                            <span class="customer-farmer-label">
                                FAVORITE MARKET
                            </span>

                            <h3>
                                <?= e($market['name']) ?>
                            </h3>

                            <?php if (!empty($market['address'])): ?>

                                <div class="customer-farmer-meta">
                                    <i data-lucide=" fa-location-dot"></i>

                                    <span>
                                        <?= e($market['address']) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <?php if (!empty($market['operating_days'])): ?>

                                <div class="customer-farmer-meta">
                                    <i data-lucide=" fa-calendar-days"></i>

                                    <span>
                                        <?= e($market['operating_days']) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <?php if (
                                !empty($market['opening_time'])
                                && !empty($market['closing_time'])
                            ): ?>

                                <div class="customer-farmer-meta">
                                    <i data-lucide=" clock"></i>

                                    <span>
                                        <?= e(
                                            date(
                                                'g:i A',
                                                strtotime($market['opening_time'])
                                            )
                                        ) ?>
                                        -
                                        <?= e(
                                            date(
                                                'g:i A',
                                                strtotime($market['closing_time'])
                                            )
                                        ) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <p class="customer-farmer-description">
                                <?= e(
                                    $market['description']
                                    ?? 'Local market offering fresh produce from nearby farmers.'
                                ) ?>
                            </p>

                            <div class="customer-farmer-card-footer">
                                <a
                                    href="market_details.php?id=<?= (int) $market['id'] ?>"
                                    class="customer-farmer-details"
                                >
                                    View Market
                                    <i data-lucide=" arrow-right"></i>
                                </a>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="customer-farmers-empty">
                <span class="customer-farmers-empty-mark">
                    <i data-lucide=" store"></i>
                </span>

                <strong>
                    No favorite markets yet.
                </strong>

                <span>
                    Markets you save will appear here when you add them
                    to your favorites.
                </span>
            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>