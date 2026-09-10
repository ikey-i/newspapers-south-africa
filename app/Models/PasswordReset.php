<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Single-use password-reset tokens for publisher accounts.
 */
final class PasswordReset
{
    private const TTL_MINUTES = 60;

    /** Create a token, invalidating any earlier unused ones for the address. */
    public static function issue(string $email): string
    {
        $email = mb_strtolower(trim($email));
        Database::execute(
            'UPDATE password_resets SET used_at = NOW() WHERE email = ? AND used_at IS NULL',
            [$email]
        );

        $token = bin2hex(random_bytes(32));
        Database::execute(
            'INSERT INTO password_resets (email, token_hash, ip_hash) VALUES (?, ?, ?)',
            [$email, hash('sha256', $token), client_ip_hash()]
        );
        return $token;
    }

    /** @return string|null  The email address if the token is valid and unused. */
    public static function emailForToken(string $token): ?string
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = Database::first(
            'SELECT email FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL
               AND created_at >= (NOW() - INTERVAL ' . self::TTL_MINUTES . ' MINUTE)',
            [hash('sha256', $token)]
        );
        return $row === null ? null : (string) $row['email'];
    }

    public static function consume(string $token): void
    {
        Database::execute(
            'UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?',
            [hash('sha256', $token)]
        );
    }
}
