<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die('Invalid market ID.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

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

$errors = [];
$successMessage = '';

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

$stmt = $conn->prepare("
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
        map_provider,
        status
    FROM markets
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Failed to load market.');
}

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$market = $result->fetch_assoc();

$stmt->close();

if (!$market) {
    die('Market not found.');
}

$name = $market['name'] ?? '';
$description = $market['description'] ?? '';
$address = $market['address'] ?? '';
$latitude = $market['latitude'] !== null
    ? (string) $market['latitude']
    : '';
$longitude = $market['longitude'] !== null
    ? (string) $market['longitude']
    : '';
$openingTime = $market['opening_time'] ?? '';
$closingTime = $market['closing_time'] ?? '';

if (!empty($market['operating_days'])) {
    $operatingDays = array_values(
        array_intersect(
            array_map(
                'trim',
                explode(',', $market['operating_days'])
            ),
            $allowedDays
        )
    );
}

$mapProvider = $market['map_provider'] ?? 'OpenStreetMap';
$status = $market['status'] ?? 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($submittedToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Invalid security token. Please try again.';
    }

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

    if (!is_array($operatingDays)) {
        $operatingDays = [];
    }

    $operatingDays = array_values(
        array_intersect($operatingDays, $allowedDays)
    );

    if (empty($errors)) {

        if ($name === '') {
            $errors[] = 'Market name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'Market name cannot exceed 150 characters.';
        } elseif (mb_strlen($description) > 1000) {
            $errors[] = 'Description cannot exceed 1000 characters.';
        } elseif ($address === '') {
            $errors[] = 'Address is required.';
        } elseif (mb_strlen($address) > 255) {
            $errors[] = 'Address cannot exceed 255 characters.';
        } elseif (
            $latitude !== '' &&
            (
                !is_numeric($latitude) ||
                (float) $latitude < -90 ||
                (float) $latitude > 90
            )
        ) {
            $errors[] =
                'Please enter a valid latitude between -90 and 90.';
        } elseif (
            $longitude !== '' &&
            (
                !is_numeric($longitude) ||
                (float) $longitude < -180 ||
                (float) $longitude > 180
            )
        ) {
            $errors[] =
                'Please enter a valid longitude between -180 and 180.';
        } elseif (
            $openingTime !== '' &&
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $openingTime
            )
        ) {
            $errors[] = 'Please enter a valid opening time.';
        } elseif (
            $closingTime !== '' &&
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $closingTime
            )
        ) {
            $errors[] = 'Please enter a valid closing time.';
        } elseif (
            $openingTime !== '' &&
            $closingTime !== '' &&
            $closingTime <= $openingTime
        ) {
            $errors[] =
                'Closing time must be later than opening time.';
        } elseif (
            !in_array(
                $mapProvider,
                $allowedMapProviders,
                true
            )
        ) {
            $errors[] = 'Invalid map provider.';
        } elseif (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            $errors[] = 'Invalid market status.';
        } elseif (empty($operatingDays)) {
            $errors[] =
                'Please select at least one operating day.';
        } elseif (
            mb_strlen(
                implode(', ', $operatingDays)
            ) > 100
        ) {
            $errors[] =
                'The selected operating days are too long.';
        }
    }

    if (empty($errors)) {

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

            $stmt = $conn->prepare("
                UPDATE markets
                SET
                    name = ?,
                    description = ?,
                    address = ?,
                    latitude = ?,
                    longitude = ?,
                    opening_time = ?,
                    closing_time = ?,
                    operating_days = ?,
                    map_provider = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                'sssddsssssi',
                $name,
                $description,
                $address,
                $latitudeValue,
                $longitudeValue,
                $openingTimeValue,
                $closingTimeValue,
                $operatingDaysValue,
                $mapProvider,
                $status,
                $id
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $csrfToken = $_SESSION['csrf_token'];

            $successMessage = 'Market updated successfully.';

        } catch (mysqli_sql_exception $e) {

            error_log(
                'MarketLink - Edit Market Error: ' .
                $e->getMessage()
            );

            $errors[] =
                'Unable to update the market. Please try again.';
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

    <title>Edit Market | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-edit-market-page">

    <section class="admin-page-hero">

        <div class="admin-page-hero-copy">

            <span class="eyebrow">
                ADMIN / MARKETS
            </span>

            <h1>
                Edit local <em>market.</em>
            </h1>

            <p>
                Update the market's details, location, operating hours,
                and availability.
            </p>

        </div>

        <div class="admin-page-mark">
            07
        </div>

    </section>

    <?php if (!empty($errors)): ?>

        <div class="admin-page-alert alert-danger">

            <span class="admin-alert-mark">!</span>

            <div>

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>

        <div class="admin-page-alert alert-success">

            <span class="admin-alert-mark">✓</span>

            <div>
                <?= htmlspecialchars(
                    $successMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        </div>

    <?php endif; ?>

    <form
        method="POST"
        action=""
        class="admin-market-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $csrfToken,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <section class="admin-form-section">

            <div class="admin-form-section-heading">

                <span class="admin-section-number">
                    01 / LOCATION
                </span>

                <h2>
                    Market <em>details.</em>
                </h2>

                <p>
                    Update the basic information customers see when
                    discovering this market.
                </p>

            </div>

            <div class="admin-market-form-card">

                <div class="admin-market-form-grid">

                    <div class="admin-market-field admin-market-field-full">

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
                            ) ?>"
                            placeholder="e.g. Central Farmers Market"
                        >

                    </div>

                    <div class="admin-market-field admin-market-field-full">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            maxlength="1000"
                            placeholder="Describe this market, its atmosphere, or what customers can find there."
                        ><?= htmlspecialchars(
                            $description,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>

                    </div>

                    <div class="admin-market-field admin-market-field-full">

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
                            ) ?>"
                            placeholder="Enter the full market address"
                        >

                    </div>

                </div>

            </div>

        </section>

        <section class="admin-form-section">

            <div class="admin-form-section-heading">

                <span class="admin-section-number">
                    02 / LOCATION DATA
                </span>

                <h2>
                    Market <em>map.</em>
                </h2>

                <p>
                    Adjust the exact market location or update the
                    coordinates manually.
                </p>

            </div>

            <div class="admin-market-form-card">

                <div class="admin-market-form-grid">

                    <div class="admin-market-field">

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
                            ) ?>"
                            placeholder="e.g. 15.3694"
                        >

                        <span class="admin-market-field-help">
                            Between -90 and 90.
                        </span>

                    </div>

                    <div class="admin-market-field">

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
                            ) ?>"
                            placeholder="e.g. 44.1910"
                        >

                        <span class="admin-market-field-help">
                            Between -180 and 180.
                        </span>

                    </div>

                    <div class="admin-market-field admin-market-field-full">

                        <label>
                            Market Location
                        </label>

                        <div
                            id="market-map"
                            class="admin-market-map"
                        ></div>

                        <span class="admin-market-field-help">
                            Click anywhere on the map to move the market
                            location.
                        </span>

                    </div>

                    <div class="admin-market-field admin-market-field-full">

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
                                    ) ?>"
                                    <?= $mapProvider === $provider
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $provider,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </div>

        </section>

        <section class="admin-form-section">

            <div class="admin-form-section-heading">

                <span class="admin-section-number">
                    03 / AVAILABILITY
                </span>

                <h2>
                    Market <em>hours.</em>
                </h2>

                <p>
                    Update the days and hours when customers can visit
                    this market.
                </p>

            </div>

            <div class="admin-market-form-card">

                <div class="admin-market-form-grid">

                    <div class="admin-market-field">

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
                            ) ?>"
                        >

                    </div>

                    <div class="admin-market-field">

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
                            ) ?>"
                        >

                    </div>

                    <div class="admin-market-field admin-market-field-full">

                        <label>
                            Operating Days
                        </label>

                        <div class="admin-market-days">

                            <?php foreach ($allowedDays as $day): ?>

                                <label class="admin-market-day">

                                    <input
                                        type="checkbox"
                                        name="operating_days[]"
                                        value="<?= htmlspecialchars(
                                            $day,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= in_array(
                                            $day,
                                            $operatingDays,
                                            true
                                        )
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <span>
                                        <?= htmlspecialchars(
                                            substr($day, 0, 3),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <section class="admin-form-section">

            <div class="admin-form-section-heading">

                <span class="admin-section-number">
                    04 / VISIBILITY
                </span>

                <h2>
                    Market <em>status.</em>
                </h2>

                <p>
                    Choose whether this market is currently available
                    throughout MarketLink.
                </p>

            </div>

            <div class="admin-market-form-card">

                <div class="admin-market-status-options">

                    <?php foreach ($allowedStatuses as $marketStatus): ?>

                        <label class="admin-market-status-option">

                            <input
                                type="radio"
                                name="status"
                                value="<?= htmlspecialchars(
                                    $marketStatus,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $status === $marketStatus
                                    ? 'checked'
                                    : '' ?>
                            >

                            <span class="admin-market-status-content">

                                <span class="admin-market-status-title">
                                    <?= htmlspecialchars(
                                        ucfirst($marketStatus),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                                <span class="admin-market-status-description">

                                    <?php if ($marketStatus === 'active'): ?>

                                        Customers can discover and use
                                        this market.

                                    <?php else: ?>

                                        Keep this market hidden from
                                        active listings.

                                    <?php endif; ?>

                                </span>

                            </span>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

        <div class="admin-market-form-actions">

            <a
                href="markets.php"
                class="admin-action-cancel"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="admin-action-submit"
            >
                Save Changes
            </button>

        </div>

    </form>

</main>

<script>
    window.marketLocation = {
        latitude: <?= $latitude !== '' ? (float) $latitude : 15.3694 ?>,
        longitude: <?= $longitude !== '' ? (float) $longitude : 44.1910 ?>
    };
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../assets/js/leaflet.js"></script>

</body>
</html>