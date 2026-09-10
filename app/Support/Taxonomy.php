<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Fixed vocabularies for browsing: newspaper types, South African provinces
 * (plus "National"), and the newsroom sections articles are filed under.
 */
final class Taxonomy
{
    /** @var array<string, string> type key => label */
    public const TYPES = [
        'community'   => 'Community newspaper',
        'independent' => 'Independent newspaper',
        'regional'    => 'Regional newspaper',
        'national'    => 'National newspaper',
        'online'      => 'Online-only publication',
    ];

    /** @var list<string> */
    public const PROVINCES = [
        'National',
        'Eastern Cape',
        'Free State',
        'Gauteng',
        'KwaZulu-Natal',
        'Limpopo',
        'Mpumalanga',
        'North West',
        'Northern Cape',
        'Western Cape',
    ];

    /** @var list<string> */
    public const SECTIONS = [
        'News',
        'Community',
        'Politics',
        'Business',
        'Sport',
        'Opinion',
        'Lifestyle',
        'Arts & Culture',
        'Education',
        'Obituaries',
    ];

    public static function typeLabel(string $key): ?string
    {
        return self::TYPES[$key] ?? null;
    }

    public static function isType(string $key): bool
    {
        return isset(self::TYPES[$key]);
    }

    /**
     * Resolve a URL slug back to a canonical province name, or null.
     */
    public static function provinceFromSlug(string $slug): ?string
    {
        return self::fromSlug($slug, self::PROVINCES);
    }

    /**
     * Resolve a URL slug back to a canonical section name, or null.
     */
    public static function sectionFromSlug(string $slug): ?string
    {
        return self::fromSlug($slug, self::SECTIONS);
    }

    public static function isSection(string $name): bool
    {
        return in_array($name, self::SECTIONS, true);
    }

    /**
     * @param list<string> $haystack
     */
    private static function fromSlug(string $slug, array $haystack): ?string
    {
        $slug = slugify($slug);
        foreach ($haystack as $name) {
            if (slugify($name) === $slug) {
                return $name;
            }
        }
        return null;
    }
}
