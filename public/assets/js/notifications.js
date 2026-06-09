// Notification polling
(function() {
    const notifBadge = document.getElementById('notifBadge');
    if (!notifBadge) return;

    async function pollNotifications() {
        try {
            const resp = await fetch('/api/get-notifications');
            const data = await resp.json();
            if (data.success) {
                const count = data.count || 0;
                if (count > 0) {
                    notifBadge.textContent = count;
                    notifBadge.style.display = 'inline-flex';
                } else {
                    notifBadge.textContent = '';
                    notifBadge.style.display = 'none';
                }
            }
        } catch (err) {
            console.warn('Notification poll failed:', err);
        }
    }

    // Poll every 30 seconds
    pollNotifications();
    setInterval(pollNotifications, 30000);
})();
