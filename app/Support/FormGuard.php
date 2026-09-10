<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lightweight spam defences for public forms: a honeypot field and a
 * minimum time-to-complete check. Used alongside (not instead of) the CSRF
 * token and the rate limiter.
 */
final class FormGuard
{
    /** Bots love to fill anything that looks like a URL/name field. */
    private const HONEYPOT = 'contact_url';

    /** Reject submissions completed faster than this (seconds). */
    private const MIN_SECONDS = 2;

    /** Treat the form as stale after this (seconds). */
    private const MAX_SECONDS = 3600;

    /**
     * Hidden honeypot input plus a timestamp marker in the session.
     * Call this when rendering the form.
     */
    public static function fields(string $formId): string
    {
        start_session();
        $_SESSION['_form_ts'][$formId] = time();

        return '<div class="form-hp" aria-hidden="true">'
            . '<label>Leave this field blank'
            . '<input type="text" name="' . self::HONEYPOT . '" tabindex="-1" autocomplete="off"></label>'
            . '</div>';
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function isSpam(string $formId, array $input): bool
    {
        if (trim((string) ($input[self::HONEYPOT] ?? '')) !== '') {
            return true;
        }

        start_session();
        $ts = $_SESSION['_form_ts'][$formId] ?? null;
        unset($_SESSION['_form_ts'][$formId]);

        if (!is_int($ts)) {
            return false; // no marker (e.g. cookie dropped) — don't punish
        }

        $elapsed = time() - $ts;
        return $elapsed < self::MIN_SECONDS || $elapsed > self::MAX_SECONDS;
    }
}
