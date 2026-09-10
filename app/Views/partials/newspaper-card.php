<?php
/** @var array<string,mixed> $paper */
$typeLabel = \App\Support\Taxonomy::typeLabel((string) ($paper['type'] ?? '')) ?? 'Newspaper';
$place = trim(implode(', ', array_filter([$paper['city'] ?? '', $paper['province'] ?? ''])));
?>
<article class="np-card">
    <a class="np-card__link" href="<?= e(url('paper/' . $paper['slug'])) ?>">
        <?= view('partials/masthead', ['paper' => $paper, 'size' => 'md']) ?>
        <span class="np-card__body">
            <span class="np-card__name"><?= e($paper['name']) ?></span>
            <?php if (!empty($paper['tagline'])): ?>
                <span class="np-card__tagline"><?= e($paper['tagline']) ?></span>
            <?php endif ?>
            <span class="np-card__meta">
                <span class="tag"><?= e($typeLabel) ?></span>
                <?php if ($place !== ''): ?><span class="np-card__place"><?= e($place) ?></span><?php endif ?>
            </span>
        </span>
    </a>
</article>
