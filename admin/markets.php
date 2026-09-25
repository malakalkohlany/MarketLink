<?php

require_once '../includes/include.php';
require_once '../config/database.php';

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        address,
        opening_time,
        closing_time,
        operating_days,
        latitude,
        longitude,
        map_provider,
        status,
        created_at
    FROM markets
    ORDER BY created_at DESC
");

$stmt->execute();

$result = $stmt->get_result();

$markets = [];

while ($row = $result->fetch_assoc()) {
    $markets[] = $row;
}

$stmt->close();

?>

<div class="page-header">

    <h1>Markets</h1>

    <p>Manage all markets.</p>

</div>

<section class="table-section">

    <div class="section-header">

        <h2>Markets</h2>

        <a
            href="add_market.php"
            class="btn btn-primary"
        >
            Add Market
        </a>

    </div>

    <table class="data-table">

        <thead>

            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>Operating Days</th>
                <th>Opening</th>
                <th>Closing</th>
                <th>Coordinates</th>
                <th>Map Provider</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

        </thead>

        <tbody>

            <?php if (empty($markets)): ?>

                <tr>
                    <td colspan="10">
                        No markets found.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($markets as $market): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($market['id']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['address'] ?? 'N/A') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['operating_days'] ?? 'N/A') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['opening_time'] ?? 'N/A') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['closing_time'] ?? 'N/A') ?>
                        </td>

                        <td>

                            <?php if (
                                $market['latitude'] !== null &&
                                $market['longitude'] !== null
                            ): ?>

                                <?= htmlspecialchars($market['latitude']) ?>,
                                <?= htmlspecialchars($market['longitude']) ?>

                            <?php else: ?>

                                N/A

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= htmlspecialchars($market['map_provider'] ?? 'N/A') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($market['status'] ?? 'N/A') ?>
                        </td>

                        <td>

                            <a
                                href="edit_market.php?id=<?= urlencode($market['id']) ?>"
                                class="btn btn-secondary"
                            >
                                Edit
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</section>