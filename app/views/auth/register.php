<div class="auth-page">
    <div class="auth-brand">
        <div class="auth-brand-content">
            <?= icon('activity') ?>
            <h1>MediConnect</h1>
            <p>Join Zambia's healthcare platform</p>
        </div>
    </div>
    <div class="auth-form">
        <h2>Create account</h2>
        <form method="POST" action="/register">
            <div class="field">
                <label class="field-label">Full name</label>
                <input class="field-input" type="text" name="name" required>
            </div>
            <div class="field">
                <label class="field-label">Email</label>
                <input class="field-input" type="email" name="email" required>
            </div>
            <div class="field">
                <label class="field-label">Password</label>
                <input class="field-input" type="password" name="password" required>
            </div>
            <div class="field">
                <label class="field-label">Role</label>
                <select class="field-input" name="role" required>
                    <option value="patient">Patient</option>
                    <option value="doctor">Doctor</option>
                    <option value="pharmacy">Pharmacy</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Register</button>
        </form>
    </div>
</div>
