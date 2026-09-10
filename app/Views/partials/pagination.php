<?php
/** @var \App\Support\Paginator $paginator */
if (!$paginator->hasPages()) {
    return;
}
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($paginator->hasPrevious()): ?>
        <a class="pagination__step" rel="prev" href="<?= e($paginator->pageUrl($paginator->currentPage - 1)) ?>">&larr; Previous</a>
    <?php endif ?>

    <ul class="pagination__pages">
        <?php foreach ($paginator->window() as $page): ?>
            <li>
                <?php if ($page === $paginator->currentPage): ?>
                    <span class="pagination__page is-current" aria-current="page"><?= e((string) $page) ?></span>
                <?php else: ?>
                    <a class="pagination__page" href="<?= e($paginator->pageUrl($page)) ?>"><?= e((string) $page) ?></a>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>

    <?php if ($paginator->hasNext()): ?>
        <a class="pagination__step" rel="next" href="<?= e($paginator->pageUrl($paginator->currentPage + 1)) ?>">Next &rarr;</a>
    <?php endif ?>
</nav>
