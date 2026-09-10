<?php
/** @var array<string,mixed> $edition */
/** @var list<array<string,mixed>> $articles */
$when = !empty($edition['edition_date']) ? date('j F Y', strtotime((string) $edition['edition_date'])) : '';
$hasPdf = !empty($edition['pdf_path']);
$pdfUrl = url('paper/' . $edition['newspaper_slug'] . '/editions/' . $edition['id'] . '/pdf');
?>
<nav class="crumbs">
    <a href="<?= e(url('paper/' . $edition['newspaper_slug'])) ?>"><?= e($edition['newspaper_name']) ?></a>
    <span>/</span>
    <a href="<?= e(url('paper/' . $edition['newspaper_slug'] . '/editions')) ?>">Back issues</a>
</nav>

<div class="edition-view">
    <div class="edition-view__cover">
        <?php if (!empty($edition['cover_path'])): ?>
            <img src="<?= e(url($edition['cover_path'])) ?>" alt="Cover of <?= e($edition['title']) ?>">
        <?php else: ?>
            <span class="ed-card__placeholder" style="<?= e(brand_tile_style((string) $edition['newspaper_name'])) ?>">PDF</span>
        <?php endif ?>
    </div>
    <div class="edition-view__body">
        <h1><?= e($edition['title']) ?></h1>
        <?php if ($when !== ''): ?><p class="edition-view__date"><?= e($when) ?></p><?php endif ?>
        <?php if (!empty($edition['description'])): ?>
            <p><?= e($edition['description']) ?></p>
        <?php endif ?>
        <?php if ($hasPdf): ?>
            <p class="edition-view__actions">
                <a class="btn btn--primary" href="<?= e($pdfUrl) ?>">Download PDF<?php if (!empty($edition['pdf_size'])): ?>
                    <span>(<?= e(number_format((int) $edition['pdf_size'] / 1048576, 1)) ?> MB)</span><?php endif ?></a>
                <a class="btn" href="<?= e($pdfUrl) ?>" target="_blank" rel="noopener">Open in browser</a>
            </p>
        <?php else: ?>
            <p class="empty">No PDF is attached to this edition.</p>
        <?php endif ?>
    </div>
</div>

<?php if ($hasPdf): ?>
    <object class="edition-view__embed" data="<?= e($pdfUrl) ?>#view=FitH" type="application/pdf">
        <p>Your browser can't display the PDF inline. <a href="<?= e($pdfUrl) ?>">Download it instead</a>.</p>
    </object>
<?php endif ?>

<?php if ($articles !== []): ?>
<section class="home-section">
    <div class="home-section__head"><h2>In this edition</h2></div>
    <div class="art-grid art-grid--3">
        <?php foreach ($articles as $a): ?>
            <?= view('partials/article-card', ['article' => $a + ['newspaper_name' => $edition['newspaper_name']], 'showPaper' => false]) ?>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>
