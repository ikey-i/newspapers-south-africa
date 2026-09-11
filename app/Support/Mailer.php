<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Minimal transactional mailer. Three backends, chosen by config('mail.method'):
 *
 *   'log'  — append the message to var/mail/ (local dev; nothing is sent).
 *   'mail' — PHP's mail() (cPanel / shared hosting default; deliverability
 *            depends on the host's mail setup — set SPF/DKIM).
 *   'smtp' — deliver via App\Support\SmtpClient using config('mail.smtp.*').
 *            Use this for a real mailbox / transactional-email provider.
 *
 * Kept deliberately small: one plain-text message per call, no queueing.
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
            'To: ' . $to,
            'Subject: ' . $subject,
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . self::messageIdHost() . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: NewspapersSA',
        ];

        $method = (string) config('mail.method', 'log');

        if ($method === 'smtp') {
            return self::sendSmtp($fromEmail, $to, $headers, $body);
        }

        if ($method === 'mail') {
            // mail() takes To/Subject as separate arguments — strip them from
            // the header block it's also given, or most MTAs will duplicate them.
            $mailHeaders = array_values(array_filter(
                $headers,
                static fn (string $h): bool => !str_starts_with($h, 'To: ') && !str_starts_with($h, 'Subject: ')
            ));
            return @mail($to, $subject, $body, implode("\r\n", $mailHeaders));
        }

        return self::logMessage($headers, $body);
    }

    private static function sendSmtp(string $fromEmail, string $to, array $headers, string $body): bool
    {
        $cfg = (array) config('mail.smtp', []);
        $host = (string) ($cfg['host'] ?? '');
        if ($host === '') {
            error_log('Mailer: mail.method is "smtp" but mail.smtp.host is not configured.');
            return false;
        }

        $client = new SmtpClient(
            host: $host,
            port: (int) ($cfg['port'] ?? 587),
            encryption: (string) ($cfg['encryption'] ?? 'tls'),
            username: isset($cfg['username']) && $cfg['username'] !== '' ? (string) $cfg['username'] : null,
            password: isset($cfg['password']) ? (string) $cfg['password'] : null,
        );

        try {
            $client->send($fromEmail, $to, $headers, $body);
            return true;
        } catch (\Throwable $e) {
            error_log('Mailer (smtp): ' . $e->getMessage());
            return false;
        }
    }

    private static function messageIdHost(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    private static function logMessage(array $headers, string $body): bool
    {
        $dir = BASE_PATH . '/var/mail';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        $file = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt';
        $content = implode("\n", $headers) . "\n\n" . $body . "\n";
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
