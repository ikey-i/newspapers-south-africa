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
            'updated'      => '12 September 2026',
            'layout_title' => 'Privacy policy — ' . config('app.name'),
            ...$this->legalContext(),
        ]);
    }

    public function terms(): void
    {
        render('pages/terms', [
            'title'        => 'Terms of use',
            'updated'      => '12 September 2026',
            'layout_title' => 'Terms of use — ' . config('app.name'),
            ...$this->legalContext(),
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

    /**
     * Shared operator/contact details for the legal pages.
     *
     * @return array{contactEmail:string,operatorName:string,operatorAddress:string,
     *               infoOfficerName:string,infoOfficerEmail:string,needsReview:bool}
     */
    private function legalContext(): array
    {
        $contactEmail = Setting::get('contact_email');
        $operatorName = (string) config('legal.operator_name', '');
        $officerEmail = (string) config('legal.info_officer_email', '');

        return [
            'contactEmail'     => $contactEmail,
            'operatorName'     => $operatorName,
            'operatorAddress'  => (string) config('legal.operator_address', ''),
            'infoOfficerName'  => (string) config('legal.info_officer_name', ''),
            'infoOfficerEmail' => $officerEmail !== '' ? $officerEmail : $contactEmail,
            // A blank/placeholder operator name (still holding its default
            // "[...]" bracket) means nobody has filled in config('legal') yet.
            'needsReview'      => $operatorName === '' || str_contains($operatorName, '['),
        ];
    }
}
