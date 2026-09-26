<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us - MarketLink</title>

    <link
    rel="stylesheet"
    href="../assets/css/style.css"
    >
    <!-- Leaflet CSS -->
    <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        #map {
            width: 100%;
            height: 450px;
        }
    </style>
</head>

<body>

    <h1>Contact Us</h1>

    <p>
        If you have any questions or need assistance, please contact the
        MarketLink team.
    </p>

    <h2>Our Location</h2>

    <div id="map"></div>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // MarketLink location
        // Temporary coordinates for testing the map.
        const marketLinkLatitude = 42.3555;
        const marketLinkLongitude = -71.0565;

        const map = L.map('map').setView(
            [marketLinkLatitude, marketLinkLongitude],
            4
        );

        // OpenStreetMap tiles
        L.tileLayer(
            'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
            {
                maxZoom: 19,
                attribution:
                    '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);

        // MarketLink location marker
        L.marker([
            marketLinkLatitude,
            marketLinkLongitude
        ])
        .addTo(map)
        .bindPopup('<b>MarketLink</b><br>Boston, Massachusetts')
        .openPopup();
    </script>

</body>
</html>