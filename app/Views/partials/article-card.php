<?php
/**
 * @var array<string,mixed> $article   needs newspaper_slug, newspaper_name, slug, title
 * @var bool $showPaper
 */
$showPaper = $showPaper ?? true;
$href = url('paper/' . $article['newspaper_slug'] . '/article/' . $article['slug']);
$when = !empty($article['published_at']) ? date('j M Y', strtotime((string) $article['published_at'])) : '';
?>
<article class="art-card">
    <?php if (!empty($article['hero_image_path'])): ?>
        <a class="art-card__media" href="<?= e($href) ?>">
            <img src="<?= e(url($article['hero_image_path'])) ?>" alt="" loading="lazy">
        </a>
    <?php endif ?>
    <div class="art-card__body">
        <?php if (!empty($article['section'])): ?>
            <a class="art-card__section" href="<?= e(url('section/' . slugify((string) $article['section']))) ?>"><?= e($article['section']) ?></a>
        <?php endif ?>
        <h3 class="art-card__title"><a href="<?= e($href) ?>"><?= e($article['title']) ?></a></h3>
        <?php if (!empty($article['standfirst'])): ?>
            <p class="art-card__standfirst"><?= e(str_excerpt((string) $article['standfirst'], 140)) ?></p>
        <?php endif ?>
        <p class="art-card__meta">
            <?php if ($showPaper): ?>
                <a href="<?= e(url('paper/' . $article['newspaper_slug'])) ?>"><?= e($article['newspaper_name']) ?></a>
            <?php endif ?>
            <?php if ($when !== ''): ?><span><?= e($when) ?></span><?php endif ?>
        </p>
    </div>
</article>
