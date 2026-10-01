<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// First run on a fresh upload: create .env from the template with a unique app key,
// so the web installer at /install can start without SSH.
$envFile = __DIR__.'/../.env';
if (! file_exists($envFile) && file_exists(__DIR__.'/../.env.example')) {
    @copy(__DIR__.'/../.env.example', $envFile);
}
if (file_exists($envFile) && preg_match('/^APP_KEY=\s*$/m', (string) file_get_contents($envFile))) {
    $key = 'base64:'.base64_encode(random_bytes(32));
    @file_put_contents($envFile, preg_replace('/^APP_KEY=\s*$/m', 'APP_KEY='.$key, file_get_contents($envFile)));
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
