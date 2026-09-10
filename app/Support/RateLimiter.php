<?php

declare(strict_types=1);

namespace App\Support;

use App\Database;

/**
 * Simple database-backed rate limiting: count recent rows written by the same
 * client in a table that carries an `ip_hash` and `created_at` column.
 */
final class RateLimiter
{
    /** Tables this limiter is allowed to look at. */
    private const TABLES = ['signup_attempts', 'password_resets', 'publisher_login_attempts'];

    public static function tooMany(string $table, int $max, int $minutes): bool
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new \InvalidArgumentException("Unsupported table: {$table}");
        }

        $minutes = max(1, $minutes);

        $row = Database::first(
            "SELECT COUNT(*) AS c FROM {$table}
             WHERE ip_hash = ? AND created_at >= (NOW() - INTERVAL {$minutes} MINUTE)",
            [client_ip_hash()]
        );

        return (int) ($row['c'] ?? 0) >= $max;
    }
}
