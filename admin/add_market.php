<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('admin');

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

    $operatingDays = array_values(
        array_intersect($operatingDays, $allowedDays)
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($errorMessage === '') {

        if ($name === '') {
            $errorMessage = 'Market name is required.';
        }

        elseif (mb_strlen($name) > 150) {
            $errorMessage = 'Market name cannot exceed 150 characters.';
        }

        elseif ($address === '') {
            $errorMessage = 'Address is required.';
        }

        elseif (mb_strlen($address) > 255) {
            $errorMessage = 'Address cannot exceed 255 characters.';
        }

        elseif (
            $latitude !== '' &&
            (!is_numeric($latitude) || $latitude < -90 || $latitude > 90)
        ) {
            $errorMessage = 'Please enter a valid latitude between -90 and 90.';
        }

        elseif (
            $longitude !== '' &&
            (!is_numeric($longitude) || $longitude < -180 || $longitude > 180)
        ) {
            $errorMessage = 'Please enter a valid longitude between -180 and 180.';
        }

        elseif (
            $openingTime !== '' &&
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $openingTime)
        ) {
            $errorMessage = 'Please enter a valid opening time.';
        }

        elseif (
            $closingTime !== '' &&
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $closingTime)
        ) {
            $errorMessage = 'Please enter a valid closing time.';
        }

        elseif (!in_array($mapProvider, $allowedMapProviders, true)) {
            $errorMessage = 'Invalid map provider.';
        }

        elseif (!in_array($status, $allowedStatuses, true)) {
            $errorMessage = 'Invalid market status.';
        }

        elseif (mb_strlen(implode(', ', $operatingDays)) > 100) {
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
         * Convert empty optional values to NULL.
         *
         * latitude      -> DECIMAL(10,8)
         * longitude     -> DECIMAL(11,8)
         * opening_time  -> TIME
         * closing_time  -> TIME
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


        /*
        |--------------------------------------------------------------------------
        | MySQLi Prepared Statement
        |--------------------------------------------------------------------------
        */

        try {

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
            | Success
            |--------------------------------------------------------------------------
            */

            $_SESSION['success_message'] = 'Market added successfully.';

            header('Location: markets.php');
            exit;

        } catch (mysqli_sql_exception $e) {

            $errorMessage = 'Unable to add the market. Please try again.';

            /*
             * For development only, you can temporarily use:
             *
             * $errorMessage = $e->getMessage();
             *
             * Do NOT display database errors on the deployed competition site.
             */
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

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

</head>

<body>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>


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
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
            class="form-container"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($csrfToken); ?>"
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
                    value="<?php echo htmlspecialchars($name); ?>"
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
                    placeholder="Describe the market"
                ><?php echo htmlspecialchars($description); ?></textarea>

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
                    value="<?php echo htmlspecialchars($address); ?>"
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
                        value="<?php echo htmlspecialchars($latitude); ?>"
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
                        value="<?php echo htmlspecialchars($longitude); ?>"
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
                            value="<?php echo htmlspecialchars($provider); ?>"
                            <?php echo $mapProvider === $provider ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($provider); ?>
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
                        value="<?php echo htmlspecialchars($openingTime); ?>"
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
                        value="<?php echo htmlspecialchars($closingTime); ?>"
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
                                value="<?php echo htmlspecialchars($day); ?>"
                                <?php echo in_array(
                                    $day,
                                    $operatingDays,
                                    true
                                ) ? 'checked' : ''; ?>
                            >

                            <?php echo htmlspecialchars($day); ?>

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
                            value="<?php echo htmlspecialchars($marketStatus); ?>"
                            <?php echo $status === $marketStatus ? 'selected' : ''; ?>
                        >
                            <?php echo ucfirst($marketStatus); ?>
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