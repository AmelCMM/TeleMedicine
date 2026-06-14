<?php if (!isLoggedIn()): ?>
    </main>
</div>

<footer class="site-footer animate-fade">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="sidebar-logo" style="padding: 0; margin-bottom: var(--space-4);">
                    <span class="sidebar-logo-mark"><?= icon('heart') ?></span>
                    <span class="sidebar-logo-text">MediConnect</span>
                </div>
                <p class="footer-brand-desc">Advancing healthcare accessibility across Zambia through secure digital consultation and pharmacy integration.</p>
            </div>
            <div>
                <div class="footer-col-title">Resources</div>
                <ul class="footer-links">
                    <li><a href="/patient/find-doctor">Find a Provider</a></li>
                    <li><a href="/emergency/nearest">Emergency Map</a></li>
                    <li><a href="/register">Provider Enrollment</a></li>
                </ul>
            </div>
            <div>
                <div class="footer-col-title">Contact</div>
                <ul class="footer-links">
                    <li><a href="tel:991">National Emergency: 991</a></li>
                    <li><a href="mailto:support@mediconnect.zm">support@mediconnect.zm</a></li>
                    <li><a href="#">Help Center</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div style="display:flex; justify-content:center; gap:var(--space-6); margin-bottom:var(--space-4);">
                <a href="#" style="color:var(--text-muted); font-size:var(--text-xs);">Privacy Policy</a>
                <a href="#" style="color:var(--text-muted); font-size:var(--text-xs);">Terms of Service</a>
                <a href="#" style="color:var(--text-muted); font-size:var(--text-xs);">Cookie Settings</a>
            </div>
            <p>&copy; <?= date('Y') ?> MediConnect Zambia. All rights reserved.</p>
        </div>
    </div>
</footer>
<?php else: ?>
    </main>
</div>
<?php endif; ?>

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js').catch(function(err) {
        console.warn('ServiceWorker registration failed:', err);
    });
}
</script>
</body>
</html>
