<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Setting;
use App\Support\Ads;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Recaptcha;
use App\Support\Validator;

final class SettingsController
{
    private const SLOT_KEYS = [
        'adsense_slot_leaderboard',
        'adsense_slot_infeed',
        'adsense_slot_article',
        'adsense_slot_sidebar',
    ];

    public function edit(): void
    {
        Auth::requireLogin();
        $this->render([]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please go back and try again.');
        }

        $v = new Validator($_POST);
        $v->label('Contact email')->optional('contact_email')->email('contact_email')->max('contact_email', 160);
        $v->label('Admin notification email')->optional('admin_notify_email')->email('admin_notify_email')->max('admin_notify_email', 160);
        $v->label('Publisher ID')->optional('adsense_publisher_id')->max('adsense_publisher_id', 40);
        foreach (self::SLOT_KEYS as $slot) {
            $v->label('Ad slot ID')->optional($slot)->max($slot, 20);
        }
        $v->label('reCAPTCHA site key')->optional('recaptcha_site_key')->max('recaptcha_site_key', 100);
        $v->label('reCAPTCHA secret key')->optional('recaptcha_secret_key')->max('recaptcha_secret_key', 100);

        $in = $v->validated();

        $publisher = '';
        if (trim((string) ($in['adsense_publisher_id'] ?? '')) !== '') {
            $publisher = Ads::normalisePublisherId((string) $in['adsense_publisher_id']);
            if ($publisher === '') {
                $v->addError('adsense_publisher_id', 'That does not look like a publisher ID (e.g. ca-pub-1234567890123456).');
            }
        }

        foreach (self::SLOT_KEYS as $slot) {
            $val = trim((string) ($in[$slot] ?? ''));
            if ($val !== '' && preg_match('/^\d{6,20}$/', $val) !== 1) {
                $v->addError($slot, 'Ad slot IDs are digits only.');
            }
        }

        if (isset($_POST['recaptcha_enabled'])) {
            if (trim((string) ($in['recaptcha_site_key'] ?? '')) === '') {
                $v->addError('recaptcha_site_key', 'Enter a site key to enable reCAPTCHA.');
            }
            if (trim((string) ($in['recaptcha_secret_key'] ?? '')) === '') {
                $v->addError('recaptcha_secret_key', 'Enter a secret key to enable reCAPTCHA.');
            }
        }

        if ($v->fails()) {
            $this->render($v->errors(), $_POST);
            return;
        }

        $values = [
            'ads_enabled'          => isset($_POST['ads_enabled']) ? '1' : '0',
            'adsense_auto_ads'     => isset($_POST['adsense_auto_ads']) ? '1' : '0',
            'adsense_publisher_id' => $publisher,
            'contact_email'        => trim((string) ($in['contact_email'] ?? '')),
            'admin_notify_email'   => trim((string) ($in['admin_notify_email'] ?? '')),
            'recaptcha_enabled'    => isset($_POST['recaptcha_enabled']) ? '1' : '0',
            'recaptcha_site_key'   => trim((string) ($in['recaptcha_site_key'] ?? '')),
            'recaptcha_secret_key' => trim((string) ($in['recaptcha_secret_key'] ?? '')),
        ];
        foreach (self::SLOT_KEYS as $slot) {
            $values[$slot] = trim((string) ($in[$slot] ?? ''));
        }

        Setting::putMany($values);

        flash('admin_success', 'Settings saved.');
        redirect(url('admin/settings'));
    }

    /**
     * @param array<string,string> $errors
     * @param array<string,mixed> $old
     */
    private function render(array $errors, array $old = []): void
    {
        $GLOBALS['old'] = $old !== [] ? $old : Setting::all();

        admin_view('admin/settings', [
            'heading'      => 'Settings',
            'errors'       => $errors,
            'settings'     => Setting::all(),
            'adsLive'      => Ads::enabled(),
            'recaptchaLive' => Recaptcha::enabled(),
            'csrfField'    => Csrf::field(),
        ]);
    }
}
