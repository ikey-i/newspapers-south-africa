<?php
/** @var string $content */
/** @var string $title */
/** @var string $heading */
/** @var array<string,mixed>|null $user */
/** @var mixed $success */
/** @var mixed $notice */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$approved = ($user['newspaper_status'] ?? '') === 'active' && !empty($user['email_verified_at']);
$nav = ['publish' => 'Dashboard'];
if ($approved) {
    $nav += [
        'publish/articles' => 'Articles',
        'publish/editions' => 'Editions',
        'publish/newspaper' => 'Newspaper',
    ];
}
$isActive = static function (string $key) use ($path): bool {
    $target = base_path() . '/' . $key;
    return $key === 'publish' ? $path === $target : str_starts_with($path, $target);
};
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
    <?= $head ?? '' ?>
</head>
<body class="admin">
    <header class="admin-bar">
        <div class="admin-bar__inner">
            <a class="admin-bar__brand" href="<?= e(url('publish')) ?>"><?= e($user['newspaper_name'] ?? config('app.name')) ?></a>
            <nav class="admin-nav">
                <?php foreach ($nav as $key => $label): ?>
                    <a href="<?= e(url($key)) ?>"<?= $isActive($key) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endforeach ?>
            </nav>
            <div class="admin-bar__user">
                <?php if ($approved): ?>
                    <a href="<?= e(url('paper/' . $user['newspaper_slug'])) ?>" target="_blank" rel="noopener">View newspaper ↗</a>
                <?php endif ?>
                <?php if ($user !== null): ?>
                    <span><?= e($user['name']) ?></span>
                    <form method="post" action="<?= e(url('publish/logout')) ?>">
                        <?= \App\Support\Csrf::field() ?>
                        <button type="submit" class="admin-bar__logout">Sign out</button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="admin-main__inner">
            <h1 class="admin-heading"><?= e($heading) ?></h1>

            <?php if (!empty($success)): ?>
                <p class="notice notice--ok"><?= e(is_string($success) ? $success : 'Saved.') ?></p>
            <?php endif ?>
            <?php if (!empty($notice)): ?>
                <p class="notice notice--info"><?= e(is_string($notice) ? $notice : '') ?></p>
            <?php endif ?>

            <?= $content ?>
        </div>
    </main>

    <?= $scripts ?? '' ?>
</body>
</html>
