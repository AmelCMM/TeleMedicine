<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    $db = Database::getConnection();

    try {
        $stmt = $db->prepare('SELECT id, name, password_hash, role, is_active FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit;
        }

        if (!$user['is_active']) {
            echo json_encode(['success' => false, 'message' => 'Your account has been deactivated. Contact support.']);
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        $redirectMap = [
            ROLE_PATIENT  => '/patient/dashboard',
            ROLE_DOCTOR   => '/doctor/dashboard',
            ROLE_PHARMACY => '/pharmacy/dashboard',
            ROLE_ADMIN    => '/admin/dashboard',
        ];

        echo json_encode([
            'success'  => true,
            'message'  => 'Login successful.',
            'redirect' => $redirectMap[$user['role']] ?? '/',
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
        exit;
    }
}

$pageTitle = 'Login';
?>
<div class="auth-layout">
    <div class="auth-panel">
        <div class="auth-panel-title">Welcome back to MediConnect</div>
        <div class="auth-panel-sub">Continue your healthcare journey from where you left off.</div>
        <div class="auth-panel-features">
            <div class="auth-panel-feature"><?= icon('message-square') ?> Chat, voice &amp; video consultations</div>
            <div class="auth-panel-feature"><?= icon('shield') ?> Secure &amp; confidential</div>
            <div class="auth-panel-feature"><?= icon('clock') ?> Available 24/7</div>
        </div>
    </div>
    <div class="auth-form-panel">
        <div class="auth-form-card">
            <h1 class="auth-title">Sign in</h1>
            <p class="auth-subtitle">Welcome back! Enter your credentials to continue</p>

            <form id="loginForm" method="POST" action="/login">
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="field-label" for="email">Email address</label>
                    <input class="field-input" type="email" id="email" name="email" required autocomplete="email" placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <label class="field-label" for="password">Password</label>
                    <div class="password-wrapper">
                        <input class="field-input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                        <button type="button" class="password-toggle" id="passwordToggle" tabindex="-1" aria-label="Toggle password visibility">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="auth-form-row">
                    <label class="field-check">
                        <input type="checkbox" name="remember" value="1">
                        <span>Remember me</span>
                    </label>
                    <a href="/forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">Sign in</button>
            </form>

            <p class="auth-link">
                Don't have an account? <a href="/register">Create one</a>
            </p>
        </div>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('loginForm');
    var toggle = document.getElementById('passwordToggle');
    var password = document.getElementById('password');

    if (toggle && password) {
        toggle.addEventListener('click', function() {
            var isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            toggle.innerHTML = isPassword
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        });
    }

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            var btn = this.querySelector('button[type="submit"]');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Signing in';

            try {
                var formData = new FormData(this);
                var resp = await fetch('/login', { method: 'POST', body: formData });
                var data = await resp.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showToast('error', data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                showToast('error', 'An error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
})();
</script>
