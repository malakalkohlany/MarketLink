<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$user_id = getUserId();

$stmt = $conn->prepare("
    SELECT id
    FROM farmers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

if (!$farmer) {
    die("Farmer account not found.");
}

$farmer_id = $farmer['id'];

$stmt->close();
// Pagination
$items_per_page = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $items_per_page;
// Count total markets
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_markets
    FROM market_farmer
    WHERE farmer_id = ?
");

$count_stmt->bind_param("i", $farmer_id);
$count_stmt->execute();

$count_result = $count_stmt->get_result();
$total_markets = $count_result->fetch_assoc()['total_markets'];

$count_stmt->close();

$total_pages = ceil($total_markets / $items_per_page);

if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $items_per_page;
}

$market_stmt = $conn->prepare("
    SELECT
        markets.id,
        markets.name,
        markets.description,
        markets.address,
        markets.latitude,
        markets.longitude,
        markets.opening_time,
        markets.closing_time,
        markets.operating_days,
        markets.map_provider,
        markets.status
    FROM market_farmer
    INNER JOIN markets
        ON market_farmer.market_id = markets.id
    WHERE market_farmer.farmer_id = ?
    ORDER BY markets.name ASC
    LIMIT ? OFFSET ?
");

$market_stmt->bind_param("iii", $farmer_id, $items_per_page, $offset);
$market_stmt->execute();

$markets = $market_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Markets</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/farmer.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content farmer-markets-page">

        <section class="customer-page-hero">
            <div class="customer-page-hero-copy">
                <span class="eyebrow">FARMER / MARKETS</span>

                <h1>
                    Your local <em>markets.</em>
                </h1>

                <p>
                    View the markets assigned to you and keep track of where your products are available.
                </p>
            </div>

            <div class="customer-page-hero-mark">06</div>
        </section>

        <section class="farmer-markets-section">

            <div class="customer-section-heading">
                <div>
                    <span class="customer-section-number">01 / MARKETS</span>

                    <h2>
                        Assigned <em>markets.</em>
                    </h2>
                </div>

                <span class="customer-record-count">
                    <?= e($total_markets) ?> MARKETS
                </span>
            </div>

            <?php if ($total_markets === 0): ?>

                <div class="customer-products-empty">
                    <div class="customer-products-empty-mark">
                        <i data-lucide=" store"></i>
                    </div>

                    <h3>No markets assigned.</h3>

                    <p>
                        You have not been assigned to any markets yet.
                    </p>
                </div>

            <?php else: ?>

                <div class="farmer-markets-table-wrapper">
                    <table class="farmer-markets-table">

                        <thead>
                            <tr>
                                <th>Market Name</th>
                                <th>Description</th>
                                <th>Address</th>
                                <th>Opening Time</th>
                                <th>Closing Time</th>
                                <th>Operating Days</th>
                                <th>Map Provider</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($market = $markets->fetch_assoc()): ?>

                                <tr>
                                    <td>
                                        <span class="farmer-market-name">
                                            <?= e($market['name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($market['description'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= e($market['address']) ?>
                                    </td>

                                    <td>
                                        <?= e($market['opening_time'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= e($market['closing_time'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= e($market['operating_days'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= e($market['map_provider']) ?>
                                    </td>

                                    <td>
                                        <span class="farmer-market-status status-<?= e($market['status']) ?>">
                                            <?= e(ucfirst($market['status'])) ?>
                                        </span>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>
                </div>

                <?php if ($total_pages > 1): ?>

                    <div class="product-pagination">

                        <?php if ($page > 1): ?>

                            <a href="?page=<?= $page - 1 ?>">
                                <i data-lucide="chevron-left"></i>
                            </a>

                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>

                            <a
                                href="?page=<?= $i ?>"
                                class="<?= $i == $page ? 'active' : '' ?>"
                            >
                                <?= $i ?>
                            </a>

                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>

                            <a href="?page=<?= $page + 1 ?>">
                                <i data-lucide="chevron-right"></i>
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </main>

    <script src="../assets/js/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>