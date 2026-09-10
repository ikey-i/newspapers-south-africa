<?php
/** @var array<string,mixed> $paper */
/** @var list<array<string,mixed>> $editions */
?>
<nav class="crumbs">
    <a href="<?= e(url('paper/' . $paper['slug'])) ?>"><?= e($paper['name']) ?></a>
    <span>/</span> <span>Back issues</span>
</nav>

<header class="page-head">
    <h1><?= e($paper['name']) ?> — back issues</h1>
    <p class="page-head__count"><?= count($editions) ?> edition<?= count($editions) === 1 ? '' : 's' ?></p>
</header>

<?php if ($editions === []): ?>
    <p class="empty">No editions published yet.</p>
<?php else: ?>
    <div class="edition-grid">
        <?php foreach ($editions as $ed): ?>
            <article class="ed-card">
                <a class="ed-card__cover" href="<?= e(url('paper/' . $paper['slug'] . '/editions/' . $ed['id'])) ?>">
                    <?php if (!empty($ed['cover_path'])): ?>
                        <img src="<?= e(url($ed['cover_path'])) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="ed-card__placeholder" style="<?= e(brand_tile_style((string) $paper['name'])) ?>">PDF</span>
                    <?php endif ?>
                </a>
                <div class="ed-card__body">
                    <h3><a href="<?= e(url('paper/' . $paper['slug'] . '/editions/' . $ed['id'])) ?>"><?= e($ed['title']) ?></a></h3>
                    <?php if (!empty($ed['edition_date'])): ?>
                        <p class="ed-card__date"><?= e(date('j F Y', strtotime((string) $ed['edition_date']))) ?></p>
                    <?php endif ?>
                    <?php if (!empty($ed['pdf_path'])): ?>
                        <a class="btn btn--sm" href="<?= e(url('paper/' . $paper['slug'] . '/editions/' . $ed['id'] . '/pdf')) ?>">Download PDF</a>
                    <?php endif ?>
                </div>
            </article>
        <?php endforeach ?>
    </div>
<?php endif ?>
