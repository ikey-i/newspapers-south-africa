<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Article;
use App\Models\Edition;
use App\Models\Newspaper;
use App\Support\Paginator;

final class NewspaperController
{
    public function show(array $params): void
    {
        $paper = Newspaper::findActiveBySlug((string) ($params['slug'] ?? ''));
        if ($paper === null) {
            abort(404, 'That newspaper could not be found.');
        }

        $id = (int) $paper['id'];
        $total = Article::countForNewspaper($id);
        $paginator = Paginator::fromQuery($total, 12, url('paper/' . $paper['slug']));
        $articles = Article::forNewspaper($id, 12, $paginator->offset);

        render('newspaper', [
            'paper'         => $paper,
            'articles'      => $articles,
            'paginator'     => $paginator,
            'editions'      => Edition::forNewspaper($id),
            'latestPdf'     => Edition::latestWithPdf($id),
            'layout_title'  => $paper['name'] . ' — ' . config('app.name'),
            'layout_description' => $paper['tagline'] ?: ('Stories, PDF editions and back issues from ' . $paper['name'] . '.'),
        ]);
    }

    public function article(array $params): void
    {
        $article = Article::findPublished(
            (string) ($params['slug'] ?? ''),
            (string) ($params['article'] ?? '')
        );
        if ($article === null) {
            abort(404, 'That story could not be found.');
        }

        Article::recordView((int) $article['id']);

        $more = Article::forNewspaper((int) $article['newspaper_id'], 6);
        $more = array_values(array_filter($more, static fn ($a): bool => (int) $a['id'] !== (int) $article['id']));

        render('article', [
            'article'      => $article,
            'more'         => array_slice($more, 0, 4),
            'layout_title' => $article['title'] . ' — ' . $article['newspaper_name'],
            'layout_description' => $article['standfirst'] ?: str_excerpt((string) $article['body_html'], 160),
        ]);
    }
}
