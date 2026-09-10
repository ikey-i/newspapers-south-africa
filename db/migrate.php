<?php

/**
 * Database migration / setup CLI.
 *
 *   php db/migrate.php                          Apply db/schema.sql
 *   php db/migrate.php create-admin USER PASS   Create or update an admin login
 *
 * The ADMIN_USERNAME / ADMIN_PASSWORD environment variables are used as a
 * fallback when create-admin is called without arguments.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

use App\Database;

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
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('Could not read schema.sql');
    }

    $pdo = Database::connection();
    $statements = splitSqlStatements($sql);

    $applied = 0;
    foreach ($statements as $statement) {
        $pdo->exec($statement);
        $applied++;
    }

    echo "Schema applied ({$applied} statements).\n";

    $count = (int) ($pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() ?: 0);
    if ($count === 0) {
        echo "\nNo admin account exists yet. Create one with:\n";
        echo "  php db/migrate.php create-admin <username> <password>\n";
    }
}

function createAdmin(string $username, string $password): void
{
    $username = trim($username);
    if ($username === '' || strlen($password) < 8) {
        throw new RuntimeException('Provide a username and a password of at least 8 characters.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    Database::execute(
        'INSERT INTO admins (username, password_hash) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)',
        [$username, $hash]
    );

    echo "Admin account '{$username}' is ready.\n";
}

/**
 * Split a SQL file into individual statements.
 *
 * Handles "--" line comments and blank lines. The schema deliberately avoids
 * semicolons inside string literals so a simple split is safe here.
 *
 * @return list<string>
 */
function splitSqlStatements(string $sql): array
{
    $lines = preg_split('/\r\n|\r|\n/', $sql) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }
        $clean[] = $line;
    }

    $joined = implode("\n", $clean);
    $parts = array_map('trim', explode(';', $joined));

    return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
}
