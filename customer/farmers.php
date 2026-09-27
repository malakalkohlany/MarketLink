<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();


// ==========================================================================
// Toggle Farmer Favorite
// ==========================================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['toggle_favorite'])
) {

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


    // ================================================================
    // Remove Favorite
    // ================================================================

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
    }


    // ================================================================
    // Add Favorite
    // ================================================================

    else {

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
                . mysqli_stmt_error($conn)
            );
        }


        mysqli_stmt_close($insertStmt);
    }


    header('Location: farmers.php');

    exit;
}


// ==========================================================================
// Get Favorite Farmers
// ==========================================================================

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


// ==========================================================================
// Get Approved Farmers
// ==========================================================================

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


    <!-- Leaflet CSS -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
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


    <!-- Font Awesome -->

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


        /*
        |--------------------------------------------------------------------------
        | Farmer Card
        |--------------------------------------------------------------------------
        */

        .farmer-card {

            position: relative !important;

            background: #ffffff;

            border: 1px solid #e5e5e5;

            border-radius: 12px;

            padding: 20px;

            transition: 0.2s ease;

        }


        .farmer-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 4px 12px
                rgba(0, 0, 0, 0.08);

        }


        /*
        |--------------------------------------------------------------------------
        | Favorite Button
        |--------------------------------------------------------------------------
        */

        .farmer-favorite-form {

            position: absolute !important;

            top: 12px !important;

            right: 12px !important;

            z-index: 100 !important;

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

        }


        /*
        |--------------------------------------------------------------------------
        | Farmer Content
        |--------------------------------------------------------------------------
        */

        .farmer-card h3 {

            margin-top: 0;

            margin-bottom: 10px;

            padding-right: 45px;

        }


        .farmer-card p {

            margin: 8px 0;

            color: #666;

        }


        .farmer-card .farmer-description {

            margin-top: 12px;

            line-height: 1.5;

        }


        .farmer-card .view-details {

            display: inline-block;

            margin-top: 15px;

            padding: 10px 16px;

            border-radius: 8px;

            text-decoration: none;

        }


        .no-farmers {

            padding: 30px;

            text-align: center;

            color: #777;

            background: #fff;

            border: 1px solid #e5e5e5;

            border-radius: 12px;

        }


        /*
        |--------------------------------------------------------------------------
        | Leaflet Fix
        |--------------------------------------------------------------------------
        */

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


            <!-- =========================================================
                 MAP
                 ========================================================= -->

            <div class="map-container">

                <div id="map"></div>

            </div>


            <!-- =========================================================
                 FARMERS
                 ========================================================= -->

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


                            <!-- Favorite Button -->

                            <form
                                method="POST"
                                action="farmers.php"
                                class="farmer-favorite-form"
                            >

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


    <!-- =========================================================
         Leaflet JS
         ========================================================= -->

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>


    <script>

        // =====================================================
        // Initialize Map
        // =====================================================

        const map =
            L.map('map').setView(
                [42.3555, -71.0565],
                4
            );


        // =====================================================
        // OpenStreetMap
        // =====================================================

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                maxZoom: 19,

                attribution:
                    '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);


        // =====================================================
        // Farmers Data
        // =====================================================

        const farmers =
            <?= json_encode(
                $farmers,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ); ?>;


        // =====================================================
        // Sort Farmers By User Location
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
        // User Location
        // =====================================================

        // Cambridge, Massachusetts, USA

        const userLatitude =
            42.3736;


        const userLongitude =
            -71.1097;


        // =====================================================
        // Calculate Distance For Each Farmer
        // =====================================================

        const farmerCards =
            document.querySelectorAll(
                '.farmer-card'
            );


        const cards =
            Array.from(
                farmerCards
            );


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


        // =====================================================
        // Sort Cards From Nearest To Farthest
        // =====================================================

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


        // =====================================================
        // Rebuild Farmers Grid
        // =====================================================

        const farmersGrid =
            document.querySelector(
                '.farmers-grid'
            );


        if (farmersGrid) {

            cards.forEach(function (card) {

                farmersGrid.appendChild(card);

            });

        }


        // =====================================================
        // Escape HTML
        // =====================================================

        function escapeHtml(value) {

            return String(value ?? '')

                .replace(/&/g, '&amp;')

                .replace(/</g, '&lt;')

                .replace(/>/g, '&gt;')

                .replace(/"/g, '&quot;')

                .replace(/'/g, '&#039;');

        }


        // =====================================================
        // Add Farmer Markers
        // =====================================================

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


        // =====================================================
        // Fit Map To Markers
        // =====================================================

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


        // =====================================================
        // Fix Map Size
        // =====================================================

        setTimeout(function () {

            map.invalidateSize();

        }, 300);

    </script>


</body>

</html>