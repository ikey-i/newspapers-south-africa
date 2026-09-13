<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database;
use App\Support\Auth;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        $byStatus = [];
        foreach (Database::all("SELECT status, COUNT(*) AS c FROM newspapers GROUP BY status") as $row) {
            $byStatus[(string) $row['status']] = (int) $row['c'];
        }

        $stats = [
            'papers_total'   => array_sum($byStatus),
            'papers_pending' => $byStatus['pending'] ?? 0,
            'papers_active'  => $byStatus['active'] ?? 0,
            'papers_suspended' => $byStatus['suspended'] ?? 0,
            'publishers'     => (int) (Database::first('SELECT COUNT(*) AS c FROM publishers')['c'] ?? 0),
            'articles'       => (int) (Database::first("SELECT COUNT(*) AS c FROM articles WHERE status = 'published'")['c'] ?? 0),
            'editions'       => (int) (Database::first("SELECT COUNT(*) AS c FROM editions WHERE is_published = 1")['c'] ?? 0),
        ];

        admin_view('admin/dashboard', [
            'heading'          => 'Dashboard',
            'stats'            => $stats,
            'mailNotSending'   => (string) config('app.env', 'local') === 'production'
                && (string) config('mail.method', 'log') === 'log',
        ]);
    }
}
