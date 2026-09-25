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
");

$market_stmt->bind_param("i", $farmer_id);
$market_stmt->execute();

$markets = $market_stmt->get_result();

$total_markets = $markets->num_rows;

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
    <?php endif; ?>

</body>
</html>