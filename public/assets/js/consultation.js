// Consultation room utilities
(function() {
    // Check if we're on a consultation page
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;

    // Auto-scroll to bottom when new messages arrive
    const observer = new MutationObserver(function() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });

    observer.observe(chatMessages, { childList: true, subtree: true });
})();
