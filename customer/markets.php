<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['toggle_favorite'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect('markets.php');
    }

    $marketId = filter_input(
        INPUT_POST,
        'market_id',
        FILTER_VALIDATE_INT
    );

    if (!$marketId || !$customerId) {
        redirect('markets.php');
    }

    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT customer_id
         FROM favorite_markets
         WHERE customer_id = ?
           AND market_id = ?
         LIMIT 1"
    );

    if (!$checkStmt) {
        die('Favorite check prepare failed.');
    }

    mysqli_stmt_bind_param(
        $checkStmt,
        "ii",
        $customerId,
        $marketId
    );

    if (!mysqli_stmt_execute($checkStmt)) {
        die('Favorite check execute failed.');
    }

    mysqli_stmt_store_result($checkStmt);

    $exists = mysqli_stmt_num_rows($checkStmt) > 0;

    mysqli_stmt_close($checkStmt);

    if ($exists) {
        $deleteStmt = mysqli_prepare(
            $conn,
            "DELETE FROM favorite_markets
             WHERE customer_id = ?
               AND market_id = ?"
        );

        if (!$deleteStmt) {
            die('Favorite delete prepare failed.');
        }

        mysqli_stmt_bind_param(
            $deleteStmt,
            "ii",
            $customerId,
            $marketId
        );

        if (!mysqli_stmt_execute($deleteStmt)) {
            die('Favorite delete failed.');
        }

        mysqli_stmt_close($deleteStmt);
    } else {
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
            die('Favorite insert prepare failed.');
        }

        mysqli_stmt_bind_param(
            $insertStmt,
            "ii",
            $customerId,
            $marketId
        );

        if (!mysqli_stmt_execute($insertStmt)) {
            die('Favorite insert failed.');
        }

        mysqli_stmt_close($insertStmt);
    }

    redirect('markets.php');
}

$favoriteMarkets = [];

$favoriteStmt = mysqli_prepare(
    $conn,
    "SELECT market_id
     FROM favorite_markets
     WHERE customer_id = ?"
);

if (!$favoriteStmt) {
    die('Favorite list prepare failed.');
}

mysqli_stmt_bind_param(
    $favoriteStmt,
    "i",
    $customerId
);

if (!mysqli_stmt_execute($favoriteStmt)) {
    die('Favorite list execute failed.');
}

mysqli_stmt_bind_result(
    $favoriteStmt,
    $favoriteMarketId
);

while (mysqli_stmt_fetch($favoriteStmt)) {
    $favoriteMarkets[] = (int) $favoriteMarketId;
}

mysqli_stmt_close($favoriteStmt);

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

$marketCount = count($markets);

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
        href="../assets/css/customer.css"
    >
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content customer-farmers-page">

    <div class="customer-page-hero customer-farmers-hero">
        <div class="customer-page-hero-copy">
            <span class="eyebrow">
                CUSTOMER / LOCAL MARKETS
            </span>

            <h1>
                Find where
                <br>
                freshness <em>meets you.</em>
            </h1>

            <p>
                Explore active local markets, discover where fresh produce
                is sold, and find the markets closest to you.
            </p>
        </div>

        <div class="customer-page-hero-mark">
            01
        </div>
    </div>

    <section class="customer-farmers-intro">
        <div class="customer-shopping-note">
            <span class="customer-shopping-note-icon">
                <i class="fa-solid fa-location-dot"></i>
            </span>

            <div>
                <strong>
                    Find markets around you.
                </strong>

                <span>
                    Use the map to explore local markets or allow location
                    access to sort markets by distance from you.
                </span>
            </div>
        </div>
    </section>

    <section class="customer-farmers-map-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    01 / EXPLORE
                </span>

                <h2>
                    Local <em>markets.</em>
                </h2>
            </div>

            <span class="customer-record-count">
                <?= $marketCount ?>
                market<?= $marketCount !== 1 ? 's' : '' ?>
                available
            </span>
        </div>

        <div class="customer-farmer-location-tools">

            <div class="customer-farmer-search">
                <label for="marketSearch">
                    Search Markets
                </label>

                <div class="customer-farmer-input-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="marketSearch"
                        placeholder="Search by market name, address or operating day"
                        autocomplete="off"
                    >
                </div>
            </div>

            <div class="customer-farmer-search">
                <label for="marketDayFilter">
                    Operating Day
                </label>

                <div class="customer-day-filter">
                    <i class="fa-solid fa-calendar-days"></i>

                    <select id="marketDayFilter">
                        <option value="">All Days</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
            </div>

            <div class="customer-farmer-location-actions">
                <button
                    type="button"
                    id="findNearbyMarkets"
                    class="customer-farmer-location-button"
                >
                    <i class="fa-solid fa-location-crosshairs"></i>
                    Find Markets Near Me
                </button>

                <button
                    type="button"
                    id="showAllMarkets"
                    class="customer-farmer-show-all"
                    style="display: none;"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Show All Markets
                </button>

                <span
                    id="marketLocationStatus"
                    class="customer-farmer-location-status"
                    style="display: none;"
                ></span>
            </div>

        </div>

        <div class="customer-farmers-map-card">
            <div id="map"></div>
        </div>

    </section>

    <section class="customer-farmers-list-section">

        <div class="customer-section-heading">
            <div>
                <span class="customer-section-number">
                    02 / DISCOVER
                </span>

                <h2>
                    Browse <em>markets.</em>
                </h2>
            </div>

            <span
                class="customer-record-count"
                id="marketVisibleCount"
            >
                <?= $marketCount ?>
                result<?= $marketCount !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if (!empty($markets)): ?>

            <div
                class="customer-farmers-no-match"
                id="marketNoMatch"
            >
                <span class="customer-farmers-no-match-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>

                <strong>
                    No markets found.
                </strong>

                <span>
                    Try a different search term or operating day.
                </span>
            </div>

            <div
                class="customer-farmers-grid"
                id="marketsGrid"
            >

                <?php foreach ($markets as $market): ?>

                    <?php

                    $marketId = (int) $market['id'];

                    $isFavorite = in_array(
                        $marketId,
                        $favoriteMarkets,
                        true
                    );

                    ?>

                    <article
                        class="customer-farmer-card"
                        data-market-id="<?= $marketId ?>"
                    >

                        <div class="customer-farmer-card-top">

                            <span class="customer-farmer-card-number">
                                <?= str_pad(
                                    (string) $marketId,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span>

                            <form
                                method="POST"
                                action="markets.php"
                                class="customer-farmer-favorite-form"
                            >

                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="market_id"
                                    value="<?= $marketId ?>"
                                >

                                <button
                                    type="submit"
                                    name="toggle_favorite"
                                    class="customer-farmer-favorite <?= $isFavorite ? 'is-favorite' : '' ?>"
                                    title="<?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                    aria-label="<?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                >
                                    <i class="<?= $isFavorite ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                                </button>

                            </form>

                        </div>

                        <div class="customer-farmer-card-content">

                            <span class="customer-farmer-label">
                                LOCAL MARKET
                            </span>

                            <h3>
                                <?= e($market['name']) ?>
                            </h3>

                            <?php if (!empty($market['address'])): ?>

                                <div class="customer-farmer-meta">
                                    <i class="fa-solid fa-location-dot"></i>

                                    <span>
                                        <?= e($market['address']) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <?php if (!empty($market['operating_days'])): ?>

                                <div class="customer-farmer-meta">
                                    <i class="fa-solid fa-calendar-days"></i>

                                    <span>
                                        <?= e($market['operating_days']) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <?php if (
                                !empty($market['opening_time'])
                                && !empty($market['closing_time'])
                            ): ?>

                                <div class="customer-farmer-meta">
                                    <i class="fa-solid fa-clock"></i>

                                    <span>
                                        <?= e(date('g:i A', strtotime($market['opening_time']))) ?>
                                        -
                                        <?= e(date('g:i A', strtotime($market['closing_time']))) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <p class="customer-farmer-description">
                                Visit this local market to explore fresh
                                produce from nearby farmers.
                            </p>

                            <div class="customer-farmer-card-footer">

                                <a
                                    href="market_details.php?id=<?= $marketId ?>"
                                    class="customer-farmer-details"
                                >
                                    View Market
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <div
                class="farmer-pagination"
                id="marketPagination"
            ></div>

        <?php else: ?>

            <div class="customer-farmers-empty">

                <span class="customer-farmers-empty-mark">
                    <i class="fa-solid fa-store"></i>
                </span>

                <strong>
                    No markets are currently available.
                </strong>

                <span>
                    Active local markets will appear here when they become
                    available.
                </span>

            </div>

        <?php endif; ?>

    </section>

</main>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>

<script>

const map = L.map('map').setView(
    [42.3555, -71.0565],
    4
);

L.tileLayer(
    'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
    {
        maxZoom: 20,
        attribution: '&copy; OpenStreetMap contributors'
    }
).addTo(map);

const markets = <?= json_encode(
    $markets,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;

const marketSearch =
    document.getElementById('marketSearch');

const marketDayFilter =
    document.getElementById('marketDayFilter');

const marketsGrid =
    document.getElementById('marketsGrid');

const marketPagination =
    document.getElementById('marketPagination');

const marketNoMatch =
    document.getElementById('marketNoMatch');

const marketVisibleCount =
    document.getElementById('marketVisibleCount');

const findNearbyMarkets =
    document.getElementById('findNearbyMarkets');

const showAllMarkets =
    document.getElementById('showAllMarkets');

const marketLocationStatus =
    document.getElementById('marketLocationStatus');

const cards = marketsGrid
    ? Array.from(
        marketsGrid.querySelectorAll('.customer-farmer-card')
    )
    : [];

const marketsPerPage = 8;

let currentPage = 1;

let filteredCards = [...cards];

function updateVisibleCount() {
    const count = filteredCards.length;

    marketVisibleCount.textContent =
        `${count} result${count !== 1 ? 's' : ''}`;
}

function renderPagination() {
    if (!marketPagination) {
        return;
    }

    const totalPages =
        Math.ceil(filteredCards.length / marketsPerPage);

    marketPagination.innerHTML = '';

    if (totalPages <= 1) {
        marketPagination.style.display = 'none';
        return;
    }

    marketPagination.style.display = 'flex';

    const previousButton =
        document.createElement('button');

    previousButton.type = 'button';

    previousButton.className =
        'farmer-pagination-button farmer-pagination-arrow';

    previousButton.innerHTML =
        '<i class="fa-solid fa-arrow-left"></i> Previous';

    previousButton.disabled =
        currentPage === 1;

    previousButton.addEventListener(
        'click',
        function () {
            if (currentPage > 1) {
                currentPage--;
                renderMarkets();
            }
        }
    );

    marketPagination.appendChild(
        previousButton
    );

    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {

        const pageButton =
            document.createElement('button');

        pageButton.type = 'button';

        pageButton.className =
            'farmer-pagination-button';

        if (page === currentPage) {
            pageButton.classList.add('active');
        }

        pageButton.textContent = page;

        pageButton.addEventListener(
            'click',
            function () {
                currentPage = page;
                renderMarkets();
            }
        );

        marketPagination.appendChild(
            pageButton
        );
    }

    const nextButton =
        document.createElement('button');

    nextButton.type = 'button';

    nextButton.className =
        'farmer-pagination-button farmer-pagination-arrow';

    nextButton.innerHTML =
        'Next <i class="fa-solid fa-arrow-right"></i>';

    nextButton.disabled =
        currentPage === totalPages;

    nextButton.addEventListener(
        'click',
        function () {
            if (currentPage < totalPages) {
                currentPage++;
                renderMarkets();
            }
        }
    );

    marketPagination.appendChild(
        nextButton
    );
}

function renderMarkets() {
    if (!marketsGrid) {
        return;
    }

    cards.forEach(function (card) {
        card.style.display = 'none';
    });

    const totalPages =
        Math.ceil(filteredCards.length / marketsPerPage);

    if (
        totalPages > 0 &&
        currentPage > totalPages
    ) {
        currentPage = totalPages;
    }

    const start =
        (currentPage - 1) * marketsPerPage;

    const end =
        start + marketsPerPage;

    filteredCards
        .slice(start, end)
        .forEach(function (card) {
            card.style.display = '';
            marketsGrid.appendChild(card);
        });

    if (filteredCards.length === 0) {
        marketNoMatch.style.display = 'flex';
    } else {
        marketNoMatch.style.display = 'none';
    }

    updateVisibleCount();

    renderPagination();
}

function applyMarketFilters() {
    const searchTerm =
        marketSearch.value
            .trim()
            .toLowerCase();

    const selectedDay =
        marketDayFilter.value
            .trim()
            .toLowerCase();

    filteredCards = cards.filter(
        function (card) {

            const marketName =
                card.querySelector('h3')
                    ?.textContent
                    .trim()
                    .toLowerCase() || '';

            const cardText =
                card.textContent
                    .trim()
                    .toLowerCase();

            const matchesSearch =
                searchTerm === '' ||
                marketName.includes(searchTerm) ||
                cardText.includes(searchTerm);

            const matchesDay =
                selectedDay === '' ||
                cardText.includes(selectedDay);

            return matchesSearch && matchesDay;
        }
    );

    currentPage = 1;

    renderMarkets();
}

marketSearch.addEventListener(
    'input',
    applyMarketFilters
);

marketDayFilter.addEventListener(
    'change',
    applyMarketFilters
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
        2 *
        Math.atan2(
            Math.sqrt(a),
            Math.sqrt(1 - a)
        );

    return R * c;
}

function sortMarketsByLocation(
    userLatitude,
    userLongitude
) {

    cards.forEach(function (card) {

        const marketId =
            parseInt(
                card.dataset.marketId
            );

        const market =
            markets.find(
                function (item) {
                    return (
                        parseInt(item.id) ===
                        marketId
                    );
                }
            );

        if (
            market &&
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

                card.dataset.distance =
                    calculateDistance(
                        userLatitude,
                        userLongitude,
                        marketLatitude,
                        marketLongitude
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

    const searchTerm =
        marketSearch.value
            .trim()
            .toLowerCase();

    const selectedDay =
        marketDayFilter.value
            .trim()
            .toLowerCase();

    filteredCards = cards.filter(
        function (card) {

            const marketName =
                card.querySelector('h3')
                    ?.textContent
                    .trim()
                    .toLowerCase() || '';

            const cardText =
                card.textContent
                    .trim()
                    .toLowerCase();

            const matchesSearch =
                searchTerm === '' ||
                marketName.includes(searchTerm) ||
                cardText.includes(searchTerm);

            const matchesDay =
                selectedDay === '' ||
                cardText.includes(selectedDay);

            return matchesSearch && matchesDay;
        }
    );

    currentPage = 1;

    renderMarkets();
}

findNearbyMarkets.addEventListener(
    'click',
    function () {

        if (!navigator.geolocation) {

            marketLocationStatus.textContent =
                'Location is not supported by this browser.';

            marketLocationStatus.style.display =
                'inline-flex';

            return;
        }

        marketLocationStatus.textContent =
            'Getting your location...';

        marketLocationStatus.style.display =
            'inline-flex';

        navigator.geolocation.getCurrentPosition(

            function (position) {

                const userLatitude =
                    position.coords.latitude;

                const userLongitude =
                    position.coords.longitude;

                sortMarketsByLocation(
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

                marketLocationStatus.textContent =
                    'Markets sorted by distance from your location.';

                showAllMarkets.style.display =
                    'inline-flex';

            },

            function () {

                marketLocationStatus.textContent =
                    'Unable to get your location.';

                marketLocationStatus.style.display =
                    'inline-flex';

            }
        );
    }
);

showAllMarkets.addEventListener(
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

markets.forEach(function (market) {

    const latitude =
        parseFloat(
            market.latitude
        );

    const longitude =
        parseFloat(
            market.longitude
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
        <div class="customer-farmer-popup">
            <strong>
                ${escapeHtml(
                    market.name
                )}
            </strong>

            <span>
                ${escapeHtml(
                    market.address || ''
                )}
            </span>

            <a
                href="market_details.php?id=${market.id}"
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

renderMarkets();

setTimeout(
    function () {
        map.invalidateSize();
    },
    300
);

</script>

</body>
</html>