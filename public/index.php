<?php

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/routes/Router.php';

header('Access-Control-Allow-Origin: *');

$router = new Router();

require_once dirname(__DIR__) . '/routes/web.php';

$router->dispatch();