<?php

declare(strict_types=1);

namespace App\Controllers\Publish;

use App\Models\EditionAdmin;
use App\Support\Csrf;
use App\Support\Paginator;
use App\Support\PublisherAuth;
use App\Support\Upload;
use App\Support\Validator;

final class EditionsController
{
    public function index(): void
    {
        $user = PublisherAuth::requireApproved();
        $npId = (int) $user['newspaper_id'];

        [, $total] = EditionAdmin::paginate($npId, 0, 0);
        $paginator = Paginator::fromQuery($total, EditionAdmin::PER_PAGE, url('publish/editions'));
        [$rows] = EditionAdmin::paginate($npId, EditionAdmin::PER_PAGE, $paginator->offset);

        publish_view('publish/editions/index', [
            'heading'   => 'Editions',
            'user'      => $user,
            'editions'  => $rows,
            'paginator' => $paginator,
            'total'     => $total,
        ]);
    }

    public function create(): void
    {
        $user = PublisherAuth::requireApproved();
        $this->form($user, null, [], []);
    }

    public function store(): void
    {
        $user = PublisherAuth::requireApproved();
        $this->guardCsrf();

        [$data, $errors, $publish] = $this->validate();
        if ($errors !== []) {
            $this->form($user, null, $errors, $_POST);
            return;
        }

        $stem = slugify(($user['newspaper_slug'] ?? 'edition') . '-' . ($data['title'] ?: 'edition'));

        [$cover, $coverErr] = Upload::image($_FILES['cover'] ?? null, 'covers', $stem);
        if ($coverErr !== null) {
            $this->form($user, null, ['cover' => $coverErr], $_POST);
            return;
        }
        [$pdf, $pdfErr, $pdfBytes] = Upload::pdf($_FILES['pdf'] ?? null, $stem);
        if ($pdfErr !== null) {
            Upload::delete($cover);
            $this->form($user, null, ['pdf' => $pdfErr], $_POST);
            return;
        }

        if ($cover !== null) {
            $data['cover_path'] = $cover;
        }
        if ($pdf !== null) {
            $data['pdf_path'] = $pdf;
            $data['pdf_size'] = $pdfBytes;
        }
        $data['is_published'] = $publish ? 1 : 0;

        $id = EditionAdmin::create(['newspaper_id' => (int) $user['newspaper_id']] + $data);

        flash('publish_success', $publish ? 'Edition published.' : 'Edition saved as a draft.');
        redirect(url('publish/editions/' . $id . '/edit'));
    }

    public function edit(array $params): void
    {
        $user = PublisherAuth::requireApproved();
        $edition = $this->findOr404($user, $params);
        $this->form($user, $edition, [], []);
    }

    public function update(array $params): void
    {
        $user = PublisherAuth::requireApproved();
        $this->guardCsrf();
        $edition = $this->findOr404($user, $params);

        [$data, $errors, $publish] = $this->validate();
        if ($errors !== []) {
            $this->form($user, $edition, $errors, $_POST);
            return;
        }

        $stem = slugify(($user['newspaper_slug'] ?? 'edition') . '-' . ($data['title'] ?: 'edition'));

        [$cover, $coverErr] = Upload::image($_FILES['cover'] ?? null, 'covers', $stem);
        if ($coverErr !== null) {
            $this->form($user, $edition, ['cover' => $coverErr], $_POST);
            return;
        }
        [$pdf, $pdfErr, $pdfBytes] = Upload::pdf($_FILES['pdf'] ?? null, $stem);
        if ($pdfErr !== null) {
            Upload::delete($cover);
            $this->form($user, $edition, ['pdf' => $pdfErr], $_POST);
            return;
        }

        if ($cover !== null) {
            Upload::delete($edition['cover_path'] ?? null);
            $data['cover_path'] = $cover;
        } elseif (!empty($_POST['remove_cover'])) {
            Upload::delete($edition['cover_path'] ?? null);
            $data['cover_path'] = null;
        }
        if ($pdf !== null) {
            Upload::delete($edition['pdf_path'] ?? null);
            $data['pdf_path'] = $pdf;
            $data['pdf_size'] = $pdfBytes;
        } elseif (!empty($_POST['remove_pdf'])) {
            Upload::delete($edition['pdf_path'] ?? null);
            $data['pdf_path'] = null;
            $data['pdf_size'] = null;
        }
        $data['is_published'] = $publish ? 1 : 0;

        EditionAdmin::update((int) $edition['id'], (int) $user['newspaper_id'], $data);

        flash('publish_success', $publish ? 'Edition published.' : 'Edition saved as a draft.');
        redirect(url('publish/editions/' . $edition['id'] . '/edit'));
    }

    public function setStatus(array $params): void
    {
        $user = PublisherAuth::requireApproved();
        $this->guardCsrf();
        $edition = $this->findOr404($user, $params);

        $to = (string) ($params['action'] ?? '');
        if ($to === 'publish') {
            if (empty($edition['pdf_path']) && empty($edition['cover_path'])) {
                flash('publish_notice', 'Add a PDF or a cover before publishing that edition.');
                redirect(url('publish/editions/' . $edition['id'] . '/edit'));
            }
            EditionAdmin::update((int) $edition['id'], (int) $user['newspaper_id'], ['is_published' => 1]);
            flash('publish_success', 'Edition published.');
        } elseif ($to === 'unpublish') {
            EditionAdmin::update((int) $edition['id'], (int) $user['newspaper_id'], ['is_published' => 0]);
            flash('publish_success', 'Edition hidden.');
        } else {
            abort(404);
        }
        redirect(url('publish/editions'));
    }

    public function destroy(array $params): void
    {
        $user = PublisherAuth::requireApproved();
        $this->guardCsrf();
        $edition = $this->findOr404($user, $params);

        Upload::delete($edition['cover_path'] ?? null);
        Upload::delete($edition['pdf_path'] ?? null);
        EditionAdmin::delete((int) $edition['id'], (int) $user['newspaper_id']);

        flash('publish_success', 'Edition deleted.');
        redirect(url('publish/editions'));
    }

    // -----------------------------------------------------------------------

    /**
     * @return array{0: array<string,mixed>, 1: array<string,string>, 2: bool}
     */
    private function validate(): array
    {
        $publish = isset($_POST['publish']);

        $v = new Validator($_POST);
        $v->label('Edition title')->required('title')->max('title', 200);
        $v->label('Description')->optional('description')->max('description', 2000);
        $v->label('Edition date')->optional('edition_date');
        $in = $v->validated();

        $date = trim((string) ($in['edition_date'] ?? ''));
        if ($date !== '' && \DateTime::createFromFormat('Y-m-d', $date) === false) {
            $v->addError('edition_date', 'Use the date picker (YYYY-MM-DD).');
        }

        if ($v->fails()) {
            return [[], $v->errors(), $publish];
        }

        return [[
            'title'       => $in['title'],
            'edition_date' => $date ?: null,
            'description' => $in['description'] ?: null,
        ], [], $publish];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed>|null $edition
     * @param array<string,string> $errors
     * @param array<string,mixed> $old
     */
    private function form(array $user, ?array $edition, array $errors, array $old): void
    {
        $GLOBALS['old'] = $old !== [] ? $old : ($edition ?? []);

        publish_view('publish/editions/form', [
            'heading'   => $edition === null ? 'Add an edition' : 'Edit edition',
            'user'      => $user,
            'edition'   => $edition,
            'errors'    => $errors,
            'csrfField' => Csrf::field(),
        ]);
    }

    /**
     * @param array<string,mixed> $user
     * @param array{id?:string} $params
     * @return array<string,mixed>
     */
    private function findOr404(array $user, array $params): array
    {
        $edition = EditionAdmin::find((int) ($params['id'] ?? 0), (int) $user['newspaper_id']);
        if ($edition === null) {
            abort(404, 'That edition could not be found.');
        }
        return $edition;
    }

    private function guardCsrf(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please go back and try again.');
        }
    }
}
