<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Article;
use App\Models\Edition;
use App\Models\Newspaper;

final class EditionController
{
    public function latest(): void
    {
        render('editions', [
            'editions'     => Edition::latest(30),
            'layout_title' => 'PDF editions — ' . config('app.name'),
            'layout_description' => 'Download full-edition PDFs of community and local newspapers from across South Africa.',
        ]);
    }

    public function archive(array $params): void
    {
        $paper = Newspaper::findActiveBySlug((string) ($params['slug'] ?? ''));
        if ($paper === null) {
            abort(404, 'That newspaper could not be found.');
        }

        render('edition-archive', [
            'paper'        => $paper,
            'editions'     => Edition::forNewspaper((int) $paper['id']),
            'layout_title' => $paper['name'] . ' — back issues — ' . config('app.name'),
        ]);
    }

    public function show(array $params): void
    {
        $edition = Edition::findPublished(
            (string) ($params['slug'] ?? ''),
            (int) ($params['id'] ?? 0)
        );
        if ($edition === null) {
            abort(404, 'That edition could not be found.');
        }

        render('edition', [
            'edition'      => $edition,
            'articles'     => Article::forEdition((int) $edition['id']),
            'layout_title' => $edition['title'] . ' — ' . $edition['newspaper_name'],
        ]);
    }

    public function pdf(array $params): void
    {
        $edition = Edition::findPublished(
            (string) ($params['slug'] ?? ''),
            (int) ($params['id'] ?? 0)
        );
        if ($edition === null || empty($edition['pdf_path'])) {
            abort(404, 'That PDF could not be found.');
        }

        $full = BASE_PATH . '/' . ltrim((string) $edition['pdf_path'], '/');
        if (!is_file($full) || !str_starts_with(realpath($full) ?: '', realpath(BASE_PATH . '/uploads/pdfs') ?: "\0")) {
            abort(404, 'That PDF could not be found.');
        }

        $filename = slugify((string) $edition['newspaper_slug'] . '-' . $edition['title']) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($full));
        header('X-Content-Type-Options: nosniff');
        readfile($full);
    }
}
