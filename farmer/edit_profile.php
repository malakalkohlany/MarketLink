<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

if(!isset($_SESSION['user_id'])){
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $stall_name = $_POST['stall_name'];
    $contact_person = $_POST['contact_person'];
    $description = $_POST['description'];
    $farmer_address = $_POST['farmer_address'];

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
         address = ?
         WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssi",
    $stall_name,
    $contact_person,
    $description,
    $farmer_address,
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
</head>
<body>
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
     </section>
     <button type="submit">Save Changes</button>
     <button type="button" onclick="window.location.href='profile.php'">Cancel</button>
     </form>
</body>
</html>