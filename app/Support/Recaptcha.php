<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * Google reCAPTCHA v2 (checkbox), driven by the site settings — same pattern
 * as Ads. Inactive (and every check passes) until an admin enables it and
 * sets both keys, so local dev and CI never need real keys.
 */
final class Recaptcha
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public static function enabled(): bool
    {
        return Setting::bool('recaptcha_enabled')
            && Setting::get('recaptcha_site_key') !== ''
            && Setting::get('recaptcha_secret_key') !== '';
    }

    public static function siteKey(): string
    {
        return Setting::get('recaptcha_site_key');
    }

    /**
     * The widget markup plus its (on-demand) script tag, or '' when disabled.
     */
    public static function widget(): string
    {
        if (!self::enabled()) {
            return '';
        }
        $key = e(self::siteKey());
        return '<div class="g-recaptcha" data-sitekey="' . $key . '"></div>'
            . '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }

    /**
     * Verify a submitted g-recaptcha-response token against Google's API.
     * Returns true when reCAPTCHA is disabled (nothing to check), or when the
     * token is confirmed valid. Fails closed: an unreachable/misconfigured
     * verification service counts as a failure, not a free pass.
     */
    public static function verify(?string $token): bool
    {
        if (!self::enabled()) {
            return true;
        }

        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $params = [
            'secret'   => Setting::get('recaptcha_secret_key'),
            'response' => $token,
            'remoteip' => client_ip(),
        ];

        $body = self::post(self::VERIFY_URL, $params);
        if ($body === null) {
            error_log('Recaptcha: verification request failed (network/timeout).');
            return false;
        }

        $data = json_decode($body, true);
        return is_array($data) && ($data['success'] ?? false) === true;
    }

    /**
     * @param array<string,string> $params
     */
    private static function post(string $url, array $params): ?string
    {
        $payload = http_build_query($params);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $result = curl_exec($ch);
            $failed = $result === false || curl_errno($ch) !== 0;
            curl_close($ch);
            return $failed ? null : (string) $result;
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        return $result === false ? null : $result;
    }
}
