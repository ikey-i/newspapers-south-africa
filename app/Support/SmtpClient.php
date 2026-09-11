<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A minimal synchronous SMTP client — no Composer dependency, so it can ship
 * on plain shared hosting. Supports implicit TLS (port 465), STARTTLS
 * (port 587/25) and AUTH LOGIN. One message per connection; not pooled.
 *
 * Deliberately narrow: this sends one plain-text message and nothing else.
 * If richer mail (HTML, attachments, bulk sending) is ever needed, replace
 * this with a real library instead of growing it.
 */
final class SmtpClient
{
    /** @var resource|null */
    private $socket = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $encryption,   // 'tls' (STARTTLS), 'ssl' (implicit), or 'none'
        private readonly ?string $username,
        private readonly ?string $password,
        private readonly int $timeout = 12,
    ) {
    }

    /**
     * @param list<string> $headers  Header lines, no trailing CRLF.
     * @throws \RuntimeException on any SMTP-level failure.
     */
    public function send(string $from, string $to, array $headers, string $body): void
    {
        $transport = $this->encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $errno = 0;
        $errstr = '';

        $this->socket = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT
        );
        if ($this->socket === false) {
            throw new \RuntimeException("Could not connect to {$this->host}:{$this->port}: {$errstr}");
        }
        stream_set_timeout($this->socket, $this->timeout);

        try {
            $this->expect(220);
            $this->command('EHLO ' . $this->heloName(), 250);

            if ($this->encryption === 'tls') {
                $this->command('STARTTLS', 220);
                $this->enableCrypto();
                // Servers require re-negotiating capabilities after STARTTLS.
                $this->command('EHLO ' . $this->heloName(), 250);
            }

            if ($this->username !== null && $this->username !== '') {
                $this->command('AUTH LOGIN', 334);
                $this->command(base64_encode($this->username), 334);
                $this->command(base64_encode((string) $this->password), 235);
            }

            $this->command('MAIL FROM:<' . $from . '>', 250);
            $this->command('RCPT TO:<' . $to . '>', [250, 251]);
            $this->command('DATA', 354);

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $this->dotStuff($body);
            $this->write(rtrim($message, "\r\n") . "\r\n.\r\n");
            $this->expect(250);

            $this->command('QUIT', 221);
        } finally {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function heloName(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    private function enableCrypto(): void
    {
        $methods = 0;
        foreach (['STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT', 'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT', 'STREAM_CRYPTO_METHOD_TLS_CLIENT'] as $const) {
            if (defined($const)) {
                $methods |= constant($const);
            }
        }
        if ($methods === 0) {
            $methods = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        }
        if (@stream_socket_enable_crypto($this->socket, true, $methods) !== true) {
            throw new \RuntimeException('TLS negotiation with the mail server failed.');
        }
    }

    /** @param int|list<int> $expectedCode */
    private function command(string $line, int|array $expectedCode): string
    {
        $this->write($line . "\r\n");
        return $this->expect($expectedCode);
    }

    /** @param int|list<int> $expectedCode */
    private function expect(int|array $expectedCode): string
    {
        $expected = is_array($expectedCode) ? $expectedCode : [$expectedCode];
        $response = $this->readResponse();
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException("Unexpected SMTP response: {$response}");
        }
        return $response;
    }

    private function readResponse(): string
    {
        $lines = [];
        do {
            $line = fgets($this->socket, 1024);
            if ($line === false) {
                throw new \RuntimeException('Connection to the mail server was lost.');
            }
            $lines[] = $line;
            // Multi-line responses use "250-text"; the final line uses "250 text".
        } while (isset($line[3]) && $line[3] === '-');

        return implode('', $lines);
    }

    private function write(string $data): void
    {
        if (@fwrite($this->socket, $data) === false) {
            throw new \RuntimeException('Failed to write to the mail server connection.');
        }
    }

    /** RFC 5321 transparency: lines starting with "." get an extra leading ".". */
    private function dotStuff(string $body): string
    {
        $normalised = str_replace(["\r\n", "\r", "\n"], "\n", $body);
        $lines = explode("\n", $normalised);
        foreach ($lines as &$line) {
            if (str_starts_with($line, '.')) {
                $line = '.' . $line;
            }
        }
        return implode("\r\n", $lines);
    }
}
