<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Models\Newspaper;
use App\Models\Article;

final class HomeController
{
    public function index(): void
    {
        if (CONFIG_IS_SAMPLE || !Database::isReady()) {
            http_response_code(CONFIG_IS_SAMPLE ? 503 : 200);
            render('setup', ['layout_title' => 'Set up ' . config('app.name')]);
            return;
        }

        $featured = Newspaper::featured(8);
        $latest   = Article::latest(12);
        $provinces = Newspaper::countsByProvince();

        render('home', [
            'featured'  => $featured,
            'latest'    => $latest,
            'provinces' => $provinces,
            'layout_description' => 'Read community and local newspapers from every province of South Africa — stories, full-edition PDFs and back issues in one place.',
        ]);
    }

    public function health(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $ready = !CONFIG_IS_SAMPLE && Database::isReady();
        http_response_code($ready ? 200 : 503);
        echo $ready ? "ok\n" : "not ready\n";
    }
}
