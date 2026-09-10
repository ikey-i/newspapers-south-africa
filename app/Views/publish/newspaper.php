<?php
/** @var array<string,mixed> $user */
/** @var array<string,mixed> $paper */
/** @var array<string,string> $errors */
/** @var array<string,string> $types */
/** @var list<string> $provinces */
/** @var string $csrfField */
$field = static fn (array $a): string => view('partials/field', $a);
$err = static fn (string $k): string => $errors[$k] ?? '';
$val = static fn (string $k): string => (string) ($GLOBALS['old'][$k] ?? '');
?>
<p class="admin-status">
    Public page: <a href="<?= e(url('paper/' . $paper['slug'])) ?>" target="_blank" rel="noopener"><?= e(base_url('paper/' . $paper['slug'])) ?></a>
    <br>The web address and featured status are set by the site administrators.
</p>

<form class="form admin-form" method="post" enctype="multipart/form-data" action="<?= e(url('publish/newspaper')) ?>" novalidate>
    <?= $csrfField ?>
    <?php if ($errors !== []): ?><p class="notice notice--warn">Please fix the highlighted fields.</p><?php endif ?>

    <div class="admin-form__grid">
        <?= $field(['name' => 'name', 'label' => 'Newspaper name', 'required' => true, 'value' => $val('name'), 'error' => $err('name')]) ?>
        <?= $field(['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => $types, 'value' => $val('type'), 'error' => $err('type')]) ?>
        <?= $field(['name' => 'tagline', 'label' => 'Tagline', 'value' => $val('tagline'), 'error' => $err('tagline')]) ?>
        <?= $field(['name' => 'province', 'label' => 'Province', 'type' => 'select', 'options' => array_combine($provinces, $provinces), 'value' => $val('province'), 'error' => $err('province'), 'placeholder' => '—']) ?>
        <?= $field(['name' => 'city', 'label' => 'Town / city', 'value' => $val('city'), 'error' => $err('city')]) ?>
        <?= $field(['name' => 'languages', 'label' => 'Languages', 'hint' => 'Comma-separated', 'value' => $val('languages'), 'error' => $err('languages')]) ?>
        <?= $field(['name' => 'established_year', 'label' => 'Established year', 'value' => $val('established_year'), 'error' => $err('established_year')]) ?>
        <?= $field(['name' => 'website', 'label' => 'Website', 'type' => 'url', 'placeholder' => 'https://', 'value' => $val('website'), 'error' => $err('website')]) ?>
        <?= $field(['name' => 'email', 'label' => 'Public email', 'type' => 'email', 'value' => $val('email'), 'error' => $err('email')]) ?>
        <?= $field(['name' => 'phone', 'label' => 'Phone', 'value' => $val('phone'), 'error' => $err('phone')]) ?>
        <?= $field(['name' => 'facebook', 'label' => 'Facebook URL', 'type' => 'url', 'value' => $val('facebook'), 'error' => $err('facebook')]) ?>
        <?= $field(['name' => 'twitter', 'label' => 'X / Twitter URL', 'type' => 'url', 'value' => $val('twitter'), 'error' => $err('twitter')]) ?>
        <?= $field(['name' => 'instagram', 'label' => 'Instagram URL', 'type' => 'url', 'value' => $val('instagram'), 'error' => $err('instagram')]) ?>
    </div>

    <?= $field(['name' => 'about', 'label' => 'About the newspaper', 'type' => 'textarea', 'rows' => 5, 'value' => $val('about'), 'error' => $err('about')]) ?>

    <div class="field">
        <span class="field__label">Masthead / logo</span>
        <?php if (!empty($paper['logo_path'])): ?>
            <p class="admin-logo-preview"><img src="<?= e(url($paper['logo_path'])) ?>" alt="" width="140"></p>
            <label class="checkbox"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>
        <?php endif ?>
        <input class="field__control" type="file" name="logo" accept="image/*">
        <?php if ($err('logo') !== ''): ?><p class="field__error"><?= e($err('logo')) ?></p><?php endif ?>
    </div>

    <div class="admin-form__footer">
        <button class="form__submit" type="submit">Save details</button>
        <a class="btn" href="<?= e(url('paper/' . $paper['slug'])) ?>" target="_blank" rel="noopener">View page ↗</a>
    </div>
</form>
