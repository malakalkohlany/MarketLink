<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);


// =====================================================
// Customer ID
// =====================================================

$customerId = (int) getUserId();


// =====================================================
// Toggle Favorite Market
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['toggle_favorite'])
) {
    $marketId = filter_input(
        INPUT_POST,
        'market_id',
        FILTER_VALIDATE_INT
    );

    if (!$marketId || !$customerId) {
        header('Location: markets.php');
        exit;
    }


    // Check if market is already favorite
    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT customer_id
         FROM favorite_markets
         WHERE customer_id = ?
           AND market_id = ?
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
        $marketId
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


    // =================================================
    // Remove Favorite
    // =================================================

    if ($exists) {

        $deleteStmt = mysqli_prepare(
            $conn,
            "DELETE FROM favorite_markets
             WHERE customer_id = ?
               AND market_id = ?"
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
            $marketId
        );

        if (!mysqli_stmt_execute($deleteStmt)) {
            die(
                'Favorite delete failed: '
                . mysqli_stmt_error($deleteStmt)
            );
        }

        mysqli_stmt_close($deleteStmt);
    }


    // =================================================
    // Add Favorite
    // =================================================

    else {

        $insertStmt = mysqli_prepare(
            $conn,
            "INSERT INTO favorite_markets
            (
                customer_id,
                market_id,
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
            $marketId
        );

        if (!mysqli_stmt_execute($insertStmt)) {
            die(
                'Favorite insert failed: '
                . mysqli_stmt_error($insertStmt)
            );
        }

        mysqli_stmt_close($insertStmt);
    }


    // Return to Markets page
    header('Location: markets.php');
    exit;
}


// =====================================================
// Get Customer Favorite Markets
// =====================================================

$favoriteMarkets = [];

$favoriteStmt = mysqli_prepare(
    $conn,
    "SELECT market_id
     FROM favorite_markets
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
    $favoriteMarketId
);

while (mysqli_stmt_fetch($favoriteStmt)) {

    $favoriteMarkets[] =
        (int) $favoriteMarketId;
}

mysqli_stmt_close($favoriteStmt);


// =====================================================
// Get Markets
// =====================================================

$markets = [];

$sql = "
    SELECT
        id,
        name,
        address,
        latitude,
        longitude,
        operating_days,
        opening_time,
        closing_time
    FROM markets
    WHERE status = 'active'
    ORDER BY name ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $markets[] = $row;
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

    <title>Markets - MarketLink</title>


    <!-- Leaflet CSS -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- Project CSS -->

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


    <style>

        /*
        |--------------------------------------------------------------------------
        | Original Map
        |--------------------------------------------------------------------------
        */

        #map {

            width: 100%;

            height: 500px;

        }


        /*
        |--------------------------------------------------------------------------
        | Markets Section
        |--------------------------------------------------------------------------
        */

        .markets-page {

            padding: 30px;

        }


        .markets-page h1 {

            margin-bottom: 8px;

        }


        .markets-page > p {

            margin-bottom: 25px;

        }


        .market-count {

            margin-bottom: 20px;

            font-weight: 600;

        }


        .section-title {

            font-size: 24px;

            margin-top: 35px;

            margin-bottom: 20px;

        }


        .markets-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(260px, 1fr)
                );

            gap: 20px;

        }


        /*
        |--------------------------------------------------------------------------
        | Market Card
        |--------------------------------------------------------------------------
        */

        .market-card {

            position: relative;

            background: #ffffff;

            border-radius: 14px;

            padding: 22px;

            box-shadow:
                0 2px 10px
                rgba(0, 0, 0, 0.08);

            display: flex;

            flex-direction: column;

            min-height: 220px;

            box-sizing: border-box;

        }


        .market-card h3 {

            margin: 0 0 12px;

            padding-right: 45px;

            font-size: 20px;

        }


        .market-card .address {

            margin-bottom: 12px;

        }


        .market-card .market-info {

            margin-bottom: 8px;

            line-height: 1.5;

        }


        /*
        |--------------------------------------------------------------------------
        | Favorite Heart
        |--------------------------------------------------------------------------
        */

        .market-favorite-form {

            position: absolute !important;

            top: 12px !important;

            right: 12px !important;

            z-index: 100 !important;

            margin: 0 !important;

        }


        .market-favorite-button {

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


        .market-favorite-button.empty {

            color: #555555 !important;

        }


        .market-favorite-button.filled {

            color: #e53935 !important;

        }


        .market-favorite-button:hover {

            transform: scale(1.08);

        }


        /*
        |--------------------------------------------------------------------------
        | Details Button
        |--------------------------------------------------------------------------
        */

        .market-details-button {

            margin-top: auto;

            display: inline-block;

            text-align: center;

            text-decoration: none;

            padding: 11px 16px;

            border-radius: 8px;

            background: #2f6f4e;

            color: #ffffff;

            font-weight: 600;

            transition: 0.2s;

        }


        .market-details-button:hover {

            opacity: 0.9;

        }


        .no-markets {

            background: #ffffff;

            padding: 25px;

            border-radius: 12px;

            color: #666;

        }


        /*
        |--------------------------------------------------------------------------
        | Leaflet Fix
        |--------------------------------------------------------------------------
        |
        | Only affects Leaflet inside the map.
        | Navbar and Sidebar are NOT changed.
        |
        */

        #map .leaflet-pane {

            z-index: 1 !important;

        }


        #map .leaflet-top,
        #map .leaflet-bottom {

            z-index: 2 !important;

        }


        @media (max-width: 768px) {

            .markets-page {

                padding: 20px;

            }


            #map {

                height: 400px;

            }

        }

    </style>

</head>


<body>


    <?php include __DIR__ . '/../includes/navbar.php'; ?>


    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">


        <div class="markets-page">


            <h1>

                Markets

            </h1>


            <p>

                Find nearby markets and view their locations.

            </p>


            <div class="market-count">

                Markets found:

                <?= count($markets); ?>

            </div>

            <div class="location-filter">

                <button
                    type="button"
                    id="findNearbyMarkets"
                >
                    <i class="fa-solid fa-location-dot"></i>
                    Find Markets Near Me
                </button>

                <button
                    type="button"
                    id="showAllMarkets"
                    style="display: none;"
                >
                    Show All Markets
                </button>

                <span
                    id="locationStatus"
                    style="display: none;"
                ></span>

            </div>

            <!-- =====================================================
                 MAP
                 ===================================================== -->

            <div id="map"></div>


            <!-- =====================================================
                 MARKETS
                 ===================================================== -->

            <h2 class="section-title">

                Our Markets

            </h2>


            <?php if (!empty($markets)): ?>


                <div class="markets-grid">


                    <?php foreach ($markets as $market): ?>


                        <?php

                        $marketId =
                            (int) $market['id'];

                        $isFavorite =
                            in_array(
                                $marketId,
                                $favoriteMarkets,
                                true
                            );

                        ?>


                        <div
                            class="market-card"
                            data-market-id="<?= $marketId ?>"
                        >


                            <!-- =================================================
                                 Favorite Heart
                                 ================================================= -->

                            <form
                                method="POST"
                                action="markets.php"
                                class="market-favorite-form"
                            >

                                <input
                                    type="hidden"
                                    name="market_id"
                                    value="<?= $marketId ?>"
                                >


                                <button
                                    type="submit"
                                    name="toggle_favorite"
                                    class="market-favorite-button <?= $isFavorite ? 'filled' : 'empty' ?>"
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
                                    $market['name']
                                ); ?>

                            </h3>


                            <div class="address">

                                📍

                                <?= e(
                                    $market['address']
                                ); ?>

                            </div>


                            <div class="market-info">

                                <strong>

                                    Opening:

                                </strong>

                                <?= e(
                                    $market['opening_time']
                                ); ?>

                            </div>


                            <div class="market-info">

                                <strong>

                                    Closing:

                                </strong>

                                <?= e(
                                    $market['closing_time']
                                ); ?>

                            </div>


                            <div class="market-info">

                                <strong>

                                    Operating Days:

                                </strong>

                                <?= e(
                                    $market['operating_days']
                                ); ?>

                            </div>


                            <a
                                href="market_details.php?id=<?= $marketId ?>"
                                class="market-details-button"
                            >

                                View Details

                            </a>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="no-markets">

                    No active markets are currently available.

                </div>


            <?php endif; ?>


        </div>

    </main>


    <!-- =========================================================
         Leaflet JS
         ========================================================= -->

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>


    <script>

        const markets =
            <?= json_encode(
                $markets,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ); ?>;

        let map;

        // =====================================================
        // Sort Markets By User Location
        // =====================================================

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


// =====================================================
// Find Markets Near Me
// =====================================================

document.getElementById('findNearbyMarkets').addEventListener('click', function () {

    if (!navigator.geolocation) {

        alert(
            'Location services are not supported by this browser.'
        );

        return;
    }


    navigator.geolocation.getCurrentPosition(

        function (position) {

            const userLatitude =
                position.coords.latitude;

            const userLongitude =
                position.coords.longitude;


            // Calculate distance for every market
            markets.forEach(function (market) {

                if (
                    market.latitude !== null &&
                    market.longitude !== null &&
                    market.latitude !== '' &&
                    market.longitude !== ''
                ) {

                    const marketLatitude =
                        parseFloat(
                            market.latitude
                        );

                    const marketLongitude =
                        parseFloat(
                            market.longitude
                        );


                    if (
                        !isNaN(marketLatitude) &&
                        !isNaN(marketLongitude)
                    ) {

                        market.distance =
                            calculateDistance(
                                userLatitude,
                                userLongitude,
                                marketLatitude,
                                marketLongitude
                            );

                    } else {

                        market.distance = Infinity;

                    }

                } else {

                    market.distance = Infinity;

                }

            });


            // Sort nearest to farthest
            markets.sort(function (a, b) {

                return (
                    a.distance -
                    b.distance
                );

            });

            // Center the map on the user's location
            map.setView(
                [userLatitude, userLongitude],
                12
            );

            // Get market cards
            const grid =
                document.querySelector(
                    '.markets-grid'
                );


            const cards =
                Array.from(
                    document.querySelectorAll(
                        '.market-card'
                    )
                );


            // Rebuild cards in nearest-first order
            if (grid) {

                markets.forEach(function (market) {

                    const card =
                        cards.find(function (item) {

                            return (
                                parseInt(
                                    item.dataset.marketId
                                ) ===
                                parseInt(
                                    market.id
                                )
                            );

                        });


                    if (card) {

                        grid.appendChild(card);

                    }

                });

            }


            // Update status
            document.getElementById(
                'locationStatus'
            ).style.display = 'inline';

            document.getElementById(
                'locationStatus'
            ).textContent =
                'Markets sorted by distance from your location.';


            // Show "Show All Markets" button
            document.getElementById(
                'showAllMarkets'
            ).style.display = 'inline-block';

        },


        function () {

            alert(
                'Unable to get your location. Please allow location access.'
            );

        }

    );

});

// =====================================================
// Show All Markets
// =====================================================

document.getElementById('showAllMarkets').addEventListener('click', function () {

    const grid =
        document.querySelector('.markets-grid');

    const cards =
        Array.from(
            document.querySelectorAll('.market-card')
        );

    if (grid) {

        cards.sort(function (a, b) {
            return (
                parseInt(a.dataset.marketId) -
                parseInt(b.dataset.marketId)
            );
        });

        cards.forEach(function (card) {
            grid.appendChild(card);
        });

    }

    // Remove nearby status
    document.getElementById(
        'locationStatus'
    ).style.display = 'none';

    // Hide Show All button
    this.style.display = 'none';

    // Return map to default view
    map.setView(
        [defaultLatitude, defaultLongitude],
        4
    );

});

        // =====================================================
        // Default Map Position
        // =====================================================

        const defaultLatitude =
            42.3555;

        const defaultLongitude =
            -71.0565;


        // =====================================================
        // Create Map
        // =====================================================

        map =
            L.map('map').setView(
                [
                    defaultLatitude,
                    defaultLongitude
                ],
                4
            );


        // =====================================================
        // OpenStreetMap
        // =====================================================

        L.tileLayer(
            'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
            {
                maxZoom: 19,
                attribution:
                    '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);


        // =====================================================
        // Add Market Markers
        // =====================================================

        const markers = [];


        markets.forEach(function (market) {

            if (
                market.latitude !== null &&
                market.longitude !== null &&
                market.latitude !== '' &&
                market.longitude !== ''
            ) {

                const latitude =
                    parseFloat(
                        market.latitude
                    );

                const longitude =
                    parseFloat(
                        market.longitude
                    );


                if (
                    !isNaN(latitude) &&
                    !isNaN(longitude)
                ) {

                    const marker =
                        L.marker([
                            latitude,
                            longitude
                        ]).addTo(map);


                    const directionsLink =
                        '<a href="#" onclick="getDirections(' +
                        latitude +
                        ',' +
                        longitude +
                        '); return false;">' +
                        'Get Directions' +
                        '</a>';


                    marker.bindPopup(
                        '<b>' +
                        market.name +
                        '</b><br>' +
                        market.address +
                        '<br><br>' +
                        directionsLink
                    );


                    markers.push(marker);

                }

            }

        });


        // =====================================================
        // Fit Map Around Markets
        // =====================================================

        if (markers.length > 0) {

            const group =
                L.featureGroup(markers);


            map.fitBounds(
                group.getBounds(),
                {
                    padding: [40, 40]
                }
            );

        }


        // =====================================================
        // Get Directions
        // =====================================================

        function getDirections(
            destinationLatitude,
            destinationLongitude
        ) {

            if (!navigator.geolocation) {

                alert(
                    'Location services are not supported by this browser.'
                );

                return;

            }


            navigator.geolocation.getCurrentPosition(

                function (position) {

                    const userLatitude =
                        position.coords.latitude;

                    const userLongitude =
                        position.coords.longitude;


                    const directionsUrl =
                        'https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=' +
                        userLatitude +
                        ',' +
                        userLongitude +
                        ';' +
                        destinationLatitude +
                        ',' +
                        destinationLongitude;


                    window.open(
                        directionsUrl,
                        '_blank'
                    );

                },


                function () {

                    alert(
                        'Unable to get your location. Please allow location access.'
                    );

                }

            );

        }


        // =====================================================
        // Fix Leaflet Rendering
        // =====================================================

        setTimeout(function () {

            map.invalidateSize();

        }, 300);

    </script>


</body>

</html>