<?php

// Prevent multiple session starts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = dirname($_SERVER['SCRIPT_NAME']);

// Properly extract route
$route = $requestUri;
if ($basePath !== '/' && $basePath !== '\\' && strpos($requestUri, $basePath) === 0) {
    $route = substr($requestUri, strlen($basePath));
}
$route = '/' . trim($route, '/');

// Common MIME types
$mimeTypes = [
    'css'  => 'text/css',
    'js'   => 'application/javascript',
    'json' => 'application/json',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2'=> 'font/woff2',
    'ttf'  => 'font/ttf',
];

// Serve static assets directly if they exist in the public folder
$filePath = __DIR__ . $route;
if ($route !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $contentType = $mimeTypes[$ext] ?? mime_content_type($filePath) ?: 'application/octet-stream';
    header('Content-Type: ' . $contentType);
    // Cache static assets for 1 year with versioning via query string
    if (isset($_GET['v'])) {
        header('Cache-Control: public, max-age=31536000, immutable');
    } else {
        header('Cache-Control: no-cache, must-revalidate');
    }
    readfile($filePath);
    exit;
}

// Route definitions
$publicRoutes = [
    '/'                         => 'index.php',
    '/login'                    => 'auth/login.php',
    '/register'                 => 'auth/register.php',
    '/forgot-password'          => 'auth/forgot-password.php',
    '/reset-password'           => 'auth/reset-password.php',
    '/patient/find-doctor'      => 'patient/find-doctor.php',
    '/emergency/nearest'        => 'emergency/nearest.php',
    '/logout'                   => 'auth/logout.php',
];

$protectedRoutes = [
    '/patient/dashboard'              => 'patient/dashboard.php',
    '/patient/book-appointment'       => 'patient/book-appointment.php',
    '/patient/appointments'           => 'patient/appointments.php',
    '/patient/consultation'           => 'patient/consultation.php',
    '/patient/health-records'         => 'patient/health-records.php',
    '/patient/prescriptions'          => 'patient/prescriptions.php',
    '/doctor/dashboard'               => 'doctor/dashboard.php',
    '/doctor/appointments'            => 'doctor/appointments.php',
    '/doctor/consultation'            => 'doctor/consultation.php',
    '/doctor/write-prescription'      => 'doctor/write-prescription.php',
    '/doctor/patients'                => 'doctor/patients.php',
    '/doctor/profile'                 => 'doctor/profile.php',
    '/pharmacy/dashboard'             => 'pharmacy/dashboard.php',
    '/pharmacy/verify-prescription'   => 'pharmacy/verify-prescription.php',
    '/admin/dashboard'                => 'admin/dashboard.php',
    '/admin/doctors'                  => 'admin/doctors.php',
    '/admin/patients'                 => 'admin/patients.php',
    '/admin/pharmacies'               => 'admin/pharmacies.php',
    '/admin/facilities'               => 'admin/facilities.php',
    '/admin/reports'                  => 'admin/reports.php',
];

// API routes
if (preg_match('#^/api/(.+)$#', $route, $apiMatch)) {
    $apiName = $apiMatch[1];
    $apiFile = dirname(__DIR__) . '/api/' . $apiName;
    if (!file_exists($apiFile) && file_exists($apiFile . '.php')) $apiFile .= '.php';

    if (file_exists($apiFile)) {
        header('Content-Type: application/json');
        require $apiFile;
        exit;
    }
}

// Match route to file
$fileToLoad = null;
if (isset($publicRoutes[$route])) {
    $fileToLoad = dirname(__DIR__) . '/' . $publicRoutes[$route];
} elseif (isset($protectedRoutes[$route])) {
    $fileToLoad = dirname(__DIR__) . '/' . $protectedRoutes[$route];
}

if ($fileToLoad && file_exists($fileToLoad)) {
    ob_start();
    require $fileToLoad;
    $output = ob_get_clean();

    // Determine if we should wrap in layout
    $isJson = is_string($output) && strlen($output) > 0 && $output[0] === '{';

    if ($isJson || isset($noLayout)) {
        header('Content-Type: application/json');
        echo $output;
    } else {
        require dirname(__DIR__) . '/includes/header.php';
        require dirname(__DIR__) . '/includes/navbar.php';
        echo $output;
        require dirname(__DIR__) . '/includes/footer.php';
    }
    exit;
}

// 404 handler
http_response_code(404);
$pageTitle = 'Page Not Found';
require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navbar.php';
echo '<div class="empty-state" style="text-align:center;padding:4rem 1rem;"><div style="font-size:5rem;margin-bottom:1rem;">404</div><h1>Page Not Found</h1><p style="color:#6C757D;margin:1rem 0;">The page you requested does not exist.</p><a href="/" class="btn btn-primary">Go Home</a></div>';
require dirname(__DIR__) . '/includes/footer.php';
