<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['notification_id'])) {

        $notification_id = (int) $_POST['notification_id'];

        $stmt = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
              AND user_id = ?
        ");

        $stmt->bind_param(
            "ii",
            $notification_id,
            $user_id
        );

        $stmt->execute();
        $stmt->close();

    } elseif (isset($_POST['mark_all_read'])) {

        $stmt = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND is_read = 0
        ");

        $stmt->bind_param("i", $user_id);

        $stmt->execute();
        $stmt->close();
    }

    header("Location: notifications.php");
    exit;
}


$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
");

$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();

$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();

$unread_count = (int) $count_data['unread_count'];

$count_stmt->close();


$notification_stmt = $conn->prepare("
    SELECT
        id,
        type,
        title,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$notification_stmt->bind_param("i", $user_id);
$notification_stmt->execute();

$notifications = $notification_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
</head>
<body>
      <h1>Notifications</h1>

      <p>Unread Notifications:<?= e($unread_count) ?></p>
      <?php if ($unread_count > 0): ?>
        <form method="POST">
            <button type="submit" name="mark_all_read">Mark All as Read</button>
        </form>
       <?php endif; ?> 
       <br>

       <?php if ($notifications->num_rows === 0): ?>
        <p>No notifications found.</p>
       <?php endif; ?> 

       <?php while ($notification = $notifications->fetch_assoc()): ?>
        <div>
            <h2><?= e($notification['title']) ?></h2>
            <p><?= e($notification['message']) ?></p>
            <p>Type:<?= e($notification['type']) ?></p>
            <p>Date:<?= formatDateTime($notification['created_at']) ?></p>

            <?php if ($notification['is_read'] == 0): ?>

                <strong>Unread</strong>
                <form method="POST">
                    <input type="hidden" name="notification_id"
                     value="<?= e($notification['id']) ?>">

                <button type="submit">Mark as Read</button>     
                </form>
            <?php else: ?>
                <span>Read</span>
            <?php endif; ?>    

        </div>
        <?php endwhile; ?>
</body>
</html>