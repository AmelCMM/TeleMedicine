<?php

define('APP_NAME', 'MediConnect');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost:8000');
define('ROOT_PATH', dirname(__DIR__));
define('SECRET_KEY', 'mc_secret_key_change_in_production_2024');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// View rendering function
function view(string $viewPath, array $data = []): void
{
    extract($data);
    ob_start();
    require ROOT_PATH . "/{$viewPath}.php";
    $content = ob_get_clean();
    require ROOT_PATH . '/includes/header.php';
    require ROOT_PATH . '/includes/navbar.php';
    echo $content;
    require ROOT_PATH . '/includes/footer.php';
}
