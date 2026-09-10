<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Publisher-side access to editions, scoped by newspaper_id.
 */
final class EditionAdmin
{
    public const PER_PAGE = 20;

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $newspaperId): ?array
    {
        return Database::first(
            'SELECT * FROM editions WHERE id = ? AND newspaper_id = ?',
            [$id, $newspaperId]
        );
    }

    /**
     * @return array{0: list<array<string,mixed>>, 1: int}
     */
    public static function paginate(int $newspaperId, int $limit, int $offset): array
    {
        $total = (int) (Database::first(
            'SELECT COUNT(*) AS c FROM editions WHERE newspaper_id = ?',
            [$newspaperId]
        )['c'] ?? 0);
        if ($limit <= 0) {
            return [[], $total];
        }

        $rows = Database::all(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM articles a WHERE a.edition_id = e.id) AS article_count
             FROM editions e
             WHERE e.newspaper_id = ?
             ORDER BY e.edition_date DESC, e.created_at DESC
             LIMIT {$limit} OFFSET " . max(0, $offset),
            [$newspaperId]
        );
        return [$rows, $total];
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $cols = array_keys($data);
        Database::execute(
            'INSERT INTO editions (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
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
            "UPDATE editions SET {$set} WHERE id = ? AND newspaper_id = ?",
            [...array_values($data), $id, $newspaperId]
        );
    }

    public static function delete(int $id, int $newspaperId): void
    {
        Database::execute('DELETE FROM editions WHERE id = ? AND newspaper_id = ?', [$id, $newspaperId]);
    }
}
