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

    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== ''
        ? (float) $_POST['latitude']
        : null;

    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== ''
        ? (float) $_POST['longitude']
        : null;

    $market_ids = $_POST['market_ids'] ?? [];

    if (!is_array($market_ids)) {
        $market_ids = [];
    }

    $market_ids = array_map('intval', $market_ids);

    $market_ids = array_values(
        array_unique(
            array_filter(
                $market_ids,
                function ($id) {
                    return $id > 0;
                }
            )
        )
    );

    if (
        isset($_FILES['profile_image']) &&
        $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            die('Failed to upload profile image.');
        }

        $image = $_FILES['profile_image'];

        if ($image['size'] > 2 * 1024 * 1024) {
            die('Profile image must be less than 2 MB.');
        }

        $image_info = getimagesize($image['tmp_name']);

        if ($image_info === false) {
            die('Invalid image file.');
        }

        $allowed_types = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_WEBP
        ];

        if (!in_array($image_info[2], $allowed_types, true)) {
            die('Only JPG, PNG, and WEBP images are allowed.');
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

            $old_file =
                $upload_dir .
                'farmer_' .
                $user_id .
                '.' .
                $old_extension;

            if (
                $old_file !== $upload_path &&
                file_exists($old_file)
            ) {
                unlink($old_file);
            }
        }

        if (!move_uploaded_file(
            $image['tmp_name'],
            $upload_path
        )) {
            die('Failed to save profile image.');
        }
    }

    $conn->begin_transaction();

    try {

        $sql = "UPDATE users
                SET name = ?, phone = ?, email = ?, address = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'Failed to prepare user update.'
            );
        }

        $stmt->bind_param(
            'ssssi',
            $name,
            $phone,
            $email,
            $address,
            $user_id
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Failed to update user information.'
            );
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
            throw new Exception(
                'Failed to prepare farmer update.'
            );
        }

        $stmt->bind_param(
            'ssssddi',
            $stall_name,
            $contact_person,
            $description,
            $farmer_address,
            $latitude,
            $longitude,
            $user_id
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Failed to update farmer information.'
            );
        }

        $stmt->close();

        $farmer_sql = "SELECT id
                       FROM farmers
                       WHERE user_id = ?
                       LIMIT 1";

        $stmt = $conn->prepare($farmer_sql);

        if (!$stmt) {
            throw new Exception(
                'Failed to prepare farmer query.'
            );
        }

        $stmt->bind_param(
            'i',
            $user_id
        );

        $stmt->execute();

        $farmer_result = $stmt->get_result();
        $farmer_row = $farmer_result->fetch_assoc();

        $stmt->close();

        if (!$farmer_row) {
            throw new Exception(
                'Farmer not found.'
            );
        }

        $farmer_id = (int) $farmer_row['id'];

        $delete_sql = "DELETE FROM market_farmer
                       WHERE farmer_id = ?";

        $stmt = $conn->prepare($delete_sql);

        if (!$stmt) {
            throw new Exception(
                'Failed to prepare market relationship delete.'
            );
        }

        $stmt->bind_param(
            'i',
            $farmer_id
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Failed to update farmer markets.'
            );
        }

        $stmt->close();

        if (!empty($market_ids)) {

            $insert_sql = "INSERT INTO market_farmer
                           (farmer_id, market_id)
                           VALUES (?, ?)";

            $stmt = $conn->prepare($insert_sql);

            if (!$stmt) {
                throw new Exception(
                    'Failed to prepare market relationship insert.'
                );
            }

            foreach ($market_ids as $market_id) {

                $stmt->bind_param(
                    'ii',
                    $farmer_id,
                    $market_id
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Failed to save farmer market.'
                    );
                }
            }

            $stmt->close();
        }

        $conn->commit();

        redirect('farmer/profile.php');

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
        INNER JOIN farmers
            ON farmers.user_id = users.id
        WHERE users.id = ?
          AND users.role = 'farmer'
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Failed to prepare farmer profile query.');
}

$stmt->bind_param(
    'i',
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$stmt->close();

if (!$farmer) {
    die('Farmer profile not found.');
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
    die('Failed to load markets.');
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
    die('Failed to prepare selected markets query.');
}

$stmt->bind_param(
    'i',
    $farmer_id
);

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

    <title>Edit Profile | MarketLink</title>

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
        href="../assets/css/components.css"
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
        href="../assets/css/profile.css"
    >

</head>

<body>

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content farmer-edit-profile-page">

        <section class="customer-page-hero">

            <div class="customer-page-hero-copy">

                <span class="eyebrow">
                    FARMER / PROFILE
                </span>

                <h1>
                    Edit your <em>profile.</em>
                </h1>

                <p>
                    Keep your personal details, farmer information, markets, and location up to date on MarketLink.
                </p>

            </div>


        </section>

        <section class="farmer-edit-profile-section">

            <div class="farmer-edit-profile-heading">

                <div>

                    <span class="customer-section-number">
                        01 / EDIT PROFILE
                    </span>

                    <h2>
                        Keep things <em>current.</em>
                    </h2>

                </div>

            </div>

            <form
                class="farmer-edit-profile-form"
                action="edit_profile.php"
                method="post"
                enctype="multipart/form-data"
            >

                <?= csrf_field() ?>

                <section class="farmer-edit-profile-card">

                    <div class="farmer-edit-profile-card-heading">

                        <div class="farmer-edit-profile-card-icon">
                            <i data-lucide="image"></i>
                        </div>

                        <div>

                            <span class="farmer-edit-profile-card-number">
                                01
                            </span>

                            <h3>
                                Profile image
                            </h3>

                        </div>

                    </div>

                    <div class="farmer-edit-profile-image-layout">

                        <div class="farmer-edit-profile-current-image">

                            <div class="farmer-profile-image-placeholder">
                                <i data-lucide="image"></i>
                                <span>Farmer photo</span>
                            </div>

                        </div>

                        <div class="farmer-edit-profile-file">

                            <label
                                for="profile_image"
                                class="farmer-edit-profile-file-label"
                            >
                                <i data-lucide="cloud-upload"></i>

                                <span>
                                    Choose a new profile image
                                </span>
                            </label>

                            <input
                                type="file"
                                id="profile_image"
                                name="profile_image"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <small>
                                JPG, PNG, or WEBP. Maximum size: 2 MB.
                            </small>

                            <span
                                class="farmer-edit-profile-file-name"
                                id="profile-image-name"
                            >
                                No new image selected
                            </span>

                        </div>

                    </div>

                </section>

                <section class="farmer-edit-profile-card">

                    <div class="farmer-edit-profile-card-heading">

                        <div class="farmer-edit-profile-card-icon">
                            <i data-lucide="user"></i>
                        </div>

                        <div>

                            <span class="farmer-edit-profile-card-number">
                                02
                            </span>

                            <h3>
                                Personal information
                            </h3>

                        </div>

                    </div>

                    <div class="farmer-edit-profile-grid">

                        <div class="farmer-edit-profile-field">

                            <label for="name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?= e($farmer['name'] ?? '') ?>"
                            >

                        </div>

                        <div class="farmer-edit-profile-field">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= e($farmer['email'] ?? '') ?>"
                            >

                        </div>

                        <div class="farmer-edit-profile-field">

                            <label for="phone">
                                Phone Number
                            </label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="<?= e($farmer['phone'] ?? '') ?>"
                            >

                        </div>

                        <div class="farmer-edit-profile-field">

                            <label for="address">
                                Address
                            </label>

                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?= e($farmer['address'] ?? '') ?>"
                            >

                        </div>

                    </div>

                </section>

                <section class="farmer-edit-profile-card">

                    <div class="farmer-edit-profile-card-heading">

                        <div class="farmer-edit-profile-card-icon">
                            <i data-lucide="store"></i>
                        </div>

                        <div>

                            <span class="farmer-edit-profile-card-number">
                                03
                            </span>

                            <h3>
                                Farmer information
                            </h3>

                        </div>

                    </div>

                    <div class="farmer-edit-profile-grid">

                        <div class="farmer-edit-profile-field">

                            <label for="stall_name">
                                Business / Stall Name
                            </label>

                            <input
                                type="text"
                                id="stall_name"
                                name="stall_name"
                                value="<?= e($farmer['stall_name'] ?? '') ?>"
                            >

                        </div>

                        <div class="farmer-edit-profile-field">

                            <label for="contact_person">
                                Contact Person
                            </label>

                            <input
                                type="text"
                                id="contact_person"
                                name="contact_person"
                                value="<?= e($farmer['contact_person'] ?? '') ?>"
                            >

                        </div>

                        <div class="farmer-edit-profile-field farmer-edit-profile-field-full">

                            <label for="description">
                                Description
                            </label>

                            <textarea
                                name="description"
                                id="description"
                                rows="5"
                            ><?= e($farmer['description'] ?? '') ?></textarea>

                        </div>

                        <div class="farmer-edit-profile-field farmer-edit-profile-field-full">

                            <label for="farmer_address">
                                Farmer Address
                            </label>

                            <input
                                type="text"
                                id="farmer_address"
                                name="farmer_address"
                                value="<?= e($farmer['farmer_address'] ?? '') ?>"
                            >

                        </div>

                    </div>

                </section>

                <section class="farmer-edit-profile-card">

                    <div class="farmer-edit-profile-card-heading">

                        <div class="farmer-edit-profile-card-icon">
                            <i data-lucide="store"></i>
                        </div>

                        <div>

                            <span class="farmer-edit-profile-card-number">
                                04
                            </span>

                            <h3>
                                Markets
                            </h3>

                        </div>

                    </div>

                    <div class="farmer-edit-profile-markets">

                        <label>
                            Markets where you sell
                        </label>

                        <div class="farmer-edit-profile-market-list">

                            <?php foreach ($markets as $market): ?>

                                <?php $market_id = (int) $market['id']; ?>

                                <label class="farmer-edit-profile-market-option">

                                    <input
                                        type="checkbox"
                                        name="market_ids[]"
                                        value="<?= $market_id ?>"
                                        <?= in_array(
                                            $market_id,
                                            $selected_markets,
                                            true
                                        ) ? 'checked' : '' ?>
                                    >

                                    <span class="farmer-edit-profile-market-check">
                                        <i data-lucide="check"></i>
                                    </span>

                                    <span class="farmer-edit-profile-market-info">

                                        <strong>
                                            <?= e($market['name']) ?>
                                        </strong>

                                        <?php if (!empty($market['address'])): ?>

                                            <small>
                                                <?= e($market['address']) ?>
                                            </small>

                                        <?php endif; ?>

                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                        <small>
                            Select all markets where you currently sell.
                        </small>

                    </div>

                </section>

                <section class="farmer-edit-profile-card">

                    <div class="farmer-edit-profile-card-heading">

                        <div class="farmer-edit-profile-card-icon">
                            <i data-lucide="map-pin"></i>
                        </div>

                        <div>

                            <span class="farmer-edit-profile-card-number">
                                05
                            </span>

                            <h3>
                                Farmer location
                            </h3>

                        </div>

                    </div>

                    <div class="farmer-edit-profile-location">

                        <div class="farmer-edit-profile-location-intro">

                            <span>
                                Stall location
                            </span>

                            <p>
                                Click on the map to select your stall location.
                            </p>

                        </div>

                        <div
                            id="map"
                            class="farmer-edit-profile-map"
                        ></div>

                        <div class="farmer-edit-profile-grid">

                            <div class="farmer-edit-profile-field">

                                <label for="latitude">
                                    Latitude
                                </label>

                                <input
                                    type="text"
                                    id="latitude"
                                    name="latitude"
                                    value="<?= e($farmer['latitude'] ?? '') ?>"
                                >

                            </div>

                            <div class="farmer-edit-profile-field">

                                <label for="longitude">
                                    Longitude
                                </label>

                                <input
                                    type="text"
                                    id="longitude"
                                    name="longitude"
                                    value="<?= e($farmer['longitude'] ?? '') ?>"
                                >

                            </div>

                        </div>

                    </div>

                </section>

                <div class="farmer-edit-profile-actions">

                    <button
                        type="submit"
                        class="farmer-edit-profile-save"
                    >
                        <i data-lucide="save"></i>
                        Save Changes
                    </button>

                    <a
                        href="profile.php"
                        class="farmer-edit-profile-cancel"
                    >
                        <i data-lucide="x"></i>
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

    <script src="../assets/js/lucide.js"></script>

    <script>
        lucide.createIcons();
    </script>

    <script>
        window.farmerLocation = {
            latitude: <?= $farmer['latitude'] !== null
                ? (float) $farmer['latitude']
                : 15.3694 ?>,
            longitude: <?= $farmer['longitude'] !== null
                ? (float) $farmer['longitude']
                : 44.1910 ?>
        };
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script src="../assets/js/leaflet.js"></script>

    <script>
        const profileImageInput =
            document.getElementById('profile_image');

        const profileImageName =
            document.getElementById('profile-image-name');

        if (profileImageInput && profileImageName) {
            profileImageInput.addEventListener(
                'change',
                function () {
                    if (this.files.length > 0) {
                        profileImageName.textContent =
                            this.files[0].name;
                    } else {
                        profileImageName.textContent =
                            'No new image selected';
                    }
                }
            );
        }
    </script>

</body>

</html>