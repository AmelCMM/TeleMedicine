<?php

define('APP_NAME', 'TeleMed Zambia');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost:8000');
define('ROOT_PATH', dirname(__DIR__));

// Render a view inside the main layout
function view(string $viewPath, array $data = []): void
{
    // Make $data keys available as variables inside the view
    extract($data);

    // Capture the view's HTML into $content
    ob_start();
    require ROOT_PATH . "/app/views/{$viewPath}.php";
    $content = ob_get_clean();

    // Inject into layout
    require ROOT_PATH . '/app/views/layouts/main.php';
}
