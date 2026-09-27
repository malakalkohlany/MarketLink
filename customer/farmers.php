<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['toggle_favorite'])
) {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: farmers.php');
        exit;
    }

    $farmerId = filter_input(
        INPUT_POST,
        'farmer_id',
        FILTER_VALIDATE_INT
    );

    if (!$farmerId || !$customerId) {
        header('Location: farmers.php');
        exit;
    }

    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT customer_id
         FROM favorite_farmers
         WHERE customer_id = ?
           AND farmer_id = ?
         LIMIT 1"
    );

    if (!$checkStmt) {
        die(
            'Favorite check prepare failed: '
            . mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $checkStmt,
        "ii",
        $customerId,
        $farmerId
    );

    if (!mysqli_stmt_execute($checkStmt)) {
        die(
            'Favorite check execute failed: '
            . mysqli_stmt_error($checkStmt)
        );
    }

    mysqli_stmt_store_result($checkStmt);

    $exists =
        mysqli_stmt_num_rows($checkStmt) > 0;

    mysqli_stmt_close($checkStmt);

    if ($exists) {
        $deleteStmt = mysqli_prepare(
            $conn,
            "DELETE FROM favorite_farmers
             WHERE customer_id = ?
               AND farmer_id = ?"
        );

        if (!$deleteStmt) {
            die(
                'Favorite delete prepare failed: '
                . mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $deleteStmt,
            "ii",
            $customerId,
            $farmerId
        );

        if (!mysqli_stmt_execute($deleteStmt)) {
            die(
                'Favorite delete failed: '
                . mysqli_stmt_error($deleteStmt)
            );
        }

        mysqli_stmt_close($deleteStmt);
    } else {
        $insertStmt = mysqli_prepare(
            $conn,
            "INSERT INTO favorite_farmers
            (
                customer_id,
                farmer_id,
                created_at
            )
            VALUES (?, ?, NOW())"
        );

        if (!$insertStmt) {
            die(
                'Favorite insert prepare failed: '
                . mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $insertStmt,
            "ii",
            $customerId,
            $farmerId
        );

        if (!mysqli_stmt_execute($insertStmt)) {
            die(
                'Favorite insert failed: '
                . mysqli_stmt_error($insertStmt)
            );
        }

        mysqli_stmt_close($insertStmt);
    }

    header('Location: farmers.php');
    exit;
}

$favoriteFarmers = [];

$favoriteStmt = mysqli_prepare(
    $conn,
    "SELECT farmer_id
     FROM favorite_farmers
     WHERE customer_id = ?"
);

if (!$favoriteStmt) {
    die(
        'Favorite list prepare failed: '
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $favoriteStmt,
    "i",
    $customerId
);

if (!mysqli_stmt_execute($favoriteStmt)) {
    die(
        'Favorite list execute failed: '
        . mysqli_stmt_error($favoriteStmt)
    );
}

mysqli_stmt_bind_result(
    $favoriteStmt,
    $favoriteFarmerId
);

while (mysqli_stmt_fetch($favoriteStmt)) {
    $favoriteFarmers[] =
        (int) $favoriteFarmerId;
}

mysqli_stmt_close($favoriteStmt);

$farmers = [];

$sql = "
    SELECT
        id,
        stall_name,
        contact_person,
        description,
        address,
        latitude,
        longitude
    FROM farmers
    WHERE approval_status = 'approved'
    ORDER BY stall_name ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $farmers[] = $row;
    }
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
    <title>Farmers - MarketLink</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >
    <link
        rel="stylesheet"
        href="../assets/css/navbar.css"
    >
    <link
        rel="stylesheet"
        href="../assets/css/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        .farmers-page {
            padding: 24px;
        }

        .map-container {
            width: 100%;
            margin-bottom: 30px;
        }

        #map {
            width: 100%;
            height: 500px;
            border-radius: 12px;
            overflow: hidden;
        }

        .farmer-count {
            margin-bottom: 20px;
            font-size: 16px;
            color: #666;
        }

        .farmer-search {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
            max-width: 300px;
        }

        .farmer-search label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .farmer-search input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            background: #ffffff;
            font-size: 14px;
            box-sizing: border-box;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .farmer-search input:focus {
            outline: none;
            border-color: #888;
            box-shadow:
                0 0 0 3px
                rgba(0, 0, 0, 0.06);
        }

        .location-filter {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .location-filter button {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            background: #222;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .location-filter button:hover {
            background: #444;
            transform: translateY(-1px);
        }

        .location-filter button:active {
            transform: translateY(0);
        }

        #showAllFarmers {
            background: #eeeeee;
            color: #333333;
        }

        #showAllFarmers:hover {
            background: #dddddd;
        }

        #farmerLocationStatus {
            font-size: 14px;
            color: #666666;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .farmers-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fill,
                    minmax(280px, 1fr)
                );
            gap: 20px;
        }

        .farmer-card {
            position: relative !important;
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 22px;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .farmer-card:hover {
            transform: translateY(-3px);
            border-color: #d8d8d8;
            box-shadow:
                0 6px 18px
                rgba(0, 0, 0, 0.08);
        }

        .farmer-favorite-form {
            position: absolute !important;
            top: 12px !important;
            right: 12px !important;
            z-index: 12 !important;
            margin: 0 !important;
        }

        .farmer-favorite-button {
            width: 36px !important;
            height: 36px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: none !important;
            border-radius: 50% !important;
            background: #ffffff !important;
            cursor: pointer !important;
            font-size: 20px !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, 0.10) !important;
            transition: 0.2s ease;
        }

        .farmer-favorite-button.empty {
            color: #555555 !important;
        }

        .farmer-favorite-button.filled {
            color: #e53935 !important;
        }

        .farmer-favorite-button:hover {
            transform: scale(1.08);
            box-shadow:
                0 3px 8px
                rgba(0, 0, 0, 0.14);
        }

        .farmer-card h3 {
            margin-top: 0;
            margin-bottom: 14px;
            padding-right: 45px;
            font-size: 19px;
            line-height: 1.3;
        }

        .farmer-card p {
            margin: 8px 0;
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .farmer-card p strong {
            color: #333;
        }

        .farmer-card .farmer-description {
            margin-top: 14px;
            line-height: 1.6;
        }

        .farmer-card .view-details {
            display: inline-block;
            margin-top: 16px;
            padding: 9px 15px;
            border-radius: 8px;
            background: #222;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .farmer-card .view-details:hover {
            background: #444;
            transform: translateY(-1px);
        }

        .no-farmers {
            padding: 30px;
            text-align: center;
            color: #777;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
        }

        #map .leaflet-pane {
            z-index: 1 !important;
        }

        #map .leaflet-top,
        #map .leaflet-bottom {
            z-index: 2 !important;
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="farmers-page">

        <h1 class="section-title">
            Farmers
        </h1>

        <div class="farmer-count">
            <?= count($farmers) ?>
            farmer<?= count($farmers) !== 1 ? 's' : '' ?>
            available
        </div>

        <div class="farmer-search">
            <label for="farmerSearch">
                Search Farmers
            </label>

            <input
                type="text"
                id="farmerSearch"
                placeholder="Search by stall name or address"
                autocomplete="off"
            >
        </div>

        <div class="location-filter">
            <button
                type="button"
                id="findNearbyFarmers"
            >
                Find Farmers Near Me
            </button>

            <button
                type="button"
                id="showAllFarmers"
                style="display: none;"
            >
                Show All Farmers
            </button>

            <span
                id="farmerLocationStatus"
                style="display: none;"
            ></span>
        </div>

        <div class="map-container">
            <div id="map"></div>
        </div>

        <h2 class="section-title">
            All Farmers
        </h2>

        <?php if (!empty($farmers)): ?>

            <div class="farmers-grid">

                <?php foreach ($farmers as $farmer): ?>

                    <?php
                    $farmerId =
                        (int) $farmer['id'];

                    $isFavorite =
                        in_array(
                            $farmerId,
                            $favoriteFarmers,
                            true
                        );
                    ?>

                    <div
                        class="farmer-card"
                        data-farmer-id="<?= $farmerId ?>"
                    >

                        <form
                            method="POST"
                            action="farmers.php"
                            class="farmer-favorite-form"
                        >

                        <?= csrf_field() ?>
                        
                            <input
                                type="hidden"
                                name="farmer_id"
                                value="<?= $farmerId ?>"
                            >

                            <button
                                type="submit"
                                name="toggle_favorite"
                                class="farmer-favorite-button <?= $isFavorite ? 'filled' : 'empty' ?>"
                                title="<?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                aria-label="<?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                            >
                                <?php if ($isFavorite): ?>
                                    <i class="fa-solid fa-heart"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-heart"></i>
                                <?php endif; ?>
                            </button>
                        </form>

                        <h3>
                            <?= e(
                                $farmer['stall_name']
                            ) ?>
                        </h3>

                        <?php if (!empty($farmer['contact_person'])): ?>
                            <p>
                                <strong>
                                    Contact:
                                </strong>
                                <?= e(
                                    $farmer['contact_person']
                                ) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($farmer['address'])): ?>
                            <p>
                                <strong>
                                    Address:
                                </strong>
                                <?= e(
                                    $farmer['address']
                                ) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($farmer['description'])): ?>
                            <p class="farmer-description">
                                <?= e(
                                    $farmer['description']
                                ) ?>
                            </p>
                        <?php endif; ?>

                        <a
                            href="farmer_details.php?id=<?= $farmerId ?>"
                            class="view-details"
                        >
                            View Details
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="no-farmers">
                <p>
                    No farmers are currently available.
                </p>
            </div>

        <?php endif; ?>

    </div>
</main>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>

<script>
    const map =
        L.map('map').setView(
            [42.3555, -71.0565],
            4
        );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    const farmers =
        <?= json_encode(
            $farmers,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        ); ?>;

    const farmerSearch =
        document.getElementById('farmerSearch');

    function applyFarmerSearch() {
        const searchTerm =
            farmerSearch.value.trim().toLowerCase();

        const cards =
            document.querySelectorAll('.farmer-card');

        cards.forEach(function (card) {
            const farmerName =
                card.querySelector('h3')?.textContent
                    .trim()
                    .toLowerCase() || '';

            const cardText =
                card.textContent
                    .trim()
                    .toLowerCase();

            const matchesSearch =
                searchTerm === '' ||
                farmerName.includes(searchTerm) ||
                cardText.includes(searchTerm);

            card.style.display =
                matchesSearch
                    ? ''
                    : 'none';
        });
    }

    farmerSearch.addEventListener(
        'input',
        applyFarmerSearch
    );

    function calculateDistance(
        lat1,
        lon1,
        lat2,
        lon2
    ) {
        const R = 6371;

        const dLat =
            (lat2 - lat1) *
            Math.PI / 180;

        const dLon =
            (lon2 - lon1) *
            Math.PI / 180;

        const a =
            Math.sin(dLat / 2) *
            Math.sin(dLat / 2) +
            Math.cos(
                lat1 * Math.PI / 180
            ) *
            Math.cos(
                lat2 * Math.PI / 180
            ) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);

        const c =
            2 * Math.atan2(
                Math.sqrt(a),
                Math.sqrt(1 - a)
            );

        return R * c;
    }

    const findNearbyFarmers =
        document.getElementById(
            'findNearbyFarmers'
        );

    const showAllFarmers =
        document.getElementById(
            'showAllFarmers'
        );

    const farmerLocationStatus =
        document.getElementById(
            'farmerLocationStatus'
        );

    function sortFarmersByLocation(
        userLatitude,
        userLongitude
    ) {
        const farmerCards =
            document.querySelectorAll(
                '.farmer-card'
            );

        const cards =
            Array.from(farmerCards);

        cards.forEach(function (card) {
            const farmerId =
                parseInt(
                    card.dataset.farmerId
                );

            const farmer =
                farmers.find(function (item) {
                    return (
                        parseInt(item.id) ===
                        farmerId
                    );
                });

            if (
                farmer &&
                farmer.latitude !== null &&
                farmer.longitude !== null &&
                farmer.latitude !== '' &&
                farmer.longitude !== ''
            ) {
                const farmerLatitude =
                    parseFloat(
                        farmer.latitude
                    );

                const farmerLongitude =
                    parseFloat(
                        farmer.longitude
                    );

                if (
                    !isNaN(farmerLatitude) &&
                    !isNaN(farmerLongitude)
                ) {
                    card.dataset.distance =
                        calculateDistance(
                            userLatitude,
                            userLongitude,
                            farmerLatitude,
                            farmerLongitude
                        );
                } else {
                    card.dataset.distance =
                        '999999999';
                }
            } else {
                card.dataset.distance =
                    '999999999';
            }
        });

        cards.sort(function (a, b) {
            return (
                parseFloat(
                    a.dataset.distance
                ) -
                parseFloat(
                    b.dataset.distance
                )
            );
        });

        const farmersGrid =
            document.querySelector(
                '.farmers-grid'
            );

        if (farmersGrid) {
            cards.forEach(function (card) {
                farmersGrid.appendChild(card);
            });
        }
    }

    findNearbyFarmers.addEventListener(
        'click',
        function () {
            if (!navigator.geolocation) {
                farmerLocationStatus.textContent =
                    'Location is not supported by this browser.';

                farmerLocationStatus.style.display =
                    'inline';

                return;
            }

            farmerLocationStatus.textContent =
                'Getting your location...';

            farmerLocationStatus.style.display =
                'inline';

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const userLatitude =
                        position.coords.latitude;

                    const userLongitude =
                        position.coords.longitude;

                    sortFarmersByLocation(
                        userLatitude,
                        userLongitude
                    );

                    map.setView(
                        [
                            userLatitude,
                            userLongitude
                        ],
                        10
                    );

                    L.marker([
                        userLatitude,
                        userLongitude
                    ])
                        .addTo(map)
                        .bindPopup(
                            'Your Location'
                        )
                        .openPopup();

                    farmerLocationStatus.textContent =
                        'Farmers sorted by distance from your location.';

                    showAllFarmers.style.display =
                        'inline-block';
                },
                function () {
                    farmerLocationStatus.textContent =
                        'Unable to get your location.';
                }
            );
        }
    );

    showAllFarmers.addEventListener(
        'click',
        function () {
            location.reload();
        }
    );

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const markers = [];

    farmers.forEach(function (farmer) {
        const latitude =
            parseFloat(
                farmer.latitude
            );

        const longitude =
            parseFloat(
                farmer.longitude
            );

        if (
            Number.isNaN(latitude) ||
            Number.isNaN(longitude)
        ) {
            return;
        }

        const marker =
            L.marker([
                latitude,
                longitude
            ]).addTo(map);

        const popupContent = `
            <div>
                <strong>
                    ${escapeHtml(
                        farmer.stall_name
                    )}
                </strong>
                <br>
                ${escapeHtml(
                    farmer.address || ''
                )}
                <br><br>
                <a
                    href="farmer_details.php?id=${farmer.id}"
                >
                    View Details
                </a>
            </div>
        `;

        marker.bindPopup(
            popupContent
        );

        markers.push(marker);
    });

    if (markers.length > 0) {
        const group =
            L.featureGroup(
                markers
            );

        map.fitBounds(
            group.getBounds(),
            {
                padding: [30, 30]
            }
        );
    }

    setTimeout(function () {
        map.invalidateSize();
    }, 300);
</script>

</body>
</html>