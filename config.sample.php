<?php

/**
 * Application configuration.
 *
 * Copy this file to `config.php` and fill in real values.
 * `config.php` is git-ignored and must never be committed.
 */

return [
    'app' => [
        'name'  => 'Newspapers South Africa',
        // 'local' enables verbose errors; use 'production' on the live site.
        'env'   => 'local',
        'debug' => true,
        // Public base URL, no trailing slash. Used for canonical URLs, sitemaps
        // and the links in verification / password-reset emails.
        'url'   => 'http://localhost:8000',
        // Timezone for timestamps shown in the admin and publisher areas.
        'timezone' => 'Africa/Johannesburg',
        // Trust X-Forwarded-For / CF-Connecting-IP (only behind a known proxy).
        'trust_proxy' => false,
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'newspapers_sa',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        // 'log'  — write messages to var/mail/ instead of sending (local dev).
        // 'mail' — use PHP's mail() (cPanel / shared hosting default).
        // 'smtp' — deliver via the mail.smtp settings below (a real mailbox
        //          or transactional-email provider — better deliverability).
        'method' => 'log',
        // From address for verification and password-reset emails.
        'from'      => 'no-reply@localhost',
        'from_name' => 'Newspapers South Africa',

        // Only used when method = 'smtp'.
        'smtp' => [
            'host'       => '',            // e.g. smtp.yourhost.co.za
            'port'       => 587,            // 587 = STARTTLS, 465 = implicit TLS, 25 = none
            'encryption' => 'tls',          // 'tls' | 'ssl' | 'none'
            'username'   => '',
            'password'   => '',
        ],
    ],
];
