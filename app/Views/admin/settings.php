<?php
/** @var array<string,string> $errors */
/** @var array<string,string> $settings */
/** @var bool $adsLive */
/** @var bool $recaptchaLive */
/** @var string $csrfField */

$field = static fn (array $a): string => view('partials/field', $a);
$err = static fn (string $k): string => $errors[$k] ?? '';
$isSubmit = isset($GLOBALS['old']['_token']);
$checked = static function (string $k) use ($isSubmit): bool {
    return $isSubmit ? isset($_POST[$k]) : (string) ($GLOBALS['old'][$k] ?? '0') === '1';
};
?>
<form class="form admin-form" method="post" action="<?= e(url('admin/settings')) ?>" novalidate>
    <?= $csrfField ?>

    <?php if ($errors !== []): ?>
        <p class="notice notice--warn">Please fix the highlighted fields.</p>
    <?php endif ?>

    <h2 class="form__section">Google AdSense</h2>

    <p class="admin-status">
        Ads are currently
        <strong><?= $adsLive ? 'showing on the site' : 'not showing' ?></strong>.
        <?php if (!$adsLive): ?>
            Enable them and add a publisher ID below.
        <?php endif ?>
    </p>

    <label class="checkbox">
        <input type="checkbox" name="ads_enabled" value="1" <?= $checked('ads_enabled') ? 'checked' : '' ?>>
        Show ads on the site
    </label>

    <?= $field([
        'name' => 'adsense_publisher_id', 'label' => 'AdSense publisher ID',
        'placeholder' => 'ca-pub-XXXXXXXXXXXXXXXX',
        'hint' => 'From your AdSense account. Accepts ca-pub-…, pub-… or just the number.',
        'value' => (string) ($GLOBALS['old']['adsense_publisher_id'] ?? ''),
        'error' => $err('adsense_publisher_id'),
    ]) ?>

    <label class="checkbox">
        <input type="checkbox" name="adsense_auto_ads" value="1" <?= $checked('adsense_auto_ads') ? 'checked' : '' ?>>
        Use Auto ads (Google chooses placements — the slot IDs below are then ignored)
    </label>

    <h3 class="form__subsection">Manual ad slots</h3>
    <p class="field__hint">Slot (ad unit) IDs from AdSense — digits only. Leave blank to hide that placement.</p>
    <?= $field(['name' => 'adsense_slot_leaderboard', 'label' => 'Leaderboard (top of every page)',
                'value' => (string) ($GLOBALS['old']['adsense_slot_leaderboard'] ?? ''), 'error' => $err('adsense_slot_leaderboard')]) ?>
    <?= $field(['name' => 'adsense_slot_infeed', 'label' => 'In-feed (within article and newspaper lists)',
                'value' => (string) ($GLOBALS['old']['adsense_slot_infeed'] ?? ''), 'error' => $err('adsense_slot_infeed')]) ?>
    <?= $field(['name' => 'adsense_slot_article', 'label' => 'Article page (within the story)',
                'value' => (string) ($GLOBALS['old']['adsense_slot_article'] ?? ''), 'error' => $err('adsense_slot_article')]) ?>
    <?= $field(['name' => 'adsense_slot_sidebar', 'label' => 'Sidebar (newspaper and article pages)',
                'value' => (string) ($GLOBALS['old']['adsense_slot_sidebar'] ?? ''), 'error' => $err('adsense_slot_sidebar')]) ?>

    <h2 class="form__section">reCAPTCHA</h2>

    <p class="admin-status">
        The newspaper sign-up form is currently
        <strong><?= $recaptchaLive ? 'protected by reCAPTCHA' : 'not using a CAPTCHA' ?></strong>.
        <?php if (!$recaptchaLive): ?>
            Enable it and add both keys below.
        <?php endif ?>
    </p>

    <label class="checkbox">
        <input type="checkbox" name="recaptcha_enabled" value="1" <?= $checked('recaptcha_enabled') ? 'checked' : '' ?>>
        Require reCAPTCHA when a newsroom registers
    </label>

    <?= $field([
        'name' => 'recaptcha_site_key', 'label' => 'reCAPTCHA site key',
        'hint' => 'From your reCAPTCHA v2 ("I\'m not a robot" checkbox) site at google.com/recaptcha/admin.',
        'value' => (string) ($GLOBALS['old']['recaptcha_site_key'] ?? ''),
        'error' => $err('recaptcha_site_key'),
    ]) ?>
    <?= $field([
        'name' => 'recaptcha_secret_key', 'label' => 'reCAPTCHA secret key',
        'value' => (string) ($GLOBALS['old']['recaptcha_secret_key'] ?? ''),
        'error' => $err('recaptcha_secret_key'),
    ]) ?>

    <h2 class="form__section">General</h2>
    <?= $field(['name' => 'contact_email', 'label' => 'Contact email', 'type' => 'email',
                'hint' => 'Shown on the privacy and terms pages.',
                'value' => (string) ($GLOBALS['old']['contact_email'] ?? ''), 'error' => $err('contact_email')]) ?>

    <div class="admin-form__footer">
        <button class="form__submit" type="submit">Save settings</button>
    </div>
</form>

<p class="admin-note">
    When a publisher ID is set, <a href="<?= e(url('ads.txt')) ?>"><code>/ads.txt</code></a>
    is generated automatically. AdSense also needs a privacy policy — a starter one
    lives at <a href="<?= e(url('privacy')) ?>">/privacy</a>; edit it in
    <code>app/Views/pages/privacy.php</code>.
</p>
<p class="admin-note">
    Register a reCAPTCHA v2 ("I'm not a robot" checkbox) site at
    <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">google.com/recaptcha/admin</a>
    with this site's domain(s) — include <code>localhost</code> while testing locally.
</p>
