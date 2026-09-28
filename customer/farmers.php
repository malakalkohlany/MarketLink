<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_CUSTOMER);

$customerId = (int) getUserId();

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['toggle_favorite'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect('farmers.php');
    }

    $farmerId = filter_input(
        INPUT_POST,
        'farmer_id',
        FILTER_VALIDATE_INT
    );

    if (!$farmerId || !$customerId) {
        redirect('farmers.php');
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
        die('Favorite check prepare failed.');
    }

    mysqli_stmt_bind_param(
        $checkStmt,
        "ii",
        $customerId,
        $farmerId
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
            "DELETE FROM favorite_farmers
             WHERE customer_id = ?
               AND farmer_id = ?"
        );

        if (!$deleteStmt) {
            die('Favorite delete prepare failed.');
        }

        mysqli_stmt_bind_param(
            $deleteStmt,
            "ii",
            $customerId,
            $farmerId
        );

        if (!mysqli_stmt_execute($deleteStmt)) {
            die('Favorite delete failed.');
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
            die('Favorite insert prepare failed.');
        }

        mysqli_stmt_bind_param(
            $insertStmt,
            "ii",
            $customerId,
            $farmerId
        );

        if (!mysqli_stmt_execute($insertStmt)) {
            die('Favorite insert failed.');
        }

        mysqli_stmt_close($insertStmt);
    }

    redirect('farmers.php');
}

$favoriteFarmers = [];

$favoriteStmt = mysqli_prepare(
    $conn,
    "SELECT farmer_id
     FROM favorite_farmers
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
    $favoriteFarmerId
);

while (mysqli_stmt_fetch($favoriteStmt)) {
    $favoriteFarmers[] = (int) $favoriteFarmerId;
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

$farmerCount = count($farmers);
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
                CUSTOMER / LOCAL FARMERS
            </span>

            <h1>
                Meet the people<br>
                behind <em>the harvest.</em>
            </h1>

            <p>
                Discover approved local farmers, explore their markets,
                learn where they grow, and connect with the people bringing
                fresh produce to your community.
            </p>

        </div>

        <div class="customer-page-hero-mark">
            02
        </div>

    </div>

    <section class="customer-farmers-intro">

        <div class="customer-shopping-note">

            <span class="customer-shopping-note-icon">
                <i class="fa-solid fa-location-dot"></i>
            </span>

            <div>
                <strong>
                    Find farmers around you.
                </strong>

                <span>
                    Use the map to explore local farmers or allow location
                    access to sort farmers by distance from you.
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
                    Local <em>farmers.</em>
                </h2>
            </div>

            <span class="customer-record-count">
                <?= $farmerCount ?>
                farmer<?= $farmerCount !== 1 ? 's' : '' ?>
                available
            </span>

        </div>

        <div class="customer-farmer-location-tools">

            <div class="customer-farmer-search">

                <label for="farmerSearch">
                    Search Farmers
                </label>

                <div class="customer-farmer-input-wrap">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="farmerSearch"
                        placeholder="Search by stall name, address or description"
                        autocomplete="off"
                    >

                </div>

            </div>

            <div class="customer-farmer-location-actions">

                <button
                    type="button"
                    id="findNearbyFarmers"
                    class="customer-farmer-location-button"
                >
                    <i class="fa-solid fa-location-crosshairs"></i>
                    Find Farmers Near Me
                </button>

                <button
                    type="button"
                    id="showAllFarmers"
                    class="customer-farmer-show-all"
                    style="display: none;"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Show All Farmers
                </button>

                <span
                    id="farmerLocationStatus"
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
                    Browse <em>farmers.</em>
                </h2>
            </div>

            <span
                class="customer-record-count"
                id="farmerVisibleCount"
            >
                <?= $farmerCount ?>
                result<?= $farmerCount !== 1 ? 's' : '' ?>
            </span>

        </div>

        <?php if (!empty($farmers)): ?>

            <div
                class="customer-farmers-no-match"
                id="farmerNoMatch"
            >
                <span class="customer-farmers-no-match-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>

                <strong>
                    No farmers found.
                </strong>

                <span>
                    Try a different search term.
                </span>
            </div>

            <div
                class="customer-farmers-grid"
                id="farmersGrid"
            >

                <?php foreach ($farmers as $farmer): ?>

                    <?php
                    $farmerId = (int) $farmer['id'];

                    $isFavorite = in_array(
                        $farmerId,
                        $favoriteFarmers,
                        true
                    );
                    ?>

                    <article
                        class="customer-farmer-card"
                        data-farmer-id="<?= $farmerId ?>"
                    >

                        <div class="customer-farmer-card-top">

                            <span class="customer-farmer-card-number">
                                <?= str_pad(
                                    (string) ($farmerId),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span>

                            <form
                                method="POST"
                                action="farmers.php"
                                class="customer-farmer-favorite-form"
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
                                LOCAL FARMER
                            </span>

                            <h3>
                                <?= e($farmer['stall_name']) ?>
                            </h3>

                            <?php if (!empty($farmer['contact_person'])): ?>

                                <div class="customer-farmer-meta">

                                    <i class="fa-solid fa-user"></i>

                                    <span>
                                        <?= e($farmer['contact_person']) ?>
                                    </span>

                                </div>

                            <?php endif; ?>

                            <?php if (!empty($farmer['address'])): ?>

                                <div class="customer-farmer-meta">

                                    <i class="fa-solid fa-location-dot"></i>

                                    <span>
                                        <?= e($farmer['address']) ?>
                                    </span>

                                </div>

                            <?php endif; ?>

                            <?php if (!empty($farmer['description'])): ?>

                                <p class="customer-farmer-description">
                                    <?= e($farmer['description']) ?>
                                </p>

                            <?php else: ?>

                                <p class="customer-farmer-description customer-farmer-description-empty">
                                    Local farmer and market producer.
                                </p>

                            <?php endif; ?>

                            <div class="customer-farmer-card-footer">

                                <a
                                    href="farmer_details.php?id=<?= $farmerId ?>"
                                    class="customer-farmer-details"
                                >
                                    View Farmer
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <div
                class="farmer-pagination"
                id="farmerPagination"
            ></div>

        <?php else: ?>

            <div class="customer-farmers-empty">

                <span class="customer-farmers-empty-mark">
                    <i class="fa-solid fa-seedling"></i>
                </span>

                <strong>
                    No farmers are currently available.
                </strong>

                <span>
                    Approved local farmers will appear here when they become
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
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    const farmers = <?= json_encode(
        $farmers,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ); ?>;

    const farmerSearch =
        document.getElementById('farmerSearch');

    const farmersGrid =
        document.getElementById('farmersGrid');

    const farmerPagination =
        document.getElementById('farmerPagination');

    const farmerNoMatch =
        document.getElementById('farmerNoMatch');

    const farmerVisibleCount =
        document.getElementById('farmerVisibleCount');

    const findNearbyFarmers =
        document.getElementById('findNearbyFarmers');

    const showAllFarmers =
        document.getElementById('showAllFarmers');

    const farmerLocationStatus =
        document.getElementById('farmerLocationStatus');

    const cards = farmersGrid
        ? Array.from(
            farmersGrid.querySelectorAll('.customer-farmer-card')
        )
        : [];

    const farmersPerPage = 8;

    let currentPage = 1;
    let filteredCards = [...cards];

    function updateVisibleCount() {
        const count = filteredCards.length;

        farmerVisibleCount.textContent =
            `${count} result${count !== 1 ? 's' : ''}`;
    }

    function renderPagination() {
        if (!farmerPagination) {
            return;
        }

        const totalPages =
            Math.ceil(filteredCards.length / farmersPerPage);

        farmerPagination.innerHTML = '';

        if (totalPages <= 1) {
            farmerPagination.style.display = 'none';
            return;
        }

        farmerPagination.style.display = 'flex';

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
                    renderFarmers();
                }
            }
        );

        farmerPagination.appendChild(
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
                    renderFarmers();
                }
            );

            farmerPagination.appendChild(
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
                    renderFarmers();
                }
            }
        );

        farmerPagination.appendChild(
            nextButton
        );
    }

    function renderFarmers() {
        if (!farmersGrid) {
            return;
        }

        cards.forEach(function (card) {
            card.style.display = 'none';
        });

        const totalPages =
            Math.ceil(filteredCards.length / farmersPerPage);

        if (
            totalPages > 0 &&
            currentPage > totalPages
        ) {
            currentPage = totalPages;
        }

        const start =
            (currentPage - 1) * farmersPerPage;

        const end =
            start + farmersPerPage;

        filteredCards
            .slice(start, end)
            .forEach(function (card) {
                card.style.display = '';
                farmersGrid.appendChild(card);
            });

        if (filteredCards.length === 0) {
            farmerNoMatch.style.display = 'flex';
        } else {
            farmerNoMatch.style.display = 'none';
        }

        updateVisibleCount();
        renderPagination();
    }

    function applyFarmerSearch() {
        const searchTerm =
            farmerSearch.value
                .trim()
                .toLowerCase();

        filteredCards = cards.filter(
            function (card) {
                const farmerName =
                    card.querySelector('h3')
                        ?.textContent
                        .trim()
                        .toLowerCase() || '';

                const cardText =
                    card.textContent
                        .trim()
                        .toLowerCase();

                return (
                    searchTerm === '' ||
                    farmerName.includes(searchTerm) ||
                    cardText.includes(searchTerm)
                );
            }
        );

        currentPage = 1;
        renderFarmers();
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

    function sortFarmersByLocation(
        userLatitude,
        userLongitude
    ) {
        cards.forEach(function (card) {
            const farmerId =
                parseInt(
                    card.dataset.farmerId
                );

            const farmer =
                farmers.find(
                    function (item) {
                        return (
                            parseInt(item.id) ===
                            farmerId
                        );
                    }
                );

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

        filteredCards = cards.filter(
            function (card) {
                const searchTerm =
                    farmerSearch.value
                        .trim()
                        .toLowerCase();

                const farmerName =
                    card.querySelector('h3')
                        ?.textContent
                        .trim()
                        .toLowerCase() || '';

                const cardText =
                    card.textContent
                        .trim()
                        .toLowerCase();

                return (
                    searchTerm === '' ||
                    farmerName.includes(searchTerm) ||
                    cardText.includes(searchTerm)
                );
            }
        );

        currentPage = 1;
        renderFarmers();
    }

    findNearbyFarmers.addEventListener(
        'click',
        function () {
            if (!navigator.geolocation) {
                farmerLocationStatus.textContent =
                    'Location is not supported by this browser.';

                farmerLocationStatus.style.display =
                    'inline-flex';

                return;
            }

            farmerLocationStatus.textContent =
                'Getting your location...';

            farmerLocationStatus.style.display =
                'inline-flex';

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
                        'inline-flex';
                },
                function () {
                    farmerLocationStatus.textContent =
                        'Unable to get your location.';

                    farmerLocationStatus.style.display =
                        'inline-flex';
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
            <div class="customer-farmer-popup">
                <strong>
                    ${escapeHtml(
                        farmer.stall_name
                    )}
                </strong>
                <span>
                    ${escapeHtml(
                        farmer.address || ''
                    )}
                </span>
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

    renderFarmers();

    setTimeout(function () {
        map.invalidateSize();
    }, 300);
</script>

</body>
</html>