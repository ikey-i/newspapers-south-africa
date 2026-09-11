<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Key/value site settings, backed by the `settings` table and cached for the
 * lifetime of the request.
 */
final class Setting
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /** Known keys and their defaults. */
    public const DEFAULTS = [
        'ads_enabled'          => '0',
        'adsense_publisher_id' => '',
        'adsense_auto_ads'     => '0',
        'adsense_slot_leaderboard' => '',
        'adsense_slot_infeed'  => '',
        'adsense_slot_article' => '',
        'adsense_slot_sidebar' => '',
        'contact_email'        => '',
        'recaptcha_enabled'    => '0',
        'recaptcha_site_key'   => '',
        'recaptcha_secret_key' => '',
    ];

    public static function get(string $key, ?string $default = null): string
    {
        self::load();
        return self::$cache[$key] ?? $default ?? self::DEFAULTS[$key] ?? '';
    }

    public static function bool(string $key): bool
    {
        return self::get($key) === '1';
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        self::load();
        return self::$cache + self::DEFAULTS;
    }

    /**
     * @param array<string, string> $values
     */
    public static function putMany(array $values): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (name, value) VALUES (:name, :value)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        foreach ($values as $name => $value) {
            $stmt->execute(['name' => $name, 'value' => $value]);
        }
        self::$cache = null;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = [];
        try {
            foreach (Database::all('SELECT name, value FROM settings') as $row) {
                self::$cache[(string) $row['name']] = (string) ($row['value'] ?? '');
            }
        } catch (\Throwable) {
            // Table missing (pre-migration) — fall back to defaults.
            self::$cache = [];
        }
    }
}
