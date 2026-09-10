<?php
/** @var array<string,mixed>|null $paper */
/** @var array<string,string> $errors */
/** @var array<string,string> $types */
/** @var list<string> $provinces */
/** @var string $csrfField */
$field = static fn (array $a): string => view('partials/field', $a);
$err = static fn (string $k): string => $errors[$k] ?? '';
$val = static fn (string $k): string => (string) ($GLOBALS['old'][$k] ?? '');
$isNew = $paper === null;
$featured = isset($GLOBALS['old']['_token']) ? isset($_POST['is_featured']) : (int) ($GLOBALS['old']['is_featured'] ?? 0) === 1;
?>
<p class="admin-back"><a href="<?= e(url('admin/newspapers')) ?>">&larr; All newspapers</a></p>

<form class="form admin-form" method="post" enctype="multipart/form-data"
      action="<?= e($isNew ? url('admin/newspapers') : url('admin/newspapers/' . $paper['id'])) ?>" novalidate>
    <?= $csrfField ?>

    <?php if ($errors !== []): ?><p class="notice notice--warn">Please fix the highlighted fields.</p><?php endif ?>

    <div class="admin-form__grid">
        <?= $field(['name' => 'name', 'label' => 'Newspaper name', 'required' => true, 'value' => $val('name'), 'error' => $err('name')]) ?>
        <?= $field(['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => $types, 'value' => $val('type'), 'error' => $err('type')]) ?>
        <?= $field(['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true,
                    'options' => ['pending' => 'Pending', 'active' => 'Active (public)', 'suspended' => 'Suspended', 'rejected' => 'Rejected'],
                    'value' => $val('status'), 'error' => $err('status')]) ?>
        <?= $field(['name' => 'tagline', 'label' => 'Tagline', 'value' => $val('tagline'), 'error' => $err('tagline')]) ?>
        <?= $field(['name' => 'province', 'label' => 'Province', 'type' => 'select', 'options' => array_combine($provinces, $provinces), 'value' => $val('province'), 'error' => $err('province'), 'placeholder' => '—']) ?>
        <?= $field(['name' => 'city', 'label' => 'Town / city', 'value' => $val('city'), 'error' => $err('city')]) ?>
        <?= $field(['name' => 'languages', 'label' => 'Languages', 'hint' => 'Comma-separated, e.g. English, isiZulu', 'value' => $val('languages'), 'error' => $err('languages')]) ?>
        <?= $field(['name' => 'established_year', 'label' => 'Established year', 'value' => $val('established_year'), 'error' => $err('established_year')]) ?>
        <?= $field(['name' => 'website', 'label' => 'Website', 'type' => 'url', 'placeholder' => 'https://', 'value' => $val('website'), 'error' => $err('website')]) ?>
        <?= $field(['name' => 'email', 'label' => 'Public email', 'type' => 'email', 'value' => $val('email'), 'error' => $err('email')]) ?>
        <?= $field(['name' => 'phone', 'label' => 'Phone', 'value' => $val('phone'), 'error' => $err('phone')]) ?>
        <?= $field(['name' => 'facebook', 'label' => 'Facebook URL', 'type' => 'url', 'value' => $val('facebook'), 'error' => $err('facebook')]) ?>
        <?= $field(['name' => 'twitter', 'label' => 'X / Twitter URL', 'type' => 'url', 'value' => $val('twitter'), 'error' => $err('twitter')]) ?>
        <?= $field(['name' => 'instagram', 'label' => 'Instagram URL', 'type' => 'url', 'value' => $val('instagram'), 'error' => $err('instagram')]) ?>
    </div>

    <?= $field(['name' => 'about', 'label' => 'About', 'type' => 'textarea', 'rows' => 5, 'value' => $val('about'), 'error' => $err('about')]) ?>

    <div class="field">
        <span class="field__label">Masthead / logo</span>
        <?php if (!$isNew && !empty($paper['logo_path'])): ?>
            <p class="admin-logo-preview"><img src="<?= e(url($paper['logo_path'])) ?>" alt="" width="120"></p>
            <label class="checkbox"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>
        <?php endif ?>
        <input class="field__control" type="file" name="logo" accept="image/*">
        <?php if ($err('logo') !== ''): ?><p class="field__error"><?= e($err('logo')) ?></p><?php endif ?>
    </div>

    <label class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= $featured ? 'checked' : '' ?>> Feature on the home page</label>

    <div class="admin-form__footer">
        <button class="form__submit" type="submit"><?= $isNew ? 'Add newspaper' : 'Save changes' ?></button>
        <?php if (!$isNew): ?><a class="btn" href="<?= e(url('paper/' . $paper['slug'])) ?>" target="_blank" rel="noopener">View ↗</a><?php endif ?>
    </div>
</form>

<?php if (!$isNew): ?>
<div class="admin-danger">
    <h2>Delete this newspaper</h2>
    <p>Removes the newspaper and <strong>all</strong> its articles, editions, publisher accounts and uploaded files. This cannot be undone.</p>
    <form method="post" action="<?= e(url('admin/newspapers/' . $paper['id'] . '/delete')) ?>" onsubmit="return confirm('Delete “<?= e($paper['name']) ?>” and everything in it?');">
        <?= $csrfField ?>
        <button class="btn btn--danger" type="submit">Delete permanently</button>
    </form>
</div>
<?php endif ?>
