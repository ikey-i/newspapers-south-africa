<?php
/** @var array<string,mixed> $user */
/** @var array<string,mixed>|null $article */
/** @var array<string,string> $errors */
/** @var list<string> $sections */
/** @var list<array{id:int,title:string}> $editions */
/** @var string $bodyValue */
/** @var string $csrfField */
$field = static fn (array $a): string => view('partials/field', $a);
$err = static fn (string $k): string => $errors[$k] ?? '';
$isNew = $article === null;
$isPublished = !$isNew && $article['status'] === 'published';
$action = $isNew ? url('publish/articles') : url('publish/articles/' . $article['id']);
$sectionOpts = array_combine($sections, $sections);
$editionOpts = [];
foreach ($editions as $ed) {
    $editionOpts[(string) $ed['id']] = $ed['title'];
}
?>
<p class="admin-back"><a href="<?= e(url('publish/articles')) ?>">&larr; All articles</a></p>

<form class="form admin-form" method="post" enctype="multipart/form-data" action="<?= e($action) ?>" novalidate
      data-media-endpoint="<?= e(url('publish/media')) ?>" data-token="<?= e(\App\Support\Csrf::token()) ?>">
    <?= $csrfField ?>

    <?php if ($errors !== []): ?><p class="notice notice--warn">Please fix the highlighted fields.</p><?php endif ?>
    <?php if (!$isNew): ?>
        <p class="admin-status">
            Status: <strong><?= e($article['status']) ?></strong>.
            <?php if ($isPublished): ?>
                <a href="<?= e(url('paper/' . $user['newspaper_slug'] . '/article/' . $article['slug'])) ?>" target="_blank" rel="noopener">View live ↗</a>
            <?php endif ?>
        </p>
    <?php endif ?>

    <?= $field(['name' => 'title', 'label' => 'Headline', 'required' => true, 'value' => (string) ($GLOBALS['old']['title'] ?? ''), 'error' => $err('title')]) ?>
    <?= $field(['name' => 'standfirst', 'label' => 'Standfirst / summary', 'type' => 'textarea', 'rows' => 2,
                'hint' => 'One or two sentences shown in listings and search results.',
                'value' => (string) ($GLOBALS['old']['standfirst'] ?? ''), 'error' => $err('standfirst')]) ?>

    <div class="admin-form__grid">
        <?= $field(['name' => 'section', 'label' => 'Section', 'type' => 'select', 'options' => $sectionOpts,
                    'value' => (string) ($GLOBALS['old']['section'] ?? ''), 'error' => $err('section'), 'placeholder' => 'No section']) ?>
        <?= $field(['name' => 'author_name', 'label' => 'Byline', 'placeholder' => 'e.g. Staff Reporter',
                    'value' => (string) ($GLOBALS['old']['author_name'] ?? $user['name']), 'error' => $err('author_name')]) ?>
        <?php if ($editionOpts !== []): ?>
            <?= $field(['name' => 'edition_id', 'label' => 'Part of an edition', 'type' => 'select', 'options' => $editionOpts,
                        'value' => (string) ($GLOBALS['old']['edition_id'] ?? ''), 'error' => $err('edition_id'), 'placeholder' => 'Not tied to an edition']) ?>
        <?php endif ?>
        <?= $field(['name' => 'slug', 'label' => 'URL slug (optional)', 'hint' => 'Leave blank to generate from the headline.',
                    'value' => (string) ($GLOBALS['old']['slug'] ?? ($article['slug'] ?? '')), 'error' => $err('slug')]) ?>
    </div>

    <div class="field">
        <span class="field__label">Article hero image</span>
        <?php if (!$isNew && !empty($article['hero_image_path'])): ?>
            <p class="admin-logo-preview"><img src="<?= e(url($article['hero_image_path'])) ?>" alt="" width="200"></p>
            <label class="checkbox"><input type="checkbox" name="remove_hero" value="1"> Remove current image</label>
        <?php endif ?>
        <input class="field__control" type="file" name="hero_image" accept="image/*">
        <?php if ($err('hero_image') !== ''): ?><p class="field__error"><?= e($err('hero_image')) ?></p><?php endif ?>
    </div>

    <div class="field<?= $err('body') !== '' ? ' field--error' : '' ?>">
        <span class="field__label">Story</span>
        <input type="hidden" id="article-body" name="body" value="<?= e($bodyValue) ?>">
        <trix-editor input="article-body" class="trix-content"></trix-editor>
        <noscript><p class="field__hint">The rich-text editor needs JavaScript. You can still paste plain text into the field above.</p></noscript>
        <?php if ($err('body') !== ''): ?><p class="field__error"><?= e($err('body')) ?></p><?php endif ?>
    </div>

    <div class="admin-form__footer">
        <button class="btn" type="submit" name="save" value="1">Save draft</button>
        <button class="form__submit" type="submit" name="publish" value="1">
            <?= $isPublished ? 'Save &amp; keep published' : 'Publish' ?>
        </button>
        <?php if ($isPublished): ?>
            <span class="admin-review__hint">or use “Save draft” to unpublish.</span>
        <?php endif ?>
    </div>
</form>

<?php if (!$isNew): ?>
<div class="admin-danger">
    <h2>Delete this story</h2>
    <form method="post" action="<?= e(url('publish/articles/' . $article['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this story permanently?');">
        <?= $csrfField ?>
        <button class="btn btn--danger" type="submit">Delete permanently</button>
    </form>
</div>
<?php endif ?>
