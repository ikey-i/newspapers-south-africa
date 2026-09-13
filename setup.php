<?php

/**
 * One-time web installer for hosts with no shell/SSH access: applies
 * db/schema.sql and creates the first admin login, both over HTTP.
 *
 * Gated by config('app.setup_token') — set that to a random string in
 * config.php, then visit:
 *
 *   https://yoursite/setup.php?token=<that string>
 *
 * It refuses to do anything once an admin account already exists, and
 * refuses to run at all while the token is blank. Delete this file (or
 * blank the token again) once you're done — see the README.
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Database;
use App\Support\Migrator;

header('X-Robots-Tag: noindex, nofollow');

/**
 * @param array{href:string,label:string}|null $link
 */
function setup_page(string $heading, string $bodyHtml, int $status = 200, ?array $link = null): never
{
    http_response_code($status);
    if ($link !== null) {
        $bodyHtml .= '<p class="admin-auth__back"><a href="' . e($link['href']) . '">' . e($link['label']) . '</a></p>';
    }
    echo '<!doctype html><html lang="en-ZA"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow">'
        . '<title>' . e($heading) . ' — ' . e((string) config('app.name')) . '</title>'
        . '<link rel="stylesheet" href="' . e(asset('assets/css/style.css')) . '">'
        . '<link rel="stylesheet" href="' . e(asset('assets/css/admin.css')) . '">'
        . '</head><body class="admin-auth"><main class="admin-auth__card">'
        . '<h1>' . e((string) config('app.name')) . '</h1>'
        . '<p class="admin-auth__sub">Site installer</p>'
        . $bodyHtml
        . '</main></body></html>';
    exit;
}

// ---------------------------------------------------------------------------
if (CONFIG_IS_SAMPLE) {
    setup_page('Not configured', '<p>No <code>config.php</code> found. Copy <code>config.sample.php</code> to '
        . '<code>config.php</code>, fill in your database details, and reload this page.</p>', 503);
}

$configuredToken = trim((string) config('app.setup_token', ''));
if ($configuredToken === '') {
    setup_page('Installer disabled', '<p>Set <code>app.setup_token</code> in <code>config.php</code> to a long '
        . 'random string, then reload this page with <code>?token=</code> that value.</p>', 403);
}

$suppliedToken = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
if (!hash_equals($configuredToken, $suppliedToken)) {
    setup_page('Not authorised', '<p>Missing or incorrect setup token.</p>', 403);
}
$tokenField = '<input type="hidden" name="token" value="' . e($suppliedToken) . '">';
$tokenQuery = '?token=' . rawurlencode($suppliedToken);

// ---------------------------------------------------------------------------
// Apply the schema (safe to repeat — every statement is IF NOT EXISTS).
try {
    $applied = Migrator::apply();
} catch (Throwable $e) {
    $detail = (bool) config('app.debug', false) ? '<p class="admin-auth__sub">' . e($e->getMessage()) . '</p>' : '';
    setup_page('Could not reach the database', '<p>Check the <code>db</code> settings in <code>config.php</code>, '
        . 'then reload this page.</p>' . $detail, 500);
}

if (Migrator::adminCount() > 0) {
    setup_page('Already set up', '<p>Schema applied (' . (int) $applied . ' statements checked) and an admin '
        . 'account already exists. There is nothing left for this page to do.</p>'
        . '<p><strong>Delete <code>setup.php</code> now</strong> (or blank <code>app.setup_token</code> in '
        . '<code>config.php</code>) — it should not stay reachable.</p>', 200, ['href' => url('admin/login'), 'label' => 'Go to admin sign in']);
}

// ---------------------------------------------------------------------------
// No admin yet: show the create-admin form, or handle its submission.
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '' || strlen($password) < 8) {
        $error = 'Enter a username and a password of at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'The passwords do not match.';
    } else {
        Migrator::createAdmin($username, $password);
        setup_page('Admin account created', '<p><strong>“' . e($username) . '”</strong> can now sign in.</p>'
            . '<p>Schema applied (' . (int) $applied . ' statements checked).</p>'
            . '<p><strong>Delete <code>setup.php</code> now</strong> (or blank <code>app.setup_token</code> in '
            . '<code>config.php</code>) — this page has nothing left to protect once your admin account exists, '
            . 'and it should not stay reachable.</p>', 200, ['href' => url('admin/login'), 'label' => 'Sign in']);
    }
}

$username = e((string) ($_POST['username'] ?? ''));
$errorHtml = $error !== '' ? '<p class="notice notice--warn">' . e($error) . '</p>' : '';

setup_page('Create the admin account', $errorHtml
    . '<p class="admin-auth__sub">Schema applied (' . (int) $applied . ' statements checked). Create your admin login to finish.</p>'
    . '<form method="post" action="' . e(url('setup.php') . $tokenQuery) . '">'
    . $tokenField
    . '<div class="field"><label class="field__label" for="username">Username</label>'
    . '<input class="field__control" type="text" id="username" name="username" value="' . $username . '" autocomplete="username" autofocus required></div>'
    . '<div class="field"><label class="field__label" for="password">Password</label>'
    . '<input class="field__control" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required></div>'
    . '<div class="field"><label class="field__label" for="password_confirm">Confirm password</label>'
    . '<input class="field__control" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required></div>'
    . '<button class="form__submit" type="submit">Create admin account</button>'
    . '</form>');
