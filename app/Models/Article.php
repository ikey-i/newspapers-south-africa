<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Published stories. Read methods return only published articles that belong
 * to an active newspaper; the publisher dashboard uses ArticleAdmin.
 */
final class Article
{
    private const PUBLIC_JOIN =
        "FROM articles a
         JOIN newspapers n ON n.id = a.newspaper_id
         WHERE a.status = 'published' AND a.published_at <= NOW() AND n.status = 'active'";

    /** @return list<array<string,mixed>> */
    public static function latest(int $limit = 12): array
    {
        return Database::all(
            "SELECT a.*, n.name AS newspaper_name, n.slug AS newspaper_slug
             " . self::PUBLIC_JOIN . "
             ORDER BY a.published_at DESC
             LIMIT " . max(1, $limit)
        );
    }

    /** @return list<array<string,mixed>> */
    public static function forNewspaper(int $newspaperId, int $limit = 20, int $offset = 0): array
    {
        return Database::all(
            "SELECT a.*, n.name AS newspaper_name, n.slug AS newspaper_slug
             " . self::PUBLIC_JOIN . " AND a.newspaper_id = ?
             ORDER BY a.published_at DESC
             LIMIT " . max(1, $limit) . " OFFSET " . max(0, $offset),
            [$newspaperId]
        );
    }

    public static function countForNewspaper(int $newspaperId): int
    {
        return (int) (Database::first(
            "SELECT COUNT(*) AS c " . self::PUBLIC_JOIN . " AND a.newspaper_id = ?",
            [$newspaperId]
        )['c'] ?? 0);
    }

    /** @return array<string,mixed>|null */
    public static function findPublished(string $newspaperSlug, string $articleSlug): ?array
    {
        return Database::first(
            "SELECT a.*, n.name AS newspaper_name, n.slug AS newspaper_slug, n.logo_path AS newspaper_logo
             " . self::PUBLIC_JOIN . " AND n.slug = ? AND a.slug = ?",
            [$newspaperSlug, $articleSlug]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function inSection(string $section, int $limit = 24, int $offset = 0): array
    {
        return Database::all(
            "SELECT a.*, n.name AS newspaper_name, n.slug AS newspaper_slug
             " . self::PUBLIC_JOIN . " AND a.section = ?
             ORDER BY a.published_at DESC
             LIMIT " . max(1, $limit) . " OFFSET " . max(0, $offset),
            [$section]
        );
    }

    public static function countInSection(string $section): int
    {
        return (int) (Database::first(
            "SELECT COUNT(*) AS c " . self::PUBLIC_JOIN . " AND a.section = ?",
            [$section]
        )['c'] ?? 0);
    }

    /** @return list<array<string,mixed>> */
    public static function forEdition(int $editionId): array
    {
        return Database::all(
            "SELECT a.*, n.slug AS newspaper_slug
             " . self::PUBLIC_JOIN . " AND a.edition_id = ?
             ORDER BY a.published_at DESC",
            [$editionId]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function search(string $q, int $limit = 20): array
    {
        $like = '%' . trim($q) . '%';
        return Database::all(
            "SELECT a.*, n.name AS newspaper_name, n.slug AS newspaper_slug
             " . self::PUBLIC_JOIN . " AND (a.title LIKE ? OR a.standfirst LIKE ?)
             ORDER BY a.published_at DESC
             LIMIT " . max(1, $limit),
            [$like, $like]
        );
    }

    public static function recordView(int $articleId): void
    {
        try {
            Database::execute('UPDATE articles SET views = views + 1 WHERE id = ?', [$articleId]);
        } catch (\Throwable) {
            // A view counter is not worth failing a page render over.
        }
    }
}
