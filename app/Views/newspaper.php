<?php
/** @var array<string,mixed> $paper */
/** @var list<array<string,mixed>> $articles */
/** @var \App\Support\Paginator $paginator */
/** @var list<array<string,mixed>> $editions */
/** @var array<string,mixed>|null $latestPdf */
$typeLabel = \App\Support\Taxonomy::typeLabel((string) $paper['type']) ?? 'Newspaper';
$place = trim(implode(', ', array_filter([$paper['city'] ?? '', $paper['province'] ?? ''])));
$socials = array_filter([
    'Website'  => $paper['website'] ?? '',
    'Facebook' => $paper['facebook'] ?? '',
    'X'        => $paper['twitter'] ?? '',
    'Instagram' => $paper['instagram'] ?? '',
]);
?>
<article class="paper-head">
    <?= view('partials/masthead', ['paper' => $paper, 'size' => 'lg']) ?>
    <div class="paper-head__body">
        <h1><?= e($paper['name']) ?></h1>
        <?php if (!empty($paper['tagline'])): ?><p class="paper-head__tagline"><?= e($paper['tagline']) ?></p><?php endif ?>
        <p class="paper-head__meta">
            <span class="tag"><?= e($typeLabel) ?></span>
            <?php if ($place !== ''): ?><span><?= e($place) ?></span><?php endif ?>
            <?php if (!empty($paper['languages'])): ?><span><?= e($paper['languages']) ?></span><?php endif ?>
            <?php if (!empty($paper['established_year'])): ?><span>Est. <?= (int) $paper['established_year'] ?></span><?php endif ?>
        </p>
        <?php if ($socials !== []): ?>
        <p class="paper-head__links">
            <?php foreach ($socials as $label => $href): ?>
                <a href="<?= e($href) ?>" rel="nofollow noopener" target="_blank"><?= e($label) ?></a>
            <?php endforeach ?>
        </p>
        <?php endif ?>
    </div>
</article>

<?php if (!empty($paper['about'])): ?>
    <p class="paper-about"><?= e($paper['about']) ?></p>
<?php endif ?>

<?php if ($latestPdf !== null): ?>
<aside class="pdf-callout">
    <div>
        <strong>Latest edition:</strong> <?= e($latestPdf['title']) ?>
        <?php if (!empty($latestPdf['edition_date'])): ?>
            <span>· <?= e(date('j F Y', strtotime((string) $latestPdf['edition_date']))) ?></span>
        <?php endif ?>
    </div>
    <a class="btn btn--primary" href="<?= e(url('paper/' . $paper['slug'] . '/editions/' . $latestPdf['id'] . '/pdf')) ?>">Download PDF</a>
</aside>
<?php endif ?>

<div class="paper-layout">
    <section class="paper-layout__main">
        <h2 class="section-title">Latest stories</h2>
        <?php if ($articles === []): ?>
            <p class="empty">This newsroom has not published any stories yet.</p>
        <?php else: ?>
            <div class="art-grid art-grid--2">
                <?php foreach ($articles as $article): ?>
                    <?= view('partials/article-card', ['article' => $article, 'showPaper' => false]) ?>
                <?php endforeach ?>
            </div>
            <?= view('partials/pagination', ['paginator' => $paginator]) ?>
        <?php endif ?>
    </section>

    <aside class="paper-layout__side">
        <h2 class="section-title">Editions &amp; back issues</h2>
        <?php if ($editions === []): ?>
            <p class="empty">No editions published yet.</p>
        <?php else: ?>
            <ul class="edition-list">
                <?php foreach (array_slice($editions, 0, 8) as $ed): ?>
                    <li>
                        <a href="<?= e(url('paper/' . $paper['slug'] . '/editions/' . $ed['id'])) ?>"><?= e($ed['title']) ?></a>
                        <?php if (!empty($ed['edition_date'])): ?>
                            <span><?= e(date('j M Y', strtotime((string) $ed['edition_date']))) ?></span>
                        <?php endif ?>
                        <?php if (!empty($ed['pdf_path'])): ?><span class="pill">PDF</span><?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
            <?php if (count($editions) > 8): ?>
                <p><a href="<?= e(url('paper/' . $paper['slug'] . '/editions')) ?>">All <?= count($editions) ?> editions &rarr;</a></p>
            <?php endif ?>
        <?php endif ?>
        <?php $sidebar = ad_slot('sidebar'); if ($sidebar !== ''): ?>
            <div class="side-ad"><?= $sidebar ?></div>
        <?php endif ?>
    </aside>
</div>
