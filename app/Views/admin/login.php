<?php
/** @var string $title */
/** @var string $error */
/** @var string $csrfField */
/** @var string $username */
?><!doctype html>
<html lang="en-ZA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?></title>
    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-auth">
    <main class="admin-auth__card">
        <h1><?= e(config('app.name')) ?></h1>
        <p class="admin-auth__sub">Admin sign in</p>

        <?php if ($error !== ''): ?>
            <p class="notice notice--warn"><?= e($error) ?></p>
        <?php endif ?>

        <form method="post" action="<?= e(url('admin/login')) ?>">
            <?= $csrfField ?>
            <div class="field">
                <label class="field__label" for="username">Username</label>
                <input class="field__control" type="text" id="username" name="username"
                       value="<?= e($username) ?>" autocomplete="username" autofocus required>
            </div>
            <div class="field">
                <label class="field__label" for="password">Password</label>
                <input class="field__control" type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>
            <button class="form__submit" type="submit">Sign in</button>
        </form>

        <p class="admin-auth__back"><a href="<?= e(url()) ?>">&larr; Back to the site</a></p>
    </main>
</body>
</html>
