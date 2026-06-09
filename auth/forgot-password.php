<?php

$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    // Check if user exists
    $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expiresAt, $user['id']]);

        // In production, send email here
        // For now, show the reset link
        $resetLink = APP_URL . '/reset-password?token=' . $token;
        error_log("Password reset link for {$user['name']}: $resetLink");
    }

    echo json_encode([
        'success' => true,
        'message' => 'If the email exists, a reset link has been sent. Please check your inbox.'
    ]);
    exit;
}

$pageTitle = 'Forgot Password';
?>
<div class="auth-layout">
    <div class="auth-panel">
        <div class="auth-panel-title">Reset your password</div>
        <div class="auth-panel-sub">We'll send you a link to reset your password and get you back to your healthcare journey.</div>
    </div>
    <div class="auth-form-panel">
        <div class="auth-form-card">
            <h1 class="auth-title">Forgot password</h1>
            <p class="auth-subtitle">Enter your email and we'll send you a reset link</p>

            <form id="forgotForm" method="POST">
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="field-label" for="email">Email address</label>
                    <input class="field-input" type="email" id="email" name="email" required placeholder="you@example.com">
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">Send reset link</button>
            </form>

            <p class="auth-link">
                Remember your password? <a href="/login">Sign in</a>
            </p>
        </div>
    </div>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = 'Sending...';

    try {
        const formData = new FormData(this);
        const resp = await fetch('/forgot-password', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            showToast('success', 'Email sent', data.message);
            this.reset();
        } else {
            showToast('error', 'Error', data.message);
        }
    } catch (err) {
        showToast('error', 'Error', 'An error occurred. Please try again.');
    }
    btn.disabled = false;
    btn.innerHTML = 'Send reset link';
});
</script>
