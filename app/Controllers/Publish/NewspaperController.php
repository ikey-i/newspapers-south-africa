<?php

declare(strict_types=1);

namespace App\Controllers\Publish;

use App\Models\NewspaperAdmin;
use App\Support\Csrf;
use App\Support\PublisherAuth;
use App\Support\Taxonomy;
use App\Support\Upload;
use App\Support\Validator;

/**
 * Lets a publisher edit their own newspaper's public profile. Status, slug,
 * featured flag and admin notes stay under admin control.
 */
final class NewspaperController
{
    public function edit(): void
    {
        $user = PublisherAuth::requireApproved();
        $paper = NewspaperAdmin::find((int) $user['newspaper_id']);
        if ($paper === null) {
            abort(404);
        }
        $this->form($user, $paper, []);
    }

    public function update(): void
    {
        $user = PublisherAuth::requireApproved();
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please go back and try again.');
        }
        $paper = NewspaperAdmin::find((int) $user['newspaper_id']);
        if ($paper === null) {
            abort(404);
        }

        $v = new Validator($_POST);
        $v->label('Name')->required('name')->max('name', 160);
        $v->label('Type')->required('type')->in('type', array_keys(Taxonomy::TYPES));
        $v->label('Province')->optional('province')->in('province', Taxonomy::PROVINCES);
        $v->label('Town / city')->optional('city')->max('city', 120);
        $v->label('Tagline')->optional('tagline')->max('tagline', 255);
        $v->label('About')->optional('about')->max('about', 5000);
        $v->label('Languages')->optional('languages')->max('languages', 255);
        $v->label('Established year')->optional('established_year')->max('established_year', 4);
        $v->label('Website')->optional('website')->url('website')->max('website', 255);
        $v->label('Email')->optional('email')->email('email')->max('email', 160);
        $v->label('Phone')->optional('phone')->max('phone', 60);
        foreach (['facebook', 'twitter', 'instagram'] as $s) {
            $v->label(ucfirst($s))->optional($s)->url($s)->max($s, 255);
        }

        $in = $v->validated();

        $year = (int) ($in['established_year'] ?? 0);
        if ($year !== 0 && ($year < 1800 || $year > (int) date('Y'))) {
            $v->addError('established_year', 'Enter a realistic year.');
        }

        if ($v->fails()) {
            $this->form($user, $paper, $v->errors(), $_POST);
            return;
        }

        [$logo, $logoErr] = Upload::image($_FILES['logo'] ?? null, 'logos', (string) $paper['slug']);
        if ($logoErr !== null) {
            $this->form($user, $paper, ['logo' => $logoErr], $_POST);
            return;
        }

        $data = [
            'name'             => $in['name'],
            'type'             => $in['type'],
            'tagline'          => $in['tagline'] ?: null,
            'about'            => $in['about'] ?: null,
            'province'         => $in['province'] ?: null,
            'city'             => $in['city'] ?: null,
            'languages'        => $in['languages'] ?: null,
            'established_year' => $year ?: null,
            'website'          => $in['website'] ?: null,
            'email'            => $in['email'] ?: null,
            'phone'            => $in['phone'] ?: null,
            'facebook'         => $in['facebook'] ?: null,
            'twitter'          => $in['twitter'] ?: null,
            'instagram'        => $in['instagram'] ?: null,
        ];

        if ($logo !== null) {
            Upload::delete($paper['logo_path'] ?? null);
            $data['logo_path'] = $logo;
        } elseif (!empty($_POST['remove_logo'])) {
            Upload::delete($paper['logo_path'] ?? null);
            $data['logo_path'] = null;
        }

        NewspaperAdmin::update((int) $paper['id'], $data);

        flash('publish_success', 'Newspaper details saved.');
        redirect(url('publish/newspaper'));
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $paper
     * @param array<string,string> $errors
     * @param array<string,mixed> $old
     */
    private function form(array $user, array $paper, array $errors, array $old = []): void
    {
        $GLOBALS['old'] = $old !== [] ? $old : $paper;

        publish_view('publish/newspaper', [
            'heading'   => 'Newspaper details',
            'user'      => $user,
            'paper'     => $paper,
            'errors'    => $errors,
            'types'     => Taxonomy::TYPES,
            'provinces' => Taxonomy::PROVINCES,
            'csrfField' => Csrf::field(),
        ]);
    }
}
