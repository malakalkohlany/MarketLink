<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_ADMIN);

$admin_id = getUserId();

$errors = [];
$success = '';
$announcements = [];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_announcement'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid CSRF token.';
    }

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

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid CSRF token.';
    }

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

                $conn->begin_transaction();

                try {
                    $stmt = $conn->prepare("
                        UPDATE announcements
                        SET status = ?
                        WHERE id = ?
                        AND admin_id = ?
                    ");

                    if (!$stmt) {
                        throw new Exception(
                            'Failed to prepare announcement update.'
                        );
                    }

                    $stmt->bind_param(
                        'sii',
                        $new_status,
                        $announcement_id,
                        $admin_id
                    );

                    if (!$stmt->execute()) {
                        $stmt->close();

                        throw new Exception(
                            'Failed to update announcement.'
                        );
                    }

                    $stmt->close();

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

                        if (!$notification_stmt) {
                            throw new Exception(
                                'Failed to prepare announcement notifications.'
                            );
                        }

                        $notification_stmt->bind_param(
                            'ss',
                            $announcement['title'],
                            $announcement['message']
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

                    redirect('announcements.php');
                } catch (Exception $e) {
                    $conn->rollback();
                    $errors[] = $e->getMessage();
                }
            }
        } else {
            $errors[] = 'Failed to load announcement.';
        }
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_announcement'])
) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid CSRF token.';
    }

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

                redirect('announcements.php');
            } else {
                $errors[] = 'Failed to delete announcement.';
                $stmt->close();
            }
        } else {
            $errors[] = 'Failed to prepare announcement deletion.';
        }
    }
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | MarketLink</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin_ann.css">
</head>

<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content admin-announcements-page">

    <section class="admin-page-hero">
        <div class="admin-page-hero-copy">
            <span class="eyebrow">ADMIN / ANNOUNCEMENTS</span>

            <h1>
                Keep users <em>informed.</em>
            </h1>

            <p>
                Share important updates and news with customers and farmers across MarketLink.
            </p>
        </div>

        <div class="admin-page-mark">
            08
        </div>
    </section>

    <?php if (!empty($errors)): ?>
        <div class="admin-page-alert alert-danger">
            <span class="admin-alert-mark">!</span>

            <div>
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="admin-page-alert alert-success">
            <span class="admin-alert-mark">✓</span>

            <div>
                <?= htmlspecialchars($success) ?>
            </div>
        </div>
    <?php endif; ?>

    <section class="admin-announcement-compose-section">

        <div class="admin-section-heading">
            <div>
                <span class="admin-section-number">01 / COMPOSE</span>

                <h2>
                    New <em>announcement.</em>
                </h2>

                <p>
                    Create an update for the MarketLink community.
                </p>
            </div>
        </div>

        <div class="admin-announcement-form-card">

            <form
                method="POST"
                action="announcements.php"
                class="admin-announcement-form"
            >

                <?= csrf_field() ?>

                <div class="admin-announcement-form-grid">

                    <div class="admin-announcement-field admin-announcement-field-full">
                        <label for="title">
                            Announcement title
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            maxlength="150"
                            value="<?= htmlspecialchars(
                                $_POST['title'] ?? ''
                            ) ?>"
                            placeholder="Enter a clear announcement title"
                            required
                        >
                    </div>

                    <div class="admin-announcement-field admin-announcement-field-full">
                        <label for="message">
                            Message
                        </label>

                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            placeholder="Write the announcement message..."
                            required
                        ><?= htmlspecialchars(
                            $_POST['message'] ?? ''
                        ) ?></textarea>
                    </div>

                    <div class="admin-announcement-field">
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

                    <div class="admin-announcement-field">
                        <label for="expires_at">
                            Expiration date
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

                <div class="admin-announcement-form-actions">
                    <button
                        type="submit"
                        name="add_announcement"
                        class="admin-action-submit"
                    >
                        Publish announcement
                    </button>
                </div>

            </form>

        </div>

    </section>

    <section class="admin-announcements-section">

        <div class="admin-section-heading">
            <div>
                <span class="admin-section-number">02 / DIRECTORY</span>

                <h2>
                    Announcement <em>archive.</em>
                </h2>

                <p>
                    Review, publish, archive, or remove existing announcements.
                </p>
            </div>

            <span class="admin-record-count">
                <?= count($announcements) ?>
                <?= count($announcements) === 1 ? 'announcement' : 'announcements' ?>
            </span>
        </div>

        <div class="admin-announcements-table">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Announcement</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($announcements)): ?>

                        <?php foreach ($announcements as $announcement): ?>

                            <tr>

                                <td class="admin-announcement-id">
                                    <?= (int) $announcement['id'] ?>
                                </td>

                                <td>
                                    <div class="admin-announcement-content">

                                        <span class="admin-announcement-title">
                                            <?= htmlspecialchars(
                                                $announcement['title']
                                            ) ?>
                                        </span>

                                        <span class="admin-announcement-message">
                                            <?= htmlspecialchars(
                                                $announcement['message']
                                            ) ?>
                                        </span>

                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="admin-status admin-announcement-status admin-status-<?= htmlspecialchars(
                                            $announcement['status']
                                        ) ?>"
                                    >
                                        <span class="admin-status-dot"></span>

                                        <?= ucfirst(
                                            $announcement['status']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="admin-announcement-admin">
                                        <?= htmlspecialchars(
                                            $announcement['admin_name']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="admin-table-date">
                                        <?= date(
                                            'Y-m-d',
                                            strtotime(
                                                $announcement['created_at']
                                            )
                                        ) ?>
                                    </span>

                                    <span class="admin-table-time">
                                        <?= date(
                                            'H:i',
                                            strtotime(
                                                $announcement['created_at']
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($announcement['expires_at'])): ?>

                                        <span class="admin-table-date">
                                            <?= date(
                                                'Y-m-d',
                                                strtotime(
                                                    $announcement['expires_at']
                                                )
                                            ) ?>
                                        </span>

                                        <span class="admin-table-time">
                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $announcement['expires_at']
                                                )
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="admin-table-muted">
                                            No expiration
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>

                                    <div class="admin-announcement-actions">

                                        <?php if (
                                            $announcement['status'] !== 'published'
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="announcements.php"
                                            >
                                                <?= csrf_field() ?>

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
                                                    class="admin-action-approve"
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
                                                <?= csrf_field() ?>

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
                                                    class="admin-action-view"
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
                                                <?= csrf_field() ?>

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
                                                    class="admin-action-view"
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
                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $announcement['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_announcement"
                                                class="admin-action-reject"
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
                            <td colspan="7">
                                <div class="admin-table-empty">
                                    <span class="admin-table-empty-mark">✦</span>

                                    <strong>No announcements yet.</strong>

                                    <span>
                                        Create your first announcement above.
                                    </span>
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>