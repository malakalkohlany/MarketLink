<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        f.id,
        f.stall_name,
        f.contact_person,
        f.description,
        f.address,
        f.latitude,
        f.longitude,
        f.created_at,

        COUNT(DISTINCT p.id) AS product_count

    FROM farmers f

    LEFT JOIN products p
        ON p.farmer_id = f.id
        AND p.is_available = 1
        AND p.moderation_status = 'approved'

    WHERE f.approval_status = 'approved'
";

$params = [];
$types = '';

if ($search !== '') {

    $sql .= "
        AND (
            f.stall_name LIKE ?
            OR f.contact_person LIKE ?
            OR f.address LIKE ?
            OR f.description LIKE ?
        )
    ";
    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'ssss';
}

$sql .= "
    GROUP BY
        f.id,
        f.stall_name,
        f.contact_person,
        f.description,
        f.address,
        f.latitude,
        f.longitude,
        f.created_at

    ORDER BY f.stall_name ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Database query failed.');
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$farmers = [];

while ($row = $result->fetch_assoc()) {
    $farmers[] = $row;
}

$stmt->close();

$visibleFarmers = array_slice($farmers, 0, 3);
$hasMoreFarmers = count($farmers) > 3;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Farmers - MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/homepage.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/farmers.css"
    >
</head>

<body>

    <header class="home-navbar">
        <div class="home-nav-inner">

            <a href="index.php" class="home-brand">
            <span class="brand-mark">M</span>
            <span class="brand-name">MarketLink</span>
        </a>

            <nav class="home-nav-links">
                <a href="../index.php">Home</a>
                <a href="about.php">About</a>
                <a href="markets.php">Markets</a>
                <a href="#" class="active">Farmers</a>
                <a href="contact.php">Contact</a>
            </nav>

            <div class="home-nav-actions">
                <a href="../auth/login.php" class="home-login">
                    Login
                </a>

                <a href="../auth/register.php" class="home-join">
                Join MarketLink
                <span>↗</span>
            </a>
            </div>

        </div>
    </header>


    <main class="farmers-page">

    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="farmers-hero">

        <div class="farmers-hero-circle farmers-circle-left"></div>
        <div class="farmers-hero-circle farmers-circle-right"></div>

        <div class="container farmers-hero-inner">

            <div>

                <span class="farmers-eyebrow">
                    MEET THE PEOPLE BEHIND THE PRODUCE
                </span>

                <h1>
                    Local farmers.
                    <em>Real produce.</em>
                </h1>

                <p>
                    Discover local farmers, the products they
                    provide, and where you can find them.
                </p>

            </div>


        </div>

    </section>

    <section class="farmers-filters">

        <div class="container">

            <div class="farmers-filter-inner">

                <div class="farmers-filter-copy">

                    <span>
                        FIND A FARMER
                    </span>

                    <h2>
                        Who are you
                        looking for?
                    </h2>

                </div>


                <form
                    method="GET"
                    action="farmers.php"
                    class="farmers-search-form"
                >

                    <div class="farmer-search-field">

                        <label for="search">
                            Search Farmers
                        </label>

                        <input
                            type="search"
                            id="search"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Farmer, stall or location..."
                        >

                    </div>


                    <div class="filter-actions">

                        <button
                            type="submit"
                            class="btn-search"
                        >
                            Search
                        </button>

                        <a
                            href="farmers.php"
                            class="btn-reset"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </section>

    <section class="farmers-results">

        <div class="container">

            <div class="farmers-result-header">

                <div>

                    <span class="farmers-eyebrow">
                        LOCAL PRODUCERS
                    </span>

                    <h2>
                        Farmers worth
                        <em>knowing.</em>
                    </h2>

                </div>

                <p>
                    <?= e(count($farmers)) ?>
                    <?= count($farmers) === 1 ? 'farmer' : 'farmers' ?>
                    found
                </p>

            </div>


            <?php if (empty($farmers)): ?>

                <section class="farmers-empty">

                    <span>✦</span>

                    <h2>
                        No Farmers Found
                    </h2>

                    <p>
                        Try changing your search and see
                        what else is nearby.
                    </p>

                    <a
                        href="farmers.php"
                        class="farmers-empty-btn"
                    >
                        View All Farmers
                    </a>

                </section>

            <?php else: ?>

                <div class="farmers-grid">

                    <?php foreach ($visibleFarmers as $index => $farmer): ?>

                        <article class="farmer-card">

                            <header class="farmer-header">

                                <span class="farmer-number">
                                    <?= str_pad(
                                        $index + 1,
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    ) ?>
                                </span>

                                <div class="farmer-symbol">
                                    ✦
                                </div>

                            </header>


                            <div class="farmer-body">

                                <h2>
                                    <?= e(
                                        $farmer['stall_name']
                                    ) ?>
                                </h2>


                                <?php if (
                                    !empty($farmer['contact_person'])
                                ): ?>

                                    <p class="farmer-contact">
                                        <?= e(
                                            $farmer['contact_person']
                                        ) ?>
                                    </p>

                                <?php endif; ?>


                                <?php if (
                                    !empty($farmer['address'])
                                ): ?>

                                    <div class="farmer-location">

                                        <span>📍</span>

                                        <span>
                                            <?= e(
                                                $farmer['address']
                                            ) ?>
                                        </span>

                                    </div>

                                <?php endif; ?>


                                <div class="farmer-card-footer">

                                    <div class="farmer-product-count">

                                        <strong>
                                            <?= e(
                                                $farmer['product_count']
                                            ) ?>
                                        </strong>

                                        <span>
                                            Products
                                        </span>

                                    </div>


                                    <?php if (
                                        !empty($farmer['latitude']) &&
                                        !empty($farmer['longitude'])
                                    ): ?>

                                        <a
                                            href="https://www.google.com/maps/search/?api=1&query=<?= e($farmer['latitude']) ?>,<?= e($farmer['longitude']) ?>"
                                            class="map-link"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            View Map
                                            <span>↗</span>
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


                <?php if ($hasMoreFarmers): ?>

                    <section class="farmers-signin-cta">

                        <div class="farmers-cta-copy">

                            <span>
                                KEEP EXPLORING
                            </span>

                            <h2 style="color: #666e5a">
                                More local
                                <em>farmers await.</em>
                            </h2>

                            <p>
                                Sign in to explore all available
                                farmers and discover more local
                                products.
                            </p>

                        </div>


                        <div class="farmers-cta-actions">

                            <a
                                href="../auth/login.php"
                                class="farmers-signin-btn"
                            >
                                Sign In
                            </a>

                            <a
                                href="../auth/register.php"
                                class="farmers-join-btn"
                            >
                                Join MarketLink
                            </a>

                        </div>

                    </section>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </section>

</main>

<footer class="home-footer">

    <div class="footer-inner">

        <div class="footer-bottom">

            <p>
                © <?= date('Y') ?> MarketLink.
                All rights reserved.
            </p>

            <p>
                Connecting you with local markets.
            </p>

        </div>

    </div>

</footer>

</body>

</html>