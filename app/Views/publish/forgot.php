<?php
/** @var string $csrfField */
/** @var string $guardField */
?>
<div class="auth-wrap">
    <h1>Reset your password</h1>
    <p>Enter your account email and we'll send a link to choose a new password.</p>

    <form class="form" method="post" action="<?= e(url('publish/forgot')) ?>">
        <?= $csrfField ?><?= $guardField ?>
        <div class="field">
            <label class="field__label" for="f-email">Email address</label>
            <input class="field__control" type="email" id="f-email" name="email" autocomplete="username" autofocus required>
        </div>
        <button class="form__submit" type="submit">Send reset link</button>
    </form>

    <p class="auth-alt"><a href="<?= e(url('publish/login')) ?>">Back to sign in</a></p>
</div>
