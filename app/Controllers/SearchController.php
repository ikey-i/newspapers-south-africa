<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Models\Article;
use App\Models\Newspaper;

final class SearchController
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $q = mb_substr($q, 0, 100);

        $newspapers = [];
        $articles = [];

        if ($q !== '' && !CONFIG_IS_SAMPLE && Database::isReady()) {
            $newspapers = Newspaper::search($q, 12);
            $articles = Article::search($q, 20);
        }

        render('search', [
            'q'           => $q,
            'newspapers'  => $newspapers,
            'articles'    => $articles,
            'layout_title' => ($q !== '' ? "Search: {$q}" : 'Search') . ' — ' . config('app.name'),
        ]);
    }
}
