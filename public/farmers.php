<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        f.id,
        f.stall_name,
        f.contact_person,
        f.description,
        f.address,
        f.latitude,
        f.longitude,
        f.created_at,

        COUNT(DISTINCT p.id) AS product_count

    FROM farmers f

    LEFT JOIN products p
        ON p.farmer_id = f.id
        AND p.is_available = 1
        AND p.moderation_status = 'approved'

    WHERE f.approval_status = 'approved'
";

$params = [];
$types = '';

if ($search !== '') {

    $sql .= "
        AND (
            f.stall_name LIKE ?
            OR f.contact_person LIKE ?
            OR f.address LIKE ?
            OR f.description LIKE ?
        )
    ";
    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'ssss';
}

$sql .= "
    GROUP BY
        f.id,
        f.stall_name,
        f.contact_person,
        f.description,
        f.address,
        f.latitude,
        f.longitude,
        f.created_at

    ORDER BY f.stall_name ASC
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

$farmers = [];

while ($row = $result->fetch_assoc()) {
    $farmers[] = $row;
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Farmers - MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

    <section class="farmers-hero">

        <div class="container">

            <h1>
                Local Farmers
            </h1>

            <p>
                Discover local farmers, their products,
                and the places where they sell.
            </p>

        </div>

    </section>


    <main class="container">

        <section class="farmers-filters">

            <form
                method="GET"
                action="farmers.php"
            >

                <div class="filter-group">

                    <label for="search">
                        Search Farmers
                    </label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="Search farmer, stall or location..."
                    >

                </div>


                <div class="filter-actions">

                    <button
                        type="submit"
                        class="btn-search"
                    >
                        Search
                    </button>


                    <a
                        href="farmers.php"
                        class="btn-reset"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </section>


        <div class="result-info">

            <?= e(count($farmers)) ?>
            farmer(s) found

        </div>


        <?php if (empty($farmers)): ?>

            <section class="empty-state">

                <h2>
                    No Farmers Found
                </h2>

                <p>
                    Try changing your search.
                </p>

            </section>
            
        <?php else: ?>

            <section class="farmers-grid">


                <?php foreach ($farmers as $farmer): ?>

                    <article class="farmer-card">


                        <header class="farmer-header">

                            <h2>

                                <?= e(
                                    $farmer['stall_name']
                                ) ?>

                            </h2>

                        </header>


                        <div class="farmer-body">


                            <?php if (
                                !empty($farmer['description'])
                            ): ?>

                                <p class="farmer-description">

                                    <?= e(
                                        truncateText(
                                            $farmer['description'],
                                            180
                                        )
                                    ) ?>

                                </p>

                            <?php endif; ?>


                            <div class="farmer-info">


                                <?php if (
                                    !empty($farmer['contact_person'])
                                ): ?>

                                    <div class="info-item">

                                        <span>
                                            👤
                                        </span>

                                        <span>

                                            <?= e(
                                                $farmer['contact_person']
                                            ) ?>

                                        </span>

                                    </div>

                                <?php endif; ?>
                                
                                <?php if (
                                    !empty($farmer['address'])
                                ): ?>

                                    <div class="info-item">

                                        <span>
                                            📍
                                        </span>

                                        <span>

                                            <?= e(
                                                $farmer['address']
                                            ) ?>

                                        </span>

                                    </div>

                                <?php endif; ?>


                            </div>


                            <div class="farmer-stats">

                                <div class="farmer-stat">

                                    <strong>

                                        <?= e(
                                            $farmer['product_count']
                                        ) ?>

                                    </strong>

                                    <span>
                                        Products
                                    </span>

                                </div>

                            </div>


                            <?php if (
                                !empty($farmer['latitude'])
                                &&
                                !empty($farmer['longitude'])
                            ): ?>

                                <a
                                    href="https://www.google.com/maps/search/?api=1&query=<?= e($farmer['latitude']) ?>,<?= e($farmer['longitude']) ?>"
                                    class="map-link"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >

                                    📍 View on Map

                                </a>

                            <?php endif; ?>


                        </div>

                    </article>

                <?php endforeach; ?>


            </section>

        <?php endif; ?>


    </main>

</body>

</html>