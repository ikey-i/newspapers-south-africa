<?php

declare(strict_types=1);

namespace App\Controllers\Publish;

use App\Database;
use App\Models\Publisher;
use App\Models\Setting;
use App\Support\Csrf;
use App\Support\FormGuard;
use App\Support\Mailer;
use App\Support\PublisherAuth;
use App\Support\RateLimiter;
use App\Support\Recaptcha;
use App\Support\Taxonomy;
use App\Support\Validator;

final class AuthController
{
    public function showRegister(): void
    {
        if (PublisherAuth::check()) {
            redirect(url('publish'));
        }
        $this->renderRegister([]);
    }

    public function register(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please try again.');
        }
        if (!Database::isReady()) {
            abort(503, 'Registration is not available yet.');
        }
        if (FormGuard::isSpam('publish_register', $_POST)) {
            redirect(url('publish/register/sent'));
        }
        if (RateLimiter::tooMany('signup_attempts', 5, 60)) {
            $this->renderRegister(['email' => 'Too many sign-ups from this connection. Please try again later.']);
            return;
        }
        if (!Recaptcha::verify($_POST['g-recaptcha-response'] ?? null)) {
            $this->renderRegister(['captcha' => 'Please complete the CAPTCHA to continue.']);
            return;
        }

        $v = new Validator($_POST);
        $v->label('Newspaper name')->required('paper_name')->max('paper_name', 160);
        $v->label('Type')->required('type')->in('type', array_keys(Taxonomy::TYPES));
        $v->label('Province')->optional('province')->in('province', Taxonomy::PROVINCES);
        $v->label('Town / city')->optional('city')->max('city', 120);
        $v->label('Website')->optional('website')->url('website')->max('website', 255);
        $v->label('Your name')->required('name')->max('name', 120);
        $v->label('Email')->required('email')->email('email')->max('email', 160);
        $v->label('Password')->required('password')->min('password', 10)->max('password', 200);

        $in = $v->validated();

        if (($in['password'] ?? '') !== (string) ($_POST['password_confirm'] ?? '')) {
            $v->addError('password_confirm', 'The passwords do not match.');
        }
        if (!$v->fails() && Publisher::emailTaken((string) $in['email'])) {
            $v->addError('email', 'An account with that email already exists. Try signing in.');
        }

        if ($v->fails()) {
            $this->renderRegister($v->errors());
            return;
        }

        Database::execute(
            'INSERT INTO signup_attempts (ip_hash, email) VALUES (?, ?)',
            [client_ip_hash(), mb_substr((string) $in['email'], 0, 160)]
        );

        [, , $token] = Publisher::register(
            [
                'name'     => (string) $in['paper_name'],
                'type'     => (string) $in['type'],
                'province' => $in['province'] ?: null,
                'city'     => $in['city'] ?: null,
                'website'  => $in['website'] ?: null,
            ],
            [
                'name'     => (string) $in['name'],
                'email'    => (string) $in['email'],
                'password' => (string) $in['password'],
            ]
        );

        $this->sendVerifyEmail((string) $in['email'], (string) $in['name'], $token);

        redirect(url('publish/register/sent'));
    }

    public function registerSent(): void
    {
        render('publish/notice', [
            'heading' => 'Check your email',
            'body'    => 'We have sent a verification link to your email address. Click it to confirm your account. '
                       . 'Once verified, our team reviews your newspaper — usually within a working day — and then you can sign in and publish.',
            'link'    => ['href' => url('publish/login'), 'label' => 'Go to sign in'],
            'layout_title' => 'Check your email — ' . config('app.name'),
        ]);
    }

    public function verify(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $publisher = Publisher::findByVerifyToken($token);

        if ($publisher === null) {
            render('publish/notice', [
                'heading' => 'That link is not valid',
                'body'    => 'The verification link has expired or was already used. Sign in to request a new one.',
                'link'    => ['href' => url('publish/login'), 'label' => 'Go to sign in'],
                'layout_title' => 'Verification — ' . config('app.name'),
            ]);
            return;
        }

        Publisher::markVerified((int) $publisher['id']);
        PublisherAuth::login((int) $publisher['id']);
        $this->notifyAdminOfPendingNewspaper($publisher);

        flash('publish_success', 'Your email is verified. Thanks!');
        redirect(url('publish'));
    }

    public function resendVerify(): void
    {
        $user = PublisherAuth::requireLogin();
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please try again.');
        }
        if (!empty($user['email_verified_at'])) {
            redirect(url('publish'));
        }
        $token = Publisher::refreshVerifyToken((int) $user['id']);
        $this->sendVerifyEmail((string) $user['email'], (string) $user['name'], $token);
        flash('publish_success', 'We have sent another verification email.');
        redirect(url('publish'));
    }

    public function showLogin(): void
    {
        if (PublisherAuth::check()) {
            redirect(url('publish'));
        }
        $this->renderLogin();
    }

    public function login(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please try again.');
        }
        if (!Database::isReady()) {
            abort(503, 'Sign in is not available yet.');
        }
        if (PublisherAuth::isLockedOut()) {
            $this->renderLogin('Too many failed attempts. Please wait a few minutes and try again.');
            return;
        }

        PublisherAuth::purgeOldAttempts();

        if (!PublisherAuth::attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            $this->renderLogin('That email and password did not match.');
            return;
        }

        redirect(PublisherAuth::intended());
    }

    public function logout(): void
    {
        if (Csrf::check($_POST['_token'] ?? null)) {
            PublisherAuth::logout();
        }
        redirect(url('publish/login'));
    }

    // -----------------------------------------------------------------------

    /**
     * Let the site admin know a newsroom is ready for review, so approvals
     * don't rely on someone remembering to check the dashboard. No-op if no
     * admin notification address is configured, or the newspaper was already
     * handled (e.g. approved before the owner got around to verifying).
     *
     * @param array<string,mixed> $publisher
     */
    private function notifyAdminOfPendingNewspaper(array $publisher): void
    {
        if (($publisher['newspaper_status'] ?? null) !== 'pending') {
            return;
        }
        $to = Setting::get('admin_notify_email');
        if ($to === '') {
            return;
        }

        $site = config('app.name');
        $reviewLink = base_url('admin/newspapers?status=pending');
        $body = "A newsroom has verified their email and is ready for review:\n\n"
              . "  Newspaper: {$publisher['newspaper_name']}\n"
              . "  Contact:   {$publisher['name']} <{$publisher['email']}>\n\n"
              . "Review it here:\n{$reviewLink}\n\n"
              . "— {$site}";

        Mailer::send($to, "New newspaper awaiting approval: {$publisher['newspaper_name']}", $body);
    }

    private function sendVerifyEmail(string $email, string $name, string $token): void
    {
        $link = base_url('publish/verify?token=' . $token);
        $site = config('app.name');
        $body = "Hi {$name},\n\n"
              . "Thanks for registering your newspaper with {$site}.\n\n"
              . "Confirm your email address by opening this link:\n{$link}\n\n"
              . "If you didn't request this, you can ignore this message.\n\n"
              . "— {$site}";
        Mailer::send($email, "Confirm your {$site} account", $body);
    }

    /** @param array<string,string> $errors */
    private function renderRegister(array $errors): void
    {
        http_response_code($errors === [] ? 200 : 422);
        $GLOBALS['old'] = $_POST;
        render('publish/register', [
            'errors'     => $errors,
            'csrfField'  => Csrf::field(),
            'guardField' => FormGuard::fields('publish_register'),
            'types'      => Taxonomy::TYPES,
            'provinces'  => Taxonomy::PROVINCES,
            'captchaWidget' => Recaptcha::widget(),
            'layout_title' => 'Register your newspaper — ' . config('app.name'),
        ]);
    }

    private function renderLogin(string $error = ''): void
    {
        http_response_code($error === '' ? 200 : 422);
        render('publish/login', [
            'error'     => $error,
            'email'     => (string) ($_POST['email'] ?? ''),
            'csrfField' => Csrf::field(),
            'layout_title' => 'Publisher sign in — ' . config('app.name'),
        ]);
    }
}
