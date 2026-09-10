<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Print / PDF editions. Read methods return only published editions of active
 * newspapers; the publisher dashboard uses EditionAdmin.
 */
final class Edition
{
    private const PUBLIC_JOIN =
        "FROM editions e
         JOIN newspapers n ON n.id = e.newspaper_id
         WHERE e.is_published = 1 AND n.status = 'active'";

    /** @return list<array<string,mixed>> */
    public static function forNewspaper(int $newspaperId): array
    {
        return Database::all(
            "SELECT e.* " . self::PUBLIC_JOIN . " AND e.newspaper_id = ?
             ORDER BY e.edition_date DESC, e.created_at DESC",
            [$newspaperId]
        );
    }

    /** @return array<string,mixed>|null */
    public static function findPublished(string $newspaperSlug, int $editionId): ?array
    {
        return Database::first(
            "SELECT e.*, n.name AS newspaper_name, n.slug AS newspaper_slug
             " . self::PUBLIC_JOIN . " AND n.slug = ? AND e.id = ?",
            [$newspaperSlug, $editionId]
        );
    }

    /** @return array<string,mixed>|null  The most recent published edition with a PDF. */
    public static function latestWithPdf(int $newspaperId): ?array
    {
        return Database::first(
            "SELECT e.* " . self::PUBLIC_JOIN . "
             AND e.newspaper_id = ? AND e.pdf_path IS NOT NULL AND e.pdf_path <> ''
             ORDER BY e.edition_date DESC, e.created_at DESC
             LIMIT 1",
            [$newspaperId]
        );
    }

    /** @return list<array<string,mixed>> Recent editions across the whole site. */
    public static function latest(int $limit = 8): array
    {
        return Database::all(
            "SELECT e.*, n.name AS newspaper_name, n.slug AS newspaper_slug
             " . self::PUBLIC_JOIN . " AND e.pdf_path IS NOT NULL AND e.pdf_path <> ''
             ORDER BY e.edition_date DESC, e.created_at DESC
             LIMIT " . max(1, $limit)
        );
    }
}
