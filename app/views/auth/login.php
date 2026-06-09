<div class="auth-page">
    <div class="auth-brand">
        <div class="auth-brand-content">
            <?= icon('activity') ?>
            <h1>MediConnect</h1>
            <p>Zambia's telemedicine platform</p>
        </div>
    </div>
    <div class="auth-form">
        <h2>Welcome back</h2>
        <form method="POST" action="/login">
            <div class="field">
                <label class="field-label">Email</label>
                <input class="field-input" type="email" name="email" required>
            </div>
            <div class="field">
                <label class="field-label">Password</label>
                <input class="field-input" type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Login</button>
        </form>
    </div>
</div>
