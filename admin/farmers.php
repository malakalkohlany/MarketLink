<?php

require_once '../includes/include.php';

$stmt = $conn->prepare("
    SELECT
        id,
        stall_name,
        phone,
        email,
        status,
        created_at
    FROM farmers
    ORDER BY created_at DESC
");

$stmt->execute();

$farmers = $stmt->fetchAll();

?>

<div class="page-header">

    <h1>Farmers</h1>

    <p>Manage all farmers.</p>

</div>

<section class="table-section">

    <div class="section-header">

        <h2>Farmers</h2>

    </div>

    <table class="data-table">

        <thead>
            <tr>
                <th>ID</th>
                <th>Stall Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            
            <?php foreach ($farmers as $farmer): ?>

                <tr>

                    <td>
                        <?= $farmer['id'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($farmer['stall_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($farmer['phone'] ?? 'N/A') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($farmer['email'] ?? 'N/A') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($farmer['status'] ?? 'N/A') ?>
                    </td>

                    <td>
                        <?= date(
                            'Y-m-d',
                            strtotime($farmer['created_at'])
                        ) ?>
                    </td>

                    <td>
                        <a
                            href="farmer_details.php?id=<?= $farmer['id'] ?>"
                            class="btn btn-secondary"
                        >
                            View
                        </a>
                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</section>
