<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($submittedToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errorMessage = 'Invalid security token. Please try again.';
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

    if ($errorMessage === '') {

        if ($name === '') {
            $errorMessage = 'Market name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errorMessage = 'Market name cannot exceed 150 characters.';
        } elseif (mb_strlen($description) > 1000) {
            $errorMessage = 'Description cannot exceed 1000 characters.';
        } elseif ($address === '') {
            $errorMessage = 'Address is required.';
        } elseif (mb_strlen($address) > 255) {
            $errorMessage = 'Address cannot exceed 255 characters.';
        } elseif (
            $latitude !== '' &&
            (
                !is_numeric($latitude) ||
                (float) $latitude < -90 ||
                (float) $latitude > 90
            )
        ) {
            $errorMessage = 'Please enter a valid latitude between -90 and 90.';
        } elseif (
            $longitude !== '' &&
            (
                !is_numeric($longitude) ||
                (float) $longitude < -180 ||
                (float) $longitude > 180
            )
        ) {
            $errorMessage = 'Please enter a valid longitude between -180 and 180.';
        } elseif (
            $openingTime !== '' &&
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $openingTime)
        ) {
            $errorMessage = 'Please enter a valid opening time.';
        } elseif (
            $closingTime !== '' &&
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $closingTime)
        ) {
            $errorMessage = 'Please enter a valid closing time.';
        } elseif (
            $openingTime !== '' &&
            $closingTime !== '' &&
            $closingTime <= $openingTime
        ) {
            $errorMessage = 'Closing time must be later than opening time.';
        } elseif (
            !in_array($mapProvider, $allowedMapProviders, true)
        ) {
            $errorMessage = 'Invalid map provider.';
        } elseif (
            !in_array($status, $allowedStatuses, true)
        ) {
            $errorMessage = 'Invalid market status.';
        } elseif (empty($operatingDays)) {
            $errorMessage = 'Please select at least one operating day.';
        } else {
            foreach ($operatingDays as $day) {
                if (!in_array($day, $allowedDays, true)) {
                    $errorMessage = 'Invalid operating day.';
                    break;
                }
            }
        }

        if (
            $errorMessage === '' &&
            mb_strlen(implode(', ', $operatingDays)) > 100
        ) {
            $errorMessage = 'The selected operating days are too long.';
        }
    }

    if ($errorMessage === '') {

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

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $_SESSION['success_message'] = 'Market added successfully.';

                redirect('admin/markets.php');
            }

        } catch (mysqli_sql_exception $e) {

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

    <title>Add Market | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-add-market-page">

    <section class="customer-page-hero admin-customers-hero">
        <div class="customer-page-hero-copy">
            <span class="eyebrow">ADMIN / MARKETS</span>

            <h1>
                Add a new <em>market.</em>
            </h1>

            <p>
                Create a market location where customers can discover
                local farmers and fresh produce.
            </p>
        </div>

        <div class="customer-page-hero-mark">07</div>
    </section>

    <?php if ($errorMessage !== ''): ?>

        <div class="admin-page-alert alert-danger">
            <span class="admin-alert-mark">!</span>

            <div>
                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </div>
        </div>

    <?php endif; ?>

    <form
        method="POST"
        class="admin-market-form"
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

        <section class="admin-form-section">

            <div class="admin-form-section-heading">
                <span class="eyebrow">01 / LOCATION</span>

                <h2>
                    Market <em>details.</em>
                </h2>

                <p>
                    Tell customers where this market is and what they
                    can expect to find there.
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
                            ); ?>"
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
                        ); ?></textarea>

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
                            ); ?>"
                            placeholder="Enter the full market address"
                        >

                    </div>

                </div>

            </div>

        </section>

        <section class="admin-form-section">

            <div class="admin-form-section-heading">
                <span class="eyebrow">02 / LOCATION DATA</span>

                <h2>
                    Map <em>coordinates.</em>
                </h2>

                <p>
                    Add coordinates to help customers locate the market
                    accurately on a map.
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
                            ); ?>"
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
                            ); ?>"
                            placeholder="e.g. 44.1910"
                        >

                        <span class="admin-market-field-help">
                            Between -180 and 180.
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

                </div>

            </div>

        </section>

        <section class="admin-form-section">

            <div class="admin-form-section-heading">
                <span class="eyebrow">03 / AVAILABILITY</span>

                <h2>
                    Market <em>hours.</em>
                </h2>

                <p>
                    Set the hours and days when customers can visit.
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
                            ); ?>"
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
                            ); ?>"
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
                                        ); ?>"
                                        <?= in_array(
                                            $day,
                                            $operatingDays,
                                            true
                                        )
                                            ? 'checked'
                                            : ''; ?>
                                    >

                                    <span>
                                        <?= htmlspecialchars(
                                            substr($day, 0, 3),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
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
                <span class="eyebrow">04 / VISIBILITY</span>

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
                                ); ?>"
                                <?= $status === $marketStatus
                                    ? 'checked'
                                    : ''; ?>
                            >

                            <span class="admin-market-status-content">

                                <span class="admin-market-status-title">
                                    <?= htmlspecialchars(
                                        ucfirst($marketStatus),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                                <span class="admin-market-status-description">
                                    <?php if ($marketStatus === 'active'): ?>
                                        Customers can discover and use this market.
                                    <?php else: ?>
                                        Keep this market hidden from active listings.
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
                Add Market
            </button>

        </div>

    </form>

</main>

</body>
</html>