<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Tentukan path direktori Laravel (mendukung local maupun struktur cPanel ../laravel_app)
$basePath = __DIR__ . '/..';
if (!file_exists($basePath . '/vendor/autoload.php') && file_exists(__DIR__ . '/../laravel_app/vendor/autoload.php')) {
    $basePath = __DIR__ . '/../laravel_app';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath . '/bootstrap/app.php';

$app->handleRequest(Request::capture());

