<?php

// CSRF token generation and validation
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

// Redirect helper
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

// Flash message helpers
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlashMessage(): void
{
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = match ($flash['type']) {
            'error'   => 'alert-danger',
            'success' => 'alert-success',
            'info'    => 'alert-info',
            'warning' => 'alert-warning',
            default   => 'alert-info',
        };
        echo '<div class="container" style="margin-top:var(--space-4);"><div class="alert ' . $alertClass . '">';
        echo icon($flash['type'] === 'error' ? 'alert-triangle' : ($flash['type'] === 'success' ? 'check' : 'info'));
        echo '<div class="alert-text">' . htmlspecialchars($flash['message']) . '</div></div></div>';
    }
}

// Escape helper
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Current user helpers
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function getCurrentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function getCurrentUserName(): ?string
{
    return $_SESSION['user_name'] ?? null;
}

// Get patient ID from user ID
function getPatientId(int $userId): ?int
{
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT id FROM patients WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

// Get doctor ID from user ID
function getDoctorId(int $userId): ?int
{
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT id FROM doctors WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

// Create notification
function createNotification(int $userId, string $title, string $message, string $type): void
{
    $db = Database::getConnection();
    $stmt = $db->prepare(
        'INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $title, $message, $type]);
}

// Generate Jitsi room name
function generateJitsiRoom(int $appointmentId): string
{
    return 'mediconnect_appt_' . $appointmentId;
}

// Generate QR hash for prescription
function generateQrHash(int $prescriptionId, int $patientId, string $issuedAt): string
{
    return hash('sha256', $prescriptionId . $patientId . $issuedAt . SECRET_KEY);
}

// Time ago helper
function timeAgo(string $timestamp): string
{
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return date('M j, g:i A', strtotime($timestamp));
}

// Format currency
function formatCurrency(float $amount, string $currency = 'ZMW'): string
{
    return $currency . ' ' . number_format($amount, 2);
}

// Get initials from name
function getInitials(string $name): string
{
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1));
    }
    return strtoupper(substr($words[0], 0, 2));
}

// Inline SVG icon helper
function icon(string $name, string $class = ''): string
{
    $path = ROOT_PATH . '/public/assets/img/icons/' . $name . '.svg';
    if (!file_exists($path)) return '';
    $svg = file_get_contents($path);
    if ($class) {
        $svg = str_replace('<svg', '<svg class="' . e($class) . '"', $svg);
    }
    return $svg;
}
