function selectContactType(type) {
    const subject =
        document.getElementById('subject');

    const message =
        document.getElementById('message');

    subject.value = type;

    if (type === 'Question') {
        message.value =
            'Hello MarketLink team,\n\n' +
            'I have a question about: ';
    }
    else if (type === 'Report a Problem') {
        message.value =
            'Hello MarketLink team,\n\n' +
            'I would like to report a problem:\n\n';
    }
    else if (type === 'Market Information') {
        message.value =
            'Hello MarketLink team,\n\n' +
            'I would like to provide/update market information:\n\n';
    }
    else if (type === 'Feedback') {
        message.value =
            'Hello MarketLink team,\n\n' +
            'I would like to share the following feedback:\n\n';
    }

    message.focus();
}


// Temporary coordinates for testing the map
const marketLinkLatitude = 42.3555;
const marketLinkLongitude = -71.0565;

const map = L.map('map').setView(
    [marketLinkLatitude, marketLinkLongitude],
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

L.marker([
    marketLinkLatitude,
    marketLinkLongitude
])
    .addTo(map)
    .bindPopup(
        '<b>MarketLink</b><br>Boston, Massachusetts'
    )
    .openPopup();