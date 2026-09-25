<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('customer');

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

    <style>

        .farmers-page {
            padding: 30px;
        }

        .farmers-page h1 {
            margin-bottom: 8px;
        }

        .farmers-page > p {
            margin-bottom: 25px;
        }

        .map-container {
            width: 100%;
            background: #ffffff;
            border-radius: 14px;
            padding: 15px;
            box-sizing: border-box;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 35px;
        }

        #map {
            width: 100%;
            height: 500px;
            min-height: 500px;
            border-radius: 10px;
            z-index: 1;
        }

        .farmer-count {
            margin-bottom: 15px;
            font-weight: 600;
        }

        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .farmers-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(260px, 1fr)
            );
            gap: 20px;
        }

        .farmer-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            min-height: 220px;
            box-sizing: border-box;
        }

        .farmer-card h3 {
            margin: 0 0 10px;
            font-size: 20px;
        }

        .farmer-card .contact-person {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .farmer-card .address {
            margin-bottom: 12px;
        }

        .farmer-card .description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 18px;
        }

        .farmer-card .details-button {
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

        .farmer-card .details-button:hover {
            opacity: 0.9;
        }

        .no-farmers {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            color: #666;
        }

        @media (max-width: 768px) {

            .farmers-page {
                padding: 20px;
            }

            #map {
                height: 400px;
                min-height: 400px;
            }

        }

    </style>

</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">

        <div class="farmers-page">

            <h1>Farmers</h1>

            <p>
                Find farmers and view their stall locations.
            </p>


            <!-- Farmers Count -->

            <div class="farmer-count">

                Farmers found:
                <?= count($farmers); ?>

            </div>


            <!-- Map -->

            <div class="map-container">

                <div id="map"></div>

            </div>


            <!-- Farmers Section -->

            <h2 class="section-title">
                Our Farmers
            </h2>


            <?php if (!empty($farmers)): ?>

                <div class="farmers-grid">

                    <?php foreach ($farmers as $farmer): ?>

                        <div class="farmer-card">

                            <h3>
                                <?= e($farmer['stall_name']); ?>
                            </h3>


                            <div class="contact-person">

                                Contact:
                                <?= e($farmer['contact_person']); ?>

                            </div>


                            <div class="address">

                                📍
                                <?= e($farmer['address']); ?>

                            </div>


                            <div class="description">

                                <?= e(
                                    truncateText(
                                        $farmer['description'],
                                        120
                                    )
                                ); ?>

                            </div>


                            <a
                                href="farmer_details.php?id=<?= (int)$farmer['id']; ?>"
                                class="details-button"
                            >
                                View Details
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="no-farmers">

                    No approved farmers are currently available.

                </div>

            <?php endif; ?>

        </div>

    </main>


    <!-- Leaflet JS -->

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>


    <script>

        const farmers = <?= json_encode(
            $farmers,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        ); ?>;


        /*
         * Default map position.
         */
        const defaultLatitude = 42.3555;
        const defaultLongitude = -71.0565;


        /*
         * Create map.
         */
        const map = L.map('map').setView(
            [
                defaultLatitude,
                defaultLongitude
            ],
            4
        );


        /*
         * OpenStreetMap.
         */
        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                maxZoom: 19,

                attribution:
                    '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);


        /*
         * Store valid markers.
         */
        const markers = [];


        /*
         * Add farmers to map.
         */
        farmers.forEach(function (farmer) {

            if (
                farmer.latitude !== null &&
                farmer.longitude !== null &&
                farmer.latitude !== '' &&
                farmer.longitude !== ''
            ) {

                const latitude =
                    parseFloat(farmer.latitude);

                const longitude =
                    parseFloat(farmer.longitude);


                if (
                    !isNaN(latitude) &&
                    !isNaN(longitude)
                ) {

                    const marker = L.marker([
                        latitude,
                        longitude
                    ]).addTo(map);


                    const stallName =
                        farmer.stall_name ||
                        'Farmer';


                    const address =
                        farmer.address ||
                        'Address not available';


                    marker.bindPopup(
                        '<strong>' +
                        escapeHtml(stallName) +
                        '</strong><br>' +
                        escapeHtml(address) +
                        '<br><br>' +
                        '<a href="farmer_details.php?id=' +
                        encodeURIComponent(farmer.id) +
                        '">' +
                        'View Details' +
                        '</a>'
                    );


                    markers.push(marker);

                }

            }

        });


        /*
         * Automatically fit map
         * around all farmers.
         */
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


        /*
         * Escape popup text.
         */
        function escapeHtml(value) {

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

        }


        /*
         * Fix Leaflet rendering.
         */
        setTimeout(function () {

            map.invalidateSize();

        }, 300);

    </script>

</body>

</html>