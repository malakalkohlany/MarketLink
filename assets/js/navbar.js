function toggleNotifications() {

    const dropdown = document.getElementById('notificationDropdown');

    if (!dropdown) return;

    dropdown.classList.toggle('show');
}


// Close when clicking outside

document.addEventListener('click', function (event) {

    const wrapper = document.querySelector('.notification-wrapper');
    const dropdown = document.getElementById('notificationDropdown');

    if (!wrapper || !dropdown) return;

    if (!wrapper.contains(event.target)) {
        dropdown.classList.remove('show');
    }

});


function openNotification(notificationId) {
    fetch('/MarketLink/actions/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body:
        'notification_id=' + encodeURIComponent(notificationId) +
        '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(
                '.notification-item[data-id="' + notificationId + '"]'
            );

            if (item) {
                item.classList.remove('unread');
            }

            updateNotificationBadge();
        } else {
            console.error('Failed to mark notification:', data.message);
        }
    })
    .catch(error => {
        console.error('Notification error:', error);
    });
}


function markAllNotificationsRead() {
    fetch('/MarketLink/actions/mark_all_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            document
                .querySelectorAll('.notification-item.unread')
                .forEach(item => {
                    item.classList.remove('unread');
                });

            const badge = document.querySelector('.notification-badge');

            if (badge) {
                badge.remove();
            }

            const unreadText = document.querySelector(
                '.notification-header span'
            );

            if (unreadText) {
                unreadText.remove();
            }

            const markButton = document.querySelector(
                '.notification-header button'
            );

            if (markButton) {
                markButton.remove();
            }

        } else {
            console.error(
                'Failed to mark all notifications:',
                data.message
            );
        }
    })
    .catch(error => {
        console.error('Notification error:', error);
    });
}


function updateNotificationBadge() {
    fetch('/MarketLink/actions/get_notifications_count.php')
        .then(response => response.json())
        .then(data => {

            const button = document.querySelector(
                '.notification-button'
            );

            if (!button) return;

            let badge = button.querySelector(
                '.notification-badge'
            );

            if (data.count > 0) {

                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'notification-badge';
                    button.appendChild(badge);
                }

                badge.textContent =
                    data.count > 99
                        ? '99+'
                        : data.count;

            } else if (badge) {

                badge.remove();
            }
        })
        .catch(error => {
            console.error(
                'Notification count error:',
                error
            );
        });
}