<?php
/** @var list<array<string,mixed>> $articles */
/** @var \App\Support\Paginator $paginator */
/** @var int $total */
/** @var array{q:string,status:string,newspaper:int} $filters */
/** @var list<array<string,mixed>> $newspapers */
/** @var string $csrfField */
?>
<form class="admin-filter" method="get" action="<?= e(url('admin/articles')) ?>">
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Headline…">
    <select name="status">
        <option value="">Any status</option>
        <option value="published"<?= $filters['status'] === 'published' ? ' selected' : '' ?>>Published</option>
        <option value="draft"<?= $filters['status'] === 'draft' ? ' selected' : '' ?>>Draft</option>
    </select>
    <select name="newspaper">
        <option value="">Any newspaper</option>
        <?php foreach ($newspapers as $n): ?>
            <option value="<?= (int) $n['id'] ?>"<?= $filters['newspaper'] === (int) $n['id'] ? ' selected' : '' ?>><?= e($n['name']) ?></option>
        <?php endforeach ?>
    </select>
    <button class="btn" type="submit">Filter</button>
</form>

<p class="admin-count"><?= number_format($total) ?> stor<?= $total === 1 ? 'y' : 'ies' ?></p>

<?php if ($articles === []): ?>
    <p class="empty">No stories match.</p>
<?php else: ?>
<div class="table-wrap">
<table class="admin-table">
    <thead><tr><th>Headline</th><th>Newspaper</th><th>Status</th><th>Date</th><th>Views</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($articles as $a): ?>
        <tr>
            <td><?= e($a['title']) ?></td>
            <td><a href="<?= e(url('admin/newspapers/' . $a['newspaper_id'] . '/edit')) ?>"><?= e($a['newspaper_name']) ?></a></td>
            <td><span class="pill <?= $a['status'] === 'published' ? 'pill--ok' : 'pill--warn' ?>"><?= e($a['status']) ?></span></td>
            <td><?= e(!empty($a['published_at']) ? date('j M Y', strtotime((string) $a['published_at'])) : '—') ?></td>
            <td><?= number_format((int) $a['views']) ?></td>
            <td class="admin-table__actions">
                <a href="<?= e(url('paper/' . $a['newspaper_slug'] . '/article/' . $a['slug'])) ?>" target="_blank" rel="noopener">View</a>
                <?php if ($a['status'] === 'published'): ?>
                <form class="inline-form" method="post" action="<?= e(url('admin/articles/' . $a['id'] . '/unpublish')) ?>">
                    <?= $csrfField ?><button class="linkbtn" type="submit">Unpublish</button>
                </form>
                <?php endif ?>
                <form class="inline-form" method="post" action="<?= e(url('admin/articles/' . $a['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this story?');">
                    <?= $csrfField ?><button class="linkbtn" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</div>
<?= view('partials/pagination', ['paginator' => $paginator]) ?>
<?php endif ?>
