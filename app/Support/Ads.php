<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * Google AdSense integration, driven by the site settings.
 *
 * Nothing renders unless ads are enabled AND a publisher ID is set.
 */
final class Ads
{
    /** settings key => on-page placeholder name. */
    private const SLOT_KEYS = [
        'leaderboard' => 'adsense_slot_leaderboard',
        'infeed'      => 'adsense_slot_infeed',
        'article'     => 'adsense_slot_article',
        'sidebar'     => 'adsense_slot_sidebar',
    ];

    public static function enabled(): bool
    {
        return Setting::bool('ads_enabled') && self::publisherId() !== '';
    }

    public static function autoAds(): bool
    {
        return self::enabled() && Setting::bool('adsense_auto_ads');
    }

    /**
     * The "ca-pub-XXXXXXXXXXXXXXXX" client id, or '' if not set / malformed.
     */
    public static function publisherId(): string
    {
        return self::normalisePublisherId(Setting::get('adsense_publisher_id'));
    }

    /**
     * Accepts "ca-pub-123", "pub-123" or "123" and returns "ca-pub-123",
     * or '' if it doesn't look like a publisher id.
     */
    public static function normalisePublisherId(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/(\d{10,20})/', $raw, $m) !== 1) {
            return '';
        }
        return 'ca-pub-' . $m[1];
    }

    /**
     * The <head> loader script. Empty when ads are off.
     */
    public static function headScript(): string
    {
        if (!self::enabled()) {
            return '';
        }
        $client = e(self::publisherId());
        return '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='
            . $client . '" crossorigin="anonymous"></script>';
    }

    /**
     * A manual ad unit for the named placeholder. Empty when ads are off, when
     * auto-ads is on (Google places them itself), or when the slot id is blank.
     */
    public static function slot(string $name): string
    {
        if (!self::enabled() || self::autoAds()) {
            return '';
        }
        $key = self::SLOT_KEYS[$name] ?? null;
        if ($key === null) {
            return '';
        }
        $slotId = trim(Setting::get($key));
        if ($slotId === '' || preg_match('/^\d{6,20}$/', $slotId) !== 1) {
            return '';
        }

        $client = e(self::publisherId());
        $slot = e($slotId);

        return <<<HTML
        <div class="ad-slot ad-slot--{$name}" aria-hidden="true">
            <ins class="adsbygoogle" style="display:block" data-ad-client="{$client}"
                 data-ad-slot="{$slot}" data-ad-format="auto" data-full-width-responsive="true"></ins>
            <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
        </div>
        HTML;
    }

    /**
     * The single line an ads.txt file needs, or '' when no publisher id is set.
     */
    public static function adsTxtLine(): string
    {
        $client = self::publisherId();
        if ($client === '') {
            return '';
        }
        $pub = substr($client, 3); // strip "ca-"
        return "google.com, {$pub}, DIRECT, f08c47fec0942fa0";
    }
}
