<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';



if(!isset($_SESSION['user_id'])){
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT 
            users.name,
            users.email,
            users.phone,
            users.address,
            users.role,
            users.status,
            users.created_at,
            farmers.stall_name,
            farmers.contact_person,
            farmers.description,
            farmers.address AS farmer_address,
            farmers.latitude,
            farmers.longitude,
            farmers.approval_status
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
    <title>farmer Profile | MarkeLine</title>
</head>
<body>
     <h1>farmer Profile</h1>
     <a href="edit_profile.php">Edit Profile</a>

     <section>
        <h2>Personal Information</h2>
        <p>
            <strong>Full Name:</strong>
            <?= htmlspecialchars($farmer['name'])?>
        </p>
         <p>
            <strong>Email:</strong>
            <?= htmlspecialchars($farmer['email'])?>
        </p>
         <p>
            <strong>Phone Number:</strong>
            <?= htmlspecialchars($farmer['phone'])?>
        </p>
         <p>
            <strong>Address:</strong>
            <?= htmlspecialchars($farmer['address'])?>
        </p>
     </section>

     <section>
        <h2>Farmer Information</h2>
        <p>
            <strong>Business / Stall Name:</strong>
            <?= htmlspecialchars($farmer['stall_name']) ?>
        </p>
        <p>
            <strong>contact Person:</strong>
            <?= htmlspecialchars($farmer['contact_person']) ?>
        </p>
        <p>
            <strong>Description:</strong>
            <?= htmlspecialchars($farmer['description']) ?>
        </p>
        <p>
            <strong>Farmer Address:</strong>
            <?= htmlspecialchars($farmer['farmer_address']) ?>
        </p>
     </section>
     <section>
        <h2>Account Information</h2>

        <p>
        <strong>Role:</strong>
        <?php echo htmlspecialchars($farmer['role']); ?>
        </p>

        <p>
        <strong>Account Status:</strong>
        <?php echo htmlspecialchars($farmer['status']); ?>
        </p>

        <p> 
        <strong>Approval Status:</strong>
        <?php echo htmlspecialchars($farmer['approval_status']); ?>
        </p>

        <p>
        <strong>Created At:</strong>
        <?php echo htmlspecialchars($farmer['created_at']); ?>
        </p>
     </section>

</body>
</html>
