<?php

require_once __DIR__ . '/../includes/include.php';

$user_id = getUserId();

$stmt = $conn->prepare("
    SELECT id
    FROM farmers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

if (!$farmer) {
    die("Farmer account not found.");
}

$farmer_id = $farmer['id'];

$stmt->close();
$market_stmt = $conn->prepare("
    SELECT
        id,
        name
    FROM markets
    ORDER BY name ASC
");

$market_stmt->execute();

$markets = $market_stmt->get_result();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $market_id = (int) $_POST['market_id'];
    $day_of_week = trim($_POST['day_of_week']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $cutoff_time = $_POST['cutoff_time'];
    $max_orders = (int) $_POST['max_orders'];

    if (
        $market_id <= 0 ||
        empty($day_of_week) ||
        empty($start_time) ||
        empty($end_time) ||
        empty($cutoff_time) ||
        $max_orders <= 0
    ) {
        die("Please enter valid pickup slot information.");
    }

    $stmt = $conn->prepare("
        INSERT INTO pickup_slots
        (
            farmer_id,
            market_id,
            day_of_week,
            start_time,
            end_time,
            cutoff_time,
            max_orders
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iissssi",
        $farmer_id,
        $market_id,
        $day_of_week,
        $start_time,
        $end_time,
        $cutoff_time,
        $max_orders
    );

    if (!$stmt->execute()) {
        die("Insert failed: " . $stmt->error);
    }

    $stmt->close();

    $success_message = "Pickup slot added successfully.";
}
$slot_stmt = $conn->prepare("
    SELECT
        pickup_slots.id,
        pickup_slots.day_of_week,
        pickup_slots.start_time,
        pickup_slots.end_time,
        pickup_slots.cutoff_time,
        pickup_slots.max_orders,
        pickup_slots.is_available,
        markets.name AS market_name
    FROM pickup_slots
    INNER JOIN markets
        ON pickup_slots.market_id = markets.id
    WHERE pickup_slots.farmer_id = ?
    ORDER BY pickup_slots.day_of_week ASC, pickup_slots.start_time ASC
");

$slot_stmt->bind_param("i", $farmer_id);
$slot_stmt->execute();

$slots = $slot_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Slots</title>
</head>
<body>
    <h1>Pickup Slots</h1>

    <?php if (isset($success_message)): ?>
        <p><?= e($success_message) ?></p>
    <?php endif; ?>
    
    <h2>Add Pickup Slots</h2>

    <form method="POST">
        <label for="market_id">Market</label>
        <select name="market_id" id="market_id" required>
        <option value="">Select Market</option>
        <?php while ($market = $markets->fetch_assoc()): ?>
            <option value="<?= e($market['id']) ?>">
                <?= e($market['name']) ?>
            </option>
        <?php endwhile; ?>   
    </select>
    <br><br>
    
    <label for="day_of_week">Day</label>
    <select name="day_of_week" id="day_of_week" required>
            <option value="">Select Day</option>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
    </select>
    <br><br>

    <label for="start_time">Start Time</label>
    <input type="time" name="start_time" id="start_time" required>
    <br><br>

    <label for="end_time">End Time</label>
    <input type="time" name="end_time" id="end_time" required>
    <br><br>
    
    <label for="cutoff_time">Cutoff Time</label>
    <input type="time" name="cutoff_time" id="cutoff_time" required>
    <br><br>
    
    <label for="max_orders">Maximun Orders</label>
    <input type="number" name="max_orders" id="max_orders" min="1" required>
    <br><br>    

    <button type="submit">Add Pickup Slot</button>
    </form>

    <h2>My Pickup Slot</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Market</th>
                <th>Day</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Cutoff Time</th>
                <th>Maximun Orders</th>
                <th>Availability</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($slot = $slots->fetch_assoc()): ?>
             <tr>
                <td><?= e($slot['market_name']) ?></td>
                <td><?= e($slot['day_of_week']) ?></td>
                <td><?= e($slot['start_time']) ?></td>
                <td><?= e($slot['end_time']) ?></td>
                <td><?= e($slot['cutoff_time']) ?></td>
                <td><?= e($slot['max_orders']) ?></td>
                <td>
                    <?php if ($slot['is_available']): ?>
                        Available
                    <?php else: ?>
                        Unavailable
                    <?php endif; ?>                    
                </td>
             </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>