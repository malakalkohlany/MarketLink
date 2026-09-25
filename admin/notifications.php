<?php

require_once '../includes/include.php';

$stmt = $conn->prepare("
    SELECT
        id,
        user_id,
        title,
        message,
        type,
        is_read,
        created_at
    FROM notifications
    ORDER BY created_at DESC
");

$stmt->execute();

$notifications = $stmt->fetchAll();

?>
<div class="page-header">

    <h1>
        Notifications
    </h1>

    <p>
        View all notifications.
    </p>

</div>

<div class="page-header">

    <h1>
        Notifications
    </h1>

    <p>
        View all notifications.
    </p>

</div>


<section class="table-section">

    <div class="section-header">

        <h2>
            Notifications
        </h2>

    </div>


    <table class="data-table">

        <thead>

            <tr>

                <th>
                    ID
                </th>

                <th>
                    User ID
                </th>

                <th>
                    Title
                </th>

                <th>
                    Message
                </th>

                <th>
                    Type
                </th>

                <th>
                    Status
                </th>

                <th>
                    Date
                </th>

            </tr>

        </thead>
<tbody>

            <?php if (!empty($notifications)): ?>

                <?php foreach ($notifications as $notification): ?>

                    <tr>

                        <td>
                            <?= $notification['id'] ?>
                        </td>

                        <td>
                            <?= $notification['user_id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $notification['title']
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $notification['message']
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $notification['type'] ?? 'N/A'
                            ) ?>
                        </td>

                        <td>

                            <?php if ($notification['is_read']): ?>

                                <span class="status status-active">
                                    Read
                                </span>

                            <?php else: ?>

                                <span class="status status-pending">
                                    Unread
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= date(
                                'Y-m-d H:i',
                                strtotime(
                                    $notification['created_at']
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="7">
                        No notifications found.
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</section>