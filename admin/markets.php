<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$errors = [];
$markets = [];

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

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();
    $markets = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} else {

    $errors[] = 'Failed to load markets.';
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

    <title>Markets | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

</head>

<body>

<div class="admin-container">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    
    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>
                    Markets
                </h1>

                <p>
                    Manage all markets.
                </p>

            </div>

            <div>

                <a
                    href="add_market.php"
                    class="btn btn-primary"
                >
                    Add Market
                </a>

            </div>

        </div>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= htmlspecialchars($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <section class="table-section">

            <div class="section-header">

                <h2>
                    Markets
                </h2>

            </div>

            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Name
                            </th>

                            <th>
                                Address
                            </th>

                            <th>
                                Opening
                            </th>

                            <th>
                                Closing
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($markets)): ?>

                            <?php foreach ($markets as $market): ?>

                                <tr>

                                    <td>
                                        <?= (int)$market['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $market['name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $market['address'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $market['opening_time'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $market['closing_time'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $market['status'] ?? ''
                                            ) ?>"
                                        >
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $market['status'] ?? 'N/A'
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?= !empty($market['created_at'])
                                            ? date(
                                                'Y-m-d',
                                                strtotime(
                                                    $market['created_at']
                                                )
                                            )
                                            : 'N/A'
                                        ?>
                                    </td>

                                    <td>

                                        <a
                                            href="edit_market.php?id=<?= (int)$market['id'] ?>"
                                            class="btn btn-sm btn-secondary"
                                        >
                                            Edit
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8">
                                    No markets found.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>


</html>
