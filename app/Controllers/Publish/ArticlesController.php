<?php

declare(strict_types=1);

namespace App\Controllers\Publish;

use App\Models\ArticleAdmin;
use App\Support\Csrf;
use App\Support\HtmlSanitizer;
use App\Support\Paginator;
use App\Support\PublisherAuth;
use App\Support\Taxonomy;
use App\Support\Upload;
use App\Support\Validator;

final class ArticlesController
{
    public function index(): void
    {
        $user = PublisherAuth::requireApproved();
        $npId = (int) $user['newspaper_id'];

        $filters = [
            'status' => (string) ($_GET['status'] ?? ''),
            'q'      => trim((string) ($_GET['q'] ?? '')),
        ];
        $query = http_build_query(array_filter($filters));
        $baseUrl = url('publish/articles') . ($query !== '' ? '?' . $query : '');

        [, $total] = ArticleAdmin::paginate($npId, $filters, 0, 0);
        $paginator = Paginator::fromQuery($total, ArticleAdmin::PER_PAGE, $baseUrl);
        [$rows] = ArticleAdmin::paginate($npId, $filters, ArticleAdmin::PER_PAGE, $paginator->offset);

        publish_view('publish/articles/index', [
            'heading'   => 'Articles',
            'user'      => $user,
            'articles'  => $rows,
            'paginator' => $paginator,
            'filters'   => $filters,
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

        [$data, $errors, $publish] = $this->validate($user, null);
        if ($errors !== []) {
            $this->form($user, null, $errors, $_POST);
            return;
        }

        [$hero, $heroErr] = Upload::image($_FILES['hero_image'] ?? null, 'heroes', $data['slug']);
        if ($heroErr !== null) {
            $this->form($user, null, ['hero_image' => $heroErr], $_POST);
            return;
        }
        if ($hero !== null) {
            $data['hero_image_path'] = $hero;
        }

        $data['status'] = $publish ? 'published' : 'draft';
        $data['published_at'] = $publish ? date('Y-m-d H:i:s') : null;

        $id = ArticleAdmin::create(['newspaper_id' => (int) $user['newspaper_id']] + $data);

        flash('publish_success', $publish ? 'Story published.' : 'Draft saved.');
        redirect(url('publish/articles/' . $id . '/edit'));
    }

    public function edit(array $params): void
    {
        $user = PublisherAuth::requireApproved();
        $article = $this->findOr404($user, $params);
        $this->form($user, $article, [], []);
    }

    public function update(array $params): void
    {
        $user = PublisherAuth::requireApproved();
        $this->guardCsrf();
        $article = $this->findOr404($user, $params);

        [$data, $errors, $publish] = $this->validate($user, (int) $article['id']);
        if ($errors !== []) {
            $this->form($user, $article, $errors, $_POST);
            return;
        }

        [$hero, $heroErr] = Upload::image($_FILES['hero_image'] ?? null, 'heroes', $data['slug']);
        if ($heroErr !== null) {
            $this->form($user, $article, ['hero_image' => $heroErr], $_POST);
            return;
        }
        if ($hero !== null) {
            Upload::delete($article['hero_image_path'] ?? null);
            $data['hero_image_path'] = $hero;
        } elseif (!empty($_POST['remove_hero'])) {
            Upload::delete($article['hero_image_path'] ?? null);
            $data['hero_image_path'] = null;
        }

        if ($publish) {
            $data['status'] = 'published';
            $data['published_at'] = $article['published_at'] ?: date('Y-m-d H:i:s');
        } else {
            $data['status'] = 'draft';
        }

        ArticleAdmin::update((int) $article['id'], (int) $user['newspaper_id'], $data);

        flash('publish_success', $publish ? 'Story published.' : 'Draft saved.');
        redirect(url('publish/articles/' . $article['id'] . '/edit'));
    }

    public function setStatus(array $params): void
    {
        $user = PublisherAuth::requireApproved();
        $this->guardCsrf();
        $article = $this->findOr404($user, $params);

        $to = (string) ($params['action'] ?? '');
        if ($to === 'publish') {
            ArticleAdmin::update((int) $article['id'], (int) $user['newspaper_id'], [
                'status' => 'published',
                'published_at' => $article['published_at'] ?: date('Y-m-d H:i:s'),
            ]);
            flash('publish_success', 'Story published.');
        } elseif ($to === 'unpublish') {
            ArticleAdmin::update((int) $article['id'], (int) $user['newspaper_id'], ['status' => 'draft']);
            flash('publish_success', 'Story moved back to drafts.');
        } else {
            abort(404);
        }
        redirect(url('publish/articles'));
    }

    public function destroy(array $params): void
    {
        $user = PublisherAuth::requireApproved();
        $this->guardCsrf();
        $article = $this->findOr404($user, $params);

        Upload::delete($article['hero_image_path'] ?? null);
        ArticleAdmin::delete((int) $article['id'], (int) $user['newspaper_id']);

        flash('publish_success', 'Story deleted.');
        redirect(url('publish/articles'));
    }

    // -----------------------------------------------------------------------

    /**
     * @param array<string,mixed> $user
     * @return array{0: array<string,mixed>, 1: array<string,string>, 2: bool}
     */
    private function validate(array $user, ?int $ignoreId): array
    {
        $npId = (int) $user['newspaper_id'];
        $publish = isset($_POST['publish']);

        $v = new Validator($_POST);
        $v->label('Headline')->required('title')->max('title', 255);
        $v->label('Standfirst')->optional('standfirst')->max('standfirst', 400);
        $v->label('Section')->optional('section');
        $v->label('Byline')->optional('author_name')->max('author_name', 120);

        $in = $v->validated();

        $section = trim((string) ($in['section'] ?? ''));
        if ($section !== '' && !Taxonomy::isSection($section)) {
            $v->addError('section', 'Choose one of the listed sections.');
        }

        $editionId = null;
        $rawEdition = (int) ($_POST['edition_id'] ?? 0);
        if ($rawEdition > 0) {
            $ok = false;
            foreach (ArticleAdmin::editionOptions($npId) as $opt) {
                if ($opt['id'] === $rawEdition) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $v->addError('edition_id', 'That edition does not belong to your newspaper.');
            } else {
                $editionId = $rawEdition;
            }
        }

        $body = HtmlSanitizer::clean((string) ($_POST['body'] ?? ''));
        if ($publish && trim(strip_tags($body)) === '') {
            $v->addError('body', 'Add some text before publishing.');
        }

        if ($v->fails()) {
            return [[], $v->errors(), $publish];
        }

        $slugSource = trim((string) ($_POST['slug'] ?? '')) ?: (string) $in['title'];
        $slug = slugify($slugSource);
        if ($slug === 'n-a' || ArticleAdmin::slugTaken($slug, $npId, $ignoreId)) {
            $slug = unique_slug($slugSource, static fn (string $s): bool => ArticleAdmin::slugTaken($s, $npId, $ignoreId));
        }

        $data = [
            'slug'        => $slug,
            'title'       => $in['title'],
            'standfirst'  => $in['standfirst'] ?: null,
            'body_html'   => $body ?: null,
            'section'     => $section ?: null,
            'author_name' => $in['author_name'] ?: null,
            'edition_id'  => $editionId,
        ];

        return [$data, [], $publish];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed>|null $article
     * @param array<string,string> $errors
     * @param array<string,mixed> $old
     */
    private function form(array $user, ?array $article, array $errors, array $old): void
    {
        $GLOBALS['old'] = $old !== [] ? $old : ($article ?? []);

        publish_view('publish/articles/form', [
            'heading'     => $article === null ? 'Write a story' : 'Edit story',
            'user'        => $user,
            'article'     => $article,
            'errors'      => $errors,
            'sections'    => Taxonomy::SECTIONS,
            'editions'    => ArticleAdmin::editionOptions((int) $user['newspaper_id']),
            'bodyValue'   => $old !== [] ? (string) ($old['body'] ?? '') : (string) ($article['body_html'] ?? ''),
            'csrfField'   => Csrf::field(),
            'head'        => '<link rel="stylesheet" href="' . e(asset('assets/css/editor.css')) . '">',
            'scripts'     => '<script src="' . e(asset('assets/js/editor.js')) . '" defer></script>',
        ]);
    }

    /**
     * @param array<string,mixed> $user
     * @param array{id?:string} $params
     * @return array<string,mixed>
     */
    private function findOr404(array $user, array $params): array
    {
        $article = ArticleAdmin::find((int) ($params['id'] ?? 0), (int) $user['newspaper_id']);
        if ($article === null) {
            abort(404, 'That story could not be found.');
        }
        return $article;
    }

    private function guardCsrf(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            abort(400, 'Your session expired. Please go back and try again.');
        }
    }
}
