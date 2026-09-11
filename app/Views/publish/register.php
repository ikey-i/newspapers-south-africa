<?php
/** @var array<string,string> $errors */
/** @var string $csrfField */
/** @var string $guardField */
/** @var array<string,string> $types */
/** @var list<string> $provinces */
/** @var string $captchaWidget */
$field = static fn (array $a): string => view('partials/field', $a);
$err = static fn (string $k): string => $errors[$k] ?? '';
?>
<div class="auth-wrap auth-wrap--wide">
    <h1>Register your newspaper</h1>
    <p class="lead">Free for South African newsrooms. Verify your email, then our team approves the listing.</p>

    <?php if ($errors !== []): ?>
        <p class="notice notice--warn">Please fix the highlighted fields.</p>
    <?php endif ?>

    <form class="form" method="post" action="<?= e(url('publish/register')) ?>" novalidate>
        <?= $csrfField ?><?= $guardField ?>

        <h2 class="form__section">Your newspaper</h2>
        <?= $field(['name' => 'paper_name', 'label' => 'Newspaper name', 'required' => true, 'value' => old('paper_name'), 'error' => $err('paper_name')]) ?>
        <?= $field(['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => $types, 'value' => old('type'), 'error' => $err('type')]) ?>
        <div class="form__row">
            <?= $field(['name' => 'province', 'label' => 'Province', 'type' => 'select', 'options' => array_combine($provinces, $provinces), 'value' => old('province'), 'error' => $err('province'), 'placeholder' => 'Choose a province']) ?>
            <?= $field(['name' => 'city', 'label' => 'Town / city', 'value' => old('city'), 'error' => $err('city')]) ?>
        </div>
        <?= $field(['name' => 'website', 'label' => 'Website (optional)', 'type' => 'url', 'placeholder' => 'https://', 'value' => old('website'), 'error' => $err('website')]) ?>

        <h2 class="form__section">Your account</h2>
        <?= $field(['name' => 'name', 'label' => 'Your name', 'required' => true, 'value' => old('name'), 'error' => $err('name')]) ?>
        <?= $field(['name' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true, 'value' => old('email'), 'error' => $err('email')]) ?>
        <div class="form__row">
            <?= $field(['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'hint' => 'At least 10 characters.', 'error' => $err('password')]) ?>
            <?= $field(['name' => 'password_confirm', 'label' => 'Confirm password', 'type' => 'password', 'required' => true, 'error' => $err('password_confirm')]) ?>
        </div>

        <p class="field__hint">By registering you agree to our
            <a href="<?= e(url('terms')) ?>" target="_blank" rel="noopener">terms</a> and
            <a href="<?= e(url('privacy')) ?>" target="_blank" rel="noopener">privacy policy</a>.</p>

        <?php if ($captchaWidget !== ''): ?>
            <div class="field<?= $err('captcha') !== '' ? ' field--error' : '' ?>">
                <?= $captchaWidget ?>
                <?php if ($err('captcha') !== ''): ?><p class="field__error"><?= e($err('captcha')) ?></p><?php endif ?>
            </div>
        <?php endif ?>

        <button class="form__submit" type="submit">Create account</button>
    </form>

    <p class="auth-alt">Already registered? <a href="<?= e(url('publish/login')) ?>">Sign in</a></p>
</div>
