<?php

/**
 * Send a one-off test email using the current config('mail.*') settings.
 * Handy for checking mail.method = 'smtp' credentials before inviting
 * publishers.
 *
 *   php scripts/test-mail.php you@example.com
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

use App\Support\Mailer;

$to = trim((string) ($argv[1] ?? ''));
if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Usage: php scripts/test-mail.php you@example.com\n");
    exit(1);
}

echo 'Sending via mail.method = ' . config('mail.method', 'log') . " ...\n";

$ok = Mailer::send(
    $to,
    'Test email from ' . config('app.name'),
    "This is a test message from " . config('app.name') . ".\n\n"
    . "If you received this, your mail configuration works.\n"
);

if ($ok) {
    echo "Sent (or logged) successfully.\n";
    if (config('mail.method', 'log') === 'log') {
        echo "Check var/mail/ for the message (nothing was actually sent).\n";
    }
    exit(0);
}

fwrite(STDERR, "Failed. Check the PHP error log for details.\n");
exit(1);
