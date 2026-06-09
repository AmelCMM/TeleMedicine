// Booking date/time slot picker
(function() {
    const dateInput = document.getElementById('scheduled_at');
    const doctorId = new URLSearchParams(window.location.search).get('doctor_id');

    if (!dateInput || !doctorId) return;

    dateInput.addEventListener('change', async function() {
        const date = this.value;
        if (!date) return;

        // Could add dynamic slot loading here from /api/get-availability
        // For now, the native datetime-local input handles the UX
    });
})();
