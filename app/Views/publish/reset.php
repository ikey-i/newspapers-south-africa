<?php
/** @var string $token */
/** @var array<string,string> $errors */
/** @var string $csrfField */
$err = static fn (string $k): string => $errors[$k] ?? '';
?>
<div class="auth-wrap">
    <h1>Choose a new password</h1>

    <form class="form" method="post" action="<?= e(url('publish/reset')) ?>" novalidate>
        <?= $csrfField ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <?= view('partials/field', ['name' => 'password', 'label' => 'New password', 'type' => 'password', 'required' => true, 'hint' => 'At least 10 characters.', 'error' => $err('password')]) ?>
        <?= view('partials/field', ['name' => 'password_confirm', 'label' => 'Confirm password', 'type' => 'password', 'required' => true, 'error' => $err('password_confirm')]) ?>
        <button class="form__submit" type="submit">Change password</button>
    </form>
</div>
