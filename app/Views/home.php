<?php
/** @var list<array<string,mixed>> $featured */
/** @var list<array<string,mixed>> $latest */
/** @var list<array{province:string,c:int}> $provinces */
?>
<section class="hero">
    <div class="hero__inner">
        <h1 class="hero__title">Every South African newspaper, in one place</h1>
        <p class="hero__lead">Read community and local papers from all nine provinces — their latest stories, full-edition PDFs and back issues.</p>
        <?= view('partials/search-form', ['query' => '', 'variant' => 'hero']) ?>
        <p class="hero__links">
            <a href="<?= e(url('newspapers')) ?>">Browse all newspapers</a> ·
            <a href="<?= e(url('list-your-newspaper')) ?>">List your newspaper</a>
        </p>
    </div>
</section>

<?php if ($featured !== []): ?>
<section class="home-section">
    <div class="home-section__head">
        <h2>Featured newspapers</h2>
        <a href="<?= e(url('newspapers')) ?>">All newspapers &rarr;</a>
    </div>
    <div class="np-grid">
        <?php foreach ($featured as $paper): ?>
            <?= view('partials/newspaper-card', ['paper' => $paper]) ?>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>

<?php if ($latest !== []): ?>
<section class="home-section">
    <div class="home-section__head">
        <h2>Latest stories</h2>
        <a href="<?= e(url('section/news')) ?>">Latest news &rarr;</a>
    </div>
    <div class="art-grid">
        <?php foreach ($latest as $i => $article): ?>
            <?= view('partials/article-card', ['article' => $article]) ?>
            <?php if ($i === 3): $infeed = ad_slot('infeed'); if ($infeed !== ''): ?>
                <div class="art-grid__ad"><?= $infeed ?></div>
            <?php endif; endif ?>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>

<?php if ($provinces !== []): ?>
<section class="home-section">
    <div class="home-section__head"><h2>Browse by province</h2></div>
    <ul class="chip-list">
        <?php foreach ($provinces as $row): ?>
            <li><a class="chip" href="<?= e(url('province/' . slugify($row['province']))) ?>">
                <?= e($row['province']) ?> <span class="chip__count"><?= (int) $row['c'] ?></span>
            </a></li>
        <?php endforeach ?>
    </ul>
</section>
<?php endif ?>

<?php if ($featured === [] && $latest === []): ?>
<section class="prose">
    <p>No newspapers have been published yet. If you run a newsroom,
        <a href="<?= e(url('list-your-newspaper')) ?>">list your newspaper</a> to get started.</p>
</section>
<?php endif ?>
