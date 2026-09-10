<?php
/** @var array<string,mixed> $article */
/** @var list<array<string,mixed>> $more */
$when = !empty($article['published_at']) ? date('j F Y', strtotime((string) $article['published_at'])) : '';
$articleAd = ad_slot('article');
?>
<nav class="crumbs">
    <a href="<?= e(url('paper/' . $article['newspaper_slug'])) ?>"><?= e($article['newspaper_name']) ?></a>
    <?php if (!empty($article['section'])): ?>
        <span>/</span>
        <a href="<?= e(url('section/' . slugify((string) $article['section']))) ?>"><?= e($article['section']) ?></a>
    <?php endif ?>
</nav>

<article class="story">
    <header class="story__head">
        <h1><?= e($article['title']) ?></h1>
        <?php if (!empty($article['standfirst'])): ?>
            <p class="story__standfirst"><?= e($article['standfirst']) ?></p>
        <?php endif ?>
        <p class="story__byline">
            <?php if (!empty($article['author_name'])): ?><span>By <?= e($article['author_name']) ?></span><?php endif ?>
            <?php if ($when !== ''): ?><span><?= e($when) ?></span><?php endif ?>
            <a href="<?= e(url('paper/' . $article['newspaper_slug'])) ?>"><?= e($article['newspaper_name']) ?></a>
        </p>
    </header>

    <?php if (!empty($article['hero_image_path'])): ?>
        <figure class="story__hero">
            <img src="<?= e(url($article['hero_image_path'])) ?>" alt="">
        </figure>
    <?php endif ?>

    <div class="story__body prose">
        <?= $article['body_html'] ?? '' ?>
    </div>

    <?php if ($articleAd !== ''): ?>
        <div class="story__ad"><?= $articleAd ?></div>
    <?php endif ?>
</article>

<?php if ($more !== []): ?>
<section class="home-section">
    <div class="home-section__head"><h2>More from <?= e($article['newspaper_name']) ?></h2></div>
    <div class="art-grid art-grid--3">
        <?php foreach ($more as $m): ?>
            <?= view('partials/article-card', ['article' => $m, 'showPaper' => false]) ?>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>
