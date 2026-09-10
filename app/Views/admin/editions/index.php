<?php
/** @var list<array<string,mixed>> $editions */
/** @var \App\Support\Paginator $paginator */
/** @var int $total */
/** @var array{q:string,newspaper:int} $filters */
/** @var list<array<string,mixed>> $newspapers */
/** @var string $csrfField */
?>
<form class="admin-filter" method="get" action="<?= e(url('admin/editions')) ?>">
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Edition title…">
    <select name="newspaper">
        <option value="">Any newspaper</option>
        <?php foreach ($newspapers as $n): ?>
            <option value="<?= (int) $n['id'] ?>"<?= $filters['newspaper'] === (int) $n['id'] ? ' selected' : '' ?>><?= e($n['name']) ?></option>
        <?php endforeach ?>
    </select>
    <button class="btn" type="submit">Filter</button>
</form>

<p class="admin-count"><?= number_format($total) ?> edition<?= $total === 1 ? '' : 's' ?></p>

<?php if ($editions === []): ?>
    <p class="empty">No editions match.</p>
<?php else: ?>
<div class="table-wrap">
<table class="admin-table">
    <thead><tr><th>Edition</th><th>Newspaper</th><th>Date</th><th>PDF</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($editions as $ed): ?>
        <tr>
            <td><?= e($ed['title']) ?></td>
            <td><a href="<?= e(url('admin/newspapers/' . $ed['newspaper_id'] . '/edit')) ?>"><?= e($ed['newspaper_name']) ?></a></td>
            <td><?= e(!empty($ed['edition_date']) ? date('j M Y', strtotime((string) $ed['edition_date'])) : '—') ?></td>
            <td><?= !empty($ed['pdf_path']) ? e(number_format((int) $ed['pdf_size'] / 1048576, 1)) . ' MB' : '—' ?></td>
            <td><span class="pill <?= (int) $ed['is_published'] === 1 ? 'pill--ok' : 'pill--warn' ?>"><?= (int) $ed['is_published'] === 1 ? 'published' : 'draft' ?></span></td>
            <td class="admin-table__actions">
                <a href="<?= e(url('paper/' . $ed['newspaper_slug'] . '/editions/' . $ed['id'])) ?>" target="_blank" rel="noopener">View</a>
                <?php if ((int) $ed['is_published'] === 1): ?>
                <form class="inline-form" method="post" action="<?= e(url('admin/editions/' . $ed['id'] . '/unpublish')) ?>">
                    <?= $csrfField ?><button class="linkbtn" type="submit">Hide</button>
                </form>
                <?php endif ?>
                <form class="inline-form" method="post" action="<?= e(url('admin/editions/' . $ed['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this edition?');">
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
