<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| Form Defaults
|--------------------------------------------------------------------------
*/

$name = '';
$description = '';
$address = '';
$latitude = '';
$longitude = '';
$openingTime = '';
$closingTime = '';
$operatingDays = [];
$mapProvider = 'OpenStreetMap';
$status = 'active';
$errorMessage = '';


/*
|--------------------------------------------------------------------------
| Allowed Values
|--------------------------------------------------------------------------
*/

$allowedDays = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday'
];

$allowedMapProviders = [
    'OpenStreetMap',
    'Google Maps'
];

$allowedStatuses = [
    'active',
    'inactive'
];


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($submittedToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errorMessage = 'Invalid security token. Please try again.';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Form Values
    |--------------------------------------------------------------------------
    */

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $openingTime = trim($_POST['opening_time'] ?? '');
    $closingTime = trim($_POST['closing_time'] ?? '');
    $operatingDays = $_POST['operating_days'] ?? [];
    $mapProvider = $_POST['map_provider'] ?? 'OpenStreetMap';
    $status = $_POST['status'] ?? 'active';


    /*
    |--------------------------------------------------------------------------
    | Normalize Operating Days
    |--------------------------------------------------------------------------
    */

    if (!is_array($operatingDays)) {
        $operatingDays = [];
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($errorMessage === '') {

        // Name
        if ($name === '') {

            $errorMessage = 'Market name is required.';

        } elseif (mb_strlen($name) > 150) {

            $errorMessage = 'Market name cannot exceed 150 characters.';


        // Description
        } elseif (mb_strlen($description) > 1000) {

            $errorMessage = 'Description cannot exceed 1000 characters.';


        // Address
        } elseif ($address === '') {

            $errorMessage = 'Address is required.';

        } elseif (mb_strlen($address) > 255) {

            $errorMessage = 'Address cannot exceed 255 characters.';


        // Latitude
        } elseif (
            $latitude !== '' &&
            (
                !is_numeric($latitude) ||
                (float) $latitude < -90 ||
                (float) $latitude > 90
            )
        ) {

            $errorMessage = 'Please enter a valid latitude between -90 and 90.';


        // Longitude
        } elseif (
            $longitude !== '' &&
            (
                !is_numeric($longitude) ||
                (float) $longitude < -180 ||
                (float) $longitude > 180
            )
        ) {

            $errorMessage = 'Please enter a valid longitude between -180 and 180.';


        // Opening time
        } elseif (
            $openingTime !== '' &&
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $openingTime
            )
        ) {

            $errorMessage = 'Please enter a valid opening time.';


        // Closing time
        } elseif (
            $closingTime !== '' &&
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $closingTime
            )
        ) {

            $errorMessage = 'Please enter a valid closing time.';


        // Opening/closing relationship
        } elseif (
            $openingTime !== '' &&
            $closingTime !== '' &&
            $closingTime <= $openingTime
        ) {

            $errorMessage = 'Closing time must be later than opening time.';


        // Map provider
        } elseif (
            !in_array($mapProvider, $allowedMapProviders, true)
        ) {

            $errorMessage = 'Invalid map provider.';


        // Status
        } elseif (
            !in_array($status, $allowedStatuses, true)
        ) {

            $errorMessage = 'Invalid market status.';


        // Operating days must exist
        } elseif (empty($operatingDays)) {

            $errorMessage = 'Please select at least one operating day.';


        // Validate operating days
        } else {

            foreach ($operatingDays as $day) {

                if (!in_array($day, $allowedDays, true)) {
                    $errorMessage = 'Invalid operating day.';
                    break;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Operating Days Length
        |--------------------------------------------------------------------------
        */

        if (
            $errorMessage === '' &&
            mb_strlen(implode(', ', $operatingDays)) > 100
        ) {
            $errorMessage = 'The selected operating days are too long.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Insert Market
    |--------------------------------------------------------------------------
    */

    if ($errorMessage === '') {

        /*
        |--------------------------------------------------------------------------
        | Convert Optional Values to NULL
        |--------------------------------------------------------------------------
        */

        $latitudeValue = $latitude !== ''
            ? (float) $latitude
            : null;

        $longitudeValue = $longitude !== ''
            ? (float) $longitude
            : null;

        $openingTimeValue = $openingTime !== ''
            ? $openingTime
            : null;

        $closingTimeValue = $closingTime !== ''
            ? $closingTime
            : null;

        $operatingDaysValue = !empty($operatingDays)
            ? implode(', ', $operatingDays)
            : null;


        try {

            /*
            |--------------------------------------------------------------------------
            | Check For Duplicate Market
            |--------------------------------------------------------------------------
            */

            $duplicateStmt = $conn->prepare("
                SELECT id
                FROM markets
                WHERE name = ?
                  AND address = ?
                LIMIT 1
            ");

            $duplicateStmt->bind_param(
                "ss",
                $name,
                $address
            );

            $duplicateStmt->execute();

            $duplicateResult = $duplicateStmt->get_result();

            if ($duplicateResult->num_rows > 0) {

                $duplicateStmt->close();

                $errorMessage = 'A market with the same name and address already exists.';

            } else {

                $duplicateStmt->close();


                /*
                |--------------------------------------------------------------------------
                | Insert Market
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    INSERT INTO markets (
                        name,
                        description,
                        address,
                        latitude,
                        longitude,
                        opening_time,
                        closing_time,
                        operating_days,
                        map_provider,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                /*
                |--------------------------------------------------------------------------
                | MySQLi Binding
                |--------------------------------------------------------------------------
                |
                | We bind latitude/longitude as doubles.
                | Empty values are represented as NULL.
                |
                */

                $stmt->bind_param(
                    "sssddsssss",
                    $name,
                    $description,
                    $address,
                    $latitudeValue,
                    $longitudeValue,
                    $openingTimeValue,
                    $closingTimeValue,
                    $operatingDaysValue,
                    $mapProvider,
                    $status
                );

                $stmt->execute();

                $stmt->close();


                /*
                |--------------------------------------------------------------------------
                | Regenerate CSRF Token
                |--------------------------------------------------------------------------
                */

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));


                /*
                |--------------------------------------------------------------------------
                | Success
                |--------------------------------------------------------------------------
                */

                $_SESSION['success_message'] = 'Market added successfully.';

                header('Location: markets.php');
                exit;
            }

        } catch (mysqli_sql_exception $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Database Error
            |--------------------------------------------------------------------------
            */

            error_log(
                'MarketLink - Add Market Error: ' .
                $e->getMessage()
            );

            $errorMessage = 'Unable to add the market. Please try again.';
        }
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

    <title>Add Market - MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>

<body>


    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">

    <div class="page-container">

        <div class="page-header">

            <div>

                <h1>Add Market</h1>

                <p>
                    Add a new market location to MarketLink.
                </p>

            </div>

        </div>


        <?php if ($errorMessage !== ''): ?>

            <div class="alert alert-error">

                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            class="form-container"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $csrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >


            <!-- Market Name -->

            <div class="form-group">

                <label for="name">
                    Market Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    required
                    value="<?= htmlspecialchars(
                        $name,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                    placeholder="Enter market name"
                >

            </div>


            <!-- Description -->

            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="1000"
                    placeholder="Describe the market"
                ><?= htmlspecialchars(
                    $description,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?></textarea>

            </div>


            <!-- Address -->

            <div class="form-group">

                <label for="address">
                    Address
                </label>

                <input
                    type="text"
                    id="address"
                    name="address"
                    maxlength="255"
                    required
                    value="<?= htmlspecialchars(
                        $address,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                    placeholder="Enter market address"
                >

            </div>


            <!-- Coordinates -->

            <div class="form-row">

                <div class="form-group">

                    <label for="latitude">
                        Latitude
                    </label>

                    <input
                        type="number"
                        id="latitude"
                        name="latitude"
                        step="0.00000001"
                        min="-90"
                        max="90"
                        value="<?= htmlspecialchars(
                            $latitude,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        placeholder="e.g. 15.3694"
                    >

                </div>


                <div class="form-group">

                    <label for="longitude">
                        Longitude
                    </label>

                    <input
                        type="number"
                        id="longitude"
                        name="longitude"
                        step="0.00000001"
                        min="-180"
                        max="180"
                        value="<?= htmlspecialchars(
                            $longitude,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        placeholder="e.g. 44.1910"
                    >

                </div>

            </div>


            <!-- Map Provider -->

            <div class="form-group">

                <label for="map_provider">
                    Map Provider
                </label>

                <select
                    id="map_provider"
                    name="map_provider"
                >

                    <?php foreach ($allowedMapProviders as $provider): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $provider,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            <?= $mapProvider === $provider
                                ? 'selected'
                                : ''; ?>
                        >

                            <?= htmlspecialchars(
                                $provider,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Opening / Closing Time -->

            <div class="form-row">

                <div class="form-group">

                    <label for="opening_time">
                        Opening Time
                    </label>

                    <input
                        type="time"
                        id="opening_time"
                        name="opening_time"
                        value="<?= htmlspecialchars(
                            $openingTime,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="closing_time">
                        Closing Time
                    </label>

                    <input
                        type="time"
                        id="closing_time"
                        name="closing_time"
                        value="<?= htmlspecialchars(
                            $closingTime,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >

                </div>

            </div>


            <!-- Operating Days -->

            <div class="form-group">

                <label>
                    Operating Days
                </label>

                <div class="checkbox-group">

                    <?php foreach ($allowedDays as $day): ?>

                        <label class="checkbox-label">

                            <input
                                type="checkbox"
                                name="operating_days[]"
                                value="<?= htmlspecialchars(
                                    $day,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                <?= in_array(
                                    $day,
                                    $operatingDays,
                                    true
                                )
                                    ? 'checked'
                                    : ''; ?>
                            >

                            <?= htmlspecialchars(
                                $day,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- Status -->

            <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                >

                    <?php foreach ($allowedStatuses as $marketStatus): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $marketStatus,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            <?= $status === $marketStatus
                                ? 'selected'
                                : ''; ?>
                        >

                            <?= htmlspecialchars(
                                ucfirst($marketStatus),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Buttons -->

            <div class="form-actions">

                <a
                    href="markets.php"
                    class="button button-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="button button-primary"
                >
                    Add Market
                </button>

            </div>

        </form>

    </div>

</main>

</body>

</html>