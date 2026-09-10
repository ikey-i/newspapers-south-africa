<?php

/**
 * Application bootstrap: configuration, error handling, autoloading, helpers.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------
if (is_file(BASE_PATH . '/config.php')) {
    $config = require BASE_PATH . '/config.php';
    define('CONFIG_IS_SAMPLE', false);
} else {
    // Allows a freshly cloned copy to boot and show setup instructions.
    $config = require BASE_PATH . '/config.sample.php';
    define('CONFIG_IS_SAMPLE', true);
}

$GLOBALS['config'] = $config;

// ---------------------------------------------------------------------------
// Environment
// ---------------------------------------------------------------------------
date_default_timezone_set($config['app']['timezone'] ?? 'Africa/Johannesburg');

$debug = (bool) ($config['app']['debug'] ?? false);

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

// ---------------------------------------------------------------------------
// Autoloader for the App\ namespace (app/Foo/Bar.php => App\Foo\Bar)
// ---------------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// ---------------------------------------------------------------------------
// Procedural helpers
// ---------------------------------------------------------------------------
require APP_PATH . '/helpers.php';

// ---------------------------------------------------------------------------
// Global error / exception handling
// ---------------------------------------------------------------------------
set_exception_handler(static function (\Throwable $e) use ($debug): void {
    error_log((string) $e);
    http_response_code(500);

    if ($debug) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "500 Internal Server Error\n\n";
        echo $e::class . ': ' . $e->getMessage() . "\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo $e->getTraceAsString() . "\n";
        return;
    }

    if (function_exists('render_error_page')) {
        render_error_page(500);
        return;
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Something went wrong. Please try again later.';
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
