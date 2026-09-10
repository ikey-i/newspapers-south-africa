<?php
/** @var string $q */
/** @var list<array<string,mixed>> $newspapers */
/** @var list<array<string,mixed>> $articles */
?>
<header class="page-head">
    <h1>Search</h1>
</header>

<?= view('partials/search-form', ['query' => $q, 'variant' => 'page']) ?>

<?php if ($q === ''): ?>
    <p class="empty">Type a newspaper name, a town, or words from a headline.</p>
<?php elseif ($newspapers === [] && $articles === []): ?>
    <p class="empty">Nothing found for &ldquo;<?= e($q) ?>&rdquo;.</p>
<?php else: ?>
    <?php if ($newspapers !== []): ?>
        <section class="search-group">
            <h2>Newspapers</h2>
            <div class="np-grid">
                <?php foreach ($newspapers as $paper): ?>
                    <?= view('partials/newspaper-card', ['paper' => $paper]) ?>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>
    <?php if ($articles !== []): ?>
        <section class="search-group">
            <h2>Stories</h2>
            <div class="art-grid art-grid--3">
                <?php foreach ($articles as $article): ?>
                    <?= view('partials/article-card', ['article' => $article]) ?>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>
<?php endif ?>
