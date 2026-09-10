<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Support\Ads;

final class SitemapController
{
    public function xml(): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $urls = [
            base_url(),
            base_url('newspapers'),
            base_url('list-your-newspaper'),
            base_url('privacy'),
            base_url('terms'),
        ];

        if (!CONFIG_IS_SAMPLE && Database::isReady()) {
            foreach (Database::all("SELECT slug, updated_at FROM newspapers WHERE status = 'active' ORDER BY name") as $row) {
                $urls[] = [base_url('paper/' . $row['slug']), (string) $row['updated_at']];
            }
            foreach (Database::all(
                "SELECT a.slug AS aslug, n.slug AS nslug, a.updated_at
                 FROM articles a JOIN newspapers n ON n.id = a.newspaper_id
                 WHERE a.status = 'published' AND a.published_at <= NOW() AND n.status = 'active'
                 ORDER BY a.published_at DESC LIMIT 5000"
            ) as $row) {
                $urls[] = [base_url('paper/' . $row['nslug'] . '/article/' . $row['aslug']), (string) $row['updated_at']];
            }
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            [$loc, $lastmod] = is_array($url) ? $url : [$url, null];
            echo '  <url><loc>' . e($loc) . '</loc>';
            if ($lastmod) {
                echo '<lastmod>' . e(substr($lastmod, 0, 10)) . '</lastmod>';
            }
            echo "</url>\n";
        }
        echo '</urlset>' . "\n";
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /publish\n";
        echo 'Sitemap: ' . base_url('sitemap.xml') . "\n";
    }

    public function adsTxt(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $line = Ads::adsTxtLine();
        echo $line === '' ? "# No AdSense publisher ID configured yet.\n" : $line . "\n";
    }
}
