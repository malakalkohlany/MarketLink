<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

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

    <title>About Us - FreshFind</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

    <nav>

        <a href="index.php">
            FreshFind
        </a>

        <a href="markets.php">
            Markets
        </a>

        <a href="farmers.php">
            Farmers
        </a>

        <a href="about.php">
            About Us
        </a>

        <a href="contact.php">
            Contact Us
        </a>

    </nav>

    <main>

        <section>

            <div>

                <p>
                    About FreshFind
                </p>

                <h1>
                    Connecting Communities With Local Farmers
                </h1>

                <p>
                    FreshFind helps residents discover local farmers
                    markets, farmers, fresh products, and seasonal
                    produce in one convenient platform.
                </p>

            </div>

        </section>
 <section>

            <div>

                <h2>
                    About Our Platform
                </h2>

                <p>
                    FreshFind is a browser-based platform designed
                    to make it easier for residents to discover
                    farmers markets and local food producers.
                </p>

                <p>
                    Visitors can explore active markets, discover
                    approved farmers, browse available products,
                    and learn more about different product categories.
                </p>

            </div>

        </section>

        <section>

            <div>

                <h2>
                    What FreshFind Provides
                </h2>

                <div>

                    <article>

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
                        </a>

                    </article>

                    <article>

                        <h3>
                            Discover Farmers
                        </h3>

                        <p>
                            Explore approved local farmers and
                            learn about the products they provide.
                        </p>

                        <a href="farmers.php">
                            Explore Farmers
                        </a>

                    </article>

                    <article>

                        <h3>
                            Explore Products
                        </h3>

                        <p>
                            Discover fresh products available
                            from participating local farmers.
                        </p>

                    </article>

                    <article>

                        <h3>
                            Support Local Communities
                        </h3>

                        <p>
                            FreshFind helps connect residents with
                            local growers and encourages support for
                            community-based food producers.
                        </p>

                    </article>

                </div>

            </div>

        </section>
 <section>

            <div>

                <h2>
                    FreshFind at a Glance
                </h2>

                <div>

                    <div>

                        <strong>
                            <?= e($marketCount) ?>
                        </strong>

                        <span>
                            Active Markets
                        </span>

                    </div>

                    <div>

                        <strong>
                            <?= e($farmerCount) ?>
                        </strong>

                        <span>
                            Approved Farmers
                        </span>

                    </div>

                    <div>

                        <strong>
                            <?= e($productCount) ?>
                        </strong>

                        <span>
                            Available Products
                        </span>

                    </div>

                    <div>

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

        <section>

            <div>

                <h2>
                    Featured Markets
                </h2>

                <?php if (empty($featuredMarkets)): ?>

                    <p>
                        No active markets are currently available.
                    </p>

                <?php else: ?>
 <div>

                        <?php foreach ($featuredMarkets as $market): ?>

                            <article>

                                <h3>
                                    <?= e($market['name']) ?>
                                </h3>

                                <p>
                                    <?= e($market['address']) ?>
                                </p>

                                <?php if (!empty($market['operating_days'])): ?>

                                    <p>
                                        Days:
                                        <?= e($market['operating_days']) ?>
                                    </p>

                                <?php endif; ?>

                                <?php if (
                                    !empty($market['opening_time']) &&
                                    !empty($market['closing_time'])
                                ): ?>

                                    <p>

                                        <?= e(
                                            date(
                                                'h:i A',
                                                strtotime($market['opening_time'])
                                            )
                                        ) ?>

                                        -

                                        <?= e(
                                            date(
                                                'h:i A',
                                                strtotime($market['closing_time'])
                                            )
                                        ) ?>

                                    </p>

                                <?php endif; ?>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <a href="markets.php">
                        View All Markets
                    </a>

                <?php endif; ?>

            </div>

        </section>

        <section>

            <div>

                <h2>
                    Product Categories
                </h2>

                <?php if (empty($categories)): ?>

                    <p>
                        No product categories are currently available.
                    </p>

                <?php else: ?>

                    <div>

                        <?php foreach ($categories as $category): ?>

                            <article>

                                <h3>
                                    <?= e($category['name']) ?>
                                </h3>

                                <?php if (!empty($category['description'])): ?>

                                    <p>
                                        <?= e($category['description']) ?>
                                    </p>

                                <?php endif; ?>

                                <p>

                                    <?= e($category['product_count']) ?>

                                    available products

                                </p>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </section>
  <section>

            <div>

                <h2>
                    Our Purpose
                </h2>

                <p>
                    FreshFind brings market information,
                    local farmers, and fresh products together
                    in one accessible platform.
                </p>

                <p>
                    The platform is designed to help visitors
                    make informed decisions about where and
                    when to find local produce.
                </p>

            </div>

        </section>

        <section>

            <div>

                <h2>
                    Need More Information?
                </h2>

                <p>
                    If you have questions about FreshFind,
                    local markets, or participating farmers,
                    please contact us.
                </p>

                <a href="contact.php">
                    Contact Us
                </a>

            </div>

        </section>

    </main>

</body>

</html>
