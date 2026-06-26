// Notification polling and permission
(function() {
    const notifBadge = document.getElementById('notifBadge');
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    const markAllRead = document.getElementById('markAllRead');

    if (notifBtn) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();

            // Request permission if needed
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }

            // Toggle dropdown
            notifDropdown.classList.toggle('open');
            if (notifDropdown.classList.contains('open')) {
                pollNotifications();
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (notifDropdown && !notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            notifDropdown.classList.remove('open');
        }
    });

    if (markAllRead) {
        markAllRead.addEventListener('click', async function() {
            try {
                const resp = await fetch('/api/mark-notification', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=mark_all'
                });
                const data = await resp.json();
                if (data.success) {
                    pollNotifications();
                }
            } catch (err) {
                console.error('Mark all read failed:', err);
            }
        });
    }

    async function pollNotifications() {
        try {
            const resp = await fetch('/api/get-notifications');
            const data = await resp.json();
            if (data.success) {
                // Update badge
                const count = data.count || 0;
                if (count > 0) {
                    notifBadge.style.display = 'block';
                } else {
                    notifBadge.style.display = 'none';
                }

                // Update list if dropdown is open or on initial load
                if (notifList) {
                    if (data.notifications && data.notifications.length > 0) {
                        notifList.innerHTML = data.notifications.map(n => `
                            <div class="notif-item" onclick="markRead(${n.id})">
                                <div class="notif-title">${escapeHtml(n.title)}</div>
                                <div class="notif-msg">${escapeHtml(n.message)}</div>
                                <div class="notif-time">${n.created_at}</div>
                            </div>
                        `).join('');
                    } else {
                        notifList.innerHTML = '<div class="notif-empty">No new notifications</div>';
                    }
                }
            }
        } catch (err) {
            console.warn('Notification poll failed:', err);
        }
    }

    window.markRead = async function(id) {
        try {
            await fetch('/api/mark-notification', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            });
            pollNotifications();
        } catch (err) {}
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Poll every 30 seconds
    pollNotifications();
    setInterval(pollNotifications, 30000);
})();
