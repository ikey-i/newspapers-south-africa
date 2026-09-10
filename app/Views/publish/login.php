<?php
/** @var string $error */
/** @var string $email */
/** @var string $csrfField */
?>
<div class="auth-wrap">
    <h1>Publisher sign in</h1>

    <?php if ($error !== ''): ?>
        <p class="notice notice--warn"><?= e($error) ?></p>
    <?php endif ?>
    <?php if ($flash = flash_pull('publish_success')): ?>
        <p class="notice notice--ok"><?= e(is_string($flash) ? $flash : 'Done.') ?></p>
    <?php endif ?>

    <form class="form" method="post" action="<?= e(url('publish/login')) ?>">
        <?= $csrfField ?>
        <div class="field">
            <label class="field__label" for="f-email">Email address</label>
            <input class="field__control" type="email" id="f-email" name="email" value="<?= e($email) ?>" autocomplete="username" autofocus required>
        </div>
        <div class="field">
            <label class="field__label" for="f-password">Password</label>
            <input class="field__control" type="password" id="f-password" name="password" autocomplete="current-password" required>
        </div>
        <button class="form__submit" type="submit">Sign in</button>
    </form>

    <p class="auth-alt">
        <a href="<?= e(url('publish/forgot')) ?>">Forgot your password?</a><br>
        New here? <a href="<?= e(url('publish/register')) ?>">Register your newspaper</a>
    </p>
</div>
