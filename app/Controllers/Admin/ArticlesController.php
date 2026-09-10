<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Paginator;
use App\Support\Upload;

/**
 * Site-wide article moderation. The publisher owns their content; the admin can
 * unpublish or remove anything.
 */
final class ArticlesController
{
    public function index(): void
    {
        Auth::requireLogin();

        $q       = trim((string) ($_GET['q'] ?? ''));
        $status  = (string) ($_GET['status'] ?? '');
        $paperId = (int) ($_GET['newspaper'] ?? 0);

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(a.title LIKE ? OR a.standfirst LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like);
        }
        if (in_array($status, ['draft', 'published'], true)) {
            $where[] = 'a.status = ?';
            $params[] = $status;
        }
        if ($paperId > 0) {
            $where[] = 'a.newspaper_id = ?';
            $params[] = $paperId;
        }
        $whereSql = implode(' AND ', $where);

        $qs = http_build_query(array_filter(['q' => $q, 'status' => $status, 'newspaper' => $paperId ?: null]));
        $baseUrl = url('admin/articles') . ($qs !== '' ? '?' . $qs : '');

        $total = (int) (Database::first("SELECT COUNT(*) AS c FROM articles a WHERE {$whereSql}", $params)['c'] ?? 0);
        $paginator = Paginator::fromQuery($total, 30, $baseUrl);

        $rows = Database::all(
            "SELECT a.*, n.name AS newspaper_name, n.slug AS newspaper_slug
             FROM articles a JOIN newspapers n ON n.id = a.newspaper_id
             WHERE {$whereSql}
             ORDER BY COALESCE(a.published_at, a.updated_at) DESC
             LIMIT 30 OFFSET " . $paginator->offset,
            $params
        );

        admin_view('admin/articles/index', [
            'heading'    => 'Articles',
            'articles'   => $rows,
            'paginator'  => $paginator,
            'total'      => $total,
            'filters'    => ['q' => $q, 'status' => $status, 'newspaper' => $paperId],
            'newspapers' => Database::all('SELECT id, name FROM newspapers ORDER BY name'),
            'csrfField'  => Csrf::field(),
        ]);
    }

    public function unpublish(array $params): void
    {
        $article = $this->guard($params);
        Database::execute("UPDATE articles SET status = 'draft' WHERE id = ?", [(int) $article['id']]);
        flash('admin_success', "“{$article['title']}” was unpublished.");
        redirect(url('admin/articles'));
    }

    public function destroy(array $params): void
    {
        $article = $this->guard($params);
        Upload::delete($article['hero_image_path'] ?? null);
        Database::execute('DELETE FROM articles WHERE id = ?', [(int) $article['id']]);
        flash('admin_success', "“{$article['title']}” was deleted.");
        redirect(url('admin/articles'));
    }

    /**
     * @param array{id?:string} $params
     * @return array<string,mixed>
     */
    private function guard(array $params): array
    {
        Auth::requireLogin();
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please go back and try again.');
        }
        $article = Database::first('SELECT * FROM articles WHERE id = ?', [(int) ($params['id'] ?? 0)]);
        if ($article === null) {
            abort(404, 'That story could not be found.');
        }
        return $article;
    }
}
