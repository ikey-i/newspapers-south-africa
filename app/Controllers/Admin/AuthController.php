<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database;
use App\Support\Auth;
use App\Support\Csrf;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(url('admin'));
        }
        $this->render();
    }

    public function login(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please try again.');
        }

        if (!Database::isReady()) {
            abort(503, 'The admin area is not available yet.');
        }

        if (Auth::isLockedOut()) {
            $this->render('Too many failed attempts. Please wait a few minutes and try again.');
            return;
        }

        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        Auth::purgeOldAttempts();

        if (!Auth::attempt($username, $password)) {
            $this->render('That username and password did not match.');
            return;
        }

        redirect(Auth::intended());
    }

    public function logout(): void
    {
        if (Csrf::check($_POST['_token'] ?? null)) {
            Auth::logout();
        }
        redirect(url('admin/login'));
    }

    private function render(string $error = ''): void
    {
        http_response_code($error === '' ? 200 : 422);
        echo view('admin/login', [
            'title'      => 'Sign in — ' . config('app.name') . ' admin',
            'error'      => $error,
            'csrfField'  => Csrf::field(),
            'username'   => (string) ($_POST['username'] ?? ''),
        ]);
    }
}
