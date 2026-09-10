<?php
/** @var string $section */
/** @var list<array<string,mixed>> $articles */
/** @var \App\Support\Paginator $paginator */
/** @var int $total */
$sections = \App\Support\Taxonomy::SECTIONS;
?>
<header class="page-head">
    <h1><?= e($section) ?></h1>
    <p class="page-head__count"><?= number_format($total) ?> stor<?= $total === 1 ? 'y' : 'ies' ?></p>
</header>

<ul class="chip-list chip-list--tabs">
    <?php foreach ($sections as $s): ?>
        <li><a class="chip<?= $s === $section ? ' is-active' : '' ?>" href="<?= e(url('section/' . slugify($s))) ?>"><?= e($s) ?></a></li>
    <?php endforeach ?>
</ul>

<?php if ($articles === []): ?>
    <p class="empty">No stories in this section yet.</p>
<?php else: ?>
    <div class="art-grid">
        <?php foreach ($articles as $article): ?>
            <?= view('partials/article-card', ['article' => $article]) ?>
        <?php endforeach ?>
    </div>
    <?= view('partials/pagination', ['paginator' => $paginator]) ?>
<?php endif ?>
