<?php

/**
 * Router for the PHP built-in web server:
 *
 *     php -S localhost:8000 router.php
 *
 * Serves real files (assets, uploads) directly and hands everything else to
 * the front controller. Not used in production — Apache/.htaccess does this.
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    // Let the built-in server handle static files.
    return false;
}

require __DIR__ . '/index.php';
