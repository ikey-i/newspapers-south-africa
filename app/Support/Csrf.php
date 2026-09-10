<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Per-session CSRF token. One token per session, rotated only on request.
 */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        start_session();
        if (empty($_SESSION[self::KEY]) || !is_string($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function check(?string $token): bool
    {
        start_session();
        $expected = $_SESSION[self::KEY] ?? null;
        return is_string($expected)
            && is_string($token)
            && $token !== ''
            && hash_equals($expected, $token);
    }

    public static function rotate(): void
    {
        start_session();
        $_SESSION[self::KEY] = bin2hex(random_bytes(32));
    }
}
