<?php

require_once __DIR__ . '/../includes/include.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'count' => 0
    ]);

    exit;
}

$userId = getUserId();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
");

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'count' => 0
    ]);

    exit;
}

$stmt->bind_param('i', $userId);
$stmt->execute();

$stmt->bind_result($count);
$stmt->fetch();

$stmt->close();

echo json_encode([
    'success' => true,
    'count' => (int) $count
]);