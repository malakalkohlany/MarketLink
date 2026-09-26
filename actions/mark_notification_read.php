<?php

require_once __DIR__ . '/../includes/include.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in.'
    ]);

    exit;
}

$userId = (int) getUserId();
$notificationId = isset($_POST['notification_id'])
    ? (int) $_POST['notification_id']
    : 0;

if ($notificationId <= 0) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification ID.'
    ]);

    exit;
}

/*
 * IMPORTANT:
 * The user_id condition prevents one user from marking
 * another user's notification as read.
 */
$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = ?
      AND user_id = ?
");

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare notification update.'
    ]);

    exit;
}

$stmt->bind_param(
    'ii',
    $notificationId,
    $userId
);

if ($stmt->execute()) {

    echo json_encode([
        'success' => true
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to mark notification as read.'
    ]);
}

$stmt->close();