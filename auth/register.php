<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (!in_array($role, [ROLE_PATIENT, ROLE_DOCTOR, ROLE_PHARMACY])) {
        echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        exit;
    }

    $db = Database::getConnection();

    try {
        $stmt = $db->prepare('SELECT email, phone FROM users WHERE email = ? OR phone = ?');
        $stmt->execute([$email, $phone]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['email'] === $email) {
                echo json_encode(['success' => false, 'message' => 'Email already registered.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Phone number already registered.']);
            }
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            'INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $phone, $passwordHash, $role]);
        $userId = (int)$db->lastInsertId();

        if ($role === ROLE_PATIENT) {
            $stmt = $db->prepare('INSERT INTO patients (user_id) VALUES (?)');
            $stmt->execute([$userId]);
        } elseif ($role === ROLE_DOCTOR) {
            $hpczNo        = trim($_POST['hpcz_number'] ?? '');
            $specialization = trim($_POST['specialization'] ?? '');
            $fee           = (float)($_POST['consultation_fee'] ?? 0);

            if (empty($hpczNo) || empty($specialization)) {
                echo json_encode(['success' => false, 'message' => 'HPCZ number and specialization are required for doctors.']);
                exit;
            }

            $stmt = $db->prepare(
                'INSERT INTO doctors (user_id, hpcz_number, specialization, consultation_fee) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $hpczNo, $specialization, $fee]);
        } elseif ($role === ROLE_PHARMACY) {
            $licenseNo    = trim($_POST['license_number'] ?? '');
            $facilityId   = !empty($_POST['facility_id']) ? (int)$_POST['facility_id'] : null;

            if (empty($licenseNo)) {
                echo json_encode(['success' => false, 'message' => 'License number is required for pharmacies.']);
                exit;
            }

            $stmt = $db->prepare(
                'INSERT INTO pharmacies (user_id, facility_id, license_number) VALUES (?, ?, ?)'
            );
            $stmt->execute([$userId, $facilityId, $licenseNo]);
        }

        $_SESSION['user_id']   = $userId;
        $_SESSION['role']      = $role;
        $_SESSION['user_name'] = $name;

        $redirectMap = [
            ROLE_PATIENT  => '/patient/dashboard',
            ROLE_DOCTOR   => '/doctor/dashboard',
            ROLE_PHARMACY => '/pharmacy/dashboard',
        ];

        echo json_encode([
            'success'  => true,
            'message'  => 'Account created successfully.',
            'redirect' => $redirectMap[$role] ?? '/',
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
        exit;
    }
}

$role = $_GET['role'] ?? 'patient';
$db = Database::getConnection();
$facilities = [];
try {
    $stmt = $db->query('SELECT id, name, type FROM facilities WHERE is_active = 1 ORDER BY name');
    $facilities = $stmt->fetchAll();
} catch (PDOException $e) {}

$pageTitle = 'Create Account';
?>
<div class="auth-layout">
    <div class="auth-panel">
        <div class="auth-panel-title">Join MediConnect today</div>
        <div class="auth-panel-sub">Choose your role and create your account to get started.</div>
        <div class="auth-panel-features">
            <div class="auth-panel-feature"><?= icon('user') ?> Free registration</div>
            <div class="auth-panel-feature"><?= icon('shield') ?> Your data is safe &amp; secure</div>
            <div class="auth-panel-feature"><?= icon('message-square') ?> Consult from anywhere</div>
        </div>
    </div>
    <div class="auth-form-panel">
        <div class="auth-form-card">
            <h1 class="auth-title">Create account</h1>
            <p class="auth-subtitle">Select your role and fill in your details below</p>

            <form id="registerForm" method="POST" action="/register">
                <?= csrfField() ?>

                <div class="role-cards">
                    <label class="role-card">
                        <input type="radio" name="role" value="patient" <?= $role === 'patient' ? 'checked' : '' ?>>
                        <span class="role-card-label">
                            <?= icon('user') ?>
                            <span class="role-card-title">Patient</span>
                            <span class="role-card-desc">Consult a doctor</span>
                        </span>
                    </label>
                    <label class="role-card">
                        <input type="radio" name="role" value="doctor" <?= $role === 'doctor' ? 'checked' : '' ?>>
                        <span class="role-card-label">
                            <?= icon('heart') ?>
                            <span class="role-card-title">Doctor</span>
                            <span class="role-card-desc">Offer consultations</span>
                        </span>
                    </label>
                    <label class="role-card">
                        <input type="radio" name="role" value="pharmacy" <?= $role === 'pharmacy' ? 'checked' : '' ?>>
                        <span class="role-card-label">
                            <?= icon('shield') ?>
                            <span class="role-card-title">Pharmacy</span>
                            <span class="role-card-desc">Verify prescriptions</span>
                        </span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="field-label" for="name">Full name</label>
                    <input class="field-input" type="text" id="name" name="name" required autocomplete="name" placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label class="field-label" for="email">Email address</label>
                    <input class="field-input" type="email" id="email" name="email" required autocomplete="email" placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <label class="field-label" for="phone">Phone number</label>
                    <input class="field-input" type="tel" id="phone" name="phone" required autocomplete="tel" placeholder="+260 97X XXX XXX">
                </div>

                <div class="form-group">
                    <label class="field-label" for="password">Password</label>
                    <div class="password-wrapper">
                        <input class="field-input" type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters">
                        <button type="button" class="password-toggle" id="passwordToggle" tabindex="-1" aria-label="Toggle password visibility">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div id="doctorFields" class="role-fields <?= $role === 'doctor' ? 'visible' : '' ?>">
                    <div class="form-group">
                        <label class="field-label" for="hpcz_number">HPCZ registration number</label>
                        <input class="field-input" type="text" id="hpcz_number" name="hpcz_number" placeholder="e.g. HPCZ/2024/12345">
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="specialization">Specialization</label>
                        <input class="field-input" type="text" id="specialization" name="specialization" placeholder="e.g. General Practitioner, Cardiology">
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="consultation_fee">Consultation fee (ZMW)</label>
                        <input class="field-input" type="number" id="consultation_fee" name="consultation_fee" step="0.50" min="0" placeholder="0.00">
                    </div>
                </div>

                <div id="pharmacyFields" class="role-fields <?= $role === 'pharmacy' ? 'visible' : '' ?>">
                    <div class="form-group">
                        <label class="field-label" for="license_number">Pharmacy license number</label>
                        <input class="field-input" type="text" id="license_number" name="license_number" placeholder="e.g. PHA/2024/6789">
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="facility_id">Associated facility</label>
                        <select class="field-select" id="facility_id" name="facility_id">
                            <option value="">None</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?= $facility['id'] ?>"><?= e($facility['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">Create account</button>
            </form>

            <p class="auth-link">
                Already have an account? <a href="/login">Sign in</a>
            </p>
        </div>
    </div>
</div>

<script>
(function() {
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

    document.querySelectorAll('input[name="role"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById('doctorFields').classList.toggle('visible', this.value === 'doctor');
            document.getElementById('pharmacyFields').classList.toggle('visible', this.value === 'pharmacy');
        });
    });

    var form = document.getElementById('registerForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            var btn = this.querySelector('button[type="submit"]');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Creating account';

            try {
                var formData = new FormData(this);
                var resp = await fetch('/register', { method: 'POST', body: formData });
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
