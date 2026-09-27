<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$user_id = $_SESSION['user_id'];

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
</head>
<body>

    <h1>My Markets</h1>
    <p>Total Markets: <?= e($total_markets) ?></p>

    <?php if ($total_markets === 0): ?>
        <p>No markets have been assigned to you yet.</p>
    <?php else: ?>

        <table border="1">
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
                        <td><?= e($market['name']) ?></td>
                        <td><?= e($market['description'] ?? '') ?></td>
                        <td><?= e($market['address']) ?></td>
                        <td><?= e($market['opening_time'] ?? '') ?></td>
                        <td><?= e($market['closing_time'] ?? '') ?></td>
                        <td><?= e($market['operating_days'] ?? '') ?></td>
                        <td><?= e($market['map_provider']) ?></td>
                        <td><?= e(ucfirst($market['status'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>

    <div class="pagination">

        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>"
               <?= $i == $page ? 'class="active"' : '' ?>>
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>">Next</a>
        <?php endif; ?>

    </div>
   <?php endif; ?>


    <?php endif; ?>

</body>
</html>