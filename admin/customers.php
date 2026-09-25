<?php

require_once __DIR__ . '/../includes/include.php';

requireRole('admin');

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email,
        phone,
        address,
        status,
        created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY created_at DESC
");
$result = $stmt->get_result();
$markets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<div class="page-header">

    <h1>Customers</h1>

    <p>Manage all customers.</p>

</div>

<section class="table-section">

    <div class="section-header">

        <h2>Customers</h2>

    </div>

    <table class="data-table">

        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($customers as $customer): ?>

                <tr>

                    <td>
                        <?= $customer['id'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($customer['name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($customer['email']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($customer['phone'] ?? 'N/A') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($customer['status']) ?>
                    </td>

                    <td>
                        <?= date(
                            'Y-m-d',
                            strtotime($customer['created_at'])
                        ) ?>
                    </td>

                    <td>
                        <a
                            href="customer_details.php?id=<?= $customer['id'] ?>"
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