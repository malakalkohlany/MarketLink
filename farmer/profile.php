<?php

require_once __DIR__ . '/../includes/include.php';


$user_id = getUserId();

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
    <title>farmer Profile | MarketLine</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>
    
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="profile-container">

            <div class="profile-card">

                <h1>farmer Profile</h1>
                <a href="edit_profile.php">Edit Profile</a>

                <section>
                    <h2>Personal Information</h2>
                    <p>
                        <strong>Full Name:</strong>
                        <?= e($farmer['name'])?>
                    </p>
                    <p>
                        <strong>Email:</strong>
                        <?= e($farmer['email'])?>
                    </p>
                    <p>
                        <strong>Phone Number:</strong>
                        <?= e($farmer['phone'])?>
                    </p>
                    <p>
                        <strong>Address:</strong>
                        <?= e($farmer['address'])?>
                    </p>
                </section>

                <section>
                    <h2>Farmer Information</h2>
                    <p>
                        <strong>Business / Stall Name:</strong>
                        <?= e($farmer['stall_name']) ?>
                    </p>
                    <p>
                        <strong>contact Person:</strong>
                        <?= e($farmer['contact_person']) ?>
                    </p>
                    <p>
                        <strong>Description:</strong>
                        <?= e($farmer['description']) ?>
                    </p>
                    <p>
                        <strong>Farmer Address:</strong>
                        <?= e($farmer['farmer_address']) ?>
                    </p>
                </section>
                <section>
                    <h2>Account Information</h2>

                    <p>
                    <strong>Role:</strong>
                    <?php echo e($farmer['role']); ?>
                    </p>

                    <p>
                    <strong>Account Status:</strong>
                    <?php echo e($farmer['status']); ?>
                    </p>

                    <p> 
                    <strong>Approval Status:</strong>
                    <?php echo e($farmer['approval_status']); ?>
                    </p>

                    <p>
                    <strong>Created At:</strong>
                    <?php echo e($farmer['created_at']); ?>
                    </p>
                </section>
            </div>
        </div>
    </main>

</body>
</html>
