<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    setFlashMessage('error', 'Please log in to access this page.');
    redirect('/login');
}

// Optional role check — call with requireRole('doctor')
function requireRole(string $requiredRole): void
{
    if ($_SESSION['role'] !== $requiredRole) {
        http_response_code(403);
        die('Access denied. You do not have permission to view this page.');
    }
}
