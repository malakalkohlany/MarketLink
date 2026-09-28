<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);

requireApprovedFarmer();

$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and clean form data
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

    // Profile Image Upload
    if (
        isset($_FILES['profile_image']) &&
        $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            die("Failed to upload profile image.");
        }

        $image = $_FILES['profile_image'];

        // Maximum size: 2 MB
        if ($image['size'] > 2 * 1024 * 1024) {
            die("Profile image must be less than 2 MB.");
        }

        // Verify that the uploaded file is a real image
        $image_info = getimagesize($image['tmp_name']);

        if ($image_info === false) {
            die("Invalid image file.");
        }

        // Allowed image types
        $allowed_types = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_WEBP
        ];

        if (!in_array($image_info[2], $allowed_types, true)) {
            die("Only JPG, PNG, and WEBP images are allowed.");
        }

        // Get the correct extension
        $extensions = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp'
        ];

        $extension = $extensions[$image_info[2]];

        // Farmer images folder
        $upload_dir = __DIR__ . '/../assets/images/farmers/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Use the logged-in user's ID as the image name
        $file_name = 'farmer_' . $user_id . '.' . $extension;

        $upload_path = $upload_dir . $file_name;

        // Remove old profile image with another extension
        foreach (['jpg', 'png', 'webp'] as $old_extension) {

            $old_file = $upload_dir . 'farmer_' . $user_id . '.' . $old_extension;

            if ($old_file !== $upload_path && file_exists($old_file)) {
                unlink($old_file);
            }
        }

        // Save new profile image
        if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
            die("Failed to save profile image.");
        }
    }

    // Update user information
    $sql = "UPDATE users
            SET name = ?, phone = ?, email = ?, address = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Failed to prepare user update: " . $conn->error);
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
        die("Failed to update user information: " . $stmt->error);
    }

    $stmt->close();

    // Update farmer information
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
        die("Failed to prepare farmer update: " . $conn->error);
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
        die("Failed to update farmer information: " . $stmt->error);
    }

    $stmt->close();

    header("Location: profile.php");
    exit;
}

// Load current farmer information
$sql = "SELECT
            users.name,
            users.email,
            users.phone,
            users.address,
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
    die("Failed to prepare farmer profile query: " . $conn->error);
}

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$farmer = $result->fetch_assoc();

$stmt->close();

?>
<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <h1>Edit Profile</h1>
        <form action="edit_profile.php" method="post" enctype="multipart/form-data">
        <section>
            <h2>Profile Image</h2>
            <div>
                <label for="profile_image">Profile Image</label>
                <input
                    type="file"
                    id="profile_image"
                    name="profile_image"
                    accept=".jpg,.jpeg,.png,.webp"
                >
                <small>Allowed formats: JPG, PNG, WEBP. Maximum size: 2 MB.</small>
            </div>
        </section>
        <section>
            <h2>Personal Information</h2>
            <div>
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                value="<?php echo htmlspecialchars($farmer['name']); ?>">
            </div>
            <div>
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                value="<?php echo htmlspecialchars($farmer['email']); ?>">
            </div>  
            <div>
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone"
                value="<?php echo htmlspecialchars($farmer['phone']); ?>">
            </div>        
            <div>
                <label for="address">Address</label>
                <input type="text" id="address" name="address"
                value="<?php echo htmlspecialchars($farmer['address']); ?>">
            </div>
        </section>
        <section>
            <h2>Farmer Information</h2>
            <div>
            <label for="stall_name">Business / Stall Name</label>
            <input
                type="text"
                id="stall_name"
                name="stall_name"
                value="<?php echo htmlspecialchars($farmer['stall_name']); ?>">
            </div>    
            <div>
            <label for="contact_person">Contact Person</label>
            <input
                type="text"
                id="contact_person"
                name="contact_person"
                value="<?php echo htmlspecialchars($farmer['contact_person']); ?>">
            </div>   
            <div>
            <label for="description">Description</label>
            <textarea
            name="description"
            id="description"><?php echo htmlspecialchars($farmer['description']);?></textarea>
            </div> 
            <div>
            <label for="farmer_address">Farmer Address</label>
            <input
                type="text"
                id="farmer_address"
                name="farmer_address"
                value="<?php echo htmlspecialchars($farmer['farmer_address']); ?>">
            </div>
            <div>
                <label>Farmer Location</label>
                <p>Click on the map to select your stall location.</p>
                <div id="map" style="width: 100%; height: 400px;"></div>
            </div>
            <div>
                <label for="latitude">Latitude</label>
                <input
                    type="text"
                    id="latitude"
                    name="latitude"
                    value="<?php echo htmlspecialchars($farmer['latitude'] ?? ''); ?>">
            </div>
            <div>
                <label for="longitude">Longitude</label>
                <input
                    type="text"
                    id="longitude"
                    name="longitude"
                    value="<?php echo htmlspecialchars($farmer['longitude'] ?? ''); ?>">
            </div>
        </section>
        <button type="submit">Save Changes</button>
        <button type="button" onclick="window.location.href='profile.php'">Cancel</button>
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