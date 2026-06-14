<?php

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $token    = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        exit;
    }

    $db = Database::getConnection();

    try {
        $stmt = $db->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()');
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token.']);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
        $stmt->execute([$passwordHash, $user['id']]);

        echo json_encode(['success' => true, 'message' => 'Password reset successful! Redirecting to login...']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        exit;
    }
}

if (empty($token)) {
    redirect('/login');
}

$pageTitle = 'Reset Password';
?>
<div class="auth-layout">
    <div class="auth-panel">
        <div class="auth-panel-title">Create a new password</div>
        <div class="auth-panel-sub">Choose a strong password for your account.</div>
    </div>
    <div class="auth-form-panel">
        <div class="auth-form-card">
            <h1 class="auth-title">Reset password</h1>
            <p class="auth-subtitle">Enter your new password</p>

            <form id="resetForm" method="POST" action="/reset-password">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="form-group">
                    <label class="field-label" for="password">New password</label>
                    <input class="field-input" type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">Reset password</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('resetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Resetting...';

    try {
        const formData = new FormData(this);
        const resp = await fetch('/reset-password', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            showToast('success', 'Success', data.message);
            setTimeout(() => window.location.href = '/login', 2000);
        } else {
            showToast('error', 'Error', data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        showToast('error', 'Error', 'An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>
