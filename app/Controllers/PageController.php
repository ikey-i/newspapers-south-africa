<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Setting;

final class PageController
{
    public function privacy(): void
    {
        render('pages/privacy', [
            'title'        => 'Privacy policy',
            'contactEmail' => Setting::get('contact_email'),
            'updated'      => '10 September 2026',
            'layout_title' => 'Privacy policy — ' . config('app.name'),
        ]);
    }

    public function terms(): void
    {
        render('pages/terms', [
            'title'        => 'Terms of use',
            'contactEmail' => Setting::get('contact_email'),
            'updated'      => '10 September 2026',
            'layout_title' => 'Terms of use — ' . config('app.name'),
        ]);
    }

    public function listYourNewspaper(): void
    {
        render('pages/list-your-newspaper', [
            'title'        => 'List your newspaper',
            'layout_title' => 'List your newspaper — ' . config('app.name'),
            'layout_description' => 'Are you a South African newsroom? Create a free publisher account to run your online edition, publish stories and share your PDF.',
        ]);
    }
}
