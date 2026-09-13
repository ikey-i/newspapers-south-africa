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
        // A one-time secret for /setup.php — the web-based installer for hosts
        // with no shell access. Leave blank to keep /setup.php refusing to run
        // at all. Set it to a long random string before your first deploy,
        // visit /setup.php?token=<that string>, then blank this again (or
        // delete setup.php) once your admin account exists — see the README.
        'setup_token' => '',
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

    // Who is legally operating this site — shown on /privacy and /terms.
    // POPIA (South Africa's data protection act) requires every "responsible
    // party" to name a registered Information Officer; if that's you, your
    // own details are fine. Fill these in before launch.
    'legal' => [
        'operator_name'      => '[Your name or registered business name]',
        'operator_address'   => '[Your registered/physical address, city, South Africa]',
        'info_officer_name'  => '[Information Officer name]',
        // Falls back to the "Contact email" set in /admin/settings if left blank.
        'info_officer_email' => '',
    ],
];
