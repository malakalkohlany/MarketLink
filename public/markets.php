<?php

require_once '../includes/include.php';

$search = trim($_GET['search'] ?? '');
$day = trim($_GET['day'] ?? '');
$sort = $_GET['sort'] ?? 'name';

$allowedDays = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday'
];

if (!in_array($day, $allowedDays, true)) {
    $day = '';
}

$allowedSorts = [
    'name',
    'opening',
    'newest'
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'name';
}

if ($sort === 'opening') {
    $orderBy = 'm.opening_time ASC, m.name ASC';
} elseif ($sort === 'newest') {
    $orderBy = 'm.created_at DESC';
} else {
    $orderBy = 'm.name ASC';
}

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
        m.map_provider,
        m.created_at,

        COUNT(DISTINCT mf.farmer_id) AS farmer_count,

        COUNT(
            DISTINCT CASE
                WHEN p.is_available = 1
                AND p.moderation_status = 'approved'
                THEN p.id
            END
        ) AS product_count

    FROM markets m

    LEFT JOIN market_farmer mf
        ON mf.market_id = m.id

    LEFT JOIN products p
        ON p.farmer_id = mf.farmer_id

    WHERE m.status = 'active'
";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= "
        AND (
            m.name LIKE ?
            OR m.address LIKE ?
            OR m.description LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'sss';
}

if ($day !== '') {
    $sql .= "
        AND FIND_IN_SET(
            ?,
            REPLACE(m.operating_days, ' ', '')
        ) > 0
    ";

    $params[] = $day;
    $types .= 's';
}

$sql .= "
    GROUP BY
        m.id,
        m.name,
        m.description,
        m.address,
        m.latitude,
        m.longitude,
        m.opening_time,
        m.closing_time,
        m.operating_days,
        m.map_provider,
        m.created_at

    ORDER BY $orderBy
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

$markets = [];

while ($row = $result->fetch_assoc()) {
    $markets[] = $row;
}


$stmt->close();

$today = date('l');
$currentTime = date('H:i:s');

foreach ($markets as &$market) {
    $marketDays = array_filter(
        array_map(
            'trim',
            explode(',', $market['operating_days'] ?? '')
        )
    );
    

$visibleMarkets = array_slice($markets, 0, 3);
$hasMoreMarkets = count($markets) > 3;

    $market['is_open'] =
        in_array($today, $marketDays, true)
        &&
        !empty($market['opening_time'])
        &&
        !empty($market['closing_time'])
        &&
        $currentTime >= $market['opening_time']
        &&
        $currentTime <= $market['closing_time'];
}

unset($market);
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Markets - FreshFind</title>

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
        href="../assets/css/markets.css"
    >

</head>

<body>

    <header class="home-navbar">

    <div class="home-nav-inner">

        <a href="index.php" class="home-brand">

            <span class="brand-mark">
                F
            </span>

            <span class="brand-name">
                FreshFind
            </span>

        </a>


        <nav class="home-nav-links">

            <a href="../index.php">
                Home
            </a>

            <a href="markets.php">
                Markets
            </a>

            <a href="farmers.php">
                Farmers
            </a>

            <a href="about.php">
                About
            </a>

            <a href="contact.php">
                Contact
            </a>

        </nav>


        <div class="home-nav-actions">

            <a href="../auth/login.php" class="home-login">
                Login
            </a>

            <a href="../auth/register.php" class="home-join">
                Join FreshFind
            </a>

        </div>

    </div>

</header>
 <main>

        <section class="markets-hero">

            <div class="container">

                <h1>
                    Farmers Markets
                </h1>

                <p>
                    Find local farmers markets,
                    opening hours, farmers and products.
                </p>

            </div>

        </section>


        <section class="markets-filters">

            <div class="container">

                <h2>
                    Find a Market
                </h2>

                <form
                    method="GET"
                    action="markets.php"
                >

                    <div class="filter-group">

                        <label for="search">
                            Search
                        </label>

                        <input
                            type="search"
                            id="search"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search market, location or description"
                        >

                    </div>


                    <div class="filter-group">

                        <label for="day">
                            Operating Day
                        </label>

                        <select
                            id="day"
                            name="day"
                        >

                            <option value="">
                                All Days
                            </option>

                            <?php foreach ($allowedDays as $allowedDay): ?>

                                <option
                                    value="<?= e($allowedDay) ?>"
                                    <?= $day === $allowedDay ? 'selected' : '' ?>
                                >
                                    <?= e($allowedDay) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>
                     <div class="filter-group">

                        <label for="sort">
                            Sort By
                        </label>

                        <select
                            id="sort"
                            name="sort"
                        >

                            <option
                                value="name"
                                <?= $sort === 'name' ? 'selected' : '' ?>
                            >
                                Alphabetical
                            </option>

                            <option
                                value="opening"
                                <?= $sort === 'opening' ? 'selected' : '' ?>
                            >
                                Opening Time
                            </option>

                            <option
                                value="newest"
                                <?= $sort === 'newest' ? 'selected' : '' ?>
                            >
                                Newest
                            </option>

                        </select>

                    </div>


                    <div class="filter-actions">

                        <button
                            type="submit"
                        >
                            Search
                        </button>

                        <a href="markets.php">
                            Reset
                        </a>

                    </div>

                </form>

            </div>

        </section>
        <section class="markets-results">

            <div class="container">

                <div class="result-info">

                    <p>
                        <?= e(count($markets)) ?>
                        market(s) found
                    </p>

                </div>


                <?php if (empty($markets)): ?>

                    <section class="empty-state">

                        <h2>
                            No Markets Found
                        </h2>

                        <p>
                            No active markets match your search criteria.
                        </p>

                        <a href="markets.php">
                            View All Markets
                        </a>

                    </section>


                <?php else: ?>

                    <div class="markets-grid">

                        <?php foreach ($visibleMarkets as $market): ?>

                            <article class="market-card">

                                <header class="market-header">

                                    <div>

                                        <h2>
                                            <?= e($market['name']) ?>
                                        </h2>

                                        <?php if (!empty($market['address'])): ?>

                                            <p>
                                                <?= e($market['address']) ?>
                                            </p>

                                        <?php endif; ?>

                                    </div>


                                    <?php if ($market['is_open']): ?>

                                        <span class="market-status market-open">
                                            Open Now
                                        </span>

                                    <?php else: ?>

                                        <span class="market-status market-closed">
                                            Closed Now
                                        </span>

                                    <?php endif; ?>

                                </header>
                                 <div class="market-body">



                                    <div class="market-info">

                                        <div class="info-item">

                                            <strong>
                                                Opening Hours
                                            </strong>

                                            <?php if (
                                                !empty($market['opening_time']) &&
                                                !empty($market['closing_time'])
                                            ): ?>

                                                <span>

                                                    <?= e(
                                                        date(
                                                            'h:i A',
                                                            strtotime(
                                                                $market['opening_time']
                                                            )
                                                        )
                                                    ) ?>

                                                    -

                                                    <?= e(
                                                        date(
                                                            'h:i A',
                                                            strtotime(
                                                                $market['closing_time']
                                                            )
                                                        )
                                                    ) ?>

                                                </span>

                                            <?php else: ?>

                                                <span>
                                                    Schedule unavailable
                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <div class="info-item">

                                            <strong>
                                                Operating Days
                                            </strong>

                                            <div class="operating-days">

                                                <?php

                                                $marketDays = array_filter(
                                                    array_map(
                                                        'trim',
                                                        explode(
                                                            ',',
                                                            $market['operating_days'] ?? ''
                                                        )
                                                    )
                                                );

                                                ?>

                                                <?php if (!empty($marketDays)): ?>

                                                    <?php foreach ($marketDays as $marketDay): ?>

                                                        <span class="day-tag">
                                                            <?= e($marketDay) ?>
                                                        </span>

                                                    <?php endforeach; ?>

                                                <?php else: ?>

                                                    <span>
                                                        No schedule available
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </div>
                                    <div class="market-stats">

                                        <div class="market-stat">

                                            <strong>
                                                <?= e($market['farmer_count']) ?>
                                            </strong>

                                            <span>
                                                Farmers
                                            </span>

                                        </div>


                                        <div class="market-stat">

                                            <strong>
                                                <?= e($market['product_count']) ?>
                                            </strong>

                                            <span>
                                                Products
                                            </span>

                                        </div>

                                    </div>


                                    <?php if (
                                        !empty($market['latitude']) &&
                                        !empty($market['longitude'])
                                    ): ?>

                                        <div class="market-location">

                                            <a
                                                href="https://www.google.com/maps/search/?api=1&query=<?= e($market['latitude']) ?>,<?= e($market['longitude']) ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                View on Map
                                            </a>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <?php if ($hasMoreMarkets): ?>
                        <section class="markets-signin-cta">
                            <div class="markets-signin-inner">

                                <div class="markets-signin-copy">
                                    <span>KEEP EXPLORING</span>

                                    <h2>
                                        There's more<br>
                                        to discover.
                                    </h2>

                                    <p>
                                        Sign in to explore all available markets,
                                        farmers, and fresh local products.
                                    </p>
                                </div>

                                <div class="markets-signin-actions">
                                    <a href="../auth/login.php" class="markets-signin-btn">
                                        Sign In
                                    </a>

                                    <a href="../auth/register.php" class="markets-join-btn">
                                        Join FreshFind
                                    </a>
                                </div>

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
                    &copy; <?= date('Y') ?> FreshFind.
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