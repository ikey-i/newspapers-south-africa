<?php
/** @var list<array<string,mixed>> $editions */
?>
<header class="page-head">
    <h1>PDF editions</h1>
    <p class="page-head__count">Full-edition PDFs from newsrooms across South Africa</p>
</header>

<?php if ($editions === []): ?>
    <p class="empty">No PDF editions have been published yet.</p>
<?php else: ?>
    <div class="edition-grid">
        <?php foreach ($editions as $ed): ?>
            <article class="ed-card">
                <a class="ed-card__cover" href="<?= e(url('paper/' . $ed['newspaper_slug'] . '/editions/' . $ed['id'])) ?>">
                    <?php if (!empty($ed['cover_path'])): ?>
                        <img src="<?= e(url($ed['cover_path'])) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="ed-card__placeholder" style="<?= e(brand_tile_style((string) $ed['newspaper_name'])) ?>">PDF</span>
                    <?php endif ?>
                </a>
                <div class="ed-card__body">
                    <a class="ed-card__paper" href="<?= e(url('paper/' . $ed['newspaper_slug'])) ?>"><?= e($ed['newspaper_name']) ?></a>
                    <h3><a href="<?= e(url('paper/' . $ed['newspaper_slug'] . '/editions/' . $ed['id'])) ?>"><?= e($ed['title']) ?></a></h3>
                    <?php if (!empty($ed['edition_date'])): ?>
                        <p class="ed-card__date"><?= e(date('j F Y', strtotime((string) $ed['edition_date']))) ?></p>
                    <?php endif ?>
                    <a class="btn btn--sm" href="<?= e(url('paper/' . $ed['newspaper_slug'] . '/editions/' . $ed['id'] . '/pdf')) ?>">Download PDF</a>
                </div>
            </article>
        <?php endforeach ?>
    </div>
<?php endif ?>
