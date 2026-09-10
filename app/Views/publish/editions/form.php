<?php
/** @var array<string,mixed> $user */
/** @var array<string,mixed>|null $edition */
/** @var array<string,string> $errors */
/** @var string $csrfField */
$field = static fn (array $a): string => view('partials/field', $a);
$err = static fn (string $k): string => $errors[$k] ?? '';
$val = static fn (string $k): string => (string) ($GLOBALS['old'][$k] ?? '');
$isNew = $edition === null;
$isPublished = !$isNew && (int) $edition['is_published'] === 1;
$action = $isNew ? url('publish/editions') : url('publish/editions/' . $edition['id']);
?>
<p class="admin-back"><a href="<?= e(url('publish/editions')) ?>">&larr; All editions</a></p>

<form class="form admin-form" method="post" enctype="multipart/form-data" action="<?= e($action) ?>" novalidate>
    <?= $csrfField ?>
    <?php if ($errors !== []): ?><p class="notice notice--warn">Please fix the highlighted fields.</p><?php endif ?>

    <div class="admin-form__grid">
        <?= $field(['name' => 'title', 'label' => 'Edition title', 'required' => true, 'placeholder' => 'e.g. Week of 8 September 2026', 'value' => $val('title'), 'error' => $err('title')]) ?>
        <?= $field(['name' => 'edition_date', 'label' => 'Edition date', 'type' => 'date', 'value' => $val('edition_date') ?: (!$isNew && $edition['edition_date'] ? substr((string) $edition['edition_date'], 0, 10) : ''), 'error' => $err('edition_date')]) ?>
    </div>

    <?= $field(['name' => 'description', 'label' => 'Description (optional)', 'type' => 'textarea', 'rows' => 3, 'value' => $val('description'), 'error' => $err('description')]) ?>

    <div class="field">
        <span class="field__label">Cover image (optional)</span>
        <?php if (!$isNew && !empty($edition['cover_path'])): ?>
            <p class="admin-logo-preview"><img src="<?= e(url($edition['cover_path'])) ?>" alt="" width="150"></p>
            <label class="checkbox"><input type="checkbox" name="remove_cover" value="1"> Remove current cover</label>
        <?php endif ?>
        <input class="field__control" type="file" name="cover" accept="image/*">
        <?php if ($err('cover') !== ''): ?><p class="field__error"><?= e($err('cover')) ?></p><?php endif ?>
    </div>

    <div class="field">
        <span class="field__label">Full-edition PDF</span>
        <p class="field__hint">Up to 25 MB. This is what readers download.</p>
        <?php if (!$isNew && !empty($edition['pdf_path'])): ?>
            <p class="admin-status">
                Current PDF: <?= e(number_format((int) $edition['pdf_size'] / 1048576, 1)) ?> MB —
                <a href="<?= e(url('paper/' . $user['newspaper_slug'] . '/editions/' . $edition['id'] . '/pdf')) ?>" target="_blank" rel="noopener">open</a>
            </p>
            <label class="checkbox"><input type="checkbox" name="remove_pdf" value="1"> Remove current PDF</label>
        <?php endif ?>
        <input class="field__control" type="file" name="pdf" accept="application/pdf,.pdf">
        <?php if ($err('pdf') !== ''): ?><p class="field__error"><?= e($err('pdf')) ?></p><?php endif ?>
    </div>

    <div class="admin-form__footer">
        <button class="btn" type="submit" name="save" value="1">Save draft</button>
        <button class="form__submit" type="submit" name="publish" value="1"><?= $isPublished ? 'Save &amp; keep published' : 'Publish' ?></button>
    </div>
</form>

<?php if (!$isNew): ?>
<div class="admin-danger">
    <h2>Delete this edition</h2>
    <p>The PDF and cover are removed. Articles filed under it stay, but lose the link.</p>
    <form method="post" action="<?= e(url('publish/editions/' . $edition['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this edition permanently?');">
        <?= $csrfField ?>
        <button class="btn btn--danger" type="submit">Delete permanently</button>
    </form>
</div>
<?php endif ?>
