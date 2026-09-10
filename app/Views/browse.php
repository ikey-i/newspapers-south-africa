<?php
/** @var list<array<string,mixed>> $newspapers */
/** @var \App\Support\Paginator $paginator */
/** @var array{q:string,province:string,type:string} $filters */
/** @var int $total */
/** @var string $heading */
$provinces = \App\Support\Taxonomy::PROVINCES;
$types = \App\Support\Taxonomy::TYPES;
?>
<header class="page-head">
    <h1><?= e($heading) ?></h1>
    <p class="page-head__count"><?= number_format($total) ?> newspaper<?= $total === 1 ? '' : 's' ?></p>
</header>

<form class="filter-bar" method="get" action="<?= e(url('newspapers')) ?>">
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name or town…" maxlength="100">
    <select name="province">
        <option value="">Any province</option>
        <?php foreach ($provinces as $p): ?>
            <option value="<?= e(slugify($p)) ?>"<?= $filters['province'] === $p ? ' selected' : '' ?>><?= e($p) ?></option>
        <?php endforeach ?>
    </select>
    <select name="type">
        <option value="">Any type</option>
        <?php foreach ($types as $k => $label): ?>
            <option value="<?= e($k) ?>"<?= $filters['type'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach ?>
    </select>
    <button type="submit">Filter</button>
</form>

<?php if ($newspapers === []): ?>
    <p class="empty">No newspapers match. <a href="<?= e(url('newspapers')) ?>">Clear filters</a></p>
<?php else: ?>
    <div class="np-grid">
        <?php foreach ($newspapers as $paper): ?>
            <?= view('partials/newspaper-card', ['paper' => $paper]) ?>
        <?php endforeach ?>
    </div>
    <?= view('partials/pagination', ['paginator' => $paginator]) ?>
<?php endif ?>
