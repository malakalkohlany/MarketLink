<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$markets = [];

$sql = "SELECT id, name, address, latitude, longitude, operating_days,
               opening_time, closing_time
        FROM markets
        WHERE status = 'active'
        ORDER BY name ASC";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Markets - MarketLink</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

    <style>
        #map {
            width: 100%;
            height: 500px;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>Markets</h1>

        <p>Find nearby markets and view their locations.</p>

        <div id="map"></div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const markets = <?php echo json_encode($markets); ?>;

        const defaultLatitude = 42.3555;
        const defaultLongitude = -71.0565;

        const map = L.map('map').setView(
            [defaultLatitude, defaultLongitude],
            4
        );

        L.tileLayer(
            'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
            {
                maxZoom: 19,
                attribution:
                    '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);

        markets.forEach(function(market) {

            if (market.latitude !== null && market.longitude !== null) {

                const latitude = parseFloat(market.latitude);
                const longitude = parseFloat(market.longitude);

                const marker = L.marker([
                    latitude,
                    longitude
                ]).addTo(map);

                const directionsLink =
                    '<a href="#" onclick="getDirections(' +
                    latitude + ',' + longitude +
                    '); return false;">Get Directions</a>';

                marker.bindPopup(
                    '<b>' + market.name + '</b><br>' +
                    market.address + '<br><br>' +
                    directionsLink
                );
            }
        });

    function getDirections(destinationLatitude, destinationLongitude) {

    if (!navigator.geolocation) {
        alert('Location services are not supported by this browser.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {

            const userLatitude = position.coords.latitude;
            const userLongitude = position.coords.longitude;

            const directionsUrl =
                'https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=' +
                userLatitude + ',' + userLongitude + ';' +
                destinationLatitude + ',' + destinationLongitude;

            window.open(directionsUrl, '_blank');
        },
        function() {
            alert('Unable to get your location. Please allow location access.');
        }
    );
}
        
    </script>

</body>

</html>