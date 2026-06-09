<?php if (!isLoggedIn()): ?>
        </div>
    </main>
</div>

<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <div style="display:flex;align-items:center;gap:var(--space-3);">
                <span style="width:36px;height:36px;border-radius:var(--radius);background-color:var(--primary-500);display:flex;align-items:center;justify-content:center;">
                    <?= icon('heart') ?>
                </span>
                <span style="font-family:var(--font-display);font-size:var(--text-lg);font-weight:800;color:var(--text);">MediConnect</span>
            </div>
            <p class="footer-brand-desc">Bringing healthcare closer to you. Consult licensed doctors from anywhere in Zambia via chat, voice, or video.</p>
        </div>
        <div>
            <div class="footer-col-title">Quick Links</div>
            <div class="footer-links">
                <a href="/patient/find-doctor">Find a Doctor</a>
                <a href="/register">Register</a>
                <a href="/login">Login</a>
            </div>
        </div>
        <div>
            <div class="footer-col-title">Contact</div>
            <div class="footer-links">
                <a href="tel:991">Emergency: 991</a>
                <a href="mailto:support@mediconnect.zm">support@mediconnect.zm</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> MediConnect. All rights reserved.
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
