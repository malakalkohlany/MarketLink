edit_market.php


<?php

require_once '../includes/include.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$market = null;
$errors = [];
$success = '';

if ($id) {

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
            status,
            created_at,
            updated_at
        FROM markets
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $market = $stmt->fetch();
}

if (!$market) {
    die("Market not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    $opening_time = $_POST['opening_time'] ?? '';
    $closing_time = $_POST['closing_time'] ?? '';

    $operating_days = $_POST['operating_days'] ?? [];

    $map_provider = $_POST['map_provider'] ?? 'OpenStreetMap';
    $status = $_POST['status'] ?? 'active';

   if ($name === '') {
        $errors[] = "Market name is required.";
    }

    if ($address === '') {
        $errors[] = "Market address is required.";
    }
    if (empty($errors)) {

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
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $description,
            $address,
            $latitude,
            $longitude,
            $opening_time,
            $closing_time,
            json_encode(
                $operating_days,
                JSON_UNESCAPED_UNICODE
            ),
            $map_provider,
            $status,
            $id
        ]);

        $success = "Market updated successfully.";
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
                status,
                created_at,
                updated_at
            FROM markets
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $market = $stmt->fetch();
    }
}

$current_days = [];

if (!empty($market['operating_days'])) {

    $decoded_days = json_decode(
        $market['operating_days'],
        true
    );

    if (is_array($decoded_days)) {
        $current_days = $decoded_days;
    }
}

?>

<div class="page-header">

    <h1>
        Edit Market
    </h1>

    <p>
        Update market information.
    </p>

</div>


<?php if (!empty($errors)): ?>

    <section class="table-section">

        <?php foreach ($errors as $error): ?>

            <p>
                <?= htmlspecialchars($error) ?>
            </p>

        <?php endforeach; ?>

    </section>

<?php endif; ?>


<?php if ($success): ?>

    <section class="table-section">

        <p>
            <?= htmlspecialchars($success) ?>
        </p>

    </section>

<?php endif; ?>


<section class="form-section">

    <div class="section-header">

        <h2>
            Market Information
        </h2>

        <a
            href="markets.php"
            class="btn btn-secondary"
        >
            Back to Markets
        </a>

    </div>


    <form method="POST">


        <div class="details-grid">


            <div class="form-group">

                <label for="name">
                    Market Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars(
                        $market['name']
                    ) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="address">
                    Address
                </label>

                <input
                    type="text"
                    id="address"
                    name="address"
                    value="<?= htmlspecialchars(
                        $market['address'] ?? ''
                    ) ?>"
                    required
                >

            </div>

     <div class="form-group">

                <label for="latitude">
                    Latitude
                </label>

                <input
                    type="text"
                    id="latitude"
                    name="latitude"
                    value="<?= htmlspecialchars(
                        $market['latitude'] ?? ''
                    ) ?>"
                >

            </div>


            <div class="form-group">

                <label for="longitude">
                    Longitude
                </label>

                <input
                    type="text"
                    id="longitude"
                    name="longitude"
                    value="<?= htmlspecialchars(
                        $market['longitude'] ?? ''
                    ) ?>"
                >

            </div>


            <div class="form-group">

                <label for="opening_time">
                    Opening Time
                </label>

                <input
                    type="time"
                    id="opening_time"
                    name="opening_time"
                    value="<?= htmlspecialchars(
                        $market['opening_time'] ?? ''
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
                        $market['closing_time'] ?? ''
                    ) ?>"
                >

            </div>


            <div class="form-group">

                <label for="map_provider">
                    Map Provider
                </label>

                <select
                    id="map_provider"
                    name="map_provider"
                >

                    <option
                        value="OpenStreetMap"
                        <?= (
                            $market['map_provider']
                            === 'OpenStreetMap'
                        ) ? 'selected' : '' ?>
                    >
                        OpenStreetMap
                    </option>

                    <option
                        value="Google Maps"
                        <?= (
                            $market['map_provider']
                            === 'Google Maps'
                        ) ? 'selected' : '' ?>
                    >
                        Google Maps
                    </option>

                </select>

            </div>
   <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                >

                    <option
                        value="active"
                        <?= (
                            $market['status']
                            === 'active'
                        ) ? 'selected' : '' ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= (
                            $market['status']
                            === 'inactive'
                        ) ? 'selected' : '' ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


        </div>


        <div class="form-group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="5"
            ><?= htmlspecialchars(
                $market['description'] ?? ''
            ) ?></textarea>

        </div>
         <div class="form-group">

            <label>
                Operating Days
            </label>


            <?php

            $days = [
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday'
            ];

            ?>


            <div>

                <?php foreach ($days as $day): ?>

                    <label>

                        <input
                            type="checkbox"
                            name="operating_days[]"
                            value="<?= $day ?>"
                            <?= in_array(
                                $day,
                                $current_days
                            ) ? 'checked' : '' ?>
                        >

                        <?= $day ?>

                    </label>

                <?php endforeach; ?>

            </div>

        </div>


        <button
            type="submit"
            class="btn btn-primary"
        >
            Update Market
        </button>


    </form>

</section>
