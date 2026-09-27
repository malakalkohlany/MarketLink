<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$admin_id = getUserId();

$errors = [];
$success = '';
$announcements = [];


/*
 * Add announcement
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_announcement'])
) {

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $expires_at = trim($_POST['expires_at'] ?? '');

    if ($title === '') {
        $errors[] = 'Announcement title is required.';
    }

    if ($message === '') {
        $errors[] = 'Announcement message is required.';
    }

    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        $errors[] = 'Invalid announcement status.';
    }

    if ($expires_at !== '') {

        $date = DateTime::createFromFormat(
            'Y-m-d\TH:i',
            $expires_at
        );

        if (
            !$date ||
            $date->format('Y-m-d\TH:i') !== $expires_at
        ) {
            $errors[] = 'Invalid expiration date.';
        }
    }

    if (empty($errors)) {

        $expires_value = $expires_at !== ''
            ? str_replace('T', ' ', $expires_at) . ':00'
            : null;

        $conn->begin_transaction();

        try {

            /*
             * Insert announcement
             */
            $stmt = $conn->prepare("
                INSERT INTO announcements
                (
                    admin_id,
                    title,
                    message,
                    status,
                    expires_at
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception('Failed to prepare announcement.');
            }

            $stmt->bind_param(
                'issss',
                $admin_id,
                $title,
                $message,
                $status,
                $expires_value
            );

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Failed to add announcement.');
            }

            $stmt->close();


            /*
             * If the announcement is published immediately,
             * create notifications for customers and farmers.
             */
            if ($status === 'published') {

                $notification_stmt = $conn->prepare("
                    INSERT INTO notifications
                    (
                        user_id,
                        type,
                        title,
                        message
                    )
                    SELECT
                        id,
                        'announcement',
                        ?,
                        ?
                    FROM users
                    WHERE role IN ('customer', 'farmer')
                ");

                if (!$notification_stmt) {
                    throw new Exception(
                        'Failed to prepare announcement notifications.'
                    );
                }

                $notification_stmt->bind_param(
                    'ss',
                    $title,
                    $message
                );

                if (!$notification_stmt->execute()) {
                    $notification_stmt->close();

                    throw new Exception(
                        'Failed to create announcement notifications.'
                    );
                }

                $notification_stmt->close();
            }


            $conn->commit();

            $success = 'Announcement added successfully.';
            $_POST = [];

        } catch (Exception $e) {

            $conn->rollback();

            $errors[] = $e->getMessage();
        }
    }
}


/*
 * Update announcement status
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $announcement_id = filter_input(
        INPUT_POST,
        'id',
        FILTER_VALIDATE_INT
    );

    $new_status = $_POST['status'] ?? '';

    if (!$announcement_id) {
        $errors[] = 'Invalid announcement.';
    }

    if (!in_array(
        $new_status,
        ['draft', 'published', 'archived'],
        true
    )) {
        $errors[] = 'Invalid announcement status.';
    }

    if (empty($errors)) {

        /*
         * Get the current announcement first.
         * We need the old status to know whether
         * this is a new publication.
         */
        $stmt = $conn->prepare("
            SELECT
                status,
                title,
                message
            FROM announcements
            WHERE id = ?
            AND admin_id = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                'ii',
                $announcement_id,
                $admin_id
            );

            $stmt->execute();

            $result = $stmt->get_result();
            $announcement = $result->fetch_assoc();

            $stmt->close();

            if (!$announcement) {

                $errors[] = 'Announcement not found.';

            } else {

                $old_status = $announcement['status'];

                /*
                 * Update status
                 */
                $stmt = $conn->prepare("
                    UPDATE announcements
                    SET status = ?
                    WHERE id = ?
                    AND admin_id = ?
                ");

                if ($stmt) {

                    $stmt->bind_param(
                        'sii',
                        $new_status,
                        $announcement_id,
                        $admin_id
                    );

                    if ($stmt->execute()) {

                        $stmt->close();

                        /*
                         * Create notifications only when
                         * the announcement becomes published.
                         *
                         * This prevents duplicate notifications
                         * when the status is already published.
                         */
                        if (
                            $new_status === 'published' &&
                            $old_status !== 'published'
                        ) {

                            $notification_stmt = $conn->prepare("
                                INSERT INTO notifications
                                (
                                    user_id,
                                    type,
                                    title,
                                    message
                                )
                                SELECT
                                    id,
                                    'announcement',
                                    ?,
                                    ?
                                FROM users
                                WHERE role IN ('customer', 'farmer')
                            ");

                            if ($notification_stmt) {

                                $notification_stmt->bind_param(
                                    'ss',
                                    $announcement['title'],
                                    $announcement['message']
                                );

                                $notification_stmt->execute();

                                $notification_stmt->close();
                            }
                        }

                        header('Location: announcements.php');
                        exit;

                    } else {

                        $errors[] =
                            'Failed to update announcement.';

                        $stmt->close();
                    }

                } else {

                    $errors[] =
                        'Failed to prepare announcement update.';
                }
            }

        } else {

            $errors[] = 'Failed to load announcement.';
        }
    }
}


/*
 * Delete announcement
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_announcement'])
) {

    $announcement_id = filter_input(
        INPUT_POST,
        'id',
        FILTER_VALIDATE_INT
    );

    if (!$announcement_id) {
        $errors[] = 'Invalid announcement.';
    }

    if (empty($errors)) {

        $stmt = $conn->prepare("
            DELETE FROM announcements
            WHERE id = ?
            AND admin_id = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                'ii',
                $announcement_id,
                $admin_id
            );

            if ($stmt->execute()) {

                $stmt->close();

                header('Location: announcements.php');
                exit;

            } else {

                $errors[] =
                    'Failed to delete announcement.';

                $stmt->close();
            }

        } else {

            $errors[] =
                'Failed to prepare announcement deletion.';
        }
    }
}


/*
 * Load announcements
 */
$result = $conn->query("
    SELECT
        a.id,
        a.admin_id,
        a.title,
        a.message,
        a.status,
        a.created_at,
        a.expires_at,
        u.name AS admin_name
    FROM announcements a
    INNER JOIN users u
        ON a.admin_id = u.id
    ORDER BY a.created_at DESC
");

if ($result) {
    $announcements = $result->fetch_all(MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Announcements | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

</head>

<body>


    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">

                <div class="page-header">

                    <div>

                        <h1>
                            Announcements
                        </h1>

                        <p>
                            Manage announcements for users.
                        </p>

                    </div>

                </div>

                <?php if (!empty($errors)): ?>

                    <div class="alert alert-danger">

                        <?php foreach ($errors as $error): ?>

                            <p>
                                <?= htmlspecialchars($error) ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <?php if ($success !== ''): ?>

                    <div class="alert alert-success">

                        <?= htmlspecialchars($success) ?>

                    </div>

                <?php endif; ?>

                <section class="form-section">

                    <div class="section-header">

                        <h2>
                            Add Announcement
                        </h2>

                    </div>

                    <form
                        method="POST"
                        action="announcements.php"
                    >

                        <div class="form-group">

                            <label for="title">
                                Title
                            </label>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                maxlength="150"
                                value="<?= htmlspecialchars(
                                    $_POST['title'] ?? ''
                                ) ?>"
                                required
                            >
                        </div>

                        <div class="form-group">

                            <label for="message">
                                Message
                            </label>

                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                required
                            ><?= htmlspecialchars(
                                $_POST['message'] ?? ''
                            ) ?></textarea>

                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="status">
                                    Status
                                </label>

                                <select
                                    id="status"
                                    name="status"
                                >

                                    <option
                                        value="draft"
                                        <?= ($_POST['status'] ?? 'draft') === 'draft'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Draft
                                    </option>

                                    <option
                                        value="published"
                                        <?= ($_POST['status'] ?? '') === 'published'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Published
                                    </option>

                                    <option
                                        value="archived"
                                        <?= ($_POST['status'] ?? '') === 'archived'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Archived
                                    </option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label for="expires_at">
                                    Expiration Date
                                </label>

                                <input
                                    type="datetime-local"
                                    id="expires_at"
                                    name="expires_at"
                                    value="<?= htmlspecialchars(
                                        $_POST['expires_at'] ?? ''
                                    ) ?>"
                                >

                            </div>

                        </div>

                        <div class="form-actions">

                            <button
                                type="submit"
                                name="add_announcement"
                                class="btn btn-primary"
                            >
                                Add Announcement
                            </button>

                        </div>

                    </form>

                </section>

                <section class="table-section">

                    <div class="section-header">

                        <h2>
                            All Announcements
                        </h2>

                    </div>

                        <div class="table-responsive">

                        <table class="data-table">

                            <thead>

                                <tr>

                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Expires At</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php if (!empty($announcements)): ?>

                                    <?php foreach ($announcements as $announcement): ?>

                                        <tr>

                                            <td>
                                                <?= (int) $announcement['id'] ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $announcement['title']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $announcement['message']
                                                ) ?>
                                            </td>

                                            <td>

                                                <span
                                                    class="status status-<?= htmlspecialchars(
                                                        $announcement['status']
                                                    ) ?>"
                                                >
                                                    <?= ucfirst(
                                                        $announcement['status']
                                                    ) ?>
                                                </span>

                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $announcement['admin_name']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= date(
                                                    'Y-m-d H:i',
                                                    strtotime(
                                                        $announcement['created_at']
                                                    )
                                                ) ?>
                                            </td>

                                            <td>

                                                <?php if (
                                                    !empty($announcement['expires_at'])
                                                ): ?>

                                                    <?= date(
                                                        'Y-m-d H:i',
                                                        strtotime(
                                                            $announcement['expires_at']
                                                        )
                                                    ) ?>

                                                <?php else: ?>

                                                    No expiration

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <div class="action-buttons">

                                                    <?php if (
                                                        $announcement['status'] !== 'published'
                                                    ): ?>

                                                        <form
                                                            method="POST"
                                                            action="announcements.php"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= (int) $announcement['id'] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="status"
                                                                value="published"
                                                            >

                                                            <button
                                                                type="submit"
                                                                name="update_status"
                                                                class="btn btn-primary"
                                                            >
                                                                Publish
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                    <?php if (
                                                        $announcement['status'] !== 'archived'
                                                    ): ?>

                                                        <form
                                                            method="POST"
                                                            action="announcements.php"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= (int) $announcement['id'] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="status"
                                                                value="archived"
                                                            >

                                                            <button
                                                                type="submit"
                                                                name="update_status"
                                                                class="btn btn-secondary"
                                                            >
                                                                Archive
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                    <?php if (
                                                        $announcement['status'] !== 'draft'
                                                    ): ?>

                                                        <form
                                                            method="POST"
                                                            action="announcements.php"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= (int) $announcement['id'] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="status"
                                                                value="draft"
                                                            >

                                                            <button
                                                                type="submit"
                                                                name="update_status"
                                                                class="btn btn-secondary"
                                                            >
                                                                Draft
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                    <form
                                                        method="POST"
                                                        action="announcements.php"
                                                        onsubmit="return confirm('Are you sure you want to delete this announcement?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= (int) $announcement['id'] ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            name="delete_announcement"
                                                            class="btn btn-danger"
                                                        >
                                                            Delete
                                                        </button>

                                                    </form>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="8">
                                            No announcements found.
                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </section>

            </main>

        </div>

</body>

</html>
