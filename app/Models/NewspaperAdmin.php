<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Admin-side access to newspapers: every row regardless of status, plus the
 * moderation actions (approve / reject / suspend / restore) and CRUD.
 */
final class NewspaperAdmin
{
    public const PER_PAGE = 30;

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM newspapers WHERE id = ?', [$id]);
    }

    public static function slugTaken(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM newspapers WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        return Database::first($sql, $params) !== null;
    }

    /**
     * @param array{q?:string,status?:string,province?:string} $filters
     * @return array{0: list<array<string,mixed>>, 1: int}
     */
    public static function paginate(array $filters, int $limit, int $offset): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(n.name LIKE ? OR n.city LIKE ? OR n.slug LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'active', 'rejected', 'suspended'], true)) {
            $where[] = 'n.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['province'])) {
            $where[] = 'n.province = ?';
            $params[] = $filters['province'];
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) (Database::first("SELECT COUNT(*) AS c FROM newspapers n WHERE {$whereSql}", $params)['c'] ?? 0);
        if ($limit <= 0) {
            return [[], $total];
        }

        $rows = Database::all(
            "SELECT n.*,
                    (SELECT COUNT(*) FROM publishers p WHERE p.newspaper_id = n.id) AS publisher_count,
                    (SELECT COUNT(*) FROM articles a WHERE a.newspaper_id = n.id AND a.status = 'published') AS article_count
             FROM newspapers n
             WHERE {$whereSql}
             ORDER BY (n.status = 'pending') DESC, n.created_at DESC
             LIMIT {$limit} OFFSET " . max(0, $offset),
            $params
        );

        return [$rows, $total];
    }

    /** @return array<string,int> */
    public static function statusCounts(): array
    {
        $out = ['pending' => 0, 'active' => 0, 'suspended' => 0, 'rejected' => 0];
        foreach (Database::all('SELECT status, COUNT(*) AS c FROM newspapers GROUP BY status') as $row) {
            $out[(string) $row['status']] = (int) $row['c'];
        }
        return $out;
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $cols = array_keys($data);
        $place = implode(', ', array_fill(0, count($cols), '?'));
        Database::execute(
            'INSERT INTO newspapers (' . implode(', ', $cols) . ') VALUES (' . $place . ')',
            array_values($data)
        );
        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        if ($data === []) {
            return;
        }
        $set = implode(', ', array_map(static fn ($c) => "{$c} = ?", array_keys($data)));
        Database::execute(
            "UPDATE newspapers SET {$set} WHERE id = ?",
            [...array_values($data), $id]
        );
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::execute(
            'UPDATE newspapers SET status = ?, reviewed_at = NOW() WHERE id = ?',
            [$status, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM newspapers WHERE id = ?', [$id]);
    }
}
