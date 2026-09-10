<?php
/** @var array<string,mixed> $user */
/** @var list<array<string,mixed>> $articles */
/** @var \App\Support\Paginator $paginator */
/** @var array{status:string,q:string} $filters */
/** @var int $total */
$token = \App\Support\Csrf::field();
$tabs = ['' => 'All', 'published' => 'Published', 'draft' => 'Drafts'];
?>
<div class="admin-toolbar">
    <div class="admin-tabs">
        <?php foreach ($tabs as $k => $label): ?>
            <a href="<?= e(url('publish/articles' . ($k !== '' ? '?status=' . $k : ''))) ?>"
               <?= $filters['status'] === $k ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach ?>
    </div>
    <a class="btn btn--primary" href="<?= e(url('publish/articles/new')) ?>">Write a story</a>
</div>

<form class="admin-filter" method="get" action="<?= e(url('publish/articles')) ?>">
    <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif ?>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search headlines…">
    <button class="btn" type="submit">Search</button>
</form>

<p class="admin-count"><?= number_format($total) ?> stor<?= $total === 1 ? 'y' : 'ies' ?></p>

<?php if ($articles === []): ?>
    <p class="empty">No stories yet. <a href="<?= e(url('publish/articles/new')) ?>">Write your first one.</a></p>
<?php else: ?>
<div class="table-wrap">
<table class="admin-table">
    <thead><tr><th>Headline</th><th>Section</th><th>Status</th><th>Date</th><th>Views</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($articles as $a): ?>
        <tr>
            <td>
                <a href="<?= e(url('publish/articles/' . $a['id'] . '/edit')) ?>"><?= e($a['title']) ?></a>
                <?php if (!empty($a['edition_title'])): ?><span class="admin-table__slug">in “<?= e($a['edition_title']) ?>”</span><?php endif ?>
            </td>
            <td><?= e($a['section'] ?: '—') ?></td>
            <td><span class="pill <?= $a['status'] === 'published' ? 'pill--ok' : 'pill--warn' ?>"><?= e($a['status']) ?></span></td>
            <td><?= e(!empty($a['published_at']) ? date('j M Y', strtotime((string) $a['published_at'])) : 'draft') ?></td>
            <td><?= number_format((int) $a['views']) ?></td>
            <td class="admin-table__actions">
                <?php if ($a['status'] === 'published'): ?>
                    <a href="<?= e(url('paper/' . $user['newspaper_slug'] . '/article/' . $a['slug'])) ?>" target="_blank" rel="noopener">View</a>
                    <form class="inline-form" method="post" action="<?= e(url('publish/articles/' . $a['id'] . '/unpublish')) ?>">
                        <?= $token ?><button class="linkbtn" type="submit">Unpublish</button>
                    </form>
                <?php else: ?>
                    <form class="inline-form" method="post" action="<?= e(url('publish/articles/' . $a['id'] . '/publish')) ?>">
                        <?= $token ?><button class="linkbtn" type="submit">Publish</button>
                    </form>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</div>
<?= view('partials/pagination', ['paginator' => $paginator]) ?>
<?php endif ?>
