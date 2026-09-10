<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database;
use App\Models\PasswordReset;
use App\Models\Publisher;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Mailer;
use App\Support\Paginator;

final class PublishersController
{
    public function index(): void
    {
        Auth::requireLogin();

        $q = trim((string) ($_GET['q'] ?? ''));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where = '(p.name LIKE ? OR p.email LIKE ? OR n.name LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
        }

        $total = (int) (Database::first(
            "SELECT COUNT(*) AS c FROM publishers p JOIN newspapers n ON n.id = p.newspaper_id WHERE {$where}",
            $params
        )['c'] ?? 0);

        $paginator = Paginator::fromQuery($total, 30, url('admin/publishers') . ($q !== '' ? '?q=' . urlencode($q) : ''));

        $rows = Database::all(
            "SELECT p.*, n.name AS newspaper_name, n.slug AS newspaper_slug, n.status AS newspaper_status
             FROM publishers p JOIN newspapers n ON n.id = p.newspaper_id
             WHERE {$where}
             ORDER BY p.created_at DESC
             LIMIT 30 OFFSET " . $paginator->offset,
            $params
        );

        admin_view('admin/publishers/index', [
            'heading'    => 'Publishers',
            'publishers' => $rows,
            'paginator'  => $paginator,
            'q'          => $q,
            'total'      => $total,
            'csrfField'  => Csrf::field(),
        ]);
    }

    public function toggleActive(array $params): void
    {
        $this->action($params, function (array $pub): string {
            $new = (int) $pub['is_active'] === 1 ? 0 : 1;
            Database::execute('UPDATE publishers SET is_active = ? WHERE id = ?', [$new, (int) $pub['id']]);
            return $new === 1 ? "{$pub['email']} was reactivated." : "{$pub['email']} was deactivated.";
        });
    }

    public function resendVerify(array $params): void
    {
        $this->action($params, function (array $pub): string {
            if (!empty($pub['email_verified_at'])) {
                return "{$pub['email']} is already verified.";
            }
            $token = Publisher::refreshVerifyToken((int) $pub['id']);
            $link = base_url('publish/verify?token=' . $token);
            Mailer::send((string) $pub['email'], 'Confirm your ' . config('app.name') . ' account',
                "Open this link to confirm your account:\n{$link}\n");
            return "Verification email re-sent to {$pub['email']}.";
        });
    }

    public function sendReset(array $params): void
    {
        $this->action($params, function (array $pub): string {
            $token = PasswordReset::issue((string) $pub['email']);
            $link = base_url('publish/reset?token=' . $token);
            Mailer::send((string) $pub['email'], 'Reset your ' . config('app.name') . ' password',
                "Open this link to choose a new password (valid one hour):\n{$link}\n");
            return "Password-reset email sent to {$pub['email']}.";
        });
    }

    /**
     * @param array{id?:string} $params
     * @param callable(array<string,mixed>):string $fn
     */
    private function action(array $params, callable $fn): void
    {
        Auth::requireLogin();
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please go back and try again.');
        }
        $pub = Database::first('SELECT * FROM publishers WHERE id = ?', [(int) ($params['id'] ?? 0)]);
        if ($pub === null) {
            abort(404, 'That publisher could not be found.');
        }
        flash('admin_success', $fn($pub));
        redirect(url('admin/publishers'));
    }
}
