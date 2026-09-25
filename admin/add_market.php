
<?php

require_once '../includes/include.php';

/* Check admin access*/
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../auth/login.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$errors = [];


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

/*  form submission*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

/*CSRF Validation */

  $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        $errors[] = "Invalid security token. Please refresh the page and try again.";
    }

/*Get Form Data*/

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

    /*Normalize Operating Days*/

 if (!is_array($operatingDays)) {
        $operatingDays = [];
    }

    $operatingDays = array_values(
        array_unique(
            array_intersect($operatingDays, $allowedDays)
        )
    );

      if ($name === '') {
        $errors[] = "Market name is required.";
    } elseif (mb_strlen($name) > 150) {
        $errors[] = "Market name must not exceed 150 characters.";
    }

    if ($address === '') {
        $errors[] = "Market address is required.";
    } elseif (mb_strlen($address) > 255) {
        $errors[] = "Market address must not exceed 255 characters.";
    }

    if ($description !== '' && mb_strlen($description) > 2000) {
        $errors[] = "Description must not exceed 2000 characters.";
    }

     if ($latitude !== '') {

        if (!is_numeric($latitude)) {
            $errors[] = "Latitude must be a valid number.";
        } elseif ((float)$latitude < -90 || (float)$latitude > 90) {
            $errors[] = "Latitude must be between -90 and 90.";
        }
    }
if ($longitude !== '') {

        if (!is_numeric($longitude)) {
            $errors[] = "Longitude must be a valid number.";
        } elseif ((float)$longitude < -180 || (float)$longitude > 180) {
            $errors[] = "Longitude must be between -180 and 180.";
        }
    }
     if ($openingTime !== '') {

        $openingDate = DateTime::createFromFormat('H:i', $openingTime);

        if (
            !$openingDate ||
            $openingDate->format('H:i') !== $openingTime
        ) {
            $errors[] = "Opening time is invalid.";
        }
    }

    if ($closingTime !== '') {

        $closingDate = DateTime::createFromFormat('H:i', $closingTime);

        if (
            !$closingDate ||
            $closingDate->format('H:i') !== $closingTime
        ) {
            $errors[] = "Closing time is invalid.";
        }
    }
    if (
        $openingTime !== '' &&
        $closingTime !== '' &&
        empty($errors)
    ) {

        if ($openingTime >= $closingTime) {
            $errors[] = "Closing time must be later than opening time.";
        }
    }
  if (empty($operatingDays)) {
        $errors[] = "Please select at least one operating day.";
    }
     if (!in_array($mapProvider, $allowedMapProviders, true)) {
        $errors[] = "Invalid map provider.";
    }
     if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = "Invalid market status.";
    }

if (empty($errors)) {

        $days = implode(', ', $operatingDays);

        $sql = "
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
            VALUES (
                :name,
                :description,
                :address,
                :latitude,
                :longitude,
                :opening_time,
                :closing_time,
                :operating_days,
                :map_provider,
                :status
            )
        ";

        try {

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':name' => $name,

                ':description' =>
                    $description !== ''
                        ? $description
                        : null,

                ':address' => $address,

                ':latitude' =>
                    $latitude !== ''
                        ? (float)$latitude
                        : null,

                ':longitude' =>
                    $longitude !== ''
                        ? (float)$longitude
                        : null,

                ':opening_time' =>
                    $openingTime !== ''
                        ? $openingTime
                        : null,

                ':closing_time' =>
                    $closingTime !== ''
                        ? $closingTime
                        : null,

                ':operating_days' =>
                    $days !== ''
                        ? $days
                        : null,

                ':map_provider' => $mapProvider,

                ':status' => $status
            ]);

   
   $_SESSION['success_message'] = "Market added successfully.";
     header("Location: markets.php");
            exit;

        } catch (PDOException $e) {

         error_log(
                "FreshFind - Add Market Error: " .
                $e->getMessage()
            );

            $errors[] = "Unable to add the market. Please try again.";
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

    // <meta
    //     name="description"
    //     content="FreshFind Admin - Add a new farmers market."
    // >

    <title>Add Market | FreshFind</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

    <main class="main-content">

        <div class="page-header">

            <h1>
                Add New Market
            </h1>

            <p>
                Add a new farmers market to FreshFind.
            </p>

        </div>

          <?php if (!empty($errors)): ?>

            <div
                class="alert alert-error"
                role="alert"
                aria-live="assertive"
            >

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

        <?php endif; ?>
          <form
            action="add_market.php"
            method="POST"
            class="market-form"
            novalidate
        >

            <!-- CSRF Protection -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $csrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <!-- Market Information -->

            <section
                class="form-section"
                aria-labelledby="market-information-title"
            >

                <h2 id="market-information-title">
                    Market Information
                </h2>


                <div class="form-group">

                    <label for="name">
                        Market Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter market name"
                        value="<?= htmlspecialchars(
                            $_POST['name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="150"
                        autocomplete="organization"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Enter market description"
                        maxlength="2000"
                    ><?= htmlspecialchars(
                        $_POST['description'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                </div>


                <div class="form-group">

                    <label for="address">
                        Address
                    </label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        placeholder="Enter market address"
                        value="<?= htmlspecialchars(
                            $_POST['address'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="255"
                        autocomplete="street-address"
                        required
                    >

                </div>

            </section>

             <!-- Market Location -->

            <section
                class="form-section"
                aria-labelledby="market-location-title"
            >

                <h2 id="market-location-title">
                    Market Location
                </h2>


                <div class="form-row">

                    <div class="form-group">

                        <label for="latitude">
                            Latitude
                        </label>

                        <input
                            type="number"
                            id="latitude"
                            name="latitude"
                            step="any"
                            min="-90"
                            max="90"
                            placeholder="Example: 15.3694"
                            value="<?= htmlspecialchars(
                                $_POST['latitude'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
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
                            step="any"
                            min="-180"
                            max="180"
                            placeholder="Example: 44.1910"
                            value="<?= htmlspecialchars(
                                $_POST['longitude'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="map_provider">
                        Map Provider
                    </label>

                    <select
                        id="map_provider"
                        name="map_provider"
                    >

                        <?php
                        $currentMapProvider =
                            $_POST['map_provider']
                            ?? 'OpenStreetMap';
                        ?>

                        <?php foreach (
                            $allowedMapProviders as $provider
                        ): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $provider,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $currentMapProvider === $provider
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

            </section>


            <!-- Operating Schedule -->

            <section
                class="form-section"
                aria-labelledby="operating-schedule-title"
            >

                <h2 id="operating-schedule-title">
                    Operating Schedule
                </h2>


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
                                $_POST['opening_time'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
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
                                $_POST['closing_time'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="form-group">

                    <fieldset>

                        <legend>
                            Operating Days
                        </legend>

                        <div class="days">

                            <?php foreach (
                                $allowedDays as $day
                            ): ?>

                                <label>

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
                                            $_POST['operating_days']
                                                ?? [],
                                            true
                                        )
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <?= htmlspecialchars(
                                        $day,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </fieldset>

                </div>

            </section>

              <!-- Market Status -->

            <section
                class="form-section"
                aria-labelledby="market-status-title"
            >

                <h2 id="market-status-title">
                    Market Status
                </h2>


                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <?php
                    $currentStatus =
                        $_POST['status'] ?? 'active';
                    ?>

                    <select
                        id="status"
                        name="status"
                    >

                        <?php foreach (
                            $allowedStatuses as $marketStatus
                        ): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $marketStatus,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $currentStatus === $marketStatus
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= ucfirst(
                                    htmlspecialchars(
                                        $marketStatus,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </section>


            <!-- Buttons -->

            <div class="form-actions">

                <a
                    href="markets.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Add Market
                </button>

            </div>

        </form>

    </main>

</body>

</html>