<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\NewspaperAdmin;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Paginator;
use App\Support\Taxonomy;
use App\Support\Upload;
use App\Support\Validator;

final class NewspapersController
{
    public function index(): void
    {
        Auth::requireLogin();

        $filters = [
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'status'   => (string) ($_GET['status'] ?? ''),
            'province' => Taxonomy::provinceFromSlug((string) ($_GET['province'] ?? '')) ?? '',
        ];

        $query = http_build_query(array_filter([
            'q' => $filters['q'], 'status' => $filters['status'],
            'province' => $_GET['province'] ?? '',
        ]));
        $baseUrl = url('admin/newspapers') . ($query !== '' ? '?' . $query : '');

        [, $total] = NewspaperAdmin::paginate($filters, 0, 0);
        $paginator = Paginator::fromQuery($total, NewspaperAdmin::PER_PAGE, $baseUrl);
        [$rows] = NewspaperAdmin::paginate($filters, NewspaperAdmin::PER_PAGE, $paginator->offset);

        admin_view('admin/newspapers/index', [
            'heading'   => 'Newspapers',
            'newspapers' => $rows,
            'paginator' => $paginator,
            'filters'   => $filters,
            'counts'    => NewspaperAdmin::statusCounts(),
            'total'     => $total,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        $this->form(null, [], []);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $this->guardCsrf();

        [$data, $errors] = $this->validate(null);
        if ($errors !== []) {
            $this->form(null, $errors, $_POST);
            return;
        }

        [$logo, $logoError] = Upload::image($_FILES['logo'] ?? null, 'logos', $data['slug']);
        if ($logoError !== null) {
            $this->form(null, ['logo' => $logoError], $_POST);
            return;
        }
        if ($logo !== null) {
            $data['logo_path'] = $logo;
        }

        $id = NewspaperAdmin::create($data);
        flash('admin_success', "“{$data['name']}” was added.");
        redirect(url('admin/newspapers/' . $id . '/edit'));
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $paper = $this->findOr404($params);
        $this->form($paper, [], []);
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        $this->guardCsrf();
        $paper = $this->findOr404($params);

        [$data, $errors] = $this->validate((int) $paper['id']);
        if ($errors !== []) {
            $this->form($paper, $errors, $_POST);
            return;
        }

        [$logo, $logoError] = Upload::image($_FILES['logo'] ?? null, 'logos', $data['slug']);
        if ($logoError !== null) {
            $this->form($paper, ['logo' => $logoError], $_POST);
            return;
        }
        if ($logo !== null) {
            Upload::delete($paper['logo_path'] ?? null);
            $data['logo_path'] = $logo;
        } elseif (!empty($_POST['remove_logo'])) {
            Upload::delete($paper['logo_path'] ?? null);
            $data['logo_path'] = null;
        }

        NewspaperAdmin::update((int) $paper['id'], $data);
        flash('admin_success', "“{$data['name']}” was saved.");
        redirect(url('admin/newspapers/' . $paper['id'] . '/edit'));
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        $this->guardCsrf();
        $paper = $this->findOr404($params);

        Upload::delete($paper['logo_path'] ?? null);
        NewspaperAdmin::delete((int) $paper['id']);
        flash('admin_success', "“{$paper['name']}” and its content were deleted.");
        redirect(url('admin/newspapers'));
    }

    public function setStatus(array $params): void
    {
        Auth::requireLogin();
        $this->guardCsrf();
        $paper = $this->findOr404($params);

        $map = [
            'approve' => 'active',
            'reject'  => 'rejected',
            'suspend' => 'suspended',
            'restore' => 'active',
        ];
        $action = (string) ($params['action'] ?? '');
        if (!isset($map[$action])) {
            abort(404);
        }

        NewspaperAdmin::setStatus((int) $paper['id'], $map[$action]);
        $verb = ['approve' => 'approved', 'reject' => 'rejected', 'suspend' => 'suspended', 'restore' => 'restored'][$action];
        flash('admin_success', "“{$paper['name']}” was {$verb}.");

        $return = (string) ($_POST['return'] ?? '');
        $safe = (str_starts_with($return, '/') && !str_starts_with($return, '//')
                 && !str_contains($return, '\\') && str_contains($return, 'admin/newspapers'))
            ? $return
            : url('admin/newspapers');
        redirect($safe);
    }

    // -----------------------------------------------------------------------

    /**
     * @return array{0: array<string,mixed>, 1: array<string,string>}
     */
    private function validate(?int $ignoreId): array
    {
        $v = new Validator($_POST);
        $v->label('Name')->required('name')->max('name', 160);
        $v->label('Type')->required('type')->in('type', array_keys(Taxonomy::TYPES));
        $v->label('Status')->required('status')->in('status', ['pending', 'active', 'rejected', 'suspended']);
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

        if ($v->fails()) {
            return [[], $v->errors()];
        }
        $in = $v->validated();

        $year = (int) ($in['established_year'] ?? 0);
        if ($year !== 0 && ($year < 1800 || $year > (int) date('Y'))) {
            return [[], ['established_year' => 'Enter a realistic year.']];
        }

        $slugSource = trim((string) ($_POST['slug'] ?? '')) ?: $in['name'];
        $slug = slugify($slugSource);
        if ($slug === 'n-a' || NewspaperAdmin::slugTaken($slug, $ignoreId)) {
            $slug = unique_slug($slugSource, static fn (string $s): bool => NewspaperAdmin::slugTaken($s, $ignoreId));
        }

        $data = [
            'slug'             => $slug,
            'name'             => $in['name'],
            'type'             => $in['type'],
            'status'           => $in['status'],
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
            'is_featured'      => isset($_POST['is_featured']) ? 1 : 0,
        ];

        return [$data, []];
    }

    /**
     * @param array<string,mixed>|null $paper
     * @param array<string,string> $errors
     * @param array<string,mixed> $old
     */
    private function form(?array $paper, array $errors, array $old): void
    {
        $GLOBALS['old'] = $old !== [] ? $old : ($paper ?? ['status' => 'active']);

        admin_view('admin/newspapers/form', [
            'heading'   => $paper === null ? 'Add a newspaper' : 'Edit ' . $paper['name'],
            'paper'     => $paper,
            'errors'    => $errors,
            'types'     => Taxonomy::TYPES,
            'provinces' => Taxonomy::PROVINCES,
            'csrfField' => Csrf::field(),
        ]);
    }

    /**
     * @param array{id?:string} $params
     * @return array<string,mixed>
     */
    private function findOr404(array $params): array
    {
        $paper = NewspaperAdmin::find((int) ($params['id'] ?? 0));
        if ($paper === null) {
            abort(404, 'That newspaper could not be found.');
        }
        return $paper;
    }

    private function guardCsrf(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please go back and try again.');
        }
    }
}
