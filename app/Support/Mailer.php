<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Minimal transactional mailer. Two backends, chosen by config('mail.method'):
 *
 *   'log'  — append the message to var/mail/ (local dev; nothing is sent).
 *   'mail' — PHP's mail() (cPanel / shared hosting default).
 *
 * Kept deliberately small and swappable so an SMTP backend can be dropped in
 * later without touching callers.
 */
final class Mailer
{
    /**
     * @return bool  true if the message was handed off (or logged) successfully.
     */
    public static function send(string $to, string $subject, string $body): bool
    {
        $to = trim($to);
        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $fromEmail = (string) config('mail.from', 'no-reply@localhost');
        $fromName  = (string) config('mail.from_name', config('app.name', 'Newspapers'));
        $subject   = self::sanitiseHeader($subject);

        $headers = [
            'From: ' . self::encodeName($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: NewspapersSA',
        ];

        $method = (string) config('mail.method', 'log');

        if ($method === 'mail') {
            return @mail($to, $subject, $body, implode("\r\n", $headers));
        }

        return self::logMessage($to, $subject, $body, $headers);
    }

    private static function logMessage(string $to, string $subject, string $body, array $headers): bool
    {
        $dir = BASE_PATH . '/var/mail';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        $file = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt';
        $content = "To: {$to}\nSubject: {$subject}\n" . implode("\n", $headers) . "\n\n" . $body . "\n";
        return @file_put_contents($file, $content) !== false;
    }

    private static function sanitiseHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $value));
    }

    private static function encodeName(string $name): string
    {
        $name = self::sanitiseHeader($name);
        return preg_match('/[^\x20-\x7e]/', $name) === 1
            ? '=?UTF-8?B?' . base64_encode($name) . '?='
            : '"' . str_replace('"', '', $name) . '"';
    }
}
