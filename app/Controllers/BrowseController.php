<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Article;
use App\Models\Newspaper;
use App\Support\Paginator;
use App\Support\Taxonomy;

final class BrowseController
{
    public function index(): void
    {
        $filters = [
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'province' => Taxonomy::provinceFromSlug((string) ($_GET['province'] ?? '')) ?? '',
            'type'     => Taxonomy::isType((string) ($_GET['type'] ?? '')) ? (string) $_GET['type'] : '',
        ];

        $query = http_build_query(array_filter([
            'q' => $filters['q'],
            'province' => $_GET['province'] ?? '',
            'type' => $filters['type'],
        ]));
        $baseUrl = url('newspapers') . ($query !== '' ? '?' . $query : '');

        [, $total] = Newspaper::paginate($filters, 0, 0);
        $paginator = Paginator::fromQuery($total, Newspaper::PER_PAGE, $baseUrl);
        [$rows] = Newspaper::paginate($filters, Newspaper::PER_PAGE, $paginator->offset);

        render('browse', [
            'newspapers' => $rows,
            'paginator'  => $paginator,
            'filters'    => $filters,
            'total'      => $total,
            'heading'    => 'South African newspapers',
            'layout_title' => 'All newspapers — ' . config('app.name'),
        ]);
    }

    public function province(array $params): void
    {
        $province = Taxonomy::provinceFromSlug((string) ($params['slug'] ?? ''));
        if ($province === null) {
            abort(404, 'Unknown province.');
        }

        $baseUrl = url('province/' . slugify($province));
        [, $total] = Newspaper::paginate(['province' => $province], 0, 0);
        $paginator = Paginator::fromQuery($total, Newspaper::PER_PAGE, $baseUrl);
        [$rows] = Newspaper::paginate(['province' => $province], Newspaper::PER_PAGE, $paginator->offset);

        render('browse', [
            'newspapers' => $rows,
            'paginator'  => $paginator,
            'filters'    => ['q' => '', 'province' => $province, 'type' => ''],
            'total'      => $total,
            'heading'    => 'Newspapers in ' . $province,
            'layout_title' => 'Newspapers in ' . $province . ' — ' . config('app.name'),
        ]);
    }

    public function section(array $params): void
    {
        $section = Taxonomy::sectionFromSlug((string) ($params['slug'] ?? ''));
        if ($section === null) {
            abort(404, 'Unknown section.');
        }

        $baseUrl = url('section/' . slugify($section));
        $total = Article::countInSection($section);
        $paginator = Paginator::fromQuery($total, 24, $baseUrl);
        $articles = Article::inSection($section, 24, $paginator->offset);

        render('section', [
            'section'   => $section,
            'articles'  => $articles,
            'paginator' => $paginator,
            'total'     => $total,
            'layout_title' => $section . ' — ' . config('app.name'),
            'layout_description' => 'The latest ' . strtolower($section) . ' stories from community and local newspapers across South Africa.',
        ]);
    }
}
