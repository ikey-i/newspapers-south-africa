<?php

declare(strict_types=1);

namespace App\Controllers\Publish;

use App\Database;
use App\Models\PasswordReset;
use App\Models\Publisher;
use App\Support\Csrf;
use App\Support\FormGuard;
use App\Support\Mailer;
use App\Support\PublisherAuth;
use App\Support\RateLimiter;
use App\Support\Validator;

final class PasswordController
{
    public function showForgot(): void
    {
        render('publish/forgot', [
            'csrfField'  => Csrf::field(),
            'guardField' => FormGuard::fields('publish_forgot'),
            'error'      => '',
            'layout_title' => 'Reset your password — ' . config('app.name'),
        ]);
    }

    public function forgot(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please try again.');
        }

        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));

        // Always show the same confirmation — no account enumeration.
        $genericDone = function (): never {
            render('publish/notice', [
                'heading' => 'Check your email',
                'body'    => 'If an account exists for that address, we have sent a link to reset the password. '
                           . 'The link is valid for one hour.',
                'link'    => ['href' => url('publish/login'), 'label' => 'Back to sign in'],
                'layout_title' => 'Reset your password — ' . config('app.name'),
            ]);
            exit;
        };

        if (FormGuard::isSpam('publish_forgot', $_POST)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || !Database::isReady()
            || RateLimiter::tooMany('password_resets', 5, 60)) {
            $genericDone();
        }

        $publisher = Publisher::findByEmail($email);
        if ($publisher !== null && (int) $publisher['is_active'] === 1) {
            $token = PasswordReset::issue($email);
            $link = base_url('publish/reset?token=' . $token);
            $site = config('app.name');
            Mailer::send($email, "Reset your {$site} password",
                "Someone asked to reset the password for your {$site} account.\n\n"
                . "Open this link to choose a new password (valid for one hour):\n{$link}\n\n"
                . "If this wasn't you, you can ignore this message.\n\n— {$site}");
        }

        $genericDone();
    }

    public function showReset(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        if (PasswordReset::emailForToken($token) === null) {
            render('publish/notice', [
                'heading' => 'That link is not valid',
                'body'    => 'The reset link has expired or was already used. Request a new one.',
                'link'    => ['href' => url('publish/forgot'), 'label' => 'Request a new link'],
                'layout_title' => 'Reset your password — ' . config('app.name'),
            ]);
            return;
        }

        render('publish/reset', [
            'token'     => $token,
            'errors'    => [],
            'csrfField' => Csrf::field(),
            'layout_title' => 'Choose a new password — ' . config('app.name'),
        ]);
    }

    public function reset(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please try again.');
        }

        $token = (string) ($_POST['token'] ?? '');
        $email = PasswordReset::emailForToken($token);
        if ($email === null) {
            abort(400, 'That reset link is no longer valid.');
        }

        $v = new Validator($_POST);
        $v->label('Password')->required('password')->min('password', 10)->max('password', 200);
        $in = $v->validated();
        if (($in['password'] ?? '') !== (string) ($_POST['password_confirm'] ?? '')) {
            $v->addError('password_confirm', 'The passwords do not match.');
        }

        if ($v->fails()) {
            render('publish/reset', [
                'token'     => $token,
                'errors'    => $v->errors(),
                'csrfField' => Csrf::field(),
                'layout_title' => 'Choose a new password — ' . config('app.name'),
            ]);
            return;
        }

        $publisher = Publisher::findByEmail($email);
        if ($publisher === null) {
            abort(400, 'That account no longer exists.');
        }

        Publisher::setPassword((int) $publisher['id'], (string) $in['password']);
        PasswordReset::consume($token);
        PublisherAuth::logout();

        flash('publish_success', 'Your password has been changed. Please sign in.');
        redirect(url('publish/login'));
    }
}
