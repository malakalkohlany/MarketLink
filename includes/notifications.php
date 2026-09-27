<?php

function createNotification(
    mysqli $conn,
    int $userId,
    string $type,
    string $title,
    string $message
): bool {
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            user_id,
            type,
            title,
            message
        )
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "isss",
        $userId,
        $type,
        $title,
        $message
    );

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}


function sendWeeklyStockReminders(
    mysqli $conn,
    string $weekStart
): void {
    
    $stmt = $conn->prepare("
        SELECT DISTINCT
            f.user_id
        FROM farmers f
        INNER JOIN weekly_stock_templates wst
            ON wst.farmer_id = f.id
        WHERE f.approval_status = 'approved'
          AND wst.is_active = 1
    ");

    if (!$stmt) {
        return;
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return;
    }

    $result = $stmt->get_result();

    $checkStmt = $conn->prepare("
        SELECT id
        FROM notifications
        WHERE user_id = ?
          AND type = 'weekly_stock_reminder'
          AND created_at >= ?
        LIMIT 1
    ");

    if (!$checkStmt) {
        $stmt->close();
        return;
    }

    $title = 'Weekly Stock Reminder';

    $message =
        "It's a new week! Please review and update your "
        . "weekly stock so customers know what's available.";

    while ($row = $result->fetch_assoc()) {

        $userId = (int) $row['user_id'];

        $checkStmt->bind_param(
            'is',
            $userId,
            $weekStart
        );

        if (!$checkStmt->execute()) {
            continue;
        }

        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            continue;
        }

        createNotification(
            $conn,
            $userId,
            'weekly_stock_reminder',
            $title,
            $message
        );
    }

    $checkStmt->close();
    $stmt->close();
}

function notifyWeeklyStockUpdated(
    mysqli $conn,
    int $farmerId,
    string $weekStart
): void {
    /*
     * Get the farmer's display name.
     */
    $farmerStmt = $conn->prepare("
        SELECT stall_name
        FROM farmers
        WHERE id = ?
        LIMIT 1
    ");

    if (!$farmerStmt) {
        return;
    }

    $farmerStmt->bind_param('i', $farmerId);

    if (!$farmerStmt->execute()) {
        $farmerStmt->close();
        return;
    }

    $farmerResult = $farmerStmt->get_result();
    $farmer = $farmerResult->fetch_assoc();

    $farmerStmt->close();

    if (!$farmer) {
        return;
    }

    $farmerName = trim((string) $farmer['stall_name']);

    if ($farmerName === '') {
        $farmerName = 'A favorite farmer';
    }

    
    $recipientStmt = $conn->prepare("
        SELECT user_id
        FROM fav_farmers
        WHERE farmer_id = ?

        UNION

        SELECT fm.user_id
        FROM fav_markets fm
        INNER JOIN market_farmer mf
            ON mf.market_id = fm.market_id
        WHERE mf.farmer_id = ?
    ");

    if (!$recipientStmt) {
        return;
    }

    $recipientStmt->bind_param(
        'ii',
        $farmerId,
        $farmerId
    );

    if (!$recipientStmt->execute()) {
        $recipientStmt->close();
        return;
    }

    $recipients = $recipientStmt->get_result();

    /*
     * Prepare duplicate check once.
     */
    $checkStmt = $conn->prepare("
        SELECT id
        FROM notifications
        WHERE user_id = ?
          AND type = 'weekly_stock_updated'
          AND title = ?
          AND message = ?
          AND created_at >= ?
        LIMIT 1
    ");

    if (!$checkStmt) {
        $recipientStmt->close();
        return;
    }

    $title = 'Weekly Stock Updated';

    $message =
        $farmerName .
        ' has updated their stock for this week. '
        . 'Check what\'s available now.';

    while ($recipient = $recipients->fetch_assoc()) {

        $userId = (int) $recipient['user_id'];

        $checkStmt->bind_param(
            'isss',
            $userId,
            $title,
            $message,
            $weekStart
        );

        if (!$checkStmt->execute()) {
            continue;
        }

        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            continue;
        }

        createNotification(
            $conn,
            $userId,
            'weekly_stock_updated',
            $title,
            $message
        );
    }

    $checkStmt->close();
    $recipientStmt->close();
}