<?php

declare(strict_types=1);

namespace App\Support;

use App\Database;

/**
 * Admin authentication: password check, session, and brute-force lockout.
 */
final class Auth
{
    private const SESSION_KEY = 'admin_id';

    /** Failed attempts from one client before a temporary lockout. */
    private const MAX_ATTEMPTS = 5;

    /** Lockout / attempt window, minutes. */
    private const WINDOW_MINUTES = 15;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        start_session();
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }
        $user = Database::first('SELECT id, username, last_login_at FROM admins WHERE id = ?', [(int) $id]);
        return $user ?: null;
    }

    /**
     * Redirect to the login page (remembering where we were headed) unless
     * the current request is authenticated.
     */
    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }
        start_session();
        $_SESSION['admin_intended'] = $_SERVER['REQUEST_URI'] ?? url('admin');
        redirect(url('admin/login'));
    }

    public static function isLockedOut(): bool
    {
        $row = Database::first(
            "SELECT COUNT(*) AS c FROM admin_login_attempts
             WHERE ip_hash = ? AND successful = 0
               AND created_at >= (NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE)",
            [client_ip_hash()]
        );
        return (int) ($row['c'] ?? 0) >= self::MAX_ATTEMPTS;
    }

    /**
     * Try to log in. Returns true on success. Always records the attempt.
     */
    public static function attempt(string $username, string $password): bool
    {
        $username = trim($username);
        $admin = Database::first('SELECT * FROM admins WHERE username = ?', [$username]);

        $ok = $admin !== null && password_verify($password, (string) $admin['password_hash']);

        Database::execute(
            'INSERT INTO admin_login_attempts (ip_hash, username, successful) VALUES (?, ?, ?)',
            [client_ip_hash(), $username !== '' ? mb_substr($username, 0, 60) : null, $ok ? 1 : 0]
        );

        if (!$ok) {
            return false;
        }

        if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_DEFAULT)) {
            Database::execute(
                'UPDATE admins SET password_hash = ? WHERE id = ?',
                [password_hash($password, PASSWORD_DEFAULT), (int) $admin['id']]
            );
        }

        self::login((int) $admin['id']);
        Database::execute('UPDATE admins SET last_login_at = NOW() WHERE id = ?', [(int) $admin['id']]);

        return true;
    }

    public static function login(int $adminId): void
    {
        start_session();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION[self::SESSION_KEY] = $adminId;
    }

    public static function logout(): void
    {
        start_session();
        unset($_SESSION[self::SESSION_KEY]);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Where to send the user after logging in.
     */
    public static function intended(): string
    {
        start_session();
        $to = $_SESSION['admin_intended'] ?? null;
        unset($_SESSION['admin_intended']);
        if (is_string($to) && str_starts_with($to, '/') && !str_starts_with($to, '//')) {
            return $to;
        }
        return url('admin');
    }

    public static function purgeOldAttempts(): void
    {
        // Housekeeping — keep the table small. Runs cheaply on login POST.
        Database::execute(
            'DELETE FROM admin_login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)'
        );
    }
}
