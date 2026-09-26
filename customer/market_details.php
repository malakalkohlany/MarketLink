<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('customer');

$customerId = (int) getUserId();

$marketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($marketId <= 0) {
    header('Location: markets.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Toggle Favorite Market
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['toggle_favorite'])
) {
    $checkStmt = mysqli_prepare(
        $conn,
        "
        SELECT customer_id
        FROM favorite_markets
        WHERE customer_id = ?
          AND market_id = ?
        LIMIT 1
        "
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

    $exists = mysqli_stmt_num_rows($checkStmt) > 0;

    mysqli_stmt_close($checkStmt);


    /*
    |--------------------------------------------------------------------------
    | Remove Favorite
    |--------------------------------------------------------------------------
    */
    if ($exists) {

        $deleteStmt = mysqli_prepare(
            $conn,
            "
            DELETE FROM favorite_markets
            WHERE customer_id = ?
              AND market_id = ?
            "
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


    /*
    |--------------------------------------------------------------------------
    | Add Favorite
    |--------------------------------------------------------------------------
    */
    else {

        $insertStmt = mysqli_prepare(
            $conn,
            "
            INSERT INTO favorite_markets
            (
                customer_id,
                market_id,
                created_at
            )
            VALUES
            (?, ?, NOW())
            "
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


    /*
    |--------------------------------------------------------------------------
    | Return to Same Market
    |--------------------------------------------------------------------------
    */
    header(
        'Location: market-details.php?id=' . $marketId
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Market Details
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        name,
        description,
        address,
        latitude,
        longitude,
        opening_time,
        closing_time,
        operating_days,
        map_provider
    FROM markets
    WHERE id = ?
      AND status = 'active'
    LIMIT 1
    "
);

if (!$stmt) {
    die(
        'Market query prepare failed: '
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $marketId
);

if (!mysqli_stmt_execute($stmt)) {
    die(
        'Market query execute failed: '
        . mysqli_stmt_error($stmt)
    );
}

$result = mysqli_stmt_get_result($stmt);

$market = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$market) {
    header('Location: markets.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Check Favorite Status
|--------------------------------------------------------------------------
*/
$favoriteStmt = mysqli_prepare(
    $conn,
    "
    SELECT customer_id
    FROM favorite_markets
    WHERE customer_id = ?
      AND market_id = ?
    LIMIT 1
    "
);

if (!$favoriteStmt) {
    die(
        'Favorite status prepare failed: '
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $favoriteStmt,
    "ii",
    $customerId,
    $marketId
);

if (!mysqli_stmt_execute($favoriteStmt)) {
    die(
        'Favorite status execute failed: '
        . mysqli_stmt_error($favoriteStmt)
    );
}

mysqli_stmt_store_result($favoriteStmt);

$isFavorite =
    mysqli_stmt_num_rows($favoriteStmt) > 0;

mysqli_stmt_close($favoriteStmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($market['name']) ?> - MarketLink
    </title>


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
        | Market Details Page
        |--------------------------------------------------------------------------
        */

        .market-details-page {

            padding: 30px;

            box-sizing: border-box;
        }


        /*
        |--------------------------------------------------------------------------
        | Back Button
        |--------------------------------------------------------------------------
        */

        .market-back-link {

            display: inline-block;

            margin-bottom: 20px;

            color: #333;

            text-decoration: none;

            font-weight: 600;
        }


        .market-back-link:hover {

            text-decoration: underline;
        }


        /*
        |--------------------------------------------------------------------------
        | Details Card
        |--------------------------------------------------------------------------
        */

        .market-details-card {

            position: relative;

            background: #ffffff;

            border-radius: 14px;

            padding: 22px;

            margin-bottom: 20px;

            box-shadow:
                0 2px 10px
                rgba(0, 0, 0, 0.08);

            box-sizing: border-box;
        }


        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        .market-details-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 18px;
        }


        .market-details-header h1 {

            margin: 0;

            padding-right: 45px;

            font-size: 28px;

            color: #222;
        }


        /*
        |--------------------------------------------------------------------------
        | Favorite Button
        |--------------------------------------------------------------------------
        */

        .market-favorite-form {

            margin: 0;

            flex-shrink: 0;
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
        | Information
        |--------------------------------------------------------------------------
        */

        .market-info {

            margin-bottom: 8px;

            line-height: 1.5;

            color: #555;
        }


        .market-info strong {

            color: #222;
        }


        /*
        |--------------------------------------------------------------------------
        | Description
        |--------------------------------------------------------------------------
        */

        .market-description {

            margin-top: 20px;

            padding-top: 20px;

            border-top: 1px solid #eee;

            color: #555;

            line-height: 1.7;
        }


        .market-description strong {

            color: #222;

            font-size: 18px;
        }


        .market-description p {

            margin-bottom: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Map
        |--------------------------------------------------------------------------
        */

        #marketMap {

            width: 100%;

            height: 400px;

            border-radius: 14px;

            overflow: hidden;
        }


        /*
        |--------------------------------------------------------------------------
        | Leaflet Fix
        |--------------------------------------------------------------------------
        |
        | Keep Leaflet below navbar/sidebar.
        |
        */

        #marketMap .leaflet-pane {

            z-index: 1 !important;
        }


        #marketMap .leaflet-top,
        #marketMap .leaflet-bottom {

            z-index: 2 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 768px) {

            .market-details-page {

                padding: 20px;
            }


            .market-details-header {

                align-items: flex-start;
            }


            .market-details-header h1 {

                font-size: 24px;
            }


            #marketMap {

                height: 400px;
            }
        }

    </style>

</head>


<body>


<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">


    <div class="market-details-page">


        <!-- Back -->
        <a
            href="markets.php"
            class="market-back-link"
        >
            ← Back to Markets
        </a>


        <!-- =====================================================
             MARKET INFORMATION
             ===================================================== -->

        <div class="market-details-card">


            <div class="market-details-header">


                <h1>
                    <?= e($market['name']) ?>
                </h1>


                <!-- Favorite -->

                <form
                    method="POST"
                    action="market-details.php?id=<?= (int) $market['id'] ?>"
                    class="market-favorite-form"
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


            </div>


            <!-- Address -->

            <div class="market-info">

                <strong>
                    📍 Address:
                </strong>

                <?= e($market['address']) ?>

            </div>


            <!-- Opening -->

            <div class="market-info">

                <strong>
                    Opening:
                </strong>

                <?= e($market['opening_time']) ?>

            </div>


            <!-- Closing -->

            <div class="market-info">

                <strong>
                    Closing:
                </strong>

                <?= e($market['closing_time']) ?>

            </div>


            <!-- Operating Days -->

            <div class="market-info">

                <strong>
                    Operating Days:
                </strong>

                <?= e($market['operating_days']) ?>

            </div>


            <!-- Map Provider -->

            <?php if (!empty($market['map_provider'])): ?>

                <div class="market-info">

                    <strong>
                        Map Provider:
                    </strong>

                    <?= e($market['map_provider']) ?>

                </div>

            <?php endif; ?>


            <!-- Description -->

            <?php if (!empty($market['description'])): ?>

                <div class="market-description">

                    <strong>
                        Description
                    </strong>

                    <p>
                        <?= nl2br(e($market['description'])) ?>
                    </p>

                </div>

            <?php endif; ?>


        </div>


        <!-- =====================================================
             MARKET LOCATION
             ===================================================== -->

        <div class="market-details-card">


            <h2>
                Market Location
            </h2>


            <div id="marketMap"></div>


        </div>


    </div>


</main>


<!-- Leaflet JS -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


<script>

    const latitude =
        <?= (float) $market['latitude'] ?>;

    const longitude =
        <?= (float) $market['longitude'] ?>;


    const map =
        L.map('marketMap').setView(
            [latitude, longitude],
            15
        );


    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,

            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    L.marker(
        [latitude, longitude]
    )
    .addTo(map)
    .bindPopup(
        <?= json_encode($market['name']) ?>
    )
    .openPopup();


    /*
    |--------------------------------------------------------------------------
    | Fix Map Rendering
    |--------------------------------------------------------------------------
    */

    setTimeout(function () {

        map.invalidateSize();

    }, 300);

</script>


</body>

</html>

