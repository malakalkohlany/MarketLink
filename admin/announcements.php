<?php

require_once '../includes/include.php';

$admin_id = $_SESSION['user_id'] ?? null;

$errors = [];
$success = '';
$announcements = [];

if (!$admin_id || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['delete'])) {

    $announcement_id = filter_input(
        INPUT_GET,
        'delete',
        FILTER_VALIDATE_INT
    );

    if ($announcement_id) {

        $stmt = $conn->prepare("
            DELETE FROM announcements
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param("i", $announcement_id);
            $stmt->execute();
            $stmt->close();

            header("Location: announcements.php");
            exit;
        }
    }
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_announcement'])
) {

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $expires_at = trim($_POST['expires_at'] ?? '');

    if ($title === '') {
        $errors[] = "Announcement title is required.";
    }

    if ($message === '') {
        $errors[] = "Announcement message is required.";
    }

    if (!in_array(
        $status,
        ['draft', 'published', 'archived'],
        true
    )) {
        $errors[] = "Invalid announcement status.";
    }
     if (empty($errors)) {

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

        if ($stmt) {

            $expires_value = $expires_at !== ''
                ? $expires_at
                : null;

            $stmt->bind_param(
                "issss",
                $admin_id,
                $title,
                $message,
                $status,
                $expires_value
            );

            try {

                $stmt->execute();

                $success = "Announcement added successfully.";

                $_POST = [];

            } catch (mysqli_sql_exception $e) {

                $errors[] = "Failed to add announcement.";
            }

            $stmt->close();
        }
    }
}

if (isset($_GET['status']) && isset($_GET['id'])) {

    $announcement_id = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    $new_status = $_GET['status'];

    if (
        $announcement_id &&
        in_array(
            $new_status,
            ['draft', 'published', 'archived'],
            true
        )
    ) {

        $stmt = $conn->prepare("
            UPDATE announcements
            SET status = ?
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                "si",
                $new_status,
                $announcement_id
            );

            $stmt->execute();
            $stmt->close();

            header("Location: announcements.php");
            exit;
        }
    }
}
$stmt = $conn->query("
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
    ORDER BY a.id DESC
");

if ($stmt) {
    $announcements = $stmt->fetch_all(MYSQLI_ASSOC);
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

    <title>Announcements | FreshFind</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>


<!-- Admin Sidebar -->

<aside class="sidebar">

    <div class="logo">
        FreshFind
    </div>

    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="add_market.php">
            Add Market
        </a>

        <a href="markets.php">
            Markets
        </a>

        <a href="farmers.php">
            Farmers
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="users.php">
            Users
        </a>

        <a href="orders.php">
            Orders
        </a>

        <a href="reviews.php">
            Reviews
        </a>

        <a
            href="announcements.php"
            class="active"
        >
            Announcements
        </a>

        <a href="reports.php">
            Reports
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </nav>

</aside>
<!-- Main Content -->

<main class="main-content">


    <div class="page-header">

        <h1>
            Announcements
        </h1>

        <p>
            Manage announcements and notifications for users.
        </p>

    </div>


    <!-- Error Messages -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-error">

            <?php foreach ($errors as $error): ?>

                <p>
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- Success Message -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($success) ?>

        </div>

    <?php endif; ?>


    <!-- Add Announcement -->

    <section class="form-section">

        <h2>
            Add Announcement
        </h2>


        <form
            action="announcements.php"
            method="POST"
            class="announcement-form"
        >


            <div class="form-group">

                <label for="title">
                    Title
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Enter announcement title"
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
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
                    placeholder="Enter announcement message"
                    required
                ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

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
                        value="<?= htmlspecialchars($_POST['expires_at'] ?? '') ?>"
                    >

                </div>


            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Add Announcement
                </button>

            </div>


        </form>

    </section>


    <!-- Announcements List -->

    <section class="table-section">

        <div class="section-header">

            <h2>
                All Announcements
            </h2>

        </div>


        <table class="data-table">

            <thead>

                <tr>

                    <th>
                        ID
                    </th>

                    <th>
                        Title
                    </th>

                    <th>
                        Message
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Created By
                    </th>

                    <th>
                        Created At
                    </th>

                    <th>
                        Expires At
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if (!empty($announcements)): ?>


                    <?php foreach ($announcements as $announcement): ?>


                        <tr>


                            <td>
                                <?= $announcement['id'] ?>
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

                                <?php if ($announcement['expires_at']): ?>

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

                                        <a
                                            href="announcements.php?id=<?= $announcement['id'] ?>&status=published"
                                            class="btn btn-primary"
                                        >
                                            Publish
                                        </a>

                                    <?php endif; ?>


                                    <?php if (
                                        $announcement['status'] !== 'archived'
                                    ): ?>

                                        <a
                                            href="announcements.php?id=<?= $announcement['id'] ?>&status=archived"
                                            class="btn btn-secondary"
                                        >
                                            Archive
                                        </a>

                                    <?php endif; ?>


                                    <?php if (
                                        $announcement['status'] !== 'draft'
                                    ): ?>

                                        <a
                                            href="announcements.php?id=<?= $announcement['id'] ?>&status=draft"
                                            class="btn btn-secondary"
                                        >
                                            Draft
                                        </a>

                                    <?php endif; ?>


                                    <a
                                        href="announcements.php?delete=<?= $announcement['id'] ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this announcement?');"
                                    >
                                        Delete
                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                        >
                            No announcements found.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </section>


</main>


</body>

</html>