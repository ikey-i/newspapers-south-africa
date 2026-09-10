<?php

declare(strict_types=1);

namespace App\Support;

use App\Database;

/**
 * Publisher (newsroom) authentication: password check, session, brute-force
 * lockout. Mirrors App\Support\Auth but for the `publishers` table.
 */
final class PublisherAuth
{
    private const SESSION_KEY = 'publisher_id';
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * The signed-in publisher joined to their newspaper, or null.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        start_session();
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }
        $row = Database::first(
            "SELECT p.*, n.name AS newspaper_name, n.slug AS newspaper_slug,
                    n.status AS newspaper_status
             FROM publishers p
             JOIN newspapers n ON n.id = p.newspaper_id
             WHERE p.id = ? AND p.is_active = 1",
            [(int) $id]
        );
        return $row ?: null;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            start_session();
            $_SESSION['publisher_intended'] = $_SERVER['REQUEST_URI'] ?? url('publish');
            redirect(url('publish/login'));
        }
        return $user;
    }

    /**
     * Guard for actions that publish content: the account must be verified and
     * the newspaper approved. Returns the user; redirects to the dashboard
     * (which explains the hold) otherwise.
     *
     * @return array<string,mixed>
     */
    public static function requireApproved(): array
    {
        $user = self::requireLogin();
        if (empty($user['email_verified_at']) || $user['newspaper_status'] !== 'active') {
            flash('publish_notice', 'Your account is not active yet — see below.');
            redirect(url('publish'));
        }
        return $user;
    }

    public static function isLockedOut(): bool
    {
        $row = Database::first(
            "SELECT COUNT(*) AS c FROM publisher_login_attempts
             WHERE ip_hash = ? AND successful = 0
               AND created_at >= (NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE)",
            [client_ip_hash()]
        );
        return (int) ($row['c'] ?? 0) >= self::MAX_ATTEMPTS;
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = mb_strtolower(trim($email));
        $publisher = Database::first('SELECT * FROM publishers WHERE email = ?', [$email]);

        $ok = $publisher !== null
            && (int) $publisher['is_active'] === 1
            && password_verify($password, (string) $publisher['password_hash']);

        Database::execute(
            'INSERT INTO publisher_login_attempts (ip_hash, email, successful) VALUES (?, ?, ?)',
            [client_ip_hash(), $email !== '' ? mb_substr($email, 0, 160) : null, $ok ? 1 : 0]
        );

        if (!$ok) {
            return false;
        }

        if (password_needs_rehash((string) $publisher['password_hash'], PASSWORD_DEFAULT)) {
            Database::execute(
                'UPDATE publishers SET password_hash = ? WHERE id = ?',
                [password_hash($password, PASSWORD_DEFAULT), (int) $publisher['id']]
            );
        }

        self::login((int) $publisher['id']);
        Database::execute('UPDATE publishers SET last_login_at = NOW() WHERE id = ?', [(int) $publisher['id']]);

        return true;
    }

    public static function login(int $publisherId): void
    {
        start_session();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION[self::SESSION_KEY] = $publisherId;
    }

    public static function logout(): void
    {
        start_session();
        unset($_SESSION[self::SESSION_KEY]);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function intended(): string
    {
        start_session();
        $to = $_SESSION['publisher_intended'] ?? null;
        unset($_SESSION['publisher_intended']);
        if (is_string($to) && str_starts_with($to, '/') && !str_starts_with($to, '//') && str_contains($to, '/publish')) {
            return $to;
        }
        return url('publish');
    }

    public static function purgeOldAttempts(): void
    {
        Database::execute(
            'DELETE FROM publisher_login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)'
        );
    }
}
