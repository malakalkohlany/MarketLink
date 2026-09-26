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

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE user_id = ?
      AND is_read = 0
");

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare notification update.'
    ]);

    exit;
}

$stmt->bind_param('i', $userId);

if ($stmt->execute()) {

    echo json_encode([
        'success' => true,
        'updated' => $stmt->affected_rows
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to mark notifications as read.'
    ]);
}

$stmt->close();