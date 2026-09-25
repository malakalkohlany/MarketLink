<?php

require_once __DIR__ . '/../includes/include.php';

$user_id =getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $stall_name = $_POST['stall_name'];
    $contact_person = $_POST['contact_person'];
    $description = $_POST['description'];
    $farmer_address = $_POST['farmer_address'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

$sql = "UPDATE users
        SET name = ?, phone = ?, email = ?, address = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $name, $phone, $email, $address, $user_id);
$stmt->execute();

$sql = "UPDATE farmers
         SET stall_name = ?,
         contact_person = ?,
         description = ?,
         address = ?,
         latitude = ?,
         longitude = ?
         WHERE user_id = ?";

$stmt = $conn->prepare($sql);
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
if ($stmt->execute()){
    header("location: profile.php");
    exit;
}}

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
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile </title>
    <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>



    <main class="main-content">
        <h1>Edit Profile</h1>
        <form action="edit_profile.php" method="post">
        <section>
            <h2>Personal Information</h2>
            <div>
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                value="<?php echo htmlspecialchars($farmer['name']) ?>">
            </div>

            <div>
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                value="<?php echo htmlspecialchars($farmer['email']) ?>">
            </div>  

            <div>
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone"
                value="<?php echo htmlspecialchars($farmer['phone']) ?>">
            </div>        

            <div>
                <label for="address">Address</label>
                <input type="text" id="address" name="address"
                value="<?php echo htmlspecialchars($farmer['address']) ?>">
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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const savedLatitude = <?php echo $farmer['latitude'] !== null ? $farmer['latitude'] : 15.3694; ?>;
    const savedLongitude = <?php echo $farmer['longitude'] !== null ? $farmer['longitude'] : 44.1910; ?>;

    const map = L.map('map').setView(
        [savedLatitude, savedLongitude],
        13
    );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    let marker = L.marker([
        savedLatitude,
        savedLongitude
    ]).addTo(map);

    map.on('click', function(event) {

        const latitude = event.latlng.lat;
        const longitude = event.latlng.lng;

        if (marker) {
            marker.setLatLng([
                latitude,
                longitude
            ]);
        } else {
            marker = L.marker([
                latitude,
                longitude
            ]).addTo(map);
        }

        document.getElementById('latitude').value =
            latitude.toFixed(8);

        document.getElementById('longitude').value =
            longitude.toFixed(8);
    });
</script>

</body>
</html>