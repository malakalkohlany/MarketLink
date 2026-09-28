<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header('Location: edit_profile.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $stall_name = trim($_POST['stall_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $farmer_address = trim($_POST['farmer_address'] ?? '');

    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;

    $market_ids = $_POST['market_ids'] ?? [];

    if (!is_array($market_ids)) {
        $market_ids = [];
    }

    $market_ids = array_map('intval', $market_ids);
    $market_ids = array_values(array_unique(array_filter(
        $market_ids,
        function ($id) {
            return $id > 0;
        }
    )));

    if (
        isset($_FILES['profile_image']) &&
        $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            die("Failed to upload profile image.");
        }

        $image = $_FILES['profile_image'];

        if ($image['size'] > 2 * 1024 * 1024) {
            die("Profile image must be less than 2 MB.");
        }

        $image_info = getimagesize($image['tmp_name']);

        if ($image_info === false) {
            die("Invalid image file.");
        }

        $allowed_types = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_WEBP
        ];

        if (!in_array($image_info[2], $allowed_types, true)) {
            die("Only JPG, PNG, and WEBP images are allowed.");
        }

        $extensions = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp'
        ];

        $extension = $extensions[$image_info[2]];

        $upload_dir = __DIR__ . '/../assets/images/farmers/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = 'farmer_' . $user_id . '.' . $extension;

        $upload_path = $upload_dir . $file_name;

        foreach (['jpg', 'png', 'webp'] as $old_extension) {

            $old_file = $upload_dir . 'farmer_' . $user_id . '.' . $old_extension;

            if ($old_file !== $upload_path && file_exists($old_file)) {
                unlink($old_file);
            }
        }

        if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
            die("Failed to save profile image.");
        }
    }

    $conn->begin_transaction();

    try {

        $sql = "UPDATE users
                SET name = ?, phone = ?, email = ?, address = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare user update.");
        }

        $stmt->bind_param(
            "ssssi",
            $name,
            $phone,
            $email,
            $address,
            $user_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update user information.");
        }

        $stmt->close();

        $sql = "UPDATE farmers
                SET stall_name = ?,
                    contact_person = ?,
                    description = ?,
                    address = ?,
                    latitude = ?,
                    longitude = ?
                WHERE user_id = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare farmer update.");
        }

        $stmt->bind_param(
            "ssssddi",
            $stall_name,
            $contact_person,
            $description,
            $farmer_address,
            $latitude,
            $longitude,
            $user_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update farmer information.");
        }

        $stmt->close();

        $farmer_sql = "SELECT id
                       FROM farmers
                       WHERE user_id = ?
                       LIMIT 1";

        $stmt = $conn->prepare($farmer_sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare farmer query.");
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $farmer_result = $stmt->get_result();
        $farmer_row = $farmer_result->fetch_assoc();

        $stmt->close();

        if (!$farmer_row) {
            throw new Exception("Farmer not found.");
        }

        $farmer_id = (int) $farmer_row['id'];

        $delete_sql = "DELETE FROM market_farmer
                       WHERE farmer_id = ?";

        $stmt = $conn->prepare($delete_sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare market relationship delete.");
        }

        $stmt->bind_param("i", $farmer_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update farmer markets.");
        }

        $stmt->close();

        if (!empty($market_ids)) {

            $insert_sql = "INSERT INTO market_farmer
                           (farmer_id, market_id)
                           VALUES (?, ?)";

            $stmt = $conn->prepare($insert_sql);

            if (!$stmt) {
                throw new Exception("Failed to prepare market relationship insert.");
            }

            foreach ($market_ids as $market_id) {

                $stmt->bind_param(
                    "ii",
                    $farmer_id,
                    $market_id
                );

                if (!$stmt->execute()) {
                    throw new Exception("Failed to save farmer market.");
                }
            }

            $stmt->close();
        }

        $conn->commit();

        redirect('profile.php');

    } catch (Exception $e) {

        $conn->rollback();

        die($e->getMessage());
    }
}

$sql = "SELECT
            users.name,
            users.email,
            users.phone,
            users.address,
            farmers.id AS farmer_id,
            farmers.stall_name,
            farmers.contact_person,
            farmers.description,
            farmers.address AS farmer_address,
            farmers.latitude,
            farmers.longitude
        FROM users
        INNER JOIN farmers ON farmers.user_id = users.id
        WHERE users.id = ? AND users.role = 'farmer'
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Failed to prepare farmer profile query.");
}

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$farmer = $result->fetch_assoc();

$stmt->close();

if (!$farmer) {
    die("Farmer profile not found.");
}

$farmer_id = (int) $farmer['farmer_id'];

$markets_sql = "SELECT
                    id,
                    name,
                    address,
                    latitude,
                    longitude
                FROM markets
                ORDER BY name ASC";

$markets_result = $conn->query($markets_sql);

if (!$markets_result) {
    die("Failed to load markets.");
}

$markets = [];

while ($market = $markets_result->fetch_assoc()) {
    $markets[] = $market;
}

$selected_sql = "SELECT market_id
                 FROM market_farmer
                 WHERE farmer_id = ?";

$stmt = $conn->prepare($selected_sql);

if (!$stmt) {
    die("Failed to prepare selected markets query.");
}

$stmt->bind_param("i", $farmer_id);

$stmt->execute();

$selected_result = $stmt->get_result();

$selected_markets = [];

while ($row = $selected_result->fetch_assoc()) {
    $selected_markets[] = (int) $row['market_id'];
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Profile</title>

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

</head>

<body>

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>Edit Profile</h1>

        <form
            action="edit_profile.php"
            method="post"
            enctype="multipart/form-data"
        >

            <?= csrf_field() ?>

            <section>

                <h2>Profile Image</h2>

                <div>

                    <label for="profile_image">
                        Profile Image
                    </label>

                    <input
                        type="file"
                        id="profile_image"
                        name="profile_image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>
                        Allowed formats: JPG, PNG, WEBP. Maximum size: 2 MB.
                    </small>

                </div>

            </section>

            <section>

                <h2>Personal Information</h2>

                <div>

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($farmer['name'] ?? '') ?>"
                    >

                </div>

                <div>

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($farmer['email'] ?? '') ?>"
                    >

                </div>

                <div>

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars($farmer['phone'] ?? '') ?>"
                    >

                </div>

                <div>

                    <label for="address">
                        Address
                    </label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="<?= htmlspecialchars($farmer['address'] ?? '') ?>"
                    >

                </div>

            </section>

            <section>

                <h2>Farmer Information</h2>

                <div>

                    <label for="stall_name">
                        Business / Stall Name
                    </label>

                    <input
                        type="text"
                        id="stall_name"
                        name="stall_name"
                        value="<?= htmlspecialchars($farmer['stall_name'] ?? '') ?>"
                    >

                </div>

                <div>

                    <label for="contact_person">
                        Contact Person
                    </label>

                    <input
                        type="text"
                        id="contact_person"
                        name="contact_person"
                        value="<?= htmlspecialchars($farmer['contact_person'] ?? '') ?>"
                    >

                </div>

                <div>

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        name="description"
                        id="description"
                    ><?= htmlspecialchars($farmer['description'] ?? '') ?></textarea>

                </div>

                <div>

                    <label for="farmer_address">
                        Farmer Address
                    </label>

                    <input
                        type="text"
                        id="farmer_address"
                        name="farmer_address"
                        value="<?= htmlspecialchars($farmer['farmer_address'] ?? '') ?>"
                    >

                </div>

            </section>

            <section>

                <h2>Markets</h2>

                <div>

                    <label for="market_ids">
                        Markets where you sell
                    </label>

                    <select
                        id="market_ids"
                        name="market_ids[]"
                        multiple
                        size="6"
                    >

                        <?php foreach ($markets as $market): ?>

                            <option
                                value="<?= (int) $market['id'] ?>"
                                <?= in_array(
                                    (int) $market['id'],
                                    $selected_markets,
                                    true
                                ) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($market['name']) ?>
                                <?php if (!empty($market['address'])): ?>
                                    - <?= htmlspecialchars($market['address']) ?>
                                <?php endif; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <small>
                        Hold Ctrl on Windows or Command on Mac to select multiple markets.
                    </small>

                </div>

            </section>

            <section>

                <h2>Farmer Location</h2>

                <div>

                    <label>
                        Farmer Location
                    </label>

                    <p>
                        Click on the map to select your stall location.
                    </p>

                    <div
                        id="map"
                        style="width: 100%; height: 400px;"
                    ></div>

                </div>

                <div>

                    <label for="latitude">
                        Latitude
                    </label>

                    <input
                        type="text"
                        id="latitude"
                        name="latitude"
                        value="<?= htmlspecialchars($farmer['latitude'] ?? '') ?>"
                    >

                </div>

                <div>

                    <label for="longitude">
                        Longitude
                    </label>

                    <input
                        type="text"
                        id="longitude"
                        name="longitude"
                        value="<?= htmlspecialchars($farmer['longitude'] ?? '') ?>"
                    >

                </div>

            </section>

            <button type="submit">
                Save Changes
            </button>

            <button
                type="button"
                onclick="window.location.href='profile.php'"
            >
                Cancel
            </button>

        </form>

    </main>

    <script>
        window.farmerLocation = {
            latitude: <?= $farmer['latitude'] !== null ? $farmer['latitude'] : 15.3694 ?>,
            longitude: <?= $farmer['longitude'] !== null ? $farmer['longitude'] : 44.1910 ?>
        };
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script src="../assets/js/leaflet.js"></script>

</body>
</html>
 