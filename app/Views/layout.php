<?php
/** @var string $content */
/** @var string $title */
/** @var string $description */
$title = $title ?? config('app.name');
$description = $description ?? '';
$siteName = config('app.name');
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$bp = base_path();
?><!doctype html>
<html lang="en-ZA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <?php if ($description !== ''): ?>
    <meta name="description" content="<?= e($description) ?>">
    <?php endif ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <?php if ($description !== ''): ?>
    <meta property="og:description" content="<?= e($description) ?>">
    <?php endif ?>
    <meta name="theme-color" content="#8b1a1a">
    <link rel="canonical" href="<?= e(base_url(ltrim((string) $reqPath, '/'))) ?>">
    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <?= \App\Support\Ads::headScript() ?>
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <div class="container site-header__inner">
            <a class="site-header__brand" href="<?= e(url()) ?>">
                <span class="site-header__mark" aria-hidden="true">SA</span>
                <span class="site-header__name"><?= e($siteName) ?></span>
            </a>
            <button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <nav class="site-nav" id="site-nav" aria-label="Primary">
                <a href="<?= e(url('newspapers')) ?>">All newspapers</a>
                <a href="<?= e(url('section/news')) ?>">Latest news</a>
                <a href="<?= e(url('editions')) ?>">PDF editions</a>
                <a href="<?= e(url('list-your-newspaper')) ?>">List your newspaper</a>
                <a class="site-nav__cta" href="<?= e(url('publish/login')) ?>">Publisher login</a>
            </nav>
        </div>
    </header>

    <?php $hideSubSearch = in_array($reqPath, ['/', $bp, $bp . '/', $bp . '/search'], true); ?>
    <?php if (!$hideSubSearch): ?>
    <div class="site-subbar">
        <div class="container">
            <?= view('partials/search-form', ['query' => (string) ($_GET['q'] ?? ''), 'variant' => 'bar']) ?>
        </div>
    </div>
    <?php endif ?>

    <?php $leaderboard = ad_slot('leaderboard'); ?>
    <?php if ($leaderboard !== ''): ?>
    <div class="container ad-leaderboard"><?= $leaderboard ?></div>
    <?php endif ?>

    <main id="main" class="container site-main">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <nav class="site-footer__nav" aria-label="Footer">
                <a href="<?= e(url('newspapers')) ?>">All newspapers</a>
                <a href="<?= e(url('section/news')) ?>">Latest news</a>
                <a href="<?= e(url('editions')) ?>">PDF editions</a>
                <a href="<?= e(url('list-your-newspaper')) ?>">List your newspaper</a>
                <a href="<?= e(url('publish/login')) ?>">Publisher login</a>
                <a href="<?= e(url('privacy')) ?>">Privacy</a>
                <a href="<?= e(url('terms')) ?>">Terms</a>
            </nav>
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. An independent directory of South African community and local newspapers.</p>
        </div>
    </footer>

    <?php if (\App\Support\Ads::enabled()): ?>
    <div class="cookie-notice" id="cookie-notice" hidden>
        <p>We use cookies for analytics and advertising.
            <a href="<?= e(url('privacy')) ?>">Learn more</a>.</p>
        <button type="button" id="cookie-notice-ok">Got it</button>
    </div>
    <?php endif ?>

    <script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
    <?= $scripts ?? '' ?>
</body>
</html>
