// Farmer map
if (document.getElementById('map')) {
    const savedLatitude =
        window.farmerLocation?.latitude ?? 15.3694;

    const savedLongitude =
        window.farmerLocation?.longitude ?? 44.1910;

    const map = L.map('map').setView(
        [savedLatitude, savedLongitude],
        13
    );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    let marker = L.marker([
        savedLatitude,
        savedLongitude
    ]).addTo(map);

    map.on('click', function(event) {
        const latitude = event.latlng.lat;
        const longitude = event.latlng.lng;

        if (marker) {
            marker.setLatLng([
                latitude,
                longitude
            ]);
        } else {
            marker = L.marker([
                latitude,
                longitude
            ]).addTo(map);
        }

        document.getElementById('latitude').value =
            latitude.toFixed(8);

        document.getElementById('longitude').value =
            longitude.toFixed(8);
    });
}


// Admin market map
if (document.getElementById('market-map')) {
    const savedLatitude =
        window.marketLocation?.latitude ?? 15.3694;

    const savedLongitude =
        window.marketLocation?.longitude ?? 44.1910;

    const marketMap = L.map('market-map').setView(
        [savedLatitude, savedLongitude],
        13
    );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(marketMap);

    let marketMarker = L.marker([
        savedLatitude,
        savedLongitude
    ]).addTo(marketMap);

    marketMap.on('click', function(event) {
        const latitude = event.latlng.lat;
        const longitude = event.latlng.lng;

        marketMarker.setLatLng([
            latitude,
            longitude
        ]);

        document.getElementById('latitude').value =
            latitude.toFixed(8);

        document.getElementById('longitude').value =
            longitude.toFixed(8);
    });
}