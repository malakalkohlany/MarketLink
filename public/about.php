<?php

require_once '../includes/include.php';

$marketCount = 0;
$farmerCount = 0;
$productCount = 0;
$categoryCount = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM markets
    WHERE status = 'active'
");

if ($result) {
    $marketCount = (int) $result->fetch_assoc()['total'];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM farmers
    WHERE approval_status = 'approved'
");

if ($result) {
    $farmerCount = (int) $result->fetch_assoc()['total'];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE is_available = 1
    AND moderation_status = 'approved'
");

if ($result) {
    $productCount = (int) $result->fetch_assoc()['total'];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM categories
    WHERE status = 'active'
");

if ($result) {
    $categoryCount = (int) $result->fetch_assoc()['total'];
}

$result = $conn->query("
    SELECT 
        m.id,
        m.name,
        m.address,
        m.operating_days,
        m.opening_time,
        m.closing_time
    FROM markets m
    WHERE m.status = 'active'
    ORDER BY m.name ASC
    LIMIT 4
");

$featuredMarkets = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $featuredMarkets[] = $row;
    }
}

$result = $conn->query("
    SELECT
        c.id,
        c.name,
        c.description,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.is_available = 1
        AND p.moderation_status = 'approved'
    WHERE c.status = 'active'
    GROUP BY c.id, c.name, c.description
    ORDER BY c.name ASC
    LIMIT 6
");

$categories = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
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

    <title>About Us - MarketLink</title>

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
        href="../assets/css/about.css"
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
                <a href="#" class="active">About</a>
                <a href="markets.php">Markets</a>
                <a href="farmers.php">Farmers</a>
                <a href="contact.php">Contact</a>
            </nav>

            <div class="home-nav-actions">
                <a href="../auth/login.php" class="home-login">
                    Login
                </a>

                <a href="auth/register.php" class="home-join">
                Join MarketLink
                <span>↗</span>
            </a>
            </div>

        </div>
    </header>

    <main class="about-page">

    <section class="about-hero">

        <div class="about-hero-decoration about-decoration-left"></div>
        <div class="about-hero-decoration about-decoration-right"></div>

        <div class="container about-hero-inner">

            <div class="about-hero-copy">

                <span class="about-eyebrow">
                    ABOUT MarketLink
                </span>

                <h1>
                    Bringing local food
                    <em>closer to you.</em>
                </h1>

                <p>
                    MarketLink helps you discover local farmers markets,
                    farmers, fresh products, and seasonal produce —
                    all in one place.
                </p>

                <div class="about-hero-actions">

                    <a
                        href="markets.php"
                        class="about-primary-btn"
                    >
                        Explore Markets
                    </a>

                    <a
                        href="farmers.php"
                        class="about-secondary-btn"
                    >
                        Meet Farmers
                    </a>

                </div>

            </div>

            <div class="about-hero-note">

                <span>LOCAL</span>
                <span>FRESH</span>
                <span>CONNECTED</span>

            </div>

        </div>

    </section>

    <section class="about-intro">

        <div class="container about-intro-grid">

            <div class="about-section-label">
                <span>01</span>
                <p>
                    ABOUT THE PLATFORM
                </p>
            </div>

            <div class="about-intro-content">

                <h2>
                    A simpler way to
                    <span>find local.</span>
                </h2>

                <p class="about-lead">
                    MarketLink is a browser-based platform designed
                    to make discovering local farmers markets and
                    food producers easier.
                </p>

                <p>
                    Instead of searching across different places,
                    MarketLink brings market information, approved
                    farmers, available products, and product
                    categories together in one convenient platform.
                </p>

            </div>

        </div>

    </section>

    <section class="about-features">

        <div class="container">

            <div class="about-section-heading">

                <span class="about-eyebrow">
                    WHAT YOU CAN DISCOVER
                </span>

                <h2>
                    Everything starts
                    <em>locally.</em>
                </h2>

                <p>
                    Explore the people, places, and products
                    that make local food communities special.
                </p>

            </div>

            <div class="about-feature-grid">

                <article class="about-feature about-feature-sage">

                    <span class="feature-number">
                        01
                    </span>

                    <h3>
                        Discover Markets
                    </h3>

                    <p>
                        Find active farmers markets and view
                        their locations, operating days,
                        and opening hours.
                    </p>

                    <a href="markets.php">
                        Explore Markets
                        <span>↗</span>
                    </a>

                </article>


                <article class="about-feature about-feature-terracotta">

                    <span class="feature-number">
                        02
                    </span>

                    <h3>
                        Meet Farmers
                    </h3>

                    <p>
                        Explore approved local farmers and
                        learn more about the products they
                        provide.
                    </p>

                    <a href="farmers.php">
                        Explore Farmers
                        <span>↗</span>
                    </a>

                </article>


                <article class="about-feature about-feature-marigold">

                    <span class="feature-number">
                        03
                    </span>

                    <h3>
                        Explore Products
                    </h3>

                    <p>
                        Discover fresh products available
                        from participating local farmers.
                    </p>

                    <a href="markets.php">
                        Find Products
                        <span>↗</span>
                    </a>

                </article>


                <article class="about-feature about-feature-dark">

                    <span class="feature-number">
                        04
                    </span>

                    <h3>
                        Support Local
                    </h3>

                    <p>
                        MarketLink connects residents with
                        local growers and community-based
                        food producers.
                    </p>

                </article>

            </div>

        </div>

    </section>
    <section class="about-stats">

        <div class="container">

            <div class="about-stats-heading">

                <span class="about-eyebrow">
                    MarketLink AT A GLANCE
                </span>

                <h2 style="color: #666e5a">
                    A growing local
                    <em>community.</em>
                </h2>

            </div>


            <div class="about-stats-grid">

                <div class="about-stat">
                    <strong>
                        <?= e($marketCount) ?>
                    </strong>

                    <span>
                        Active Markets
                    </span>
                </div>


                <div class="about-stat">
                    <strong>
                        <?= e($farmerCount) ?>
                    </strong>

                    <span>
                        Approved Farmers
                    </span>
                </div>


                <div class="about-stat">
                    <strong>
                        <?= e($productCount) ?>
                    </strong>

                    <span>
                        Available Products
                    </span>
                </div>


                <div class="about-stat">
                    <strong>
                        <?= e($categoryCount) ?>
                    </strong>

                    <span>
                        Product Categories
                    </span>
                </div>

            </div>

        </div>

    </section>

    <section class="about-markets">

        <div class="container">

            <div class="about-section-heading about-heading-row">

                <div>

                    <span class="about-eyebrow">
                        PLACES TO EXPLORE
                    </span>

                    <h2>
                        Featured
                        <em>markets.</em>
                    </h2>

                </div>

                <a
                    href="markets.php"
                    class="about-text-link"
                >
                    View all markets
                    <span>↗</span>
                </a>

            </div>


            <?php if (empty($featuredMarkets)): ?>

                <div class="about-empty">
                    <p>
                        No active markets are currently available.
                    </p>
                </div>

            <?php else: ?>

                <div class="about-market-grid">

                    <?php foreach ($featuredMarkets as $index => $market): ?>

                        <article class="about-market-card">

                            <div class="about-market-number">
                                <?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?>
                            </div>

                            <div class="about-market-content">

                                <h3>
                                    <?= e($market['name']) ?>
                                </h3>

                                <?php if (!empty($market['address'])): ?>

                                    <p class="about-market-address">
                                        <?= e($market['address']) ?>
                                    </p>

                                <?php endif; ?>

                                <?php if (!empty($market['operating_days'])): ?>

                                    <p class="about-market-days">
                                        <?= e($market['operating_days']) ?>
                                    </p>

                                <?php endif; ?>

                                <?php if (
                                    !empty($market['opening_time']) &&
                                    !empty($market['closing_time'])
                                ): ?>

                                    <span class="about-market-hours">

                                        <?= e(
                                            date(
                                                'h:i A',
                                                strtotime($market['opening_time'])
                                            )
                                        ) ?>

                                        –

                                        <?= e(
                                            date(
                                                'h:i A',
                                                strtotime($market['closing_time'])
                                            )
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <section class="about-categories">

        <div class="container">

            <div class="about-section-heading">

                <span class="about-eyebrow">
                    EXPLORE WHAT'S FRESH
                </span>

                <h2>
                    Something for
                    <em>every table.</em>
                </h2>

            </div>


            <?php if (empty($categories)): ?>

                <div class="about-empty">
                    <p>
                        No product categories are currently available.
                    </p>
                </div>

            <?php else: ?>

                <div class="about-category-list">

                    <?php foreach ($categories as $category): ?>

                        <article class="about-category">

                            <div class="about-category-name">

                                <span>✦</span>

                                <h3>
                                    <?= e($category['name']) ?>
                                </h3>

                            </div>

                            <div class="about-category-info">

                                <?php if (!empty($category['description'])): ?>

                                    <p>
                                        <?= e($category['description']) ?>
                                    </p>

                                <?php endif; ?>

                                <strong>
                                    <?= e($category['product_count']) ?>
                                    products
                                </strong>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <section class="about-purpose">

        <div class="container">

            <div class="about-purpose-inner">

                <div class="about-purpose-copy">

                    <span class="about-eyebrow">
                        OUR PURPOSE
                    </span>

                    <h2>
                        Local food should
                        be easier to find.
                    </h2>

                    <p>
                        MarketLink brings market information,
                        local farmers, and fresh products
                        together in one accessible platform.
                    </p>

                    <p>
                        Whether you're looking for a nearby
                        market or discovering what local farmers
                        have to offer, MarketLink helps you know
                        where to look.
                    </p>

                </div>


                <div class="about-purpose-action">

                    <span>
                        HAVE A QUESTION?
                    </span>

                    <h3>
                        Let's talk.
                    </h3>

                    <a
                        href="contact.php"
                        class="about-primary-btn"
                    >
                        Contact Us
                    </a>

                </div>

            </div>

        </div>

    </section>

</main>

<footer class="home-footer">

    <div class="footer-inner">

        <div class="footer-bottom">

            <p>
                © <?= date('Y') ?> MarketLink. All rights reserved.
            </p>

            <p>
                Connecting you with local markets.
            </p>

        </div>

    </div>

</footer>

</body>

</html>
