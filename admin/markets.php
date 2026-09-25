<?php

require_once '../includes/include.php';

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        address,
        opening_time,
        closing_time,
        status,
        created_at
    FROM markets
    ORDER BY created_at DESC
");

$stmt->execute();

$markets = $stmt->fetchAll();

?>

<div class="page-header">

    <h1>Markets</h1>

    <p>Manage all markets.</p>

</div>

<section class="table-section">

    <div class="section-header">

        <h2>Markets</h2>

    </div>

    <table class="data-table">

        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>Opening</th>
                <th>Closing</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($markets as $market): ?>

                <tr>

                    <td>
                        <?= $market['id'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($market['name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($market['address'] ?? 'N/A') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($market['opening_time'] ?? 'N/A') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($market['closing_time'] ?? 'N/A') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($market['status'] ?? 'N/A') ?>
                    </td>

                    <td>
                        <a
                            href="edit_market.php?id=<?= $market['id'] ?>"
                            class="btn btn-secondary"
                        >
                            Edit
                        </a>
                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</section>
