<?php

require_once '../config/database.php';

$farmers = [];

$sql = "SELECT id, stall_name, address, latitude, longitude
        FROM farmers
        WHERE approval_status = 'approved'
        ORDER BY stall_name ASC";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Farmers - MarketLink</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        #map {
            width: 100%;
            height: 500px;
        }
    </style>
</head>

<body>

    <h1>Farmers</h1>

    <p>Find farmers and view their stall locations.</p>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const farmers = <?php echo json_encode($farmers); ?>;

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

        farmers.forEach(function(farmer) {

            if (farmer.latitude !== null && farmer.longitude !== null) {

                const latitude = parseFloat(farmer.latitude);
                const longitude = parseFloat(farmer.longitude);

                const marker = L.marker([
                    latitude,
                    longitude
                ]).addTo(map);

                marker.bindPopup(
                    '<b>' + farmer.stall_name + '</b><br>' +
                    (farmer.address || 'Address not available')
                );
            }
        });
    </script>

</body>

</html>