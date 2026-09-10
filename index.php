<?php

/**
 * Newspapers South Africa — front controller.
 *
 * Every request that is not a real file on disk is routed here by .htaccess
 * (production) or router.php (PHP built-in server).
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$router = require __DIR__ . '/app/routes.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'
);
