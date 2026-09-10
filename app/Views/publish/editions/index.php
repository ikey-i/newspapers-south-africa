<?php
/** @var array<string,mixed> $user */
/** @var list<array<string,mixed>> $editions */
/** @var \App\Support\Paginator $paginator */
/** @var int $total */
$token = \App\Support\Csrf::field();
?>
<div class="admin-toolbar">
    <p class="admin-count"><?= number_format($total) ?> edition<?= $total === 1 ? '' : 's' ?></p>
    <a class="btn btn--primary" href="<?= e(url('publish/editions/new')) ?>">Add an edition</a>
</div>

<?php if ($editions === []): ?>
    <p class="empty">No editions yet. <a href="<?= e(url('publish/editions/new')) ?>">Add your first PDF edition.</a></p>
<?php else: ?>
<div class="table-wrap">
<table class="admin-table">
    <thead><tr><th>Edition</th><th>Date</th><th>PDF</th><th>Stories</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($editions as $ed): ?>
        <tr>
            <td><a href="<?= e(url('publish/editions/' . $ed['id'] . '/edit')) ?>"><?= e($ed['title']) ?></a></td>
            <td><?= e(!empty($ed['edition_date']) ? date('j M Y', strtotime((string) $ed['edition_date'])) : '—') ?></td>
            <td><?php if (!empty($ed['pdf_path'])): ?><span class="pill pill--ok"><?= e(number_format((int) $ed['pdf_size'] / 1048576, 1)) ?> MB</span><?php else: ?><span class="pill">none</span><?php endif ?></td>
            <td><?= (int) $ed['article_count'] ?></td>
            <td><span class="pill <?= (int) $ed['is_published'] === 1 ? 'pill--ok' : 'pill--warn' ?>"><?= (int) $ed['is_published'] === 1 ? 'published' : 'draft' ?></span></td>
            <td class="admin-table__actions">
                <?php if ((int) $ed['is_published'] === 1): ?>
                    <a href="<?= e(url('paper/' . $user['newspaper_slug'] . '/editions/' . $ed['id'])) ?>" target="_blank" rel="noopener">View</a>
                    <form class="inline-form" method="post" action="<?= e(url('publish/editions/' . $ed['id'] . '/unpublish')) ?>">
                        <?= $token ?><button class="linkbtn" type="submit">Unpublish</button>
                    </form>
                <?php else: ?>
                    <form class="inline-form" method="post" action="<?= e(url('publish/editions/' . $ed['id'] . '/publish')) ?>">
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
