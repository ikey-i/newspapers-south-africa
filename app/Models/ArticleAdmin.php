<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Publisher-side access to articles: everything belonging to one newspaper,
 * drafts included. Every method is scoped by newspaper_id — callers pass the
 * signed-in publisher's newspaper.
 */
final class ArticleAdmin
{
    public const PER_PAGE = 20;

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $newspaperId): ?array
    {
        return Database::first(
            'SELECT * FROM articles WHERE id = ? AND newspaper_id = ?',
            [$id, $newspaperId]
        );
    }

    public static function slugTaken(string $slug, int $newspaperId, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM articles WHERE newspaper_id = ? AND slug = ?';
        $params = [$newspaperId, $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        return Database::first($sql, $params) !== null;
    }

    /**
     * @param array{status?:string,q?:string} $filters
     * @return array{0: list<array<string,mixed>>, 1: int}
     */
    public static function paginate(int $newspaperId, array $filters, int $limit, int $offset): array
    {
        $where = ['a.newspaper_id = ?'];
        $params = [$newspaperId];

        if (in_array($filters['status'] ?? '', ['draft', 'published'], true)) {
            $where[] = 'a.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(a.title LIKE ? OR a.standfirst LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like);
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) (Database::first("SELECT COUNT(*) AS c FROM articles a WHERE {$whereSql}", $params)['c'] ?? 0);
        if ($limit <= 0) {
            return [[], $total];
        }

        $rows = Database::all(
            "SELECT a.*, e.title AS edition_title
             FROM articles a
             LEFT JOIN editions e ON e.id = a.edition_id
             WHERE {$whereSql}
             ORDER BY COALESCE(a.published_at, a.updated_at) DESC
             LIMIT {$limit} OFFSET " . max(0, $offset),
            $params
        );
        return [$rows, $total];
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $cols = array_keys($data);
        Database::execute(
            'INSERT INTO articles (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
            array_values($data)
        );
        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, int $newspaperId, array $data): void
    {
        if ($data === []) {
            return;
        }
        $set = implode(', ', array_map(static fn ($c) => "{$c} = ?", array_keys($data)));
        Database::execute(
            "UPDATE articles SET {$set} WHERE id = ? AND newspaper_id = ?",
            [...array_values($data), $id, $newspaperId]
        );
    }

    public static function delete(int $id, int $newspaperId): void
    {
        Database::execute('DELETE FROM articles WHERE id = ? AND newspaper_id = ?', [$id, $newspaperId]);
    }

    /** @return list<array{id:int,title:string}> Published editions for the edition picker. */
    public static function editionOptions(int $newspaperId): array
    {
        $rows = Database::all(
            'SELECT id, title FROM editions WHERE newspaper_id = ? ORDER BY edition_date DESC, created_at DESC',
            [$newspaperId]
        );
        return array_map(static fn ($r) => ['id' => (int) $r['id'], 'title' => (string) $r['title']], $rows);
    }
}
