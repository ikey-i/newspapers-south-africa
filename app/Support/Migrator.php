<?php

declare(strict_types=1);

namespace App\Support;

use App\Database;
use RuntimeException;

/**
 * Applies db/schema.sql and creates the first admin login. Shared by the CLI
 * (db/migrate.php) and the no-shell-access web installer (setup.php) so
 * neither one drifts from the other.
 */
final class Migrator
{
    /**
     * Apply db/schema.sql. Safe to call repeatedly — every statement in the
     * file uses IF NOT EXISTS / ON DUPLICATE KEY UPDATE.
     *
     * @return int Number of statements executed.
     */
    public static function apply(): int
    {
        $sql = file_get_contents(BASE_PATH . '/db/schema.sql');
        if ($sql === false) {
            throw new RuntimeException('Could not read db/schema.sql');
        }

        $pdo = Database::connection();
        $statements = self::splitSqlStatements($sql);

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        return count($statements);
    }

    public static function adminCount(): int
    {
        return (int) (Database::first('SELECT COUNT(*) AS c FROM admins')['c'] ?? 0);
    }

    /**
     * Create or update an admin login.
     *
     * @throws RuntimeException if the username/password don't meet the
     *         minimum requirements.
     */
    public static function createAdmin(string $username, string $password): void
    {
        $username = trim($username);
        if ($username === '' || strlen($password) < 8) {
            throw new RuntimeException('Provide a username and a password of at least 8 characters.');
        }

        Database::execute(
            'INSERT INTO admins (username, password_hash) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)',
            [$username, password_hash($password, PASSWORD_DEFAULT)]
        );
    }

    /**
     * Split a SQL file into individual statements.
     *
     * Handles "--" line comments and blank lines. The schema deliberately
     * avoids semicolons inside string literals so a simple split is safe here.
     *
     * @return list<string>
     */
    private static function splitSqlStatements(string $sql): array
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
}
