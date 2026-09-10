<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Support\Paginator;

/**
 * The public directory of newspapers. Read methods here return only
 * publicly-visible ("active") papers; the admin area uses NewspaperAdmin.
 */
final class Newspaper
{
    public const PER_PAGE = 24;

    /** @return array<string,mixed>|null */
    public static function findActiveBySlug(string $slug): ?array
    {
        return Database::first(
            "SELECT * FROM newspapers WHERE slug = ? AND status = 'active'",
            [$slug]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function featured(int $limit = 8): array
    {
        return Database::all(
            "SELECT * FROM newspapers
             WHERE status = 'active'
             ORDER BY is_featured DESC, name ASC
             LIMIT " . max(1, $limit)
        );
    }

    /**
     * Paginated public listing with optional province / type filters and a
     * free-text query.
     *
     * @param array{q?:string,province?:string,type?:string} $filters
     * @return array{0: list<array<string,mixed>>, 1: int}
     */
    public static function paginate(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::filterClause($filters);

        $total = (int) (Database::first(
            "SELECT COUNT(*) AS c FROM newspapers WHERE {$where}",
            $params
        )['c'] ?? 0);

        if ($limit <= 0) {
            return [[], $total];
        }

        $rows = Database::all(
            "SELECT * FROM newspapers WHERE {$where}
             ORDER BY is_featured DESC, name ASC
             LIMIT {$limit} OFFSET " . max(0, $offset),
            $params
        );

        return [$rows, $total];
    }

    /**
     * @param array{q?:string,province?:string,type?:string} $filters
     * @return array{0:string, 1:array<int,string>}
     */
    private static function filterClause(array $filters): array
    {
        $where = ["status = 'active'"];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(name LIKE ? OR city LIKE ? OR about LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['province'])) {
            $where[] = 'province = ?';
            $params[] = (string) $filters['province'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'type = ?';
            $params[] = (string) $filters['type'];
        }

        return [implode(' AND ', $where), $params];
    }

    /** @return list<array{province:string,c:int}> */
    public static function countsByProvince(): array
    {
        $rows = Database::all(
            "SELECT province, COUNT(*) AS c FROM newspapers
             WHERE status = 'active' AND province IS NOT NULL AND province <> ''
             GROUP BY province ORDER BY province ASC"
        );
        return array_map(
            static fn (array $r): array => ['province' => (string) $r['province'], 'c' => (int) $r['c']],
            $rows
        );
    }

    /** Full-text-ish search for the public search page. @return list<array<string,mixed>> */
    public static function search(string $q, int $limit = 12): array
    {
        $like = '%' . trim($q) . '%';
        return Database::all(
            "SELECT * FROM newspapers
             WHERE status = 'active' AND (name LIKE ? OR city LIKE ? OR about LIKE ?)
             ORDER BY is_featured DESC, name ASC
             LIMIT " . max(1, $limit),
            [$like, $like, $like]
        );
    }
}
