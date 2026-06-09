// QR Scanner for pharmacy verification
(function() {
    const qrReader = document.getElementById('qr-reader');
    if (!qrReader) return;

    // html5-qrcode library is loaded via CDN in verify-prescription.php
    // This file contains fallback/helper functions

    function handleScanError(err) {
        // Scanning errors are non-critical; camera may be unavailable
        if (err.includes('Camera')) {
            qrReader.innerHTML = '<p class="text-muted">Camera unavailable. Enter the code manually below.</p>';
        }
    }
})();
