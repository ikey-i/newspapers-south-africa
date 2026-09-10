<?php

declare(strict_types=1);

namespace App\Controllers\Publish;

use App\Database;
use App\Support\Csrf;
use App\Support\PublisherAuth;

final class DashboardController
{
    public function index(): void
    {
        $user = PublisherAuth::requireLogin();

        $verified = !empty($user['email_verified_at']);
        $approved = $user['newspaper_status'] === 'active';

        $counts = ['articles' => 0, 'published' => 0, 'editions' => 0];
        if ($verified && $approved) {
            $counts['articles'] = (int) (Database::first(
                'SELECT COUNT(*) AS c FROM articles WHERE newspaper_id = ?', [(int) $user['newspaper_id']]
            )['c'] ?? 0);
            $counts['published'] = (int) (Database::first(
                "SELECT COUNT(*) AS c FROM articles WHERE newspaper_id = ? AND status = 'published'", [(int) $user['newspaper_id']]
            )['c'] ?? 0);
            $counts['editions'] = (int) (Database::first(
                'SELECT COUNT(*) AS c FROM editions WHERE newspaper_id = ?', [(int) $user['newspaper_id']]
            )['c'] ?? 0);
        }

        publish_view('publish/dashboard', [
            'heading'  => 'Dashboard',
            'user'     => $user,
            'verified' => $verified,
            'approved' => $approved,
            'status'   => (string) $user['newspaper_status'],
            'counts'   => $counts,
            'csrfField' => Csrf::field(),
        ]);
    }
}
