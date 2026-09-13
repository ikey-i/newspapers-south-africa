<?php

/**
 * Database migration / setup CLI.
 *
 *   php db/migrate.php                          Apply db/schema.sql
 *   php db/migrate.php create-admin USER PASS   Create or update an admin login
 *
 * The ADMIN_USERNAME / ADMIN_PASSWORD environment variables are used as a
 * fallback when create-admin is called without arguments.
 *
 * No shell access on your host? Use /setup.php instead — see the README.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

use App\Support\Migrator;

if (CONFIG_IS_SAMPLE) {
    fwrite(STDERR, "No config.php found. Copy config.sample.php to config.php first.\n");
    exit(1);
}

$command = $argv[1] ?? 'migrate';

try {
    match ($command) {
        'migrate'      => runMigration(),
        'create-admin' => createAdmin($argv[2] ?? getenv('ADMIN_USERNAME') ?: '', $argv[3] ?? getenv('ADMIN_PASSWORD') ?: ''),
        default        => throw new RuntimeException("Unknown command: {$command}"),
    };
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

function runMigration(): void
{
    $applied = Migrator::apply();
    echo "Schema applied ({$applied} statements).\n";

    if (Migrator::adminCount() === 0) {
        echo "\nNo admin account exists yet. Create one with:\n";
        echo "  php db/migrate.php create-admin <username> <password>\n";
    }
}

function createAdmin(string $username, string $password): void
{
    Migrator::createAdmin($username, $password);
    echo "Admin account '{$username}' is ready.\n";
}
