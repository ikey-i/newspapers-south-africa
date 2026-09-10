<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Paginator;
use App\Support\Upload;

final class EditionsController
{
    public function index(): void
    {
        Auth::requireLogin();

        $q       = trim((string) ($_GET['q'] ?? ''));
        $paperId = (int) ($_GET['newspaper'] ?? 0);

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = 'e.title LIKE ?';
            $params[] = '%' . $q . '%';
        }
        if ($paperId > 0) {
            $where[] = 'e.newspaper_id = ?';
            $params[] = $paperId;
        }
        $whereSql = implode(' AND ', $where);

        $qs = http_build_query(array_filter(['q' => $q, 'newspaper' => $paperId ?: null]));
        $baseUrl = url('admin/editions') . ($qs !== '' ? '?' . $qs : '');

        $total = (int) (Database::first("SELECT COUNT(*) AS c FROM editions e WHERE {$whereSql}", $params)['c'] ?? 0);
        $paginator = Paginator::fromQuery($total, 30, $baseUrl);

        $rows = Database::all(
            "SELECT e.*, n.name AS newspaper_name, n.slug AS newspaper_slug
             FROM editions e JOIN newspapers n ON n.id = e.newspaper_id
             WHERE {$whereSql}
             ORDER BY e.edition_date DESC, e.created_at DESC
             LIMIT 30 OFFSET " . $paginator->offset,
            $params
        );

        admin_view('admin/editions/index', [
            'heading'    => 'Editions',
            'editions'   => $rows,
            'paginator'  => $paginator,
            'total'      => $total,
            'filters'    => ['q' => $q, 'newspaper' => $paperId],
            'newspapers' => Database::all('SELECT id, name FROM newspapers ORDER BY name'),
            'csrfField'  => Csrf::field(),
        ]);
    }

    public function unpublish(array $params): void
    {
        $edition = $this->guard($params);
        Database::execute('UPDATE editions SET is_published = 0 WHERE id = ?', [(int) $edition['id']]);
        flash('admin_success', "“{$edition['title']}” was hidden.");
        redirect(url('admin/editions'));
    }

    public function destroy(array $params): void
    {
        $edition = $this->guard($params);
        Upload::delete($edition['cover_path'] ?? null);
        Upload::delete($edition['pdf_path'] ?? null);
        Database::execute('DELETE FROM editions WHERE id = ?', [(int) $edition['id']]);
        flash('admin_success', "“{$edition['title']}” was deleted.");
        redirect(url('admin/editions'));
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
        $edition = Database::first('SELECT * FROM editions WHERE id = ?', [(int) ($params['id'] ?? 0)]);
        if ($edition === null) {
            abort(404, 'That edition could not be found.');
        }
        return $edition;
    }
}
